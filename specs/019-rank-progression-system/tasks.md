---

description: "Task list for Système de Rangs et Progression implementation"
---

# Tasks: Système de Rangs et Progression

**Input**: Design documents from `/specs/019-rank-progression-system/`

**Prerequisites**: plan.md ✓, spec.md ✓, research.md ✓, data-model.md ✓, quickstart.md ✓

**Tests**: Included — Constitution Principle V mandates PHPUnit tests for all modified services, listeners, and new controller actions.

**Organization**: Tasks grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story label (US1–US4)
- Exact file paths included in every task

---

## Phase 1: Setup (Baseline Verification)

**Purpose**: Confirm existing backend is in expected state before any modification.

- [x] T001 Run baseline tests — `php bin/phpunit tests/Unit/Service/ContributorLevelServiceTest.php tests/Notification/EventListener/ContributionValidatedListenerTest.php` — both must pass green before any changes
- [x] T002 Verify ContributorLevel DB rows — `php bin/console doctrine:query:sql "SELECT rank_number, name, threshold FROM contributor_level ORDER BY rank_number"` — must return 6 rows matching data-model.md (Novice 0, Apprenti 5, Chroniqueur confirmé 15, Archiviste 30, Érudit 60, Grand Sage 100)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core backend changes that ALL user stories depend on. Must complete before any user story work.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [x] T003 Refactor `src/Event/ContributionValidatedEvent.php` — replace constructor param `public WorkEntry $workEntry` with `public string $title`; remove WorkEntry import; update all usages in codebase (`ContributionValidatedListener`, `ModerationService`, any test that constructs this event); in `ContributionValidatedListener`: line 36 `$event->workEntry->getTitle()` → `$event->title`, line 38 `sourceId` → `null`
- [x] T004 [P] Add `countPublishedByUser(User $user): int` (WHERE status='PUBLISHED' AND author=user) and `countBatchPublished(array $users): array` (SELECT author_id, COUNT(id) GROUP BY author_id WHERE author_id IN (...) AND status='PUBLISHED') to `src/Repository/CorrectionProposalRepository.php`
- [x] T005 [P] Add `countBatchValidated(array $users): array` (SELECT user_id, COUNT(id) GROUP BY user_id WHERE user_id IN (...) AND status=VALIDATED) to `src/Repository/SuggestionRepository.php`
- [x] T006 [P] Add `findAllSortedByThreshold(): array` (SELECT * ORDER BY threshold ASC) to `src/Repository/ContributorLevelRepository.php`
- [x] T007 Update `src/Service/ContributorLevelService.php` — inject `CorrectionProposalRepository $correctionRepo` in constructor; replace direct `SuggestionRepository::countByStatus()` calls with private `countValidatedContributions(User $user): int` (= Suggestion VALIDATED count + CorrectionProposal PUBLISHED count); add public `computeRankBatch(array $users): array` returning `[userId => ?ContributorLevel]` (key = UUID string via `$user->getId()->toRfc4122()`) using `countBatchValidated` + `countBatchPublished` + `findAllSortedByThreshold`; extend `getMetrics()` to also return `nextLevel` (`?ContributorLevel` — the next rank tier object, null if at max rank) (depends on T004, T005, T006)
- [x] T008 Update `src/Service/ModerationService.php` — in `approve()` add `elseif` branch for `CorrectionProposal`: extract `$title = $entity->getWorkEntry()->getTitle()`, guard `if ($entity->getAuthor() !== null)`, dispatch `new ContributionValidatedEvent($title, $entity->getAuthor())`; update existing WorkEntry branch to pass `$entity->getTitle()` string instead of `$entity` object (depends on T003)
- [x] T008b [P] Update `src/Service/ModerationService.php` — in `moderateSuggestion()` add dispatch of `ContributionValidatedEvent` when `$newStatus === SuggestionStatus::VALIDATED` and `$suggestion->getUser() !== null`: `dispatch(new ContributionValidatedEvent('une suggestion', $suggestion->getUser()))` — rank detection in listener will run from this event, enabling rank-up notification for Suggestion validations (depends on T003)

**Checkpoint**: Foundation ready — repository methods exist, service uses combined counts, event carries string title, moderateSuggestion dispatches rank event. User story phases can begin.

---

## Phase 3: User Story 1 — Affichage du rang dans le profil et les menus (Priority: P1) 🎯 MVP

**Goal**: Rank badge visible in all identity zones — moderation queue rows, review/comment items, admin users list, new public profile page. Profile menu text title already wired (no change needed).

**Independent Test**: Log in as ROLE_USER with validated contributions → confirm colored rank badge appears in moderation dashboard author column, review item author area, admin users list, and `/profil/{pseudo}`. Log in as ROLE_MODERATOR → confirm no rank badge shown anywhere.

### Tests for User Story 1

- [x] T009 [P] [US1] Update `tests/Unit/Service/ContributorLevelServiceTest.php` — add `testComputeRankUsesCombinedCount()` (mock both repos, assert sum of Suggestion + CorrectionProposal counts used) and `testComputeRankBatchReturnsCorrectMap()` (mock batch repos, assert returned `[userId => ContributorLevel]` map uses UUID string keys) and `testGetMetricsReturnsNextLevel()` (assert `nextLevel` key present, null when at max rank)
- [x] T009b [P] [US1] Create `tests/Integration/Controller/ProfileControllerTest.php` — add `testPublicProfileShowsRankBadgeForRoleUser()` (ROLE_USER with validated contributions → rank badge visible), `testPublicProfileHidesRankForModerator()` (ROLE_MODERATOR → no rank badge), `testPublicProfileReturns404ForUnknownPseudo()` (unknown pseudo → 404)

### Implementation for User Story 1

- [x] T010 [P] [US1] Create `assets/styles/components/_rank-badge.scss` — define `.badge-rank` base (display:inline-flex, padding, border-radius, font-size:.7rem, font-weight:600, letter-spacing:.05em, text-transform:uppercase) and `.badge-rank-1` through `.badge-rank-6` each with background/color/border using tokens: rank1=parchemin(-100/-700/-300), rank2=mousse(-100/-700/-300), rank3=encre(-100/-700/-300), rank4=ambre(-100/-700/-300), rank5=or(-100/-700/-300), rank6=cuir(-100/-700/-300); import file in main SCSS entry point
- [x] T011 [P] [US1] Create `templates/components/_rank_badge.html.twig` — accepts params `level` (ContributorLevel|null) and `compact` (bool, default false); renders `<span class="badge badge-rank badge-rank-{{ level.rankNumber }}" aria-label="Rang {{ level.name }}">{{ level.name }}</span>` only when `level is not null` (depends on T010)
- [x] T012 [US1] Update moderation controller (action rendering `moderation/dashboard.html.twig`) — inject `ContributorLevelService`, collect all author User objects from pending rows, call `computeRankBatch($authors)`, pass `ranksByUserId` to template (depends on T007)
- [x] T013 [US1] Update `templates/moderation/dashboard.html.twig` — for each suggestion/correction row, set `{% set userRank = ranksByUserId[author.id.toRfc4122()] ?? null %}` then `{% include 'components/_rank_badge.html.twig' with {level: userRank} %}` next to author pseudo (depends on T011, T012)
- [x] T014 [US1] Update book/review controller (action rendering `livre/_review_item.html.twig` context) — inject `ContributorLevelService`, collect review author Users, call `computeRankBatch($authors)`, pass `ranksByUserId` to template (depends on T007)
- [x] T015 [US1] Update `templates/livre/_review_item.html.twig` — set `{% set userRank = ranksByUserId[review.author.id.toRfc4122()] ?? null %}` and include rank badge in review-author-meta div (depends on T011, T014)
- [x] T016 [US1] Update admin controller (action rendering `admin/users.html.twig`) — inject `ContributorLevelService`, call `computeRankBatch($allUsers)`, pass `ranksByUserId` to template (depends on T007)
- [x] T017 [US1] Update `templates/admin/users.html.twig` — in pseudo column, show rank badge only for rows where user has ROLE_USER (not ROLE_MODERATOR/ROLE_ADMIN): `{% if not (user.hasRole('ROLE_MODERATOR') or user.hasRole('ROLE_ADMIN')) %}{% include '_rank_badge.html.twig' with {level: ranksByUserId[user.id.toRfc4122()] ?? null} %}{% endif %}` (depends on T011, T016)
- [x] T018 [US1] Add `publicProfile(string $pseudo)` action to `src/Controller/ProfileController.php` — route `GET /profil/{pseudo}`, name `profile_public`, no `#[IsGranted]`; fetch User by pseudo (404 if not found); compute `$rankLevel = null`; if user has only ROLE_USER (not ROLE_MODERATOR/ROLE_ADMIN) set `$rankLevel = $contributorLevelService->computeRank($user)`; compute `$validatedCount`; pass `profileUser`, `rankLevel`, `validatedCount`, `isRankVisible` to template (depends on T007)
- [x] T019 [US1] Create `templates/profile/show.html.twig` — extends base layout; shows `profileUser.displayName` as h1; if `isRankVisible` include rank badge prominently; shows validated contribution count; shows join date; no collection/wishlist data (depends on T011, T018)

**Checkpoint**: User Story 1 complete. Rank badge visible in all identity zones for ROLE_USER. ROLE_MODERATOR/ROLE_ADMIN see no rank badge.

---

## Phase 4: User Story 2 — Bandeau dynamique sur le tableau de bord des suggestions (Priority: P1)

**Goal**: Suggestion dashboard banner dynamically shows validated count delta to next rank (or max-rank congratulation message).

**Independent Test**: Log in as user with 7 validated contributions (threshold for Chroniqueur confirmé = 15, delta = 8) → banner shows "Tu es à 8 fiches du rang Chroniqueur confirmé." Log in as user at rank 6 (Grand Sage) → banner shows congratulation message.

### Implementation for User Story 2

- [x] T020 [US2] Update suggestion controller action (renders `templates/suggestion/index.html.twig`) — inject `ContributorLevelService`, call `$metrics = $contributorLevelService->getMetrics($user)`; pass `$metrics['currentLevel']` as `currentRank`, `$metrics['nextLevel']` as `nextRank`, `$metrics['deltaToNext']` as `delta` to template; no delta computation in controller (depends on T007); also update `tests/Integration/Controller/SuggestionControllerTest.php` to assert banner shows correct delta message for a user with partial progress and congratulation message for max-rank user
- [x] T021 [US2] Update `templates/suggestion/index.html.twig` — in dashboard banner block: if `nextRank is null` render `"Tu as atteint le rang suprême de {{ currentRank.name }}. Merci pour tes contributions inestimables !"`, else render `"Tu es à {{ delta }} fiche{{ delta > 1 ? 's' : '' }} du rang {{ nextRank.name }}."` (depends on T020)

**Checkpoint**: User Story 2 complete. Banner shows correct delta for all user progression states.

---

## Phase 5: User Story 3 — Notification de passage de rang (Priority: P2)

**Goal**: Rank-up notification always fires when a moderated approval crosses a rank threshold. No preference gate. Links to `/mes-suggestions`.

**Independent Test**: Validate the N-th suggestion of a user whose count reaches exactly a rank threshold → notification of type RANK_UP appears in notification center with rank name and link to /mes-suggestions. Validate a suggestion that does NOT cross a threshold → no RANK_UP notification generated.

### Tests for User Story 3

- [x] T022 [P] [US3] Update `tests/Notification/EventListener/ContributionValidatedListenerTest.php` — update all event constructions to `new ContributionValidatedEvent('title', $user)`; add `testRankDetectionRunsEvenWhenContributionPrefDisabled()` (verify `RankUpEvent` dispatched even when CONTRIBUTION_VALIDATED pref is disabled)
- [x] T023 [P] [US3] Update `tests/Notification/EventListener/RankUpListenerTest.php` — add `testAlwaysDispatchesRegardlessOfPreference()` (verify NotificationMessage dispatched even when rankUp pref is false) and `testAlwaysDispatchesWhenNoPreferenceExists()` (verify dispatch when no preference record exists)
- [x] T024 [US3] Create `tests/Unit/Service/ModerationServiceTest.php` — add `testApproveWorkEntryDispatchesContributionValidatedEvent()`, `testApproveCorrectionProposalDispatchesContributionValidatedEvent()`, `testApproveWithNullAuthorDoesNotDispatch()`, `testModerateSuggestionDispatchesContributionValidatedEventOnValidated()`, `testModerateSuggestionDoesNotDispatchOnRefused()` (depends on T008, T008b)

### Implementation for User Story 3

- [x] T025 [US3] Update `src/EventListener/ContributionValidatedListener.php` — move rank detection block (computeRank before/after + RankUpEvent dispatch) OUTSIDE the `if ($preferences->isEnabled(NotificationType::CONTRIBUTION_VALIDATED))` guard; rank detection runs unconditionally on every event; CONTRIBUTION_VALIDATED notification dispatch remains inside the pref guard (depends on T003)
- [x] T026 [US3] Update `src/EventListener/RankUpListener.php` — remove the `!$preference->isEnabled(NotificationType::RANK_UP)` guard entirely; inject `UrlGeneratorInterface $router`; set `targetUrl: $this->router->generate('suggestions_index')` (route `suggestions_index` = `/mes-suggestions`) in dispatched `NotificationMessage`

**Checkpoint**: User Story 3 complete. Rank-up notification fires unconditionally on threshold crossing, links to suggestion dashboard.

---

## Phase 6: User Story 4 — Configuration de la matrice des rangs (Priority: P2)

**Goal**: Rank grid configured via Doctrine fixture, updatable without code changes.

**Independent Test**: Query DB — 6 rows in `contributor_level` with correct thresholds. Update fixture value, reload, query again → new thresholds applied without code change.

### Implementation for User Story 4

- [x] T027 [US4] Verify `src/DataFixtures/ContributorLevelFixture.php` defines all 6 ranks exactly as per data-model.md: Novice(rankNumber=1, threshold=0), Apprenti(2, 5), Chroniqueur confirmé(3, 15), Archiviste(4, 30), Érudit(5, 60), Grand Sage(6, 100) — add or correct any missing/wrong entries

**Checkpoint**: User Story 4 complete. Rank grid in DB matches authoritative spec; updatable via fixture reload.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Validation, regression checks, and integration verification.

- [x] T028 [P] Verify SCSS color tokens exist for all 6 rank palettes — grep `assets/styles/tokens/_colors.scss` for `--parchemin`, `--mousse`, `--encre`, `--ambre`, `--or`, `--cuir` each with `-100`, `-300`, `-700` variants; confirm all 18 token values are defined before `_rank-badge.scss` compilation
- [x] T029 Run full PHPUnit suite for all modified/new files — `php bin/phpunit tests/Unit/Service/ContributorLevelServiceTest.php tests/Unit/Service/ModerationServiceTest.php tests/Notification/EventListener/ContributionValidatedListenerTest.php tests/Notification/EventListener/RankUpListenerTest.php` — all must pass
- [x] T030 Run quickstart.md end-to-end validation — verify 6 ContributorLevel rows in DB; test rank badge renders in moderation dashboard, review items, admin users list; test public profile at `/profil/{pseudo}`; test suggestion dashboard banner delta message; test rank-up notification generated on threshold crossing

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 — BLOCKS all user story phases
- **Phase 3 (US1)**: Depends on Phase 2 — T004/T005/T006/T007 required for batch queries
- **Phase 4 (US2)**: Depends on Phase 2 — T007 required for combined count + rank computation
- **Phase 5 (US3)**: Depends on Phase 2 — T003 (event refactor), T008 (ModerationService CorrectionProposal branch), and T008b (moderateSuggestion event) required
- **Phase 6 (US4)**: Independent — can run in parallel with Phases 3–5 (fixture only, no code deps)
- **Phase 7 (Polish)**: Depends on all prior phases complete

### User Story Dependencies

- **US1 (P1)**: Depends on Foundational. No dependency on US2/US3/US4.
- **US2 (P1)**: Depends on Foundational (T007). No dependency on US1/US3/US4.
- **US3 (P2)**: Depends on Foundational (T003 event refactor, T008 ModerationService). No dependency on US1/US2/US4.
- **US4 (P2)**: No code dependencies. Can start at any time.

### Parallel Opportunities Within Phases

- Phase 2: T004, T005, T006 can run in parallel (different repository files)
- Phase 3: T009, T010 can run in parallel (tests and SCSS/Twig independent); T012+T013, T014+T015, T016+T017 each pair can be done together after T011/T014/T016
- Phase 2: T008 and T008b can run in parallel (different methods in ModerationService)
- Phase 5: T022, T023 can run in parallel (different test files); T025 and T026 can run in parallel (different listeners)
- Phase 7: T028 and T029 can run in parallel

---

## Parallel Example: Phase 2 (Foundational)

```bash
# Three repository tasks in parallel:
Task T004: Add CorrectionProposalRepository batch methods
Task T005: Add SuggestionRepository::countBatchValidated
Task T006: Add ContributorLevelRepository::findAllSortedByThreshold

# Then, once T004/T005/T006 done:
Task T007: Update ContributorLevelService (depends on T004, T005, T006)
Task T008: Update ModerationService (depends on T003 — can overlap with T007)
```

## Parallel Example: Phase 3 (User Story 1)

```bash
# Independent first steps:
Task T009: Write ContributorLevelService tests
Task T010: Create _rank-badge.scss
Task T011: Create _rank_badge.html.twig

# Then in parallel (once T011/T007 done):
Task T012: Update ModerationController
Task T014: Update BookController
Task T016: Update AdminController
Task T018: Add ProfileController::publicProfile
```

---

## Implementation Strategy

### MVP First (User Story 1 + User Story 2)

1. Complete Phase 1: Baseline verification
2. Complete Phase 2: Foundational backend changes (CRITICAL — blocks all stories)
3. Complete Phase 3: US1 — rank badge in all identity zones
4. Complete Phase 4: US2 — dynamic suggestion banner
5. **STOP and VALIDATE**: Rank badge renders everywhere, banner shows correct delta
6. Deploy/demo if ready

### Incremental Delivery

1. Phase 1 + Phase 2 → Backend foundation ready
2. Phase 3 (US1) → Rank visible everywhere for ROLE_USER → Demo (MVP!)
3. Phase 4 (US2) → Dynamic banner added → Demo
4. Phase 5 (US3) → Rank-up notifications fire → Demo
5. Phase 6 (US4) → Fixture verified → Done
6. Phase 7 → Full validation pass

---

## Notes

- **No schema migration** — `contributor_level` table already exists and seeded
- **No cached rank on User** — always computed on-the-fly via `ContributorLevelService`
- **N+1 forbidden** — batch queries required for all list renders (see `computeRankBatch`)
- **Profile menu** — already wired as text title (`menuData.rankName`) — no badge needed there (spec FR-004 exemption)
- **CorrectionProposal status** — raw string `'PUBLISHED'`, NOT `SuggestionStatus::VALIDATED` (see research Decision 2)
- **Rank-up notification** — always fires, no preference gate (FR-011 removed)
- **Multiple thresholds skipped** — only final rank triggers notification (confirmed 2026-06-05)
