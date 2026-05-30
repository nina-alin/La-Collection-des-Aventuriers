# Data Model: Auth Pages — Nouveau Design

**Feature**: `010-auth-pages` | **Date**: 2026-05-30

## Modified Entity: User

**File**: `src/Entity/User.php`

### New field

| Field | Type | Default | Notes |
|-------|------|---------|-------|
| `isEmailVerified` | `bool` | `false` | Set to `true` on email confirmation click. Auto-set to `true` via `UserGoogleVerifiedListener` when `googleId` is not null (FR-011 compliant). |

### Validation rule
- Google OAuth users: `isEmailVerified = true` enforced by EntityListener at persist/update time.
- Email/password users: `isEmailVerified = false` until confirmation link clicked.

### Effect on existing logic
- `UserChecker::checkPreAuth()` gains a new check: if `!$user->isEmailVerified()` and `$user->getGoogleId() === null`, throw `CustomUserMessageAccountStatusException('email_not_verified')`.
- `RegistrationController`: after successful registration, no longer calls `$this->security->login()`; instead calls `EmailVerificationService::sendConfirmationEmail()` and renders `state = 'confirmation'`.

---

## New Entity: ResetPasswordToken

**File**: `src/Entity/ResetPasswordToken.php`

| Field | Type | Nullable | Notes |
|-------|------|----------|-------|
| `id` | `int` (IDENTITY) | no | Primary key |
| `token` | `string(64)` unique | no | `bin2hex(random_bytes(32))`, cryptographically secure |
| `user` | `ManyToOne(User)` | no | ON DELETE CASCADE |
| `expiresAt` | `DateTimeImmutable` | no | `now + 30 minutes` (FR-007, FR-013) |
| `used` | `bool` | no | default `false`; set `true` on successful reset |
| `createdAt` | `DateTimeImmutable` | no | Set in constructor |

### State transitions

```
CREATED (used=false, expiresAt > now)
  ↓ used or expired
INVALID (used=true OR expiresAt <= now)
```

### Validation rules
- Token considered valid iff: `used = false` AND `expiresAt > now`.
- On new token creation for a user: all prior tokens for that user set `used = true` atomically (Doctrine transaction, FR-016). If new token creation fails, transaction rolls back — old tokens remain valid.
- Single use: after successful `resetPassword()`, token set `used = true`.

### Indexes
- `UNIQUE(token)`
- Index on `(user_id, used, expiresAt)` for efficient "find valid token for user" queries

---

## New Entity: EmailVerificationToken

**File**: `src/Entity/EmailVerificationToken.php`

| Field | Type | Nullable | Notes |
|-------|------|----------|-------|
| `id` | `int` (IDENTITY) | no | Primary key |
| `token` | `string(64)` unique | no | `bin2hex(random_bytes(32))` |
| `user` | `OneToOne(User)` | no | ON DELETE CASCADE |
| `expiresAt` | `DateTimeImmutable` | no | `now + 24 hours` (duration matches FR-024 template; see assumption) |
| `createdAt` | `DateTimeImmutable` | no | Set in constructor |

### State transitions

```
PENDING (token exists, expiresAt > now)
  ↓ click confirmation link → User.isEmailVerified = true, token deleted
VERIFIED (token deleted)
  ↓ expired without click
EXPIRED (token exists, expiresAt <= now) → user re-requests via "Renvoyer le lien"
```

### Resend behavior
- Delete existing token (if any), create new token with fresh `expiresAt`, send email.
- Rate-limited at 5 req/hour/IP (FR-025, same limiter as password reset resend).

### Indexes
- `UNIQUE(token)`
- `UNIQUE(user_id)` (OneToOne enforced at DB level)

---

## New Infrastructure: Doctrine remember_me tokens

**Table**: `rememberme_token` (auto-created by Symfony Doctrine provider)

No entity file needed — Symfony manages this table internally.

| Column | Type | Notes |
|--------|------|-------|
| `series` | `varchar(88)` PK | token series identifier |
| `value` | `varchar(88)` | token value |
| `lastUsed` | `datetime` | last refresh |
| `class` | `varchar(100)` | user class |
| `username` | `varchar(200)` | user identifier |

### Invalidation on password reset
In `PasswordResetService::resetPassword()`, within the same transaction as password hash update:
```php
$this->em->getConnection()->executeStatement(
    'DELETE FROM rememberme_token WHERE username = :email',
    ['email' => $user->getEmail()]
);
```

---

## Migration scope

One migration covers:
1. `ALTER TABLE "user" ADD is_email_verified BOOLEAN NOT NULL DEFAULT false`
2. `CREATE TABLE reset_password_token (...)`
3. `CREATE TABLE email_verification_token (...)`
4. `CREATE TABLE rememberme_token (...)` — added after enabling doctrine remember_me provider

---

## Entity relationship diagram

```
User (existing)
  ├── OneToOne ← EmailVerificationToken (user_id, ON DELETE CASCADE)
  ├── OneToMany ← ResetPasswordToken[] (user_id, ON DELETE CASCADE)
  └── (implicit) rememberme_token.username = user.email
```
