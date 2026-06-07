# Public API Contracts

Controller: `src/Controller/PublicApiController.php`

All endpoints:
- Require no authentication (`PUBLIC_ACCESS` in `security.yaml`)
- Return `application/json`
- Are read-only (`GET`)
- Return HTTP 200 on success, 500 on unexpected server error

---

## GET /api/public/stats

Returns aggregated platform statistics for the landing page stats pill and animated counters.

### Response (200)

```json
{
  "total_books": 1247,
  "total_users": 389,
  "new_this_week": 12
}
```

| Field | Type | Source |
|-------|------|--------|
| `total_books` | `int` | `BookRepository::countPublished()` |
| `total_users` | `int` | `UserRepository::countActive()` |
| `new_this_week` | `int` | `BookRepository::countPublishedSince(now - 7 days)` |

### Error behavior

If the database is unavailable, the controller catches the exception and returns:
```json
{"error": "unavailable"}
```
with HTTP 503. The frontend hides the stats zone and displays `--` for counters.

---

## GET /api/public/marquee

Returns a shuffled list of popular entities (books, authors, collections) for the horizontal marquee band.

### Response (200)

```json
[
  {
    "name": "Le Sorcier de la Montagne de Feu",
    "type": "book",
    "url": "/livre/le-sorcier-de-la-montagne-de-feu",
    "subtitle": "Livre · 1982",
    "initials": "Sorcier",
    "color_class": "bg-cuir"
  },
  {
    "name": "Steve Jackson",
    "type": "author",
    "url": "/authors/steve-jackson",
    "subtitle": "Auteur · 18 œuvres",
    "initials": "SJ",
    "color_class": "is-author"
  },
  {
    "name": "Défis Fantastiques",
    "type": "collection",
    "url": "/collections/defis-fantastiques",
    "subtitle": "Collection · 59 tomes",
    "initials": "Défis F.",
    "color_class": "bg-grimoire"
  }
]
```

| Field | Type | Notes |
|-------|------|-------|
| `name` | `string` | Display name |
| `type` | `"book"\|"author"\|"collection"` | Entity type |
| `url` | `string` | Absolute path (no host) |
| `subtitle` | `string` | Secondary label shown below name |
| `initials` | `string` | Short label for the visual tile (≤ 8 chars) |
| `color_class` | `string` | CSS class applied to `.marquee-cover`: `bg-cuir`, `bg-mousse`, `bg-encre`, `bg-sang`, `bg-or`, `bg-grimoire`, or `is-author` |

### Payload size

Targets 25–30 items (10 books + 10 authors + 10 collections, shuffled). The CSS animation guarantees a full viewport fill without JS duplication.

### Empty list behavior

If all three repositories return empty results (e.g., fresh install), the response is `[]`. The frontend hides the `.hero-marquee` container (`display: none`).

### Error behavior

Same as `/api/public/stats`: returns `{"error": "unavailable"}` with HTTP 503; frontend hides the marquee container.
