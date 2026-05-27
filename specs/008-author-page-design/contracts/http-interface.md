# HTTP Interface Contract: Author Page

**Feature**: `008-author-page-design` | **Date**: 2026-05-26

## Endpoint

```
GET /authors/{slug}
```

### Path Parameters

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `slug` | string | yes | Contributor slug (e.g. `joe-dever`) |

### Query Parameters

| Param | Type | Default | Values | Description |
|-------|------|---------|--------|-------------|
| `saga` | string | (none) | slugified saga name | Filter bibliography to a single saga |
| `sort` | string | `chrono` | `chrono` \| `alpha` | Sort order for bibliography grid |

### Behavior

| Condition | Response |
|-----------|----------|
| Valid author slug, role=Author | 200 — full author page |
| Unknown slug | 404 |
| Slug exists but role ≠ Author | 404 |
| Soft-deleted contributor | 404 (Gedmo filter active) |
| `?saga=` not provided | All contributions shown, pill "TOUT" `aria-pressed="true"` |
| `?saga=loup-solitaire` | Only Loup Solitaire contributions shown |
| `?saga=unknown-value` | All contributions shown (fallback), pill "TOUT" `aria-pressed="true"` |
| `?sort=chrono` or absent | `frenchPublicationYear` ASC, NULL last; title ASC tiebreak |
| `?sort=alpha` | `title` ASC |
| Both params combined | `?saga=loup-solitaire&sort=alpha` — filter AND sort applied |

### Response Format

Server-rendered HTML. No JSON API. Content-Type: `text/html; charset=UTF-8`.

### Navigation Side Effects

- Saga pill click → full page reload with `?saga={slug}` appended
- Sort control change → full page reload with `?sort={value}` appended (preserves `?saga=` if active)
- View toggle (grille/liste) → client-side only, no server request

### Accessibility Contract

| Element | Requirement |
|---------|-------------|
| Portrait `<img>` | `alt="Portrait de {firstName} {lastName}"` |
| Portrait placeholder | `alt=""` (decorative) |
| Saga filter group | `role="group"` + `aria-label="Filtrer par saga"` |
| Active saga pill | `aria-pressed="true"` |
| Inactive saga pill | `aria-pressed="false"` |
| Biography section | `<section aria-label="Biographie">` |
| Books grid | `<ul role="list">` containing `<li>` per card |
| Sort control | `<label>` or `aria-label` identifying the control |
| View toggle button | `aria-pressed` reflecting current state |
