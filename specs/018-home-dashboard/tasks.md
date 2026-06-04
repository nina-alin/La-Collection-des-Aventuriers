---

description: "Task list for 018-home-dashboard implementation"
---

# Tasks: Dashboard (018-home-dashboard)

**Input**: Design documents from `/specs/018-home-dashboard/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/ui-contract.md ✅, quickstart.md ✅

**Tests**: Included — required by Constitution Principle V (DashboardService unit, ActivityEventListener integration, PurgeActivityEventsCommand unit, DashboardController functional).

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story?] Description`

- **[P]**: Can run in parallel (different files, no unmet dependencies)
- **[Story]**: User story this task belongs to (US1–US5)
- Exact file paths included in each task

---

## Phase 1: Setup

**Purpose**: Remove stale routes, register config parameter before any story work begins.

- [X] T001 [P] Remove `DefaultController::home()` home stub route and the `/suggestions`, `/suggestions/nouveau` placeholder stubs in `src/Controller/DefaultController.php` (delete file if no routes remain)
- [X] T002 [P] Add `app.forum_url` parameter (empty default) to `config/services.yaml` and bind it for injection into `DashboardController`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can start.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [X] T003 [P] Create `ActivityEventType` backed enum with cases SOCIAL / CONTRIBUTION / MODERATION / PERSONAL in `src/Entity/Enum/ActivityEventType.php`
- [X] T004 [P] Create `DashboardData` readonly DTO with all constructor fields as defined in `data-model.md` (greeting, formattedDate, headerSubtitle, collectionCount/Delta, toRead/ToBuy counts, suggestions fields, catalogue counts, library counts, wishlistCount, globalPendingSuggestions, recentBooks, averageRatings, activityEvents, isModerator) in `src/Dto/DashboardData.php`
- [X] T005 Create `ActivityEvent` entity with all fields (id, type, actorUser ManyToOne CASCADE, actorInitials, actorPseudo, bookTitle, bookSlug, statusBadge, createdAt) and indexes `idx_activity_event_created_at` and `idx_activity_event_type_created_at` in `src/Entity/ActivityEvent.php` (depends on T003)
- [X] T006 Create `ActivityEventRepository` with `findRecentCommunity(int $limit = 10): array` (ORDER BY createdAt DESC) and `deleteOlderThan(\DateTimeImmutable $before): int` in `src/Repository/ActivityEventRepository.php` (depends on T005)
- [X] T007 Create `DashboardController` with `#[Route('/', name: 'home')]`, `#[IsGranted('ROLE_USER')]`, `DashboardService` constructor injection, and a placeholder `home()` action returning an empty response in `src/Controller/DashboardController.php` (depends on T004)
- [X] T008 Generate Doctrine migration for the `activity_event` table (id, actor_user_id FK, type, actor_initials, actor_pseudo, book_title, book_slug, status_badge, created_at) with both indexes in `migrations/` (depends on T005, run `php bin/console doctrine:migrations:diff`)

**Checkpoint**: Foundation ready — user story implementation can now begin.

---

## Phase 3: User Story 1 — Consultation du tableau de bord personnel (Priority: P1) 🎯 MVP

**Goal**: Authenticated user sees header (date, greeting, contextual subtitle) and 3 KPI blocks (MA COLLECTION, À LIRE, MES SUGGESTIONS) with correct personal data.

**Independent Test**: Verify header and 3 KPI blocks display correct values for a known user fixture (collection count, delta, reading pile, buy list, suggestions) without other sections.

### Tests for User Story 1

- [X] T009 [P] [US1] Write `DashboardServiceTest` covering: header greeting format, formattedDate zero-padded day, subtitle for standard user vs moderator, subtitle when `previousLoginAt` is null (welcome message), all 3 KPI values and edge cases (0 collection, 0 suggestions, 0 validated recently) in `tests/Unit/Service/DashboardServiceTest.php`
- [X] T010 [P] [US1] Write `DashboardControllerTest` covering: unauthenticated redirect to `/connexion`, authenticated `200 OK` with `#dashboard-header` and `#kpi-blocks` present in HTML in `tests/Functional/Controller/DashboardControllerTest.php`

### Implementation for User Story 1

- [X] T011 [P] [US1] Add `lastLoginAt: ?\DateTimeImmutable` and `previousLoginAt: ?\DateTimeImmutable` nullable columns with getters/setters to `src/Entity/User.php`
- [X] T012 [US1] Generate Doctrine migration for `last_login_at` and `previous_login_at` nullable timestamp columns on `user` table in `migrations/` (depends on T011, run `php bin/console doctrine:migrations:diff`)
- [X] T013 [P] [US1] Update `AuthenticationEventSubscriber::onLoginSuccess()` to rotate timestamps: `previousLoginAt ← lastLoginAt`, `lastLoginAt ← now (UTC)`, then flush in `src/EventSubscriber/AuthenticationEventSubscriber.php`
- [X] T014 [P] [US1] Add `countOwnedByUser(User $user): int`, `countOwnedAddedSince(User $user, \DateTimeImmutable $since): int`, `countToReadByUser(User $user): int`, `countToBuyByUser(User $user): int` to `src/Repository/UserBookRepository.php`
- [X] T015 [P] [US1] Add `countAllByUser(User $user): int` and `countRecentlyValidatedByUser(User $user, \DateTimeImmutable $since): int` (validated in last 24 h) to `src/Repository/SuggestionRepository.php`
- [X] T016 [P] [US1] Add `countPublished(): int` (all PUBLISHED books, respects Gedmo SoftDeleteable filter) and `countPublishedSince(\DateTimeImmutable $since): int` (PUBLISHED books with `createdAt >= $since`, same filter — required by `buildHeader()` for FR-003 "[N] nouvelle(s) fiche(s) depuis ta dernière visite") to `src/Repository/BookRepository.php`
- [X] T017 [US1] Create `DashboardService` with constructor injecting all required repositories and `Security`; implement `buildDashboardData(User $user): DashboardData` with independent `try/catch` per section returning sentinel values on failure and populating an `$errors` array in `src/Service/DashboardService.php` (depends on T004, T014, T015, T016)
- [X] T018 [US1] Implement `buildHeader()` private method in `DashboardService`: compute `formattedDate` (zero-padded day, uppercase, no year), `greeting` ("SALUTATIONS, {PSEUDO}."), `headerSubtitle` (standard: "[N] nouvelle(s) fiche(s) depuis ta dernière visite"; moderator: "[N] nouvelle(s) fiche(s) · [M] suggestion(s) en attente"; first visit: generic welcome when `previousLoginAt` is null — [N] from `BookRepository::countPublishedSince($user->getPreviousLoginAt())`, [M] from `SuggestionRepository::countGlobalPending()`) in `src/Service/DashboardService.php` (depends on T016, T017, T023)
- [X] T019 [US1] Implement `buildKpis()` private method in `DashboardService`: compute `collectionCount`, `collectionDelta` (sliding 30-day window via `countOwnedAddedSince`), `toReadCount`, `toBuyCount`, `suggestionsTotal`, `suggestionsPending`, `suggestionsValidatedRecently` (last 24 h) in `src/Service/DashboardService.php` (depends on T017, T014, T015)
- [X] T020 [US1] Wire `DashboardController::home()` to call `DashboardService::buildDashboardData($this->getUser())` and render `home/index.html.twig` with `['dashboardData' => $data, 'forumUrl' => $this->forumUrl]` (`$forumUrl` is the `%app.forum_url%` config param bound via T002) in `src/Controller/DashboardController.php` (depends on T002, T007, T017)
- [X] T021 [US1] Implement dashboard template: `#dashboard-header` section (formattedDate, greeting, headerSubtitle) and `#kpi-blocks` section (3 blocks with main value + conditional subtitle per ui-contract.md edge cases: hidden if delta=0, hidden if count=0) with keyboard-navigable structure in `templates/home/index.html.twig` (depends on T020)

**Checkpoint**: Header shows correct date, greeting, and contextual subtitle. All 3 KPI blocks display correct values. `DashboardServiceTest` and `DashboardControllerTest` pass.

---

## Phase 4: User Story 2 — Navigation rapide depuis le dashboard (Priority: P2)

**Goal**: User navigates the application via a 4-card quick-access grid with dynamic subtitles; moderator/admin additionally sees the "ÉDITER UNE FICHE" card.

**Independent Test**: Verify 4 standard cards always present with correct subtitles; moderation card present for ROLE_MODERATOR/ROLE_ADMIN, absent from DOM for ROLE_USER.

### Tests for User Story 2

- [X] T022 [P] [US2] Add `DashboardControllerTest` assertions: `#quick-access-grid` contains exactly 4 cards for a standard user, moderation card absent from DOM; repeat as moderator and verify 5th card present; also assert `#forum-banner` is present in DOM and the "Y aller ->" `<a>` `href` matches the configured forum URL in `tests/Functional/Controller/DashboardControllerTest.php`

### Implementation for User Story 2

- [X] T023 [P] [US2] Add `countGlobalPending(): int` (all users, status PENDING) to `src/Repository/SuggestionRepository.php`
- [X] T024 [P] [US2] Add `countWithPublishedBooks(): int` to `src/Repository/ContributorRepository.php` counting contributors who have at least one PUBLISHED book contribution (existing `ContributorRepository::countAll()` counts all contributors regardless of book status — insufficient for FR-008 catalogue counts)
- [X] T025 [US2] Implement `buildQuickAccess()` private method in `DashboardService`: compute `catalogueBookCount`, `catalogueAuthorCount`, `libraryBookCount` (= collectionCount), `libraryToReadCount` (= toReadCount), `wishlistCount` (= toBuyCount), `globalPendingSuggestions`, `isModerator` (via `Security::isGranted()`) in `src/Service/DashboardService.php` (depends on T023, T024)
- [X] T026 [US2] Implement `#quick-access-grid` section in `templates/home/index.html.twig`: 4 standard cards with dynamic subtitles, featured style on "FAIRE UNE SUGGESTION", conditional moderation card via `{% if dashboardData.isModerator %}` (no `is_granted()` in template), `aria-label` on all card `<a>` elements (depends on T021, T025)

**Checkpoint**: 4 standard cards always visible with correct subtitles. Moderation card gated correctly by role, verified by `DashboardControllerTest`.

---

## Phase 5: User Story 3 — Découverte des nouveautés du catalogue (Priority: P3)

**Goal**: User sees the 5 most recently updated published books with cover, title, author, year, catalogue reference, half-star rating, and relative timestamp; can click through to detail or "TOUT VOIR".

**Independent Test**: Verify exactly 5 books ordered by `updated_at` DESC, all data fields present, star rating renders correctly for `.5` averages, cover placeholder shown when image absent.

### Tests for User Story 3

- [X] T027 [P] [US3] Write `RatingExtensionTest` unit test: score 0→0 stars, score 10→5 stars, score 7→3.5 stars, score 8→4 stars (rounding formula `round($avg / 2 * 2) / 2`), output is structured array for full/half/empty icons in `tests/Unit/Twig/RatingExtensionTest.php`
- [X] T027b [P] [US3] Write `BookRepositoryTest` integration test for `findRecentlyPublished(5)` (SC-004): seed 6+ books with distinct `updatedAt` values (mix PUBLISHED and non-PUBLISHED, include one soft-deleted), assert exactly 5 PUBLISHED non-deleted books returned ordered by `updatedAt` DESC in `tests/Integration/Repository/BookRepositoryTest.php`

### Implementation for User Story 3

- [X] T028 [P] [US3] Add `findRecentlyPublished(int $limit = 5): array` (LEFT JOIN contributions, contributor, editor; WHERE status = PUBLISHED; ORDER BY updatedAt DESC; LIMIT) to `src/Repository/BookRepository.php`
- [X] T029 [US3] Create `RatingExtension` Twig extension registering `rating_stars` filter: converts 0–10 average to 0–5 scale, rounds to nearest 0.5, returns `['full' => int, 'half' => bool, 'empty' => int]` array in `src/Twig/Extension/RatingExtension.php` (depends on T027)
- [X] T030 [US3] Implement `buildNewEntries()` private method in `DashboardService`: call `findRecentlyPublished(5)`, collect book IDs, call existing `findAverageRatingsByIds(array $bookIds)`, populate `recentBooks` and `averageRatings` in `src/Service/DashboardService.php` (depends on T028, T029)
- [X] T031 [US3] Implement `#nouveautes` section in `templates/home/index.html.twig`: cover with `alt="{book.title} — couverture"` (fallback placeholder `alt="Couverture non disponible"` on error), title, author, year, catalogue ref, star rating via `|rating_stars` filter with `aria-label="{N}/5 étoiles"`, relative timestamp, "TOUT VOIR ->" link to `/catalogue`, empty-state if fewer than 5 books (depends on T030)

**Checkpoint**: 5 most recent published books displayed. Half-star ratings render correctly. `RatingExtensionTest` passes.

---

## Phase 6: User Story 4 — Suivi de l'activité communautaire (Priority: P4)

**Goal**: User sees a chronological feed of up to 10 community events (ratings, publications, moderation actions, wishlist additions) with avatar, descriptive phrase, optional status badge, and relative timestamp.

**Independent Test**: Seed 4 known ActivityEvents (one per type); verify feed shows correct phrases, correct 2nd-person vs 3rd-person logic for moderation events, status badges, and avatars.

### Tests for User Story 4

- [X] T032 [P] [US4] Write `ActivityEventListenerTest` integration test: dispatch each of the 4 domain events, assert correct `ActivityEvent` record written (type, actorPseudo, bookTitle, bookSlug, statusBadge) for SOCIAL / CONTRIBUTION / MODERATION / PERSONAL in `tests/Integration/EventListener/ActivityEventListenerTest.php`
- [X] T033 [P] [US4] Write `PurgeActivityEventsCommandTest` unit test: mock `ActivityEventRepository::deleteOlderThan`, assert command calls it with a date 30 days before now and outputs the deleted count in `tests/Unit/Command/PurgeActivityEventsCommandTest.php`

### Implementation for User Story 4

- [X] T034 [P] [US4] Create `ReviewSubmittedEvent` readonly class with `User $actor`, `Book $book` constructor in `src/Event/ReviewSubmittedEvent.php`
- [X] T035 [P] [US4] Create `BookPublishedEvent` readonly class with `User $actor` (moderator), `Book $book` constructor in `src/Event/BookPublishedEvent.php`
- [X] T036 [P] [US4] Create `SuggestionModeratedEvent` readonly class with `User $actor`, `Suggestion $suggestion`, `SuggestionStatus $newStatus` constructor in `src/Event/SuggestionModeratedEvent.php`
- [X] T037 [P] [US4] Create `BookAddedToWishlistEvent` readonly class with `User $actor`, `Book $book` constructor in `src/Event/BookAddedToWishlistEvent.php`
- [X] T038 Create `ActivityEventListener` handling all 4 events: build `ActivityEvent` from event payload (snapshot actorPseudo, actorInitials, bookTitle, bookSlug, statusBadge at write time), persist and flush synchronously via `EntityManagerInterface` in `src/EventListener/ActivityEventListener.php` (depends on T034–T037, T005, T006)
- [X] T039 [P] [US4] Dispatch `new ReviewSubmittedEvent($user, $book)` in `ReviewService` after `flush()` in `src/Service/ReviewService.php` (depends on T034)
- [X] T040 [P] [US4] Dispatch `new BookPublishedEvent($actor, $book)` when Book status → PUBLISHED and `new SuggestionModeratedEvent($actor, $suggestion, $newStatus)` when suggestion validated/refused in `src/Service/ModerationService.php` (depends on T035, T036)
- [X] T041 [P] [US4] Dispatch `new BookAddedToWishlistEvent($actor, $book)` when `isToBuy` is set to true in `src/Service/UserBookService.php` (depends on T037)
- [X] T042 [US4] Create `PurgeActivityEventsCommand` (name: `app:purge-activity-events`) calling `ActivityEventRepository::deleteOlderThan(new \DateTimeImmutable('-30 days'))` and outputting deleted count in `src/Command/PurgeActivityEventsCommand.php` (depends on T006)
- [X] T043 [US4] Add `purge_activity_events` cron entry (`spec: "0 3 1 * *"`, `cmd: "php bin/console app:purge-activity-events"`) under `crons:` in `.platform.app.yaml`
- [X] T044 [US4] Implement `buildActivityFeed()` private method in `DashboardService`: call `ActivityEventRepository::findRecentCommunity(10)`, populate `activityEvents` array in `src/Service/DashboardService.php` (depends on T006)
- [X] T045 [US4] Implement `#activite` section in `templates/home/index.html.twig`: avatar circle with `actorInitials` (or placeholder if null) and `role="img" aria-label`, phrase template per event type (3rd person default; 2nd person for MODERATION events where `actorUser == app.user`), optional `statusBadge` chip, relative timestamp, "MON FIL ->" link to `/activite`, empty-state "Pas encore d'activité communautaire." when feed is empty (depends on T044)

**Checkpoint**: All 4 event types feed into `ActivityEvent` table. Feed renders correctly in template. `ActivityEventListenerTest` and `PurgeActivityEventsCommandTest` pass.

---

## Phase 7: User Story 5 — Accès au forum communautaire (Priority: P5)

**Goal**: Every authenticated user sees the "REJOINDRE LA TAVERNE DES AVENTURIERS" forum banner at the bottom of the page with a working "Y aller ->" link.

**Independent Test**: Verify `#forum-banner` block is present in DOM for any authenticated user and `href` matches configured forum URL.

### Implementation for User Story 5

- [X] T046 [US5] Implement `#forum-banner` section at bottom of `templates/home/index.html.twig`: "REJOINDRE LA TAVERNE DES AVENTURIERS" heading and "Y aller ->" button `<a>` linking to `%app.forum_url%` (passed from `DashboardController` via template variable) (depends on T021)

**Checkpoint**: Forum banner present at page bottom. "Y aller ->" link uses configured URL.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Responsiveness, accessibility, and final validation across all sections.

- [X] T047 [P] Validate mobile-first responsive layout in `templates/home/index.html.twig` per `contracts/ui-contract.md#responsive-mobile-first-contract`: KPI blocks stack vertically on `< md`, 2×2 quick-access grid on mobile / 2-col tablet / 4-col desktop, single-column nouveautés horizontal card on `lg+` — use existing Bootstrap breakpoint classes
- [X] T048 [P] WCAG 2.1 AA audit on `templates/home/index.html.twig`: all cover images have `alt`, all interactive cards/buttons are `<a>`/`<button>` with descriptive `aria-label`, star rating container has `aria-label="{N}/5 étoiles"`, avatar circles have `role="img" aria-label`, keyboard tab order covers all interactive elements
- [ ] T049 Run full test suite (`php bin/phpunit`) and verify all quickstart.md smoke tests pass (header date format, KPI increment, moderation card RBAC, nouveautés ordering, activity feed event types, forum banner link); verify SC-001 by measuring dashboard response time via Symfony profiler with realistic fixture data — flag if approaching 3s threshold

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 — BLOCKS all user stories
- **US1 (Phase 3)**: Depends on Phase 2 — 🎯 MVP, validate before continuing
- **US2 (Phase 4)**: Depends on Phase 2 (and can reuse US1 repo methods already built)
- **US3 (Phase 5)**: Depends on Phase 2 (independent of US1/US2)
- **US4 (Phase 6)**: Depends on Phase 2 (ActivityEvent entity from Foundation)
- **US5 (Phase 7)**: Depends on Phase 1 (forum_url config) and US1 template skeleton
- **Polish (Phase 8)**: Depends on all desired user stories complete

### User Story Dependencies

- **US1 (P1)**: No inter-story dependencies — pure user data
- **US2 (P2)**: Can reuse `collectionCount`, `toReadCount`, `toBuyCount` computed in US1; adds `globalPendingSuggestions` and `catalogueBookCount`
- **US3 (P3)**: Independent — only `BookRepository` and new `RatingExtension`
- **US4 (P4)**: Independent — own entity, events, listener; only shares `DashboardService` integration point
- **US5 (P5)**: Independent — template-only addition

### Within Each User Story

- Tests listed before implementation (write tests, then implement until they pass)
- Entities before repositories
- Repositories before services
- Services before controller wiring
- Controller wiring before template

### Parallel Opportunities

- T001 ‖ T002 (Phase 1)
- T003 ‖ T004 (Phase 2)
- T005 → T006 → T007 (sequential)
- T009 ‖ T010 ‖ T011 ‖ T013 ‖ T014 ‖ T015 ‖ T016 (US1 tests + repo tasks in parallel)
- T018 ‖ T019 (within `DashboardService`, once T017 exists — different private methods)
- T022 ‖ T023 ‖ T024 (US2 test + repo tasks in parallel)
- T027 ‖ T028 (US3 test + repo in parallel)
- T032 ‖ T033 ‖ T034 ‖ T035 ‖ T036 ‖ T037 (US4 tests + domain events in parallel)
- T039 ‖ T040 ‖ T041 (dispatch wiring in separate service files)
- T047 ‖ T048 (Phase 8)

---

## Parallel Execution Examples

### User Story 1 (after Foundational complete)

```
Parallel batch A (tests + repos + entity):
  T009 DashboardServiceTest
  T010 DashboardControllerTest
  T011 User entity timestamps
  T013 AuthenticationEventSubscriber
  T014 UserBookRepository methods
  T015 SuggestionRepository methods
  T016 BookRepository::countPublished

Sequential:
  T012 Migration (after T011)
  T017 DashboardService (after T016)
  T018 buildHeader (after T017)
  T019 buildKpis (after T017)
  T020 Controller wire (after T017)
  T021 Template header+KPIs (after T020)
```

### User Story 4 (after Foundational complete)

```
Parallel batch A (tests + events):
  T032 ActivityEventListenerTest
  T033 PurgeActivityEventsCommandTest
  T034 ReviewSubmittedEvent
  T035 BookPublishedEvent
  T036 SuggestionModeratedEvent
  T037 BookAddedToWishlistEvent

Sequential:
  T038 ActivityEventListener (after T034–T037)

Parallel batch B (service dispatch wiring):
  T039 ReviewService
  T040 ModerationService
  T041 UserBookService
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001–T002)
2. Complete Phase 2: Foundational (T003–T008) — CRITICAL
3. Complete Phase 3: User Story 1 (T009–T021)
4. **STOP and VALIDATE**: Header + KPIs work, tests pass
5. Deploy/demo if ready

### Incremental Delivery

1. Setup + Foundational → Foundation ready
2. US1 (P1) → Personal header + KPIs → **MVP**
3. US2 (P2) → Quick navigation grid → **v1.1**
4. US3 (P3) → Catalogue nouveautés → **v1.2**
5. US4 (P4) → Activity feed + purge infra → **v1.3**
6. US5 (P5) → Forum banner → **v1.4**
7. Polish → Responsive + WCAG + test suite → **Ship**

---

## Notes

- `[P]` = different files, no unmet dependencies within the phase
- `[US1]`–`[US5]` map tasks to user stories for traceability
- Migration tasks require running `php bin/console doctrine:migrations:diff` after entity changes
- `DashboardService::buildDashboardData()` wraps each `build*()` section in its own `try/catch`; failures populate an `$errors` array; template renders inline error blocks per failed section
- `%app.forum_url%` is a static config value — no DB lookup needed
- The `rating_stars` filter converts Review scores (stored 1–10) to display (0–5) using `round($avg / 2 * 2) / 2`
- `ActivityEvent` writes are synchronous (Decision 2 in research.md) — no Messenger bus
- Role check for moderation card lives in `DashboardService` via `Security::isGranted()`, not in Twig
- T024: check existing `ContributorRepository` or `PersonRepository` for a `countAll()` before adding a new method
