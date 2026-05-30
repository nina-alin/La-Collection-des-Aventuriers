# Route Contracts: Système de Notation et Commentaires

**Branch**: `009-book-review-rating`

All routes are Symfony HTML form routes (not a public API). Response format depends on `Accept` header / preferred format.

---

## POST `/livre/{slug}/avis`

**Route name**: `app_book_review_submit`
**Auth**: `IS_AUTHENTICATED_FULLY` — redirect to login if not authenticated
**CSRF**: Yes (token field `review_token` in form)

### Request

Form fields (submitted via standard HTML form):
| Field | Type | Validation |
|-------|------|------------|
| `review[score]` | int | Required, 1–10 |
| `review[comment]` | string\|null | Optional, max 1000 chars; empty → NULL |
| `review[_token]` | string | CSRF token |

### Responses

| Condition | Format | Response |
|-----------|--------|----------|
| Valid, Turbo Stream request | `text/vnd.turbo-stream.html` | 4-stream update (stats header, histogram, reviews list, form) |
| Valid, standard request | `302` | Redirect to `app_book_show` |
| Validation failure, Turbo Stream | `text/vnd.turbo-stream.html` | Stream replacing `review-form` with form + errors |
| Validation failure, standard | `422` | Re-render page with form errors |
| Race condition duplicate | `409 Conflict` | Flash error + Turbo Stream OR redirect |
| Not authenticated | `302` | Redirect to login |

### Turbo Stream Targets (on success)

| Target ID | Content |
|-----------|---------|
| `stats-header` | Updated average, count, last 4 evaluators avatars |
| `histogram` | Updated distribution bars |
| `reviews-list` | Refreshed paginated list (page 1, filter "récentes") |
| `review-form` | Pre-filled form with submitted review, "Modifier mon avis" button + delete button |

---

## DELETE `/livre/{slug}/avis/{id}`

**Route name**: `app_book_review_delete`
**Auth**: `IS_AUTHENTICATED_FULLY`
**Authorization**: `ReviewVoter::CAN_DELETE` — author OR moderator/admin
**CSRF**: Yes (token in request body or query param `_token`)

### Responses

| Condition | Format | Response |
|-----------|--------|----------|
| Success, Turbo Stream | `text/vnd.turbo-stream.html` | 4-stream update (stats header, histogram, reviews list, form reset to empty "Publier mon avis") |
| Success, standard | `302` | Redirect to `app_book_show` |
| Not found | `404` | Standard 404 |
| Forbidden | `403` | Standard 403 |
| Delete fails | Flash error | Turbo Stream preserving current state |

---

## GET `/livre/{slug}/avis`

**Route name**: `app_book_reviews`
**Auth**: Public (no authentication required)

### Query Parameters

| Param | Values | Default | Description |
|-------|--------|---------|-------------|
| `filter` | `recentes`, `avec_commentaire` | `recentes` | Filter mode |
| `page` | int ≥ 1 | `1` | Pagination page |

### Response

Rendered as a `<turbo-frame id="reviews-list">` partial. Contains:
- Filtered + paginated list (10 per page)
- Pagination controls (hidden if total ≤ 10)
- "Aucune évaluation pour l'instant" message if empty

Pagination links preserve `filter` param via `app.request.query.all|merge({page: p})`.

---

## ReviewVoter Logic

```
CAN_DELETE:
  - subject is a Review
  - voter checks:
      IF user == review.user → GRANT
      IF user has ROLE_MODERATOR or ROLE_ADMIN → GRANT
      ELSE → DENY
```
