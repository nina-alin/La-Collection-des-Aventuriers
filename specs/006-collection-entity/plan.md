# Implementation Plan: Entité Collection et Vue Détail

**Branch**: `006-collection-entity` | **Date**: 2026-05-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/006-collection-entity/spec.md`

## Summary

Create a `Collection` entity (series metadata), a public detail page at `/collections/{slug}` with server-side paginated book list (Doctrine Paginator, 20/page), backed PHP 8.1 enums for `genre` and `statut`, slug auto-generation via `SluggerInterface` with collision suffix, and updates to the book detail view (breadcrumb + collection link).

## Technical Context

**Language/Version**: PHP 8.1 (backed enums), Symfony LTS (6.x/7.x)

**Primary Dependencies**: Doctrine ORM, Doctrine Migrations, Symfony SluggerInterface (`symfony/string`), Doctrine Paginator (`doctrine/orm`), PHPUnit (WebTestCase), Foundry factories

**Storage**: PostgreSQL — new `collection` table + nullable `collection_id` FK on existing `book` table

**Testing**: PHPUnit WebTestCase functional tests + Foundry factories (pattern already used: `BookFixtures`)

**Target Platform**: Platform.sh (Linux server)

**Project Type**: Symfony web application (catalogue / encyclopedic read-only)

**Performance Goals**: p95 < 2 s on collection page (SC-001, all paginated pages) — single Paginator query, no N+1

**Constraints**: No admin UI (fixtures only); no upload (image path stored as string); no redirect on slug change (old slug → 404)

**Scale/Scope**: Single-page feature; ~20 books/page; read-only public access

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Complémentarité Stricte | ✅ PASS | Encyclopedic catalogue view, no discussion/news/forum feature |
| II. Architecture Symfony LTS | ✅ PASS | Thin `CollectionController` (HTTP only), slug logic in `CollectionSlugger` service + `CollectionListener` EntityListener (DI via constructor), Doctrine ORM exclusively. No new managed service added — schema evolution only (Doctrine Migrations); `.platform.app.yaml`, `routes.yaml`, `services.yaml` unchanged. |
| III. Workflow Validation | ✅ N/A | No user-submitted content — all data via fixtures/admin; page is read-only |
| IV. RBAC — Trois Niveaux | ✅ PASS | All routes `PUBLIC_ACCESS`; no data-mutating routes in this feature |
| V. Sécurité & Tests | ✅ PASS | FR-010 mandates WebTestCase + Foundry factories for all acceptance scenarios |

Post-design re-check: No violations introduced. No Complexity Tracking entries required.

## Project Structure

### Documentation (this feature)

```text
specs/006-collection-entity/
├── plan.md              ← this file
├── research.md          ← Phase 0 output
├── data-model.md        ← Phase 1 output
├── quickstart.md        ← Phase 1 output
├── contracts/
│   └── routes.md        ← Phase 1 output
└── tasks.md             ← Phase 2 output (/speckit-tasks)
```

### Source Code

```text
src/
├── Controller/
│   ├── BookController.php          (no change — breadcrumb/link handled in Twig only)
│   └── CollectionController.php    (new)
├── Entity/
│   ├── Book.php                    (update: ManyToOne Collection + index)
│   ├── Collection.php              (new)
│   └── Enum/
│       ├── GenreCollection.php     (new)
│       └── StatutCollection.php    (new)
├── EntityListener/
│   └── CollectionListener.php      (new: Doctrine EntityListener for slug auto-generation)
├── Repository/
│   ├── BookRepository.php          (update: join collection)
│   └── CollectionRepository.php    (new: findBySlug, paginatedBooks)
├── Service/
│   └── CollectionSlugger.php       (new: SluggerInterface + collision suffix)
└── DataFixtures/
    └── Factory/
        └── CollectionFactory.php   (new: Foundry factory)

templates/
├── collection/
│   └── show.html.twig              (new)
└── livre/
    └── show.html.twig              (update: breadcrumb + collection link)

assets/styles/
├── components/
│   └── _badges.scss                (update: badge-genre-* + badge-statut-*)
└── pages/
    └── _collection.scss            (new)

migrations/
└── Version20260525XXXXXX.php       (new: collection table + book.collection_id)

tests/
└── Functional/
    └── CollectionControllerTest.php (new: all acceptance scenarios)
    └── BookCollectionBreadcrumbTest.php (new: breadcrumb scenarios)

config/packages/
└── security.yaml                   (update: add ^/collections/ PUBLIC_ACCESS)
```

**Structure Decision**: Single Symfony project layout. No backend/frontend split. Twig templates + SCSS following existing pattern.

## Complexity Tracking

> No Constitution Check violations — table omitted.
