# Tasks: Système de Suivi — Créateurs & Collections (025)

**Input**: Design documents from `/specs/025-follow-collections-contributors/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/ ✅, quickstart.md ✅

**Tests**: Included (Constitution Check §V requires: FollowController, BookFollowJobHandler, BookPublishedFollowListener, ContributorRepository onlyFollowed, CollectionListController)

**Organization**: Tasks grouped by user story — each story independently implementable and testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story label (US1–US4)

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Messenger config and CLAUDE.md update — no entity or code dependencies

- [X] T001 Update `config/packages/messenger.yaml` — add explicit `retry_strategy` (max_retries: 3, multiplier: 2, delay: 1000) under `async` transport and add `routing: 'App\Messenger\Message\BookFollowJob': async` (R-001, R-008)
- [X] T002 [P] Update `CLAUDE.md` — replace current contents with pointer to `specs/025-follow-collections-contributors/plan.md`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Entities and migration — MUST be complete before any user story can begin

**⚠️ CRITICAL**: No user story work can begin until T003–T007 are done

- [X] T003 [P] Create `src/Entity/UserFollowedContributor.php` — Doctrine entity with `id INT`, `user ManyToOne User ON DELETE CASCADE`, `contributor ManyToOne Contributor ON DELETE CASCADE`, `createdAt DateTimeImmutable NOT NULL`; `#[UniqueConstraint]` on `(user_id, contributor_id)`; `#[Index]` on `contributor_id` (see data-model.md)
- [X] T004 [P] Modify `src/Entity/Book.php` — add `followNotificationSentAt: ?\DateTimeImmutable = null` with `#[ORM\Column(type: 'datetime_immutable', nullable: true)]` (data-model.md)
- [X] T005 [P] Modify `src/Entity/Enum/NotificationType.php` — add `case FOLLOW_NOVELTY = 'follow_novelty';` (data-model.md)
- [X] T006 Create `src/Repository/UserFollowedContributorRepository.php` — standard Doctrine repository; add `findRecipientsForBook(Book $book): array` returning `[['user'=>User, 'templateType'=>'contributor'|'collection', 'contributor'=>?Contributor, 'collection'=>?Collection]]` via single query with LEFT JOINs and priority MIN(CASE role) logic (data-model.md query block); add `isFollowing(User $user, Contributor $contributor): bool`; add `findFollowedContributorIds(User $user): array` returning UUIDs (depends on T003)
- [X] T007 Generate and review Doctrine migration — run `php bin/console doctrine:migrations:diff`, inspect generated `migrations/Version*.php` for `user_followed_contributor` table + `follow_notification_sent_at` column, then apply with `php bin/console doctrine:migrations:migrate --no-interaction` (depends on T003, T004)

**Checkpoint**: DB schema ready — user story work can now begin

---

## Phase 3: User Story 1 — Suivre un Créateur depuis la page Créateurs (Priority: P1) 🎯 MVP

**Goal**: Authenticated user can follow/unfollow a Contributor from the `/createurs` page with optimistic UI update; guest sees login modal

**Independent Test**: `POST /follow/contributor/{id}` persists relation; button renders correct initial state; guest clic triggers modal — no dependency on US2/US3/US4

### Tests for User Story 1

> **Write tests FIRST — they must FAIL before implementation**

- [X] T008 [P] [US1] Create `tests/Controller/FollowControllerTest.php` — functional tests: authenticated user follows contributor → response `{"followed":true, "token":"..."}` + relation in DB; authenticated user unfollows → `{"followed":false}`; invalid CSRF → 403; contributor not found → 404; unauthenticated → 401 (route protected); use PHPUnit + Symfony WebTestCase + fixtures/factory for User and Contributor

### Implementation for User Story 1

- [X] T009 [P] [US1] Create `assets/controllers/follow_controller.js` — Stimulus controller: `static values = {url: String, token: String, followed: Boolean, authenticated: Boolean}`; `static targets = ['button', 'icon', 'label']`; `toggle()` method: if `!authenticatedValue` dispatch `follow:open-login-modal` on window and return; optimistic toggle on button/icon/label + disable button; `fetch POST` with `_token` + `Content-Type: application/x-www-form-urlencoded`; on success update `tokenValue` + `followedValue` + re-enable; on error rollback state + re-enable + dispatch `follow:error` (toast 4s "Une erreur est survenue. Votre action n'a pas été enregistrée.") (contracts/follow-endpoints.md, R-003)
- [X] T010 [P] [US1] Create `templates/components/_follow_login_modal.html.twig` — reuse existing `modal_controller.js` + `Modal.html.twig` pattern; listen for `follow:open-login-modal` event on window; display message "Connectez-vous pour suivre ce créateur et recevoir des alertes." with CTA "Se connecter" linking to login route with `?_target_path={{ app.request.uri|url_encode }}` parameter to return user to origin page after login (R-007)
- [X] T011 [US1] Create `src/Controller/FollowController.php` — `POST /follow/contributor/{id}` route `follow_contributor_toggle`; `#[IsGranted('ROLE_USER')]`; validate CSRF token `follow_contributor_{id}`; load Contributor or 404; call `UserFollowedContributorRepository` to toggle (insert/delete); flush; return `JsonResponse(['followed' => $followed, 'token' => $newCsrfToken])` (note: direct repository call — matches existing `CollectionController` subscribe/unsubscribe pattern; no FollowService layer needed for this simple toggle) (depends on T006, T008)
- [X] T012 [US1] Update `src/Controller/CreateursController.php` — inject `UserFollowedContributorRepository`; after fetching contributor list, call `findFollowedContributorIds($this->getUser())` (returns `[]` for guests); pass `followedContributorIds` array to Twig template (depends on T006)
- [X] T013 [US1] Update `templates/createurs/index.html.twig` — include `_follow_login_modal.html.twig`; add Stimulus `data-controller="follow"` attributes on each contributor card button: `data-follow-url-value`, `data-follow-token-value="{{ csrf_token('follow_contributor_' ~ c.id) }}"`, `data-follow-followed-value="{{ c.id in followedContributorIds ? 'true' : 'false' }}"`, `data-follow-authenticated-value="{{ is_granted('ROLE_USER') ? 'true' : 'false' }}"` per contracts/follow-endpoints.md Twig usage example (depends on T009, T010, T012)

**Checkpoint**: User Story 1 fully functional — authenticated follow/unfollow works, guest sees modal, tests pass

---

## Phase 4: User Story 2 — Suivre une Collection depuis sa page détaillée (Priority: P1)

**Goal**: Authenticated user can follow/unfollow a Collection from its detail page with optimistic UI; guest sees login modal

**Independent Test**: `POST /follow/collection/{id}` persists relation via `UserCollectionSubscription`; button state correct on reload — no dependency on US1 tests (shares FollowController code but independent endpoint)

### Tests for User Story 2

- [X] T014 [P] [US2] Add collection follow test cases to `tests/Controller/FollowControllerTest.php` — follow collection → `{"followed":true}`; unfollow → `{"followed":false}`; invalid CSRF → 403; collection not found → 404; verify `UserCollectionSubscription` insert/delete in DB

### Implementation for User Story 2

- [X] T015 [US2] Add `POST /follow/collection/{id}` endpoint to `src/Controller/FollowController.php` — route `follow_collection_toggle`; `#[IsGranted('ROLE_USER')]`; validate CSRF `follow_collection_{id}`; load Collection or 404; toggle `UserCollectionSubscription` (insert/delete using `UserCollectionSubscriptionRepository` — existing repo); flush; return `JsonResponse(['followed' => $followed, 'token' => $newCsrfToken])` (note: direct repository call — matches existing `CollectionController` subscribe/unsubscribe pattern) (depends on T014)
- [X] T016 [US2] Update `templates/collection/show.html.twig` — replace existing POST form subscribe/unsubscribe button with Stimulus follow controller wiring: `data-controller="follow"`, `data-follow-url-value="{{ path('follow_collection_toggle', {id: collection.id}) }}"`, `data-follow-token-value="{{ csrf_token('follow_collection_' ~ collection.id) }}"`, `data-follow-followed-value="{{ isFollowed ? 'true' : 'false' }}"`, `data-follow-authenticated-value="{{ is_granted('ROLE_USER') ? 'true' : 'false' }}"` ; include `_follow_login_modal.html.twig`; inject `isFollowed` bool from controller (depends on T015)

**Checkpoint**: User Stories 1 and 2 both independently functional

---

## Phase 5: User Story 3 — Recevoir une notification lors d'une nouvelle publication (Priority: P2)

**Goal**: On Book publish (status → PUBLIÉ), async Messenger job notifies all users following ≥1 related Contributor or Collection; deduplicated per user; `followNotificationSentAt` prevents re-notification

**Independent Test**: Publish a book linked to a followed Author and followed Collection by same user → exactly 1 `FOLLOW_NOVELTY` notification in DB; republish → 0 new notifications (T004 followNotificationSentAt check)

### Tests for User Story 3

- [X] T017 [P] [US3] Create `tests/Messenger/BookFollowJobHandlerTest.php` — unit tests: book with `followNotificationSentAt` set → handler returns early, 0 notifications; book null `followNotificationSentAt` + 1 follower → 1 notification created, `followNotificationSentAt` set; user follows both Author AND Collection → 1 notification with `templateType=contributor`; multiple followers → N notifications; use PHPUnit + mocks for EntityManager and repositories
- [X] T034 [P] [US3] Create `tests/EventListener/BookPublishedFollowListenerTest.php` — unit tests: mock `MessageBusInterface`; Book status change to PUBLIÉ → `dispatch(BookFollowJob)` called once; Book status change to BROUILLON → dispatch NOT called; Book status PUBLIÉ but `followNotificationSentAt` already set → dispatch still called (guard is in handler, not listener); use PHPUnit mocks (Constitution §V — "primary business logic must have test coverage")

### Implementation for User Story 3

- [X] T018 [P] [US3] Create `src/Messenger/Message/BookFollowJob.php` — simple message DTO: `readonly class BookFollowJob { public function __construct(public readonly string $bookId) {} }`
- [X] T019 [US3] Create `src/EventListener/BookPublishedFollowListener.php` — Symfony event listener via Doctrine `postUpdate` event (`#[AsDoctrineListener(Events::postUpdate)]`); check `$args->getEntityChangeSet()` for `status` key and new value `BookStatus::PUBLIE`; dispatch `new BookFollowJob($book->getId())` via `MessageBusInterface`; keep listener thin — no notification logic; matches existing `BookAddedToCollectionListener` pattern (depends on T018, T034)
- [X] T020 [US3] Create `src/Messenger/Handler/BookFollowJobHandler.php` — `#[AsMessageHandler]`; load Book by `$message->bookId`, 404-guard; check `followNotificationSentAt` → early return if set (FR-013); set `followNotificationSentAt = new \DateTimeImmutable()` + flush (optimistic lock); call `UserFollowedContributorRepository::findRecipientsForBook($book)` for recipients list; for each recipient create `Notification` with type `FOLLOW_NOVELTY`, `sourceId = "follow_book_{$bookId}"`, message and URL per FR-010 template priority (Auteur > Illustrateur > Traducteur > Collection); batch persist + flush (depends on T006, T017, T018, T019)

**Checkpoint**: Publish flow dispatches async job → notifications created in DB for followers; deduplication confirmed by tests

---

## Phase 6: User Story 4 — Filtrer la liste par entités suivies (Priority: P2)

**Goal**: Authenticated user can toggle "Uniquement ceux que je suis" on `/createurs` and `/collections` to filter to followed entities; toggle hidden for guests; new `/collections` page created

**Independent Test**: User with 2 followed contributors activates `?onlyFollowed=1` → only those 2 appear; user with 0 follows → empty state CTA; same for `/collections?followed=true` — independent of US3

### Tests for User Story 4

- [X] T021 [P] [US4] Create `tests/Controller/CollectionListControllerTest.php` — GET /collections → 200 HTML; GET /collections?followed=true authenticated with subscriptions → only subscribed collections; GET /collections?followed=true unauthenticated → redirect or ignore filter; empty state when no subscriptions + toggle active → empty state message
- [X] T022 [P] [US4] Create `tests/Repository/ContributorRepositoryTest.php` — `applyFilters()` with `onlyFollowed=true` and a user with 1 followed contributor → returns only that contributor; `onlyFollowed=true` with no follows → empty result; `onlyFollowed=false` → all contributors

### Implementation for User Story 4

- [X] T023 [P] [US4] Create `src/Dto/CollectionListFilterState.php` — DTO with properties: `bool $followed = false`, `?string $genre = null`, `?string $statut = null`, `int $page = 1`; add static factory `fromRequest(Request $request): self` parsing query params `followed=true`, `genre`, `statut`, `page` (contracts/follow-endpoints.md GET /collections params)
- [X] T024 [US4] Modify `src/Repository/ContributorRepository.php` — update `findPaginatedFiltered()` signature to `findPaginatedFiltered(ContributorFilterState $state, ?User $user = null)`; add `onlyFollowed` branch: when `$state->onlyFollowed && $user !== null` add `innerJoin(UserFollowedContributor::class, 'ufc', 'WITH', 'ufc.contributor = c AND ufc.user = :followUser')->setParameter('followUser', $user)` (data-model.md filter block) (depends on T022)
- [X] T025 [US4] Modify `src/Service/ContributeurService.php` — update method signatures that call `ContributorRepository::findPaginatedFiltered()` to pass `?User $user = null` parameter; propagate `$user` from callers
- [X] T026 [US4] Update `src/Controller/CreateursController.php` — pass `$this->getUser()` to `ContributeurService` calls; keep `followedContributorIds` injection from T012; pass `isAuthenticated` bool to template for conditional toggle rendering (depends on T024, T025)
- [X] T027 [US4] Modify `src/Repository/CollectionRepository.php` (or create new method) — add `findPaginatedFiltered(CollectionListFilterState $state, ?User $user = null)` with pagination; when `$state->followed && $user !== null` add `innerJoin(UserCollectionSubscription::class, 'ucs', 'WITH', 'ucs.collection = c AND ucs.user = :followUser')` (data-model.md collection filter block); **also** add `findFollowedCollectionIds(User $user): array` to `UserCollectionSubscriptionRepository` — returns array of collection UUID strings for the given user (required by T028 for template `followedCollectionIds`) (depends on T023)
- [X] T028 [US4] Create `src/Controller/CollectionListController.php` — route `GET /collections` named `app_collections`; public access; parse `CollectionListFilterState::fromRequest($request)`; call `CollectionRepository::findPaginatedFiltered($state, $this->getUser())`; pass `filterState`, `collections`, `isAuthenticated`, `followedCollectionIds` (from `UserCollectionSubscription` repo) to template (depends on T021, T023, T027)
- [X] T029 [US4] Create `templates/collections/index.html.twig` — new collections list page modelled on `templates/createurs/index.html.twig` design pattern; sidebar with genre/statut filters + toggle "Uniquement ceux que je suis" (shown only when `is_granted('ROLE_USER')`, active when `filterState.followed`, URL param `?followed=true`); each collection card includes Stimulus follow controller wiring per contracts; empty state when no results + toggle active: "Vous ne suivez encore aucune collection. Découvrez les collections !" + link to `/collections` (no toggle); include `_follow_login_modal.html.twig` (depends on T028)
- [X] T030 [US4] Update `templates/createurs/index.html.twig` — add "Uniquement ceux que je suis" toggle in sidebar (shown only when `is_granted('ROLE_USER')`; active state when `?onlyFollowed=1`; empty state message: "Vous ne suivez encore personne. Découvrez les créateurs !" + link to `/createurs` without toggle when 0 follows) (depends on T026)

**Checkpoint**: All 4 user stories functional and independently testable

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T031 [P] Fix nav link — update base template `templates/base.html.twig` (or navbar partial) to replace `href="#"` on "Les collections" with `{{ path('app_collections') }}`
- [X] T032 Run full PHPUnit suite — `php bin/phpunit` — confirm 0 regressions; fix any broken tests from CreateursController/ContributorRepository signature changes
- [ ] T033 [P] Run quickstart.md validation scenarios — manual check: follow contributor (< 100ms), follow collection, guest modal, `?onlyFollowed=1` filter, `/collections?followed=true` filter, publish book → notification, republish → no new notification (quickstart.md §Vérification manuelle)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 — BLOCKS all user stories
- **Phase 3 (US1)**: Depends on Phase 2 — particularly T006 (UserFollowedContributorRepository)
- **Phase 4 (US2)**: Depends on Phase 2; can start in parallel with Phase 3 (different endpoint + different template)
- **Phase 5 (US3)**: Depends on Phase 2 (T004 followNotificationSentAt, T006 findRecipientsForBook); T034 has no dependencies — can start immediately; can start in parallel with US1/US2
- **Phase 6 (US4)**: Depends on Phase 2; T024 builds on ContributorRepository; independent of US3
- **Phase 7 (Polish)**: Depends on all desired stories complete

### User Story Dependencies

- **US1 (P1)**: After Phase 2 — no other story dependency
- **US2 (P1)**: After Phase 2 — no other story dependency; shares `FollowController.php` with US1 (add endpoint, don't rewrite)
- **US3 (P2)**: After Phase 2 — no dependency on US1/US2 (notifications are server-side only)
- **US4 (P2)**: After Phase 2 — depends on `UserFollowedContributorRepository::findFollowedContributorIds` (T006) already built by US1

### Within Each Story

- Tests FIRST (write and confirm FAIL) → Models/Repos → Services → Controllers → Templates

### Parallel Opportunities

- T003, T004, T005 in Phase 2 — different files, no inter-dependency
- T008, T009, T010 in Phase 3 — different files
- T018, T034 in Phase 5 — simple DTO and listener test, no dependencies
- T021, T022, T023 in Phase 6 — different files
- US1 backend (T011) and US2 tests (T014) — different concerns

---

## Parallel Example: User Story 1

```bash
# Run in parallel (after T003 done):
T008: Create tests/Controller/FollowControllerTest.php
T009: Create assets/controllers/follow_controller.js
T010: Create templates/components/_follow_login_modal.html.twig (with _target_path CTA)

# Then sequentially:
T011: FollowController.php contributor endpoint  (depends on T006, T008 red)
T012: CreateursController.php update             (depends on T006)
T013: templates/createurs/index.html.twig        (depends on T009, T010, T012)
```

---

## Implementation Strategy

### MVP First (US1 + US2 Only)

1. Phase 1: Setup config
2. Phase 2: Foundational entities + migration
3. Phase 3: US1 — Follow Créateur (P1)
4. Phase 4: US2 — Follow Collection (P1)
5. **STOP and VALIDATE**: Both P1 stories working, optimistic UI confirmed
6. Deploy/demo if ready

### Incremental Delivery

1. Setup + Foundational → DB schema live
2. US1 → test independently → confirm follow/unfollow + optimistic UI
3. US2 → test independently → collection follow/unfollow parity
4. US3 → test independently → notification pipeline verified
5. US4 → test independently → filter toggle + new /collections page

---

## Notes

- `UserCollectionSubscription` (existing entity) covers `UserFollowedCollections` — do NOT rename (R-002)
- Existing `collection_subscribe` / `collection_unsubscribe` POST routes remain as non-JS fallback; new AJAX endpoints are additive (R-002)
- `?onlyFollowed=1` kept for Créateurs (existing DTO convention); `?followed=true` for new Collections page (R-005, R-006)
- `followNotificationSentAt` is permanent — no reset mechanism; republication never triggers new notifications (FR-013)
- Stimulus `follow_controller.js` identifier: `follow` — must match `assets/controllers/follow_controller.js` filename convention
- All [P] tasks = different files, safe to run in parallel per story
