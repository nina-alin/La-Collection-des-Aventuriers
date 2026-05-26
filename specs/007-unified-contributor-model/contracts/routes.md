# Route Contracts: Contributor Profile Pages

All routes are public (no authentication required), read-only (GET only), and render Twig templates.

---

## GET /authors/{slug}

**Controller**: `ContributorController::authorShow`
**Route name**: `app_author_show`
**Template**: `templates/contributeur/author_show.html.twig`

**Parameters**:
| Param | Type | Source |
|---|---|---|
| slug | string | URL path |

**Success (200)**:
- Contributor exists and has ≥ 1 Contribution with role `Author`
- Template receives: `contributor` (Contributor entity), `contributions` (filtered Contribution[] with Book)
- Layout: contributor header (name, portrait/avatar, biography if not null) + ordered book list (title, publication year, no cover thumbnail)
- Books ordered by `frenchPublicationYear ASC NULLS LAST`, then `title ASC`

**Not Found (404)**:
- Contributor slug does not exist, OR
- Contributor exists but has no Author contributions

---

## GET /illustrators/{slug}

**Controller**: `ContributorController::illustratorShow`
**Route name**: `app_illustrator_show`
**Template**: `templates/contributeur/illustrator_show.html.twig`

**Parameters**:
| Param | Type | Source |
|---|---|---|
| slug | string | URL path |

**Success (200)**:
- Contributor exists and has ≥ 1 Contribution with role `Illustrator`
- Template receives: `contributor` (Contributor entity), `contributions` (filtered Contribution[] with Book)
- Layout: contributor header (name, portrait/avatar, no biography) + responsive cover grid (one tile per book, linked to book detail page)
- Missing cover: neutral placeholder tile at same aspect ratio, `alt="Cover not available"`
- Books ordered by `frenchPublicationYear ASC NULLS LAST`, then `title ASC`

**Not Found (404)**:
- Contributor slug does not exist, OR
- Contributor exists but has no Illustrator contributions

---

## GET /traductors/{slug}

**Controller**: `ContributorController::traductorShow`
**Route name**: `app_traductor_show`
**Template**: `templates/contributeur/traductor_show.html.twig`

**Parameters**:
| Param | Type | Source |
|---|---|---|
| slug | string | URL path |

**Success (200)**:
- Contributor exists and has ≥ 1 Contribution with role `Traductor`
- Template receives: `contributor` (Contributor entity), `contributions` (filtered Contribution[] with Book)
- Layout: contributor header (name, portrait/avatar, biography if not null) + ordered book list (title, publication year)
- Books ordered by `frenchPublicationYear ASC NULLS LAST`, then `title ASC`

**Not Found (404)**:
- Contributor slug does not exist, OR
- Contributor exists but has no Traductor contributions

---

## Shared UI Contracts

### Contributor Header (all three pages)

| Condition | Render |
|---|---|
| `portraitImage` not null | `<img>` with alt = contributor name |
| `portraitImage` null | CSS initials avatar; initial from `pseudo[0]` if pseudo set, else `firstName[0]`; aria-label = contributor name |
| `biography` not null | Biography section rendered |
| `biography` null | Biography section suppressed entirely (no empty element) |

### Contribution `details` field

- Not null → rendered in book entry
- Null → suppressed entirely

### Accessibility (FR-022)

- All `<img>` have descriptive `alt` text
- All avatars have `aria-label` containing contributor name
- Semantic HTML landmarks (`<main>`, `<header>`, `<nav>`, `<section>`)
- Keyboard navigation via Bootstrap defaults

### Responsiveness (FR-023)

- Bootstrap grid system, mobile-first
- Illustrator gallery: responsive grid (Bootstrap `col-*` classes)
