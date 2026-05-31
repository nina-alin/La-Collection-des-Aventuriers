---

description: "Task list for Omnibox Global Search implementation"
---

# Tasks: Omnibox Global Search

**Input**: Design documents from `/specs/012-omnibox-global-search/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/search-api.md ✅, quickstart.md ✅

**Tests**: Included — required by Constitution V (plan.md): `GlobalSearchServiceTest` + `SearchControllerTest`.

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: US1–US4 maps to user stories from spec.md
- Exact file paths in all descriptions

---

## Phase 1: Setup

**Purpose**: CSS entry point and DTO classes — preconditions before any backend or frontend story work.

- [X] T001 Create `assets/styles/components/search.css` with `@import url('../../../design/assets/search.css')` and add `@import 'components/search.css'` to `assets/styles/app.scss`
- [X] T002 [P] Create `src/Dto/Search/SearchResultItem.php` as a `readonly class` with constructor: `string $type`, `string $slug`, `string $title`, `string $subtitle`, `?string $thumbnailUrl`, `?string $initials`, `?string $avatarColor`
- [X] T003 [P] Create `src/Dto/Search/SearchResponse.php` as a `readonly class` with constructor: `array $results` and `array $popular` (both typed as `SearchResultItem[]` in docblocks)

**Checkpoint**: DTOs autoloaded, CSS entry wired — backend and frontend work can begin.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Repository search methods, service, and JSON API endpoint for dynamic search — all user stories depend on this backend.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [X] T004 [P] Add `findForGlobalSearch(string $q, int $limit = 5): array` to `src/Repository/BookRepository.php` — Doctrine QueryBuilder with case-insensitive `LIKE :q` on `b.title`, `WHERE b.status = :published`, `setMaxResults($limit)`
- [X] T005 [P] Add `findForGlobalSearch(string $q, int $limit = 3): array` to `src/Repository/CollectionRepository.php` — LIKE on `gc.nom`, no status filter, `setMaxResults($limit)`
- [X] T006 [P] Add `findForGlobalSearch(string $q, int $limit = 3): array` to `src/Repository/ContributorRepository.php` — LIKE on `c.firstName OR c.lastName OR c.pseudo` (`LOWER(CONCAT(...)) LIKE :q` or multi-OR), `setMaxResults($limit)`
- [X] T007 Create `src/Service/GlobalSearchService.php` with `query(string $q): array` — calls repos (T004–T006), maps entities to `SearchResultItem` DTOs (subtitle per type: livre `{ref} · {year} · {author}`, collection `collection · {N} tomes · {author}`, auteur `auteur · {N} fiches`; auteur gets `initials` from first letters of firstName+lastName and `avatarColor` deterministic from slug), merges results array, slices to max 8 total
- [X] T008 Create `src/Controller/SearchController.php` with `#[Route('/api/search', name: 'api_search', methods: ['GET'])]` + `#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]` action: validate `strlen($q) > 100 → 400 JsonResponse`, blank `$q → {"results":[]}`, call `GlobalSearchService::query()`, return `JsonResponse(['results' => $items])`, wrap in try/catch returning `{"results":[]}` on exception

**Checkpoint**: `curl -b 'PHPSESSID=...' 'http://localhost/api/search?q=steve'` returns JSON with typed results.

---

## Phase 3: User Story 1 — Recherche par frappe (Priority: P1) 🎯 MVP

**Goal**: User types in search field → panel opens → live results (livres, collections, auteurs) → click navigates to entity page.

**Independent Test**: Open app as authenticated user, click search field, type "Steve", click an auteur result → `/auteur/steve-jackson` loads.

### Tests for User Story 1

> **NOTE: Write tests FIRST, ensure they FAIL before implementation**

- [X] T009 [P] [US1] Write `tests/Unit/Service/GlobalSearchServiceTest.php` — cover: happy path returns `SearchResultItem[]`, empty `$q` returns `[]`, results capped at 8 total, subtitle correctly formatted per type (livre/collection/auteur), auteur gets non-null `initials` and `avatarColor`
- [X] T010 [P] [US1] Write `tests/Functional/Controller/SearchControllerTest.php` for `GET /api/search` — cover: authenticated + valid `q` returns 200 `{"results":[...]}`, blank `q` returns `{"results":[]}`, `q` > 100 chars returns 400, unauthenticated returns 302 redirect to login

### Implementation for User Story 1

- [X] T011 [US1] Update `templates/components/Layout/Navbar.html.twig` — add `<form class="sh-search" data-controller="search">` with `<input type="search" data-search-target="input" placeholder="Rechercher un livre, un auteur, une collection…">` and `<div class="search-dropdown" hidden data-search-target="panel">` containing `<ul data-search-target="results" role="listbox">` and `<div data-search-target="status" class="sr-only">` (hidden status region)
- [X] T012 [US1] Create `assets/controllers/search_controller.js` Stimulus controller — targets: `inputTarget`, `panelTarget`, `resultsTarget`, `statusTarget`; `connect()`: bind `document` click for close-outside; `focus` → show panel; `input` → debounce 300ms, abort previous `AbortController`, fetch `GET /api/search?q=${q}`, timeout 5000ms; on fetch start: inject 3 `<li class="search-skeleton">` placeholders into `resultsTarget`; on response: clear placeholders, call `renderItems(results)` or inject "Aucun résultat" `<li>` if empty; `renderItem(item)`: returns `<li role="option" id="search-option-${i}"><a href="/${item.type === 'livre' ? 'livre' : item.type === 'collection' ? 'collection' : 'auteur'}/${item.slug}">...</a></li>` with thumbnail or initials-avatar fallback, title, subtitle, type badge; on item click: navigate via `window.location.href`; close panel on outside click (mirrors `suggestion-autocomplete_controller.js` pattern)

**Checkpoint**: US1 fully functional — search field, live results, click-to-navigate. `php bin/phpunit --filter GlobalSearchService` passes.

---

## Phase 4: User Story 2 — Suggestions pré-saisie contextuelles (Priority: P2)

**Goal**: Before typing, panel shows "Recherches Récentes" (in-memory session FIFO, max 5) and "Souvent Consultés" (popular entities from backend, max 4). Both hidden on first keystroke, restored on field clear.

**Independent Test**: Open panel without typing → both sections visible with data or elegant empty state. Perform a search, reopen panel → typed term in "Recherches Récentes".

### Tests for User Story 2

- [X] T013 [US2] Extend `tests/Functional/Controller/SearchControllerTest.php` with `GET /api/search/popular` tests: authenticated returns 200 `{"popular":[...]}`, unauthenticated returns 302, service exception degrades to `{"popular":[]}`

### Implementation for User Story 2

- [X] T014 [P] [US2] Add `findMostPopular(int $limit = 4): array` to `src/Repository/BookRepository.php` — `ORDER BY SIZE(b.reviews) DESC` (or subquery count), PUBLISHED only, `setMaxResults($limit)`
- [X] T015 [P] [US2] Add `findMostPopular(int $limit = 2): array` to `src/Repository/CollectionRepository.php` — `ORDER BY SIZE(gc.books) DESC`, `setMaxResults($limit)`
- [X] T016 [P] [US2] Add `findMostPopular(int $limit = 2): array` to `src/Repository/ContributorRepository.php` — `ORDER BY SIZE(c.contributions) DESC`, `setMaxResults($limit)`
- [X] T017 [US2] Add `findPopular(): array` to `src/Service/GlobalSearchService.php` — calls `BookRepository::findMostPopular(4)`, `CollectionRepository::findMostPopular(2)`, `ContributorRepository::findMostPopular(2)`; merge strategy: `array_merge($books, $collections, $contributors)` then `array_slice(..., 0, 4)` (books-first — deterministic, reflects catalog reality where books outnumber other types); maps to `SearchResultItem` DTOs; each repo call wrapped in try/catch so a single failing source degrades silently (omit, rest continue)
- [X] T018 [US2] Add `#[Route('/api/search/popular', name: 'api_search_popular', methods: ['GET'])]` + `#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]` action to `src/Controller/SearchController.php`: call `GlobalSearchService::findPopular()`, return `JsonResponse(['popular' => $items])`, catch all exceptions and return `{"popular":[]}`
- [X] T019 [US2] Extend `assets/controllers/search_controller.js` — add `this._history = []` (FIFO max 5, dedup-move-to-front per FR-023); on panel open: fetch `GET /api/search/popular`, render "Souvent Consultés" `<section>` with `(N)` counter and items (hide section on fetch error); render "Recherches Récentes" `<section>` from `this._history` with clock icon per item and `(N)` counter (hide if empty); render pre-saisie header "COMMENCE À ÉCRIRE_" with ↑↓ ESC legend; on `input` first char: hide pre-saisie sections + header, switch to results mode; on field clear (`value === ''`): restore pre-saisie state; after result-click or Enter: push trimmed query to `this._history`

**Checkpoint**: US2 functional — pre-saisie sections show/hide correctly. History grows. Popular items load from `/api/search/popular`.

---

## Phase 5: User Story 3 — Navigation entièrement au clavier (Priority: P3)

**Goal**: ↑↓ moves visual focus through all visible panel items via `aria-activedescendant`. Enter validates. Escape closes and returns focus to input. Full WCAG 2.1 AA combobox.

**Independent Test**: Open panel via Tab/focus, press ↓ → first item highlighted, press Enter → navigates. Press Escape → panel closes, cursor back in input field.

- [X] T020 [US3] Update `templates/components/Layout/Navbar.html.twig` — add on `<input>`: `role="combobox"`, `aria-expanded="false"` (Stimulus toggles), `aria-controls="search-listbox"`, `aria-haspopup="listbox"`, `aria-autocomplete="list"`; add `id="search-listbox"` on the results `<ul>`; add `aria-live="polite"` and `aria-atomic="true"` on the status `<div data-search-target="status">`
- [X] T021 [US3] Extend `assets/controllers/search_controller.js` — add `this._activeIndex = -1`; `keydown` handler on input: `ArrowDown` increments index through all visible `[role=option]` items (clamp at last), `ArrowUp` decrements (clamp at -1), update `input.setAttribute('aria-activedescendant', 'search-option-' + this._activeIndex)` and add/remove `.is-active` CSS class on items; `Enter` with `_activeIndex >= 0`: follow `href` of active `<a>`; `Escape`: hide panel, call `this.inputTarget.focus()`; toggle `aria-expanded` on open/close; on each render: set `this.statusTarget.textContent = '${count} résultats'` for screen reader announcement; reset `this._activeIndex = -1` on each new debounce cycle

**Checkpoint**: US3 functional — full keyboard navigation without mouse. Screen reader announces result count. `aria-activedescendant` updates correctly.

---

## Phase 6: User Story 4 — Accès à la recherche avancée (Priority: P4)

**Goal**: "Recherche avancée dans le Catalogue →" link always anchored at panel bottom. Enter with no selection redirects to `/catalogue?q=:query`.

**Independent Test**: Open panel → footer link visible → click → `/catalogue` loads. Type "magic", press Enter without selection → arrives at `/catalogue?q=magic`.

- [X] T022 [P] [US4] Update `templates/components/Layout/Navbar.html.twig` — add `<div class="search-footer">` inside panel: `<a href="{{ path('app_catalogue') }}" class="search-advanced-link" tabindex="0">Recherche avancée dans le Catalogue →</a>` (Tab-only, NOT `role="option"`, not in ↑↓ per FR-012) and `<span class="search-esc-hint">ESC FERMER</span>` (FR-013)
- [X] T023 [US4] Extend `assets/controllers/search_controller.js` `keydown` handler — `Enter` branch: if `this._activeIndex === -1` and `this.inputTarget.value.trim() !== ''` → `window.location.href = '/catalogue?q=' + encodeURIComponent(this.inputTarget.value.trim())`; skip if value empty

**Checkpoint**: US4 functional — footer always visible. Enter-without-selection redirects to catalogue with query.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Skeleton animation CSS, mobile layout, controller cleanup, full test run, manual validation.

- [X] T024 [P] Add `.search-skeleton` shimmer to `assets/styles/components/search.css` — `@keyframes search-shimmer` with `background: linear-gradient(90deg, ...)` shift, `.search-skeleton-item` with `animation: search-shimmer 1.2s infinite`, block structure matching result item (40×40px indicator + two lines)
- [X] T025 [P] Verify `assets/styles/components/search.css` has `@media (max-width: 719px)` overrides — panel `position: fixed; width: 100%; left: 0; top: var(--navbar-height)` matching `design/assets/search.css` mobile spec (SC-005)
- [X] T026 [P] Review `assets/controllers/search_controller.js` `disconnect()` lifecycle — remove document `click` listener and cancel any pending debounce timer (`clearTimeout`) and pending `AbortController` to prevent memory leaks on Turbo navigation
- [X] T027 Run `php bin/phpunit --filter Search` — all tests in `GlobalSearchServiceTest` and `SearchControllerTest` must pass; fix any failures
- [ ] T028 Manual quickstart.md walkthrough — verify all 4 independent test scenarios from spec.md: type and navigate (US1), pre-saisie sections (US2), keyboard nav ↑↓/Enter/Escape (US3), footer link and Enter-to-catalogue (US4); verify edge cases: empty field restores pre-saisie, "Aucun résultat" on no match, API error silently hides section, text truncation via ellipsis, mobile <720px layout. **Perf gate (SC-002/SC-003)**: open browser DevTools → Network tab, click search field and measure time-to-first-byte + DOMContentLoaded delta for panel paint (must be ≤300ms, SC-002); after typing 3+ chars, measure response-received → results-rendered delta (must be ≤500ms, SC-003).

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on T002–T003 (DTOs) — **BLOCKS all user stories**
- **US1 (Phase 3)**: Depends on Phase 2 completion — independent of US2–US4
- **US2 (Phase 4)**: Depends on Phase 2; extends US1 Stimulus controller (T012 must exist before T019)
- **US3 (Phase 5)**: Depends on Phase 2; extends US1 Stimulus controller (T012) and template (T011)
- **US4 (Phase 6)**: Depends on Phase 2; extends US1 Stimulus controller (T012) and template (T011)
- **Polish (Phase 7)**: Depends on all desired user stories complete

### User Story Dependencies

- **US1 (P1)**: Can start after Phase 2. No dependency on US2–US4.
- **US2 (P2)**: Can start after Phase 2. Requires T012 (Stimulus core) before T019 (pre-saisie extension).
- **US3 (P3)**: Can start after Phase 2. Requires T011 (template) before T020 (ARIA update) and T012 before T021.
- **US4 (P4)**: Can start after Phase 2. T022 (template footer) independent of T023 (Stimulus Enter behavior).

### Within Each Phase

- T002, T003, T004, T005, T006 — all different files, fully parallel
- T007 depends on T004–T006; T008 depends on T007
- T009, T010 (US1 tests) — different files, parallel
- T011, T012 (US1 template + Stimulus) — T012 can reference T011 HTML structure but different files; T011 first
- T014, T015, T016 (US2 popular repo methods) — different files, fully parallel
- T017 depends on T014–T016; T018 depends on T017; T019 depends on T012

### Parallel Opportunities

```bash
# Phase 1 DTO creation (different files):
T002: src/Dto/Search/SearchResultItem.php
T003: src/Dto/Search/SearchResponse.php

# Phase 2 repository search methods (different files):
T004: BookRepository.findForGlobalSearch()
T005: CollectionRepository.findForGlobalSearch()
T006: ContributorRepository.findForGlobalSearch()

# US1 tests (different files):
T009: GlobalSearchServiceTest
T010: SearchControllerTest (GET /api/search)

# US2 popular repository methods (different files):
T014: BookRepository.findMostPopular()
T015: CollectionRepository.findMostPopular()
T016: ContributorRepository.findMostPopular()

# Phase 7 polish (CSS files + test run):
T024: .search-skeleton CSS
T025: mobile @media CSS
T026: disconnect() cleanup review
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: T001–T003 (CSS + DTOs)
2. Complete Phase 2: T004–T008 (repos + service + controller)
3. Complete Phase 3: T009–T012 (US1 tests + Twig + Stimulus)
4. **STOP and VALIDATE**: `php bin/phpunit --filter GlobalSearchService` + `php bin/phpunit --filter SearchController` pass; type "Steve" in search bar, click result, navigate to entity page
5. Deploy/demo if ready

### Incremental Delivery

1. Phase 1 + Phase 2 → Backend search API functional
2. Phase 3 (US1) → **MVP**: search, results, click-to-navigate
3. Phase 4 (US2) → **+** session history + popular pre-saisie
4. Phase 5 (US3) → **+** full keyboard navigation + WCAG ARIA
5. Phase 6 (US4) → **+** "Recherche avancée" link + Enter-to-catalogue
6. Phase 7 → Tests pass, skeleton animation, mobile layout, memory-safe

---

## Notes

- `[P]` = parallel-safe (different files, no in-flight dependencies within the same phase)
- `[USN]` = traceability label back to spec.md user story
- Tests T009–T010 and T013 are mandatory per Constitution V — not optional
- No DB migration — all entities existing; DTOs are PHP-only classes
- Session history lives in Stimulus controller memory only — no `localStorage`, no backend (FR-003, FR-023)
- `AbortController` mandatory in T012 for cancel-on-new-keystroke (FR-019)
- `aria-activedescendant` approach (NOT roving tabindex) mandated by FR-012
- "Souvent Consultés" data uses review/book/contribution counts as popularity proxy (research.md §1 — no Redis, no new infra)
- `avatarColor` for auteur avatar: derive deterministically from contributor `slug` (e.g. hash mod 5 → one of: cuir, mousse, encre, sang, or) matching search-api.md contract
