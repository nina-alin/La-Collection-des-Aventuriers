# URL Query Parameter Contract

**Feature**: `015-catalogue-advanced-filtering` | **Endpoint**: `GET /catalogue`

The catalogue page uses URL query parameters as the canonical representation of the active filter state (FR-024), enabling deep linking, browser back/forward navigation, and shareable URLs.

---

## Parameters

| Parameter | Type | Example | Default | Notes |
|-----------|------|---------|---------|-------|
| `sort` | string | `note-desc` | `note-desc` | See sort slugs below |
| `editors[]` | int[] | `editors[]=3&editors[]=7` | `[]` | Editor IDs; multi-value |
| `paragraphMin` | int | `200` | (none) | Clamped to DB-actual min if below bounds |
| `paragraphMax` | int | `450` | (none) | Clamped to DB-actual max if above bounds |
| `collectionStatus` | string | `dans-ma-collection` | (none) | Status slug; ignored for guests |
| `onlyFavorites` | int | `1` | (none) | `1` = active; any other value = ignored |
| `hideModeration` | int | `1` | (none) | `1` = active |
| `q` | string | `Loup+Noir` | (none) | In-page search query; URL-encoded |
| `page` | int | `3` | `1` | 1-indexed; reset to 1 on Appliquer / TOUT EFFACER |

---

## Sort Slugs

| URL slug | Panel label (authoritative) | Toolbar label |
|----------|-----------------------------|---------------|
| `note-desc` | Note moyenne (10/10) | Trier · Note décroissante |
| `alpha` | Ordre alphabétique | Trier · A → Z |
| `parution-fr` | Année · parution France | Trier · Parution récente |
| `parution-orig` | Année · édition originale | Trier · Parution ancienne |
| `recent` | Récemment ajouté au wiki | Trier · Récemment ajouté |

---

## Collection Status Slugs

| URL slug | Meaning |
|----------|---------|
| `dans-ma-collection` | User owns the book |
| `a-acheter` | User is hunting / wants to buy |
| `a-lire` | User plans to read |
| `lu` | User has read |
| `pas-dans-ma-collection` | User does not own the book |

---

## Constraints

- `paragraphMin` ≤ `paragraphMax`; if violated, server clamps (min wins).
- `page` ≥ 1; if `page` exceeds last available page after filtering, server redirects to last page (302).
- `collectionStatus`, `onlyFavorites`, `hideModeration` silently ignored for unauthenticated users.
- Unknown or malformed parameters ignored; `ActiveFilterState::fromRequest()` falls back to field defaults.

---

## History Entry Behaviour

| Action | History | Mechanism |
|--------|---------|-----------|
| "Appliquer" click | `pushState` | `LiveAction` → `RedirectResponse` → Turbo Drive |
| Chip "×" removal | `pushState` | GET form → Turbo Drive |
| "TOUT EFFACER" | `pushState` | GET link → Turbo Drive |
| Pagination | `pushState` | GET link → Turbo Drive |
| Sort toolbar change | `replaceState` | GET form with `data-turbo-action="replace"` |
| View mode toggle | Not in URL | sessionStorage only |

---

## Search Suggestions Sub-Endpoint

```
GET /catalogue/search-suggestions?q={query}
```

**Response** (JSON):
```json
{
  "books":   [{ "id": 42, "title": "Le Loup des Mers", "author": "Steve Jackson" }],
  "authors": [{ "id": 7, "name": "Ian Livingstone" }]
}
```

- Minimum query length: 1 character (FR-007).
- Scoped to `book.status = PUBLISHED` only.
- Returns at most 5 books + 5 authors.
- Returns `{"books": [], "authors": []}` (empty arrays, not 404) when no matches — dropdown hidden client-side.
