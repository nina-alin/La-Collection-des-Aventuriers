# Implementation Plan: Unified Contributor Model

**Branch**: `007-unified-contributor-model` | **Date**: 2026-05-25 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/007-unified-contributor-model/spec.md`

## Summary

Replace the separate Author, Illustrator, and Translator entities with a unified `Contributor` entity linked to Books via a `Contribution` pivot (role: Author | Illustrator | Traductor). Expose three public profile pages (`/authors/{slug}`, `/illustrators/{slug}`, `/traductors/{slug}`) each showing the contributor's biography and role-filtered book list. Slug generation uses a custom `ContributorSlugger` service (pseudo if set, else firstName+lastName), following the existing `CollectionSlugger` pattern. Soft-delete infrastructure is added to `Contributor`, `Contribution`, and `Book` via Gedmo SoftDeleteable.

## Technical Context

**Language/Version**: PHP 8.2+

**Primary Dependencies**: Symfony 7.2, Doctrine ORM 3.x, stof/doctrine-extensions-bundle ^1.12 (Gedmo Sluggable + SoftDeleteable), symfony/uid (UUID v7), symfony/string (SluggerInterface)

**Storage**: PostgreSQL (Platform.sh) / SQLite (test env)

**Testing**: PHPUnit 12.5 (`php bin/phpunit`)

**Target Platform**: Platform.sh (Linux, PHP-FPM)

**Project Type**: Symfony web application

**Performance Goals**: ≤2 DB queries per contributor profile page (FR-013). Single DQL JOIN FETCH satisfies this.

**Constraints**: No production data to migrate. No admin CRUD in scope. Soft-delete infrastructure added but not triggered by any UI in this feature.

**Scale/Scope**: ~3 new entities, ~3 new services/listeners, ~1 new controller (3 routes), ~3 new templates, 1 data model migration.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Assessment | Status |
|---|---|---|
| I. Complémentarité Stricte | Profile pages are read-only encyclopedia entries (author bio + book list). No forum/news feature added. | ✅ PASS |
| II. Architecture Symfony LTS | Thin `ContributorController` (HTTP only), business logic in `ContributorSlugger` service, Doctrine ORM exclusively, full DI throughout. | ✅ PASS |
| III. Workflow Validation | No user-submitted content. Profile pages are read-only public views. | ✅ PASS (N/A) |
| IV. RBAC | Profile routes are public GET routes — no mutation, no CSRF, no `#[IsGranted]` needed. No data-mutating routes added. | ✅ PASS |
| V. Tests | Unit tests for Contributor entity and ContributorSlugger; controller tests for all three profile routes (200 + 404). | ✅ REQUIRED |

**Re-check post-design**: No design decision introduced violations. No Platform.sh infrastructure change (no new managed service added). Soft-delete is a Gedmo filter — no new infrastructure service.

## Project Structure

### Documentation (this feature)

```text
specs/007-unified-contributor-model/
├── plan.md              ← this file
├── research.md          ← Phase 0 output
├── data-model.md        ← Phase 1 output
├── quickstart.md        ← Phase 1 output
├── contracts/
│   └── routes.md        ← Phase 1 output
└── tasks.md             ← Phase 2 output (/speckit-tasks command)
```

### Source Code (repository root)

```text
src/
├── Controller/
│   └── ContributorController.php          ← new: 3 public GET routes
├── Entity/
│   ├── Contributor.php                    ← new
│   ├── Contribution.php                   ← new
│   ├── Enum/
│   │   └── ContributionRole.php           ← new
│   └── Book.php                           ← modified: remove authors/illustrators/translator; add contributions + deletedAt
├── EntityListener/
│   ├── ContributorListener.php            ← new: slug generation on prePersist/preUpdate
│   └── BookSoftDeleteListener.php         ← new: cascade soft-delete Book → Contribution
├── Repository/
│   └── ContributorRepository.php          ← new: findBySlugAndRole()
└── Service/
    └── ContributorSlugger.php             ← new: mirrors CollectionSlugger

src/DataFixtures/
├── Factory/
│   ├── ContributorFactory.php             ← new
│   └── ContributionFactory.php            ← new
└── AppFixtures.php                        ← updated: replace Author/Illustrator fixtures

templates/
├── contributeur/
│   ├── author_show.html.twig              ← new: text-focused bibliography layout
│   ├── illustrator_show.html.twig         ← new: image gallery layout
│   └── traductor_show.html.twig           ← new: text list layout
└── livre/
    └── show.html.twig                     ← updated: replace authors/illustrators bylines with contributions

config/packages/
└── stof_doctrine_extensions.yaml         ← updated: add softdeleteable: true

migrations/
└── Version20260525XXXXXX.php              ← new: full schema migration

tests/
├── Controller/
│   └── ContributorControllerTest.php      ← new
├── Unit/
│   ├── Entity/
│   │   └── ContributorTest.php            ← new
│   └── Service/
│       └── ContributorSluggerTest.php     ← new

REMOVED:
  src/Entity/Author.php
  src/Entity/Illustrator.php
  src/Entity/Translator.php
  src/DataFixtures/Factory/AuthorFactory.php
  src/Repository/AuthorRepository.php (if exists)
  src/Repository/IllustratorRepository.php (if exists)
  src/Repository/TranslatorRepository.php (if exists)
```

**Structure Decision**: Single Symfony project (existing layout). New code follows existing conventions — entity/service/listener/controller/template layers.

## Complexity Tracking

> No Constitution violations requiring justification.
