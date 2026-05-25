# Tasks: Book Detail Page (Fiche Œuvre)

**Input**: Design documents from `specs/005-book-detail-view/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/web-routes.md ✅

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1–US4)
- Exact file paths in all descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Install new Composer packages and write their configuration files.

- [X] T001 [P] Require `stof/doctrine-extensions-bundle` ^1.12 via `composer require stof/doctrine-extensions-bundle`
- [X] T002 [P] Require `vich/uploader-bundle` ^2.4 via `composer require vich/uploader-bundle`
- [X] T003 Create `config/packages/stof_doctrine_extensions.yaml` enabling `sluggable: true` for the default ORM (per research.md §1)
- [X] T004 Create `config/packages/vich_uploader.yaml` with `book_cover` and `book_gallery` mappings pointing to `public/uploads/books/covers` and `public/uploads/books/gallery` (per research.md §2)
- [X] T005 Add `TAVERNE_URL=https://taverne-des-aventuriers.example.com` to `.env` (placeholder only — real URL must be set in Platform.sh environment variables, not committed) and document in `.env.example` with comment: "Override per environment; configure in Platform.sh UI for staging/production"
- [X] T026 Update `.platform.app.yaml` — add writable mount `'public/uploads': { source: local, source_path: uploads }` for VichUploader uploads directory; must land in same commit set as T004 (constitution §II — infra changes require platform.sh update) (per research.md §2)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: All entities, repository, migration, and security config that every user story depends on.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [X] T006 [P] Create `src/Entity/Enum/BookStatus.php` — backed enum `string` with cases `PENDING`, `PUBLISHED`, `REJECTED` (per data-model.md)
- [X] T007 [P] Create `src/Entity/Enum/BookImageTab.php` — backed enum `string` with cases `TOME`, `DOS`, `TRANCHE`, `PAGES`, `CARTE` (per data-model.md)
- [X] T008 [P] Create `src/Entity/Author.php` — fields `id`, `firstName`, `lastName`, `slug` (Gedmo Sluggable from `['firstName','lastName']`, unique); inverse ManyToMany `books` mapped by `authors` (per data-model.md)
- [X] T009 [P] Create `src/Entity/Illustrator.php` — identical structure to `Author`; inverse ManyToMany `books` mapped by `illustrators` (per data-model.md)
- [X] T010 [P] Create `src/Entity/Translator.php` — fields `id`, `firstName`, `lastName`, `slug` (Gedmo Sluggable from `['firstName','lastName']`, unique); inverse OneToMany `books` mapped by `translator` (per data-model.md)
- [X] T011 [P] Create `src/Entity/Editor.php` — fields `id`, `name`, `slug` (Gedmo Sluggable from `['name']`, unique); inverse OneToMany `books` mapped by `editor` (per data-model.md)
- [X] T012 Create `src/Entity/Book.php` — all fields from data-model.md table (title, originalTitle, slug, isbn, pages, paragraphs, frenchPublicationYear, originalPublicationYear, editionInfo, saga, volumeNumber, summary, coverImage, status, languages); relations authors ManyToMany, illustrators ManyToMany, translator ManyToOne nullable, editor ManyToOne not-null, galleryImages OneToMany cascade remove orphanRemoval; indexes `idx_book_slug`, `idx_book_status`; UniqueEntity on `isbn`
- [X] T013 Create `src/Entity/BookImage.php` — fields `id`, `tab` (BookImageTab enum), `imagePath` (VichUploader managed), `book` (ManyToOne); unique constraint `uniq_book_tab` on `(book_id, tab)` (per data-model.md)
- [X] T014 Create `src/Repository/BookRepository.php` — implement `findBySlugWithRelations(string $slug): ?Book` using a single DQL query with `LEFT JOIN FETCH` on authors, illustrators, translator, editor, galleryImages to avoid N+1 (per data-model.md)
- [X] T015 Generate Doctrine migration via `php bin/console doctrine:migrations:diff` and review generated file in `migrations/` — tables: `author`, `illustrator`, `translator`, `editor`, `book`, `book_image`, `book_author`, `book_illustrator`; all indexes and unique constraints from data-model.md
- [X] T016 [P] Update `config/packages/security.yaml` — add `role_hierarchy` (`ROLE_MODERATOR: ROLE_USER`, `ROLE_ADMIN: [ROLE_MODERATOR, ROLE_USER]`) and insert `{ path: ^/livre/, roles: PUBLIC_ACCESS }` before the catch-all rule (per research.md §3 and §4)
- [X] T029 [P] Write `tests/Unit/Entity/` persistence tests — `AuthorTest`, `IllustratorTest`, `TranslatorTest`, `EditorTest`, `BookImageTest`: assert field mapping round-trips via EntityManager; confirm `slug` unique constraint enforced; runs after T015 (migration must exist) (per constitution §V: PHPUnit coverage required for all main entities)

**Checkpoint**: All entities migrated, security updated, entity tests written — user story implementation can begin.

---

## Phase 3: User Story 1 — Consulter la fiche d'un livre (Priority: P1) 🎯 MVP

**Goal**: Public route `GET /livre/{slug}` renders full editorial data (header, fiche technique, résumé, cover, SEO) for PUBLISHED books; returns 404 for PENDING/REJECTED to non-moderators.

**Independent Test**: `GET /livre/{slug}` with a PUBLISHED book → 200 with title, authors, editor, cover, résumé, fiche technique table. Same URL with PENDING book + no auth → 404.

- [X] T017 [US1] Create `src/Service/BookAccessChecker.php` — method `assertViewable(Book $book, ?UserInterface $user): void` throws `NotFoundHttpException` when status ≠ PUBLISHED and user lacks ROLE_MODERATOR (per data-model.md Service section)
- [X] T018 [US1] Create `src/Controller/BookController.php` — thin `show(string $slug, Request $request)` action: load via `BookRepository::findBySlugWithRelations`, call `BookAccessChecker::assertViewable`, read `TAVERNE_URL` via `#[Autowire(env: 'TAVERNE_URL')]`, render `livre/show.html.twig` with `book` and `taverneUrl` variables (per contracts/web-routes.md)
- [X] T019 [US1] Create `templates/livre/show.html.twig` — implement header (saga/volume eyebrow hidden when both null, title, originalTitle hidden if null, authors inline comma-separated), fiche technique table (12 rows per FR-005 order, each row hidden if null/empty), résumé section (hidden if summary null/empty), cover image with placeholder SVG alt=book.title, SEO blocks (`{% block title %}`, `{% block meta %}` with: `og:title` = book title; `og:image` = `{{ vich_uploader_asset(book, 'coverImage') | absolute_url }}` — fallback to placeholder SVG `absolute_url` when `coverImage` null; `meta description` = `"{title} par {authors|join(', ')} — {summary|slice(0,160)}"` — fallback to title alone if summary null) conforming to `design/pages/livre.html` (per contracts/web-routes.md and FR-004, FR-005, FR-006, FR-013, FR-016)
- [X] T020 [P] [US1] Write `tests/Unit/Service/BookAccessCheckerTest.php` — PHPUnit tests covering: PUBLISHED book + anonymous → no exception; PUBLISHED book + ROLE_USER → no exception; PENDING book + anonymous → NotFoundHttpException; PENDING book + ROLE_USER → NotFoundHttpException; PENDING book + ROLE_MODERATOR → no exception; REJECTED book + anonymous → NotFoundHttpException; REJECTED book + ROLE_MODERATOR → no exception
- [X] T021 [US1] Write `tests/Functional/Controller/BookControllerTest.php` — PHPUnit functional tests for US1 acceptance scenarios: SC1 (PUBLISHED → 200, header fields present), SC2 (fiche technique rows present), SC3 (PENDING + no auth → 404); create Book entities directly via EntityManager in setUp

**Checkpoint**: `GET /livre/{slug}` fully functional for US1. BookAccessCheckerTest and BookControllerTest green.

---

## Phase 4: User Story 2 — Parcourir la galerie d'images (Priority: P2)

**Goal**: Gallery tab navigation (Tome/Dos/Tranche/Pages/Carte) in the book detail page; WCAG 2.1 AA keyboard + screen-reader accessible.

**Independent Test**: Clicking each tab renders its image; active tab is visually distinguished; tabs without a BookImage are hidden; empty gallery shows "Tome" tab with placeholder SVG.

- [X] T022 [US2] Extend `templates/livre/show.html.twig` — add gallery tab bar with `role="tablist"`, each tab button `role="tab"` and `aria-controls`, tabpanel `role="tabpanel"` and `aria-labelledby`; render only tabs that have a BookImage (exception: show "Tome" with placeholder SVG when no BookImage at all); wire to Stimulus controller `data-controller="gallery"` (per FR-007 and contracts/web-routes.md §Cover+Gallery)
- [X] T023 [US2] Create `assets/controllers/gallery_controller.js` — Stimulus controller handling tab click (show/hide panels, toggle aria-selected), keyboard navigation with ArrowLeft/ArrowRight keys, initial active tab on first available BookImage (per research.md §7)

**Checkpoint**: Gallery tabs functional and WCAG 2.1 AA compliant; US1 unaffected.

---

## Phase 5: User Story 3 — Accéder au lien partenaire "La Taverne" (Priority: P3)

**Goal**: "En discuter sur la Taverne des Aventuriers" button renders on every published book page, opening in a new tab via `TAVERNE_URL` env var.

**Independent Test**: Clicking "En discuter sur la Taverne" opens `TAVERNE_URL` in a new browser tab with `rel="noopener noreferrer"`.

- [X] T024 [US3] Extend `templates/livre/show.html.twig` — add Taverne link block: `<a href="{{ taverneUrl }}" target="_blank" rel="noopener noreferrer">En discuter sur la Taverne des Aventuriers</a>` styled per design system; `taverneUrl` already injected by BookController from T018 (per FR-008 and contracts/web-routes.md §Taverne Link)

**Checkpoint**: Taverne link present on all published book pages; US1 and US2 unaffected.

---

## Phase 6: User Story 4 — Préparer les actions de collection (Priority: P4)

**Goal**: Action bar with 4 buttons (Ma Collection, À lire, À acheter, Favori) visible only to authenticated users, styled per design system, no functional handlers (placeholder for spec 006).

**Independent Test**: Authenticated user sees 4 action bar buttons styled per design system; anonymous visitor sees no action bar.

- [X] T025 [US4] Extend `templates/livre/show.html.twig` — add `{% if app.user %}` block rendering 4 action buttons (Ma Collection, À lire, À acheter, Favori) styled per `design/pages/livre.html`; buttons have no `onclick` handlers (per FR-009)

**Checkpoint**: Action bar visible for authenticated users only; all prior user stories unaffected.

---

## Final Phase: Polish & Cross-Cutting Concerns

- [ ] T027 [P] Visual review of `templates/livre/show.html.twig` against `design/pages/livre.html` — verify vieux grimoire design system (colours, fonts, spacing), responsive breakpoints, placeholder SVG for missing cover, all 12 fiche technique rows, null-masking behaviour
- [X] T028 Run full PHPUnit test suite (`php bin/phpunit`) and confirm BookAccessCheckerTest + BookControllerTest + entity tests (T029) green; fix any regressions

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately; T001 and T002 can run in parallel; T026 can run in parallel with T003–T005 (no bundle install dependency — just YAML edit)
- **Foundational (Phase 2)**: Depends on Phase 1 completion — **BLOCKS all user stories**
  - T006–T011, T016 can all run in parallel within Phase 2
  - T012 depends on T006–T011 and T003–T004 (bundle config must exist for VichUploader annotation)
  - T013 depends on T007, T012
  - T014 depends on T012
  - T015 depends on T012, T013
  - T029 depends on T015 (migration must exist); runs in parallel with T016
- **US1 (Phase 3)**: Depends on Phase 2 completion; T020 can run in parallel with T019
- **US2 (Phase 4)**: Depends on Phase 3 (extends the template created in T019)
- **US3 (Phase 5)**: Depends on Phase 3 (taverneUrl already injected in BookController T018)
- **US4 (Phase 6)**: Depends on Phase 3 (extends the same template)
- **Polish (Final)**: Depends on all desired user stories complete

### User Story Dependencies

- **US1 (P1)**: Starts after Phase 2 — no story dependencies
- **US2 (P2)**: Depends on US1 (extends show.html.twig)
- **US3 (P3)**: Depends on US1 (taverneUrl injection in BookController)
- **US4 (P4)**: Depends on US1 (extends show.html.twig)
- US2, US3, US4 can proceed in parallel once US1 is complete

### Within Each User Story

- Service before controller (T017 → T018)
- Controller before template (T018 → T019)
- Unit tests (T020) can be written in parallel with template (T019)
- Functional tests (T021) after template (T019)

---

## Parallel Example: User Story 1

```bash
# Phase 1 — run together:
Task T001: "Require stof/doctrine-extensions-bundle"
Task T002: "Require vich/uploader-bundle"
Task T026: "Update .platform.app.yaml writable mount"  # parallel with T003-T005

# Phase 2 — run together (after T001, T002):
Task T006: "Create BookStatus enum"
Task T007: "Create BookImageTab enum"
Task T008: "Create Author entity"
Task T009: "Create Illustrator entity"
Task T010: "Create Translator entity"
Task T011: "Create Editor entity"
Task T016: "Update security.yaml"
# After T015 (migration):
Task T029: "Write entity persistence tests"  # parallel with T016

# US1 — run together (after T019):
Task T020: "Write BookAccessCheckerTest"
Task T021: "Write BookControllerTest"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational — CRITICAL
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: `GET /livre/{slug}` returns full page; 404 for PENDING
5. Deploy/demo if ready

### Incremental Delivery

1. Setup + Foundational → Foundation ready
2. US1 → validate independently → **MVP shipped** (fiche livre accessible)
3. US2 → gallery tabs functional
4. US3 → Taverne link live
5. US4 → action bar placeholder visible
6. Polish → visual QA

### Parallel Strategy (if multiple developers)

1. Both run Phase 1 + Phase 2 together
2. Once Foundational complete:
   - Dev A: US1 (T017 → T018 → T019 → T021)
   - Dev B: T020 (unit tests, parallel with T019)
3. Once US1 done:
   - Dev A: US2 (T022, T023)
   - Dev B: US3 (T024) + US4 (T025)

---

## Notes

- [P] tasks = different files, no incomplete dependencies
- [Story] label maps each task to a specific user story for traceability
- Tests are included (required by constitution check V — security + coverage)
- No Foundry — PHPUnit creates entities directly via EntityManager (research.md §5)
- `imagePath` set directly to fixture asset path in tests (VichUploader imageFile virtual property not used — FR-015)
- Do not add `onclick` handlers to US4 action buttons — spec 006 will add them
- Commit after each completed phase checkpoint
- T026 (.platform.app.yaml mount) is in Phase 1 — must not be deferred past T004 (VichUploader config)
