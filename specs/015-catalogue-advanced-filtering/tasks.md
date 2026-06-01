# Tasks: Catalogue Page & Advanced Filtering

**Input**: Design documents from `/specs/015-catalogue-advanced-filtering/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/url-filter-params.md ✅, quickstart.md ✅

**Tests**: Included — Constitution Check (plan.md §V) explicitly requires PHPUnit coverage for UserBook entity, BookRepository filter/COUNT/bounds methods, CatalogueController smoke test, and FilterPanelComponent COUNT logic.

**Organization**: Tasks grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story this task belongs to (US1–US5)
- Exact file paths in all descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Remove the existing stub, create the skeleton controller and template directories that all subsequent tasks depend on.

- [X] T001 Remove `DefaultController::catalogue()` stub route and create empty `CatalogueController` skeleton class in `src/Controller/CatalogueController.php`
- [X] T002 [P] Create template directories: `templates/catalogue/` and `templates/components/Catalogue/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Entities, DTO, repositories, service, and fixtures that ALL user stories depend on. No user story work can begin until this phase is complete.

**⚠️ CRITICAL**: This phase MUST be complete before any Phase 3+ task starts.

- [X] T003 Create `UserBookStatus` backed string enum with five cases (`DANS_MA_COLLECTION`, `A_ACHETER`, `A_LIRE`, `LU`, `PAS_DANS_MA_COLLECTION`) in `src/Entity/Enum/UserBookStatus.php`
- [X] T004 Create `UserBook` Doctrine entity with fields (`id`, `user ManyToOne→User`, `book ManyToOne→Book`, `status UserBookStatus`, `isFavorite bool`, `createdAt`, `updatedAt`), UNIQUE constraint `(user_id, book_id)`, indexes `idx_user_book_user_id` / `idx_user_book_book_id`, and `#[ORM\PreUpdate]` lifecycle callback for `updatedAt` in `src/Entity/UserBook.php`
- [X] T005 Add inverse `userBooks OneToMany→UserBook` relationship (`cascade: ['remove']`) to `src/Entity/Book.php`
- [X] T006 Add `repositoryClass: EditorRepository::class` to `#[ORM\Entity]` attribute on `src/Entity/Editor.php` (no schema change)
- [X] T007 Generate Doctrine migration for the `user_book` table (run `php bin/console doctrine:migrations:diff`) and verify SQL in `migrations/` matches the schema in `data-model.md`
- [X] T008 Create `ActiveFilterState` PHP readonly class with properties (`sort`, `editors int[]`, `paragraphMin ?int`, `paragraphMax ?int`, `collectionStatus ?string`, `onlyFavorites bool`, `hideModeration bool`, `searchQuery ?string`, `page int`), static factory `fromRequest(Request $request): self`, `toUrlParams(): array`, and `countActiveFilters(): int` in `src/Dto/ActiveFilterState.php`
- [X] T009 [P] Create `EditorRepository` with `findByNameSearch(string $q, int $limit = 20): array` (DQL `LOWER(e.name) LIKE LOWER(:q)`) and `findWithBookCount(): array` (COUNT aggregate, ORDER BY bookCount DESC) in `src/Repository/EditorRepository.php`
- [X] T010 [P] Create `UserBookRepository` with `findByUserAndBookIds(User $user, int[] $bookIds): array` (batch-load marks for one page) in `src/Repository/UserBookRepository.php`
- [X] T011 Add three methods to `src/Repository/BookRepository.php`: `findParagraphBounds(): array` (SELECT MIN/MAX paragraphs WHERE status=PUBLISHED), `countFiltered(ActiveFilterState $state): int` (SELECT COUNT(DISTINCT b.id) with full WHERE clause), and `findFilteredPaginated(ActiveFilterState $state, int $perPage = 24): Paginator` (full DQL with editors/paragraph/sort/search/collectionStatus/isFavorite JOINs using the join strategy from data-model.md)
- [X] T012 Create `CatalogueService` with `getParagraphBounds(): array`, `getFilteredResults(ActiveFilterState $state): Paginator`, and `getUserBooksForPage(?User $user, int[] $bookIds): array` in `src/Service/CatalogueService.php`
- [X] T013 [P] Add `isFavorite bool`, `isOwned bool`, `isWishlist bool` public props (default `false`) to `src/Twig/Components/Book/Card.php`
- [X] T014 [P] Write PHPUnit tests covering `UserBookStatus` enum values and `UserBook` entity construction, UNIQUE constraint, and `countActiveFilters()` on `ActiveFilterState` in `tests/Entity/UserBookTest.php`
- [X] T015 [P] Write PHPUnit tests covering `BookRepository::findParagraphBounds()`, `countFiltered()` (with and without active filters), and `findFilteredPaginated()` result shape in `tests/Repository/BookRepositoryTest.php`
- [X] T034 [P] Write PHPUnit tests covering `EditorRepository::findByNameSearch()` (LOWER LIKE query, empty-string handling, no-results case) and `findWithBookCount()` (aggregate count, ORDER BY book count DESC) in `tests/Repository/EditorRepositoryTest.php`
- [X] T016 Add `UserBook` fixtures (sample owned/favourite/wishlist entries for dev books) to `src/DataFixtures/AppFixtures.php`

**Checkpoint**: `php bin/phpunit tests/Entity/UserBookTest.php tests/Repository/BookRepositoryTest.php tests/Repository/EditorRepositoryTest.php` must pass. `php bin/console doctrine:migrations:migrate` must succeed.

---

## Phase 3: User Story 1 — Browse and Filter the Catalogue (Priority: P1) 🎯 MVP

**Goal**: Desktop two-column layout with collapsible filter rail, Live Component filter panel (draft state + COUNT button), results grid with book cards (marks), active-filter chips, pagination, and URL-serialized filter state.

**Independent Test**: Open `/catalogue`, apply at least two filters (e.g., two editors + paragraph range) via the desktop panel, click "Appliquer", verify result count updates and chips appear in the toolbar.

### Implementation for User Story 1

- [X] T017 [US1] Implement `CatalogueController::index()` — parse `ActiveFilterState::fromRequest()`, call `CatalogueService` for paragraph bounds, filtered results (`Paginator`), and user book marks; redirect to last available page when `page` exceeds total; pass all data to `templates/catalogue/index.html.twig`; add `#[Route('/catalogue', name: 'app_catalogue')]` in `src/Controller/CatalogueController.php`
- [X] T018 [P] [US1] Create `CatalogueExtension` Twig extension with a `filter_url(ActiveFilterState $state, array $overrides): string` helper (builds URL query string via `toUrlParams()` + overrides) in `src/Twig/Extension/CatalogueExtension.php`
- [X] T019 [US1] Create `FilterPanelComponent` Symfony UX Live Component PHP class with writable LiveProps (`sort`, `selectedEditors int[]`, `paragraphMin ?int`, `paragraphMax ?int`, `collectionStatus ?string`, `onlyFavorites bool`, `hideModeration bool`, `editorSearch string` debounced 300 ms), computed `expectedCount int` (via `BookRepository::countFiltered()`), computed `visibleEditors array` (via `EditorRepository::findByNameSearch()`), `#[LiveAction] applyFilters()` returning `RedirectResponse` to `/catalogue?...`, and `#[LiveAction] clearPanel()` resetting draft props to last applied state in `src/Twig/Components/Catalogue/FilterPanelComponent.php`
- [X] T020 [US1] Create `FilterPanelComponent` Twig template with five accordion sections: "TRIER PAR" (five mutually exclusive radio buttons), "ÉDITEUR & COLLECTION" (server-side search input debounced 300 ms + checkbox list showing 5 editors initially + "Voir + X autres" reveal button + editor active-criteria badge), "FORMAT · PARAGRAPHES" (dual-handle range slider with dynamic bounds + preset pills hidden when outside bounds + range badge), "STATUT DANS MA COLLECTION" (omitted entirely from DOM for guests; dropdown + two toggles for authenticated), panel footer with "Effacer" (clearPanel) and "Appliquer — X" (disabled while in-flight, applyFilters) buttons in `templates/components/Catalogue/FilterPanelComponent.html.twig`
- [X] T021 [P] [US1] Modify `templates/components/Book/Card.html.twig` to: render stacked `.cover-marks` icons (red heart for `isFavorite`, green checkmark for `isOwned`, amber cart for `isWishlist`, combinable) in the card top-right corner; apply `title` 2-line clamp (`-webkit-line-clamp: 2`) and `author` 1-line clamp; add hover lift effect (`translateY(-2px)`, `box-shadow: var(--shadow-md)`, `border-color: var(--border-strong)`)
- [X] T022a [US1] Create base structure of `templates/catalogue/index.html.twig` — extends base layout; desktop ≥ 880 px two-column grid (filter rail left, results area right) with chevron collapse toggle; mobile ≤ 879 px hides rail; results toolbar structure: aria-live result count container (FR-009), chip strip with "TOUT EFFACER (X)" link (FR-010/012), quick-sort `<select>` with `data-turbo-action="replace"` and five options (FR-013), grid/list toggle buttons (FR-014); FAB button markup with badge placeholder pinned at bottom of mobile viewport
- [X] T022b [US1] Complete content areas in `templates/catalogue/index.html.twig` — full-screen modal container wrapping `FilterPanelComponent` (wired to `data-controller="catalogue-fab"`); results grid area rendering paginated `Book/Card` components with mark props; skeleton card placeholder region (FR-025b — verify `.skeleton-card` CSS exists in `design/assets/`; implement inline per design reference if absent); pagination block ("Précédent", numbered pages, "Suivant", "Page X sur Y" — FR-027)
- [X] T022c [US1] Wire and finalize `templates/catalogue/index.html.twig` — add `aria-live="polite"` regions for result count changes (FR-009), chip add/remove (FR-010/011), and skeleton→results transition (FR-025b); attach Stimulus controllers via `data-controller` attributes (`catalogue-fab`, `catalogue-view`, `catalogue-search`); confirm all five sort option values match `contracts/url-filter-params.md` canonical keys
- [X] T023 [US1] Write PHPUnit smoke test for `CatalogueController::index()` (unauthenticated GET `/catalogue` returns HTTP 200, page contains results grid, URL params hydrate `ActiveFilterState`; assert `.filter-section--collection-status` is **absent from DOM** for unauthenticated requests per FR-021) in `tests/Controller/CatalogueControllerTest.php`
- [X] T033 [P] [US1] Write PHPUnit tests covering `FilterPanelComponent::expectedCount()` (DQL COUNT returns correct integer for given draft state; falls back gracefully on query failure per FR-023) and `clearPanel()` (draft props reset to last applied state per FR-025a/FR-025c) in `tests/Twig/Components/FilterPanelComponentTest.php`

**Checkpoint**: `php bin/phpunit tests/Controller/CatalogueControllerTest.php tests/Twig/Components/FilterPanelComponentTest.php` passes. Desktop filter apply cycle works end-to-end.

---

## Phase 4: User Story 2 — Filter on Mobile via FAB Modal (Priority: P1)

**Goal**: On ≤ 879 px viewports, FAB is visible with active-filter badge; tapping opens full-screen filter modal with focus trap; "Appliquer" closes modal and refreshes grid.

**Independent Test**: Open `/catalogue` at viewport ≤ 879 px, verify FAB visible and desktop panel hidden, tap FAB, apply one filter, verify modal closes and grid updates, verify FAB badge updates.

### Implementation for User Story 2

- [X] T024 [US2] Create `assets/controllers/catalogue-fab_controller.js` — Stimulus controller that: toggles full-screen modal open/closed on FAB click; traps keyboard focus within the modal when open (Tab/Shift+Tab cycle stays inside); closes modal on Escape key and returns focus to the FAB; exposes target refs for `fab`, `modal`, and `overlay`

**Checkpoint**: At ≤ 879 px viewport, FAB badge shows active filter count, modal opens/closes with correct focus behavior.

---

## Phase 5: User Story 3 — Contextual In-Page Search (Priority: P2)

**Goal**: In-page search bar shows autocomplete dropdown grouped by books/authors (1-char minimum), Enter adds a search chip to active filters, chip × removes it.

**Independent Test**: Type a partial book title in the in-page search, verify dropdown appears grouped by category, press Enter, verify search chip appears and grid filters accordingly.

### Implementation for User Story 3

- [X] T025 [P] [US3] Implement `CatalogueController::searchSuggestions()` for `GET /catalogue/search-suggestions?q=` — returns JSON `{"books": [{id, title, author}], "authors": [{id, name}]}` (max 5+5), scope to `status = PUBLISHED`, minimum query length 1 char, returns empty arrays (not 404) on no match; add `#[Route('/catalogue/search-suggestions', name: 'app_catalogue_search_suggestions')]` in `src/Controller/CatalogueController.php`
- [X] T026 [US3] Create `assets/controllers/catalogue-search_controller.js` — Stimulus controller that: debounces fetch to `/catalogue/search-suggestions?q=` after 1 character; renders grouped dropdown (`LIVRES — N RÉSULTATS`, `AUTEURS — N RÉSULTAT`); hides dropdown on empty results or request failure (silent); on Enter or suggestion click: closes dropdown, replaces any existing search chip (single chip at a time), submits GET navigation adding `q=` to URL params

**Checkpoint**: Type 1+ chars in search bar → dropdown appears; press Enter → search chip in toolbar → grid filtered by query.

---

## Phase 6: User Story 4 — Toggle Grid / List View (Priority: P2)

**Goal**: Grid/list toggle instantly switches results layout; selected mode persists for the session (sessionStorage).

**Independent Test**: Click list-view icon → results display as horizontal rows; click grid icon → revert to vertical cards; apply a filter → view mode unchanged.

### Implementation for User Story 4

- [X] T027 [US4] Create `assets/controllers/catalogue-view_controller.js` — Stimulus controller that: toggles CSS class (`grid-view` / `list-view`) on results container; persists choice to `sessionStorage` under key `catalogueView`; restores persisted value on page load; does not reset on filter apply

**Checkpoint**: List toggle → horizontal rows; grid toggle → vertical cards; choice survives Appliquer.

---

## Phase 7: User Story 5 — Sort Results (Priority: P3)

**Goal**: Toolbar sort dropdown triggers immediate grid reorder (replaceState); bidirectional sync keeps toolbar dropdown and panel "TRIER PAR" radio in agreement.

**Independent Test**: Change sort from toolbar dropdown → grid reorders and panel radio reflects same sort; change sort in panel + click "Appliquer" → toolbar dropdown updates.

### Implementation for User Story 5

- [X] T028 [US5] Wire bidirectional sort sync in `templates/catalogue/index.html.twig`: toolbar `<select>` submits GET form with `data-turbo-action="replace"` (replaceState) and sets `sort=` param; `CatalogueController` already reads `sort` via `ActiveFilterState`; ensure toolbar selected option and panel radio are both set from `activeFilterState.sort` in Twig; verify all five canonical mappings (`note-desc` / `alpha` / `parution-fr` / `parution-orig` / `recent`) render correct panel and toolbar labels per contracts/url-filter-params.md

**Checkpoint**: All five sort options work bidirectionally; toolbar sort change does not add browser history entry.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Accessibility, error handling, and final validation spanning all stories.

- [X] T029 [P] Audit and complete `aria-live="polite"` regions in `templates/catalogue/index.html.twig` and `templates/components/Catalogue/FilterPanelComponent.html.twig`: result count changes (FR-009), chip add/remove (FR-010/011), and skeleton→results transition (FR-025b) must all be announced to screen readers
- [X] T030 [P] Add WCAG 2.1 AA keyboard navigation attributes throughout `templates/components/Catalogue/FilterPanelComponent.html.twig`: accordion chevron buttons (`role="button"`, `aria-expanded`), checkboxes (`aria-label`), slider handles (`role="slider"`, `aria-valuemin`/`aria-valuemax`/`aria-valuenow`), chip × buttons (`aria-label="Retirer le filtre [X]"`), Appliquer/Effacer buttons
- [X] T031 [P] Implement error recovery in `templates/catalogue/index.html.twig` + Stimulus: on "Appliquer" server failure (5xx/network), restore previous grid content and display non-blocking error toast via existing `toast_controller.js`; on paragraph bounds fetch failure at page load, show non-blocking toast and fall back to slider range `[0, 999]`
- [ ] T032 [MANUAL] Run quickstart.md validation: `doctrine:migrations:migrate`, `doctrine:fixtures:load --append`, browse `/catalogue`, apply multi-criteria filter, verify result count chip and URL params; run full PHPUnit suite `php bin/phpunit tests/Entity/UserBookTest.php tests/Repository/BookRepositoryTest.php tests/Repository/EditorRepositoryTest.php tests/Controller/CatalogueControllerTest.php tests/Twig/Components/FilterPanelComponentTest.php`; run `EXPLAIN (ANALYZE, BUFFERS)` on a representative multi-criteria DQL filter query and verify no Seq Scan on `book`, `user_book`, or `editor` tables (SC-001)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 — **BLOCKS all user stories**
- **US1 (Phase 3)**: Depends on Phase 2 — core MVP
- **US2 (Phase 4)**: Depends on T022b (modal container markup exists)
- **US3 (Phase 5)**: Depends on T017 (controller route exists for new route), T022b (search bar markup exists)
- **US4 (Phase 6)**: Depends on T022b (results container markup exists)
- **US5 (Phase 7)**: Depends on T019, T022c (panel + fully wired template both exist)
- **Polish (Phase 8)**: Depends on all user story phases complete

### User Story Dependencies

- **US1 (P1)**: Start after Phase 2 — no dependency on US2–US5
- **US2 (P1)**: Start after T022b — independent of US3/US4/US5
- **US3 (P2)**: Start after T017, T022b — independent of US2/US4/US5
- **US4 (P2)**: Start after T022b — independent of US2/US3/US5
- **US5 (P3)**: Start after T019, T022c — independent of US2/US3/US4

### Within Each Phase

- Foundational: T003 → T004 → T005 (entity chain); T003, T006, T008 can start immediately in parallel; T009/T010 after T006/T004; T011 after T008; T012 after T008, T010, T011; T014/T015 parallelizable after their entity/repo targets
- US1: T017 after T008, T012; T018 parallel; T019 after T008, T009, T011; T020 after T019; T021 after T013; T022a after T017, T018, T021; T022b after T022a, T020; T022c after T022b; T023 after T017; T033 after T019

---

## Parallel Execution Examples

### Foundational Phase (Phase 2) — Start Together

```
Parallel group A (no dependencies):
  T003  Create UserBookStatus enum
  T008  Create ActiveFilterState DTO

After T003 completes:
  T004  Create UserBook entity

After T004 completes:
  T005  Add inverse relationship to Book
  T010  Create UserBookRepository
  T014  Write UserBook PHPUnit tests

After T006 completes:
  T009  Create EditorRepository

After T008 completes:
  T011  Add BookRepository filter methods
  T015  Write BookRepository PHPUnit tests

After T009 completes:
  T034  Write EditorRepository PHPUnit tests

After T008, T010, T011 complete:
  T012  Create CatalogueService
```

### User Story 1 (Phase 3) — Parallelizable Groups

```
After T008, T009, T011 (from Phase 2):
  T018  Create CatalogueExtension (parallel)
  T019  Create FilterPanelComponent PHP (parallel)
  T021  Modify Book/Card template (parallel, after T013)

After T019:
  T020  Create FilterPanelComponent Twig template

After T017, T018, T021:
  T022a  Create base catalogue/index.html.twig (layout + toolbar)

After T022a, T020:
  T022b  Add content areas (grid, pagination, modal, skeleton)

After T022b:
  T022c  Wire aria-live regions + Stimulus controllers

After T017:
  T023  Write CatalogueController PHPUnit test

After T019:
  T033  Write FilterPanelComponent PHPUnit test
```

---

## Implementation Strategy

### MVP First (US1 Only — P1 Desktop)

1. Complete Phase 1: Setup (T001–T002)
2. Complete Phase 2: Foundational (T003–T016) — **critical gate**
3. Complete Phase 3: US1 (T017–T023)
4. **STOP and VALIDATE**: `php bin/phpunit`, browse `/catalogue`, apply filters on desktop
5. Ship desktop filtering as MVP

### Incremental Delivery

1. Setup + Foundational → skeleton + all entities/repos ready
2. US1 complete → desktop filter cycle fully functional (MVP)
3. US2 complete → mobile FAB + modal working (both P1 stories done)
4. US3 complete → in-page search autocomplete
5. US4 complete → grid/list toggle
6. US5 complete → sort bidirectional sync
7. Polish → accessibility + error recovery + full validation

---

## Summary

| Phase | Tasks | Story | Parallelizable |
|-------|-------|-------|----------------|
| Setup | T001–T002 | — | T002 |
| Foundational | T003–T016, T034 | — | T003, T006, T008, T009, T010, T013, T014, T015, T034 |
| US1 (P1) | T017–T021, T022a–T022c, T023, T033 | US1 | T018, T021, T023, T033 |
| US2 (P1) | T024 | US2 | — |
| US3 (P2) | T025–T026 | US3 | T025 |
| US4 (P2) | T027 | US4 | — |
| US5 (P3) | T028 | US5 | — |
| Polish | T029–T032 | — | T029, T030, T031 |
| **Total** | **36** | | |

- **US1**: 10 tasks (core MVP; was 7 — T022 split into T022a/b/c, T033 added)
- **US2**: 1 task
- **US3**: 2 tasks
- **US4**: 1 task
- **US5**: 1 task
- **Foundational**: 15 tasks (was 14 — T034 added)
- **Polish**: 4 tasks
