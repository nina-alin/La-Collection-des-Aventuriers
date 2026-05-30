# Implementation Plan: Auth Pages — Nouveau Design

**Branch**: `010-auth-pages` | **Date**: 2026-05-30 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/010-auth-pages/spec.md`

## Summary

Redesign the four authentication pages (`/connexion`, `/inscription`, `/mot-de-passe-oublie`, `/reinitialiser-mot-de-passe`) with a split-screen layout from the design mockups, implement password reset (new backend feature), email verification (new backend feature), and fix session/remember_me invalidation on password reset. Touches Twig templates, two new backend controllers, four new/modified entities, and the Symfony Mailer + Doctrine remember_me token provider (both currently absent).

## Technical Context

**Language/Version**: PHP 8.2+, Symfony 7.2.*

**Primary Dependencies**: Doctrine ORM 3.6, PHPUnit 12.5, Stimulus 2.35, Turbo 2.36, Webpack Encore, symfony/rate-limiter (installed), symfony/lock (installed)

**New dependencies required**:
- `symfony/mailer 7.2.*` — transactional email (NOT currently installed; see research.md §1)

**Storage**: PostgreSQL via Doctrine ORM. New tables: `reset_password_token`, `email_verification_token`, `rememberme_token` (auto-created by Doctrine remember_me provider)

**Testing**: PHPUnit 12.5 (existing `tests/Functional`, `tests/Integration`, `tests/Unit`)

**Target Platform**: Platform.sh (Linux), served via Symfony runtime

**Project Type**: Symfony web application — Twig + Stimulus frontend

**Performance Goals**: Password strength meter < 100ms response per keystroke (SC-004); form state transitions without full page reload when JS active (SC-005)

**Constraints**:
- No new CSS/JS frameworks (constitution Frontend Integration)
- Twig only for templates
- No modification of: `BruteForceProtectionService`, `UserRegistrationService`, existing rate-limiter config (FR-011). Exception: `GoogleAuthenticator::onAuthenticationSuccess()` modified for FR-028 account linking + redirect fix — all other methods unchanged.
- CSRF on all mutating routes (FR-026)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-checked after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I — Complémentarité Stricte | ✅ PASS | Auth pages are infrastructure, not editorial/forum feature |
| II — Architecture Symfony LTS | ✅ PASS | All controllers thin; business logic in Services; Doctrine ORM; DI throughout. `symfony/mailer` add does not touch `.platform/services.yaml` (no managed infra service added — SMTP via env var). Doctrine remember_me table = migration, not Platform.sh service. |
| III — Workflow Contenu | ✅ PASS | Auth pages don't submit content; no PENDING workflow involved |
| IV — RBAC | ✅ PASS | New routes all `PUBLIC_ACCESS`; CSRF on all form mutations (FR-026); `#[IsGranted]` not required on public auth routes |
| V — Sécurité & Tests | ✅ PASS (obligation) | PHPUnit tests required for: `ResetPasswordToken`, `EmailVerificationToken`, `UserChecker` email check, `PasswordResetService`, `EmailVerificationService`, rate-limiting, full-logout on reset |

## Project Structure

### Documentation (this feature)

```text
specs/010-auth-pages/
├── plan.md              ← this file
├── research.md          ← Phase 0 output
├── data-model.md        ← Phase 1 output
├── quickstart.md        ← Phase 1 output
├── contracts/
│   └── routes.md        ← Phase 1 output
└── tasks.md             ← Phase 2 output (/speckit-tasks)
```

### Source Code (repository root)

```text
src/
├── Controller/
│   ├── SecurityController.php         (modify: new Twig template only)
│   ├── RegistrationController.php     (modify: email verification flow, no auto-login)
│   ├── PasswordResetController.php    (NEW)
│   └── EmailVerificationController.php (NEW)
├── Entity/
│   ├── User.php                       (modify: add isEmailVerified field)
│   ├── ResetPasswordToken.php         (NEW)
│   └── EmailVerificationToken.php     (NEW)
├── EntityListener/
│   └── UserGoogleVerifiedListener.php (NEW: auto-set isEmailVerified=true on googleId set)
├── Repository/
│   ├── ResetPasswordTokenRepository.php  (NEW)
│   └── EmailVerificationTokenRepository.php (NEW)
├── Security/
│   └── GoogleAuthenticator.php        (modify: onAuthenticationSuccess — account linking + redirect, FR-028)
└── Service/
    ├── PasswordResetService.php       (NEW)
    ├── EmailVerificationService.php   (NEW)
    └── AuthMailerService.php          (NEW)

assets/
├── styles/pages/
│   └── auth.css                       (NEW: copy from design/assets/auth.css)
└── controllers/
    ├── auth-password_controller.js    (NEW: strength meter + show/hide)
    └── auth-submit_controller.js      (NEW: submit spinner + disable)

templates/
├── security/
│   └── login.html.twig                (modify: split-screen redesign)
├── registration/
│   └── register.html.twig             (modify: split-screen + confirmation state)
├── password_reset/
│   ├── request.html.twig              (NEW)
│   └── reset.html.twig                (NEW)
├── email_verification/
│   └── verified.html.twig             (NEW: post-click confirmation page)
└── emails/
    ├── password_reset.html.twig       (NEW)
    ├── password_reset.txt.twig        (NEW)
    ├── email_confirmation.html.twig   (NEW)
    └── email_confirmation.txt.twig    (NEW)

config/packages/
├── security.yaml                      (modify: doctrine remember_me provider, new access_control)
├── rate_limiter.yaml                  (modify: add password_reset_limiter, resend_limiter)
└── mailer.yaml                        (NEW)

migrations/
└── VersionXXXX.php                    (NEW: isEmailVerified, reset_password_token, email_verification_token, rememberme_token)
```

**Structure Decision**: Single Symfony web application. No new frontend framework. Auth pages use the existing `app` Webpack entry; `auth.css` imported in the auth Twig layout via `encore_entry_link_tags('auth')` — new `auth` entry added to `webpack.config.js`.

## Complexity Tracking

> No Constitution violations requiring justification.

---

*Next step: `/speckit-tasks` to generate the implementation task list.*
