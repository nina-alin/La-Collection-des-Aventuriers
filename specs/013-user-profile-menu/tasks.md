---

description: "Task list for Menu Profil Utilisateur Responsive"
---

# Tasks: Menu Profil Utilisateur Responsive

**Input**: Design documents from `/specs/013-user-profile-menu/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/ ✅, quickstart.md ✅

**Tests**: Required — Constitution Principle V mandates PHPUnit tests for role badge visibility, moderation section DOM gating, and ARIA attributes.

**Organization**: Tasks grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1–US6)
- Exact file paths included in every description

---

## Phase 1: Setup (Data Layer)

**Purpose**: Repository additions and DTO that unblock the service — no UI yet

- [X] T001 [P] Add `countPending(): int` DQL COUNT query to `src/Repository/WorkEntryRepository.php`
- [X] T002 [P] Add `countPending(): int` DQL COUNT query to `src/Repository/CorrectionProposalRepository.php`
- [X] T003 Create `ProfileMenuDto` read-only value object with all 7 constructor properties in `src/Dto/ProfileMenuDto.php`
- [X] T004 Create `ProfileMenuService::getMenuData(User $user): ProfileMenuDto` aggregating rank (ContributorLevelService), validatedCount (SuggestionRepository), pendingModerationCount (sum of both repos) in `src/Service/ProfileMenuService.php`

**Checkpoint**: Data layer complete — T001–T004 must pass `php bin/console debug:container ProfileMenuService` before Phase 2

---

## Phase 2: Foundational (Component Skeleton)

**Purpose**: Component class and test file skeleton that all user story phases extend

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T005 Create `ProfileMenu` Twig Component class with `#[AsTwigComponent]`, constructor-injected `ProfileMenuService` and `Security`, and `mount()` populating `$this->menuData` in `src/Twig/Components/Layout/ProfileMenu.php`
- [X] T006 Create `tests/Twig/Components/ProfileMenuTest.php` skeleton with `InteractsWithTwigComponents` trait and a `makeComponent(array $roles)` helper that returns a rendered `ProfileMenu` component with a mock `ProfileMenuDto` — Note: focus trap (FR-019) is Stimulus JS behavior; untestable via PHPUnit — no PHPUnit test required for it

**Checkpoint**: Foundation ready — `php bin/phpunit tests/Twig/Components/ProfileMenuTest.php` runs (0 tests, 0 failures)

---

## Phase 3: User Story 1 — Accès au menu profil (Priority: P1) 🎯 MVP

**Goal**: Trigger button opens/closes a dropdown (desktop ≥720px) or bottom-sheet (mobile <720px); swipe and keyboard close it

**Independent Test**: Click avatar on desktop → dropdown appears/disappears; narrow viewport to <720px → bottom-sheet slides up; swipe down → closes; press Escape → closes and focus returns to trigger

### Tests for User Story 1

- [X] T007 [P] [US1] Add `testTriggerHasAriaAttributes` asserting `aria-haspopup="menu"` and `aria-controls="user-menu"` on trigger `<button>` in `tests/Twig/Components/ProfileMenuTest.php`

### Implementation for User Story 1

- [X] T008 [US1] Create `assets/controllers/profile_menu_controller.js` with full Stimulus controller: `open`/`close`/`toggle` methods, `openValueChanged` callback toggling `is-open` class on card and backdrop, `onKeydown` for Escape/Tab/Arrows focus trap, and swipe detection (`touchstart`/`touchmove`/`touchend`, threshold 80px) per `contracts/profile-menu-stimulus.md`
- [X] T009 [US1] Create `templates/components/Layout/ProfileMenu.html.twig` with `.menu-anchor` root element (`data-controller="profile-menu"`), trigger `<button id="user-trigger">` with all ARIA attributes (`aria-haspopup="menu"`, `aria-expanded="false"`, `aria-controls="user-menu"`), `.menu-backdrop` overlay, and `.menu-card.user-card` panel with `id="user-menu"` `role="menu"` `aria-label="Compte utilisateur"` per `contracts/profile-menu-component.md`

**Checkpoint**: Click trigger → `is-open` class added to card; click again or press Escape → removed; ARIA test passes

---

## Phase 4: User Story 6 — Déconnexion (Priority: P1)

**Goal**: Logout button styled in red with "SORTIR" visible; invokes existing logout handler

**Independent Test**: Click "Se déconnecter" → session destroyed, redirect to home/login; button renders with `.logout` red class and "SORTIR" text

### Implementation for User Story 6

- [X] T010 [US6] Add logout section (`.logout-section`) containing existing logout form/button reuse with `.menu-link.logout` red styling and "SORTIR" right-aligned meta to `templates/components/Layout/ProfileMenu.html.twig`

**Checkpoint**: Logout button visible in red with "SORTIR"; existing handler triggered on click; no new logout logic

---

## Phase 5: User Story 2 — En-tête utilisateur et badge de rôle (Priority: P2)

**Goal**: Header shows "BONJOUR", pseudo, displayName, avatar (initials fallback), status dot, and role badge only for mod/admin

**Independent Test**: Login as standard user → no badge element in DOM; as moderator → "● MODÉRATEUR" badge; as admin → "● ADMINISTRATEUR" badge

### Tests for User Story 2

- [X] T011 [P] [US2] Add `testStandardUserHasNoBadgeInDom` asserting `.badge-role-mod` and `.badge-role-admin` absent from rendered HTML in `tests/Twig/Components/ProfileMenuTest.php`
- [X] T012 [P] [US2] Add `testModeratorUserShowsModBadge` asserting "MODÉRATEUR" text and badge element present in `tests/Twig/Components/ProfileMenuTest.php`
- [X] T013 [P] [US2] Add `testAdminUserShowsAdminBadge` asserting "ADMINISTRATEUR" text and badge element present in `tests/Twig/Components/ProfileMenuTest.php`

### Implementation for User Story 2

- [X] T014 [US2] Add `.menu-head-user` header section to `templates/components/Layout/ProfileMenu.html.twig`: avatar `<img>` with `user_initials` filter fallback, status dot, "BONJOUR" greeting, pseudo/displayName, and role badge conditional on `menuData.highestRole` (`ROLE_ADMIN` → "● ADMINISTRATEUR", `ROLE_MODERATOR` → "● MODÉRATEUR", else no badge element in DOM)

**Checkpoint**: Role badge tests pass; standard user HTML contains no badge markup

---

## Phase 6: User Story 3 — Navigation personnelle (Priority: P2)

**Goal**: "Mon Profil" link with rank meta aligned right; "Mes Suggestions" link with validatedCount meta; both redirect correctly

**Independent Test**: Open menu → "Mon Profil" shows rank (e.g. "AVENTURIER") or "—"; "Mes Suggestions" shows count (e.g. "17 VALIDÉES")

### Tests for User Story 3

- [X] T015 [US3] Add `testRankFallbackWhenNull` asserting "—" appears in `.lnk-meta` when `rankName` is `null` in `tests/Twig/Components/ProfileMenuTest.php`

### Implementation for User Story 3

- [X] T016 [US3] Add personal navigation `.menu-section` to `templates/components/Layout/ProfileMenu.html.twig`: "Mon Profil" `.menu-link` with `href="#"` placeholder (research Decision 1) and `<span class="lnk-meta">{{ menuData.rankName ?? '—' }}</span>`; "Mes Suggestions" `.menu-link` with contributor dashboard route and `<span class="lnk-meta">{{ menuData.validatedCount }} VALIDÉES</span>`; each link with `role="menuitem"`

**Checkpoint**: Navigation links visible with correct metadata; rank fallback test passes

---

## Phase 7: User Story 4 — Outils de modération (Priority: P3)

**Goal**: Moderation section with pending count visible only to ROLE_MODERATOR/ROLE_ADMIN; absent from DOM for standard users

**Independent Test**: Standard user → inspect DOM → "OUTILS DE MODÉRATION" absent; moderator/admin → section present with count

### Tests for User Story 4

- [X] T017 [P] [US4] Add `testModerationSectionAbsentForStandardUser` asserting "OUTILS DE MODÉRATION" not in rendered HTML for `ROLE_USER` in `tests/Twig/Components/ProfileMenuTest.php`
- [X] T018 [P] [US4] Add `testModerationSectionPresentForModerator` asserting "OUTILS DE MODÉRATION" and pending count present for `ROLE_MODERATOR` in `tests/Twig/Components/ProfileMenuTest.php`

### Implementation for User Story 4

- [X] T019 [US4] Add moderation `.menu-section` wrapped in `{% if is_granted('ROLE_MODERATOR') %}` to `templates/components/Layout/ProfileMenu.html.twig`: section label "OUTILS DE MODÉRATION" and "Salle de Modération" `.menu-link.role-action` with `<span class="badge-new">{{ menuData.pendingModerationCount }} À RELIRE</span>` and `role="menuitem"`

**Checkpoint**: Moderation tests pass; section completely absent from standard user HTML

---

## Phase 8: User Story 5 — Bascule de thème (Priority: P3)

**Goal**: Theme toggle reflects current localStorage theme; toggling switches interface and persists across page loads

**Independent Test**: Toggle to parchment → `document.documentElement.dataset.theme` = "parchment"; reload → toggle still checked; toggle off → "dark"

### Implementation for User Story 5

- [X] T020 [US5] Add preferences `.menu-toggle-row` section to `templates/components/Layout/ProfileMenu.html.twig`: "Thème Parchemin" label with `<input type="checkbox" id="theme-switch-menu">` toggle
- [X] T021 [US5] Add `toggleTheme(e)` handler and `connect()` localStorage read to `assets/controllers/profile_menu_controller.js`: read `localStorage.getItem('theme')` on connect to set checkbox initial state; on toggle write `localStorage.setItem('theme', ...)` and set `document.documentElement.dataset.theme` per research Decision 7

**Checkpoint**: Theme persists across page reload; toggle state matches active theme on open

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Wire component into navbar, validate full suite, verify against design reference

- [X] T022 Replace the inline `.lp-user-menu` div in `templates/components/Layout/Navbar.html.twig` with `{% if is_granted('IS_AUTHENTICATED_REMEMBERED') %}<twig:Layout:ProfileMenu />{% endif %}`
- [X] T023 [P] Run PHPUnit test suite: `php bin/phpunit tests/Twig/Components/ProfileMenuTest.php` — all 7 required tests must pass
- [X] T024 [P] Visual validation: cross-check `templates/components/Layout/ProfileMenu.html.twig` output structure against `design/dashboard.html` lines 761–832; verify CSS classes match reference markup; assert "Paramètres" and "Aide & raccourcis" links are absent from rendered HTML (FR-015); verify open/close completes within 2s (SC-001)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 — T004 must be wired before T005
- **User Stories (Phase 3+)**: All depend on Foundational phase (T005, T006)
  - US1 (Phase 3) and US6 (Phase 4) are P1 — implement first, sequentially (share same template file)
  - US2 (Phase 5) and US3 (Phase 6) are P2 — follow P1 stories
  - US4 (Phase 7) and US5 (Phase 8) are P3 — follow P2 stories
- **Polish (Phase 9)**: Depends on all user story phases complete

### User Story Dependencies

- **US1 (P1)**: Can start after Phase 2 — no dependency on other stories
- **US6 (P1)**: Extends same template as US1 — implement after US1 template exists (T009)
- **US2 (P2)**: Extends same template — implement after US6 (T010 done)
- **US3 (P2)**: Extends same template — implement after US2 (T014 done)
- **US4 (P3)**: Extends same template — implement after US3 (T016 done)
- **US5 (P3)**: Extends same template + Stimulus controller — implement after US4 (T019 done)

### Within Each User Story

- Tests MUST be written before implementation (per Constitution Principle V)
- Template section additions are sequential (same file)
- [P] tagged tasks within a phase touch different files — run in parallel

### Parallel Opportunities

- T001 and T002 (both repo additions) — parallel
- T011, T012, T013 (US2 tests, all different test methods) — parallel
- T017, T018 (US4 tests) — parallel
- T023, T024 (Polish validation tasks) — parallel

---

## Parallel Example: User Story 2

```bash
# Launch all US2 tests before implementation:
Task T011: testStandardUserHasNoBadgeInDom
Task T012: testModeratorUserShowsModBadge
Task T013: testAdminUserShowsAdminBadge

# Then implement:
Task T014: Add .menu-head-user header section to ProfileMenu.html.twig
```

---

## Implementation Strategy

### MVP First (US1 + US6 Only — P1 Stories)

1. Complete Phase 1: Setup (T001–T004)
2. Complete Phase 2: Foundational (T005–T006)
3. Complete Phase 3: US1 — menu open/close (T007–T009)
4. Complete Phase 4: US6 — logout (T010)
5. **STOP and VALIDATE**: Menu opens/closes, logout works → functional menu skeleton
6. Wire into Navbar (T022) for smoke test

### Incremental Delivery

1. Setup + Foundational → data + component skeleton ready
2. US1 + US6 (P1) → functional menu with logout → **Demo 1** ✅
3. US2 + US3 (P2) → full header and navigation with dynamic data → **Demo 2** ✅
4. US4 + US5 (P3) → moderation section + theme toggle → **Demo 3** ✅
5. Polish → wired into navbar, all tests green → **Ship** ✅

---

## Notes

- `[P]` tasks touch different files — safe to parallelize
- Template file (`ProfileMenu.html.twig`) is built section-by-section — sequential edits only
- Stimulus controller (`profile_menu_controller.js`) extended in US5 — edit the existing file, don't replace
- Tests required by Constitution Principle V: T007, T011, T012, T013, T015, T017, T018
- "Mon Profil" link uses `href="#"` placeholder per research Decision 1 (no profile page route exists yet)
- Avatar fallback uses existing `user_initials` Twig filter per research Decision 4
- Theme key in localStorage is `'theme'`; parchment mode = `'parchment'`; dark = absent or `'dark'`
