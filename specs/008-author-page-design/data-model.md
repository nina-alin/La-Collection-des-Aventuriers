# Data Model: Intégration du Design de la Page Auteur

**Feature**: `008-author-page-design` | **Date**: 2026-05-26

## Schema Changes

**None.** No new entities, no migrations. This feature is a read-only view over the existing data model.

## Entities Used

### Contributor

| Field | Type | Nullable | Used in |
|-------|------|----------|---------|
| `firstName` | string(100) | no | Nameplate, vitals, portrait alt |
| `lastName` | string(100) | no | Nameplate, vitals, portrait alt |
| `pseudo` | string(100) | yes | Vitals row (hidden if null) |
| `slug` | string(255) | no | Route param, 404 lookup |
| `biography` | text | yes | Bio card (hidden if null) |
| `nationality` | string(2) | yes | Vitals row |
| `birthDate` | date | yes | Life dates block (block hidden if null) |
| `deathDate` | date | yes | Life dates block death cell |
| `portraitImage` | string(255) | yes | Portrait frame (placeholder if null) |
| `contributions` | OneToMany → Contribution | — | Loaded via JOIN |

### Contribution

| Field | Type | Nullable | Used in |
|-------|------|----------|---------|
| `book` | ManyToOne → Book | no | Bibliography grid |
| `role` | ContributionRole enum | no | Filter: `Author` only |
| `details` | string(255) | yes | Not displayed on author page |

### Book

| Field | Type | Nullable | Used in |
|-------|------|----------|---------|
| `title` | string(255) | no | Card title |
| `slug` | string(255) | yes | Card link href |
| `saga` | string(255) | yes | Saga pills, card color, card reference |
| `volumeNumber` | smallint | yes | Card short reference (e.g. "LS nº1") |
| `frenchPublicationYear` | smallint | yes | Card edition info, chrono sort |
| `editionInfo` | string(255) | yes | Card edition info |
| `editor` | ManyToOne → Editor | no | Card editor name |
| `collection` | ManyToOne → CollectionEntity | yes | Card collection name |
| `status` | BookStatus enum | no | Displayed as badge on book card (FR-010) |

### Editor

| Field | Type | Used in |
|-------|------|---------|
| `name` | string(255) | Card editor line |

### CollectionEntity (Book.collection)

| Field | Type | Used in |
|-------|------|---------|
| `name` | string(255) | Card collection line |

### CollectionEntry (Future — Placeholder)

Not yet implemented. The authenticated user's ownership status for each book is displayed as a static placeholder:
- All books → "NON POSSÉDÉ" footer text (grey)
- The `is_granted('IS_AUTHENTICATED_FULLY')` Twig check gates the visibility of FR-008 counter

## Repository Contract

### `ContributorRepository::findContributionsBySlug()`

```php
/**
 * Loads contributor with all Author-role contributions (eager JOIN on book+editor).
 * Applies saga slug filter and sort order in PHP.
 * Returns null if slug not found.
 *
 * @param string      $slug        Contributor slug
 * @param string|null $sagaFilter  Slugified saga name (e.g. 'loup-solitaire'), null = no filter
 * @param string      $sortOrder   'chrono' (default) | 'alpha'
 *
 * @return array{
 *   contributor: Contributor,
 *   filteredContributions: list<Contribution>,
 *   sagaGroups: list<array{slug: string, name: string, count: int}>,
 *   totalCount: int
 * }|null
 */
public function findContributionsBySlug(
    string $slug,
    ?string $sagaFilter,
    string $sortOrder = 'chrono'
): ?array
```

**Behavior**:
- `sagaFilter = null` → returns all contributions (sorted)
- `sagaFilter = 'unknown-saga'` → empty filter result → falls back to all contributions, pill "TOUT" active
- `sagaGroups` always reflects the full (unfiltered) list — accurate pill counts
- `totalCount` = total contributions count (for `SA BIBLIOGRAPHIE · N fiches` header)

## State Transitions

No mutable state in this feature. All paths are read-only.

## Validation Rules

None (read-only page). 404 on unknown slug is the only "validation" — handled by controller.
