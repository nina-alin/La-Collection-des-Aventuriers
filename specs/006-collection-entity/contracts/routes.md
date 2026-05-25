# Route Contracts: Entité Collection

**Feature**: `006-collection-entity` | **Date**: 2026-05-25

## Routes

### GET /collections/{slug}

| Item | Value |
|------|-------|
| Route name | `app_collection_show` |
| Controller | `App\Controller\CollectionController::show` |
| Auth | `PUBLIC_ACCESS` (no authentication required) |
| Parameters | `slug: string` (path) |
| Query params | `page: int` (optional, default 1) |

**Responses**:

| Condition | HTTP Status | Body |
|-----------|-------------|------|
| Collection found, page valid | 200 | `templates/collection/show.html.twig` |
| Slug not found in DB | 404 | Symfony default 404 page |
| `page` ≤ 0 or non-integer | 404 | Symfony default 404 page |
| `page` > total pages | 404 | Symfony default 404 page |
| `page = 1` (default) | 200 | Same as page valid |

**Note**: `?page=1` and no `?page` param produce identical responses. No redirect between them.

---

### Template Contract: collection/show.html.twig

**Variables passed from controller**:

| Variable | Type | Description |
|----------|------|-------------|
| `collection` | `App\Entity\Collection` | The collection entity |
| `books` | `Doctrine\ORM\Tools\Pagination\Paginator` | Paginated book list (max 20) |
| `currentPage` | `int` | Current page number |
| `totalPages` | `int` | Total number of pages |
| `totalBooks` | `int` | Total book count in collection |

**Required rendering**:
- Header: nom, nomOriginal (if set), imageLogo (or `placeholder-cover.svg`), badge genre (`.badge.badge-genre-{value}`), badge statut (`.badge.badge-statut-{value}`)
- Meta: description, createurs inline (hidden if `[]`), anneeCreation (if set), editeurHistorique (if set)
- Book list: cover, volumeNumber (if set), title, link to `/livre/{slug}` — or "Aucun livre disponible" if empty
- Breadcrumb: `Catalogue / {collection.nom}`
- `<title>`: `{nom} — La Collection dont vous êtes le héros` (page 1), `{nom} (page N) — La Collection dont vous êtes le héros` (page N≥2)
- `<link rel="canonical">`: always pointing to `/collections/{slug}` (no `?page=`)

---

### Updated Route: GET /livre/{slug}

**Changed behavior** (no signature change):

| Change | Before | After |
|--------|--------|-------|
| Breadcrumb (book with collection) | `Catalogue / {titre}` | `Catalogue / {Nom Collection (link)} / {titre}` |
| Breadcrumb (book without collection) | `Catalogue / {titre}` | `Catalogue / {titre}` (unchanged) |
| Saga/Volume row (book with collection) | plain text saga name | clickable link to `/collections/{slug}` |
| Book entity join | no collection | `findBySlugWithRelations` includes `leftJoin('b.collection', 'c')` |

---

### Security Configuration

```yaml
# config/packages/security.yaml — access_control addition
- { path: ^/collections/, roles: PUBLIC_ACCESS }
```

**Placement**: before the catch-all `^/` rule, after existing `^/livre/` rule.
