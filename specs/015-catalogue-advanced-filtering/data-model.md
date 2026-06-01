# Data Model: Catalogue Page & Advanced Filtering

**Branch**: `015-catalogue-advanced-filtering` | **Date**: 2026-06-01

---

## New Entities

### UserBook

Tracks a user's personal relationship with a specific book (collection status + favourite flag). Write operations (marking books) are out of scope for this feature; entity created for read display and filter only.

**Table**: `user_book`

| Field | PHP Type | DB Column | Nullable | Constraints |
|-------|----------|-----------|----------|-------------|
| `id` | `int` | `id SERIAL PK` | No | Auto-generated |
| `user` | `User` | `user_id UUID FK → "user".id` | No | ON DELETE CASCADE |
| `book` | `Book` | `book_id INT FK → book.id` | No | ON DELETE CASCADE |
| `status` | `UserBookStatus` | `status VARCHAR(30)` | No | Enum backed string |
| `isFavorite` | `bool` | `is_favorite BOOLEAN` | No | Default `false` |
| `createdAt` | `\DateTimeImmutable` | `created_at TIMESTAMP` | No | Set on construct |
| `updatedAt` | `\DateTimeImmutable` | `updated_at TIMESTAMP` | No | Updated via `#[ORM\PreUpdate]` lifecycle callback |

**Unique Constraint**: `UNIQUE (user_id, book_id)` — one `UserBook` per user per book.

**DB Indexes**:
- `idx_user_book_user_id` on `user_id` (filter by user for catalogue display)
- `idx_user_book_book_id` on `book_id` (batch-load by book IDs)

**Relationships**:
- `ManyToOne → User` (owning side, `nullable: false`, `ON DELETE CASCADE`)
- `ManyToOne → Book` (owning side, `nullable: false`, `ON DELETE CASCADE`)

---

### UserBookStatus (backed enum)

```php
// src/Entity/Enum/UserBookStatus.php
enum UserBookStatus: string
{
    case DANS_MA_COLLECTION     = 'dans-ma-collection';
    case A_ACHETER              = 'a-acheter';
    case A_LIRE                 = 'a-lire';
    case LU                     = 'lu';
    case PAS_DANS_MA_COLLECTION = 'pas-dans-ma-collection';
}
```

**Card mark mapping** (FR-026):
| Status | Card mark |
|--------|-----------|
| `DANS_MA_COLLECTION` | Green checkmark (owned) |
| `A_ACHETER` | Amber cart (wishlist) |
| `A_LIRE` | None (status only, no mark) |
| `LU` | None |
| `PAS_DANS_MA_COLLECTION` | None |

`isFavorite = true` → red heart mark (combinable with any status mark).

---

## New DTOs

### ActiveFilterState

**Location**: `src/Dto/ActiveFilterState.php`

Immutable readonly class. Hydrated by `CatalogueController::index()` from URL query params.

| Property | PHP Type | Default | URL param |
|----------|----------|---------|-----------|
| `sort` | `string` | `'note-desc'` | `sort` |
| `editors` | `int[]` | `[]` | `editors[]` |
| `paragraphMin` | `?int` | `null` | `paragraphMin` |
| `paragraphMax` | `?int` | `null` | `paragraphMax` |
| `collectionStatus` | `?string` | `null` | `collectionStatus` |
| `onlyFavorites` | `bool` | `false` | `onlyFavorites` |
| `hideModeration` | `bool` | `false` | `hideModeration` |
| `searchQuery` | `?string` | `null` | `q` |
| `page` | `int` | `1` | `page` |

**Static factory**: `ActiveFilterState::fromRequest(Request $request): self` — validates and coerces each param; invalid values fall back to defaults.

**Instance method**: `toUrlParams(): array` — returns associative array suitable for `$router->generate()` or `http_build_query()`.

**Instance method**: `countActiveFilters(): int` — counts criteria contributing to the FAB badge and "TOUT EFFACER (X)" count (includes search chip; excludes sort and page).

---

## Modified Entities

### Book (existing)

**Added inverse relationship** (no new DB column):

| Field | PHP Type | Relationship |
|-------|----------|--------------|
| `userBooks` | `Collection<int, UserBook>` | `OneToMany → UserBook`, `mappedBy: 'book'`, `cascade: ['remove']` |

### Editor (existing)

**Added** `repositoryClass: EditorRepository::class` to `#[ORM\Entity]` attribute (no schema change).

### Book/Card Twig Component (existing: `src/Twig/Components/Book/Card.php`)

**Added props**:

| Prop | PHP Type | Default | Meaning |
|------|----------|---------|---------|
| `isFavorite` | `bool` | `false` | Red heart mark |
| `isOwned` | `bool` | `false` | Green checkmark mark |
| `isWishlist` | `bool` | `false` | Amber cart mark |

---

## CatalogueFilterPanelComponent — LiveProp inventory

The `CatalogueFilterPanelComponent` (Symfony UX Live Component) exposes the following:

**Writable LiveProps** (draft state, mirroring `ActiveFilterState` fields):

| LiveProp | PHP Type | Default |
|----------|----------|---------|
| `sort` | `string` | `'note-desc'` |
| `selectedEditors` | `array` | `[]` (int[]) |
| `paragraphMin` | `?int` | `null` |
| `paragraphMax` | `?int` | `null` |
| `collectionStatus` | `?string` | `null` |
| `onlyFavorites` | `bool` | `false` |
| `hideModeration` | `bool` | `false` |
| `editorSearch` | `string` | `''` (debounced 300 ms) |

**Computed on each re-render** (not writable):

| Property | PHP Type | How computed |
|----------|----------|--------------|
| `expectedCount` | `int` | DQL COUNT query via `BookRepository::countFiltered()` |
| `visibleEditors` | `array` | `EditorRepository::findByNameSearch($this->editorSearch)` |

**LiveActions**:
- `applyFilters()` → builds `ActiveFilterState` from draft props → `RedirectResponse` to `/catalogue?...`
- `clearPanel()` → resets draft props to last applied state (called on panel close without apply, FR-025c)

---

## Database Schema

### New table: `user_book`

```sql
CREATE TABLE user_book (
    id          SERIAL       NOT NULL PRIMARY KEY,
    user_id     UUID         NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    book_id     INTEGER      NOT NULL REFERENCES book(id) ON DELETE CASCADE,
    status      VARCHAR(30)  NOT NULL DEFAULT 'dans-ma-collection',
    is_favorite BOOLEAN      NOT NULL DEFAULT false,
    created_at  TIMESTAMP    NOT NULL,
    updated_at  TIMESTAMP    NOT NULL,
    CONSTRAINT uniq_user_book UNIQUE (user_id, book_id)
);
CREATE INDEX idx_user_book_user_id ON user_book (user_id);
CREATE INDEX idx_user_book_book_id ON user_book (book_id);
```

---

## New / Modified Repository Methods

### BookRepository (MODIFIED — `src/Repository/BookRepository.php`)

| Method | Signature | SQL pattern |
|--------|-----------|-------------|
| `findParagraphBounds` | `(): array{min: int, max: int}` | `SELECT MIN(b.paragraphs), MAX(b.paragraphs) WHERE b.status = PUBLISHED` |
| `countFiltered` | `(ActiveFilterState $state): int` | Same DQL as `findFilteredPaginated` without `setFirstResult`/`setMaxResults`; returns COUNT |
| `findFilteredPaginated` | `(ActiveFilterState $state, int $perPage = 24): Paginator` | Full DQL with all filters: editors, paragraph range, sort, search, collection status (requires LEFT JOIN on `user_book` when authenticated) |

### EditorRepository (NEW — `src/Repository/EditorRepository.php`)

| Method | Signature | SQL pattern |
|--------|-----------|-------------|
| `findByNameSearch` | `(string $q, int $limit = 20): Editor[]` | `WHERE LOWER(e.name) LIKE LOWER(:q)` |
| `findWithBookCount` | `(): array` | `SELECT e, COUNT(b.id) AS bookCount GROUP BY e.id ORDER BY bookCount DESC` |

### UserBookRepository (NEW — `src/Repository/UserBookRepository.php`)

| Method | Signature | SQL pattern |
|--------|-----------|-------------|
| `findByUserAndBookIds` | `(User $user, int[] $bookIds): UserBook[]` | `WHERE ub.user = :user AND ub.book IN (:ids)` — batch-loads marks for one page |

---

## DQL filter query — join strategy

`findFilteredPaginated` joins:
```
book b
  LEFT JOIN b.contributions contrib
  LEFT JOIN contrib.contributor contributor
  LEFT JOIN b.editor e
  LEFT JOIN b.reviews r (for avg rating sort)
  LEFT JOIN b.userBooks ub WITH ub.user = :currentUser (only when authenticated)
WHERE
  b.status = PUBLISHED
  [AND e.id IN (:editors)]
  [AND b.paragraphs BETWEEN :paragraphMin AND :paragraphMax]
  [AND ub.status = :collectionStatus]   -- authenticated only
  [AND ub.isFavorite = true]            -- authenticated only
  [AND LOWER(b.title) LIKE :q OR LOWER(contributor.name) LIKE :q]
ORDER BY
  [sort field]
```

`countFiltered` uses the same WHERE clause with `SELECT COUNT(DISTINCT b.id)`.
