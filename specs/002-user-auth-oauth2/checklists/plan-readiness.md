# Plan Readiness Checklist: Inscription et Authentification (Classique + Google OAuth2)

**Purpose**: Validate that requirements are unambiguous and complete enough to begin technical planning — no blocking unknowns remain for the planner
**Created**: 2026-05-23
**Feature**: [spec.md](../spec.md)
**Audience**: Author self-check before `/speckit-plan`
**Depth**: Standard

---

## Requirement Completeness

- [x] CHK001 - Are password complexity/strength rules (minimum length, character requirements) documented for the registration form? [Gap, FR-001] → min 8 chars, no character type constraints
- [x] CHK002 - Is the specific password hashing algorithm (bcrypt, argon2i, etc.) required or left to implementation choice? [Clarity, FR-003] → **Resolved**: bcrypt cost ≥ 13 required (FR-003)
- [x] CHK003 - Are the exact Google OAuth2 scopes required (`email`, `profile`, `openid`) documented? [Gap, FR-012] → openid + email + profile
- [x] CHK004 - Are session cookie security attributes (Secure, HttpOnly, SameSite) specified or left to implementation? [Gap, FR-006] → Secure + HttpOnly + SameSite=Lax required
- [x] CHK005 - Are all mandatory form fields for classic registration explicitly enumerated beyond pseudo/email/password? [Completeness, FR-001] → **Resolved**: pseudo, adresse email, mot de passe, confirmation du mot de passe, case à cocher RGPD — aucun autre champ obligatoire (FR-001)
- [x] CHK006 - Is the content and required fields of the intermediate GDPR consent page defined? [Gap, FR-019] → consent checkbox + privacy policy link + Confirm/Cancel buttons
- [x] CHK007 - Are Google OAuth2 redirect URI requirements documented (allowed origins, callback path)? [Gap, FR-010] → **Resolved**: `/auth/google/callback` (FR-010)

## Requirement Clarity

- [x] CHK008 - Is the brute force attempt counter defined as "consecutive failures" or "total failures within a window"? [Ambiguity, FR-008] → consecutive failures; resets on successful login
- [x] CHK009 - Is the automatic IP unblock mechanism after 15 minutes documented (passive expiry vs explicit reset)? [Clarity, FR-008] → passive expiry, no admin action required
- [x] CHK010 - Is the "30-day persistent cookie" refresh strategy specified (does activity extend the 30 days, or is it a fixed expiry)? [Ambiguity, FR-006] → fixed expiry from creation date
- [x] CHK011 - Is the previous-URL capture and preservation mechanism for post-login redirection specified? [Clarity, FR-005] → **Resolved**: Symfony Security `_security.target_path` session variable (FR-025)
- [x] CHK012 - Is "message d'erreur générique" for failed login defined with example wording, or is exact copy left to implementation? [Clarity, FR-007] → **Resolved**: *« Identifiant ou mot de passe incorrect. »* (FR-007)
- [x] CHK013 - Is the auto-login mechanism after classic registration specified (session creation method, same as login flow)? [Clarity, US1-Scenario1] → **Resolved**: standard session (browser-close expiry, no persistence) via FR-022
- [x] CHK014 - Is the rate-limit counter for registration (5/hour/IP) defined with a sliding window or fixed window reset? [Ambiguity, FR-021] → sliding window (60-minute rolling)

## Edge Case Coverage

- [x] CHK015 - Is the account merge flow (FR-015) fully specified for cases where the existing Google account has profile data that may conflict with the new password registration? [Completeness, FR-015] → existing Google profile preserved; only password field added
- [x] CHK016 - Is the incremental pseudo-suffix algorithm (FR-018) bounded — is there a maximum retry limit or infinite loop protection specified? [Clarity, FR-018] → no limit; increment until available pseudo found
- [x] CHK017 - Is the cleanup/expiry of Google data temporarily stored in session (during GDPR consent step) documented — what happens on timeout or browser close? [Gap, FR-019] → cleared at browser session end (close or expiry)
- [x] CHK018 - Is the behavior when both email and pseudo collide during Google account creation (FR-012 + FR-018) specified? [Coverage, FR-012] → **Resolved**: email checked first — if email exists, FR-011 applies (connect to existing account, no new account created, pseudo conflict irrelevant); if email unknown, new account created with pseudo suffix per FR-018. Documented in clarifications.

## Non-Functional Requirements

- [x] CHK019 - Are the audit log format, destination (file/DB/external), and rotation/retention requirements specified for FR-020? [Clarity, FR-020] → application log file (Monolog); no DB table required
- [x] CHK020 - Are GDPR data retention limits for authentication logs documented? [Gap, FR-020] → **Resolved**: log retention is infrastructure-managed (deployment configuration) — no application-level retention limit defined for v1 (FR-020)
- [x] CHK021 - Are the timing boundaries in SC-001 and SC-002 ("moins de 2 minutes", "moins de 30 secondes") defined with explicit start/end measurement points? [Measurability, SC-001, SC-002] → page load to post-auth redirect, normal conditions
- [x] CHK022 - Are there requirements for authentication endpoint response time under load (beyond user-perceived time)? [Gap] → **Resolved**: out of scope v1 — no server-side performance SLA defined under load

## Dependencies & Scope Boundaries

- [x] CHK023 - Is the Google OAuth2 credentials management strategy documented (environment variable, secrets manager, per-environment config)? [Gap, FR-010] → env vars GOOGLE_CLIENT_ID + GOOGLE_CLIENT_SECRET per environment
- [x] CHK024 - Is the v1 exclusion of "adding a password to a Google-only account" documented in a way that prevents accidental implementation leakage during planning? [Completeness, Assumptions] → **Resolved**: documented in Assumptions ("hors scope v1 — peut faire l'objet d'une évolution ultérieure")
- [x] CHK025 - Is the absence of email verification on classic registration documented as a known security trade-off (spam accounts, credential enumeration risk)? [Assumption] → **Resolved**: documented in Assumptions as accepted trade-off for v1 ("peut être ajoutée ultérieurement")
- [x] CHK026 - Are CSRF protection requirements tied to a specific mechanism or left to implementation choice? [Clarity, FR-009] → implementation choice (framework built-in preferred); scope of protection explicitly defined in FR-009

## Notes

- All items resolved — spec is ready for `/speckit-plan`
- Password recovery confirmed out of scope — no items generated for it
- Email verification on classic registration confirmed out of scope v1
