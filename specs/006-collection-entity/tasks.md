# Tasks: Entité Collection et Vue Détail

**Input**: Design documents from `/specs/006-collection-entity/`

**Prerequisites**: plan.md ✓, spec.md ✓, research.md ✓, data-model.md ✓, contracts/routes.md ✓, quickstart.md ✓

**Tests**: Included — FR-010 explicitly requires `WebTestCase` functional tests + Foundry factories.

**Organization**: Tasks grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2)
- Exact file paths in all descriptions

---

## Phase 1: Setup (Prerequisite Verification)

**Purpose**: Confirm blocking dependency before any implementation begins

- [X] T001 Verify spec 005 is merged: run `git log --oneline master | grep 005` — output must show the 005 merge commit (blocking dependency per spec assumptions)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core entities, services, and DB schema — MUST be complete before any user story

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 [P] Create backed enum `GenreCollection: string` in `src/Entity/Enum/GenreCollection.php` with 6 cases: `MEDIEVAL_FANTASTIQUE = 'medieval-fantastique'`, `SCIENCE_FICTION = 'science-fiction'`, `HORREUR = 'horreur'`, `ESPIONNAGE = 'espionnage'`, `AVENTURE = 'aventure'`, `CONTEMPORAIN = 'contemporain'` per data-model.md
- [X] T003 [P] Create backed enum `StatutCollection: string` in `src/Entity/Enum/StatutCollection.php` with 3 cases: `EN_COURS = 'en-cours'`, `TERMINEE = 'terminee'`, `REEDITEE = 'reeditee'` per data-model.md
- [X] T004 Create `Collection` entity in `src/Entity/Collection.php` with all 11 fields from data-model.md (UUID id auto-generated via `Uuid::v4()`, nom, nomOriginal, slug, description, genre, createurs JSON default `[]`, anneeCreation, editeurHistorique, statut, imageLogo), Doctrine ORM mapping with `uniq_collection_slug` + `uniq_collection_nom` unique indexes + `idx_collection_slug`, Symfony validation constraints from FR-008, and `OneToMany books` inverse side (`mappedBy: 'collection'`)
- [X] T005 Create `CollectionSlugger` service in `src/Service/CollectionSlugger.php` implementing `generateUnique(string $nom, ?string $currentSlug = null): string` with collision-suffix loop per research.md Decision 1; wire slug auto-generation via `src/EntityListener/CollectionListener.php` registered as `doctrine.orm.entity_listener` for entity `Collection` (tag: `#[AsEntityListener(event: Events::prePersist, entity: Collection::class)]` + `#[AsEntityListener(event: Events::preUpdate, ...)]`), `CollectionSlugger` injected via constructor DI — regenerate slug only when `nom` has changed
- [X] T023 [P] Create unit test `tests/Unit/Service/CollectionSluggerTest.php` covering: normal slug (no collision), first collision (→ `-2` suffix), two consecutive collisions (→ `-3` suffix) — mock `CollectionRepository` to control collision returns
- [X] T006 Update `src/Entity/Book.php` to add nullable `ManyToOne` relation `$collection` → `Collection` (`nullable: true`, `inversedBy: 'books'`, `onDelete: 'SET NULL'`) and `#[ORM\Index(name: 'idx_book_collection_id', columns: ['collection_id'])]` on the class per data-model.md Modified Entities section
- [X] T007 Create `CollectionRepository` in `src/Repository/CollectionRepository.php` with: `findBySlug(string $slug): ?Collection` (findOneBy slug), and `paginateBooksForCollection(Collection $c, int $page, int $perPage = 20): Paginator` using DQL with `CASE WHEN b.volumeNumber IS NULL THEN 1 ELSE 0 END ASC, b.volumeNumber ASC, b.title ASC` ORDER BY + `setFirstResult` / `setMaxResults` + `new Paginator($qb, fetchJoinCollection: false)` per research.md Decision 2
- [X] T008 Update `src/Repository/BookRepository.php` — add `->leftJoin('b.collection', 'c')->addSelect('c')` to the `findBySlugWithRelations` method to eager-load the collection relationship per contracts/routes.md Updated Route section
- [X] T009 Review and complete migration in `migrations/Version20260525121111.php` (file already exists untracked): verify `up()` contains `CREATE TABLE collection` with all columns + `uniq_collection_slug`, `uniq_collection_nom`, `idx_collection_slug` indexes; `ALTER TABLE book ADD COLUMN collection_id UUID DEFAULT NULL`; FK constraint `ON DELETE SET NULL`; `CREATE INDEX idx_book_collection_id`; and correct `down()` per data-model.md Database Schema section
- [ ] T010 Execute migration: `php bin/console doctrine:migrations:migrate --no-interaction`

**Checkpoint**: Foundation ready — Collection entity, Book relation, CollectionSlugger, CollectionListener (EntityListener), CollectionRepository, DB schema all in place. T023 unit tests green. Phase 3 and Phase 4 can begin.

---

## Phase 3: User Story 1 — Consulter la fiche d'une collection (Priority: P1) 🎯 MVP

**Goal**: Public page at `/collections/{slug}` displays all collection metadata + server-side paginated book list (20/page, volumeNumber ASC NULLS LAST)

**Independent Test**: Navigate to `/collections/defis-fantastiques` → HTTP 200, page shows nom, description, genre badge, statut badge, créateurs inline, paginated book list sorted by volumeNumber. Validates independently of US2.

- [X] T011 [P] [US1] Create Foundry factory `CollectionFactory` in `src/DataFixtures/Factory/CollectionFactory.php` with explicit defaults for all fields: `nom: faker()->words(3, true)`, `nomOriginal: null`, `slug: auto (CollectionSlugger prePersist)`, `description: faker()->paragraph()`, `genre: faker()->randomElement(GenreCollection::cases())`, `createurs: []`, `anneeCreation: null`, `editeurHistorique: null`, `statut: StatutCollection::EN_COURS`, `imageLogo: null` per data-model.md Foundry Factory Defaults section
- [X] T012 [US1] Create `tests/Functional/CollectionControllerTest.php` using `WebTestCase` covering: GET valid slug → 200, GET unknown slug → 404, GET `?page=2` with 25 books → 200 + exactly 5 books on page 2, GET `?page=99` → 404, GET `?page=abc` → 404, GET `?page=0` → 404, collection with 0 books → response contains "Aucun livre disponible", imageLogo set → `<img>` with correct src, imageLogo null → `placeholder-cover.svg` per spec.md US1 acceptance scenarios + FR-010
- [X] T013 [US1] Create `src/Controller/CollectionController.php` with `#[Route('/collections/{slug}', name: 'app_collection_show', methods: ['GET'])]` action: 1) `findBySlug($slug)` → `NotFoundHttpException` if null, 2) validate `$rawPage = $request->query->get('page', '1')` — non-numeric or `(int)$rawPage < 1` → `NotFoundHttpException`, 3) `$page = (int) $rawPage`, 4) `paginateBooksForCollection($collection, $page)` → compute `$totalPages = max(1, (int) ceil(count($books) / 20))`, 5) `$page > $totalPages` → `NotFoundHttpException`, 6) render `collection/show.html.twig` with `collection`, `books`, `currentPage`, `totalPages`, `totalBooks` per contracts/routes.md + research.md Decision 5
- [X] T014 [US1] Update `config/packages/security.yaml` to add `- { path: ^/collections/, roles: PUBLIC_ACCESS }` in `access_control` section before the catch-all `^/` rule, after the existing `^/livre/` rule per contracts/routes.md Security Configuration
- [X] T015 [US1] Create `templates/collection/show.html.twig` extending base layout with: breadcrumb `CATALOGUE / {collection.nom}`, `<title>` block (`{nom} — La Collection...` for page 1, `{nom} (page N) — La Collection...` for N≥2), `<link rel="canonical" href="{{ path('app_collection_show', {slug: collection.slug}) }}">` in `<head>`, header section with nom + nomOriginal (if set) + imageLogo or `placeholder-cover.svg` + `.badge.badge-genre-{collection.genre.value}` + `.badge.badge-statut-{collection.statut.value}`, meta section (description, createurs joined by ", " hidden if empty, anneeCreation if set, editeurHistorique if set), paginated book list (cover img or placeholder, volumeNumber if set, titre, `<a href="{{ path('app_book_show', {slug: b.slug}) }}">`), empty state "Aucun livre disponible", pagination controls per contracts/routes.md + FR-004 + FR-012
- [X] T016 [P] [US1] Add CSS badge classes to `assets/styles/components/_badges.scss`: `.badge-genre-medieval-fantastique`, `.badge-genre-science-fiction`, `.badge-genre-horreur`, `.badge-genre-espionnage`, `.badge-genre-aventure`, `.badge-genre-contemporain` and `.badge-statut-en-cours`, `.badge-statut-terminee`, `.badge-statut-reeditee` using existing semantic color tokens from `_colors.scss` per FR-009
- [X] T017 [P] [US1] Create `assets/styles/pages/_collection.scss` with collection page layout styles (card grid, header, meta section); add `@import 'pages/collection'` (or `@use`) in `assets/styles/app.scss` per plan.md project structure

**Checkpoint**: User Story 1 fully functional — `/collections/{slug}` end-to-end. Can deploy/demo as MVP independently of US2.

---

## Phase 4: User Story 2 — Naviguer depuis une fiche livre vers sa collection (Priority: P2)

**Goal**: Book detail page (`/livre/{slug}`) shows clickable collection name linking to `/collections/{slug}` + updated breadcrumb hierarchy

**Independent Test**: On a fiche livre linked to a collection, collection name is a link → `/collections/{slug}`. Breadcrumb shows `Catalogue / {Nom Collection (lien)} / {Titre Livre}`. Book without collection shows `Catalogue / {Titre Livre}`.

- [X] T018 [US2] Create `tests/Functional/BookCollectionBreadcrumbTest.php` using `WebTestCase` covering: book with collection → breadcrumb contains link to `/collections/{slug}`, book without collection → breadcrumb shows `Catalogue / {titre}` with no collection segment, Saga/Volume row with collection → row contains `<a href="/collections/{slug}">` per spec.md US2 acceptance scenarios + FR-010
- [X] T019 [US2] Update `templates/livre/show.html.twig`: 1) breadcrumb — `{% if book.collection %}CATALOGUE / <a href="{{ path('app_collection_show', {slug: book.collection.slug}) }}">{{ book.collection.nom }}</a> / {{ book.titre }}{% else %}CATALOGUE / {{ book.titre }}{% endif %}`, 2) Saga/Volume row — wrap collection name in `<a href="{{ path('app_collection_show', {slug: book.collection.slug}) }}">` when `book.collection` not null per contracts/routes.md Updated Route section + FR-006 + FR-007

**Checkpoint**: User Stories 1 AND 2 both functional — full navigation flow works end-to-end.

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: Final validation and quality gate

- [ ] T020 Run `php bin/console doctrine:schema:validate` — all mappings must be valid; fix any mismatch between entity annotations and DB schema
- [ ] T021 Run `php bin/phpunit tests/Functional/CollectionControllerTest.php tests/Functional/BookCollectionBreadcrumbTest.php` — all tests must pass green
- [ ] T022 [P] Verify SEO via browser: navigate to `/collections/{slug}?page=2` → `<title>` contains `(page 2)` suffix and `<link rel="canonical">` points to `/collections/{slug}` without `?page=` per FR-012
- [ ] T024 [P] Verify performance via Symfony debug toolbar in dev: open `/collections/{slug}` and `/collections/{slug}?page=2` → total response time < 2 s and Doctrine query count ≤ 2 per request (one collection lookup + one Paginator query) per SC-001 + SC-003
- [ ] T025 [P] Visual review: open `/collections/{slug}` in browser → confirm `.badge-genre-*` and `.badge-statut-*` render with correct semantic colours, card grid / header / meta section match existing design patterns, breadcrumb reads `CATALOGUE / {Nom Collection}`, mobile layout intact per SC-007 + FR-009

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Requires T001 verified — BLOCKS all user stories
- **User Story 1 (Phase 3)**: Requires Phase 2 complete (T010 migration executed)
- **User Story 2 (Phase 4)**: Requires Phase 2 complete; can run in parallel with Phase 3
- **Polish (Phase 5)**: Requires Phases 3 + 4 complete

### User Story Dependencies

- **US1 (P1)**: Independent after Phase 2 — no dependency on US2
- **US2 (P2)**: Independent after Phase 2 — reuses `Book.collection` added in T006 (foundational); no dependency on US1

### Within Phase 2

```
T002 (enum) ─┐
T003 (enum) ─┤──→ T004 (entity) ──→ T005 (slugger + EntityListener) ──→ T023 (unit test, parallel)
             │                  ├──→ T006 (Book update) ──→ T007 (CollectionRepo)
             │                  │                       └──→ T008 (BookRepo update)
             │                  └──→ T009 (migration review)
                                              └──→ T010 (migrate)
```

### Within Phase 3 (US1)

```
T011 (factory, parallel) ──→ T012 (tests)
T016 (CSS, parallel)
T017 (SCSS, parallel)
T012 (tests RED) → T013, T014, T015 (implementation GREEN)
```

### Within Phase 4 (US2)

```
T018 (tests RED) → T019 (implementation GREEN)
```

---

## Parallel Example: Phase 2

```bash
# Start simultaneously (different files):
Task T002: src/Entity/Enum/GenreCollection.php
Task T003: src/Entity/Enum/StatutCollection.php

# Then sequentially:
Task T004: src/Entity/Collection.php (depends on T002, T003)
Task T005: src/Service/CollectionSlugger.php + src/EntityListener/CollectionListener.php (depends on T004)
Task T023: tests/Unit/Service/CollectionSluggerTest.php (parallel with T006/T007/T008, depends on T005)
Task T006: src/Entity/Book.php update (depends on T004)
Task T007: src/Repository/CollectionRepository.php (depends on T004, T006)
Task T008: src/Repository/BookRepository.php update (depends on T006)
Task T009 → T010: migration review + execute
```

## Parallel Example: Phase 3 (User Story 1)

```bash
# Start simultaneously once Phase 2 complete:
Task T011: src/DataFixtures/Factory/CollectionFactory.php
Task T016: assets/styles/components/_badges.scss additions
Task T017: assets/styles/pages/_collection.scss

# Then:
Task T012: tests/Functional/CollectionControllerTest.php (needs T011)
Task T013: src/Controller/CollectionController.php
Task T014: config/packages/security.yaml
Task T015: templates/collection/show.html.twig
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: T001 prerequisite check
2. Complete Phase 2: T002–T010 (CRITICAL — blocks everything)
3. Complete Phase 3: T011–T017 (US1 collection page)
4. **STOP and VALIDATE**: `php bin/phpunit tests/Functional/CollectionControllerTest.php`
5. Demo `/collections/{slug}` independently

### Incremental Delivery

1. Phase 1 + Phase 2 → Foundation ready
2. Phase 3 → US1 → Test + Deploy MVP (collection detail page live)
3. Phase 4 → US2 → Test + Deploy (book→collection navigation)
4. Phase 5 → All tests green + schema valid

### Parallel Team Strategy

With two developers after Phase 2 complete:
- Developer A: Phase 3 (US1 — collection page)
- Developer B: Phase 4 (US2 — book breadcrumb + links)

---

## Notes

- `[P]` tasks are on different files with no pending dependencies — safe to parallelize
- Migration `migrations/Version20260525121111.php` already exists untracked — T009 reviews/completes it, does not regenerate
- CollectionSlugger must regenerate slug only when `nom` changes (re-generate slug if and only if `nom` has changed — per research.md Decision 1)
- `NULLS LAST` for books without `volumeNumber` uses portable DQL: `CASE WHEN b.volumeNumber IS NULL THEN 1 ELSE 0 END ASC` as first ORDER BY column (no PostgreSQL-specific syntax in DQL)
- `genre` backed enum: `GenreCollection` — planned migration to separate `Genre` entity is a future iteration, out of scope here
- Security: both routes (`/collections/{slug}` and `/collections/{slug}?page=N`) are covered by the single `^/collections/` rule in access_control
