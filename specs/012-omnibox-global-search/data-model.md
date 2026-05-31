# Data Model: Omnibox Global Search

**Phase 1 Output** | Branch: `012-omnibox-global-search`

---

## Entities Used (existing — no schema changes)

### Book
Queried for dynamic search results and popular items.

| Field | Type | Used for |
|-------|------|---------|
| `slug` | `string` | Navigation URL `/livre/:slug` |
| `title` | `string` | Display title, search match |
| `isbn` | `string\|null` | Reference metadata |
| `frenchPublicationYear` | `int\|null` | Metadata subtitle |
| `coverImage` | `string\|null` | Thumbnail (fallback: generic book icon) |
| `contributions` | `Collection<Contribution>` | Author name for metadata |
| `editor` | `Editor\|null` | Editor name for metadata |
| `status` | `BookStatus` | Filter: only `PUBLISHED` books returned |

### Collection (GameCollection)
Queried for dynamic search results.

| Field | Type | Used for |
|-------|------|---------|
| `slug` | `string` | Navigation URL `/collection/:slug` |
| `nom` | `string` | Display title, search match |
| `books` | `Collection<Book>` | Count of tomes (metadata) |
| `statut` | `StatutCollection` | Optional display |

### Contributor
Queried for dynamic search results and popular items.

| Field | Type | Used for |
|-------|------|---------|
| `slug` | `string` | Navigation URL `/auteur/:slug` |
| `firstName` + `lastName` | `string` | Display name, search match |
| `pseudo` | `string\|null` | Alternate search match |
| `portraitImage` | `string\|null` | Avatar (fallback: initials) |
| `contributions` | `Collection<Contribution>` | Count of fiches (metadata) |

---

## New DTOs (no DB schema)

### SearchResultItem (PHP DTO)

```php
// App\Dto\Search\SearchResultItem
readonly class SearchResultItem
{
    public function __construct(
        public string $type,          // 'livre' | 'collection' | 'auteur'
        public string $slug,
        public string $title,
        public string $subtitle,      // formatted metadata per type
        public ?string $thumbnailUrl, // null = use type-based fallback
        public ?string $initials,     // auteur only, for avatar fallback
        public ?string $avatarColor,  // auteur only, CSS class for avatar bg
    ) {}
}
```

### SearchResponse (PHP DTO)

```php
// App\Dto\Search\SearchResponse
readonly class SearchResponse
{
    public function __construct(
        /** @var SearchResultItem[] */
        public array $results,
        /** @var SearchResultItem[] */
        public array $popular,
    ) {}
}
```

---

## New Repository Methods

### BookRepository

```php
// Search: finds published books matching title (case-insensitive LIKE)
public function findForGlobalSearch(string $q, int $limit = 5): array

// Popular: books with most reviews (published only)
public function findMostPopular(int $limit = 4): array
```

### CollectionRepository

```php
// Search: finds collections matching nom (case-insensitive LIKE)
public function findForGlobalSearch(string $q, int $limit = 3): array

// Popular: collections with most books
public function findMostPopular(int $limit = 2): array
```

### ContributorRepository

```php
// Search: finds contributors matching firstName OR lastName (case-insensitive LIKE)
public function findForGlobalSearch(string $q, int $limit = 3): array

// Popular: contributors with most contributions
public function findMostPopular(int $limit = 2): array
```

---

## Session State (JS only — no DB)

### Recent Searches (Stimulus controller state)

```js
// Managed entirely in Stimulus controller memory
this._history = []  // string[], max 5 entries, FIFO dedup-by-move-to-front
```

Policy:
- Max 5 distinct entries
- Identical query → remove from position, prepend to front (no duplicate)
- 6th distinct query → evicts oldest (index 4)
- Reset on page reload (in-memory only)

---

## Result Distribution

Dynamic search: max **8 total** items across all types:
- Books: up to 5 (highest relevance, most results expected)
- Collections: up to 3
- Contributors: up to 3
- Enforced server-side: `GlobalSearchService` caps at 8 merged

Popular (pre-saisie): max **4 total** items across all types.
- Candidates: up to 4 books + 2 collections + 2 contributors (8 total from repos)
- Merge strategy: **books-first** — `array_merge($books, $collections, $contributors)`, then `array_slice(0, 4)`
- Rationale: deterministic, reflects catalog reality (books dominate). If fewer than 4 books available, slots fill from collections then contributors.

---

## URL Patterns

| Entity | URL | Controller |
|--------|-----|-----------|
| Book | `/livre/{slug}` | `BookController::show` (existing) |
| Collection | `/collection/{slug}` | `CollectionController::show` (existing) |
| Contributor | `/auteur/{slug}` | `ContributorController::show` (existing) |
| Catalogue (no result / Enter) | `/catalogue?q={query}` | `DefaultController` (existing) |

---

## No Schema Migration Required

All entities used are existing. No new Doctrine entity, no migration, no infrastructure change.
