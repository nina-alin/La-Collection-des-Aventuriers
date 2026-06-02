# Tasks: Personal Library Lists (Listes Livre)

**Feature**: 016-personal-library-lists
**Input**: Design documents from `/specs/016-personal-library-lists/`
**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/ ✅

**Tests**: PHPUnit tests included — required per plan.md (UserBookService unit tests + Live Component functional tests).

**Organization**: Tasks grouped by user story. Phase 2 (foundation) must complete before user story phases begin.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: Maps to user story (US1–US5 from spec.md)
- Exact file paths in descriptions

---

## Phase 1: Setup (Existing Project Audit)

**Purpose**: Confirm current code state before migration. No new infrastructure.

- [X] T001 Audit src/Entity/UserBook.php, src/Entity/Enum/UserBookStatus.php, src/Repository/UserBookRepository.php, templates/livre/show.html.twig, and assets/controllers/toast-container_controller.js to map current state against plan.md

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Schema migration, service layer, and toast infrastructure — ALL user story phases blocked until complete.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [X] T002 Modify src/Entity/UserBook.php — remove `#[ORM\Column(type: Types::STRING, enumType: UserBookStatus::class)]` `$status` field; add `#[ORM\Column(options: ['default' => false])]` bool `$isOwned`, `$isToRead`, `$isToBuy` properties (keep `$isFavorite`); add `isAllInactive(): bool` method; add getters/setters for new fields per data-model.md
- [X] T003 Delete src/Entity/Enum/UserBookStatus.php — remove enum class file; fix any remaining `UserBookStatus` references in codebase (repository, fixtures, controllers)
- [X] T004 [P] Modify src/Repository/UserBookRepository.php — add `findByUserAndBook(User $user, Book $book): ?UserBook` method using `findOneBy(['user' => $user, 'book' => $book])`
- [X] T005 Create migrations/VersionXXXXXXXXXXXXXX.php — Doctrine migration per data-model.md SQL plan: `ADD COLUMN is_owned/is_to_read/is_to_buy BOOLEAN NOT NULL DEFAULT FALSE`; `UPDATE` from `status` values (DANS_MA_COLLECTION→is_owned, A_ACHETER→is_to_buy, A_LIRE→is_to_read); `DELETE` rows where status IN ('lu','pas-dans-ma-collection'); `DROP COLUMN status`
- [X] T006 Create src/Service/UserBookService.php — implement `toggleOwned(User, Book): array`, `toggleToRead(User, Book): array`, `toggleToBuy(User, Book): array`, `toggleFavorite(User, Book): array` per contracts/UserBookService.md; inject `UserBookRepository` + `EntityManagerInterface`; enforce auto-coherence (isOwned↔isToBuy mutual exclusion); cascade-delete UserBook when `isAllInactive()`; return `['newValue' => bool, 'affected' => string[]]`
- [X] T007 [P] Modify assets/controllers/toast-container_controller.js — add `connect()` listener: `document.addEventListener('toast', handler)` where handler injects Bootstrap toast HTML per research.md Decision 3 into existing `.toast-rail` element: `<div data-controller="toast" data-toast-auto-dismiss-ms-value="5000" class="toast toast--{type}" role="status">...</div>`

**Checkpoint**: Entity migrated, service implemented, toast wired. User story phases can now begin.

---

## Phase 3: User Story 5 — Restitution contextuelle au chargement (Priority: P1) 🎯 MVP Foundation

**Goal**: Live Component created and displays accurate user-book state on page load — no toggle actions yet.

**Independent Test**: Load `/livres/{slug}` as authenticated user whose UserBook has `is_owned=true, is_favorite=true` → "Ma Collection" and "Favori" buttons have `is-active` class, others do not. Load as unauthenticated → no buttons rendered. Load with no UserBook record → all buttons inactive.

- [X] T008 [US5] Create src/Twig/Components/Book/LibraryActionsComponent.php — `#[AsLiveComponent]`, `#[LiveProp] readonly Book $book`; inject `UserBookRepository`; computed getters `isOwned(): bool`, `isToRead(): bool`, `isToBuy(): bool`, `isFavorite(): bool` via `findByUserAndBook()`; extend `AbstractController` per research.md Decision 2
- [X] T009 [P] [US5] Create templates/components/Book/LibraryActionsComponent.html.twig — 4 `action-toggle` buttons per contracts/BookLibraryActionsComponent.md template contract; `role="group"` `aria-label="Listes personnelles"`; `is-active` class bound to getters; `data-action="live#action"` `data-live-action-param` per button; SVG icons (can use existing project icons)
- [X] T010 [US5] Modify templates/livre/show.html.twig — replace existing static `actions-grid` div with `<twig:Book:LibraryActionsComponent :book="book" />` inside existing `{% if app.user %}...{% endif %}` guard
- [X] T011 [US5] Create tests/Functional/BookLibraryActionsTest.php — test US5 acceptance scenarios: authenticated user with known DB state sees matching `is-active` buttons; user with no UserBook sees all buttons inactive; unauthenticated user sees no button group

**Checkpoint**: Component renders accurate state at page load. US5 acceptance scenarios pass.

---

## Phase 4: User Story 1 — "Dans ma collection" toggle (Priority: P1) 🎯 MVP

**Goal**: Authenticated user toggles `isOwned`; auto-coherence clears `isToBuy`; toast shown on each action.

**Independent Test**: On `/livres/{slug}` as authenticated user with no UserBook — click "Ma Collection" → button gains `is-active`, toast "Ajouté à votre collection". Click again → button loses `is-active`, toast "Retiré de votre collection". Pre-set `isToBuy=true` → click "Ma Collection" → both `isOwned` active and `isToBuy` inactive in same render.

- [X] T012 [US1] Create tests/Unit/Service/UserBookServiceTest.php — test `toggleOwned`: no record → creates UserBook with `isOwned=true`; `isOwned=false` → sets `true` and `isToBuy=false` (auto-coherence); `isOwned=true` → sets `false`; all false after toggle → deletes UserBook record; returns correct `['newValue', 'affected']` array
- [X] T013 [US1] Add `toggleOwned()` `#[LiveAction]` `#[IsGranted('ROLE_USER')]` method to src/Twig/Components/Book/LibraryActionsComponent.php — call `$this->userBookService->toggleOwned($this->getUser(), $this->book)`; dispatch toast via `$this->dispatchBrowserEvent('toast', ['message' => ..., 'type' => 'success'])` using message matrix from contracts/BookLibraryActionsComponent.md; wrap in try/catch → error toast on exception
- [X] T014 [US1] Add US1 functional tests to tests/Functional/BookLibraryActionsTest.php — test all 4 acceptance scenarios: unauthenticated call → 401/redirect; toggle on from no record; toggle off; auto-coherence (`isToBuy` cleared when `isOwned` activated)

**Checkpoint**: "Ma Collection" toggle fully functional end-to-end. MVP deliverable.

---

## Phase 5: User Story 2 — "À lire" toggle (Priority: P2)

**Goal**: Authenticated user toggles `isToRead` independently of all other statuses.

**Independent Test**: Book already `isOwned=true` → click "À lire" → both "Ma Collection" and "À lire" `is-active` simultaneously. Click "À lire" again → only "Ma Collection" remains active. Verify no changes to `isOwned` or `isToBuy`.

- [X] T015 [P] [US2] Add `toggleToRead` unit tests to tests/Unit/Service/UserBookServiceTest.php — no record → creates UserBook with `isToRead=true`; toggle twice → back to `false`; no auto-coherence side-effects; returns `['newValue' => bool, 'affected' => []]`
- [X] T016 [P] [US2] Add `toggleToRead()` `#[LiveAction]` `#[IsGranted('ROLE_USER')]` to src/Twig/Components/Book/LibraryActionsComponent.php — delegate to `$this->userBookService->toggleToRead()`; dispatch toast "Ajouté à la liste À lire" / "Retiré de la liste À lire"
- [X] T017 [US2] Add US2 functional tests to tests/Functional/BookLibraryActionsTest.php — test all 3 acceptance scenarios; assert `isOwned` and `isToBuy` unaffected by `toggleToRead`

**Checkpoint**: "À lire" toggle functional. Coexistence with "Ma Collection" verified.

---

## Phase 6: User Story 3 — "À acheter" toggle (Priority: P2)

**Goal**: Authenticated user toggles `isToBuy`; auto-coherence clears `isOwned` (symmetric to US1).

**Independent Test**: Click "À acheter" → active. Then click "Ma Collection" → "À acheter" deactivates simultaneously. "À lire" unaffected throughout.

- [X] T018 [P] [US3] Add `toggleToBuy` unit tests to tests/Unit/Service/UserBookServiceTest.php — no record → creates with `isToBuy=true`; `isToBuy=false` → set `true` and `isOwned=false`; `isToBuy=true` → set `false`; all false → delete record; correct return shape
- [X] T019 [P] [US3] Add `toggleToBuy()` `#[LiveAction]` `#[IsGranted('ROLE_USER')]` to src/Twig/Components/Book/LibraryActionsComponent.php — delegate to `$this->userBookService->toggleToBuy()`; dispatch toast "Ajouté à la liste À acheter" / "Retiré de la liste À acheter"
- [X] T020 [US3] Add US3 functional tests to tests/Functional/BookLibraryActionsTest.php — test all 3 acceptance scenarios including symmetric auto-coherence (activating `isToBuy` clears `isOwned`)

**Checkpoint**: "À acheter" toggle functional. Auto-coherence bidirectional and fully tested.

---

## Phase 7: User Story 4 — "Favori" toggle (Priority: P3)

**Goal**: Authenticated user toggles `isFavorite` independently of all other statuses.

**Independent Test**: Any combination of other statuses active → click "Favori" → only "Favori" state changes. Remove book from collection (isOwned=false) → "Favori" remains active.

- [X] T021 [P] [US4] Add `toggleFavorite` unit tests to tests/Unit/Service/UserBookServiceTest.php — no record → creates with `isFavorite=true`; toggle twice → back to `false`; no auto-coherence; all false after toggle → deletes UserBook record
- [X] T022 [P] [US4] Add `toggleFavorite()` `#[LiveAction]` `#[IsGranted('ROLE_USER')]` to src/Twig/Components/Book/LibraryActionsComponent.php — delegate to `$this->userBookService->toggleFavorite()`; dispatch toast "Ajouté à vos favoris" / "Retiré de vos favoris"
- [X] T023 [US4] Add US4 functional tests to tests/Functional/BookLibraryActionsTest.php — test all 3 acceptance scenarios; assert all other status fields unaffected by `toggleFavorite`

**Checkpoint**: All 4 toggles functional, all user stories independently testable.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Accessibility, edge cases, and full suite validation across all user stories.

- [X] T024 Verify keyboard accessibility in templates/components/Book/LibraryActionsComponent.html.twig — confirm `role="group"` + `aria-label` present; buttons are native `<button>` elements; no CSS suppressing `outline`; add `aria-pressed="{{ isOwned ? 'true' : 'false' }}"` (and equivalent) per button for screen reader toggle state (FR-011)
- [X] T025 Add edge case tests to tests/Functional/BookLibraryActionsTest.php — unauthenticated direct action call → 401; all flags false after toggle → assert UserBook row deleted from DB; missing CSRF token → request rejected; idempotence check (toggle on twice via concurrent simulation → consistent state)
- [ ] T026 Run full PHPUnit suite (`php bin/phpunit`) — fix any regressions from entity migration; confirm no remaining `UserBookStatus` references; verify all 26 tasks' code changes pass; confirm SC-004 coverage. Also verify migration data integrity manually: after `doctrine:migrations:migrate`, run `SELECT COUNT(*) FROM user_book WHERE is_owned = true` and cross-check against pre-migration count of `status = 'dans-ma-collection'` rows (same for `a-acheter`→`is_to_buy` and `a-lire`→`is_to_read`); confirm `lu`/`pas-dans-ma-collection` rows were deleted.
- [ ] T027 Verify SC-001 on staging — using browser DevTools Network tab, trigger each of the 4 toggle actions once; confirm all responses return in <300 ms; document median response times in PR description

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 — **BLOCKS all user stories**
- **US5 (Phase 3)**: Depends on Phase 2 — component scaffolding requires migrated entity + service
- **US1 (Phase 4)**: Depends on Phase 3 — first toggle action added to existing component
- **US2 (Phase 5)**: Depends on Phase 2 — independent of US1/US3/US4
- **US3 (Phase 6)**: Depends on Phase 2 — independent of US2/US4
- **US4 (Phase 7)**: Depends on Phase 2 — independent of US1/US2/US3
- **Polish (Phase 8)**: Depends on all user story phases complete

### User Story Dependencies

- **US5 (P1)**: Requires Phase 2; no user story deps
- **US1 (P1)**: Requires Phase 3 complete (component must exist)
- **US2 (P2)**: Requires Phase 2 complete — can parallel with US5/US1
- **US3 (P2)**: Requires Phase 2 complete — can parallel with US2/US5
- **US4 (P3)**: Requires Phase 2 complete — fully independent

### Parallel Opportunities

- **Phase 2**: T004 [P] (repository) and T007 [P] (toast controller) run alongside T002/T003/T005/T006
- **Phase 3**: T008 and T009 [P] (PHP class + Twig template) run simultaneously
- **Phase 5**: T015 [P] + T016 [P] run simultaneously
- **Phase 6**: T018 [P] + T019 [P] run simultaneously
- **Phase 7**: T021 [P] + T022 [P] run simultaneously
- **After Phase 2**: Phases 5, 6, 7 can begin in parallel with Phase 3 if two developers

---

## Parallel Example: User Story 3

```bash
# Launch simultaneously (different files, no mutual dependencies):
Task T018: "Add toggleToBuy unit tests to tests/Unit/Service/UserBookServiceTest.php"
Task T019: "Add toggleToBuy() #[LiveAction] to src/Twig/Components/Book/LibraryActionsComponent.php"

# After both complete:
Task T020: "Add US3 functional tests to tests/Functional/BookLibraryActionsTest.php"
```

---

## Implementation Strategy

### MVP First (US5 + US1 only)

1. Complete Phase 1: Audit existing code
2. Complete Phase 2: Foundational — **CRITICAL BLOCKER**
3. Complete Phase 3: US5 — Component renders, state displayed correctly on load
4. Complete Phase 4: US1 — "Ma Collection" toggle working end-to-end with toast
5. **STOP and VALIDATE**: Test US5 + US1 on staging; check SC-001 (<300ms), SC-002, SC-003
6. Deploy: users can manage their collection status

### Incremental Delivery

1. Phase 2 complete → Deploy: schema migrated, no visible user change
2. Phase 3 (US5) → Deploy: buttons show correct state at load (read-only improvement)
3. Phase 4 (US1) → Deploy: first toggle live (MVP — main use case)
4. Phase 5+6 (US2+US3) → Deploy: À lire + À acheter toggles
5. Phase 7 (US4) → Deploy: Favoris toggle
6. Phase 8 → Final deploy: accessibility + edge case hardening

### Parallel Team Strategy (2 developers)

After Phase 2 complete:
- **Dev A**: Phase 3 (US5) → Phase 4 (US1)
- **Dev B**: Phase 5 (US2) → Phase 6 (US3) → Phase 7 (US4)

---

## Notes

- [P] = different files, no incomplete task dependencies — safe to parallelize
- US2, US3, US4 can begin after Phase 2 even without US5/US1 complete (different files)
- UserBookService unit tests use test doubles for EM/repository (no DB required)
- Functional tests require test DB with Doctrine fixtures (User + Book + UserBook)
- Run `php bin/console doctrine:migrations:migrate` in dev after completing Phase 2 before any functional testing
- Idempotence guaranteed at service level (reads DB state before each toggle)
