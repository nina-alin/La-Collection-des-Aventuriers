# Data Model: Landing Page Publique Dynamique

## New Database Entities

**None.** This feature is read-only. All data comes from existing entities via existing repositories.

---

## New DTOs

### `LandingStatsDto`

```php
// src/Dto/LandingStatsDto.php
readonly class LandingStatsDto
{
    public function __construct(
        public int $totalBooks,
        public int $totalUsers,
        public int $newThisWeek,
    ) {}
}
```

**Source of truth**:
- `totalBooks` ← `BookRepository::countPublished()`
- `totalUsers` ← `UserRepository::countActive()`
- `newThisWeek` ← `BookRepository::countPublishedSince(new \DateTimeImmutable('-7 days'))`

---

### `MarqueeItemDto`

```php
// src/Dto/MarqueeItemDto.php
readonly class MarqueeItemDto
{
    public function __construct(
        public string $name,
        public string $type,      // 'book' | 'author' | 'collection'
        public string $url,       // e.g. /livre/slug, /authors/slug, /collections/slug
        public string $subtitle,  // e.g. "Livre · 1982", "Auteur · 12 œuvres"
        public string $initials,  // short label for the cover tile (≤8 chars)
        public string $colorClass, // CSS class: bg-cuir | bg-mousse | bg-encre | bg-sang | bg-or | bg-grimoire | is-author
    ) {}
}
```

**Construction rules per type:**

| Type | `name` | `url` | `subtitle` | `initials` | `colorClass` |
|------|--------|-------|------------|------------|-------------|
| book | `Book::getTitle()` | `/livre/{slug}` | `"Livre · {year}"` | first word of title, max 8 chars | round-robin from book color list |
| author | `"{firstName} {lastName}"` or `pseudo` | `/authors/{slug}` | `"Auteur · {N} œuvres"` | initials of full name | `is-author` |
| collection | `Collection::getNom()` | `/collections/{slug}` | `"Collection · {N} tomes"` | first word of nom, max 8 chars | `bg-grimoire` |

---

## Existing Entities Used (read-only)

| Entity | Fields accessed |
|--------|----------------|
| `Book` | `title`, `slug`, `publicationYear`, `status` |
| `Contributor` | `firstName`, `lastName`, `pseudo`, `slug`, contributions count |
| `Collection` | `nom`, `slug`, books count |
| `User` | (count only via `countActive()`) |

---

## State Transitions

None — this feature has no state-mutating operations.
