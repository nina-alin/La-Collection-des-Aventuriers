# Research: Inscription et Authentification (Classique + Google OAuth2)

**Phase 0 — Technology Decisions**
**Feature**: `002-user-auth-oauth2` | **Date**: 2026-05-23

---

## 1. OAuth2 Client Library

**Decision**: `knpuniversity/oauth2-client-bundle` ^2.18 + `league/oauth2-google` ^4.0

**Rationale**: KnpU is the de facto Symfony OAuth2 client bundle. It wraps `league/oauth2-client` providers and integrates cleanly with Symfony Security via `AbstractSocialAuthenticator`. `league/oauth2-google` handles Google's OIDC discovery, token exchange, and userinfo retrieval. Both are actively maintained and compatible with Symfony 7.x. KnpU automatically generates and validates the `state` parameter (FR-009 CSRF protection on callback).

**Alternatives considered**:
- `hwi/oauth-bundle`: Heavier, more opinionated, less aligned with Symfony Security authenticator model
- Raw `symfony/http-client` calls: Duplicates KnpU's well-tested state management; higher maintenance burden

---

## 2. Rate Limiter Storage

**Decision**: Symfony Cache DBAL adapter (`DoctrineDbalAdapter`) backed by existing PostgreSQL connection. Separate pools for registration sliding window (via `RateLimiterFactory`) and login brute-force (via custom `BruteForceProtectionService`).

**Rationale**: No Redis provisioned (adding Redis requires `services.yaml` entry — Constitution II). Doctrine DBAL adapter stores state in `cache_items` PostgreSQL table — atomic, survives process restarts, no new infrastructure. Registration rate limiting (FR-021, sliding window) uses `RateLimiterFactory` with `sliding_window` policy. Login brute-force (FR-008, consecutive failures) requires custom logic not available in `RateLimiterFactory` (no reset-on-success); handled by `BruteForceProtectionService` using the same DBAL cache pool.

**Alternatives considered**:
- Redis pool: Requires new `services.yaml` entry — over-engineered for this scope
- APCu: Single-process only, fails under multiple PHP-FPM workers
- Custom DB table: Duplicates what DBAL cache pool provides

---

## 3. User Primary Key

**Decision**: UUID v4 via `symfony/uid` (`Uuid::v4()`)

**Rationale**: Spec mandates UUID v4 (clarification 2026-05-23). `symfony/uid` is a transitive dependency of Symfony 7.2; no additional package. Doctrine mapping uses `UuidType`; no extra configuration.

**Alternatives considered**:
- `ramsey/uuid-doctrine`: Additional dependency for identical result; unnecessary
- Auto-increment integer: Contradicts spec requirement

---

## 4. Remember Me Mechanism

**Decision**: Symfony Security built-in `remember_me` with signature-based cookie (`SignatureRememberMeHandler`)

**Rationale**: FR-006 requires fixed 30-day expiry and `Secure + HttpOnly + SameSite=Lax`. Symfony 7's default signature-based handler stores a signed token in the cookie itself — no database table needed. Expiry enforced by `lifetime: 2592000` (30 × 86400 s). On logout, Symfony Security clears the cookie automatically. Cookie attributes configured via `framework.session` and `security.firewalls.main.remember_me`.

**Alternatives considered**:
- Database-backed `RememberMeToken` table: Enables token invalidation per-device (not in scope v1); adds a table unnecessarily

---

## 5. Brute Force Protection (FR-008 — Consecutive Failures)

**Decision**: `BruteForceProtectionService` using Doctrine DBAL cache pool with two keys per IP: `login_failures_{sha256(ip)}` (counter, TTL = 900 s when blocked) + `login_blocked_{sha256(ip)}` (boolean flag, TTL = 900 s fixed)

**Rationale**: Symfony's built-in `login_throttling` counts total requests in a window, not consecutive failures, and cannot reset on success. `AuthenticationEventSubscriber` listens to `LoginFailureEvent` (increment counter; set block flag at threshold 10) and `LoginSuccessEvent` (delete counter). Block flag TTL is fixed at 900 s per spec ("durée fixe, non prolongée par nouvelles tentatives"). Brute-force check happens in `SecurityController` before rendering the login form response.

**Alternatives considered**:
- `login_throttling` + custom reset listener: Partial fit — still counts non-consecutive failures

---

## 6. CSRF Strategy

**Decision**: Symfony built-in CSRF for all surfaces; KnpU `state` for OAuth2

**Rationale**: `form_login` firewall has built-in CSRF (`csrf_parameter: _csrf_token`). `RegistrationFormType` uses Symfony Form CSRF (enabled by default). Logout uses POST + CSRF (Symfony Security built-in). RGPD consent form (not a Symfony FormType) uses `CsrfTokenManager` explicitly. KnpU validates the `state` parameter automatically on `/auth/google/callback` (FR-009).

---

## 7. Google Userinfo Edge Cases

**Decision**: Absent `email_verified` → treated as `false` (reject). Absent `name` → use local part of email as pseudo base.

**Rationale**: Spec clarifications (2026-05-23) are explicit on both cases. Google OIDC occasionally omits `email_verified` for legacy accounts; rejecting is the safest default. Pseudo derivation from email local part is spec-mandated (FR-018).

---

## 8. Auth Event Logging

**Decision**: Monolog `security` channel; PSR-3 levels; injected `LoggerInterface` in `AuthenticationEventSubscriber`

**Rationale**: FR-020 specifies Monolog only — no DB table. Events: `INFO` for success/logout/creation/OAuth2 start+end; `WARNING` for failures and OAuth2 errors. Dedicated `security` channel keeps auth events separate from application logs.

---

## 9. Google HTTP Timeout

**Decision**: `symfony/http-client` injected into KnpU provider via `HttpClientInterface::withOptions(['timeout' => 10])`

**Rationale**: FR-010 mandates 10 s timeout. `league/oauth2-google` supports custom HTTP clients via its constructor. On timeout/network exception, `GoogleAuthenticator::onAuthenticationFailure()` catches the exception and redirects to `/connexion` with FR-017 message.

---

## 10. Account Fusion (FR-015)

**Decision**: `UserRegistrationService::fuseGoogleAccount(User $existing, string $plainPassword): void` — adds hashed password to existing Google account without creating a new User

**Rationale**: When a visitor registers classically with an email already linked to a Google account (password=null), `UserRegistrationService` detects the existing User, calls `UserPasswordHasherInterface` with cost 13, persists the hashed password, and logs in. Google fields (`google_id`, `display_name`, `avatar_url`) are preserved unchanged (spec clarification 2026-05-23).
