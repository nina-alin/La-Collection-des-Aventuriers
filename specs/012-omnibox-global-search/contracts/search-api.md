# Contract: Search API

**Type**: Internal JSON REST endpoint (consumed by Stimulus controller only)

---

## GET /api/search

Returns dynamic search results matching a query string.

### Request

```
GET /api/search?q={query}
Authorization: authenticated session (IS_AUTHENTICATED_REMEMBERED)
Accept: application/json
```

| Parameter | Required | Type | Constraints |
|-----------|----------|------|-------------|
| `q` | yes | string | 1–100 chars, trimmed |

### Response 200

```json
{
  "results": [
    {
      "type": "livre",
      "slug": "ldvelh-no-1-le-sorcier-de-la-montagne-de-feu",
      "title": "Le Sorcier de la Montagne de Feu",
      "subtitle": "002 · 1984 · Steve Jackson",
      "thumbnailUrl": "/uploads/covers/book-1.jpg",
      "initials": null,
      "avatarColor": null
    },
    {
      "type": "auteur",
      "slug": "steve-jackson",
      "title": "Steve Jackson",
      "subtitle": "auteur · 14 fiches",
      "thumbnailUrl": null,
      "initials": "SJ",
      "avatarColor": "cuir"
    },
    {
      "type": "collection",
      "slug": "defis-fantastiques",
      "title": "Défis Fantastiques",
      "subtitle": "collection · 59 tomes · Steve Jackson",
      "thumbnailUrl": null,
      "initials": null,
      "avatarColor": null
    }
  ]
}
```

### Response schema

```
results[]:
  type          string   "livre" | "collection" | "auteur"
  slug          string   URL-ready slug
  title         string   Display name
  subtitle      string   Formatted metadata (see subtitle formats below)
  thumbnailUrl  string|null  Absolute path from /uploads/ or null
  initials      string|null  2-char initials (auteur only)
  avatarColor   string|null  CSS modifier class: "cuir"|"mousse"|"encre"|"sang"|"or" (auteur only)
```

**Subtitle formats**:
- livre: `{isbn_or_ref} · {year} · {author_or_editor}` — each part omitted if null
- collection: `collection · {N} tomes · {main_author}`
- auteur: `auteur · {N} fiches`

### Result limits

- Max 8 items total in `results[]`
- Server-side cap: 5 books + 3 collections + 3 contributors — merged and truncated to 8
- Empty query (`q` absent or blank) → `results: []`

### Error responses

| Code | Condition |
|------|-----------|
| 400 | `q` exceeds 100 chars |
| 401 | Not authenticated → redirects to login (standard Symfony behavior) |
| 500 | Unexpected error → `{"results": []}` (silent degradation per spec) |

---

## GET /api/search/popular

Returns pre-saisie "Souvent Consultés" items (no query parameter).

### Request

```
GET /api/search/popular
Authorization: authenticated session (IS_AUTHENTICATED_REMEMBERED)
Accept: application/json
```

### Response 200

```json
{
  "popular": [
    {
      "type": "livre",
      "slug": "ldvelh-no-1",
      "title": "Le Sorcier de la Montagne de Feu",
      "subtitle": "002 · 1984 · Steve Jackson",
      "thumbnailUrl": "/uploads/covers/book-1.jpg",
      "initials": null,
      "avatarColor": null
    }
  ]
}
```

- Max 4 items total
- Silent degradation: error → `{"popular": []}`

---

## Security

Both endpoints require `IS_AUTHENTICATED_REMEMBERED` (enforced via `#[IsGranted]`).
No CSRF token required (GET requests, read-only, no state mutation).

---

## Caching

No server-side response caching. Results are live queries.
Client-side: the Stimulus controller does NOT cache — each debounce fires a fresh fetch.
