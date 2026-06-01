# Data Model: Refonte Page Collection

## Entities Modified / Created

### NEW: `CollectionPublishingHistory`

| Field | Type | Nullable | Constraints |
|-------|------|----------|-------------|
| `id` | UUID (Symfony UID v4) | No | PK |
| `collection` | FK → `Collection` | No | onDelete=CASCADE |
| `editor` | FK → `Editor` | **Yes** | onDelete=SET NULL (null = éditeur inconnu) |
| `startYear` | smallint | No | — |
| `endYear` | smallint | Yes | — |
| `editionName` | varchar(255) | Yes | — |
| `details` | text | Yes | — |

**Table**: `collection_publishing_history`

**Indexes**: `(collection_id, start_year, id)` for ordered fetch.

**Repository**: `CollectionPublishingHistoryRepository` — method `findByCollection(Collection): array` returns entries `ORDER BY startYear ASC, id ASC`.

---

### MODIFIED: `Collection` (no schema change)

No new columns. The existing `editeurHistorique` (string field) is **not** removed — it remains as legacy data. `CollectionPublishingHistory` is an additive relationship.

**New inverse side**: Add `OneToMany` to `CollectionPublishingHistory` on `Collection` (optional, for eager load on show page).

---

### UNCHANGED: `Editor`, `Book`, `Contribution`, `Contributor`, `Review`

No schema modifications.

---

## New Repository Methods

### `ContributionRepository`

```php
/**
 * Returns one row per (Contributor, ContributionRole) pair across all tomes
 * of the collection, regardless of current page.
 *
 * @return array<int, array{contributor: Contributor, role: ContributionRole, count: int}>
 */
public function findRecurringByCollection(Collection $collection): array
```

DQL skeleton:
```
SELECT IDENTITY(co.contributor) AS contributorId,
       co.role,
       COUNT(co.id) AS tomeCount
FROM App\Entity\Contribution co
JOIN co.book b
WHERE b.collection = :collection
  AND co.deletedAt IS NULL
  AND b.deletedAt IS NULL
GROUP BY co.contributor, co.role
ORDER BY tomeCount DESC
```
Hydration: `getResult()` followed by second query to load Contributor objects by collected IDs (or use PARTIAL/JOIN hydration to avoid N+1).

---

### `CollectionRepository`

```php
/** Returns [min => ?int, max => ?int] of frenchPublicationYear across all books. */
public function getPublicationYearRange(Collection $collection): array

/** Returns AVG of per-book average scores, or null if no reviews exist. */
public function computeAverageRating(Collection $collection): ?float
```

---

### `CollectionPublishingHistoryRepository`

```php
/** Returns publishing history entries sorted by startYear ASC, id ASC. */
public function findByCollection(Collection $collection): array
```

---

## Service: `CollectionService`

Aggregates data for the collection show page. Injected into `CollectionController`.

```php
class CollectionService
{
    public function __construct(
        private CollectionRepository $collectionRepo,
        private ContributionRepository $contributionRepo,
        private CollectionPublishingHistoryRepository $historyRepo,
    ) {}

    public function getHeroMeta(Collection $collection): HeroMeta;
    public function getRecurringContributors(Collection $collection): RecurringContributorsResult;
    public function getPublishingHistory(Collection $collection): array; // CollectionPublishingHistory[]
}
```

**Value objects** (readonly classes, no ORM mapping):

```php
readonly class HeroMeta {
    public function __construct(
        public ?int $yearMin,
        public ?int $yearMax,
        public ?float $averageRating,
    ) {}
}

readonly class ContributorPill {
    public function __construct(
        public Contributor $contributor,
        public ContributionRole $role,
        public int $count,
        public string $initials,
    ) {}
}

readonly class RecurringContributorsResult {
    /** @param ContributorPill[] $pills */
    public function __construct(
        public int $uniqueCount,
        public array $pills,
    ) {}
}
```

---

## Hue Mapping (PHP → Twig `data-hue`)

```php
private const HUES = ['forest','storm','ember','amber','ink','gold'];

public static function hueForVolume(?int $volumeNumber): string
{
    return self::HUES[($volumeNumber ?? 0) % 6];
}
```

Exposed as a Twig helper or computed in the template via a simple macro / filter registered in a Twig extension (or inlined as `['forest','storm','ember','amber','ink','gold'][(book.volumeNumber ?? 0) % 6]`).

---

## State Transitions

None. This ticket is read-only display. No content state machines affected.

---

## Validation Rules

`CollectionPublishingHistory`:
- `startYear`: `#[Assert\NotBlank]`, `#[Assert\Range(min: 1800, max: 2100)]`
- `endYear`: `#[Assert\Range(min: 1800, max: 2100)]` (when not null)
- `endYear` ≥ `startYear` when both set (custom `#[Assert\Expression]` or class-level constraint)
