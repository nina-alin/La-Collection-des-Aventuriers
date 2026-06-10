# Data Model: 024-createurs-page

> No new entities or migrations. All entities pre-exist.

## Existing Entities Used

### Contributor (`App\Entity\Contributor`)
| Field | Type | Notes |
|-------|------|-------|
| id | uuid (v7) | PK |
| firstName | string(100) | |
| lastName | string(100) | ORDER BY for A→Z sort |
| pseudo | string(100)\|null | |
| slug | string(255) unique | URL identifier |
| biography | text\|null | Twig `line-clamp: 3` |
| nationality | string(2)\|null | ISO-2 country code |
| birthDate | date\|null | Period filter lower bound |
| deathDate | date\|null | Period filter upper bound |
| portraitImage | string(255)\|null | Null → avatar initials |
| contributions | OneToMany → Contribution[] | Eager-loaded for card data |

### Contribution (`App\Entity\Contribution`)
| Field | Type | Notes |
|-------|------|-------|
| contributor | ManyToOne → Contributor | |
| book | ManyToOne → Book | |
| role | enum ContributionRole | Author / Illustrator / Traductor |

### ContributionRole (enum)
- `Author` — filter key `auteur`
- `Illustrator` — filter key `illustrateur`
- `Traductor` — filter key `traducteur`

### Book (`App\Entity\Book`)
Relevant fields: `collection` (ManyToOne → Collection), `reviews` (OneToMany → Review), `status` (BookStatus enum — filter to PUBLISHED).

### Collection (`App\Entity\Collection`)
Relevant fields: `id` (uuid v7), `nom` (string). Top-2 collections per contributor = top 2 by `COUNT(DISTINCT book.id)` where contributor contributed; tie-broken by `col.id` DESC (UUID v7 chronological).

### Review (`App\Entity\Review`)
Relevant fields: `score` (smallint 1–10), `book` (ManyToOne). `AVG(score)` across all books of a contributor = contributor's average rating.

---

## New DTOs

### `App\Dto\ContributorFilterState` (new)
```
readonly class ContributorFilterState {
    role: string          // 'tous'|'auteur'|'traducteur'|'illustrateur'  default 'tous'
    letter: ?string       // 'A'…'Z' or null
    collectionIds: int[]  // collection IDs from panel
    periodMin: ?int       // year (birthDate >=)
    periodMax: ?int       // year (deathDate <= or birthDate <=)
    nationality: ?string  // ISO-2 or text search
    bookCountRange: ?string // '1-5'|'6-15'|'16-30'|'30+'
    onlyFollowed: bool    // hidden for anonymous users
    sort: string          // 'az'|'ouvrages'|'note'  default 'az'
    page: int             // >= 1, resets to 1 on filter/sort change
}
```
Methods: `static fromRequest(Request): self`, `toUrlParams(): array`.

---

## New Service

### `App\Service\ContributeurService` (new)
- `getPaginatedResults(ContributorFilterState, ?User $user): Paginator` — paginated (12/page) filtered contributors
- `getAvailableLetters(ContributorFilterState): string[]` — e.g. `['A','B','D',…]`
- `getCardDataBatch(array $contributorIds): array` — returns map `[id => [bookCount, averageScore, roles[], topCollections[]]]`

---

## New Twig Component

### `App\Twig\Components\Contributeur\FilterPanelComponent` (LiveComponent, new)
LiveProps mirror `ContributorFilterState` panel fields (collectionIds, periodMin/Max, nationality, bookCountRange, onlyFollowed). Draft state pattern (apply on explicit "Appliquer" click) — same as `Catalogue\FilterPanelComponent`. `applyFilters()` LiveAction returns `RedirectResponse` to `/createurs?...`.

---

## Query Patterns

### Paginated filtered list
```sql
SELECT DISTINCT c
FROM Contributor c
LEFT JOIN c.contributions contrib
LEFT JOIN contrib.book b WITH b.status = 'PUBLISHED'
LEFT JOIN b.collection col
WHERE
  [role filter: contrib.role = :role]
  AND [letter filter: UPPER(SUBSTRING(c.lastName,1,1)) = :letter]
  AND [collection filter: col.id IN (:collectionIds)]
  AND [period filter: YEAR(c.birthDate) >= :periodMin AND ...]
  AND [nationality filter: c.nationality = :nationality]
  AND [bookCount filter: (subquery COUNT) BETWEEN :min AND :max]
ORDER BY
  [az: c.lastName ASC] | [ouvrages: bookCount DESC] | [note: avgScore DESC]
```

### Available letters
```sql
SELECT DISTINCT UPPER(SUBSTRING(c.lastName,1,1)) AS letter
FROM Contributor c
[same JOINs and WHERE as above]
ORDER BY letter ASC
```

### Top 2 collections per contributor (batch, by IDs)
```sql
SELECT c.id, col.id, col.nom, COUNT(DISTINCT b.id) AS cnt
FROM Contributor c
JOIN c.contributions contrib
JOIN contrib.book b WITH b.status = 'PUBLISHED'
JOIN b.collection col
WHERE c.id IN (:ids)
GROUP BY c.id, col.id, col.nom
ORDER BY c.id ASC, cnt DESC, col.id DESC
```
PHP post-processing: take first 2 per contributor ID.

### Card data batch (bookCount + avgScore + roles)
```sql
SELECT c.id,
       COUNT(DISTINCT b.id) AS bookCount,
       AVG(r.score) AS avgScore,
       GROUP_CONCAT(DISTINCT contrib.role) AS roles
FROM Contributor c
LEFT JOIN c.contributions contrib
LEFT JOIN contrib.book b WITH b.status = 'PUBLISHED'
LEFT JOIN b.reviews r
WHERE c.id IN (:ids)
GROUP BY c.id
```
