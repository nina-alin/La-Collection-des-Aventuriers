# Implementation Plan: Système de Rangs et Progression

**Branch**: `019-rank-progression-system` | **Date**: 2026-06-05 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/019-rank-progression-system/spec.md`

## Summary

Add rank-progression gamification layer to the platform. Backend partially exists (`ContributorLevel` entity, `ContributorLevelService`, event/listener wiring), but needs three targeted fixes: (1) count `CorrectionProposal::PUBLISHED` alongside `Suggestion::VALIDATED`, (2) dispatch `ContributionValidatedEvent` for CorrectionProposal approvals, (3) fire rank-up notification unconditionally. UI integration adds a colored rank badge across all identity zones (profile menu already wired as text-only; moderation queue, review items, public profile page need badge). Batch aggregate queries prevent N+1 in multi-user lists.

## Technical Context

**Language/Version**: PHP 8.2+

**Primary Dependencies**: Symfony 7.x LTS, Doctrine ORM, Twig, Bootstrap + custom SCSS tokens

**Storage**: PostgreSQL — `contributor_level` table (seeded via fixture, no migration required for this feature)

**Testing**: PHPUnit — existing test structure under `tests/Unit/`, `tests/Notification/`

**Target Platform**: Platform.sh (Linux server)

**Project Type**: Symfony web application

**Performance Goals**: N+1 queries forbidden in multi-user list rendering (aggregate batch queries required per FR-004 clarification)

**Constraints**: No cached rank field on User entity — always computed on-the-fly. No new infrastructure services.

**Scale/Scope**: 6 fixed rank tiers; all identity zones where User identity appears

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Check | Status |
|-----------|-------|--------|
| I — Complémentarité Stricte | Rank/gamification tied to encyclopedia contribution — no general forum, no news | ✅ PASS |
| II — Architecture Symfony LTS | Services hold business logic, controllers stay thin, Doctrine ORM only, DI throughout | ✅ PASS |
| III — Workflow Validation | Feature does not touch content publishing workflow — only observes it as a side-effect | ✅ PASS |
| IV — RBAC | Rank title hidden for ROLE_MODERATOR/ROLE_ADMIN (FR-005); routes protected with `#[IsGranted]` | ✅ PASS |
| V — Tests | PHPUnit tests required for all modified services, listeners, and new controller actions | **GATE: tests must ship** |

No violations requiring Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/019-rank-progression-system/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit-tasks)
```

### Source Code (repository root)

```text
src/
├── Event/
│   └── ContributionValidatedEvent.php          # MODIFY: WorkEntry $workEntry → string $title
├── EventListener/
│   ├── ContributionValidatedListener.php       # MODIFY: rank detection outside pref-check
│   └── RankUpListener.php                      # MODIFY: remove preference gate (FR-011)
├── Repository/
│   └── CorrectionProposalRepository.php        # MODIFY: add countPublishedByUser(), countBatchPublished()
│   └── SuggestionRepository.php                # MODIFY: add countBatchValidated()
│   └── ContributorLevelRepository.php          # MODIFY: add findAllSortedByThreshold()
├── Service/
│   ├── ContributorLevelService.php             # MODIFY: add CorrectionProposalRepository, computeRankBatch()
│   └── ModerationService.php                   # MODIFY: dispatch event for CorrectionProposal
└── Controller/
    └── ProfileController.php                   # MODIFY: add publicProfile() route

templates/
├── components/
│   └── _rank_badge.html.twig                   # NEW: shared rank badge macro
├── profile/
│   └── show.html.twig                          # NEW: public profile page
├── moderation/
│   └── dashboard.html.twig                     # MODIFY: add rank badge to suggestion/correction author rows
└── livre/
    └── _review_item.html.twig                  # MODIFY: add rank badge to review author

assets/styles/components/
└── _rank-badge.scss                            # NEW: rank badge CSS using color tokens

tests/
├── Unit/Service/
│   └── ContributorLevelServiceTest.php         # MODIFY: add CorrectionProposal counting tests
├── Notification/EventListener/
│   ├── ContributionValidatedListenerTest.php   # MODIFY: update for new event shape + rank-always-fires
│   └── RankUpListenerTest.php                  # MODIFY/NEW: verify notification ignores preference
└── Unit/Service/
    └── ModerationServiceTest.php               # NEW: verify CorrectionProposal approval dispatches event
```

## Complexity Tracking

> No violations.

---

## Phase 0: Research

*See [research.md](research.md)*

Key questions resolved during research:

1. **ContributionValidatedEvent refactoring strategy** — Replace `WorkEntry $workEntry` property with `string $title`; `ModerationService` extracts title before constructing event. Cleanest approach: no cross-entity dependency in the event class.

2. **CorrectionProposal status string** — `'PUBLISHED'` (raw string, confirmed in spec FR-001); not an enum, unlike `SuggestionStatus::VALIDATED`.

3. **Rank-up notification preference gate (FR-011)** — `RankUpListener` must dispatch unconditionally. `ContributionValidatedListener` must trigger rank detection _outside_ the `isEnabled(CONTRIBUTION_VALIDATED)` block. The `NotificationPreference::rankUp` field still exists but is ignored by the listener.

4. **Batch rank computation pattern** — Two aggregate queries (Suggestion VALIDATED + CorrectionProposal PUBLISHED per-user batch), merged in PHP, ranked using in-memory sorted level list. Single `findAllSortedByThreshold()` call reuses cached result.

5. **Public profile route** — `/profil/{pseudo}` — `ProfileController::publicProfile()`. Page shows: display name, rank badge (for ROLE_USER only), validated count, join date. No collection/wishlist data exposed publicly.

6. **"Liste des suggestions" identity zone** — Moderation queue `moderation/dashboard.html.twig`: each pending WorkEntry/CorrectionProposal row shows the author's pseudo. Rank badge added here with batch query per page load (aggregate over all visible authors).

7. **"Liste des contributeurs" identity zone** — Admin users list `admin/users.html.twig`: shows all users. Rank badge added to ROLE_USER rows (hidden for ROLE_MODERATOR/ROLE_ADMIN per FR-005), using batch query.

8. **Color token → rank mapping** (confirmed from `assets/styles/tokens/_colors.scss`):
   - Rank 1 Novice → `--parchemin` palette
   - Rank 2 Apprenti → `--mousse` palette
   - Rank 3 Chroniqueur confirmé → `--encre` palette
   - Rank 4 Archiviste → `--ambre` palette
   - Rank 5 Érudit → `--or` palette
   - Rank 6 Grand Sage → `--cuir` palette (brand primary — apex)

---

## Phase 1: Design & Contracts

*See [data-model.md](data-model.md) and [quickstart.md](quickstart.md)*

### Data Model Changes

No schema changes — `ContributorLevel` table already exists and is seeded. No new migrations required.

**Repository method additions only:**

```
CorrectionProposalRepository
  + countPublishedByUser(User): int           // WHERE status='PUBLISHED' AND author=user
  + countBatchPublished(array $users): array  // [userId => count] aggregate

SuggestionRepository
  + countBatchValidated(array $users): array  // [userId => count] aggregate WHERE status=VALIDATED

ContributorLevelRepository
  + findAllSortedByThreshold(): array         // ORDER BY threshold ASC (cached internally)
```

**Service changes:**

```
ContributorLevelService
  constructor: + CorrectionProposalRepository $correctionRepo
  - countByStatus(user, VALIDATED): int           // OLD: Suggestion only
  + countValidatedContributions(user): int        // NEW: Suggestion VALIDATED + CorrectionProposal PUBLISHED
  + computeRankBatch(array $users): array         // [userId => ?ContributorLevel]
  All existing public methods updated to use countValidatedContributions()
```

**Event change:**

```
ContributionValidatedEvent
  BEFORE: public WorkEntry $workEntry, public User $recipient
  AFTER:  public string $title, public User $recipient
```

**Listener changes:**

```
ContributionValidatedListener::__invoke()
  - rank detection moved OUTSIDE the isEnabled(CONTRIBUTION_VALIDATED) guard
  - rank detection always runs when event is received
  - CONTRIBUTION_VALIDATED notification dispatched only if pref enabled
  - RankUpEvent dispatched unconditionally if rank changed

RankUpListener::__invoke()
  - preference check block removed entirely
  - always dispatches NotificationMessage
```

**ModerationService change:**

```
ModerationService::approve()
  - add elseif branch for CorrectionProposal: dispatch ContributionValidatedEvent
    title = $entity->getWorkEntry()->getTitle()
    recipient = $entity->getAuthor()
  - guard: only dispatch if author !== null
```

### UI Contracts

**New Twig macro** `templates/components/_rank_badge.html.twig`:
```twig
{# params: level (ContributorLevel|null), compact (bool, default false) #}
{% if level is not null %}
  <span class="badge badge-rank badge-rank-{{ level.rankNumber }}" aria-label="Rang {{ level.name }}">
    {{ level.name }}
  </span>
{% endif %}
```

**New route** `GET /profil/{pseudo}` → `profile_public`:
- Renders `profile/show.html.twig`
- Variables: `profileUser`, `rankLevel` (?ContributorLevel), `validatedCount`, `isRankVisible`
- Public (no auth required, but rank hidden for ROLE_MODERATOR/ROLE_ADMIN)

**Modified templates** (badge injection points):
- `moderation/dashboard.html.twig` — batch load all author ranks before loop, inject badge per row
- `livre/_review_item.html.twig` — inject badge in review-author-meta div (rank passed from parent template via batch query)
- `profile/show.html.twig` — NEW: full rank badge prominent display
- `admin/users.html.twig` — batch load ranks, show badge in pseudo column for ROLE_USER rows

### Agent Context Update

CLAUDE.md updated to reference `specs/019-rank-progression-system/plan.md`.
