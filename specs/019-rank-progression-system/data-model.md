# Data Model: Système de Rangs et Progression

## No Schema Changes Required

`ContributorLevel` table already exists and is seeded. No Doctrine migration needed.

## Existing Entity: ContributorLevel

```php
ContributorLevel {
    id: int (PK, auto)
    name: string(100)
    rankNumber: smallint (unique)
    threshold: int
}
```

**Seeded data** (authoritative — examples in user stories are illustrative only):

| rankNumber | name                 | threshold |
|-----------|----------------------|-----------|
| 1         | Novice               | 0         |
| 2         | Apprenti             | 5         |
| 3         | Chroniqueur confirmé | 15        |
| 4         | Archiviste           | 30        |
| 5         | Érudit               | 60        |
| 6         | Grand Sage           | 100       |

## Validation Rules

- `threshold <= count` → rank matches (highest threshold ≤ count wins)
- `count` = validated `Suggestion` (status=VALIDATED) + published `CorrectionProposal` (status='PUBLISHED') for the user
- If count < threshold of rank 1 (Novice, threshold=0): impossible — Novice threshold is 0, so every user is at least Novice
- Rank is hidden (not displayed) if user has ROLE_MODERATOR or ROLE_ADMIN

## Repository Additions

### CorrectionProposalRepository

```php
public function countPublishedByUser(User $user): int
// SELECT COUNT(id) WHERE author=user AND status='PUBLISHED'

public function countBatchPublished(array $users): array
// [userId => count] via GROUP BY author_id
// SELECT author_id, COUNT(id) WHERE author_id IN (...) AND status='PUBLISHED' GROUP BY author_id
```

### SuggestionRepository

```php
public function countBatchValidated(array $users): array
// [userId => count] via GROUP BY user_id
// SELECT user_id, COUNT(id) WHERE user_id IN (...) AND status='VALIDATED' GROUP BY user_id
```

### ContributorLevelRepository

```php
public function findAllSortedByThreshold(): array
// SELECT * ORDER BY threshold ASC
// Used for in-memory rank computation in batch mode
```

## Service Interface Changes

### ContributorLevelService

New private method replaces direct `SuggestionRepository::countByStatus()` calls:

```php
private function countValidatedContributions(User $user): int
// = SuggestionRepository::countByStatus(user, VALIDATED)
//   + CorrectionProposalRepository::countPublishedByUser(user)
```

New public method for multi-user lists:

```php
public function computeRankBatch(array $users): array
// Returns [userId => ?ContributorLevel]
// Uses countBatchValidated + countBatchPublished + findAllSortedByThreshold
// Constant query count (2 aggregate + 1 level fetch), regardless of user count
```

## Event Change

```php
// BEFORE
final readonly class ContributionValidatedEvent {
    public function __construct(
        public WorkEntry $workEntry,
        public User $recipient,
    ) {}
}

// AFTER
final readonly class ContributionValidatedEvent {
    public function __construct(
        public string $title,
        public User $recipient,
    ) {}
}
```

## State Transitions (rank progression)

```
User validates contribution
    → ModerationService::approve(WorkEntry|CorrectionProposal)
    → entity.status = 'PUBLISHED'
    → if author !== null → dispatch ContributionValidatedEvent(title, author)
    → ContributionValidatedListener::__invoke()
        → computeRank(user) BEFORE bus dispatch (= old rank)
        → if pref enabled → bus.dispatch(NotificationMessage[CONTRIBUTION_VALIDATED])
        → computeRank(user) AFTER bus dispatch (= new rank, count now includes this one)
        → if newRank !== oldRank → dispatch RankUpEvent(user, newRank)
    → RankUpListener::__invoke()
        → always → bus.dispatch(NotificationMessage[RANK_UP, link=/mes-suggestions])
```

**Note**: `computeRank()` is called twice — before and after the contribution_validated notification is queued. The Messenger bus is async; the notification message is only enqueued, not consumed, during the event handling. The `countValidatedContributions()` query runs against committed DB state (flush happened in `ModerationService::approve()` before event dispatch). The "before" rank uses the count before flush; the "after" rank uses the count after flush. This works because `flush()` runs before `dispatch()` in `ModerationService::approve()`.

**Edge case — multiple thresholds skipped**: if count jumps from 0 to 60 in one approval (impossible in practice but mathematically possible), `computeRank()` returns the highest matching level. Only one `RankUpEvent` is fired for the final rank — intermediate ranks are ignored (confirmed in spec clarification 2026-06-05).
