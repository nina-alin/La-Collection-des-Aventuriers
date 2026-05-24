---

description: "Task list for RBAC — Roles & Permissions implementation"
---

# Tasks: RBAC — Roles & Permissions

**Input**: Design documents from `/specs/004-rbac-roles-permissions/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/http-routes.md ✅, quickstart.md ✅

**Tests**: Included — Constitution V mandates PHPUnit coverage for all entities, services, and moderation workflows.

**Organization**: Tasks grouped by user story. Each phase is independently testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no shared dependencies)
- **[Story]**: User story label (US1–US5 from spec.md)
- Exact file paths included in every task description

---

## Phase 1: Setup (Security Configuration)

**Purpose**: Apply role hierarchy and access control rules to `security.yaml`. No code required — pure configuration. Must complete before any user story work.

- [X] T001 Add `role_hierarchy` block to `config/packages/security.yaml`: `ROLE_ADMIN: [ROLE_MODERATOR]`, `ROLE_MODERATOR: [ROLE_USER]`
- [X] T002 Add `access_control` rules to `config/packages/security.yaml` before the catch-all rule: `{ path: ^/moderation, roles: ROLE_MODERATOR }` and `{ path: ^/admin, roles: ROLE_ADMIN }`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Entities, repositories, and migration that ALL user stories depend on. No user story work starts until this phase is complete.

**⚠️ CRITICAL**: Phases 3–7 are blocked until this phase is complete.

- [X] T003 Extend `src/Entity/User.php`: add `status` field (`string(10)`, NOT NULL, default `'active'`) and `deletedAt` field (`datetime_immutable`, nullable) with Doctrine ORM attributes; expose getters/setters
- [X] T004 [P] Create `src/Entity/WorkEntry.php`: UUID primary key (Uuid::v4()), `title` (string 255, NOT NULL), `status` (string 10, NOT NULL, default `'PENDING'`), `author` (ManyToOne User nullable, no cascade), `createdAt` (datetime_immutable, set in constructor); add ORM Table/Entity attributes
- [X] T005 [P] Create `src/Entity/ModerationLog.php`: UUID primary key (Uuid::v4()), `moderatorId` (string 36, NOT NULL), `actionType` (string 10, NOT NULL), `targetEntityType` (string 100, NOT NULL), `targetEntityId` (string 36, NOT NULL), `reason` (text, nullable), `createdAt` (datetime_immutable, set in constructor, no setter); add `#[ORM\PreUpdate]` callback throwing `\LogicException('ModerationLog is append-only')` and `#[ORM\PreRemove]` callback throwing same; no FK constraints
- [X] T006 Create `src/Entity/CorrectionProposal.php`: UUID primary key, `workEntry` (ManyToOne WorkEntry, NOT NULL, no cascade), `proposedContent` (json, NOT NULL), `status` (string 10, NOT NULL, default `'PENDING'`), `author` (ManyToOne User nullable, no cascade), `createdAt` (datetime_immutable, set in constructor)
- [X] T007 [P] Create `src/Repository/WorkEntryRepository.php` extending `ServiceEntityRepository`: implement `findPending(): array` returning all WorkEntry where `status = 'PENDING'` ordered by `createdAt ASC`
- [X] T008 [P] Create `src/Repository/CorrectionProposalRepository.php` extending `ServiceEntityRepository`: implement `findPending(): array` returning all CorrectionProposal where `status = 'PENDING'` ordered by `createdAt ASC`
- [X] T009 Extend `src/Repository/UserRepository.php`: override `loadUserByIdentifier()` to throw `UsernameNotFoundException` when `deletedAt IS NOT NULL`; add `countActiveAdministrators(): int` using native SQL with PostgreSQL JSONB `roles::jsonb @> '["ROLE_ADMIN"]'::jsonb` where `status='active' AND deleted_at IS NULL`; add `countAccountsWithModerationCapability(): int` counting accounts where roles contain `ROLE_ADMIN` or `ROLE_MODERATOR` and account is active and not deleted
- [X] T010 Create Doctrine migration `migrations/Version20260524000000.php`: (1) `ALTER TABLE "user" ADD status VARCHAR(10) NOT NULL DEFAULT 'active'`, (2) `ALTER TABLE "user" ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL` with DC2Type comment, (3) `CREATE TABLE work_entry` with all fields from data-model.md, (4) `CREATE TABLE correction_proposal` with all fields, FK to work_entry and user, (5) `CREATE TABLE moderation_log` with all fields (no FK constraints), (6) add FK constraints for work_entry.author_id and correction_proposal.author_id referencing user.id (no ON DELETE)

**Checkpoint**: Run `docker compose exec php bin/console doctrine:migrations:migrate --no-interaction` — migration must apply cleanly. Run `bin/phpunit` — zero errors expected at this stage.

---

## Phase 3: User Story 1 — Role-Based Access Enforcement (Priority: P1) 🎯 MVP

**Goal**: Block ROLE_USER from moderation/admin routes (403), redirect unauthenticated to login, block banned users on every request, block soft-deleted users from authenticating.

**Independent Test**: Create three accounts (ROLE_USER, ROLE_MODERATOR, ROLE_ADMIN). Hit `/moderation` with each. Verify ROLE_USER → 403, ROLE_MODERATOR → 200, ROLE_ADMIN → 200, unauthenticated → redirect to login.

### Tests for User Story 1

- [X] T011 [P] [US1] Create `tests/Unit/Entity/UserTest.php`: test `status` defaults to `'active'`, test `setStatus()` stores `'banned'`, test `deletedAt` is nullable, test `setDeletedAt()` stores value; extend existing test class if one exists
- [X] T012 [P] [US1] Create `tests/Integration/Security/BannedUserTest.php`: test banned user on authenticated GET `/moderation` receives 403 response; test soft-deleted user cannot authenticate (UserProvider throws); test active ROLE_MODERATOR reaches `/moderation` with 200

### Implementation for User Story 1

- [X] T013 [US1] Create `src/Security/UserChecker.php` implementing `UserCheckerInterface`: `checkPreAuth()` throws `CustomUserMessageAccountStatusException('Compte supprimé.')` when `$user->getDeletedAt() !== null`; `checkPostAuth()` throws `CustomUserMessageAccountStatusException('Compte suspendu.')` when `$user->getStatus() === 'banned'`
- [X] T014 [US1] Register `App\Security\UserChecker` in `config/packages/security.yaml` under `firewalls.main.user_checker: App\Security\UserChecker`
- [X] T015 [US1] Create `src/EventSubscriber/BannedUserSubscriber.php` implementing `EventSubscriberInterface`: subscribe to `KernelEvents::REQUEST` with priority 7; skip sub-requests and non-User tokens; if `$token->getUser()` is a User instance and `status === 'banned'`, set `$event->setResponse(new Response('Accès refusé.', 403))`

**Checkpoint**: `bin/phpunit tests/Integration/Security/BannedUserTest.php` passes. Manual test per quickstart §2 works for all role levels.

---

## Phase 4: User Story 2 — Conditional Navigation Rendering (Priority: P2)

**Goal**: Navbar shows moderation link for ROLE_MODERATOR+, admin links for ROLE_ADMIN only, nothing extra for ROLE_USER.

**Independent Test**: Log in with each role; inspect nav bar. ROLE_USER: collection links only. ROLE_MODERATOR: + moderation link. ROLE_ADMIN: + admin links.

### Tests for User Story 2

- [X] T016 [P] [US2] Create `tests/Integration/Controller/NavbarRenderingTest.php`: crawl home page as ROLE_USER and assert moderation link absent; as ROLE_MODERATOR assert moderation link present; as ROLE_ADMIN assert both moderation and admin links present

### Implementation for User Story 2

- [X] T017 [US2] Update `templates/components/Layout/Navbar.html.twig`: wrap moderation dashboard link in `{% if is_granted('ROLE_MODERATOR') %}...{% endif %}`; wrap administration links in `{% if is_granted('ROLE_ADMIN') %}...{% endif %}`; personal collection links remain unconditional for authenticated users

**Checkpoint**: `bin/phpunit tests/Integration/Controller/NavbarRenderingTest.php` passes. Visual check per quickstart §3 for all three roles.

---

## Phase 5: User Story 3 — Forced PENDING Status on User Submissions (Priority: P2)

**Goal**: Any WorkEntry or CorrectionProposal submitted via `POST /work-entries` or `POST /work-entries/{id}/corrections` is stored as PENDING regardless of request payload. Enforcement occurs at entity level (default status) and is verified end-to-end via integration test.

**Independent Test**: POST to `/work-entries` as ROLE_USER including a `status: PUBLISHED` field in the body — assert persisted WorkEntry has `status = PENDING`. POST to `/work-entries/{id}/corrections` — assert persisted CorrectionProposal has `status = PENDING`. Verify ModerationService throws `\InvalidArgumentException` when transitioning a non-PENDING entity.

### Tests for User Story 3

- [X] T018 [P] [US3] Create `tests/Unit/Entity/WorkEntryTest.php`: assert status defaults to `'PENDING'` on construction with no status argument; assert `PENDING → PUBLISHED` and `PENDING → REJECTED` transitions succeed; assert attempting a second transition from `PUBLISHED` or `REJECTED` throws `\InvalidArgumentException`
- [X] T019 [P] [US3] Create `tests/Unit/Entity/CorrectionProposalTest.php`: same assertions as WorkEntryTest — PENDING default, allowed transitions, terminal state enforcement
- [X] T033 [P] [US3] Create `tests/Integration/Controller/WorkEntrySubmissionTest.php`: test POST `/work-entries` as authenticated ROLE_USER with `title` and `status: PUBLISHED` in body → 302 redirect and DB record has `status = PENDING`; test POST `/work-entries` with invalid CSRF → 403; test POST `/work-entries` unauthenticated → redirect to login; test POST `/work-entries/{id}/corrections` as authenticated ROLE_USER with `proposedContent` → 302 redirect and DB record has `status = PENDING`

### Implementation for User Story 3

- [X] T034 [US3] Create `src/Controller/WorkEntryController.php`: annotate with `#[Route('/work-entries')]` and `#[IsGranted('IS_AUTHENTICATED_FULLY')]`; implement `submit()` POST action with CSRF token `work_entry_submit` — read `title` from request, instantiate WorkEntry (entity default forces `status = PENDING`), persist via EntityManager, add flash success, redirect to `/`; implement `submitCorrection(string $id)` POST action on `/work-entries/{id}/corrections` with CSRF token `correction_submit_{$id}` — fetch WorkEntry by id (throw 404 if not found), read `proposedContent` from request, instantiate CorrectionProposal linked to WorkEntry (entity default forces `status = PENDING`), persist, add flash success, redirect to `/`; no `status` field read from request in either action (FR-005)

**Checkpoint**: `bin/phpunit tests/Unit/Entity/WorkEntryTest.php tests/Unit/Entity/CorrectionProposalTest.php tests/Integration/Controller/WorkEntrySubmissionTest.php` passes. Entity default status is PENDING and POST routes enforce it end-to-end.

---

## Phase 6: User Story 4 — Moderation Dashboard (Priority: P2)

**Goal**: ROLE_MODERATOR accesses `/moderation` dashboard, sees all PENDING submissions, can approve/reject/edit each with ModerationLog audit entry created.

**Independent Test**: Log in as ROLE_MODERATOR, open `/moderation`, approve one WorkEntry and reject one with reason. Verify status changes in DB and two rows in `moderation_log`.

### Tests for User Story 4

- [X] T020 [P] [US4] Create `tests/Unit/Entity/ModerationLogTest.php`: verify `createdAt` set in constructor and immutable (no setter); verify `PreUpdate` callback throws `\LogicException`; verify `PreRemove` callback throws `\LogicException`
- [X] T021 [P] [US4] Create `tests/Unit/Service/ModerationServiceTest.php`: test `approve()` transitions WorkEntry to PUBLISHED and persists ModerationLog with actionType APPROVED; test `reject()` transitions to REJECTED and stores nullable reason in log; test `editPendingWorkEntry()` updates title and logs MODIFIED; test `editPendingCorrection()` updates proposedContent and logs MODIFIED; test all methods throw `\InvalidArgumentException` on non-PENDING entity
- [X] T022 [P] [US4] Create `tests/Integration/Controller/ModerationControllerTest.php`: test GET `/moderation` as ROLE_MODERATOR returns 200 with PENDING items listed; test POST approve with valid CSRF returns redirect and status becomes PUBLISHED; test POST reject with reason persists reason in moderation_log; test POST with invalid CSRF returns 403; test ROLE_USER on GET `/moderation` returns 403

### Implementation for User Story 4

- [X] T023 [US4] Create `src/Service/ModerationService.php`: inject `EntityManagerInterface`; implement `approve(WorkEntry|CorrectionProposal $entity, string $moderatorId): void` — validate status is PENDING (throw `\InvalidArgumentException` if not), set status to PUBLISHED, create and persist ModerationLog with actionType APPROVED; implement `reject(WorkEntry|CorrectionProposal $entity, string $moderatorId, ?string $reason): void` — same guard, set REJECTED, log with actionType REJECTED and nullable reason; implement `editPendingWorkEntry(WorkEntry $entity, string $title, string $moderatorId): void` — validate PENDING, update title, log actionType MODIFIED; implement `editPendingCorrection(CorrectionProposal $entity, string $proposedContent, string $moderatorId): void` — validate PENDING, update proposedContent, log actionType MODIFIED; call `$this->entityManager->flush()` once at end of each method
- [X] T024 [US4] Create `src/Controller/ModerationController.php`: annotate with `#[Route('/moderation')]` and `#[IsGranted('ROLE_MODERATOR')]`; implement `index()` action fetching `WorkEntryRepository::findPending()` and `CorrectionProposalRepository::findPending()`, render `moderation/dashboard.html.twig`; implement `approveWorkEntry(string $id)`, `rejectWorkEntry(string $id)` POST actions validating CSRF `moderate_{$id}`, calling `ModerationService`, flash + redirect to `/moderation`; implement `editWorkEntry(string $id)` POST action reading `title`, calling `ModerationService::editPendingWorkEntry()`; implement `approveCorrectionProposal(string $id)`, `rejectCorrectionProposal(string $id)`, `editCorrectionProposal(string $id)` POST actions mirroring WorkEntry actions — `editCorrectionProposal` reads `proposedContent` and calls `ModerationService::editPendingCorrection()`; all POST actions use CSRF `moderate_{$id}` and redirect to `/moderation`
- [X] T025 [US4] Create `templates/moderation/dashboard.html.twig`: extends base layout; list PENDING WorkEntry and CorrectionProposal in separate sections; each row shows title/type, author pseudo, createdAt; include approve button (POST form with CSRF `moderate_{id}`), reject button (POST form with optional reason textarea and CSRF), edit button (POST form with title input and CSRF)

**Checkpoint**: `bin/phpunit tests/Unit/Service/ModerationServiceTest.php tests/Integration/Controller/ModerationControllerTest.php` passes. Manual walkthrough per quickstart §5 completes without error.

---

## Phase 7: User Story 5 — Administrator User Management (Priority: P3)

**Goal**: ROLE_ADMIN manages users: promote/demote role, ban account, soft-delete account. Guards block self-action, last-admin removal, last-moderator removal.

**Independent Test**: Log in as ROLE_ADMIN, promote ROLE_USER to ROLE_MODERATOR — promoted user accesses `/moderation` without re-login. Ban a different user — banned user cannot authenticate. Test self-ban and last-admin demotion are rejected.

### Tests for User Story 5

- [X] T026 [P] [US5] Create `tests/Unit/Service/UserManagementServiceTest.php`: test `changeRole()` throws when actor === target (FR-014); test `banUser()` throws when actor === target; test `softDeleteUser()` throws when actor === target; test `changeRole()` demoting last admin throws (FR-012); test `banUser()` on last admin throws; test `softDeleteUser()` on last admin throws; test `changeRole()` demoting last ROLE_MODERATOR when no ROLE_ADMIN exists throws (FR-015); test `softDeleteUser()` sets deletedAt, sets email=`[deleted]`, sets displayName=`[deleted]`, nullifies WorkEntry author FK, nullifies CorrectionProposal author FK; test `changeRole()` replaces roles array with single-element array (FR-016)
- [X] T027 [P] [US5] Create `tests/Integration/Controller/AdminControllerTest.php`: test GET `/admin/users` as ROLE_ADMIN returns 200 with user list; test POST `/admin/users/{id}/role` with valid CSRF and valid role returns redirect with flash success; test self-role-change returns flash error; test last-admin demotion returns flash error; test POST `/admin/users/{id}/ban` with valid CSRF bans user; test POST `/admin/users/{id}/delete` with valid CSRF soft-deletes user; test GET `/admin/settings` returns 200 with JSON `{"message": "Settings UI coming soon"}`; test all routes return 403 for ROLE_MODERATOR

### Implementation for User Story 5

- [X] T028 [US5] Create `src/Service/UserManagementService.php`: inject `EntityManagerInterface`, `UserRepository`; implement `changeRole(User $actor, User $target, string $newRole): void` — guard self-action (FR-014: throw if `$actor === $target`), guard last-admin (FR-012: if demoting admin and `countActiveAdministrators() <= 1` throw), guard last-moderator (FR-015: if demoting ROLE_MODERATOR and `countAccountsWithModerationCapability() <= 1` and no ROLE_ADMIN exists throw), call `$target->setRoles([$newRole])`, flush; implement `banUser(User $actor, User $target): void` — self-action guard, last-admin guard, set `$target->setStatus('banned')`, flush; implement `softDeleteUser(User $actor, User $target): void` — self-action guard, last-admin guard, bulk UPDATE `work_entry SET author_id = NULL WHERE author_id = :id`, bulk UPDATE `correction_proposal SET author_id = NULL WHERE author_id = :id` via native queries, then `$target->setEmail('[deleted]')`, `$target->setDisplayName('[deleted]')`, `$target->setDeletedAt(new \DateTimeImmutable())`, flush
- [X] T029 [US5] Create `src/Controller/AdminController.php`: annotate with `#[Route('/admin')]` and `#[IsGranted('ROLE_ADMIN')]`; implement `users()` GET action fetching all non-deleted users from UserRepository, render `admin/users.html.twig`; implement `changeRole(string $id)` POST action with CSRF `admin_user_{$id}`, read `role` from request, call `UserManagementService::changeRole()`, catch exceptions and flash error, redirect to `/admin/users`; implement `ban(string $id)` and `delete(string $id)` POST actions similarly; implement `settings()` GET action returning `JsonResponse(['message' => 'Settings UI coming soon'])`
- [X] T030 [US5] Create `templates/admin/users.html.twig`: extends base layout; table listing non-deleted users with columns: display name, email, role badge (color-coded), status badge (active/banned), action column; action column has three POST forms — role dropdown + submit (CSRF `admin_user_{id}`), ban button (CSRF), delete button (CSRF); exclude currently-logged-in user's self-action buttons or show them as disabled

**Checkpoint**: `bin/phpunit tests/Unit/Service/UserManagementServiceTest.php tests/Integration/Controller/AdminControllerTest.php` passes. Manual walkthrough per quickstart §6 and §7 completes without error.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Full test suite validation and quickstart walkthrough.

- [X] T031 [P] Run full PHPUnit test suite and confirm all tests pass: `docker compose exec php bin/phpunit` — all tests green, no errors or failures
- [ ] T032 Run quickstart.md validation for all five user stories: execute each scenario in quickstart.md §2–§7 manually against local Docker environment and confirm all expected outcomes

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 completion — BLOCKS all user stories
- **US1 (Phase 3)**: Depends on Phase 2 — no inter-story dependencies
- **US2 (Phase 4)**: Depends on Phase 2 — no inter-story dependencies; can run in parallel with US1
- **US3 (Phase 5)**: Depends on Phase 2 (T004, T006 entities) — no inter-story dependencies; can run in parallel with US1, US2
- **US4 (Phase 6)**: Depends on Phase 2 + US3 (entity defaults, status machine, and T034 WorkEntryController are prerequisites) — can run after Phase 5
- **US5 (Phase 7)**: Depends on Phase 2 — can run in parallel with US1, US2, US3; independent of US4
- **Polish (Phase 8)**: Depends on all prior phases complete

### User Story Dependencies

- **US1 (P1)**: After Phase 2 — independent
- **US2 (P2)**: After Phase 2 — independent
- **US3 (P2)**: After Phase 2 — independent
- **US4 (P2)**: After Phase 2 + Phase 5 (US3 entity state machine + T034 WorkEntryController in place)
- **US5 (P3)**: After Phase 2 — independent of US1–US4

### Within Each User Story

- Entity tasks before service tasks
- Service tasks before controller tasks
- Controller tasks before template tasks
- Tests and implementation may run in parallel (tests in different files)

### Parallel Opportunities

- T004 [P] and T005 [P]: WorkEntry entity + ModerationLog entity (different files, no deps on each other)
- T007 [P] and T008 [P]: WorkEntryRepository + CorrectionProposalRepository
- T011 [P] and T012 [P]: UserTest + BannedUserTest
- T018 [P], T019 [P], T033 [P]: WorkEntryTest + CorrectionProposalTest + WorkEntrySubmissionTest
- T020 [P], T021 [P], T022 [P]: All US4 test files
- T026 [P] and T027 [P]: All US5 test files
- T031 [P]: Full test suite (while T032 runs manual checks)

---

## Parallel Example: Foundational Phase

```bash
# Launch together (different files, no deps on each other):
Task T004: Create src/Entity/WorkEntry.php
Task T005: Create src/Entity/ModerationLog.php

# After T004 completes:
Task T006: Create src/Entity/CorrectionProposal.php
# In parallel:
Task T007: Create src/Repository/WorkEntryRepository.php
Task T008: Create src/Repository/CorrectionProposalRepository.php
```

## Parallel Example: User Story 4

```bash
# Launch all test files together:
Task T020: Create tests/Unit/Entity/ModerationLogTest.php
Task T021: Create tests/Unit/Service/ModerationServiceTest.php
Task T022: Create tests/Integration/Controller/ModerationControllerTest.php

# Then implement sequentially:
Task T023: Create src/Service/ModerationService.php
Task T024: Create src/Controller/ModerationController.php
Task T025: Create templates/moderation/dashboard.html.twig
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001–T002)
2. Complete Phase 2: Foundational (T003–T010)
3. Complete Phase 3: User Story 1 (T011–T015)
4. **STOP and VALIDATE**: All US1 tests pass, manual quickstart §2 verified
5. Deploy/demo if ready

### Incremental Delivery

1. Phase 1 + Phase 2 → Foundation ready
2. Phase 3 (US1) → RBAC enforced — MVP deliverable
3. Phase 4 (US2) → Nav updated
4. Phase 5 (US3) → PENDING enforced at entity level
5. Phase 6 (US4) → Moderation workflow live
6. Phase 7 (US5) → Admin user management live
7. Phase 8 → Full test suite green

### Parallel Team Strategy

With multiple developers (after Phase 1 + Phase 2 complete):
- **Dev A**: US1 (Phase 3) + US2 (Phase 4)
- **Dev B**: US3 (Phase 5) + US4 (Phase 6)
- **Dev C**: US5 (Phase 7)

---

## Notes

- `[P]` = different files, no shared in-flight dependencies — safe to parallelize
- `[Story]` maps task to spec.md user story for traceability
- All POST routes MUST include CSRF validation and `#[IsGranted]` (Constitution IV)
- Controllers MUST be thin — business logic stays in ModerationService and UserManagementService (Constitution II)
- ModerationLog is append-only: never call `remove()` on a log entity; lifecycle callbacks enforce this at ORM level
- `UserManagementService::softDeleteUser()` must nullify author FKs BEFORE anonymizing email/displayName — order matters for auditability
- Native SQL JSONB queries in UserRepository are PostgreSQL-specific by design (Decision 4 in research.md)
