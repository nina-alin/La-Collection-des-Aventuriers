# Quickstart: User Authentication Setup

**Feature**: `002-user-auth-oauth2` | **Date**: 2026-05-23

---

## Prerequisites

- PHP 8.3 + Composer
- PostgreSQL 16 running locally
- Google Cloud Console project with OAuth2 credentials
- Symfony CLI (optional but recommended)

---

## 1. Install PHP Dependencies

```bash
composer require symfony/security-bundle symfony/form symfony/validator symfony/uid
composer require doctrine/doctrine-bundle doctrine/orm doctrine/doctrine-migrations-bundle
composer require knpuniversity/oauth2-client-bundle league/oauth2-google
composer require symfony/rate-limiter symfony/http-client
```

---

## 2. Configure Environment

```dotenv
# .env.local
DATABASE_URL="postgresql://user:pass@localhost:5432/lacollection?serverVersion=16&charset=utf8"
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
APP_SECRET=32-char-random-secret-for-csrf-and-remember-me
```

---

## 3. Create Database and Run Migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

---

## 4. Configure Google OAuth2 Credentials

1. Open [Google Cloud Console](https://console.cloud.google.com/) → APIs & Services → Credentials
2. Create an OAuth 2.0 Client ID (type: Web Application)
3. Authorized redirect URI: `http://localhost:8000/auth/google/callback`
4. Copy Client ID and Client Secret to `.env.local` (already added above)

---

## 5. Verify

```bash
symfony server:start
# Visit http://localhost:8000/inscription — registration form
# Visit http://localhost:8000/connexion  — login form + Google button
```

---

## 6. Run Tests

```bash
php bin/phpunit
```

---

## Production: Platform.sh

Google credentials are set as environment variables (never committed):

```bash
platform variable:set env:GOOGLE_CLIENT_ID "your-client-id"
platform variable:set env:GOOGLE_CLIENT_SECRET "your-client-secret"
```

`DATABASE_URL` is injected automatically via the `database` relationship defined in `.platform.app.yaml`.

---

## Architecture Notes

| Concern | Mechanism |
|---------|-----------|
| Session (standard) | Session cookie, expires on browser close (FR-022) |
| Session (remember me) | Signed cookie, fixed 30-day TTL, `Secure; HttpOnly; SameSite=Lax` (FR-006) |
| Login brute force | Consecutive-failure counter per IP in `cache_items` (PostgreSQL); unblocks automatically after 15 min (FR-008) |
| Registration rate limit | Sliding window 5/hr/IP via `RateLimiterFactory` + `cache_items` (FR-021) |
| Google data (pending consent) | Stored in Symfony session under `_google_oauth_pending`; cleared on browser session end (FR-019) |
| Auth event logging | Monolog `security` channel; `INFO` for success, `WARNING` for failure/errors (FR-020) |
| Password hashing | bcrypt, cost 13, via `UserPasswordHasherInterface` (FR-003) |
