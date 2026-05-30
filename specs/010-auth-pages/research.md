# Research: Auth Pages — Nouveau Design

**Feature**: `010-auth-pages` | **Date**: 2026-05-30

## §1 — symfony/mailer: not installed

**Context**: The spec assumes mailer is configured, but `symfony/mailer` is absent from `composer.json` and no `MAILER_DSN` exists in `.env`.

**Decision**: Install `symfony/mailer 7.2.*` as a first-party Symfony package.

**Setup**:
```bash
composer require symfony/mailer
```

**Config** (`config/packages/mailer.yaml`):
```yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
```

**`.env` addition**:
```dotenv
###> symfony/mailer ###
MAILER_DSN=null://null
###< symfony/mailer ###
```

**Platform.sh**: No change to `.platform/services.yaml` or `.platform/routes.yaml`. SMTP credentials injected as Platform.sh environment variable `MAILER_DSN`. `.platform.app.yaml` may reference `$MAILER_DSN` in variables block if needed — not a managed service, no schema change required.

**Test env**: `MAILER_DSN=null://null` in `.env.test` (messages discarded; use `InMemoryTransport` via `test: true` in mailer.yaml for assertions).

**Alternatives considered**: `symfonycasts/reset-password-bundle` — rejected because spec explicitly defines a custom `ResetPasswordToken` entity and custom flow. Using a bundle would conflict with the spec's requirements around token management and atomic invalidation (FR-016).

---

## §2 — remember_me token invalidation (full logout everywhere)

**Context**: `security.yaml` configures `remember_me` without a `token_provider`. In Symfony 7.2 this defaults to HMAC-based (signature-only, not persisted). HMAC tokens cannot be invalidated individually — changing the password hash does NOT invalidate them (secret key is static). FR-008 requires full logout on all devices.

**Decision**: Switch to Doctrine-backed remember_me token provider.

**Config change** (`security.yaml`):
```yaml
remember_me:
    secret: '%kernel.secret%'
    lifetime: 2592000
    token_provider:
        doctrine: true   # ← add this
    # ... existing options unchanged
```

**Migration**: Symfony auto-creates the `rememberme_token` table via Doctrine when this provider is first activated. Generate migration after config change.

**Invalidation on password reset**: In `PasswordResetService::resetPassword()`, after persisting new hashed password:
1. Delete all `rememberme_token` rows for the user (via `RememberMeTokenRepository` or direct DBAL query)
2. The user's current session is also invalidated by Symfony when `invalidate_session: true` on logout path — but for other active sessions, token deletion handles them

**Rationale**: Database-backed tokens are the only mechanism that allows selective invalidation without requiring the user to wait for cookie expiry.

**Alternatives considered**: Rotating `kernel.secret` — rejected (would log out ALL users on ALL devices platform-wide). Adding a `tokenVersion` field to `User` — rejected (more complex, non-standard Symfony approach).

---

## §3 — State transitions: form → confirmation view (no full reload)

**Decision**: Return Twig template with a `state` variable (`'form'` or `'confirmation'`). Turbo Drive (already active site-wide) handles the navigation as a partial DOM update — no full page reload visible to the user.

**Implementation**:
```twig
{% if state == 'confirmation' %}
  {# show "check your inbox" block #}
{% else %}
  {# show form #}
{% endif %}
```

Controller POST handler returns `$this->render('...', ['state' => 'confirmation'])` on success — no redirect, same URL. Turbo Drive intercepts the response and updates the `<body>`.

**Without JS**: Same template renders correctly — full HTML response showing the confirmation state. Fully functional. ✓ (FR-010)

**Rationale**: Simpler than Turbo Streams. No `<turbo-stream>` response format to maintain. Works with zero JS for the state toggle itself.

**Alternatives considered**: Turbo Streams `<turbo-stream action="replace">` — rejected as over-engineered for a simple toggle; adds response format complexity for no UX gain.

---

## §4 — isEmailVerified for Google OAuth users

**Context**: Google OAuth users must be created with `isEmailVerified = true` (Google verifies emails). `GoogleAuthenticator` cannot be modified (FR-011). 

**Decision**: Doctrine EntityListener on `User` — `postUpdate` + `prePersist` hooks: when `googleId` is set (not null), force `isEmailVerified = true`.

**Implementation** (`UserGoogleVerifiedListener`):
```php
#[ORM\HasLifecycleCallbacks]
class User {
    // OR: use EntityListener class registered via attribute
}
```

`UserGoogleVerifiedListener::prePersist(User $user)` and `preUpdate(User $user)`: if `$user->getGoogleId() !== null`, call `$user->setIsEmailVerified(true)`.

**Rationale**: Zero modification to `GoogleAuthenticator`. Handles both new Google users and existing email/password users who link their Google account (FR-028).

**Alternatives considered**: Event subscriber on `AuthenticationSuccessEvent` — rejected (fires after login, too late; wouldn't work for account creation). Modifying `UserRegistrationService::fuseGoogleAccount()` — rejected (FR-011 protects it).

---

## §5 — Email verification token storage

**Decision**: Dedicated `EmailVerificationToken` entity (not a field on `User`).

**Fields**: `id`, `token` (unique, 64-char hex), `user` (OneToOne), `expiresAt`, `createdAt`.

**Rationale**: Keeps `User` entity clean. Allows token expiry without a nullable field on User. Consistent with `ResetPasswordToken` pattern. Token deleted on successful verification.

**Token generation**: `bin2hex(random_bytes(32))` — 64 hex chars, cryptographically secure.

**Resend behavior**: Delete existing token, create new one, send new email (rate-limited, FR-025).
