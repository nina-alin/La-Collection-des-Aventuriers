# Quickstart: Système de Notation et Commentaires

**Branch**: `009-book-review-rating`

---

## Setup (new dependency)

```bash
# PHP
composer require symfony/ux-turbo

# JS (--force resolves @hotwired/turbo peer-dep)
npm install --force
```

Add to `assets/app.js`:
```js
import '@symfony/ux-turbo';
```

---

## Database Migration

```bash
php bin/console doctrine:migrations:diff   # inspect generated SQL
php bin/console doctrine:migrations:migrate
```

Expected: creates `review` table with FK to `book` and `"user"`, unique constraint `(user_id, book_id)`.

---

## Run Tests

```bash
# Unit tests only (fast)
php bin/phpunit tests/Unit/Entity/ReviewTest.php
php bin/phpunit tests/Unit/Service/

# Functional tests (requires test DB)
php bin/phpunit tests/Functional/Controller/ReviewControllerTest.php

# All tests
php bin/phpunit
```

---

## Feature Routes

| Route | URL | Description |
|-------|-----|-------------|
| `app_book_show` | `GET /livre/{slug}` | Book page (shows stats + form + community section) |
| `app_book_review_submit` | `POST /livre/{slug}/avis` | Submit/update review |
| `app_book_review_delete` | `DELETE /livre/{slug}/avis/{id}` | Delete review |
| `app_book_reviews` | `GET /livre/{slug}/avis` | Paginated reviews Turbo Frame |

---

## Key Implementation Files

| File | Role |
|------|------|
| `src/Entity/Review.php` | Entity — score, comment, user, book, timestamps |
| `src/Repository/ReviewRepository.php` | Queries: stats, paginated list, upsert lookup |
| `src/Service/ReviewService.php` | Business logic: submit (find-or-create), delete, 409 handling |
| `src/Security/Voter/ReviewVoter.php` | CAN_DELETE: author OR mod/admin |
| `src/Controller/ReviewController.php` | Thin HTTP layer: form binding, Turbo Stream response |
| `src/Twig/Extension/UserInitialsExtension.php` | Twig filter `user_initials(user)` → "NA" or null |
| `templates/livre/_review_stream.html.twig` | 4-target Turbo Stream response |
| `templates/livre/_reviews_list.html.twig` | Turbo Frame content (paginated list) |
| `assets/controllers/shield-selector_controller.js` | Stimulus: rating pip selection + keyboard nav |
| `assets/controllers/char-counter_controller.js` | Stimulus: textarea character counter |
| `assets/controllers/relative-date_controller.js` | Stimulus: browser-timezone relative dates |

---

## Design Reference

All UI components (shields, histogram bars, filter buttons, review cards) are finalized in:
`design/pages/livre.html`

CSS classes: `.rating-pip`, `.rating-pip.is-on`, `.histo-bar`, `.reviews-filter button[aria-pressed]`, `.review`, `.mini-shield`, `.role-pip`, `.role-pip.admin`

---

## Constitution Compliance Notes

- All mutating routes: `#[IsGranted('IS_AUTHENTICATED_FULLY')]` + CSRF token
- Moderator/admin delete: handled by `ReviewVoter` — no extra route
- Reviews bypass PENDING workflow (justified violation — see plan.md Complexity Tracking)
- No new CSS framework, no new infrastructure service
