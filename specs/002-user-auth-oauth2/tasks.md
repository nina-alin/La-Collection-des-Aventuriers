---

description: "Task list for feature 002 — user authentication (classic + Google OAuth2)"
---

# Tasks: Inscription et Authentification (Classique + Google OAuth2)

**Input**: Design documents from `/specs/002-user-auth-oauth2/`

**Branch**: `002-user-auth-oauth2` | **Date**: 2026-05-23

**Prerequisites**: plan.md ✓, spec.md ✓, research.md ✓, data-model.md ✓, contracts/routes.md ✓, contracts/forms.md ✓, quickstart.md ✓

**Tests**: Required — Constitution V mandates PHPUnit for: User entity, registration service (all FR-001 scenarios), brute-force service (FR-008 thresholds), Google service (FR-011/012/015/016/018), registration rate limiter (FR-021), auth event logging (FR-020). WebTestCase integration tests included per plan.md structure.

## Format: `[ID] [P?] [Story?] Description with file path`

- **[P]**: Can run in parallel (different files, no blocking dependencies on incomplete tasks)
- **[US#]**: User story label — US1=Inscription, US2=Connexion classique, US3=Google OAuth2, US4=Déconnexion

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Install dependencies and bootstrap all config files

- [X] T001 Install PHP auth dependencies via Composer: `symfony/security-bundle symfony/form symfony/validator symfony/uid doctrine/doctrine-bundle doctrine/orm doctrine/doctrine-migrations-bundle knpuniversity/oauth2-client-bundle league/oauth2-google symfony/rate-limiter symfony/http-client`
- [X] T002 [P] Create `config/packages/doctrine.yaml` (dbal url `%env(DATABASE_URL)%`; orm auto_mapping `App\Entity\` → `src/Entity/`; register `symfony/uid` `UuidType`) and `config/packages/doctrine_migrations.yaml` (migrations_paths: `App\\Migrations` → `migrations/`)
- [X] T003 [P] Create `config/packages/cache.yaml` defining pool `cache.app.dbal` with `adapter: cache.adapter.doctrine_dbal`, `connection: default` (stores `cache_items` table in PostgreSQL — required by rate_limiter and BruteForceProtectionService; no Redis per research.md §2)
- [X] T004 [P] Create `config/packages/rate_limiter.yaml` (pool `registration_limiter`: policy `sliding_window`, limit 5, interval `3600 seconds`, cache_pool `cache.app.dbal` per FR-021 and research.md §2)
- [X] T005 [P] Create `config/packages/knpu_oauth2_client.yaml` (clients.google: type `google`, client_id `%env(GOOGLE_CLIENT_ID)%`, client_secret `%env(GOOGLE_CLIENT_SECRET)%`, redirect_route `app_oauth_google_callback`, scopes `[openid, email, profile]` per FR-010)
- [X] T006 [P] Create `config/routes/security.yaml` with all auth routes: `app_login` (`/connexion` GET), `app_logout` (`/deconnexion` POST), `app_register` (`/inscription` GET|POST), `app_oauth_google` (`/auth/google` GET), `app_oauth_google_callback` (`/auth/google/callback` GET), `app_oauth_google_consent` (`/auth/google/consent` GET|POST) — per contracts/routes.md route name map
- [X] T007 [P] Add `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `APP_SECRET` placeholder entries with comments to `.env`; add real-value instructions to `.env.local.dist`

**Checkpoint**: `composer install` succeeds; `php bin/console debug:config` shows doctrine, cache, rate_limiter, knpu_oauth2_client loaded

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: User entity, repository, DB migration, base security config — MUST complete before any user story begins

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T008 Create `src/Entity/User.php` implementing `UserInterface` + `PasswordAuthenticatedUserInterface`: `#[ORM\Table(name: '"user"')]` (quoted — PostgreSQL reserved word); UUID v4 PK (`Uuid::v4()` in `__construct`); columns: `email` varchar(180) UNIQUE NOT NULL (lowercased on set), `pseudo` varchar(30) UNIQUE NOT NULL, `password` varchar(255) NULLABLE, `roles` json NOT NULL default `[]`, `google_id` varchar(255) UNIQUE INDEX NULLABLE, `display_name` varchar(255) NULLABLE, `avatar_url` varchar(2048) NULLABLE, `created_at` DateTimeImmutable UTC set in `__construct`; `getRoles()` always appends `ROLE_USER` (FR-004); Symfony Validator annotations per data-model.md §Validation constraints
- [X] T009 Create `src/Repository/UserRepository.php` (extends `ServiceEntityRepository<User>`, implements `UserLoaderInterface`; methods: `loadUserByIdentifier(string $email): UserInterface`, `findOneByEmail(string $email): ?User`, `findOneByGoogleId(string $googleId): ?User`, `findOneByPseudo(string $pseudo): ?User`, `isEmailTaken(string $email): bool`, `isPseudoTaken(string $pseudo): bool`) — depends on T008
- [X] T010 Generate Doctrine migration `migrations/Version[timestamp].php` creating: (1) `"user"` table with all columns and UNIQUE indices on `email`, `pseudo`, `google_id`; (2) `cache_items` table (`key_` varchar(255) PK, `data` mediumblob, `lifetime` int nullable, `time` int — for DoctrineDbalAdapter) per research.md §2 — depends on T002, T008
- [X] T011 Create `config/packages/security.yaml` (password_hashers: `App\Entity\User` → bcrypt algorithm cost 13 per FR-003; providers: `app_user_provider` entity class `App\Entity\User` property `email`; firewalls: `dev` pattern `^/(_(profiler|wdt)|css|images|js)/` security false, `main` lazy true provider `app_user_provider` stateless false; access_control: public list per FR-024 — `/`, `/connexion`, `/inscription`, `/auth/google`, `/auth/google/callback`, `/auth/google/consent`, `/politique-de-confidentialite` → PUBLIC_ACCESS; all others → IS_AUTHENTICATED_REMEMBERED redirecting to `/connexion`)
- [X] T012 Create `src/EventSubscriber/AuthenticationEventSubscriber.php` (implements `EventSubscriberInterface`; inject `LoggerInterface $securityLogger` with tag `monolog.logger security`; `getSubscribedEvents`: `LoginSuccessEvent` → `onLoginSuccess` (INFO log user email+IP), `LoginFailureEvent` → `onLoginFailure` (WARNING log email+IP), `LogoutEvent` → `onLogout` (INFO log); public `logAccountCreation(User $user): void` (INFO); public `logOAuth2Event(string $event, string $email, ?string $error = null): void` (INFO for start/success, WARNING for error) per FR-020 — brute-force wiring added in T030)

**Checkpoint**: `php bin/console doctrine:migrations:migrate` creates both tables. `php bin/console debug:security` shows firewall with bcrypt cost 13. `php bin/console cache:clear` succeeds.

---

## Phase 3: User Story 1 — Inscription classique (Priority: P1) 🎯 MVP

**Goal**: Visitor registers with pseudo + email + password + RGPD consent → auto-logged in → redirected to `/`

**Independent Test**: POST valid data → User in DB, authenticated session, redirect `/`. Duplicate email/pseudo → field errors. RGPD unchecked → form error. 6th POST same IP in 1 hour → "Trop de tentatives. Réessayez dans X minutes."

### Tests for User Story 1 (Constitution V — required)

- [X] T013 [P] [US1] Create `tests/Unit/Entity/UserTest.php`: UUID v4 generated on construct; `createdAt` is UTC DateTimeImmutable; `getRoles()` always contains ROLE_USER when `roles = []`; email setter lowercases; `getPassword()` nullable; `eraseCredentials()` no-op; `getUserIdentifier()` returns email
- [X] T014 [P] [US1] Create `tests/Unit/Service/UserRegistrationServiceTest.php`: valid registration creates User with bcrypt-hashed password (cost ≥ 13) and ROLE_USER; duplicate email throws with "Cette adresse email est déjà associée à un compte."; duplicate pseudo throws with "Ce pseudo n'est pas disponible."; password < 8 chars fails; RGPD consent false blocked; FR-015 `fuseGoogleAccount` adds hashed password to existing Google account, preserves `google_id`/`display_name`/`avatar_url` intact; FR-022 auto-login uses standard session (no remember_me flag)

### Implementation for User Story 1

- [X] T015 [P] [US1] Create `src/Form/RegistrationFormType.php` (TextType `pseudo`: NotBlank, Regex `^[a-zA-Z0-9_]{3,30}$`; EmailType `email`: NotBlank, Email mode html5; RepeatedType/PasswordType `plainPassword`: NotBlank, Length min 8, first/second error messages per contracts/forms.md; CheckboxType `rgpdConsent` mapped false, IsTrue message "Vous devez accepter les conditions pour créer un compte."; CSRF enabled by default; all errors bubble to field level for simultaneous display per FR-023)
- [X] T016 [US1] Create `src/Service/UserRegistrationService.php` (inject `UserRepository`, `UserPasswordHasherInterface`, `EntityManagerInterface`, `AuthenticationEventSubscriber`; `register(string $pseudo, string $email, string $plainPassword): User` — lowercase email; (1) check if email exists as Google-only account (`password=null`) via `findOneByEmail` — if found call `fuseGoogleAccount` per FR-015 and return; (2) check `isEmailTaken` — if taken (password≠null) throw "Cette adresse email est déjà associée à un compte."; (3) check `isPseudoTaken` — if taken throw "Ce pseudo n'est pas disponible."; (4) create new User, hash password cost ≥ 13, assign ROLE_USER, persist+flush, call `$subscriber->logAccountCreation($user)`; `fuseGoogleAccount(User $existing, string $plainPassword): void` — hash password, set on existing User, flush, Google fields unchanged) — depends on T009, T012
- [X] T017 [US1] Verify `config/packages/security.yaml` firewall `main` has `stateless: false` and `lazy: true` (set in T011 — prerequisite for `Security::login()` auto-login in T018 per FR-022; no edit needed if T011 completed correctly)
- [X] T018 [US1] Create `src/Controller/RegistrationController.php` (inject `RateLimiterFactory $registrationLimiter`, `UserRegistrationService`, `Security`, `FormFactoryInterface`; GET/POST `/inscription`: consume rate limiter — if exceeded add flash "Trop de tentatives. Réessayez dans X minutes." render form 429; process `RegistrationFormType`, if valid call `UserRegistrationService::register`, call `Security::login($user, 'form_login')`, redirect `/`; on form errors re-render all simultaneously per FR-023; catch duplicate exceptions as form errors) — depends on T015, T016, T017
- [X] T019 [US1] Create `templates/registration/register.html.twig` (extend base Twig layout from design system; render `RegistrationFormType` with `form_start`/`form_errors`/`form_row` for all fields; display all field errors simultaneously per FR-023; "Se connecter avec Google" `href="{{ path('app_oauth_google') }}"` button with loading state on click per FR-010 — use the Design System button loading state pattern from feature 001 (`assets/styles/`); Design System Bootstrap classes from feature 001) — depends on T015

**Checkpoint**: `php bin/phpunit tests/Unit/Entity/UserTest.php tests/Unit/Service/UserRegistrationServiceTest.php` all pass. Visitor can register via `/inscription` and is auto-logged in.

---

## Phase 4: User Story 2 — Connexion classique (Priority: P2)

**Goal**: Registered user logs in via email + password with brute-force protection; optional 30-day remember-me cookie

**Independent Test**: Valid credentials → redirect to `_security.target_path` or `/`. Invalid → "Identifiant ou mot de passe incorrect." 10 consecutive failures → 15-min block with remaining-time message. `REMEMBERME` cookie: Secure+HttpOnly+SameSite=Lax, fixed 30-day TTL.

### Tests for User Story 2 (Constitution V — required)

- [X] T020 [P] [US2] Create `tests/Unit/Service/BruteForceProtectionServiceTest.php`: `recordFailure` increments counter; `isBlocked` false below threshold 10; `isBlocked` true at exactly 10 consecutive failures; `resetCounter` deletes both cache keys on success; block TTL is fixed 900s — new `recordFailure` during block does NOT extend TTL per FR-008; `getRemainingBlockTime` returns correct seconds from block key TTL
- [X] T021 [P] [US2] Append test methods to `tests/Integration/Controller/SecurityControllerTest.php`: GET `/connexion` returns 200 with `_username`/`_password`/`_remember_me`/`_csrf_token` fields; POST valid → 302 to `/`; POST invalid → contains "Identifiant ou mot de passe incorrect." per FR-007; POST blocked IP → contains "Trop de tentatives." per FR-008; `REMEMBERME` cookie has Secure+HttpOnly+SameSite=Lax when `_remember_me` checked per FR-006; `_security.target_path` redirect respected per FR-025
- [X] T022 [P] [US2] Create `tests/Integration/EventSubscriber/AuthenticationEventSubscriberTest.php` (KernelTestCase + Monolog test handler: successful login → INFO record in `security` channel; failed login → WARNING; logout → INFO; account creation → INFO per FR-020)

### Implementation for User Story 2

- [X] T023 [P] [US2] Create `src/Service/BruteForceProtectionService.php` (inject `CacheInterface $dbalCache`; keys: `login_failures_{sha256($ip)}` with 900s TTL on block, `login_blocked_{sha256($ip)}` with fixed 900s TTL; `isBlocked(string $ip): bool`; `recordFailure(string $ip): void` — increment failures key, at count ≥ 10 set block key TTL 900s; `resetCounter(string $ip): void` — delete both keys; `getRemainingBlockTime(string $ip): int` — return block key TTL in seconds; SHA256 IP hashing per research.md §5)
- [X] T030 [US2] Update `src/EventSubscriber/AuthenticationEventSubscriber.php` to wire brute-force (inject `BruteForceProtectionService` and `RouterInterface`; add `LoginSuccessEvent` handler: call `$bruteForce->resetCounter($ip)` after existing INFO log; add `LoginFailureEvent` handler: call `$bruteForce->recordFailure($ip)` after existing WARNING log; add `KernelEvents::REQUEST` listener at priority 10: if main request POST to `/connexion` and `$bruteForce->isBlocked($ip)`, compute `ceil($bruteForce->getRemainingBlockTime($ip) / 60)`, add flash "Trop de tentatives. Réessayez dans X minutes.", set redirect response to `app_login` — **priority 10 fires before Symfony Security listener at priority 8 to intercept before form_login processes credentials** per FR-008) — depends on T023
- [X] T024 [US2] Update `config/packages/security.yaml` firewall `main` to add: `form_login` (login_path `app_login`, check_path `app_login`, username_parameter `_username`, password_parameter `_password`, csrf_parameter `_csrf_token`, csrf_token_id `authenticate`, default_target_path `/`, always_use_default_target_path false, use_referer true per FR-025, failure_flash message "Identifiant ou mot de passe incorrect." per FR-007); `remember_me` (secret `%kernel.secret%`, lifetime 2592000, cookie_name `REMEMBERME`, cookie_secure true, cookie_httponly true, cookie_samesite lax, always_remember_me false, remember_me_parameter `_remember_me` per FR-006) — depends on T023
- [X] T025 [P] [US2] Create `src/Controller/SecurityController.php` (inject `BruteForceProtectionService`, `AuthenticationUtils`, `RequestStack`; GET `/connexion`: get client IP, check `isBlocked($ip)` — if blocked compute remaining minutes; pass `lastUsername`, `authenticationError`, `brute_blocked`, `remaining_minutes` to template; render `templates/security/login.html.twig`) — depends on T023
- [X] T026 [US2] Create `templates/security/login.html.twig` (extend base Twig layout; form POST `/connexion` with `_username` email input, `_password`, `_remember_me` checkbox, `_csrf_token` hidden per contracts/forms.md Login Form; display `authenticationError` flash message "Identifiant ou mot de passe incorrect." per FR-007; display brute-force block message with minutes remaining if `brute_blocked` per FR-008; "Se connecter avec Google" button with loading state on click per FR-010 — use the Design System button loading state pattern from feature 001 (`assets/styles/`); Design System Bootstrap classes) — depends on T025

**Checkpoint**: Login/logout cycle works. 10 consecutive failures → 15-min block. `REMEMBERME` cookie has correct security attributes. `php bin/phpunit tests/Unit/Service/BruteForceProtectionServiceTest.php tests/Integration/Controller/SecurityControllerTest.php tests/Integration/EventSubscriber/AuthenticationEventSubscriberTest.php` all pass.

---

## Phase 5: User Story 4 — Déconnexion (Priority: P1)

**Goal**: Authenticated user securely ends session; `REMEMBERME` cookie explicitly cleared; logout button in nav profile dropdown

**Independent Test**: Login (via US1/US2) → POST `/deconnexion` with CSRF → session destroyed → redirect to `/connexion`. `REMEMBERME` cookie absent after logout. GET protected route unauthenticated → redirect to `/connexion`.

### Tests for User Story 4

- [X] T027 [P] [US4] Append test methods to `tests/Integration/Controller/SecurityControllerTest.php`: POST `/deconnexion` with valid CSRF → 302 to `/connexion`; session destroyed (GET protected route → 302 to `/connexion`); `REMEMBERME` cookie cleared in response; GET `/deconnexion` returns 405 (POST only); authenticated user sees logout button in nav; logout event logged INFO per FR-020

### Implementation for User Story 4

- [X] T028 [US4] Update `config/packages/security.yaml` firewall `main` to add `logout` (path `app_logout`, target `app_login`, invalidate_session true, csrf_parameter `_csrf_token`, csrf_token_id `logout`; Symfony Security auto-clears `REMEMBERME` cookie on logout per FR-006 — research.md §4)
- [X] T029 [US4] Update base navigation template (locate correct file from design system, e.g. `templates/base.html.twig`): add user profile dropdown block `{% if is_granted('IS_AUTHENTICATED_REMEMBERED') %}` containing a `<form method="POST" action="{{ path('app_logout') }}">` with hidden `{{ csrf_token('logout') }}` field and "Se déconnecter" submit button, accessible from all authenticated pages per FR-013

**Checkpoint**: Login → profile dropdown → "Se déconnecter" → session gone, cookie cleared, redirect to `/connexion`. Unauthenticated access to any protected route redirects. All Phase 5 tests pass.

---

## Phase 6: User Story 3 — Connexion / Inscription via Google (Priority: P3)

**Goal**: Full Google OAuth2 flow with RGPD consent gate; all edge cases handled (email_verified, scope rejection, timeout, account fusion)

**Independent Test**: Mock Google responses — new email → consent page → account created → logged in. Existing email → logged into existing account. Refuse consent → no account + FR-027 flash. `email_verified: false` → FR-016 flash. Timeout → FR-017 flash (no 500). Scopes not granted → FR-026 flash.

### Tests for User Story 3 (Constitution V — required)

- [X] T031 [P] [US3] Create `tests/Unit/Service/GoogleOAuth2ServiceTest.php`: FR-011 existing email → connects existing User, no new User; FR-012 new email → creates User with `google_id`/`display_name`/`avatar_url`; FR-016 `email_verified: false` → throws exception with FR-016 message; FR-016 absent `email_verified` → treated as false, throws; FR-018 pseudo from Google name already taken → `_2` suffix until unique; FR-018 empty/absent Google `name` → email local part as pseudo base; FR-015 email belongs to Google-only (`password=null`) account → `fuseGoogleAccount` called; FR-026 missing `email`/`profile` scope → throws exception
- [X] T032 [P] [US3] Create `tests/Integration/Controller/OAuth2ControllerTest.php`: GET `/auth/google` → redirect containing `state` param (CSRF per FR-009); GET `/auth/google/consent` without `_google_oauth_pending` in session → redirect `/connexion`; GET consent page with pending data → 200 with checkbox + privacy link + Confirm + Cancel — NO Google userdata rendered per FR-019; POST consent checked + valid CSRF → User created + authenticated + redirect `/`; POST Cancel/unchecked → no User + FR-027 flash + redirect `/connexion`; session `_google_oauth_pending` cleared after consent regardless of outcome; mock Guzzle `ConnectException` on Google token fetch → no User created + FR-017 flash ("Le service Google est indisponible. Utilisez la connexion classique.") + redirect `/connexion` + no 500 per FR-017

### Implementation for User Story 3

- [X] T033 [P] [US3] Create `src/Service/GoogleOAuth2Service.php` (inject `UserRepository`, `UserRegistrationService`, `EntityManagerInterface`; `findOrCreateUser(array $googleUserInfo): User` — (1) check `email_verified`: absent or false → throw FR-016 exception; (2) `findOneByEmail` → if found and `password=null` call `UserRegistrationService::fuseGoogleAccount` per FR-015; if found with password return existing per FR-011; (3) else generate pseudo via `generateUniquePseudo`, create User with google_id/display_name/avatar_url, persist+flush per FR-012; private `generateUniquePseudo(string $googleName, string $emailLocalPart): string` — sanitize to `^[a-zA-Z0-9_]{1,30}$`, fallback to emailLocalPart if empty, append `_2`/`_3`/… with no limit until `isPseudoTaken` false, trim to 30 chars per data-model.md §Pseudo Generation FR-018) — depends on T009, T016
- [X] T034 [US3] Create `src/Security/GoogleAuthenticator.php` (extends `AbstractSocialAuthenticator`; inject `ClientRegistry`, `RouterInterface`, `RequestStack`, `AuthenticationEventSubscriber`, `UserRepository`; **does NOT inject `GoogleOAuth2Service`** — account creation belongs exclusively in T036; `supports()` on `app_oauth_google_callback`; `authenticate()`: (1) validate state param per FR-009 — mismatch → throw `InvalidCsrfTokenException`; (2) fetch Google access token + raw user info — **⚠️ KnpU uses `league/oauth2-google` backed by Guzzle, NOT Symfony HttpClient**: configure 10s timeout via `['collaborators' => ['httpClient' => new \GuzzleHttp\Client(['connect_timeout' => 10, 'timeout' => 10])]]`; catch `\GuzzleHttp\Exception\ConnectException`/`RequestException` → add FR-017 flash, throw `AuthenticationException` (redirected in `onAuthenticationFailure`); (3) check scopes — missing `email`/`profile` → add FR-026 flash, throw `AuthenticationException`; (4) check `email_verified` absent or false → add FR-016 flash, throw `AuthenticationException`; (5) store raw Google data `{email, google_id, display_name, avatar_url}` in session under `_google_oauth_pending`; (6) return `SelfValidatingPassport(new UserBadge($googleEmail, fn() => $userRepository->loadUserByIdentifier($googleEmail)))` — if user not found Symfony throws `UserNotFoundException`; `onAuthenticationSuccess` → clear `_google_oauth_pending`, log `logOAuth2Event('success', $email)`, redirect to `_security.target_path` or `/` per FR-025; `onAuthenticationFailure` → if `$exception instanceof UserNotFoundException` AND `_google_oauth_pending` set in session → log `logOAuth2Event('consent_required', $email)`, redirect to `app_oauth_google_consent`; else add generic flash from exception message, redirect `/connexion`) — depends on T012; **does NOT depend on T033**
- [X] T035 [US3] Update `config/packages/security.yaml` firewall `main`: add `custom_authenticators: [App\Security\GoogleAuthenticator]` — do NOT set `entry_point: GoogleAuthenticator` (would redirect unauthenticated users to Google OAuth2 instead of `/connexion`, violating FR-024); `form_login` (added in T024) remains the implicit entry point for unauthenticated access — depends on T034
- [X] T036 [US3] Create `src/Controller/OAuth2Controller.php` (inject `ClientRegistry`, `CsrfTokenManagerInterface`, `GoogleOAuth2Service`, `Security`, `RequestStack`, `AuthenticationEventSubscriber`; GET `app_oauth_google`: log OAuth2 initiation, redirect via `$client->redirect(['openid','email','profile'])`; GET `app_oauth_google_consent`: if no `_google_oauth_pending` in session redirect `/connexion`, generate CSRF token id `google_consent`, render `templates/oauth2/consent.html.twig`; POST `app_oauth_google_consent`: validate CSRF — invalid → FR-027 flash + redirect `/connexion`; `rgpdConsent` unchecked → FR-027 flash, clear session, redirect `/connexion`; else retrieve pending data, call `GoogleOAuth2Service::findOrCreateUser`, call `Security::login($user, GoogleAuthenticator::class)`, clear `_google_oauth_pending`, log success, redirect `/`) — depends on T033, T034
- [X] T037 [US3] Create `templates/oauth2/consent.html.twig` (extend base Twig layout; form `POST` `{{ path('app_oauth_google_consent') }}` with: required checkbox `rgpdConsent`, privacy policy link `href="{{ path('app_privacy') }}"` (`/politique-de-confidentialite`), Confirm submit button, Cancel button (GET link to `/connexion`), hidden CSRF field `_token` value `{{ csrf_token('google_consent') }}`; Google userdata NOT displayed per FR-019; Design System Bootstrap classes)

**Checkpoint**: Full Google OAuth2 flow functional. All FR-011/012/015/016/017/026/027 edge cases handled. `php bin/phpunit tests/Unit/Service/GoogleOAuth2ServiceTest.php tests/Integration/Controller/OAuth2ControllerTest.php` all pass.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Full integration test coverage, schema validation, message wording audit, visual review

- [X] T038 [P] Create `tests/Integration/Controller/RegistrationControllerTest.php` (WebTestCase: GET `/inscription` → 200; POST valid → User in DB + authenticated session + redirect `/`; POST duplicate email → form with "Cette adresse email est déjà associée à un compte."; POST duplicate pseudo → "Ce pseudo n'est pas disponible."; POST RGPD unchecked → "Vous devez accepter les conditions pour créer un compte."; POST 6th time same IP within 1h → "Trop de tentatives. Réessayez dans X minutes." per FR-021; POST mismatched passwords → "Les mots de passe ne correspondent pas.")
- [X] T039 [P] Audit all Twig templates to verify flash/error message strings exactly match FR wording: FR-007, FR-008, FR-016 ("Adresse Google non vérifiée. Utilisez la connexion classique."), FR-017 ("Le service Google est indisponible. Utilisez la connexion classique."), FR-021, FR-026 ("Connexion Google annulée."), FR-027 ("Vous devez accepter les conditions pour créer un compte.")
- [X] T040 Run `php bin/console doctrine:schema:validate` — confirm entity mapping matches migration; fix any mismatches
- [X] T041 Run `php bin/phpunit` — full test suite green; fix any failures before marking complete
- [X] T042 [P] Verify FR-024 access control: `php bin/console debug:router` + manual test that each public route in FR-024 loads anonymously and a protected route redirects to `/connexion`
- [X] T043 [P] Visual review of `/connexion` and `/inscription` at desktop and mobile viewports — confirm Design System Bootstrap class conformance per SC-006; confirm Google button loading spinner functional

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — T002–T007 all parallel after T001
- **Phase 2 (Foundational)**: Depends on Phase 1 — **BLOCKS all user stories**; T008+T011+T012 parallel; T009 needs T008; T010 needs T002+T008
- **Phase 3 (US1)**: Depends on Phase 2 — T013/T014/T015 parallel; T016 needs T009+T012; T017 needs Phase 2; T018 needs T015+T016+T017; T019 needs T015
- **Phase 4 (US2)**: Depends on Phase 2 — T020/T021/T022/T023/T025 parallel; T030 needs T023; T024 needs T023; T026 needs T025
- **Phase 5 (US4)**: Depends on Phase 4 — login page at `/connexion` must exist for post-logout redirect; T027 parallel; T028+T029 sequential
- **Phase 6 (US3)**: Depends on Phase 2+Phase 3+Phase 4 — T031/T032/T033/T034 all parallel (T034 no longer needs T033); T035 needs T034; T036 needs T033+T034; T037 needs T036
- **Phase 7 (Polish)**: Depends on all stories complete

### User Story Dependencies

- **US1 (P1 — Inscription)**: Independent after Phase 2 — 🎯 MVP
- **US2 (P2 — Connexion classique)**: Independent after Phase 2; `UserRegistrationService::fuseGoogleAccount` reused from US1
- **US4 (P1 — Déconnexion)**: Requires US2's `login.html.twig` for post-logout redirect target
- **US3 (P3 — Google OAuth2)**: Requires US1's `UserRegistrationService` (account creation); US2's `login.html.twig` for error redirects

### Within Each Story

- Tests marked [P] should be written first and must FAIL before implementation starts (TDD)
- Services before controllers; controllers before templates
- Config updates before services that reference the config

---

## Parallel Example: User Story 1

```bash
# Launch simultaneously — different files, no deps:
Task T013: tests/Unit/Entity/UserTest.php
Task T014: tests/Unit/Service/UserRegistrationServiceTest.php
Task T015: src/Form/RegistrationFormType.php

# Then sequentially:
T016: src/Service/UserRegistrationService.php  (needs T009, T012)
T017: update security.yaml firewall
T018: src/Controller/RegistrationController.php  (needs T015, T016, T017)
T019: templates/registration/register.html.twig  (needs T015)
```

## Parallel Example: User Story 3

```bash
# Launch simultaneously — T034 no longer depends on T033:
Task T031: tests/Unit/Service/GoogleOAuth2ServiceTest.php
Task T032: tests/Integration/Controller/OAuth2ControllerTest.php
Task T033: src/Service/GoogleOAuth2Service.php
Task T034: src/Security/GoogleAuthenticator.php

# Then sequentially:
T035: update security.yaml custom_authenticators  (needs T034)
T036: src/Controller/OAuth2Controller.php  (needs T033, T034)
T037: templates/oauth2/consent.html.twig
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001–T007)
2. Complete Phase 2: Foundational (T008–T012) — **CRITICAL BLOCKER**
3. Complete Phase 3: User Story 1 (T013–T019)
4. **STOP and VALIDATE**: `php bin/phpunit tests/Unit/Entity/UserTest.php tests/Unit/Service/UserRegistrationServiceTest.php` passes; register via `/inscription`, confirm redirect to `/`
5. Deploy or demo MVP

### Incremental Delivery

1. Setup + Foundational → app boots, DB migrated
2. **US1** (Phase 3) → visitor can register → MVP
3. **US2** (Phase 4) → returning user can log in
4. **US4** (Phase 5) → secure logout
5. **US3** (Phase 6) → Google OAuth2 reduces friction
6. **Polish** (Phase 7) → full test suite green, messages verified, visual review

### Parallel Team Strategy (2 developers)

After Phase 2 completes:
- **Dev A**: US1 (Phase 3) → US4 (Phase 5) — registration + logout
- **Dev B**: US2 (Phase 4) → US3 (Phase 6) — login + Google OAuth2

---

## Notes

- `[P]` = different files, no dependency on incomplete tasks in the same phase — safe to run in parallel
- `"user"` table name must be quoted in all SQL/ORM — `user` is a reserved word in PostgreSQL
- `_username` / `_password` are the Symfony Security form_login field names — NOT `email`/`password` (contracts/forms.md Login Form)
- research.md §5: brute-force uses cache keys `login_failures_{sha256($ip)}` + `login_blocked_{sha256($ip)}`; SHA256 to avoid special chars in key
- research.md §7: absent `email_verified` from Google userinfo → treat as false → reject (FR-016)
- research.md §4: remember_me uses `SignatureRememberMeHandler` — no extra DB table; Symfony Security auto-clears cookie on logout (FR-006)
- **KnpU + `league/oauth2-google` uses Guzzle HTTP, not Symfony HttpClient** — configure 10s timeout via `['collaborators' => ['httpClient' => new \GuzzleHttp\Client([...])]]`; catch `\GuzzleHttp\Exception\ConnectException` for FR-017 (research.md §9 intent adapted)
- T030 `KernelEvents::REQUEST` listener at **priority 10** (before Symfony Security at 8) intercepts POST to `/connexion` when IP is blocked — this is the only way to prevent `form_login` from processing blocked credentials
- `cache.app.dbal` pool (T003) must be defined in `cache.yaml` before `rate_limiter.yaml` (T004) and `BruteForceProtectionService` (T023) reference it — container compilation fails without it
- `GoogleAuthenticator` class reference in `security.yaml` (T035) must exist (even as a stub) before `php bin/console cache:clear` succeeds — create T034 before T035
- Session data key `_google_oauth_pending` cleared at browser session end regardless of consent outcome (FR-019); also explicitly cleared on Confirm and Cancel in T036
