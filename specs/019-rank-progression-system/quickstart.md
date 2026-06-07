# Quickstart: Système de Rangs et Progression

## Prerequisites

- Feature 017 (notifications) merged and DB migrations run
- `ContributorLevel` table seeded (run fixtures if not: `php bin/console doctrine:fixtures:load --group=contributor_level --append`)

## Verify existing backend

```bash
# Confirm ContributorLevel rows exist
php bin/console doctrine:query:sql "SELECT rank_number, name, threshold FROM contributor_level ORDER BY rank_number"

# Expected: 6 rows (Novice 0, Apprenti 5, Chroniqueur confirmé 15, Archiviste 30, Érudit 60, Grand Sage 100)
```

## Run existing tests before changes

```bash
php bin/phpunit tests/Unit/Service/ContributorLevelServiceTest.php
php bin/phpunit tests/Notification/EventListener/ContributionValidatedListenerTest.php
```

Both should pass green. Use as regression baseline.

## Key files to modify

| File | Change |
|------|--------|
| `src/Event/ContributionValidatedEvent.php` | `WorkEntry $workEntry` → `string $title` |
| `src/Service/ModerationService.php` | Add CorrectionProposal branch in `approve()` |
| `src/Repository/CorrectionProposalRepository.php` | Add `countPublishedByUser()`, `countBatchPublished()` |
| `src/Repository/SuggestionRepository.php` | Add `countBatchValidated()` |
| `src/Repository/ContributorLevelRepository.php` | Add `findAllSortedByThreshold()` |
| `src/Service/ContributorLevelService.php` | Add `CorrectionProposalRepository`, `countValidatedContributions()`, `computeRankBatch()` |
| `src/EventListener/ContributionValidatedListener.php` | Move rank detection outside pref check |
| `src/EventListener/RankUpListener.php` | Remove preference gate |
| `assets/styles/components/_rank-badge.scss` | NEW: rank badge CSS |
| `templates/components/_rank_badge.html.twig` | NEW: rank badge macro |
| `templates/moderation/dashboard.html.twig` | Add batch rank + badge per author row |
| `templates/livre/_review_item.html.twig` | Add badge (requires controller change to pass ranks) |
| `src/Controller/ProfileController.php` | Add `publicProfile()` action |
| `templates/profile/show.html.twig` | NEW: public profile page |
| `templates/admin/users.html.twig` | Add badge for ROLE_USER rows |

## Rank badge CSS structure

```scss
// tokens used: --{color}-500 (background tint), --{color}-100 (text), etc.
.badge-rank { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 3px; font-size: .7rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; }
.badge-rank-1 { background: var(--parchemin-100); color: var(--parchemin-700); border: 1px solid var(--parchemin-300); }
.badge-rank-2 { background: var(--mousse-100); color: var(--mousse-700); border: 1px solid var(--mousse-300); }
.badge-rank-3 { background: var(--encre-100); color: var(--encre-700); border: 1px solid var(--encre-300); }
.badge-rank-4 { background: var(--ambre-100); color: var(--ambre-700); border: 1px solid var(--ambre-300); }
.badge-rank-5 { background: var(--or-100); color: var(--or-700); border: 1px solid var(--or-300); }
.badge-rank-6 { background: var(--cuir-100); color: var(--cuir-700); border: 1px solid var(--cuir-300); }
```

## Batch rank pattern (controller → template)

```php
// In controller action that renders a list of users:
$ranks = $this->contributorLevelService->computeRankBatch($users);
// $ranks = [userId => ?ContributorLevel]

return $this->render('template.html.twig', [
    'users' => $users,
    'ranksByUserId' => $ranks,
]);
```

```twig
{# In template loop: #}
{% set userRank = ranksByUserId[user.id.toRfc4122()] ?? null %}
{% include 'components/_rank_badge.html.twig' with {level: userRank} %}
```

## RankUp notification link

Target URL for rank-up notification: `/mes-suggestions` (route `suggestions_index`).
Set `targetUrl: $this->router->generate('suggestions_index')` in `RankUpListener` — requires injecting `RouterInterface` or `UrlGeneratorInterface`.

Currently `targetUrl: null` in `RankUpListener`. Must be updated to the suggestions dashboard path.

## Test coverage required (Constitution V)

```
tests/Unit/Service/ContributorLevelServiceTest.php
  - testComputeRankUsesCombinedCount()
  - testComputeRankBatchReturnsCorrectMap()

tests/Unit/Service/ModerationServiceTest.php (NEW)
  - testApproveWorkEntryDispatchesContributionValidatedEvent()
  - testApproveCorrectionProposalDispatchesContributionValidatedEvent()
  - testApproveWithNullAuthorDoesNotDispatch()

tests/Notification/EventListener/ContributionValidatedListenerTest.php
  - update event construction: new ContributionValidatedEvent('title', $user)
  - testRankDetectionRunsEvenWhenContributionPrefDisabled()

tests/Notification/EventListener/RankUpListenerTest.php
  - testAlwaysDispatchesRegardlessOfPreference()
  - testAlwaysDispatchesWhenNoPreferenceExists()
```
