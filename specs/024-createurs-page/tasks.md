# Tasks: Page "Créateurs" — Galerie des Bâtisseurs

**Input**: `specs/024-createurs-page/` — plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅

**Tests**: Included — required per plan.md Phase F and spec Constitution Principle V.

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story label [US1]–[US6]
- Paths are relative to repo root

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Navigation link and SCSS entry point — no DB, no dependencies.

- [X] T001 Add "Créateurs" nav link in templates/base.html.twig (or nav partial) between Catalogue and Suggestions; active class when `app.request.pathInfo` starts with `/createurs` (FR-001, FR-002)
- [X] T002 [P] Create assets/styles/pages/_createurs.scss (scaffold with section comments) and `@import` it in assets/styles/app.scss (or pages.scss entry point)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: DTO, repository query methods, and service — required before any user story.

**⚠️ CRITICAL**: No user story work begins until this phase is complete.

- [X] T003 Create src/Dto/ContributorFilterState.php: `readonly` class; properties `role` ('tous'|'auteur'|'traducteur'|'illustrateur'), `letter` (?string A–Z), `collectionIds` (int[]), `periodMin` (?int), `periodMax` (?int), `nationality` (?string), `bookCountRange` (?string '1-5'|'6-15'|'16-30'|'30+'), `onlyFollowed` (bool), `sort` ('az'|'ouvrages'|'note'), `page` (int ≥ 1); `static fromRequest(Request $request): self` sanitizing/validating all params to defaults on invalid value; `toUrlParams(): array` omitting defaults (role=tous, sort=az, page=1)
- [X] T004 Add `findPaginatedFiltered(ContributorFilterState $state): Paginator` to src/Repository/ContributorRepository.php: LEFT JOIN contributions / book (status=PUBLISHED) / collection; DISTINCT; apply all filter conditions (role, letter UPPER(SUBSTRING(lastName,1,1)), collectionIds IN, periodMin/Max on birthDate/deathDate, nationality, bookCountRange subquery); ORDER BY lastName ASC | bookCount DESC | avgScore DESC; 12 per page
- [X] T005 Add `findAvailableLetters(ContributorFilterState $state): string[]` to src/Repository/ContributorRepository.php: same filter JOINs and WHERE as T004; `SELECT DISTINCT UPPER(SUBSTRING(c.lastName,1,1)) AS letter … ORDER BY letter ASC`; returns `string[]`
- [X] T006 Add `findCardDataBatch(array $ids): array` (SELECT c.id, COUNT(DISTINCT b.id) bookCount, AVG(r.score) avgScore, GROUP_CONCAT(DISTINCT contrib.role) roles WHERE c.id IN(:ids) GROUP BY c.id) and `findTopCollectionsBatch(array $ids): array` (SELECT c.id, col.id, col.nom, COUNT(DISTINCT b.id) cnt GROUP BY c.id, col.id ORDER BY c.id, cnt DESC, col.id DESC; PHP post-process: take top 2 per contributor ID) to src/Repository/ContributorRepository.php
- [X] T006b Add `findRoleCounts(): array` to src/Repository/ContributorRepository.php: `SELECT cr.role, COUNT(DISTINCT c.id) AS cnt FROM contributor c LEFT JOIN contribution cr ON cr.contributor_id = c.id LEFT JOIN book b ON cr.book_id = b.id AND b.status = 'PUBLISHED' GROUP BY cr.role`; PHP post-process: sum all role-specific counts to derive `tous` = COUNT(DISTINCT c.id across all rows); return `['auteur' => N, 'traducteur' => N, 'illustrateur' => N, 'tous' => N]`; contributors with multiple roles are counted in each applicable group (FR-009)
- [X] T007 Create src/Service/ContributeurService.php: `getPaginatedResults(ContributorFilterState $state): Paginator` (delegates to findPaginatedFiltered); `getAvailableLetters(ContributorFilterState $state): string[]` (delegates to findAvailableLetters); `getCardDataBatch(array $ids): array` (calls findCardDataBatch + findTopCollectionsBatch, merges into `[id => [bookCount, avgScore, roles[], topCollections[]]]` map); `countFiltered(ContributorFilterState $state): int` (count of findPaginatedFiltered result); `getRoleCounts(): array` (delegates to findRoleCounts, returns `['auteur' => N, 'traducteur' => N, 'illustrateur' => N, 'tous' => N]`); `getAutocompleteResults(string $q): array` (placeholder stub — implemented in US3 T021)

**Checkpoint**: DTO + repository + service ready — controller and story phases can begin.

---

## Phase 3: User Story 1 — Parcourir la galerie (Priority: P1) 🎯 MVP

**Goal**: Core gallery page with card grid, pagination, and skeleton loading states.

**Independent Test**: Navigate to `/createurs`; cards display avatar/initials, name, role badges, top-2 collections, bookCount, avgScore; pagination works.

### Implementation for User Story 1

- [X] T008 [US1] Create src/Controller/CreateursController.php with `#[Route('/createurs', name: 'app_createurs')]`: `$state = ContributorFilterState::fromRequest($request)`; get paginator via service; redirect to `page=totalPages` if `$state->page > totalPages`; extract `$ids` from paginator; call `getCardDataBatch($ids)` + `getAvailableLetters($state)` + `getRoleCounts()`; render `createurs/index.html.twig` passing `filterState`, `contributors`, `cardData`, `availableLetters`, `roleCounts`, `totalItems`, `totalPages` (FR-009)
- [X] T009 [US1] Create templates/createurs/index.html.twig: extends base layout; active nav "Créateurs"; results grid `data-createurs-target="grid"` rendering each contributor with: avatar circle (initials `{{ c.firstName[0] }}{{ c.lastName[0] }}` fallback when `portraitImage` null), `{% if biography %}` biography block with CSS `line-clamp: 3`, role badges (one per role from `cardData[id].roles`), top-2 collection tags (from `cardData[id].topCollections`), bookCount with `OUVRAGE`/`OUVRAGES` plural, avgScore (`–` if null), static "Suivre" button; `<meta name="createurs-count" content="{{ contributors|length }}">` + skeleton cards `<div class="creator-card creator-card--skeleton">` (circle + 2 text rects + footer band); pagination block pages 1–4 + `…` + last page with `data-turbo-action="replace"`, `disabled` on prev at page 1 / next at last page (FR-003, FR-004, FR-017–FR-022, FR-028)
- [X] T010 [P] [US1] Add creator card styles to assets/styles/pages/_createurs.scss: `.creator-card` grid variant (Bootstrap card + project SCSS); avatar circle with initials fallback; role badge styles; `.creator-card--skeleton` with `@keyframes pulse` (opacity 0.5→1→0.5, duration 1.4s, color `var(--bg-sunken)`); `line-clamp: 3` on biography block

### Tests for User Story 1

- [X] T011 [P] [US1] Create tests/Dto/ContributorFilterStateTest.php: `fromRequest` sanitizes invalid sort/role/letter values to defaults; `toUrlParams` omits role=tous / sort=az / page=1; page minimum enforced at 1 even if param < 1 (F4)
- [X] T012 [P] [US1] Extend tests/Repository/ContributorRepositoryTest.php: `findPaginatedFiltered` with role filter, letter filter, collection filter, period filter, bookCountRange filter; `findAvailableLetters` returns correct letter subset under active role filter; `findCardDataBatch` returns correct bookCount/avgScore/roles[]; `findTopCollectionsBatch` top-2 tie-breaking (equal count → col.id DESC / UUID v7 most recent wins) (F3)
- [X] T013 [P] [US1] Create tests/Service/ContributeurServiceTest.php: `getPaginatedResults` with role/letter/collection/period filters; `getAvailableLetters` changes based on active role filter; `getCardDataBatch` merges bookCount/avgScore/roles/topCollections correctly; top collections equal-count tie-breaking verified (F2)
- [X] T014 [P] [US1] Create tests/Controller/CreateursControllerTest.php: `GET /createurs` → 200; `GET /createurs?role=auteur` → 200; `GET /createurs?letter=A` → 200; `GET /createurs?sort=note` → 200; `GET /createurs?page=999` → redirect to last page (F1)

**Checkpoint**: Gallery renders with real data; pagination and skeleton cards functional.

---

## Phase 4: User Story 2 — Filtrer par métier et index alphabétique (Priority: P2)

**Goal**: Role segmented bar, A–Z index with disabled letters, sort selector, and active filter chips.

**Independent Test**: Click "Auteurs" → only auteurs shown, count updated. Click "A" → only last names starting with A. Disabled letters greyed and non-clickable.

### Implementation for User Story 2

- [X] T015 [US2] Add role bar to templates/createurs/index.html.twig: Tous / Auteurs / Traducteurs / Illustrateurs links with `?role=tous|auteur|traducteur|illustrateur` + `data-turbo-action="replace"` + page reset to 1; active state via `filterState.role == 'auteur'` etc.; render dynamic count per button from `roleCounts` variable passed by controller (e.g., `Auteurs ({{ roleCounts.auteur }})`) (FR-009, FR-010)
- [X] T016 [US2] Add alpha index A–Z to templates/createurs/index.html.twig: loop `['A','B',…,'Z']`; each letter is a link `?letter=X&page=1` + `data-turbo-action="replace"`; add class `alpha-index__letter--disabled` + `aria-disabled="true"` when letter not in `availableLetters`; active state per `filterState.letter` (FR-011, FR-012)
- [X] T017 [US2] Add sort bar + result count to templates/createurs/index.html.twig: "Trier par" select/links with options A→Z / Nombre d'ouvrages / Note moyenne (`?sort=az|ouvrages|note`), active sort highlighted per `filterState.sort`, result count display "N à M sur TOTAL" computed from `totalItems` and current page (FR-026, FR-027)
- [X] T018 [US2] Add active filter chips section to templates/createurs/index.html.twig: one chip per active non-default filter (role, letter, collection, period, nationality, bookCount) rendered as `[TYPE] [valeur]` with × link removing that param and resetting page=1; below-grid empty state block `{% if contributors|length == 0 %}` with message "Aucun créateur ne correspond à vos filtres." + "Effacer les filtres" link (all params cleared) (FR-015, FR-016, FR-029)
- [X] T019 [P] [US2] Add role bar, alpha index, sort, and chip styles to assets/styles/pages/_createurs.scss: `.alpha-index__letter--disabled` (greyed, cursor default); active letter/role state; chip `.filter-chip` with × button; result count typography

**Checkpoint**: Role filter + alpha index functional; disabled letters correct; sort param updates order; chips visible and removable.

---

## Phase 5: User Story 3 — Recherche autocomplete (Priority: P2)

**Goal**: Debounced autocomplete dropdown with grouped role results, term highlighting, AbortController for concurrent requests.

**Independent Test**: Type "jack" → dropdown with grouped "AUTEURS" / "ILLUSTRATEURS" sections, term highlighted in `<mark>` within names.

### Implementation for User Story 3

- [X] T020 [US3] Add `findForAutocomplete(string $q, int $maxPerRole = 5): array` to src/Repository/ContributorRepository.php: ILIKE/LOWER search on firstName + lastName; returns grouped `['auteur' => [...], 'illustrateur' => [...], 'traducteur' => [...]]`; each entry: `slug`, `firstName`, `lastName`, `portraitImage` (nullable), `role`, `bookCount`, `mainCollection` (nullable string), `averageScore` (nullable float); max 5 per role group (FR-005b)
- [X] T021 [US3] Implement `getAutocompleteResults(string $q): array` in src/Service/ContributeurService.php (was stub in T007): delegates to `findForAutocomplete`, returns JSON-ready grouped structure
- [X] T022 [US3] Add `#[Route('/createurs/search', name: 'app_createurs_search')]` to src/Controller/CreateursController.php: `$q = trim($request->query->get('q', ''))`, empty string → `new JsonResponse([])`, else `new JsonResponse($this->service->getAutocompleteResults($q))` (FR-005b)
- [X] T023 [US3] Add search bar + autocomplete dropdown markup to templates/createurs/index.html.twig: `<input data-action="input->createurs#search" data-createurs-target="searchInput" …>`, dropdown container `<div data-createurs-target="searchDropdown" class="creator-search-dropdown d-none">` with grouped section template (filled by JS) (FR-005)
- [X] T024 [US3] Create assets/controllers/createurs_controller.js: targets `grid`, `searchInput`, `searchDropdown`, `viewToggle`; `connect()` placeholder (view restore added in T033); `search(event)` — clear previous timeout, debounce 250ms, abort previous request via `AbortController`, fetch `/createurs/search?q=${q}`, on response render grouped dropdown (category headers + result rows rendered as `<a>` anchor tags linking to the role-specific detail page: `auteur` → `/authors/{slug}`, `illustrateur` → `/illustrators/{slug}`, `traducteur` → `/traductors/{slug}`; each row shows avatar/name/role/stats), wrap search term occurrences in `<mark>` tags; show "Aucun résultat" message when all groups are empty (US3 scenario 4); `clearSearch()` — hide dropdown, clear input; silent catch on AbortError and network errors (FR-005, FR-006, FR-007, FR-008, R-004)
- [X] T025 [P] [US3] Add search dropdown styles to assets/styles/pages/_createurs.scss: `.creator-search-dropdown` positioned below input, `.creator-search-dropdown__group` with category header, `.creator-search-dropdown__item` with avatar/name/role/stats layout, `mark` highlight style

### Tests for User Story 3

- [X] T026 [P] [US3] Extend tests/Controller/CreateursControllerTest.php: `GET /createurs/search?q=` → 200 JSON `[]`; `GET /createurs/search?q=jack` → 200 JSON with grouped result keys; `GET /createurs/search?q=xxxxnotfound` → 200 JSON `[]` (F1)

**Checkpoint**: Autocomplete opens after typing, grouped by role, term highlighted, empty-q returns [], silent failure on error.

---

## Phase 6: User Story 4 — Filtres avancés via panneau latéral (Priority: P3)

**Goal**: LiveComponent off-canvas panel with draft state; applies only on explicit "Appliquer".

**Independent Test**: Select a collection in panel → grid unchanged (draft). Click "Appliquer" → only contributors from that collection shown, chip displayed above grid.

### Implementation for User Story 4

- [X] T027 [US4] Create src/Twig/Components/Contributeur/FilterPanelComponent.php: `#[AsLiveComponent]` + `DefaultActionTrait`; writable LiveProps `selectedCollectionIds[]`, `collectionSearch` (string, default ''), `periodMin`, `periodMax`, `nationalitySearch`, `bookCountRange`, `onlyFollowed`; non-writable applied props from `mount`: `appliedRole`, `appliedLetter`, `appliedSort`, `appliedPage`; `mount(ContributorFilterState $state, CollectionRepository $collectionRepo)` hydrates draft from applied state; `getExpectedCount(): int` via `$this->service->countFiltered($draftState)`; `getVisibleCollections(): array` (filter collection list by `collectionSearch` — NOT `nationalitySearch` — limit 5, show-more pattern); `#[LiveAction] applyFilters(): RedirectResponse` redirects to `/createurs?` + merged params with page=1 forced; `#[LiveAction] clearPanel(): void` resets draft to applied values (FR-013, FR-014, C1 plan)
- [X] T028 [US4] Create templates/components/Contributor/FilterPanelComponent.html.twig: off-canvas panel; Collections section (checkboxes driven by `getVisibleCollections()` + text search input + show-more link); Période section (double range slider + decade shortcut buttons Années 70/80/90/2000s mapped to year ranges, based on birthDate/deathDate); Nationalité section (text search + checkboxes); Nombre d'ouvrages section (radio/toggle buttons 1–5 / 6–15 / 16–30 / 30+); `{% if is_granted('ROLE_USER') %}` onlyFollowed toggle (hidden for anonymous); "Appliquer · {{ getExpectedCount() }}" button; "Réinitialiser" link calling `clearPanel()` (FR-013, FR-014)
- [X] T029 [US4] Add `<twig:Contributeur:FilterPanel :state="filterState" />` and "Filtres avancés" trigger button to templates/createurs/index.html.twig
- [X] T030 [P] [US4] Add filter panel styles to assets/styles/pages/_createurs.scss: off-canvas overlay layout, range slider styling, bookCount range button active states, decade shortcut button group

**Checkpoint**: Panel opens; draft filters do not change grid; "Appliquer" updates grid and generates chips; "Réinitialiser" restores applied state.

---

## Phase 7: User Story 5 — Bouton "Suivre" statique (Priority: P3)

**Goal**: Static visual-only "Suivre" button on each creator card (no JS behavior in this ticket).

**Independent Test**: Card renders with "Suivre" button visible; clicking it does nothing functional (FR-022).

### Implementation for User Story 5

- [X] T031 [US5] Confirm static "Suivre" button HTML is present in templates/createurs/index.html.twig card markup (added in T009) and add `.btn-follow` styling in assets/styles/pages/_createurs.scss matching design/pages/createurs.html reference; no JS handler, no backend route (FR-022, US5 out-of-scope note)

---

## Phase 8: User Story 6 — Basculer entre Vue Grille et Vue Liste (Priority: P4)

**Goal**: Grid/list toggle via Stimulus, persisted in localStorage without page reload.

**Independent Test**: Click list icon → cards render as horizontal rows. Reload page → list view restored from localStorage.

### Implementation for User Story 6

- [X] T032 [US6] Add view toggle buttons (grid icon + list icon) to templates/createurs/index.html.twig sort bar: `data-createurs-target="viewToggle"` on each, `data-action="click->createurs#toggleView"`, active state CSS class on current view (FR-023)
- [X] T033 [US6] Complete `connect()` in assets/controllers/createurs_controller.js: read `localStorage.getItem('lca-createurs-view')`, apply `grid` or `list` class to `this.gridTarget` before first paint; implement `toggleView(event)`: toggle class on `this.gridTarget`, save to `localStorage.setItem('lca-createurs-view', view)` (R-007, FR-023)
- [X] T034 [P] [US6] Add list view styles to assets/styles/pages/_createurs.scss: `.creator-card--list` horizontal row variant (avatar left, name/roles/bookCount/avgScore inline — no biography block, no collection tags)

**Checkpoint**: Toggle works; filtered results preserved on view switch; localStorage persists across reloads.

---

## Phase N: Polish & Cross-Cutting Concerns

**Purpose**: Edge case validation and wiring verification.

- [X] T035 [P] Validate edge case rendering in templates/createurs/index.html.twig: conditional `[nationality][ · dates]` line (omit ` · ` separator if dates null; omit entire line if both null); biography block not rendered when `biography` is null or empty (FR-018); "0 OUVRAGE" display (not "0 OUVRAGES"); pagination `disabled` attribute on Précédent (page 1) and Suivant (last page); FR-029 "Effacer les filtres" resets all params to defaults; guard avatar initials against empty strings: use `{% if c.firstName %}{{ c.firstName[0] }}{% endif %}{% if c.lastName %}{{ c.lastName[0] }}{% endif %}` instead of bare `[0]` subscript (prevents Twig runtime error on empty-string names)
- [X] T036 [P] Register Stimulus controller: confirm `assets/controllers/createurs_controller.js` is declared in `assets/controllers.json` (or equivalent Stimulus `register()` call); verify targets and `data-action` attributes in templates match controller identifiers

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately; T001 ‖ T002
- **Phase 2 (Foundational)**: After Phase 1; T003 → T004 → T005 → T006 → T006b (sequential, same file) → T007; **BLOCKS all stories**
- **Phase 3 (US1)**: After Phase 2; T010 ‖ T011 ‖ T012 ‖ T013 ‖ T014 after T008–T009
- **Phase 4 (US2)**: After Phase 2; best after Phase 3 (extends same template + controller)
- **Phase 5 (US3)**: After Phase 2; T020 extends ContributorRepository (T006 file); T024 creates JS controller; best after Phase 4 (same template)
- **Phase 6 (US4)**: After Phase 2; LiveComponent is independent; best after Phase 5 (same template)
- **Phase 7 (US5)**: After Phase 3 (card markup exists from T009)
- **Phase 8 (US6)**: After Phase 5 (T024 creates the JS controller that T033 extends)
- **Phase N (Polish)**: After all desired phases

### User Story Dependencies

| Story | Depends on | Notes |
|-------|-----------|-------|
| US1 (P1) | Foundational | Independent — no story deps |
| US2 (P2) | Foundational + US1 | Extends same template and controller |
| US3 (P2) | Foundational + US2 | Adds endpoint + JS controller + template section |
| US4 (P3) | Foundational + US2 | LiveComponent integrates with template |
| US5 (P3) | US1 | Card markup from T009 |
| US6 (P4) | US3 | Extends JS controller from T024 |

### Parallel Opportunities

- T001 ‖ T002 (Phase 1 — different files)
- T011 ‖ T012 ‖ T013 ‖ T014 (US1 tests — all different files)
- T010 ‖ T011 ‖ T012 ‖ T013 ‖ T014 (T010 = SCSS, different file from tests)
- T019 ‖ other Phase 4 SCSS tasks
- T025 ‖ T026 (US3 — different files)
- T030 ‖ T028 (US4 — different files)
- T034 ‖ T032 (US6 — different files)
- T035 ‖ T036 (Polish — different files)

---

## Parallel Example: User Story 1

```bash
# After T008 (controller) and T009 (template) are done, launch in parallel:
Task T010: "Add creator card styles to assets/styles/pages/_createurs.scss"
Task T011: "Create tests/Dto/ContributorFilterStateTest.php"
Task T012: "Extend tests/Repository/ContributorRepositoryTest.php"
Task T013: "Create tests/Service/ContributeurServiceTest.php"
Task T014: "Create tests/Controller/CreateursControllerTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup (T001–T002)
2. Phase 2: Foundational (T003–T007) — **CRITICAL, do not skip**
3. Phase 3: User Story 1 (T008–T014)
4. **STOP & VALIDATE**: `GET /createurs` shows real cards with correct data; pagination works
5. Deploy/demo if ready

### Incremental Delivery

1. Setup + Foundational → infrastructure ready
2. US1 → gallery page functional (**MVP**)
3. US2 → role filter + alpha index + sorting
4. US3 → autocomplete search
5. US4 → advanced filter panel
6. US5 → static follow button verified
7. US6 → view toggle
8. Polish → edge cases + wiring

---

## Notes

- `design/pages/createurs.html` is the **final pixel-perfect reference** — do not modify it; read it for template integration
- No new entities, no migrations — all entities pre-exist: Contributor, Contribution, ContributionRole, Collection, Review (R-008)
- `onlyFollowed` filter silently inactive for anonymous users — toggle hidden in panel (FR-014)
- View toggle NOT in URL — localStorage `lca-createurs-view` only (FR-025, R-007)
- Any filter/sort change resets `?page=1` automatically (FR-025)
- Top-2 collections tie-breaking: equal bookCount → `col.id DESC` (UUID v7 most recent wins) (R-001)
- Autocomplete uses AbortController — only last in-flight request applied; silent catch on AbortError (R-004)
- Sort options: A→Z / Nombre d'ouvrages / Note moyenne only — "Les plus suivis" excluded (spec clarification)
