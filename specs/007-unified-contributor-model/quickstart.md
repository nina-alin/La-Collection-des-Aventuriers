# Quickstart: Unified Contributor Model

## What this feature does

Replaces three separate contributor entities (Author, Illustrator, Translator) with a unified `Contributor` entity linked to Books via a `Contribution` pivot table. Adds three public profile pages: `/authors/{slug}`, `/illustrators/{slug}`, `/traductors/{slug}`.

## Prerequisites

- Docker stack running (`make up` or `docker compose up -d`)
- Database accessible

## Key files to touch

```
src/Entity/
  Contributor.php              ← new
  Contribution.php             ← new
  Enum/ContributionRole.php    ← new
  Book.php                     ← remove authors/illustrators/translator; add contributions + deletedAt

src/EntityListener/
  ContributorListener.php      ← new (slug generation)
  BookSoftDeleteListener.php   ← new (cascade soft-delete Book → Contribution)

src/Service/
  ContributorSlugger.php       ← new (mirrors CollectionSlugger)

src/Repository/
  ContributorRepository.php    ← new (findBySlugAndRole)

src/Controller/
  ContributorController.php    ← new (3 routes)

src/DataFixtures/
  Factory/ContributorFactory.php   ← new
  Factory/ContributionFactory.php  ← new
  AppFixtures.php                  ← replace Author/Illustrator fixtures

templates/contributeur/
  author_show.html.twig        ← new
  illustrator_show.html.twig   ← new
  traductor_show.html.twig     ← new

config/packages/stof_doctrine_extensions.yaml  ← add softdeleteable: true

REMOVE:
  src/Entity/Author.php
  src/Entity/Illustrator.php
  src/Entity/Translator.php
  src/DataFixtures/Factory/AuthorFactory.php
  templates/livre/show.html.twig (update authors/illustrators bylines)
```

## Migration strategy

1. Create new `contributor` and `contribution` tables
2. Drop join tables `book_author`, `book_illustrator`
3. Remove `book.translator_id` column
4. Drop `author`, `illustrator`, `translator` tables
5. Add `book.deleted_at` and `contributor.deleted_at` columns

All in a single migration file (no production data to migrate per spec assumptions).

## Running fixtures

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

Fixtures must cover (SC-006):
- 1 Author-only contributor
- 1 Illustrator-only contributor
- 1 multi-role contributor (Author + Illustrator + Traductor)
- 1 contributor without portraitImage
- 1 book without cover image

## Running tests

```bash
php bin/phpunit
```

Tests required (Constitution V):
- `tests/Unit/Entity/ContributorTest.php` — slug logic, UUID generation
- `tests/Unit/Service/ContributorSluggerTest.php` — uniqueness, transliteration
- `tests/Controller/ContributorControllerTest.php` — 200/404 on all three routes

## Slug rules (FR-004)

| Input | Slug |
|---|---|
| pseudo = "Mad Painter" | `mad-painter` |
| pseudo = null, firstName = "John", lastName = "Blanche" | `john-blanche` |
| pseudo = "Éric Vié" | `eric-vie` |
| collision with existing `john-doe` | `john-doe-2` |
