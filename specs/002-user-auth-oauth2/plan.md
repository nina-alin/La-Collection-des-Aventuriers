# Implementation Plan: Inscription et Authentification (Classique + Google OAuth2)

**Branch**: `002-user-auth-oauth2` | **Date**: 2026-05-23 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/002-user-auth-oauth2/spec.md`

## Summary

Add user authentication to "La Collection des Aventuriers": email/password registration + login with CSRF protection, Google OAuth2 SSO, brute-force rate limiting, RGPD consent gate, session management (remember-me 30 days fixed), and structured auth event logging via Monolog. Implemented via Symfony Security component + KnpU OAuth2 bundle, Doctrine ORM (User entity, UUID v4 PK), and Symfony Rate Limiter backed by Doctrine DBAL cache pool — no new Platform.sh infrastructure services required.

## Technical Context

**Language/Version**: PHP 8.3, Symfony 7.2 LTS; Node.js 20.x LTS (build only)

**Primary Dependencies** (additions to existing stack):
- `symfony/security-bundle` 7.2.*
- `symfony/form` 7.2.*
- `symfony/validator` 7.2.*
- `doctrine/doctrine-bundle` ^2.12
- `doctrine/orm` ^3.3
- `doctrine/doctrine-migrations-bundle` ^3.3
- `knpuniversity/oauth2-client-bundle` ^2.18
- `league/oauth2-google` ^4.0
- `symfony/rate-limiter` 7.2.*
- `symfony/http-client` 7.2.*
- `symfony/uid` 7.2.*

**Storage**: PostgreSQL 16 (existing Platform.sh service). New tables: `"user"`, `doctrine_migration_versions`, `cache_items`. No new Platform.sh infrastructure services.

**Testing**: PHPUnit 11.x; Symfony WebTestCase for controller integration tests; KernelTestCase for service unit tests.

**Target Platform**: Platform.sh; no changes to `.platform/services.yaml` (no new infrastructure). `.platform.app.yaml` unchanged.

**Project Type**: Symfony web application — adds backend entities, security layer, and auth UI templates.

**Performance Goals**: SC-001 (< 2 min registration wall-clock), SC-002 (< 30 s login), SC-003 (< 60 s Google OAuth2 flow).

**Constraints**:
- bcrypt cost ≥ 13 (FR-003)
- No Redis — rate limiter uses Doctrine DBAL cache pool (PostgreSQL)
- Cookies: `Secure + HttpOnly + SameSite=Lax` (FR-006)
- CSRF on all auth forms + logout POST + RGPD consent + OAuth2 state param (FR-009)
- Google HTTP timeout: 10 s (FR-010)
- No new JS frameworks (Constitution — Frontend Integration)

**Scale/Scope**: 1 new entity; ~10 PHP source files; ~3 Twig templates; 1 Doctrine migration.

## Constitution Check

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Complémentarité Stricte | ✅ PASS | Auth infrastructure only — no forum, news, or La Taverne overlap |
| II. Architecture Symfony LTS | ✅ PASS | Thin controllers (HTTP only); business logic in `UserRegistrationService`, `BruteForceProtectionService`, `GoogleOAuth2Service`; Doctrine ORM exclusively; DI throughout; no new `.platform/services.yaml` entry; `cache_items` table created via DBAL migration in same commit |
| III. Workflow de Validation du Contenu | ✅ N/A | No user-submitted content in this feature |
| IV. RBAC | ✅ PASS | `ROLE_USER` assigned on creation (FR-004); all data-mutating routes protected by CSRF; `#[IsGranted]` on logout |
| V. Sécurité et Couverture de Tests | ✅ PASS | PHPUnit required for: User entity, registration service (all FR-001 scenarios), brute-force service (FR-008 thresholds), Google service (FR-011/012/015/016/018), registration rate limiter (FR-021), auth event logging (FR-020) |

**Gate Result**: All principles pass or N/A. Proceeding to Phase 0.

## Project Structure

### Documentation (this feature)

```text
specs/002-user-auth-oauth2/
├── plan.md              # This file
├── research.md          # Phase 0 — technology decisions
├── data-model.md        # Phase 1 — User entity schema
├── quickstart.md        # Phase 1 — developer onboarding + OAuth2 setup
├── contracts/
│   ├── routes.md        # Public/protected route contracts
│   └── forms.md         # Form field contracts
└── tasks.md             # Phase 2 output (/speckit-tasks — not created here)
```

### Source Code (repository root)

```text
la-collection-dont-vous-etes-le-heros/
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml               # NEW
│   │   ├── doctrine_migrations.yaml    # NEW
│   │   ├── security.yaml               # NEW
│   │   ├── rate_limiter.yaml           # NEW
│   │   └── knpu_oauth2_client.yaml     # NEW
│   └── routes/
│       └── security.yaml               # NEW — /connexion, /deconnexion, /auth/google*
├── migrations/
│   └── Version[timestamp].php          # NEW — "user" + cache_items tables
├── src/
│   ├── Controller/
│   │   ├── SecurityController.php      # NEW — /connexion (render only)
│   │   ├── RegistrationController.php  # NEW — /inscription GET + POST
│   │   └── OAuth2Controller.php        # NEW — /auth/google, /auth/google/consent
│   ├── Entity/
│   │   └── User.php                    # NEW — UserInterface + PasswordAuthenticatedUserInterface
│   ├── Form/
│   │   └── RegistrationFormType.php    # NEW
│   ├── Repository/
│   │   └── UserRepository.php          # NEW — UserLoaderInterface
│   ├── Security/
│   │   └── GoogleAuthenticator.php     # NEW — extends AbstractSocialAuthenticator
│   ├── Service/
│   │   ├── UserRegistrationService.php
│   │   ├── GoogleOAuth2Service.php
│   │   └── BruteForceProtectionService.php
│   └── EventSubscriber/
│       └── AuthenticationEventSubscriber.php   # NEW — FR-020 logging
├── templates/
│   ├── security/
│   │   └── login.html.twig             # NEW
│   ├── registration/
│   │   └── register.html.twig          # NEW
│   └── oauth2/
│       └── consent.html.twig           # NEW — RGPD consent gate
└── tests/
    ├── Unit/
    │   ├── Entity/UserTest.php
    │   └── Service/
    │       ├── UserRegistrationServiceTest.php
    │       ├── GoogleOAuth2ServiceTest.php
    │       └── BruteForceProtectionServiceTest.php
    └── Integration/
        ├── Controller/
        │   ├── SecurityControllerTest.php
        │   ├── RegistrationControllerTest.php
        │   └── OAuth2ControllerTest.php
        └── EventSubscriber/
            └── AuthenticationEventSubscriberTest.php
```

**Structure Decision**: Single Symfony project (existing layout). New `Security/`, `Service/`, `EventSubscriber/` namespaces alongside existing `Controller/`. Templates in dedicated `security/`, `registration/`, `oauth2/` subdirectories.

## Complexity Tracking

> No Constitution violations requiring justification.
