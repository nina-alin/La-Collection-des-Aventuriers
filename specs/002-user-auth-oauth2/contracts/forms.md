# Form Contracts

**Feature**: `002-user-auth-oauth2` | **Date**: 2026-05-23

---

## Registration Form (`/inscription`)

**Class**: `App\Form\RegistrationFormType`
**Method**: POST to `/inscription`
**CSRF**: Symfony Form built-in (field `registration[_token]`)
**Rate limit**: 5 attempts/hour/IP, sliding window — FR-021

| Field | Symfony Type | Validation | Error Messages |
|-------|-------------|------------|----------------|
| `pseudo` | `TextType` | `NotBlank`, `Regex(/^[a-zA-Z0-9_]{3,30}$/)` | "Ce pseudo n'est pas disponible." (duplicate); "Le pseudo doit comporter entre 3 et 30 caractères (lettres, chiffres, underscore)." (format) |
| `email` | `EmailType` | `NotBlank`, `Email(mode:"html5")` | "Cette adresse email est déjà associée à un compte." (duplicate) |
| `plainPassword` | `RepeatedType(PasswordType)` | `NotBlank`, `Length(min:8)` | "Les mots de passe ne correspondent pas." (mismatch); "Le mot de passe doit contenir au moins 8 caractères." (too short) |
| `rgpdConsent` | `CheckboxType` | `IsTrue` | "Vous devez accepter les conditions pour créer un compte." |
| `_token` | hidden | CSRF (auto) | Generic form error |

**Validation strategy**: All errors displayed simultaneously (FR-023).
**On success**: Creates `User`, logs in automatically (standard session — no remember_me), redirects to `/`.
**On IP block**: Renders form with "Trop de tentatives. Réessayez dans X minutes." (FR-021).

---

## Login Form (`/connexion`)

**Handler**: Symfony Security `form_login` firewall
**Method**: POST to `/connexion`
**CSRF**: `csrf_parameter: _csrf_token`, `csrf_token_id: authenticate`
**Rate limit**: 10 consecutive failures/IP → 15 min block — FR-008

| Field | HTML Name | Notes |
|-------|-----------|-------|
| Email | `_username` | Symfony Security standard identifier |
| Password | `_password` | Symfony Security standard password |
| Remember Me | `_remember_me` | Checkbox; activates 30-day signed cookie (FR-006) |
| CSRF token | `_csrf_token` | Required; validated by Symfony Security |

**On failure**: Redirect to `/connexion`; flash "Identifiant ou mot de passe incorrect." (FR-007).
**On IP block**: `/connexion` rendered with "Trop de tentatives. Réessayez dans X minutes." (FR-008).
**Remember Me cookie**: `Secure; HttpOnly; SameSite=Lax`; fixed 30-day TTL from creation; deleted on logout (FR-006).

---

## RGPD Consent Form (`/auth/google/consent`)

**Handler**: `OAuth2Controller::rgpdConsent` (GET) + `OAuth2Controller::rgpdConsentSubmit` (POST)
**Method**: POST to `/auth/google/consent`
**CSRF**: Manual `CsrfTokenManager` (field `_token`, token id `google_consent`)

| Field | Type | Notes |
|-------|------|-------|
| `rgpdConsent` | checkbox | Must be checked to submit |
| `_token` | hidden | CSRF token |
| Confirm button | submit | Creates account + logs in |
| Cancel button | submit / link | Treated as refusal |

**Page contents** (FR-019): Consent checkbox + link to `/politique-de-confidentialite` + Confirm + Cancel buttons. Google data NOT displayed to user.
**On consent**: Create User (Google), login, redirect to `/`.
**On refusal / cancel / page close / navigate away**: No account created; flash FR-027; redirect to `/connexion`.
**Session data**: Temporary Google userinfo stored in session under `_google_oauth_pending`; cleared at browser session end regardless of outcome (FR-019).

---

## "Se connecter avec Google" Button

| Property | Value |
|----------|-------|
| Location | `/connexion` + `/inscription` |
| Action | `GET /auth/google` |
| Loading state | Spinner shown on click during redirect to Google (FR-010) |
| Scopes | `openid email profile` |
| Required grants | `email` AND `profile` — rejection triggers FR-026 if missing |
