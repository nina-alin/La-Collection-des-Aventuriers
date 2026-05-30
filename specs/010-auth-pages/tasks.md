---

description: "Task list template for feature implementation"
---

# Tasks: Auth Pages — Nouveau Design

**Input**: Design documents from `specs/010-auth-pages/`

**Prerequisites**: plan.md ✅ spec.md ✅ research.md ✅ data-model.md ✅ contracts/routes.md ✅ quickstart.md ✅

**Tests**: Included — Constitution §V mandates PHPUnit tests for `ResetPasswordToken`, `EmailVerificationToken`, `UserChecker`, `PasswordResetService`, `EmailVerificationService`, rate-limiting, and full-logout.

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1–US4)
- Exact file paths in all descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: New dependency, asset pipeline entry, and reusable Stimulus controllers that all auth pages share.

- [X] T001 Install symfony/mailer 7.2.* (`composer require symfony/mailer`) and verify composer.json + composer.lock
- [X] T002 Create config/packages/mailer.yaml with `dsn: '%env(MAILER_DSN)%'` and `test: true` flag for test env (InMemoryTransport)
- [X] T003 Add `MAILER_DSN=null://null` to .env (dev default) and to .env.test
- [X] T004 [P] Add `auth` Webpack entry point in webpack.config.js importing `./assets/styles/pages/auth.css`
- [X] T005 [P] Create assets/styles/pages/auth.css (copy verbatim from design/assets/auth.css — split-screen layout, 920px breakpoint)
- [X] T006 [P] Create assets/controllers/auth-password_controller.js (Stimulus controller: toggle `type` attribute between `password` and `text` on eye icon click)
- [X] T007 [P] Create assets/controllers/auth-submit_controller.js (Stimulus controller: disable submit button + replace label with inline spinner on form submit; ignore subsequent submits)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Database schema, entities, security config, and shared services that ALL user stories depend on. No user story work can start until this phase is complete.

**⚠️ CRITICAL**: Complete all T008–T019 + T049–T052 before starting Phase 3+

- [X] T008 Add `isEmailVerified: bool = false` field with getter `isEmailVerified()` and setter `setIsEmailVerified(bool)` to src/Entity/User.php (Doctrine column, nullable: false)
- [X] T009 Create src/EntityListener/UserGoogleVerifiedListener.php (Doctrine EntityListener on User: prePersist + preUpdate hooks — when `$user->getGoogleId() !== null`, call `$user->setIsEmailVerified(true)`)
- [X] T010 [P] Create src/Entity/ResetPasswordToken.php (fields: id IDENTITY PK, token string(64) unique, user ManyToOne→User ON DELETE CASCADE, expiresAt DateTimeImmutable, used bool default false, createdAt DateTimeImmutable; constructor sets token=bin2hex(random_bytes(32)), expiresAt=now+30min, createdAt=now; index on (user_id, used, expiresAt))
- [X] T011 [P] Create src/Repository/ResetPasswordTokenRepository.php (methods: findValidTokenByString(string $token): ?ResetPasswordToken, invalidateAllForUser(User $user): void sets used=true on all active tokens for that user)
- [X] T012 [P] Create src/Entity/EmailVerificationToken.php (fields: id IDENTITY PK, token string(64) unique, user OneToOne→User ON DELETE CASCADE, expiresAt DateTimeImmutable 24h, createdAt DateTimeImmutable; constructor sets token=bin2hex(random_bytes(32)))
- [X] T013 [P] Create src/Repository/EmailVerificationTokenRepository.php (methods: findByToken(string $token): ?EmailVerificationToken, deleteForUser(User $user): void)
- [X] T014 Add `token_provider: doctrine: true` to `remember_me` firewall block in config/packages/security.yaml (keeps all other existing remember_me options unchanged)
- [X] T015 [P] Add PUBLIC_ACCESS access_control entries for `^/mot-de-passe-oublie`, `^/reinitialiser-mot-de-passe`, `^/confirmation-email` in config/packages/security.yaml
- [X] T016 Add `password_reset_limiter` (5 req/3600s/IP, sliding_window, cache.app.dbal) and `resend_limiter` (5 req/3600s/IP) to config/packages/rate_limiter.yaml
- [X] T017 Generate Doctrine migration (`php bin/console doctrine:migrations:diff`) covering: ALTER TABLE user ADD is_email_verified; CREATE TABLE reset_password_token; CREATE TABLE email_verification_token; CREATE TABLE rememberme_token — verify and run migration
- [X] T018 Implement `checkPreAuth()` in src/Security/UserChecker.php: if `!$user->isEmailVerified()` and `$user->getGoogleId() === null`, throw `CustomUserMessageAccountStatusException('email_not_verified')` (existing UserChecker file — do not replace existing checks)
- [X] T019 Create src/Service/AuthMailerService.php (inject MailerInterface + UrlGeneratorInterface; methods: `sendPasswordResetEmail(User $user, string $token): void` and `sendEmailConfirmationEmail(User $user, string $token): void` — render Twig email templates, throw on mailer error)

- [X] T049 Modify `onAuthenticationSuccess()` in src/Security/GoogleAuthenticator.php: (1) read `_google_oauth_pending` from session BEFORE removing it; (2) if `$user->getGoogleId() === null`, call `$user->setGoogleId(pending['google_id'])`, setDisplayName, setAvatarUrl, setIsEmailVerified(true), flush via EntityManager — this is the account-linking path for FR-028; (3) change fallback redirect from `$router->generate('home')` to `$router->generate('app_collection')` (all other methods unchanged — FR-011)
- [X] T050 [P] Write PHPUnit functional test for Google OAuth account linking in tests/Functional/Security/GoogleOAuthAccountLinkingTest.php: assert existing email/password user authenticated via Google → user.googleId persisted, redirect to app_collection; assert new Google user → redirect to app_collection (FR-028)
- [X] T051 [P] Add `#[Assert\Length(min: 3, max: 30)]` and `#[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/')]` constraints to the `username` field in src/Entity/User.php; verify the same constraints are enforced in src/Form/RegistrationFormType.php (FR-018 server-side); verify HTML5 `pattern`, `minlength`, `maxlength` attributes added to username input in T031 template (FR-018 client-side)
- [X] T052 [P] Write PHPUnit unit test for username validation in tests/Unit/Validation/UsernameValidationTest.php: assert values with 3–30 alphanumeric/dash/underscore chars pass; assert spaces, accented chars, dots, length < 3 or > 30 are rejected — 100 % rejection rate required (SC-008)

**Checkpoint**: Schema migrated, entities created, security configured — user story implementation can begin.

---

## Phase 3: User Story 1 — Connexion avec le nouveau design (Priority: P1) 🎯 MVP

**Goal**: Redesigned split-screen login page. Password show/hide, brute-force banner, Google OAuth button, remember-me checkbox, email-not-verified error with resend link.

**Independent Test**: Open `/connexion` at viewport >920px → split-screen layout; enter valid credentials → redirect to collection; enter wrong credentials → error zone visible; authenticated user → redirect to collection.

### Tests for User Story 1

- [X] T020 Write PHPUnit unit test for `UserChecker::checkPreAuth()` in tests/Unit/Security/UserCheckerTest.php (assert exception thrown when isEmailVerified=false + no googleId; assert no exception when isEmailVerified=true; assert no exception when googleId is set)

### Implementation for User Story 1

- [X] T021 [US1] Modify src/Controller/SecurityController.php: add redirect to `app_collection` when `$this->getUser() !== null` at top of `login()` action (FR-021); pass `brute_blocked` bool and `remaining_minutes` int to template (from BruteForceProtectionService)
- [X] T022 [US1] Redesign templates/security/login.html.twig to match design/pages/connexion.html: split-screen layout using `encore_entry_link_tags('auth')`, left cover panel with page-specific eyebrow/headline/benefits/stats, right panel with login form; include brute-force warning banner (shows when brute_blocked=true + remaining_minutes), email+password fields with show/hide toggle (data-controller="auth-password"), remember-me checkbox, Google OAuth button, error zone (aria-describedby), submit button with spinner (data-controller="auth-submit"), CSRF token, link to /mot-de-passe-oublie and /inscription; when error = 'email_not_verified': resend form in error zone pointing to POST `app_email_verification_resend` with CSRF token (FR-022)

**Checkpoint**: User Story 1 fully functional and visually correct — verify against design/pages/connexion.html.

---

## Phase 4: User Story 2 — Inscription + confirmation e-mail (Priority: P1)

**Goal**: Redesigned registration page with split-screen, password strength meter + live checklist, CGU checkbox, confirmation state after submit, email verification flow with resend.

**Independent Test**: Open `/inscription`, enter pseudo + email + strong password, check CGU, submit → view switches to "Vérifie ta boîte de réception" state without full reload; resend link sends new email; Google OAuth redirects to collection.

### Tests for User Story 2

- [X] T023 [P] [US2] Write PHPUnit unit test for EmailVerificationToken entity in tests/Unit/Entity/EmailVerificationTokenTest.php (assert token=64 chars hex, expiresAt=now+24h, createdAt set, isValid() true when not expired, false when expired)
- [X] T024 [P] [US2] Write PHPUnit unit test for EmailVerificationService in tests/Unit/Service/EmailVerificationServiceTest.php (assert sendConfirmationEmail calls AuthMailerService, assert verifyToken sets isEmailVerified=true + deletes token, assert resend deletes old token + creates new one, assert AuthMailerService exception propagates without setting confirmation state)

### Implementation for User Story 2

- [X] T025 [US2] Create src/Service/EmailVerificationService.php (methods: `sendConfirmationEmail(User $user): void` — delete existing token if any, create new EmailVerificationToken, persist, call AuthMailerService::sendEmailConfirmationEmail; `verifyToken(string $token): bool` — find token, check valid, set isEmailVerified=true, delete token, flush; `resend(string $email): void` — find user, if exists + not verified: delete old token, create new, send email — rate-limited via resend_limiter)
- [X] T026 [P] [US2] Create templates/emails/email_confirmation.html.twig (HTML email: user pseudo, confirmation link via `app_email_verify`, 24h validity notice, plain-text fallback link)
- [X] T027 [P] [US2] Create templates/emails/email_confirmation.txt.twig (plain-text version of confirmation email with same content)
- [X] T028 [US2] Write PHPUnit functional test for EmailVerificationController in tests/Functional/Controller/EmailVerificationControllerTest.php (test GET /confirmation-email/{validToken} → isEmailVerified=true; test expired/invalid token → error view; test POST /inscription/renvoyer-confirmation → rate-limited resend)
- [X] T029 [US2] Create src/Controller/EmailVerificationController.php (GET `/confirmation-email/{token}` → `verify()`: call EmailVerificationService::verifyToken, render templates/email_verification/verified.html.twig with state=success or state=error; POST `/inscription/renvoyer-confirmation` → `resend()`: validate CSRF, call EmailVerificationService::resend rate-limited via resend_limiter, redirect to app_register with state=confirmation flash)
- [X] T030 [US2] Modify src/Controller/RegistrationController.php: remove `$this->security->login()` call after registration; add redirect to app_collection if already authenticated (FR-021); on successful POST: call EmailVerificationService::sendConfirmationEmail, return render with state=confirmation; on email send failure: catch exception, render with state=form + error message (FR-027); on form error: render with state=form
- [X] T031 [US2] Redesign templates/registration/register.html.twig to match design/pages/inscription.html: split-screen layout with auth CSS, left cover panel (page-specific content), right panel with two states controlled by `state` variable — state=form: pseudo+email+password fields, strength meter div + live checklist (4 criteria via auth-password controller), CGU checkbox, Google OAuth button, show/hide password, auth-submit controller; state=confirmation: "Vérifie ta boîte de réception" block with resend form (CSRF); generic error message for duplicate email/pseudo (FR-015, FR-018)
- [X] T032 [US2] Create templates/email_verification/verified.html.twig (state=success: confirmation message + link to /connexion; state=error: error message + resend link to /inscription)

**Checkpoint**: User Story 2 fully functional — registration, email send, confirmation view, verify link, resend.

---

## Phase 5: User Story 3 — Mot de passe oublié : demande de lien (Priority: P2)

**Goal**: Forgot-password request page with form/sent states, email with reset link, resend functionality, rate-limiting.

**Independent Test**: Open `/mot-de-passe-oublie`, enter any email → view shows "Lien envoyé"; enter known email → reset email received with link.

### Tests for User Story 3

- [X] T033 [P] [US3] Write PHPUnit unit test for ResetPasswordToken entity in tests/Unit/Entity/ResetPasswordTokenTest.php (assert token=64 chars hex, expiresAt=now+30min, used=false, isValid() true when not expired and not used, false when expired or used)
- [X] T034 [P] [US3] Write PHPUnit unit test for PasswordResetService::requestReset (atomic invalidation) in tests/Unit/Service/PasswordResetServiceTest.php (assert existing active tokens marked used before new token created; assert new token created and email sent; assert unknown email → no token created, no email sent, no exception; assert AuthMailerService exception propagates)

### Implementation for User Story 3

- [X] T035 [US3] Create src/Service/PasswordResetService.php (method `requestReset(string $email): void` — in single Doctrine transaction: find user by email, invalidate all active tokens via ResetPasswordTokenRepository::invalidateAllForUser, create new ResetPasswordToken, persist, call AuthMailerService::sendPasswordResetEmail, commit — if AuthMailerService throws, rollback so no token row is persisted (spec Assumptions); method `resend(string $email): void` — same flow as requestReset, rate-limited; stub `resetPassword(): void` that throws `new \LogicException('Not implemented — see T042')` so the class compiles before Phase 6)
- [X] T036 [P] [US3] Create templates/emails/password_reset.html.twig (HTML email: user pseudo, reset link via `app_password_reset_show` with token query param, 30min validity notice)
- [X] T037 [P] [US3] Create templates/emails/password_reset.txt.twig (plain-text version of reset email)
- [X] T038 [US3] Create src/Controller/PasswordResetController.php with `request()` action (GET: redirect if authenticated, render state=form; POST: validate CSRF, rate-limit via password_reset_limiter, call PasswordResetService::requestReset, catch email exception → state=form+error, success → state=sent; catch rate limit → state=form HTTP 429) and `resend()` action (POST `/mot-de-passe-oublie/renvoyer`: validate CSRF, rate-limit via resend_limiter, call PasswordResetService::resend, redirect to app_password_reset_request with state=sent flash)
- [X] T039 [US3] Create templates/password_reset/request.html.twig to match design/pages/mot-de-passe-oublie.html: split-screen layout, left cover panel (page-specific content), right panel with state=form (email input, CSRF, auth-submit controller) and state=sent ("Lien envoyé" block with resend form)

**Checkpoint**: User Story 3 functional — form → sent state; email sent when address matches account.

---

## Phase 6: User Story 4 — Réinitialisation du mot de passe (Priority: P2)

**Goal**: Password reset form with token validation, strength meter, full logout on success (all sessions + all remember_me tokens).

**Independent Test**: Click valid reset link → form displayed; enter strong password + matching confirm → success state; reconnect with old password fails; click expired link → error state with link to /mot-de-passe-oublie.

### Tests for User Story 4

- [X] T040 [P] [US4] Write PHPUnit unit test for PasswordResetService::resetPassword in tests/Unit/Service/PasswordResetServiceTest.php (assert token marked used=true; assert password hash updated; assert rememberme_token rows deleted via DBAL; assert session invalidated; assert invalid/expired token throws exception; assert passwords-mismatch throws validation exception)
- [X] T041 [US4] ⚠️ Write-first TDD: write this functional test BEFORE T042/T043/T044 — it will fail until implementation is complete (intentional red phase). Create tests/Functional/Controller/PasswordResetControllerTest.php (test GET /reinitialiser-mot-de-passe?token=valid → form shown; test POST with valid token + matching passwords → state=success; test reconnect with old password fails; test expired token GET → state=invalid; test two simultaneous uses of same token → second returns state=invalid)

### Implementation for User Story 4

- [X] T042 [US4] Implement `resetPassword(string $token, string $plainPassword, string $passwordConfirm): void` in src/Service/PasswordResetService.php (validate token via ResetPasswordTokenRepository::findValidTokenByString; validate passwords match; in single Doctrine transaction: hash + save new password, execute DBAL `DELETE FROM rememberme_token WHERE username = :email`, mark token used=true, flush; throw on any validation error)
- [X] T043 [US4] Add `showResetForm()` (GET `/reinitialiser-mot-de-passe`) and `reset()` (POST `/reinitialiser-mot-de-passe`) to src/Controller/PasswordResetController.php (showResetForm: redirect if authenticated, find token → state=form or state=invalid; reset: validate CSRF, call PasswordResetService::resetPassword, catch validation/invalid-token exceptions → state=form/invalid with errors, on success: invalidate current session, return state=success)
- [X] T044 [US4] Create templates/password_reset/reset.html.twig to match design/pages/reinitialiser-mot-de-passe.html: split-screen layout, left cover panel (page-specific content), three states — state=form (new password + confirm fields, strength meter + live checklist, CSRF, auth-password controller, auth-submit controller, token hidden field, email shown in subtitle), state=success ("Mot de passe mis à jour" + link to /connexion), state=invalid (error message + link to /mot-de-passe-oublie)

**Checkpoint**: Complete password recovery flow functional: US3 (request) → US4 (reset with full logout).

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: WCAG compliance, dark theme, no-JS fallback, and final visual validation.

- [X] T045 [P] Add WCAG 2.1 AA attributes to all 4 auth templates: explicit `<label for>` on every input, `aria-describedby` linking inputs to error zones, `role="alert"` on error zones, contrast ≥ 4.5:1 verified against design tokens in auth.css (templates: login.html.twig, register.html.twig, request.html.twig, reset.html.twig)
- [X] T046 [P] Verify dark theme toggle renders correctly on all 4 auth pages: confirm theme-toggle component present in auth Twig layout, test CSS custom properties from auth.css respect `[data-theme="dark"]` scope (FR-009)
- [ ] T047 Validate no-JS fallback on all 4 auth forms: disable JS in browser, submit each form → server-side validation returns correct errors, strength meter absence does not block form submission, state transitions work via full-page renders (FR-010)
- [ ] T048 Run quickstart.md validation sequence: `composer install`, migration, `npm run dev`, open all 4 auth pages in browser > 920px (split-screen) and < 920px (form only), visual comparison side-by-side with design/pages/*.html mockups (SC-006)
- [X] T053 [P] Install `symfony/panther` if absent (`composer require --dev symfony/panther`); create tests/Functional/Accessibility/AuthPagesAccessibilityTest.php — for each of the 4 auth page URLs (`/connexion`, `/inscription`, `/mot-de-passe-oublie`, `/reinitialiser-mot-de-passe?token=dummy`), load via Panther, inject axe-core.js (from node_modules or CDN fallback), execute `axe.run()`, assert violations array is empty (SC-007 WCAG 2.1 AA automated audit)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 completion — **BLOCKS all user stories**
- **US1 (Phase 3)**: Depends on Phase 2 completion
- **US2 (Phase 4)**: Depends on Phase 2 completion — can run parallel to US1 (different files)
- **US3 (Phase 5)**: Depends on Phase 2 completion — can run parallel to US1+US2
- **US4 (Phase 6)**: Depends on US3 (PasswordResetController + PasswordResetService must exist)
- **Polish (Phase 7)**: Depends on all 4 user stories complete

### User Story Dependencies

- **US1 (P1)**: Independent after Phase 2
- **US2 (P1)**: Independent after Phase 2 — runs parallel to US1
- **US3 (P2)**: Independent after Phase 2 — runs parallel to US1/US2
- **US4 (P2)**: Depends on US3 (extends PasswordResetController + PasswordResetService)

### Within Each User Story

- Unit tests before entity/service implementation
- Entities before services
- Services before controllers
- Controllers before templates
- Functional tests after implementation

### Parallel Opportunities

- T004, T005, T006, T007 — all Phase 1 asset tasks in parallel
- T010, T011, T012, T013 — all entity + repository creation in parallel
- T014, T015, T016 — config file changes in parallel (different files)
- T023, T024 — US2 unit tests in parallel
- T026, T027 — email templates in parallel
- T033, T034 — US3 unit tests in parallel
- T036, T037 — US3 email templates in parallel
- T040, T041 — US4 tests in parallel (different files)
- T045, T046, T053 — Polish tasks in parallel
- T050, T051, T052 — Phase 2 additions in parallel

---

## Parallel Example: User Story 2

```bash
# Write unit tests in parallel (different files):
Task T023: tests/Unit/Entity/EmailVerificationTokenTest.php
Task T024: tests/Unit/Service/EmailVerificationServiceTest.php

# After T025 (service) is done, run in parallel:
Task T026: templates/emails/email_confirmation.html.twig
Task T027: templates/emails/email_confirmation.txt.twig
```

## Parallel Example: User Story 3

```bash
# Write unit tests in parallel:
Task T033: tests/Unit/Entity/ResetPasswordTokenTest.php
Task T034: tests/Unit/Service/PasswordResetServiceTest.php (requestReset tests)

# After T035 (service) is done, run email templates in parallel:
Task T036: templates/emails/password_reset.html.twig
Task T037: templates/emails/password_reset.txt.twig
```

---

## Implementation Strategy

### MVP First (US1 + US2 — both P1)

1. Complete Phase 1: Setup (T001–T007)
2. Complete Phase 2: Foundational (T008–T019) — **CRITICAL**
3. Complete Phase 3: US1 Login redesign (T020–T022)
4. Complete Phase 4: US2 Registration + email verification (T023–T032)
5. **STOP and VALIDATE**: Both P1 stories functional
6. Deploy/demo MVP if ready

### Incremental Delivery

1. Phase 1 + Phase 2 → infrastructure ready
2. US1 → login page live (MVP minimal)
3. US2 → registration + email verification live
4. US3 → forgot password request live
5. US4 → full password reset flow live
6. Polish → WCAG, dark theme, no-JS validation

### Parallel Team Strategy

With multiple developers after Phase 2:
- Developer A: US1 (T020–T022)
- Developer B: US2 (T023–T032)
- Developer C: US3 (T033–T039) then US4 (T040–T044)

---

## Notes

- [P] tasks = different files, no cross-task dependencies at time of execution
- [Story] label maps task to user story for traceability
- Tests marked in quickstart.md: `php bin/phpunit tests/Unit/Entity/ResetPasswordTokenTest.php`, etc.
- Constitution §V requires tests — do not skip T020, T023, T024, T028, T033, T034, T040, T041, T050, T052
- FR-011: Do NOT modify BruteForceProtectionService, UserRegistrationService. Exception: GoogleAuthenticator::onAuthenticationSuccess() IS modified in T049 for FR-028 — all other methods unchanged
- FR-028: account linking implemented in T049 (GoogleAuthenticator); tested in T050
- FR-016: ResetPasswordToken creation MUST be atomic (Doctrine transaction) — enforced in T035/T042
- FR-008: Full logout = delete rememberme_token rows (DBAL) + Symfony session invalidation — enforced in T042
- Verify each story checkpoint independently before proceeding to next story
