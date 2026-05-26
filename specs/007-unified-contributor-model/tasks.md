# Tasks: Unified Contributor Model

**Input**: Design documents from `/specs/007-unified-contributor-model/`

**Prerequisites**: plan.md ✓, spec.md ✓, research.md ✓, data-model.md ✓, contracts/routes.md ✓, quickstart.md ✓

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

Single Symfony project: `src/`, `templates/`, `tests/` at repository root.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Config changes before entity work begins.

- [X] T001 Add `softdeleteable: true` under `orm.default` in `config/packages/stof_doctrine_extensions.yaml`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: ContributionRole enum — required by both Contributor and Contribution entities in US1.

**⚠️ CRITICAL**: No user story entity work can begin until this phase is complete.

- [X] T002 Create `src/Entity/Enum/ContributionRole.php` — PHP Backed Enum with string cases: `Author = 'Author'`, `Illustrator = 'Illustrator'`, `Traductor = 'Traductor'`

**Checkpoint**: ContributionRole available — entity work can begin.

---

## Phase 3: User Story 1 — Refactor Data Model (Priority: P1) 🎯 MVP

**Goal**: Replace Author/Illustrator/Translator entities with a unified Contributor + Contribution model, wired to Book via a pivot. All legacy references removed. Database migrated. Fixtures updated. Unit tests passing.

**Independent Test**: Run `php bin/console doctrine:schema:validate`. Confirm `contributor` and `contribution` tables exist; `author`, `book_author`, `illustrator`, `book_illustrator`, `translator` tables and `book.translator_id` column do not exist; `book.deleted_at` and `contributor.deleted_at` columns present.

### Implementation for User Story 1

- [X] T003 [P] [US1] Create `src/Entity/Contributor.php` — UUID v7 PK generated in constructor via `Uuid::v7()`; fields: firstName (VARCHAR 100 NOT NULL), lastName (VARCHAR 100 NOT NULL), pseudo (VARCHAR 100 NULL), slug (VARCHAR 255 UNIQUE NOT NULL), biography (TEXT NULL), nationality (VARCHAR 2 NULL), birthDate (DATE NULL), deathDate (DATE NULL), portraitImage (VARCHAR 255 NULL), deletedAt (DATETIME NULL, `#[Gedmo\SoftDeleteable]`); OneToMany `$contributions` → Contribution (cascade: ['remove']); register `ContributorListener` as `#[ORM\EntityListeners]`
- [X] T004 [P] [US1] Create `src/Service/ContributorSlugger.php` — mirrors `CollectionSlugger`; constructor injects `SluggerInterface` and `ContributorRepository`; method `generateUnique(Contributor $contributor): string` computes base from `pseudo ?? (firstName . ' ' . lastName)`, passes through `$slugger->slug($input)->lower()->toString()`, appends `-2`, `-3`, … until no existing row matches in `contributor` table
- [X] T005 [US1] Create `src/EntityListener/ContributorListener.php` — `prePersist(Contributor $contributor)`: call `ContributorSlugger::generateUnique()` and set slug; `preUpdate(Contributor $contributor, PreUpdateEventArgs $event)`: regenerate slug only if `pseudo`, `firstName`, or `lastName` changed per `$event->hasChangedField()`; inject `ContributorSlugger` via constructor (depends on T003, T004)
- [X] T006 [US1] Create `src/Entity/Contribution.php` — UUID v7 PK; ManyToOne `$contributor` → Contributor (nullable: false, ON DELETE CASCADE); ManyToOne `$book` → Book (nullable: false, ON DELETE CASCADE); `$role` typed as `ContributionRole` enum (column type: string); `$details` VARCHAR 255 NULL; `$deletedAt` DATETIME NULL (Gedmo SoftDeleteable); DB `#[ORM\UniqueConstraint(name: 'uq_contribution_contributor_book_role', columns: ['contributor_id', 'book_id', 'role'])]`; Symfony `#[UniqueEntity(fields: ['contributor', 'book', 'role'])]` validator (depends on T002, T003)
- [X] T007 [P] [US1] Create `src/EntityListener/BookSoftDeleteListener.php` — implement `onFlush(OnFlushEventArgs $event)`: get `UnitOfWork`; iterate scheduled entity updates; for each `Book` with `deletedAt` changing from null to non-null, iterate its `$contributions`, set same `deletedAt` on each, call `UnitOfWork::recomputeSingleEntityChangeSet()` for each Contribution — no `flush()` call inside the listener; inject `EntityManagerInterface` via constructor
- [X] T008 [US1] Create `src/Repository/ContributorRepository.php` — extends `ServiceEntityRepository`; method `findBySlugAndRole(string $slug, ContributionRole $role): ?Contributor` using single DQL: `SELECT c, contrib, b FROM App\Entity\Contributor c INNER JOIN c.contributions contrib INNER JOIN contrib.book b WHERE c.slug = :slug AND contrib.role = :role ORDER BY COALESCE(b.frenchPublicationYear, 9999) ASC, b.title ASC`; returns null when slug unknown OR contributor has no contributions of requested role (both → 404) (depends on T003, T006)
- [X] T009 [US1] Modify `src/Entity/Book.php` — remove `$authors` (ManyToMany → Author, join table book_author), `$illustrators` (ManyToMany → Illustrator, join table book_illustrator), `$translator` (ManyToOne → Translator, column translator_id); add `$contributions` OneToMany → Contribution (orphanRemoval: false); add `$deletedAt` DATETIME NULL (Gedmo SoftDeleteable); register `BookSoftDeleteListener` as `#[ORM\EntityListeners]`; remove all Author/Illustrator/Translator imports and type hints (depends on T006, T007)
- [X] T010 [P] [US1] Create `src/DataFixtures/Factory/ContributorFactory.php` — Foundry factory; defaults: firstName (faker name), lastName (faker lastName), pseudo null, biography faker paragraph; include `withoutPortrait()` state that sets `portraitImage: null` (depends on T003)
- [X] T011 [US1] Create `src/DataFixtures/Factory/ContributionFactory.php` — Foundry factory; defaults: contributor via ContributorFactory, book via existing BookFactory, role ContributionRole::Author, details null (depends on T006, T010)
- [X] T012 [US1] Update `src/DataFixtures/AppFixtures.php` — remove all Author/Illustrator/Translator fixture calls; create SC-006 fixtures: (1) Author-only contributor with portraitImage, (2) Illustrator-only contributor with portraitImage, (3) multi-role contributor (Author + Illustrator + Traductor roles on different books), (4) contributor without portraitImage using `withoutPortrait()` state, (5) ensure at least one Book fixture has no cover image (depends on T010, T011)
- [X] T013 [US1] Remove legacy files: delete `src/Entity/Author.php`, `src/Entity/Illustrator.php`, `src/Entity/Translator.php`, `src/DataFixtures/Factory/AuthorFactory.php`, `src/Twig/Components/Author/Card.php`; remove `src/Repository/AuthorRepository.php`, `src/Repository/IllustratorRepository.php`, `src/Repository/TranslatorRepository.php` if they exist; remove any remaining import/use statements, type hints, or references to Author/Illustrator/Translator classes across all PHP and Twig files; update `templates/test/quickstart.html.twig` to remove `<twig:Author:Card />` usages (replace with `<twig:Contributor:Card />` after T030) (depends on T009, T012)
- [X] T014 [US1] Generate Doctrine migration: run `php bin/console doctrine:migrations:diff`, review generated file in `migrations/`; verify migration creates `contributor` and `contribution` tables, drops `author`, `book_author`, `illustrator`, `book_illustrator`, `translator` tables, removes `book.translator_id`, adds `book.deleted_at` and `contributor.deleted_at`; run `php bin/console doctrine:migrations:migrate --no-interaction` (depends on T009, T013)

### Tests for User Story 1

- [X] T015 [P] [US1] Write `tests/Unit/Entity/ContributorTest.php` — test UUID generated in constructor (not null, valid Uuid instance); test all field getters/setters round-trip; confirm Gedmo `SoftDeleteable` annotation present via reflection (depends on T003)
- [X] T016 [P] [US1] Write `tests/Unit/Service/ContributorSluggerTest.php` — test: pseudo used as slug source when non-null; firstName+lastName used when pseudo null; accented characters stripped to ASCII (é→e, ü→u); collision suffix appended (`john-doe` exists → new slug is `john-doe-2`); mock ContributorRepository for uniqueness checks (depends on T004, T008)
- [X] T028 [P] [US1] Write `tests/Unit/Entity/ContributionTest.php` — test UUID generated in constructor; field getters/setters round-trip; role must accept all `ContributionRole` enum cases; confirm `#[ORM\UniqueConstraint]` and `#[UniqueEntity]` annotations present via reflection; confirm `deletedAt` field present and nullable (Constitution §V: all main entities must have tests) (depends on T006)
- [X] T029 [P] [US1] Write `tests/Integration/Entity/ContributorSoftDeleteTest.php` — verify soft-delete Contributor DOES NOT cascade to Contribution rows: soft-delete a Contributor (set `deletedAt`), flush, assert all linked Contribution rows still have `deletedAt = null`; use a real database (SQLite test env) (depends on T003, T006)

**Checkpoint**: Data model live, migration applied, fixtures load without error — US1 independently verifiable via `php bin/console doctrine:schema:validate` and `php bin/phpunit tests/Unit/`.

---

## Phase 4: User Story 2 — Author Profile Page (Priority: P2)

**Goal**: Public GET `/authors/{slug}` renders contributor header + text-focused bibliography. Returns 404 for unknown slug or non-author contributor.

**Independent Test**: Navigate to `/authors/{slug}` with a valid Author contributor. Confirm 200, biography rendered (if set), books ordered by year ASC. Navigate to `/authors/{unknown}` and to `/authors/{illustrator-only-slug}` — both return 404.

### Implementation for User Story 2

- [X] T017 [US2] Create `src/Controller/ContributorController.php` — `#[Route('/authors/{slug}', name: 'app_author_show')]` action `authorShow(string $slug)`: fetch via `ContributorRepository::findBySlugAndRole($slug, ContributionRole::Author)`; throw `$this->createNotFoundException()` if null; render `contributeur/author_show.html.twig` passing `contributor` and `contributions` (from `$contributor->getContributions()`) variables (depends on T008)
- [X] T018 [US2] Create `templates/contributeur/author_show.html.twig` — extends base layout; `<main>` landmark; contributor header: `<img src="{{ contributor.portraitImage }}" alt="{{ contributor.firstName }} {{ contributor.lastName }}">` when portraitImage not null, else CSS initials avatar `<span aria-label="{{ contributor.firstName }} {{ contributor.lastName }}">` with initials from `pseudo[0]` or `firstName[0]`; biography `<section>` suppressed when `contributor.biography` is null; ordered book list: title + frenchPublicationYear per contribution; suppress `details` when null; Bootstrap mobile-first; design system tokens; semantic landmarks (`<main>`, `<header>`, `<section>`) (depends on T017)

### Tests for User Story 2

- [X] T019 [US2] Write `tests/Controller/ContributorControllerTest.php` — author route: GET `/authors/{valid-author-slug}` returns 200; GET `/authors/unknown-slug` returns 404; GET `/authors/{illustrator-only-slug}` returns 404; soft-delete the author Contributor fixture and assert same slug returns 404; assert portrait `<img>` has non-empty `alt` attribute; use WebTestCase and load fixtures before assertions (depends on T017, T018)

**Checkpoint**: Author profile page functional, controller tested — US2 independently verifiable.

---

## Phase 5: User Story 3 — Illustrator Profile Page (Priority: P2)

**Goal**: Public GET `/illustrators/{slug}` renders contributor header + responsive cover grid. Missing covers render neutral placeholder tile with `alt="Cover not available"`.

**Independent Test**: Navigate to `/illustrators/{slug}` with a valid Illustrator contributor. Confirm 200, cover grid displayed with correct tiles. Navigate to `/illustrators/{unknown}` and `/illustrators/{author-only-slug}` — both return 404.

### Implementation for User Story 3

- [X] T020 [US3] Add `#[Route('/illustrators/{slug}', name: 'app_illustrator_show')]` action `illustratorShow(string $slug)` to `src/Controller/ContributorController.php` — fetch via `findBySlugAndRole($slug, ContributionRole::Illustrator)`; throw 404 if null; render `contributeur/illustrator_show.html.twig` with `contributor` and `contributions` variables (depends on T017)
- [X] T021 [US3] Create `templates/contributeur/illustrator_show.html.twig` — extends base layout; contributor header (portrait/avatar, no biography section); responsive Bootstrap cover grid (`row row-cols-2 row-cols-md-3 row-cols-lg-4`): each cell renders `<img src="{{ contribution.book.coverImage }}" alt="{{ contribution.book.title }}">` linked to book detail page, OR neutral placeholder tile (same aspect ratio via CSS padding-top trick, neutral bg token, `alt="Cover not available"`) when coverImage is null/missing; mobile-first; design system tokens (depends on T020)

### Tests for User Story 3

- [X] T022 [US3] Add illustrator route tests to `tests/Controller/ContributorControllerTest.php` — GET `/illustrators/{valid-illustrator-slug}` returns 200; GET `/illustrators/unknown-slug` returns 404; GET `/illustrators/{author-only-slug}` returns 404; assert cover `<img>` elements have non-empty `alt` attribute; assert placeholder tile has `alt="Cover not available"` (depends on T020, T021)

**Checkpoint**: Illustrator profile page functional and tested — US3 independently verifiable.

---

## Phase 6: User Story 4 — Traductor Profile Page (Priority: P3)

**Goal**: Public GET `/traductors/{slug}` renders contributor header + translated book list. Returns 404 for unknown slug or non-traductor contributor.

**Independent Test**: Navigate to `/traductors/{slug}` with a valid Traductor contributor. Confirm 200, book list displayed. Navigate to `/traductors/{unknown}` — confirm 404.

### Implementation for User Story 4

- [X] T023 [US4] Add `#[Route('/traductors/{slug}', name: 'app_traductor_show')]` action `traductorShow(string $slug)` to `src/Controller/ContributorController.php` — fetch via `findBySlugAndRole($slug, ContributionRole::Traductor)`; throw 404 if null; render `contributeur/traductor_show.html.twig` with `contributor` and `contributions` variables (depends on T020)
- [X] T024 [US4] Create `templates/contributeur/traductor_show.html.twig` — extends base layout; contributor header (portrait/avatar; biography section suppressed when null); ordered book list: title + frenchPublicationYear per contribution; suppress `details` when null; Bootstrap mobile-first; design system tokens; semantic landmarks (depends on T023)

### Tests for User Story 4

- [X] T025 [US4] Add traductor route tests to `tests/Controller/ContributorControllerTest.php` — GET `/traductors/{valid-traductor-slug}` returns 200; GET `/traductors/unknown-slug` returns 404 (depends on T023, T024)

**Checkpoint**: All three profile routes functional and fully tested.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Update shared templates and final validation pass.

- [X] T026 [P] Update `templates/livre/show.html.twig` — replace legacy `authors` / `illustrators` / `translator` byline blocks with a loop over `book.contributions`; group or display by role; remove all Author/Illustrator/Translator entity references; verify design system tokens maintained
- [X] T030 [P] Create `src/Twig/Components/Contributor/Card.php` — Twig UX component mirroring `Author/Card.php` shape: public properties `name`, `avatarUrl`, `bookCount`, `loading`; `#[PostMount]` defaulting `name` to `'Contributeur inconnu'`; update `templates/test/quickstart.html.twig` to replace all `<twig:Author:Card />` usages with `<twig:Contributor:Card />` (depends on T003, T013)
- [X] T027 Final validation: run `php bin/phpunit` (all tests green); run `php bin/console doctrine:schema:validate` (schema valid); run `php bin/console doctrine:fixtures:load --no-interaction` (no errors); verify SC-001 by grepping for `Author::`, `Illustrator::`, `Translator::` AND `Author\Card`, `Author/Card` (all zero results expected)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 — BLOCKS all entity work
- **US1 (Phase 3)**: Depends on Phase 2 — builds the entire data model
- **US2 (Phase 4)**: Depends on Phase 3 — requires Contributor entity, ContributorRepository, migration applied, fixtures loaded
- **US3 (Phase 5)**: Depends on Phase 4 — ContributorController.php must exist from T017
- **US4 (Phase 6)**: Depends on Phase 5 — illustratorShow action from T020 provides controller ordering
- **Polish (Phase 7)**: Depends on Phases 3–6; T030 also depends on T013

### User Story Dependencies

- **US1 (P1)**: Foundational phase complete — no story dependencies
- **US2 (P2)**: US1 complete (Contributor entity + ContributorRepository + migration + fixtures must exist)
- **US3 (P2)**: US2 complete (ContributorController.php base from T017 must exist)
- **US4 (P3)**: US3 complete (illustratorShow from T020 establishes controller ordering pattern)

### Within Each User Story

- ContributionRole enum before any entity that references it
- Contributor entity before ContributorSlugger (repository dependency for uniqueness)
- ContributorSlugger before ContributorListener (listener calls slugger)
- Contribution entity before ContributorRepository (repository query joins contributions)
- Repository before controller
- Controller before template
- Template before controller tests

### Parallel Opportunities

- **US1**: T003 (Contributor entity), T004 (ContributorSlugger), T007 (BookSoftDeleteListener) can run in parallel — different files, no mutual dependencies
- **US1**: T010 (ContributorFactory) can run in parallel with T005, T006, T007, T008 once T003 is done
- **US1**: T015 (ContributorTest), T016 (ContributorSluggerTest), T028 (ContributionTest), T029 (ContributorSoftDeleteTest) can run in parallel — different test files

---

## Parallel Example: User Story 1

```bash
# After T002 (ContributionRole) is done, run in parallel:
T003: Create src/Entity/Contributor.php
T004: Create src/Service/ContributorSlugger.php
T007: Create src/EntityListener/BookSoftDeleteListener.php

# After T003 + T004 done, run in parallel:
T005: Create src/EntityListener/ContributorListener.php
T006: Create src/Entity/Contribution.php
T010: Create src/DataFixtures/Factory/ContributorFactory.php
T015: Write tests/Unit/Entity/ContributorTest.php
T028: Write tests/Unit/Entity/ContributionTest.php  (depends T006)
T029: Write tests/Integration/Entity/ContributorSoftDeleteTest.php  (depends T003, T006)
```

---

## Implementation Strategy

### MVP (User Story 1 Only)

1. Complete Phase 1: Setup (T001)
2. Complete Phase 2: Foundational (T002)
3. Complete Phase 3: User Story 1 (T003–T016)
4. **STOP and VALIDATE**: `php bin/console doctrine:schema:validate` + fixture load + unit tests
5. Data model complete and verified — deploy if sufficient

### Incremental Delivery

1. Setup + Foundational → Foundation ready
2. US1 → Data model live, migration applied, fixtures valid (MVP)
3. US2 → Author profile route live and tested
4. US3 → Illustrator profile route live and tested
5. US4 → Traductor profile route live and tested
6. Polish → livre/show.html.twig updated, full test suite green

---

## Notes

- [P] tasks = different files, no dependencies between them
- [Story] label maps to user story for traceability
- Constitution V mandates tests: ContributorTest, ContributionTest, ContributorSluggerTest, ContributorControllerTest (all 3 routes), ContributorSoftDeleteTest
- T013 (legacy removal) must run AFTER all legacy references are replaced (T009, T012)
- T014 (migration) must run AFTER T013 so the diff is clean
- T027 is the final acceptance gate — zero grep hits for Author/Illustrator/Translator classes AND Author\Card/Author/Card confirms SC-001
