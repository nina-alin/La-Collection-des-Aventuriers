# Route Contracts

**Feature**: `002-user-auth-oauth2` | **Date**: 2026-05-23

---

## Public Routes (no authentication required — FR-024)

| Route | Method(s) | Handler | Notes |
|-------|-----------|---------|-------|
| `/` | GET | `DefaultController::index` | Existing home page |
| `/connexion` | GET | `SecurityController::login` | Renders login form |
| `/connexion` | POST | Symfony Security `form_login` | Firewall handles; controller not invoked |
| `/inscription` | GET, POST | `RegistrationController::register` | Registration form |
| `/auth/google` | GET | `OAuth2Controller::redirectToGoogle` | Starts OAuth2 flow |
| `/auth/google/callback` | GET | Symfony Security (`GoogleAuthenticator`) | Firewall handles |
| `/auth/google/consent` | GET, POST | `OAuth2Controller::rgpdConsent` | RGPD consent gate |
| `/politique-de-confidentialite` | GET | (out of scope — assumed to exist) | |

## Protected Routes (redirect to `/connexion` if unauthenticated — FR-024)

| Route | Method | Minimum Role | Notes |
|-------|--------|-------------|-------|
| `/deconnexion` | POST | `IS_AUTHENTICATED_REMEMBERED` | Handled by Symfony Security; CSRF required |
| All other routes | * | `IS_AUTHENTICATED_REMEMBERED` | Default deny via `access_control` |

---

## Route Name Map

| Symfony Route Name | URL |
|-------------------|-----|
| `app_home` | `/` |
| `app_login` | `/connexion` |
| `app_logout` | `/deconnexion` |
| `app_register` | `/inscription` |
| `app_oauth_google` | `/auth/google` |
| `app_oauth_google_callback` | `/auth/google/callback` |
| `app_oauth_google_consent` | `/auth/google/consent` |

---

## Post-Auth Redirect Map (FR-025, FR-027)

| Trigger | Destination |
|---------|-------------|
| Successful login (classic) | `_security.target_path` or `/` |
| Successful registration | `/` (auto-login, standard redirect) |
| Successful Google OAuth2 | `_security.target_path` or `/` |
| Logout | `/connexion` |
| RGPD consent refused / cancelled | `/connexion` + flash FR-027 |
| `email_verified: false` or absent | `/connexion` + flash FR-016 |
| Google service unavailable | `/connexion` + flash FR-017 |
| Google scopes not granted | `/connexion` + flash FR-026 |
| Unauthenticated access to protected route | `/connexion` (Symfony Security automatic) |
| IP blocked (login) | `/connexion` + message FR-008 |
| IP blocked (registration) | `/inscription` + message FR-021 |
