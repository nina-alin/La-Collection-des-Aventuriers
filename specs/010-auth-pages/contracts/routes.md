# Route Contracts: Auth Pages — Nouveau Design

**Feature**: `010-auth-pages` | **Date**: 2026-05-30

All routes are `PUBLIC_ACCESS`. All mutating routes require CSRF token (FR-026).

---

## Existing routes (template changes only)

### GET/POST `/connexion` — `app_login`

**Controller**: `SecurityController::login()`  
**Template**: `security/login.html.twig` (redesigned)

**Template variables**:
| Variable | Type | Source |
|----------|------|--------|
| `lastUsername` | `string` | `AuthenticationUtils::getLastUsername()` |
| `authenticationError` | `AuthenticationException\|null` | `AuthenticationUtils::getLastAuthenticationError()` |
| `brute_blocked` | `bool` | `BruteForceProtectionService::isBlocked($ip)` |
| `remaining_minutes` | `int` | Remaining block time in minutes |

**POST**: Handled by Symfony `form_login` (no controller action needed). CSRF token `authenticate` already wired.

**Redirect if authenticated**: Controller redirects to `app_collection` if `$this->getUser() !== null` (FR-021).

---

### GET/POST `/inscription` — `app_register`

**Controller**: `RegistrationController::register()` (modified)  
**Template**: `registration/register.html.twig` (redesigned)

**Template variables**:
| Variable | Type | Source |
|----------|------|--------|
| `registrationForm` | `FormView` | `RegistrationFormType` |
| `state` | `'form'\|'confirmation'` | `'form'` by default; `'confirmation'` after successful submit |

**POST success**: No auto-login. Send confirmation email via `EmailVerificationService`. Return `state = 'confirmation'` (FR-004).

**POST error**: Return form with errors, `state = 'form'`.

**Redirect if authenticated**: Redirect to `app_collection` (FR-021).

---

## New routes

### GET/POST `/mot-de-passe-oublie` — `app_password_reset_request`

**Controller**: `PasswordResetController::request()`  
**Template**: `password_reset/request.html.twig`

**Template variables**:
| Variable | Type | Source |
|----------|------|--------|
| `state` | `'form'\|'sent'` | `'form'` default; `'sent'` after submit (any email) |
| `csrf_token` | implicit | Symfony form CSRF |

**POST body**: `email: string`

**POST success (email exists)**: Create `ResetPasswordToken`, send email, return `state = 'sent'`. Rate-limited 5/hour/IP (FR-012).

**POST success (email unknown)**: Return `state = 'sent'` — no leak (FR-005).

**POST error (rate limit)**: Flash error, return `state = 'form'` with HTTP 429.

**Redirect if authenticated**: Redirect to `app_collection` (FR-021).

---

### POST `/mot-de-passe-oublie/renvoyer` — `app_password_reset_resend`

**Controller**: `PasswordResetController::resend()`  
**Template**: none (redirects)

**POST body**: `email: string`, `_csrf_token`

**Behavior**: Invalidate old token, create new, send email (if email exists). Rate-limited 5/hour/IP (FR-025). Redirect to `app_password_reset_request` with `state = 'sent'` flash.

---

### GET `/reinitialiser-mot-de-passe` — `app_password_reset_show`

**Controller**: `PasswordResetController::showResetForm()`  
**Template**: `password_reset/reset.html.twig`

**Query param**: `token: string`

**Template variables**:
| Variable | Type | Source |
|----------|------|--------|
| `token` | `string` | Query param (passed through to form) |
| `state` | `'form'\|'success'\|'invalid'` | `'form'` default; `'invalid'` if token expired/used |
| `passwordForm` | `FormView\|null` | Present only when `state = 'form'` |

**Token validation**: If token not found, expired, or used → render `state = 'invalid'` (FR-007).

---

### POST `/reinitialiser-mot-de-passe` — `app_password_reset`

**Controller**: `PasswordResetController::reset()`  
**Template**: `password_reset/reset.html.twig`

**POST body**: `token: string`, `plainPassword: string`, `passwordConfirm: string`, `_csrf_token`

**POST success**: 
1. Validate token (error → `state = 'invalid'`)
2. Validate passwords match + strength (error → `state = 'form'` with errors)
3. Hash + save new password
4. Delete all `rememberme_token` rows for user
5. Mark token `used = true`
6. Invalidate current session
7. Return `state = 'success'` (FR-006, FR-008)

**POST error (passwords mismatch)**: Return `state = 'form'` with error message (FR-007 scenario 5).

**POST error (token invalid)**: Return `state = 'invalid'`.

---

### GET `/confirmation-email/{token}` — `app_email_verify`

**Controller**: `EmailVerificationController::verify()`  
**Template**: `email_verification/verified.html.twig`

**Path param**: `token: string`

**Behavior**:
- Token valid → set `User.isEmailVerified = true`, delete token, render success view with link to `/connexion`
- Token expired/invalid → render error view with link to resend (FR-007 equivalent for email verification)

---

### POST `/inscription/renvoyer-confirmation` — `app_resend_confirmation`

**Controller**: `EmailVerificationController::resend()`  
**Template**: none (redirects to `/inscription`)

**POST body**: `email: string`, `_csrf_token`

**Behavior**: Delete old token, create new, send email (if user exists + not yet verified). Rate-limited 5/hour/IP (FR-025). Always redirects to `/inscription` with `state = 'confirmation'` flash.

---

## Security.yaml access_control additions

```yaml
- { path: ^/mot-de-passe-oublie, roles: PUBLIC_ACCESS }
- { path: ^/reinitialiser-mot-de-passe, roles: PUBLIC_ACCESS }
- { path: ^/confirmation-email, roles: PUBLIC_ACCESS }
```

## Rate limiter additions (rate_limiter.yaml)

```yaml
password_reset_limiter:     # FR-012: 5 req/hour/IP
    policy: sliding_window
    limit: 5
    interval: '3600 seconds'
    cache_pool: cache.app.dbal

resend_limiter:             # FR-025: 5 req/hour/IP (shared by reset + confirmation resend)
    policy: sliding_window
    limit: 5
    interval: '3600 seconds'
    cache_pool: cache.app.dbal
```
