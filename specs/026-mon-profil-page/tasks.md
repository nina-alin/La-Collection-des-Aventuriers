# Tasks: Mon Profil — Tableau de Bord Utilisateur

**Feature**: `026-mon-profil-page`
**Input**: Design documents from `/specs/026-mon-profil-page/`

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story (US1–US7, maps to spec.md)
- Exact file paths in all descriptions

---

## Phase 1: Setup

**Purpose**: Prepare upload directory for avatar storage

- [X] T001 Create avatar upload directory placeholder at `public/uploads/avatars/.gitkeep`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: All DB schema changes, new entities, new repositories — MUST complete before any user story

**⚠️ CRITICAL**: No user story work begins until this phase is complete

- [X] T002 Add `loginStreak` (int, default 0), `lastLoginDate` (?date), `pendingEmail` (?string 180), `emailChangeToken` (?string 64), `emailTokenExpiresAt` (?datetime), `deletedAt` (?datetime) fields to `src/Entity/User.php`
- [X] T003 [P] Make `ModerationLog::$moderatorId` nullable (`?string`) in `src/Entity/ModerationLog.php`
- [X] T004 [P] Create `UserListType` backed enum (`Collection='collection'`, `ToRead='to_read'`, `ToBuy='to_buy'`, `Favorites='favorites'`) in `src/Entity/Enum/UserListType.php`
- [X] T005 [P] Create `GhostUser` value class with `GHOST_EMAIL = 'ghost@deleted.local'` and `GHOST_UUID` constants in `src/Entity/GhostUser.php`
- [X] T006 Create `UserListVisibility` entity (id int PK, user FK→User ON DELETE CASCADE, listType UserListType enum, isPublic bool default false) with `#[UniqueConstraint(columns: ['user_id', 'list_type'])]` in `src/Entity/UserListVisibility.php` — requires T004
- [X] T007 [P] Create `UserContributorSubscription` entity (id int PK, user FK→User ON DELETE CASCADE, contributor FK→Contributor ON DELETE CASCADE, subscribedAt datetime_immutable default now) with `#[UniqueConstraint(columns: ['user_id', 'contributor_id'])]` in `src/Entity/UserContributorSubscription.php`
- [X] T008 Create `UserListVisibilityRepository` with `findByUserAndType(User, UserListType): ?UserListVisibility` and `findAllByUser(User): array` (keyed by list type string) in `src/Repository/UserListVisibilityRepository.php` — requires T006
- [X] T009 [P] Create `UserContributorSubscriptionRepository` with `findByUserAndContributor(User, Contributor): ?UserContributorSubscription` and `findFollowedByUser(User): array` in `src/Repository/UserContributorSubscriptionRepository.php` — requires T007
- [X] T043 [P] Add `findFollowedByUser(User): array` and `findByUserAndCollection(User, Collection): ?UserCollectionSubscription` methods to pre-existing `UserCollectionSubscriptionRepository` in `src/Repository/UserCollectionSubscriptionRepository.php` — entity and repo already exist; only new query methods required
- [X] T010 Create migration M1 adding `login_streak`, `last_login_date`, `pending_email`, `email_change_token`, `email_token_expires_at`, `deleted_at` (TIMESTAMP nullable) columns to `user` table in `migrations/`
- [X] T011 [P] Create migration M2 dropping NOT NULL constraint on `moderation_log.moderator_id` in `migrations/`
- [X] T012 [P] Create migration M3 creating `user_list_visibility` table with `UNIQUE(user_id, list_type)` in `migrations/`
- [X] T013 [P] Create migration M4 creating `user_contributor_subscription` table with `UNIQUE(user_id, contributor_id)` in `migrations/`
- [X] T014 Create migration M5 inserting GhostUser row (`ghost@deleted.local`, pseudo `ancien-aventurier`, displayName `un ancien aventurier`, roles `["ROLE_USER"]`, status `active`, isEmailVerified `true`, deletedAt `null`) into `user` table — must execute after M1 in `migrations/`

**Checkpoint**: All entities, repositories, and migrations ready — user story phases can begin

---

## Phase 3: User Story 1 — Tableau de Bord Profil (Priority: P1) 🎯 MVP

**Goal**: Private dashboard at `/profil` showing user header (avatar, pseudo, role badge, registration date, guild) and 4 KPI cards (collection stats, ratings, validated suggestions, login streak)

**Independent Test**: Log in, navigate to `/profil` — header shows avatar/pseudo/badge/date/guild, 4 KPI cards render with values consistent with account data

- [X] T044 [P] [US1] Create `ProfileKpiService` encapsulating all KPI business logic: (1) `getBookStats(User): array` — total UserBook count + en-cours/terminés split + monthly add trend (current month vs previous); (2) `getRatingStats(User): array` — Review count + average rating given; (3) `getSuggestionStats(User): array` — Suggestion count WHERE status=VALIDATED + acceptance rate (VALIDATED / (VALIDATED+REJECTED), PENDING excluded); (4) `getStreakStats(User): array` — loginStreak + lastLoginDate from User in `src/Service/ProfileKpiService.php`
- [X] T015 [P] [US1] Create `LoginStreakService` with timezone-aware streak logic: resolve "today" via `User.timezone` (fallback UTC); if `lastLoginDate` = null → streak=1 + set today; if `lastLoginDate` = yesterday → streak++; if `lastLoginDate` < yesterday → streak=1; if `lastLoginDate` = today → no-op in `src/Service/LoginStreakService.php`
- [X] T016 [US1] Create `LoginStreakListener` subscribing to Symfony `LoginSuccessEvent`, calling `LoginStreakService::update(User)`, tag as `kernel.event_listener` in `services.yaml` in `src/EventListener/LoginStreakListener.php`
- [X] T017 [P] [US1] Write `LoginStreakServiceTest` covering: first login (streak=1), yesterday→increment, before-yesterday→reset to 1, same-day no-op, timezone handling (non-UTC user) in `tests/Service/LoginStreakServiceTest.php`
- [X] T018 [US1] Add `GET /profil` route (`profile_dashboard`, `#[IsGranted('ROLE_USER')]`) to `ProfileController`; inject `ProfileKpiService` (T044) and call its four methods; pass KPI arrays to template — no business logic in controller in `src/Controller/ProfileController.php`
- [X] T019 [US1] Create `templates/profile/dashboard.html.twig` with user header (avatar, pseudo, role badge for ROLE_MODERATOR/ROLE_ADMIN, absolute+relative registration date, guild link, Taverne external URL) and 4 KPI cards per `design/pages/profil.html`

**Checkpoint**: US1 complete — `/profil` renders header and KPI data for all role types

---

## Phase 4: User Story 7 — Suppression de Compte (Priority: P1)

**Goal**: RGPD-compliant soft-delete with PII anonymisation, GhostUser content reassignment, ModerationLog entry, session invalidation

**Independent Test**: Click "Supprimer mon compte" → type "SUPPRIMER" in modal → confirm → session ends, redirect to `/`, user row anonymised in DB (`email=[deleted]-{uuid}`), validated suggestions now owned by `ghost@deleted.local`

- [X] T020 [P] [US7] Create `AccountDeletionService`: (1) GhostUser guard — reject if `user.email === GhostUser::GHOST_EMAIL`; (2) reassign `Suggestion` (status=VALIDATED) author FK to GhostUser; (3) reassign `CorrectionProposal` (status=PUBLISHED) author FK to GhostUser; (4) delete `UserBook` rows for user; (5) delete `Review` rows for user; (6) anonymise User: email→`[deleted]-{uuid}`, pseudo→`[deleted]-{uuid}`, displayName→`[deleted]`, avatarUrl→null, googleId→null, pendingEmail/emailChangeToken/emailTokenExpiresAt/password→null, deletedAt→now(); (7) log `ModerationLog` (action=`ACCOUNT_DELETED`, moderatorId=null, targetEntityType=`User`, targetEntityId=user UUID) in `src/Service/AccountDeletionService.php`
- [X] T021 [P] [US7] Write `AccountDeletionServiceTest`: GhostUser guard throws exception, Suggestion VALIDATED reassigned, CorrectionProposal PUBLISHED reassigned, User fields anonymised with `[deleted]-{uuid}` pattern, ModerationLog entry created with null moderatorId, UserBook/Review rows deleted in `tests/Service/AccountDeletionServiceTest.php`
- [X] T022 [US7] Add `POST /profil/delete-account` route (`profile_delete_account`, CSRF token `delete_account`, reject if confirmation !== `"SUPPRIMER"` exact case, call `AccountDeletionService`, call `$tokenStorage->setToken(null)` + `$session->invalidate()`, redirect to `/` with success flash) in `src/Controller/ProfileController.php`
- [X] T023 [US7] Add "Zone de Danger" section to `templates/profile/dashboard.html.twig`: "Supprimer mon compte" button opens Bootstrap modal with text input; JS enables confirm button only when input === `"SUPPRIMER"` exactly; form posts to `profile_delete_account` with CSRF token

**Checkpoint**: US7 complete — account deletion flow RGPD-compliant

---

## Phase 5: User Story 2 — Visibilité des Listes (Priority: P1)

**Goal**: Per-list public/private toggle persisted via `UserListVisibility` upsert, reflected on public profile

**Independent Test**: Toggle "Ma Collection" to public → visit `/profil/{pseudo}` anonymously → collection visible. Toggle back to private → collection absent from public profile

- [X] T024 [US2] Add `POST /profil/list/{listType}/visibility` route (`profile_list_visibility`, ROLE_USER, CSRF `list_visibility_{listType}`, validate listType is valid `UserListType` enum value or return 400, upsert `UserListVisibility` row, return `JsonResponse({'isPublic': bool})` on success, 500+JSON on server error) in `src/Controller/ProfileController.php`
- [X] T025 [US2] Add 4 list tabs (Ma Collection, À Lire, À Acheter, Mes Favoris) with visibility toggle switch to `templates/profile/dashboard.html.twig`: Stimulus controller posts to `profile_list_visibility`, updates toggle state from JSON response, shows toast "Visibilité mise à jour" on success / "Erreur de mise à jour — veuillez réessayer" on failure (rollback toggle state), displays UserBook cards per tab
- [X] T026 [US2] Update `GET /profil/{pseudo}` public profile route to exclude list tabs where `UserListVisibility.isPublic = false` (or row missing = private by default) in `src/Controller/ProfileController.php`

**Checkpoint**: US2 complete — list visibility toggle persists and correctly gates public profile

---

## Phase 6: User Story 3 — Affichage et Tri des Listes (Priority: P2)

**Goal**: Grid/list view toggle, sort selector, and per-tab pagination (20 books/page)

**Independent Test**: With 5+ books in collection: click "vue liste" → condensed table layout. Click "vue grille" → card layout. Change sort → results reorder. Navigate page 2 → next 20 books shown

- [X] T027 [US3] Update `GET /profil` route to handle `?tab=collection|to_read|to_buy|favorites` and `?page=N` query params; paginate active-tab UserBook query to 20/page with prev/next offsets; pass sort param (`recently_added`, etc.) to DQL ORDER BY in `src/Controller/ProfileController.php`
- [X] T028 [US3] Add grid/list view toggle buttons (JS localStorage preference), sort dropdown, and prev/next pagination links to list tabs section in `templates/profile/dashboard.html.twig`

**Checkpoint**: US3 complete — view toggle, sort, and pagination functional per tab

---

## Phase 7: User Story 4 — Désabonnement Auteurs & Collections (Priority: P2)

**Goal**: One-click unfollow from profile with card fade-out animation and confirmation toast

**Independent Test**: From "Mes Auteurs Suivis" click "♥ SUIVI" → card fades out, toast "Désabonnement confirmé". Reload → author absent from list

- [X] T029 [P] [US4] Add `POST /profil/unfollow/contributor/{id}` (CSRF `unfollow_contributor_{id}`, delete `UserContributorSubscription` row if exists, idempotent, return `JsonResponse({success: true})`) and `POST /profil/unfollow/collection/{id}` (CSRF `unfollow_collection_{id}`, delete `UserCollectionSubscription` row, return `JsonResponse({success: true})`) routes in `src/Controller/ProfileController.php`
- [X] T030 [US4] Wire `ContributorRepository::applyFilters()` `onlyFollowed` path: add LEFT JOIN `user_contributor_subscription` WHERE `user_id = :currentUserId` AND `contributor_id = c.id`, filter where subscription exists in `src/Repository/ContributorRepository.php`
- [X] T031 [US4] Add "Mes Auteurs & Collections Suivis" section to `templates/profile/dashboard.html.twig`: cards with "♥ SUIVI" form buttons (Stimulus AJAX POST, CSS fade-out on success, toast "Désabonnement confirmé" / "Erreur lors du désabonnement — veuillez réessayer", keep card visible on error, show "Vous ne suivez aucun auteur pour le moment" / "Vous ne suivez aucune collection pour le moment" when last card removed)

**Checkpoint**: US4 complete — unfollow animation and persistence working

---

## Phase 8: User Story 5 — Paramètres & Sécurité (Priority: P2)

**Goal**: Immediate pseudo edit, double opt-in email change, avatar upload (2MB/JPG/PNG/WebP), region update, Google OAuth unlink (blocked without password)

**Independent Test**: Change pseudo → header updates. Initiate email change → confirmation email sent, old email still active. Upload avatar JPG → avatar updates. Click "Délier Google" without password → disabled button + explanatory message

- [X] T032 [P] [US5] Create `EmailChangeService`: `requestChange(User, newEmail)` — generate `bin2hex(random_bytes(32))` token, set `pendingEmail`/`emailChangeToken`/`emailTokenExpiresAt` (+24h) on User, send confirmation email to new address with `/profil/email/confirm/{token}` link; `confirmChange(token)` — find User by token, reject if expired, swap `email←pendingEmail`, clear `pendingEmail`/`emailChangeToken`/`emailTokenExpiresAt`, flush in `src/Service/EmailChangeService.php`
- [X] T033 [P] [US5] Write `EmailChangeServiceTest`: token generation sets 3 fields, confirmation swaps email and clears fields, expired token rejected, invalid token rejected, success returns updated User in `tests/Service/EmailChangeServiceTest.php`
- [X] T034 [US5] Add `POST /profil/settings/pseudo` route (`profile_update_pseudo`, CSRF `update_pseudo`, validate unique pseudo not taken, update `User.pseudo`/`User.displayName`, redirect to `profile_dashboard` with flash) in `src/Controller/ProfileController.php`
- [X] T035 [US5] Add `POST /profil/settings/email` (`profile_request_email_change`, CSRF `email_change`, call `EmailChangeService::requestChange()`, flash "Un lien de confirmation a été envoyé à {newEmail}") and `GET /profil/email/confirm/{token}` (`profile_confirm_email`, no auth required, call `EmailChangeService::confirmChange()`, invalidate session, redirect to login with success flash; on error redirect to `profile_dashboard` with error flash) routes in `src/Controller/ProfileController.php`
- [X] T036 [US5] Add `POST /profil/settings/avatar` route (`profile_update_avatar`, CSRF `update_avatar`, Symfony FileType field, assert MIME in `[image/jpeg, image/png, image/webp]`, assert max size 2MB, save as `public/uploads/avatars/{userUuid}.{ext}`, delete previous avatar file, update `User.avatarUrl`, return `JsonResponse({avatarUrl: '/uploads/avatars/xxx.jpg'})` on success, `JsonResponse({error: 'message'}, 422)` on error) in `src/Controller/ProfileController.php`
- [X] T037 [US5] Add `POST /profil/settings/region` (`profile_update_region`, CSRF `update_region`, update `User.region`, redirect with flash) and `POST /profil/settings/unlink-google` (`profile_unlink_google`, CSRF `unlink_google`, return 400 if `user.password === null` with message "Définissez un mot de passe avant de délier votre compte Google", else set `User.googleId = null`, flash "Compte Google délié") routes in `src/Controller/ProfileController.php`
- [X] T045 [US5] Add `POST /profil/settings/password` route (`profile_update_password`, CSRF `update_password`, only for users with `user.password !== null` — reject with 400 otherwise, validate `currentPassword` against current hash via `PasswordHasherInterface`, validate `newPassword` min 8 chars, hash new password and update `User.password`, flash "Mot de passe mis à jour", redirect to `profile_dashboard`) in `src/Controller/ProfileController.php`
- [X] T038 [US5] Add "Paramètres & Sécurité" section to `templates/profile/dashboard.html.twig`: pseudo inline edit form, email change form (with double opt-in notice), avatar upload component (client-side square crop JS before submit, inline error on upload failure — not toast; success shows toast "Avatar mis à jour"), region dropdown, Google OAuth unlink button (disabled + message when `user.password === null`), password change form (shown only when `user.password !== null`, posts to `profile_update_password`) — per `design/pages/profil.html`

**Checkpoint**: US5 complete — all settings mutations functional with correct error handling

---

## Phase 9: User Story 6 — Rôle & Permissions (Priority: P2)

**Goal**: Role panel for ROLE_MODERATOR/ROLE_ADMIN with permissions list; gamification rank panel with progression for ROLE_USER

**Independent Test**: ROLE_MODERATOR → blue panel "MODÉRATEUR DE LA GUILDE", Niveau 2/3, 8 permissions (6 granted/2 refused). ROLE_USER → current rank + "Plus que N fiches" message. No ContributorLevel data → "Aucune donnée de rang disponible"

- [X] T039 [US6] Add role/permissions data to `GET /profil` route: inject `permissions` array for ROLE_MODERATOR (8 permissions from FR-011: 6 granted, 2 refused) and ROLE_ADMIN (9 permissions all granted, Niveau 3/3 title "Administrateur de la guilde"); for ROLE_USER call `ContributorLevelService` — pass null to template if service returns null (zero-state: no fictional rank) in `src/Controller/ProfileController.php`
- [X] T040 [US6] Add "Rôle & Permissions" section to `templates/profile/dashboard.html.twig`: blue panel for ROLE_MODERATOR (Niveau 2·sur 3, "MODÉRATEUR DE LA GUILDE", 8 permissions with ✓/— icons) and ROLE_ADMIN (Niveau 3·sur 3, "ADMINISTRATEUR DE LA GUILDE", description from FR-011, all 9 permissions granted); rank panel for ROLE_USER (current rank badge, "Plus que N fiches validées pour atteindre [Nom]", max-rank félicitations message, "Aucune donnée de rang disponible" if null) — per `design/pages/profil.html`

**Checkpoint**: US6 complete — role panel and rank progression render correctly for all roles

---

## Phase 10: Polish & Cross-Cutting Concerns

**Purpose**: Controller integration tests and quickstart validation

- [X] T041 Write `ProfileControllerTest`: `/profil` redirects unauthenticated user to login, `/profil` returns 200 for ROLE_USER, CSRF rejection on `profile_list_visibility` / `profile_delete_account`, delete-account rejected when confirmation !== "SUPPRIMER", delete-account with correct confirmation anonymises user row in `tests/Controller/ProfileControllerTest.php`
- [X] T042 Run quickstart.md validation steps: execute all migrations (`doctrine:migrations:migrate`), verify GhostUser row via SQL, test list visibility toggle reflects on `/profil/{pseudo}`, test login streak increment via DB manual update + login, test account deletion anonymisation + suggestion reassignment. Additional checks: (a) N+1 audit — enable Doctrine SQL logging for `GET /profil` and count queries (must not grow linearly with collection size); (b) verify `ContributorLevelService` from feature 019 is injectable and returns valid rank for a ROLE_USER account; (c) verify zero-state renders "Aucune donnée de rang disponible" when ContributorLevel table is empty; (d) verify SC-007 — access `/profil/{pseudo}` as anonymous user when collection is private and confirm no list data visible

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 — BLOCKS all user stories
- **Phase 3 (US1)**: Depends on Phase 2 (User fields T002, migrations T010/T014 run)
- **Phase 4 (US7)**: Depends on Phase 2 (T002 User.deletedAt confirmed present, T003 ModerationLog nullable, T005 GhostUser constant, T014 GhostUser row)
- **Phase 5 (US2)**: Depends on Phase 2 (T006 UserListVisibility, T008 repo, T012 migration) + Phase 3 (T019 dashboard template base)
- **Phase 6 (US3)**: Depends on Phase 5 (T025 list tabs in template)
- **Phase 7 (US4)**: Depends on Phase 2 (T007 UserContributorSubscription, T009 repo) + Phase 3 (T019 dashboard template)
- **Phase 8 (US5)**: Depends on Phase 2 (T002 User token fields) + Phase 3 (T019 dashboard template)
- **Phase 9 (US6)**: Depends on Phase 3 (T019 dashboard template + T018 /profil route)
- **Phase 10 (Polish)**: Depends on all user story phases

### User Story Independence

- **US1 (P1)**: No story dependencies — start after Phase 2
- **US7 (P1)**: No story dependencies — parallel with US1
- **US2 (P1)**: Needs US1 dashboard template base (T019) — start after Phase 3
- **US3 (P2)**: Needs US2 list tabs (T025) — start after Phase 5
- **US4 (P2)**: No story dependencies beyond Phase 2 + US1 template — start after Phase 3
- **US5 (P2)**: No story dependencies beyond Phase 2 + US1 template — start after Phase 3
- **US6 (P2)**: No story dependencies beyond Phase 2 + US1 template — start after Phase 3

### Migration Execution Order

`M1` → `M2`, `M3`, `M4` (any order after M1) → `M5` (GhostUser row requires M1 User table structure)

### Parallel Opportunities Within Phases

**Phase 2**:
- T003, T004, T005 in parallel (independent files, no shared deps)
- T007 parallel with T006 (different entity files)
- T008 after T006; T009 after T007
- T011, T012, T013 parallel with T010 (different migration files)

**Phase 3 (US1)**:
- T015 (LoginStreakService) and T017 (LoginStreakServiceTest) in parallel

**Phase 4 (US7)**:
- T020 (AccountDeletionService) and T021 (AccountDeletionServiceTest) in parallel

**Phase 8 (US5)**:
- T032 (EmailChangeService) and T033 (EmailChangeServiceTest) in parallel

---

## Parallel Example: Phase 3 (US1)

```bash
# Parallel: write service + test together
Task T015: LoginStreakService in src/Service/LoginStreakService.php
Task T017: LoginStreakServiceTest in tests/Service/LoginStreakServiceTest.php

# Sequential after T015:
Task T016: LoginStreakListener in src/EventListener/LoginStreakListener.php

# Sequential after T016:
Task T018: ProfileController GET /profil in src/Controller/ProfileController.php

# Sequential after T018:
Task T019: dashboard.html.twig in templates/profile/dashboard.html.twig
```

---

## Implementation Strategy

### MVP First (P1 stories: US1 + US7 + US2)

1. Complete Phase 1: Setup (T001)
2. Complete Phase 2: Foundational (T002–T014)
3. Complete Phase 3: US1 — Dashboard header + KPIs (T015–T019)
4. Complete Phase 4: US7 — Account deletion RGPD (T020–T023)
5. Complete Phase 5: US2 — List visibility (T024–T026)
6. **STOP and VALIDATE** all P1 stories independently

### Incremental Delivery

1. Phase 1+2 → Foundation
2. Phase 3 (US1) → Dashboard header + KPIs visible
3. Phase 4 (US7) → RGPD compliance delivered
4. Phase 5 (US2) → List privacy feature
5. Phase 7 (US4) → Unfollow management
6. Phase 8 (US5) → Account settings
7. Phase 9 (US6) → Role transparency
8. Phase 6 (US3) → View/sort/pagination UX polish
9. Phase 10 → Tests + validation

---

## Notes

- `[P]` = different files, no shared dependency at that phase level
- `[Story]` label maps to user stories in `spec.md`
- Migration execution order enforced: M1 must run before M5
- GhostUser guard must be first check in `AccountDeletionService` — no exceptions
- Client-side avatar crop (canvas → blob) is required before form submit (FR-009); server validates MIME + size only
- All mutation routes require CSRF tokens (spec hard constraint)
- `[deleted]-{uuid}` anonymisation pattern mandatory — `email` and `pseudo` have UNIQUE DB constraints
- Stimulus controllers handle AJAX responses, toast display, and animations — no new JS frameworks
- Avatar upload errors are inline (not toast) per FR-009; visibility/unfollow errors use toast
- OAuth unlink button rendered disabled in template when `user.password === null` (not just JS-disabled)
