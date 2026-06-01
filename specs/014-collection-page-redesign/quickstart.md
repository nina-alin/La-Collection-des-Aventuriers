# Quickstart: Refonte Page Collection

## Prerequisites

```bash
docker compose up -d   # PostgreSQL running
php bin/console doctrine:database:create --if-not-exists
```

## Implementation Order

### Step 1 — New entity + migration

```bash
# 1a. Create src/Entity/CollectionPublishingHistory.php (see data-model.md)
# 1b. Create src/Repository/CollectionPublishingHistoryRepository.php
# 1c. Add inverse OneToMany to src/Entity/Collection.php
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Step 2 — Repository methods

Add to `ContributionRepository`:
- `findRecurringByCollection(Collection): array`

Add to `CollectionRepository`:
- `getPublicationYearRange(Collection): array`
- `computeAverageRating(Collection): ?float`

Add to `CollectionPublishingHistoryRepository`:
- `findByCollection(Collection): array`

### Step 3 — Value objects + CollectionService

Create `src/Service/CollectionService.php` with `HeroMeta`, `ContributorPill`, `RecurringContributorsResult` (readonly classes in same or dedicated files under `src/ValueObject/`).

### Step 4 — Update CollectionController

Inject `CollectionService` and `CollectionPublishingHistoryRepository`. Compute all aggregates and pass to template:
```php
$heroMeta = $this->collectionService->getHeroMeta($collection);
$recurringContributors = $this->collectionService->getRecurringContributors($collection);
$publishingHistory = $this->collectionService->getPublishingHistory($collection);
```

### Step 5 — Stimulus controller

Create `assets/controllers/collection-sort_controller.js`:
```js
import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
  static targets = ['grid'];
  sortByVolume() { /* stable sort by data-volume ASC */ }
  sortByRating() { /* stable sort by data-rating DESC */ }
  #stableSort(arr, compareFn) { /* index-preserving sort */ }
}
```

Register in `assets/controllers.json` if using `@symfony/ux-stimulus-bundle`.

### Step 6 — Twig template rewrite

Replace `templates/collection/show.html.twig` entirely based on `design/pages/collection.html`.
Integrate Stimulus: `data-controller="collection-sort"` on `.tomes-grid`.

### Step 7 — Fixtures

Add `CollectionPublishingHistory` fixtures to `AppFixtures` for Loup Solitaire collection (≥ 2 entries to exercise the timeline).

### Step 8 — Tests

```bash
php bin/phpunit tests/Entity/CollectionPublishingHistoryTest.php
php bin/phpunit tests/Repository/ContributionRepositoryTest.php
```

## Verify

```bash
symfony server:start
# Navigate to /collections/loup-solitaire
# Check: hero macaron, meta pills, 6-hue grid, sort controls, publishing history, contributors
```

## Key Files

| File | Role |
|------|------|
| `design/pages/collection.html` | Visual source of truth — all CSS classes, colors, layout |
| `src/Entity/CollectionPublishingHistory.php` | New entity |
| `src/Service/CollectionService.php` | Aggregation (hero meta, contributors) |
| `src/Repository/ContributionRepository.php` | DQL contributors query |
| `assets/controllers/collection-sort_controller.js` | Client-side sort |
| `templates/collection/show.html.twig` | Full page template |
