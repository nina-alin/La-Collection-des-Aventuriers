# Quickstart — 011 Page Contributeur Suggestions

**Date**: 2026-05-30 | **Branch**: `011-contributor-page`

## Prerequisites

```bash
# Verify you're on the feature branch
git checkout 011-contributor-page

# Verify Symfony CLI is available
symfony version
```

## Step 1 — Install LiveComponent

```bash
composer require symfony/ux-live-component
```

Flex recipe auto-registers the bundle and wires Stimulus controllers. Verify:

```bash
grep "live_component" config/bundles.php
ls assets/controllers/live_controller.js 2>/dev/null || echo "check assets/controllers.json for @symfony/ux-live-component"
```

## Step 2 — Run Database Migration

After generating the migration (done as part of implementation tasks):

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

## Step 3 — Seed ContributorLevel Data

```bash
php bin/console doctrine:fixtures:load --group=contributor_level --append
```

## Step 4 — Build Frontend Assets

```bash
npm run dev
# or for production:
npm run build
```

Verify the new Stimulus controllers are compiled:

```bash
ls public/build/ | grep suggestion
```

## Step 5 — Access the Page

```bash
symfony serve
# Navigate to: http://127.0.0.1:8000/suggestions
# Must be logged in as a ROLE_USER account
```

## Step 6 — Run Tests

```bash
# Unit + Integration tests
php bin/phpunit tests/Unit/Entity/SuggestionTest.php
php bin/phpunit tests/Unit/Service/SuggestionServiceTest.php
php bin/phpunit tests/Integration/Controller/SuggestionControllerTest.php

# Full test suite
php bin/phpunit
```

## Key Files

| Purpose | Path |
|---------|------|
| Page template | `templates/suggestion/index.html.twig` |
| Wizard LiveComponent | `src/Twig/Components/Suggestion/WizardComponent.php` |
| Wizard template | `templates/components/Suggestion/WizardComponent.html.twig` |
| Upload Stimulus | `assets/controllers/suggestion-upload_controller.js` |
| Polling Stimulus | `assets/controllers/suggestion-polling_controller.js` |
| Suggestion entity | `src/Entity/Suggestion.php` |
| Suggestion service | `src/Service/SuggestionService.php` |
| Design reference | `design/pages/suggestions.html` |
| Spec | `specs/011-contributor-page/spec.md` |
| Contracts | `specs/011-contributor-page/contracts/endpoints.md` |

## Environment Variables

No new environment variables required. The feature uses:
- `DATABASE_URL` — existing
- `APP_SECRET` — existing (for CSRF tokens)

## Upload Directory

Cover images are stored in `public/uploads/covers/`. Ensure this directory exists and is writable:

```bash
mkdir -p public/uploads/covers/tmp
chmod 775 public/uploads/covers public/uploads/covers/tmp
```

Add to `.gitignore` if not already present:
```
/public/uploads/covers/*
!/public/uploads/covers/.gitkeep
```
