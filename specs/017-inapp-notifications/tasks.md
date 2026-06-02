---

description: "Task list for Système de Notifications In-App"
---

# Tasks: Système de Notifications In-App

**Input**: Design documents from `/specs/017-inapp-notifications/`

**Organization**: Tasks grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1–US5)
- Exact file paths included in each description

---

## Phase 1: Setup (Symfony Messenger Infrastructure)

**Purpose**: Install Messenger and configure async transport before any domain work begins.

- [X] T001 Install symfony/messenger dependency (`composer require symfony/messenger`) in project root
- [X] T002 [P] Create `config/packages/messenger.yaml` with Doctrine async transport, failed transport, and `App\Messenger\Message\NotificationMessage` routed to `async` (per research.md Decision 1)
- [X] T003 [P] Add `workers.messenger` section to `.platform.app.yaml` with `php bin/console messenger:consume async --memory-limit=128M --time-limit=3600` start command (per research.md Decision 1)

---

## Phase 2: Foundational (Data Layer — Blocks All User Stories)

**Purpose**: Entities, repositories, service, Messenger DTO + handler, migration. Must be complete before any US phase.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

### Entities & Enum

- [X] T004 [P] Create `NotificationType` backed enum in `src/Entity/Enum/NotificationType.php` (4 string cases: `CONTRIBUTION_VALIDATED`, `BOOK_ACTIVITY`, `MODERATION_PENDING`, `RANK_UP`)
- [X] T005 [P] Create `Notification` entity in `src/Entity/Notification.php` with all columns (id, user_id FK CASCADE, type enum, message text, target_url nullable, is_read bool default false, created_at, source_id varchar 255), `UNIQUE(user_id, source_id)` constraint, two indexes, and constructor per `data-model.md`
- [X] T006 [P] Create `NotificationPreference` entity in `src/Entity/NotificationPreference.php` with OneToOne User FK, 4 boolean fields (all default true), and `isEnabled(NotificationType): bool` match method per `data-model.md`
- [X] T007 [P] Create `UserCollectionSubscription` entity in `src/Entity/UserCollectionSubscription.php` with user FK, collection FK, `UNIQUE(user_id, collection_id)`, `INDEX(collection_id)`, and constructor per `data-model.md`
- [X] T008 Add nullable `timezone` varchar(50) field to `src/Entity/User.php` with `getTimezone()` and `setTimezone()` accessors (per `data-model.md` Decision 3)

### Messenger DTO

- [X] T009 [P] Create `NotificationMessage` final readonly DTO in `src/Messenger/Message/NotificationMessage.php` (constructor args: `string $userId`, `string $type`, `string $message`, `string $sourceId`, `?string $targetUrl = null`) per `data-model.md`

### Repositories

- [X] T010 Create `NotificationRepository` in `src/Repository/NotificationRepository.php` with all query methods: `findRecentForUser(User, int limit=20): array`, `findPaginatedForUser(User, int page, int perPage=20): Paginator`, `countUnreadForUser(User): int`, `markReadById(User, int id): void`, `markAllReadForUser(User): void`, `deleteUnreadByTypeForUser(User, NotificationType): void`, `countForUser(User): int`, `deleteOldestForUser(User, int count): void`
- [X] T011 [P] Create `NotificationPreferenceRepository` in `src/Repository/NotificationPreferenceRepository.php` with `findByUser(User): ?NotificationPreference` method
- [X] T012 [P] Create `UserCollectionSubscriptionRepository` in `src/Repository/UserCollectionSubscriptionRepository.php` with `findSubscribersByCollection(Collection): User[]` method (for fan-out in BookAddedToCollectionListener)

### Service

- [X] T013 Create `NotificationService` in `src/Service/NotificationService.php` injecting `NotificationRepository` and `NotificationPreferenceRepository`; implement `markRead(User, int id): void`, `markAllRead(User): void`, `getUnreadCount(User): int`, `deleteUnreadByType(User, NotificationType): void`

### Messenger Handler

- [X] T014 Create `NotificationMessageHandler` in `src/Messenger/Handler/NotificationMessageHandler.php` (#[AsMessageHandler]): resolve User by ID, check `NotificationPreference::isEnabled()` (skip if disabled), create and persist `Notification`, catch `UniqueConstraintViolationException` silently (idempotence), after flush prune oldest if `countForUser > 500` (delete `count - 500` oldest in same transaction)

### Migration & Transport

- [X] T015 Generate Doctrine migration via `php bin/console doctrine:migrations:diff` covering `notification`, `notification_preference`, `user_collection_subscription` tables and `user.timezone` column; review SQL against `data-model.md` migration plan; apply with `php bin/console doctrine:migrations:migrate`
- [X] T016 Run `php bin/console messenger:setup-transports` to create the `messenger_messages` and `failed_messages` tables in the database

### Collection Subscription UI (prerequisite for `book_activity` notifications)

- [X] T056 Add `subscribe()` and `unsubscribe()` actions to `src/Controller/CollectionController.php`: `#[Route('/collections/{id}/subscribe', name: 'collection_subscribe', methods: ['POST'])]` and `#[Route('/collections/{id}/unsubscribe', name: 'collection_unsubscribe', methods: ['POST'])]`, both `#[IsGranted('ROLE_USER')]` with CSRF token `collection_subscribe_{id}`; `subscribe` persists `new UserCollectionSubscription($user, $collection)` ignoring `UniqueConstraintViolationException`; `unsubscribe` removes the existing row if found; both redirect back to collection page
- [X] T057 [P] Update `CollectionController::show()` to inject `UserCollectionSubscriptionRepository`, compute `$isSubscribed = (bool) $repo->findOneBy(['user' => $user, 'collection' => $collection])` for authenticated users (false for guests), pass to `templates/collection/show.html.twig`; add subscribe/unsubscribe toggle button to `templates/collection/show.html.twig` (CSRF form, visible only to `ROLE_USER`)

### Foundational Tests (PHPUnit)

- [X] T017 [P] Create PHPUnit tests for `Notification` entity in `tests/Notification/Entity/NotificationTest.php`: constructor sets all fields correctly, `isRead` defaults to `false`, `createdAt` is UTC `DateTimeImmutable`, `sourceId` format validated
- [X] T018 [P] Create PHPUnit tests for `NotificationPreference` entity in `tests/Notification/Entity/NotificationPreferenceTest.php`: all 4 types default enabled, `isEnabled()` match returns correct boolean per type, disabling one type does not affect others
- [X] T019 Create PHPUnit tests for `NotificationRepository` in `tests/Notification/Repository/NotificationRepositoryTest.php`: `countUnreadForUser` returns correct count, `markAllReadForUser` sets all is_read=true, `deleteUnreadByTypeForUser` deletes only matching unread, `deleteOldestForUser` removes correct rows
- [X] T020 Create PHPUnit tests for `NotificationMessageHandler` in `tests/Notification/Messenger/NotificationMessageHandlerTest.php`: creates notification row on valid message, silently skips on duplicate `sourceId`, skips insert when preference disabled, prunes to 500 after insert
- [X] T058 [P] Create PHPUnit tests for `UserCollectionSubscription` entity in `tests/Notification/Entity/UserCollectionSubscriptionTest.php`: constructor sets user and collection correctly, `createdAt` is UTC `DateTimeImmutable` (Constitution V — all main entities require tests)

**Checkpoint**: Foundation complete — data layer verified, migration applied, Messenger transport ready.

---

## Phase 3: User Story 1 — Badge + Panel + Click-to-Read (Priority: P1) 🎯 MVP

**Goal**: Authenticated user sees unread badge on bell, opens panel, views notifications grouped by date, clicks to mark read and redirect.

**Independent Test**: Seed 3 unread notifications for a test user → load any page → badge shows "3" → click bell → panel shows notifications grouped "Nouvelles · aujourd'hui" / "Plus anciennes" → click one → notification marked read, badge drops to 2, user redirected to `targetUrl`.

- [X] T021 [US1] Create `NotificationExtension` implementing `GlobalsInterface` in `src/Twig/Extension/NotificationExtension.php`: inject `Symfony\Bundle\SecurityBundle\Security` + `NotificationRepository`; `getGlobals()` returns `['unread_count' => $repo->countUnreadForUser($user)]` for authenticated users, `['unread_count' => 0]` for guests
- [X] T022 [US1] Create `NotificationPanelComponent` in `src/Twig/Components/Notification/NotificationPanelComponent.php` (#[AsLiveComponent], extends AbstractController): computed props `getNotifications(): array` (latest 20, filters `moderation_pending` for non-ROLE_MODERATOR), `getUnreadCount(): int`, `getTodayBoundary(): \DateTimeImmutable` (start of today in user's timezone from `$user->getTimezone() ?? 'UTC'`); `#[LiveAction] #[IsGranted('ROLE_USER')] markRead(int $id)` calls `NotificationService::markRead` and emits `notification:panel:redirect` browser event with `targetUrl`; `#[LiveAction] #[IsGranted('ROLE_USER')] markAllRead()` calls `NotificationService::markAllRead` **and dispatches browser event `notification:panel:read-all`** via `$this->dispatchBrowserEvent('notification:panel:read-all')`
- [X] T023 [US1] Create `templates/components/notification/notification_panel_component.html.twig`: bell button with `unread_count` badge (hidden if 0), `.menu-card` panel with `.menu-head` (title + "Tout marquer lu" button `data-action="live#action"` `data-live-action-param="markAllRead"`), `.notif-list` with today/older group headers, `{% for notification in getNotifications() %}` each `.notif-item` with pastille for unread, click triggers `markRead(id)`, empty state when no notifications, `.menu-foot` with "Voir toutes" link to `notification_history` route and "PRÉFÉRENCES" link to `profile_settings#notifications`; add click-outside dismiss via `data-action="click@window->live#action"` guard (check `!event.target.closest('.menu-card, .notif-bell')`) or equivalent LiveComponent `@window-click` pattern (FR-003)
- [X] T024 [US1] Embed `<twig:Notification:NotificationPanel />` into navbar Twig template (locate existing navbar template, wrap in `{% if is_granted('ROLE_USER') %}`, preserving existing bell icon position)
- [X] T025 [US1] Create `src/Controller/NotificationController.php` with `markRead(int $id)` action: `#[Route('/notifications/{id}/read', name: 'notification_mark_read', methods: ['POST'])]`, `#[IsGranted('ROLE_USER')]`, CSRF token `notification_read_{id}`, call `NotificationService::markRead`, redirect to `targetUrl` or `/` with info flash `'Cette notification n'a plus de cible.'` if null; 404 if not found for current user

**Checkpoint**: US1 fully functional — badge reflects unread count on page load, panel opens/closes, clicking notification marks it read and redirects.

---

## Phase 4: User Story 2 — Tout Marquer Lu (Priority: P2)

**Goal**: "Tout marquer lu" marks all notifications read; badge goes to 0 without full page reload; state persisted on refresh.

**Independent Test**: Seed 5 unread notifications → open panel → click "Tout marquer lu" → all pastilles disappear, badge=0 without page reload → refresh page → badge still 0.

- [X] T026 [P] [US2] Add `markAllRead()` action to `src/Controller/NotificationController.php`: `#[Route('/notifications/read-all', name: 'notification_mark_all_read', methods: ['POST'])]`, `#[IsGranted('ROLE_USER')]`, CSRF token `notifications_read_all`, call `NotificationService::markAllRead`, redirect to `HTTP_REFERER` or `/notifications` (non-JS fallback path)
- [X] T027 [US2] Verify `markAllRead` LiveAction in `NotificationPanelComponent` (T022) re-renders with `getUnreadCount()=0` and no pastilles; create `assets/controllers/notification_controller.js` Stimulus controller that listens for the `notification:panel:read-all` browser event dispatched by T022's `markAllRead` LiveAction and updates the static navbar badge `.notif-badge` count to 0 (since `unread_count` Twig global is set at page load only; connect this controller on the `<body>` or navbar element)

**Checkpoint**: US1 + US2 both functional — individual and bulk read marking working.

---

## Phase 5: User Story 3 — Affichage Différencié par Type (Priority: P2)

**Goal**: Each notification type displays distinct icon and CSS class: `contribution_validated` → green checkmark, `book_activity` → collection initials avatar, `moderation_pending` → warning triangle (ROLE_MODERATOR only), `rank_up` → star icon.

**Independent Test**: Create one notification per type → open panel → verify `contribution_validated` has `.success` class + SVG checkmark, `book_activity` has `.info.illustr` + collection initials on gradient, `moderation_pending` only visible to ROLE_MODERATOR with `.warn` + triangle, `rank_up` has `.success` + SVG star.

- [X] T028 [US3] Implement type-to-CSS-class and icon rendering in `templates/components/notification/notification_panel_component.html.twig`: add `{% set typeClass %}` switch per `NotificationType` value (success/info illustr/warn/success), SVG icons per type (checkmark, triangle, star), collection initials avatar with gradient for `book_activity` using collection name from notification message, unread pastille visible when `not notification.isRead`
- [X] T029 [US3] Confirm `moderation_pending` filter in `NotificationPanelComponent::getNotifications()` (implemented in T022): `moderation_pending` type is excluded from results when `!$this->isGranted('ROLE_MODERATOR')` — write a quick integration smoke test or verify manually with a non-moderator account

**Checkpoint**: All 4 notification types display correctly with distinct visuals per design spec.

---

## Phase 6: User Story 4 — Page Historique (Priority: P3)

**Goal**: `/notifications` page lists all user notifications paginated (20/page), accessible from panel footer, click-to-read works with CSRF.

**Independent Test**: Create 50 notifications for a test user → navigate to `/notifications` → list shows first 20, pagination links present → click notification → marked read → redirected to target.

- [X] T030 [US4] Add `index()` action to `src/Controller/NotificationController.php`: `#[Route('/notifications', name: 'notification_history', methods: ['GET'])]`, `#[IsGranted('ROLE_USER')]`, reads `page` query param (default 1), calls `NotificationRepository::findPaginatedForUser`, passes `notifications`, `currentPage`, `totalPages` to template
- [X] T031 [US4] Create `templates/notification/index.html.twig`: extends base layout, `<h1>Mes notifications</h1>`, loop over paginated notifications with same `.notif-item` CSS classes as panel, each item wrapped in CSRF form POSTing to `notification_mark_read`, pagination controls (prev/next, page numbers)

**Checkpoint**: US4 functional — history page accessible via panel footer link, paginated, click-to-read works.

---

## Phase 7: User Story 5 — Préférences de Notification (Priority: P3)

**Goal**: User can toggle each notification type on/off; disabling a type immediately deletes its existing unread notifications and prevents future creation.

**Independent Test**: Disable `rank_up` preference → trigger `RankUpEvent` → verify no `rank_up` notification row created for that user; re-enable → trigger → verify notification created.

- [X] T032 [US5] Create `templates/profile/_notification_preferences.html.twig` partial: 4 labeled checkboxes (one per `NotificationType`), bound to current `NotificationPreference` entity values, CSRF token `notification_preferences`, submit to `profile_notification_preferences` route, hide `moderation_pending` checkbox for non-ROLE_MODERATOR users
- [X] T033 [US5] Create `src/Controller/ProfileController.php` with `#[Route('/profile/settings', name: 'profile_settings', methods: ['GET'])]` `#[IsGranted('ROLE_USER')]` action: inject `NotificationPreferenceRepository`, load or instantiate `NotificationPreference` for current user, pass to `templates/profile/settings.html.twig`; also create `templates/profile/settings.html.twig` extending base layout — page title "Mes paramètres", section `<section id="notifications">` with heading "Notifications" containing `{% include 'profile/_notification_preferences.html.twig' %}` (no existing profile page — this is the new profile settings entry point)
- [X] T034 [US5] Add `saveNotificationPreferences()` to `src/Controller/ProfileController.php`: `#[Route('/profile/settings/notifications', name: 'profile_notification_preferences', methods: ['POST'])]`, `#[IsGranted('ROLE_USER')]`, validate CSRF, load or create `NotificationPreference` for current user, update 4 boolean fields, for each type toggled OFF call `NotificationService::deleteUnreadByType($user, $type)` synchronously, flush, redirect to profile settings with success flash
- [X] T035 [US5] Create `src/EntityListener/UserCreatedListener.php` with `#[ORM\PostPersist]` on `User` entity: in `postPersist(User $user, PostPersistEventArgs $event)`, persist `new NotificationPreference($user)` using `$event->getObjectManager()` then flush — covers both email (`UserRegistrationService::register()`) and Google OAuth registration flows with zero duplication
- [X] T036 [US5] Create PHPUnit test for preference filtering in `tests/Notification/Service/NotificationPreferenceFilterTest.php`: handler skips insert when preference is disabled for the target type; handler creates notification when preference is enabled

**Checkpoint**: US5 functional — preferences saved, unread notifications deleted on disable, future notifications respect preference.

---

## Phase 8: Event Pipeline — Domain Events & Listeners

**Goal**: Notifications created automatically via Symfony EventDispatcher + Messenger when domain events fire from existing services.

**Independent Test**: Approve a `WorkEntry` in `ModerationService` → `ContributionValidatedEvent` dispatched → Messenger handler processes message → `notification` row inserted for author; if rank changed → second `rank_up` notification also inserted.

### Domain Events

- [X] T037 [P] Create `ContributionValidatedEvent` in `src/Event/ContributionValidatedEvent.php` (`readonly` class, constructor args: `public WorkEntry $workEntry`, `public User $recipient`)
- [X] T038 [P] Create `BookAddedToCollectionEvent` in `src/Event/BookAddedToCollectionEvent.php` (`readonly` class, constructor args: `public Book $book`, `public Collection $collection`, `public bool $isBatch = false`, `public int $batchCount = 1`)
- [X] T039 [P] Create `ModerationPendingEvent` in `src/Event/ModerationPendingEvent.php` (`readonly` class, constructor arg: `public Suggestion $suggestion`)
- [X] T040 [P] Create `RankUpEvent` in `src/Event/RankUpEvent.php` (`readonly` class, constructor args: `public User $user`, `public ContributorLevel $newLevel`)

### Event Listeners

- [X] T041 Create `ContributionValidatedListener` in `src/EventListener/ContributionValidatedListener.php` (#[AsEventListener] for `ContributionValidatedEvent`): check preference for `CONTRIBUTION_VALIDATED`, dispatch `NotificationMessage` for recipient with `sourceId="contribution_validated:{workEntry.id}"`; fetch old rank from `ContributorLevelService::computeRank($recipient)` before dispatch, re-compute after, if changed dispatch `RankUpEvent` via EventDispatcher
- [X] T042 Create `BookAddedToCollectionListener` in `src/EventListener/BookAddedToCollectionListener.php` (#[AsEventListener] for `BookAddedToCollectionEvent`): fetch subscribers via `UserCollectionSubscriptionRepository::findSubscribersByCollection`; for each subscriber check preference for `BOOK_ACTIVITY`; dispatch one `NotificationMessage` per subscriber using singular template (`"{auteur} a publié une nouvelle fiche dans une collection que tu suis ({collection})."`) or batch template (`"La collection {collection} a été enrichie de {N} nouvelles fiches."`) based on `$event->isBatch`; `sourceId="book_activity:{collection.id}:{book.id}"` (or `"book_activity:batch:{collection.id}:{timestamp}"` for batch)
- [X] T043 Create `ModerationPendingListener` in `src/EventListener/ModerationPendingListener.php` (#[AsEventListener] for `ModerationPendingEvent`): fetch all ROLE_MODERATOR users via `UserRepository::findByRole('ROLE_MODERATOR')`; for each check preference for `MODERATION_PENDING`; dispatch one `NotificationMessage` per moderator with `sourceId="moderation_pending:{suggestion.id}:{moderator.id}"`
- [X] T044 Create `RankUpListener` in `src/EventListener/RankUpListener.php` (#[AsEventListener] for `RankUpEvent`): check preference for `RANK_UP`; dispatch `NotificationMessage` for `$event->user` with message `"Félicitations, tu as atteint le niveau {newLevel.label} !"` and `sourceId="rank_up:{user.id}:{newLevel.value}"`

### Dispatch Points in Existing Services

- [X] T045 Locate `ModerationService::approve(WorkEntry)` in `src/Service/ModerationService.php`; inject `EventDispatcherInterface`; add `$this->dispatcher->dispatch(new ContributionValidatedEvent($workEntry, $workEntry->getAuthor()))` call after `$em->flush()` — zero changes to existing logic
- [X] T046 Add `BookAddedToCollectionEvent` dispatch to `ModerationService::approve()` in `src/Service/ModerationService.php` (same file as T045 — sequence after T045, `EventDispatcherInterface` already injected): after the `ContributionValidatedEvent` dispatch, add `if ($entity instanceof WorkEntry && ($book = $entity->getBook()) !== null && ($col = $book->getCollection()) !== null) { $this->dispatcher->dispatch(new BookAddedToCollectionEvent($book, $col)); }` — `Book::setCollection()` is set at submission time; the PUBLISHED state change in `approve()` is the correct dispatch point
- [X] T047 Inject `EventDispatcherInterface` into `SuggestionService::submit()` in `src/Service/SuggestionService.php` (confirmed dispatch point — called from `src/Twig/Components/Suggestion/WizardComponent.php:127`); add `$this->dispatcher->dispatch(new ModerationPendingEvent($suggestion))` after `$em->flush()`

### Listener Tests

- [X] T048 [P] Create PHPUnit test for `ContributionValidatedListener` in `tests/Notification/EventListener/ContributionValidatedListenerTest.php`: dispatches `NotificationMessage` with correct fields, skips if preference disabled, detects rank-up and dispatches `RankUpEvent` when rank changes, does not dispatch rank-up when rank unchanged
- [X] T049 [P] Create PHPUnit test for `BookAddedToCollectionListener` in `tests/Notification/EventListener/BookAddedToCollectionListenerTest.php`: dispatches one message per subscriber, uses singular template for single book, uses batch template when `isBatch=true`, skips subscribers with disabled `book_activity` preference
- [X] T050 [P] Create PHPUnit test for `ModerationPendingListener` in `tests/Notification/EventListener/ModerationPendingListenerTest.php`: dispatches one message per moderator, skips moderators with disabled `moderation_pending` preference, dispatches zero messages if no moderators exist

**Checkpoint**: Full notification pipeline working end-to-end — domain events fire → listeners dispatch Messenger messages → handler creates notification rows in DB.

---

## Phase N: Polish & Cross-Cutting Concerns

- [X] T051 [P] Add skeleton loader to `templates/components/notification/notification_panel_component.html.twig`: `data-loading="addClass(skeleton)"` on `.notif-list`, add matching `.skeleton` CSS placeholder rows (3 ghost items)
- [X] T052 [P] Add Live Component error handling in `templates/components/notification/notification_panel_component.html.twig`: on `connect:error` browser event dispatch existing toast mechanism `{message: "Impossible de charger les notifications.", type: "error"}` via JS; ensure empty state is shown in panel body
- [X] T053 Verify null `targetUrl` edge case in `NotificationController::markRead` (T025): redirect to `/` with flash `"Cette notification n'a plus de cible."` — smoke test manually with a seeded notification with `targetUrl=null`
- [X] T054 Verify 500-notification pruning in `NotificationMessageHandler` (T014): write a manual seed scenario or add to T020 test suite — insert 501 notifications, trigger handler, verify count returns to 500
- [ ] T055 Run `quickstart.md` validation: `composer install` → migration → `messenger:setup-transports` → seed notifications → verify badge on page load → open panel → click read → open preferences → disable type → verify deletion → re-enable → verify creation

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately. T002 and T003 MUST be committed together (Constitution II)
- **Foundational (Phase 2)**: Depends on Phase 1 completion — **BLOCKS all user stories**. T056/T057 (subscription UI) must be in Phase 2 since UserCollectionSubscription entity (T007) must exist first
- **US1 (Phase 3)**: Depends on Phase 2 — first story, no story dependencies
- **US2 (Phase 4)**: Depends on Phase 3 (needs NotificationPanelComponent from US1)
- **US3 (Phase 5)**: Depends on Phase 3 (extends same template)
- **US4 (Phase 6)**: Depends on Phase 2 only — can run in parallel with US1/US2/US3
- **US5 (Phase 7)**: Depends on Phase 2 only — can run in parallel with US1/US2/US3/US4
- **Event Pipeline (Phase 8)**: Depends on Phase 2 — domain events and listeners can be written in parallel with US1–US5 phases
- **Polish (Phase N)**: Depends on all story phases complete

### User Story Dependencies

- **US1 (P1)**: Only depends on Foundational (Phase 2)
- **US2 (P2)**: Depends on US1 (reuses NotificationPanelComponent)
- **US3 (P2)**: Depends on US1 (extends same panel template)
- **US4 (P3)**: Depends on Foundational only — fully independent of US1–US3
- **US5 (P3)**: Depends on Foundational only — fully independent of US1–US4

### Within Each User Story

- Models and repositories before services
- Services before component/controller
- Component/controller before template
- Commit after each task or logical group

### Parallel Opportunities

- T004, T005, T006, T007 — all entity files, no shared dependencies
- T009, T010, T011, T012 — all repository/DTO files after T004–T007
- T017, T018 — entity tests can run in parallel
- T037, T038, T039, T040 — all domain event files
- T041, T042, T043, T044 — listener files (after events done); **T045 and T046 both modify `ModerationService.php` — run sequentially, not in parallel**
- T048, T049, T050 — listener test files
- US4 (Phase 6) + US5 (Phase 7) can run in parallel with US2 (Phase 4) + US3 (Phase 5)
- Event Pipeline (Phase 8) can overlap with US1–US5 phases

---

## Parallel Example: Foundational Phase

```bash
# These tasks can run simultaneously (different files):
Task T004: "Create NotificationType enum in src/Entity/Enum/NotificationType.php"
Task T005: "Create Notification entity in src/Entity/Notification.php"
Task T006: "Create NotificationPreference entity in src/Entity/NotificationPreference.php"
Task T007: "Create UserCollectionSubscription entity in src/Entity/UserCollectionSubscription.php"
Task T009: "Create NotificationMessage DTO in src/Messenger/Message/NotificationMessage.php"
```

## Parallel Example: Event Pipeline Phase

```bash
# These tasks can run simultaneously:
Task T037: "Create ContributionValidatedEvent in src/Event/ContributionValidatedEvent.php"
Task T038: "Create BookAddedToCollectionEvent in src/Event/BookAddedToCollectionEvent.php"
Task T039: "Create ModerationPendingEvent in src/Event/ModerationPendingEvent.php"
Task T040: "Create RankUpEvent in src/Event/RankUpEvent.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (Messenger install)
2. Complete Phase 2: Foundational (data layer, migration)
3. Complete Phase 3: User Story 1 (badge + panel + markRead)
4. **STOP and VALIDATE**: seed notifications, verify badge + panel end-to-end
5. Demo if ready

### Incremental Delivery

1. Phase 1 + 2 → Foundation ready (data layer + Messenger)
2. Phase 3 (US1) → Badge + panel working → validate → deploy
3. Phase 4 (US2) + Phase 5 (US3) in parallel → richer panel UX → deploy
4. Phase 6 (US4) + Phase 7 (US5) in parallel → history + preferences → deploy
5. Phase 8 (Event Pipeline) → automatic notification creation → full system live

### Parallel Team Strategy

With multiple developers, once Phase 2 is complete:
- Developer A: US1 (Phase 3) → US2 (Phase 4) → US3 (Phase 5)
- Developer B: US4 (Phase 6) + US5 (Phase 7)
- Developer C: Event Pipeline (Phase 8)

---

## Notes

- [P] = different files, no incomplete task dependencies — safe to parallelize
- [USn] = maps task to specific user story for traceability
- Tests are **required** per Constitution Principle V (entities, handler, repository, listeners)
- Commit after each task or logical group of tasks
- Stop at each Checkpoint to validate the story independently before proceeding
- Phase 8 (Event Pipeline) requires reading existing service code (ModerationService, CollectionService, SuggestionService) to find dispatch points — do not modify existing logic, only inject EventDispatcher and add dispatch call
