# Requirements Quality Checklist: Inscription et Authentification (Classique + Google OAuth2)

**Purpose**: Validate completeness, clarity, consistency, and measurability of authentication requirements before implementation
**Created**: 2026-05-23
**Feature**: [spec.md](../spec.md)
**Audience**: Author (pre-PR self-review)
**Focus**: Security, RGPD/compliance, OAuth2 external deps, UX & user flows
**Depth**: Standard

---

## Security — Password & Hashing

- [x] CHK001 Is the hashing algorithm explicitly specified (e.g., bcrypt, argon2id), or is "irréversible" the only constraint? [Clarity, Spec §FR-003] → **Resolved**: bcrypt, cost ≥ 13
- [x] CHK002 Are the work factor / cost parameters for password hashing defined (e.g., bcrypt cost ≥ 12)? [Completeness, Gap] → **Resolved**: cost 13 in FR-003
- [x] CHK003 Are pseudo format constraints documented — allowed characters, minimum and maximum length? [Clarity, Gap, Spec §FR-001] → **Resolved**: `^[a-zA-Z0-9_]{3,30}$` in FR-001
- [x] CHK004 Is "adresse email valide" quantified with a specific validation rule (RFC 5322, HTML5 type=email, custom pattern)? [Clarity, Gap, Spec §FR-001] → **Resolved**: HTML5 `type=email` in FR-001

## Security — Session & Cookie

- [x] CHK005 Does the logout requirement (FR-013) explicitly address deletion of the persistent "remember me" cookie for the current device? [Completeness, Spec §FR-013] → **Resolved**: explicit deletion required in FR-006 + FR-013
- [x] CHK006 Are session storage mechanism requirements specified (server-side token, JWT, PHP native session)? [Gap] → **Resolved**: FR-022 defines session cookie (browser-close expiry), Symfony Security default
- [x] CHK007 Is the session duration for a standard (non-"remember me") login defined — expires on browser close, or a fixed inactivity timeout? [Clarity, Gap] → **Resolved**: expires on browser close (FR-022)
- [x] CHK008 Is the auto-connection after classic registration (US1/Scenario 1) specified to use or not use the 30-day persistent session? [Clarity, Gap] → **Resolved**: standard session (FR-022), no implicit persistence

## Security — Rate Limiting & Brute Force

- [x] CHK009 Is the rate limiting storage backend specified (Redis, database, APCu, etc.) to ensure persistence across requests and instances? [Gap] → **Resolved**: counters MUST persist between requests (server-side, non-volatile) per FR-008 + FR-021; technology choice left to implementation
- [x] CHK010 Does FR-008 specify whether blocked attempts during the 15-minute window extend the timer or leave it unchanged? [Ambiguity, Spec §FR-008] → **Resolved**: timer not extended (FR-008)
- [x] CHK011 Is the brute force counter reset condition exhaustive — does a password change or account unlock also reset it, or only a successful login? [Clarity, Spec §FR-008] → **Resolved**: only successful login resets counter (FR-008)
- [x] CHK012 Are rate limiting requirements defined for the RGPD consent page submission in the Google flow? [Coverage, Gap, Spec §FR-021] → **Resolved**: not required — consent page is accessible only within an active OAuth2 session (state parameter required); not an open endpoint
- [x] CHK013 Is the sliding-window algorithm for registration rate limiting (FR-021) fully specified — per-IP only, or also per-email? [Clarity, Spec §FR-021] → **Resolved**: per-IP only (FR-021)

## Security — CSRF & General Protection

- [x] CHK014 Does the CSRF protection requirement (FR-009) explicitly include the OAuth2 `state` parameter as CSRF mitigation for the Google callback endpoint? [Completeness, Spec §FR-009] → **Resolved**: state param + callback validation in FR-009
- [x] CHK015 Are CSRF requirements defined for the logout action endpoint? [Coverage, Gap] → **Resolved**: logout (POST) covered in FR-009
- [x] CHK016 Are security requirements defined for the intermediate RGPD consent page POST (state parameter, replay protection)? [Gap] → **Resolved**: consent page POST covered in FR-009

## RGPD / Compliance

- [x] CHK017 Is consent record persistence defined — must the consent timestamp and user acceptance be stored, and if so where and for how long? [Gap] → **Resolved**: no dedicated record required; account creation event in FR-020 serves as implicit record
- [x] CHK018 Does the spec define whether the privacy policy page/URL linked in consent forms is in scope or out of scope for this feature? [Completeness, Spec §FR-019] → **Resolved**: out of scope, `/politique-de-confidentialite` assumed to exist (FR-019)
- [x] CHK019 Is the behavior defined when a user accepts the RGPD consent page then uses the browser back button before account creation completes? [Edge Case, Gap] → **Resolved**: treated as refusal — no account created (FR-019)
- [x] CHK020 Does the account fusion scenario (FR-015) address whether new RGPD consent is required when adding a password to an existing Google account? [Coverage, Spec §FR-015, §FR-019] → **Resolved**: no re-consent required (FR-015)
- [x] CHK021 Are data minimization justifications documented for storing all four Google fields (`google_id`, `email`, `display_name`, `avatar_url`) under RGPD minimality principle? [Completeness, Spec §Key Entities] → **Resolved**: all four fields serve distinct functional purposes (identity matching, UX display, visual identity) — satisfies minimality; documented in clarifications
- [x] CHK022 Is the error message text shown on RGPD consent refusal (Google flow, US3/Scenario 4) specified? [Clarity, Gap] → **Resolved**: *« Vous devez accepter les conditions pour créer un compte. »* (FR-027)

## OAuth2 / Google Integration

- [x] CHK023 Is the OAuth2 callback route/redirect_uri defined in requirements (path, environment variants)? [Gap, Spec §FR-010] → **Resolved**: `/auth/google/callback` (FR-010)
- [x] CHK024 Are requirements defined for partial scope grant — what if the user authorizes Google but denies email or profile scope? [Coverage, Gap] → **Resolved**: treated as cancellation, redirect to classic login (FR-026)
- [x] CHK025 Is a timeout duration specified for Google API calls before triggering the FR-017 unavailability error path? [Clarity, Gap, Spec §FR-017] → **Resolved**: 10 seconds (FR-010)
- [x] CHK026 Is the exact error message text for Google unavailability (FR-017) defined, or is "message d'erreur explicite" the only specification? [Clarity, Spec §FR-017] → **Resolved**: *« Le service Google est indisponible. Utilisez la connexion classique. »* (FR-017)
- [x] CHK027 Is the error message text displayed when the user cancels Google authorization (US3/Scenario 3) specified? [Clarity, Gap] → **Resolved**: *« Connexion Google annulée. »* (FR-026)
- [x] CHK028 Is the behavior defined when Google returns a `null` or empty `name`/`display_name` field — what pseudo is generated? [Edge Case, Gap, Spec §FR-018] → **Resolved**: use email local-part as base pseudo (FR-018)
- [x] CHK029 Is the behavior defined when Google's `email_verified` field is absent entirely (not just `false`)? [Edge Case, Spec §FR-016] → **Resolved**: absent treated as `false` (FR-016)
- [x] CHK030 Are requirements defined for concurrent Google OAuth2 flows from the same browser (multiple tabs)? [Coverage, Gap] → **Resolved**: each flow uses own `state` param; first to complete creates/logs in; subsequent flows hit FR-011 (existing account) or invalid state error — no additional business requirement needed; documented in clarifications

## UX / User Flows

- [x] CHK031 Is the mechanism for storing and restoring the "previously requested URL" after login specified (query param, session variable, cookie)? [Clarity, Gap, Spec §US2/Scenario 1] → **Resolved**: Symfony Security `_security.target_path` session variable (FR-025)
- [x] CHK032 Is the exact error message text for failed login (FR-007) defined, consistent with the specified text for rate-limiting (FR-008)? [Consistency, Spec §FR-007, §FR-008] → **Resolved**: *« Identifiant ou mot de passe incorrect. »* (FR-007)
- [x] CHK033 Is the exact error message text for duplicate email at registration (US1/Scenario 2) specified? [Clarity, Gap] → **Resolved**: *« Cette adresse email est déjà associée à un compte. »* (FR-001)
- [x] CHK034 Is the exact error message text for duplicate pseudo at registration (US1/Scenario 6) specified? [Clarity, Gap] → **Resolved**: *« Ce pseudo n'est pas disponible. »* (FR-001)
- [x] CHK035 Are validation error priority and display order defined when multiple fields are invalid simultaneously (e.g., duplicate email + duplicate pseudo)? [Completeness, Gap] → **Resolved**: all errors shown simultaneously (FR-023)
- [x] CHK036 Is the logout button/link location within the UI defined (navigation bar, user menu, specific page)? [Completeness, Gap] → **Resolved**: user profile dropdown in nav bar (FR-013)
- [x] CHK037 Is a list of authentication-protected routes defined, or is the protection scope (all routes except public list) specified? [Coverage, Gap, Spec §US4/Scenario 2] → **Resolved**: all routes protected except explicit public list (FR-024)
- [x] CHK038 Are loading/pending state requirements defined for the Google OAuth2 redirect (between click and Google page load)? [Completeness, Gap] → **Resolved**: button shows loading indicator (FR-010)

## Consistency & Conflicts

- [x] CHK039 Are the two rate limiting error message formats consistent — FR-008 (login) and FR-021 (registration) both specify "Réessayez dans X minutes" but use different counter types (fixed block vs sliding window): is the X calculation documented consistently? [Consistency, Spec §FR-008, §FR-021] → **Resolved**: both use same message format; X = minutes remaining; calculation differs by design (fixed vs sliding) — consistent as authored
- [x] CHK040 Is "auto-connect after registration" (US1/Scenario 1) consistent with "connexion réussie resets brute-force counter" (FR-008) — does registration-triggered auto-login reset or initialize a counter? [Consistency, Spec §FR-008] → **Resolved**: registration auto-login is not a login attempt; brute-force counter tracks login failures only — no interaction
- [x] CHK041 Does FR-015 (account fusion) specify whether the merged account's `google_id` linkage is preserved when the user later re-attempts Google login with the same email? [Completeness, Spec §FR-015] → **Resolved**: Google OAuth2 linkage stays active post-fusion (FR-015)

## Non-Functional Requirements

- [x] CHK042 Are SC-001/SC-002/SC-003 timing thresholds measurable in CI or a test environment — are reference hardware/network conditions specified for "conditions normales"? [Measurability, Spec §SC-001, §SC-002, §SC-003] → **Resolved**: "conditions normales" defined in clarifications (wall clock, single user, standard latency, no load) — manual QA acceptance; no CI benchmark required
- [x] CHK043 Is SC-006 ("conformité visuelle avec le Design System") defined with reviewable or measurable criteria (design review step, visual regression tests, component checklist)? [Measurability, Spec §SC-006] → **Resolved**: PR visual review criterion added to SC-006
- [x] CHK044 Are browser and device compatibility requirements specified beyond "desktop et mobile" (minimum browsers, viewport breakpoints, OS)? [Clarity, Gap, Spec §SC-006] → **Resolved**: last 2 major versions of Chrome, Firefox, Safari, Edge (Assumptions)
- [x] CHK045 Are performance requirements defined for pseudo suffix generation (FR-018) in high-collision scenarios — is there an acceptable time bound or a fallback? [Gap, Spec §FR-018] → **Resolved**: no performance requirement defined — collision probability is negligible at expected user volumes; acceptable implementation risk
