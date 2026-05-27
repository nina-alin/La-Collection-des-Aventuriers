# Quickstart: Intégration du Design de la Page Auteur

**Feature**: `008-author-page-design` | **Date**: 2026-05-26

## Prerequisites

- PHP 8.3 + Composer
- Node.js + npm
- PostgreSQL running (or SQLite for dev)
- Symfony CLI (`symfony server:start`)

## Setup

```bash
# Install dependencies (already done if continuing development)
composer install
npm install

# Run database migrations (no new migrations for this feature)
symfony console doctrine:migrations:migrate --no-interaction

# Load fixtures if needed
symfony console doctrine:fixtures:load --no-interaction
```

## Development

```bash
# Terminal 1 — Symfony dev server
symfony server:start

# Terminal 2 — Webpack Encore watch (picks up SCSS changes)
npm run watch
```

## Test the Author Page

```bash
# Navigate to any author with contributions
open http://127.0.0.1:8000/authors/joe-dever

# Test saga filter
open "http://127.0.0.1:8000/authors/joe-dever?saga=loup-solitaire"

# Test sort
open "http://127.0.0.1:8000/authors/joe-dever?sort=alpha"

# Test 404
open http://127.0.0.1:8000/authors/unknown-author
```

## Run Tests

```bash
# All tests
php bin/phpunit

# Only author page tests
php bin/phpunit tests/Controller/ContributorControllerTest.php

# With coverage (optional)
php bin/phpunit --coverage-html coverage/
```

## Key Files

| File | Purpose |
|------|---------|
| `src/Controller/ContributorController.php` | `authorShow` action — reads Request params, calls repo |
| `src/Repository/ContributorRepository.php` | `findContributionsBySlug()` — filtering/sorting logic |
| `templates/contributeur/author_show.html.twig` | Full page template |
| `assets/styles/pages/_auteur.scss` | Author page styles |
| `design/pages/auteur.html` | Reference mockup (static HTML) |

## Verify Exclusions (FR-011, FR-012, FR-013)

```bash
# Should return empty — these elements must not appear in rendered HTML
curl -s http://127.0.0.1:8000/authors/joe-dever | grep -c "seal-row\|seal-btn\|also-strip\|Contemporains\|Mes Favoris"
# Expected: 0

# Should return empty — no score/rating on book cards
curl -s http://127.0.0.1:8000/authors/joe-dever | grep -c "bc-score\|bouclier\|notation"
# Expected: 0
```
