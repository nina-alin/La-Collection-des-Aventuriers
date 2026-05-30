# Quickstart: Auth Pages — Nouveau Design

**Feature**: `010-auth-pages` | **Date**: 2026-05-30

## Prerequisites

```bash
# 1. Install new dependency
composer require symfony/mailer

# 2. Configure local mailer (dev — discards emails)
echo "MAILER_DSN=null://null" >> .env.local

# 3. Add doctrine remember_me config (see config/packages/security.yaml)
#    Then generate the migration:
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate

# 4. Build assets
npm run dev   # or: npm run watch
```

## New environment variables

| Variable | Required | Example |
|----------|----------|---------|
| `MAILER_DSN` | Yes | `smtp://user:pass@smtp.mailprovider.com:587` |

In `.env.test`: `MAILER_DSN=null://null` (already discards email).

## Testing email flow locally

Use Symfony Mailer's test transport: add `MAILER_DSN=smtp://localhost:1025` and run MailHog or Mailtrap to catch emails in dev.

## Verifying the split-screen layout

1. Open `/connexion` at viewport > 920 px → see split-screen (left cover + right form)
2. Resize to < 920 px → left cover hidden, form 100% width
3. Reference mockup: `design/pages/connexion.html` (open in browser directly)

## Running tests

```bash
php bin/phpunit tests/Unit/Entity/ResetPasswordTokenTest.php
php bin/phpunit tests/Unit/Entity/EmailVerificationTokenTest.php
php bin/phpunit tests/Unit/Service/PasswordResetServiceTest.php
php bin/phpunit tests/Functional/Controller/PasswordResetControllerTest.php
php bin/phpunit tests/Functional/Controller/EmailVerificationControllerTest.php
```

## Key design file references

| Design file | Implements |
|-------------|-----------|
| `design/pages/connexion.html` | `templates/security/login.html.twig` |
| `design/pages/inscription.html` | `templates/registration/register.html.twig` |
| `design/pages/mot-de-passe-oublie.html` | `templates/password_reset/request.html.twig` |
| `design/pages/reinitialiser-mot-de-passe.html` | `templates/password_reset/reset.html.twig` |
| `design/assets/auth.css` | `assets/styles/pages/auth.css` (copy verbatim) |
| `design/assets/auth.js` | Split into Stimulus controllers in `assets/controllers/` |
