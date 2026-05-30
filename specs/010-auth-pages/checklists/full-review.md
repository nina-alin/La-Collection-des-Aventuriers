# Full-Breadth Requirements Quality Checklist: Auth Pages — Nouveau Design

**Purpose**: Author self-review — identify gaps and ambiguities to resolve before planning
**Created**: 2026-05-30
**Feature**: [spec.md](../spec.md)

---

## Requirement Completeness

- [x] CHK001 — Are the visual proportions of the split-screen panels (left/right ratio) explicitly specified, or left implicit in external maquettes? → Deferred to design assets (`design/assets/auth.css`); FR-001 references them. Accepted. [Completeness, Spec §FR-001]
- [ ] CHK002 — Are the visual states of the password strength gauge defined? (color scheme per level, number of levels, threshold per level) → Deferred to design assets; FR-003 defines the 4 criteria but not visual states. Track in planning. [Completeness, Spec §FR-003]
- [ ] CHK003 — Is the content and copy of the "Vérifie ta boîte de réception" confirmation state fully defined in the spec or only in design assets? → Deferred to design assets. Track in planning. [Completeness, Spec §FR-004]
- [x] CHK004 — Are requirements defined for intermediate breakpoints (769 px–1024 px)? → Resolved: breakpoint is 920 px (design CSS). FR-015 corrected. No intermediate range. [Gap, Spec §FR-015]
- [x] CHK005 — Are content requirements for the registration confirmation email defined? → Resolved: FR-024 added (mirrors FR-013 pattern). [Gap, Spec §FR-013]
- [x] CHK006 — Is the "Renvoyer le lien" rate-limiting captured in a formal FR? → Resolved: FR-025 added (5 req/hour/IP). [Gap, Spec §Assumptions]
- [x] CHK007 — Are CSRF protection requirements explicitly stated for all form submissions? → Resolved: FR-026 added (Symfony native csrf_token). [Gap, Security]

---

## Requirement Clarity

- [x] CHK008 — Are the "maquettes HTML correspondantes" referenced in FR-001 formally identified or linked inside the spec? → Resolved: SC-006 now explicitly names the design files. [Clarity, Spec §FR-001]
- [ ] CHK009 — Is the measurement start point for SC-001 ("moins de 30 secondes") defined? (page load start, first keystroke, or form submission click) → Still open; acceptable as informal UX target. Low priority. [Clarity, Spec §SC-001]
- [x] CHK010 — Is the visual placement and behavior of the submit spinner defined? → Resolved: FR-023 updated to "en remplacement du libellé". [Clarity, Spec §FR-023]
- [ ] CHK011 — Is the content and visual structure of the token error page defined? FR-007 says "un message d'erreur clair" but specifies neither copy nor layout. → Edge case resolution added (second-tab scenario). Full copy deferred to design. [Clarity, Spec §FR-007]
- [x] CHK012 — Is the term "full logout everywhere" (FR-008/Clarifications) defined in terms of concurrent sessions on multiple devices? → Resolved: FR-008 updated to "tous les appareils". [Clarity, Spec §FR-008]

---

## Requirement Consistency

- [ ] CHK013 — Is the brute-force "bandeau d'avertissement" (US1 scenario 5) captured in a numbered FR? US1 describes it but no FR explicitly owns the UI state. → Existing `SecurityController` already passes `brute_blocked` and `remaining_minutes` to the template. Behavior is existing; no new FR needed. Template implementation in planning. [Consistency, Spec §US1]
- [x] CHK014 — Does the "e-mail déjà utilisé" message (US2 scenario 6) conflict with account enumeration protection? → Resolved: US2 scenario 6 updated to "Ces informations sont déjà utilisées". [Consistency, Conflict, Spec §US2]
- [x] CHK015 — Does FR-014 (Google OAuth on inscription → direct redirect) explicitly exclude FR-004's "Vérifie ta boîte de réception" state? → FR-014 text already explicit ("sans état de confirmation e-mail"). Accepted. [Consistency, Spec §FR-014]
- [x] CHK016 — Does FR-011 ("logique backend NE DOIT PAS être modifiée") conflict with FR-022 (blocking unverified email logins)? → Resolved: FR-011 scoped to specific components. FR-022 is new behavior requiring `User.isEmailVerified`. Conflict removed. [Conflict, Spec §FR-011, §FR-022]

---

## Acceptance Criteria Quality

- [x] CHK017 — Can SC-006 ("visuellement conformes aux maquettes validées") be objectively measured? → Resolved: SC-006 now explicitly names the design HTML files. [Measurability, Spec §SC-006]
- [x] CHK018 — Is SC-007 "ou équivalent" bounded to a defined set of acceptable audit tools? → Resolved: SC-007 now specifies "axe-core v4+". [Clarity, Spec §SC-007]
- [x] CHK019 — Is there a success criterion for pseudo validation correctness (FR-018)? → Resolved: SC-008 added. [Gap, Spec §FR-018]

---

## Scenario Coverage

- [x] CHK020 — Are requirements defined for state preservation when a user navigates away from the confirmation state and returns? → Resolved: Edge case added (state not preserved, form shown again). [Coverage, Edge Case]
- [x] CHK021 — Are browser back/forward navigation requirements defined for multi-state pages? → Resolved: Edge case added (browser default, no preservation). [Coverage]
- [x] CHK022 — Are requirements defined for the case where a Google OAuth email matches an existing password account? → Resolved: FR-028 added (auto-link accounts). [Coverage]
- [x] CHK023 — Is the exact error behavior for the second-tab token usage scenario defined? → Resolved: Edge case updated (explicit error + link to "Mot de passe oublié", same as FR-007). [Coverage, Spec §Edge Cases]
- [ ] CHK024 — Are animation/transition requirements defined for state switches? → Intentionally out of spec scope; deferred to design/implementation. [Coverage, Gap — accepted]

---

## Non-Functional Requirements

- [x] CHK025 — Are color contrast requirements defined specifically for the new split-screen design assets? → FR-017 (≥4.5:1) applies to all auth pages; sufficient. Accepted. [Spec §FR-017]
- [ ] CHK026 — Are page load performance requirements defined? SC-001–SC-003 measure task completion, not page rendering. → Out of scope for this feature; no page-load SLA needed. [Gap — accepted]
- [x] CHK027 — Are rate-limiting values for login brute-force documented? → Resolved: FR-011 updated with actual thresholds (10 failures → 15 min block). [Spec §FR-011]

---

## Dependencies & Assumptions

- [x] CHK028 — Is the `ResetPasswordToken` entity validated to store/reference the user's email? → `ResetPasswordToken` links to `User` via relation; email extractable from token→user. Accepted. [Assumption, Spec §Assumptions]
- [x] CHK029 — Are requirements defined for email delivery failure? → Resolved: FR-027 added (show error, no confirmation state switch). [Assumption, Gap]

---

## Ambiguities & Conflicts

- [x] CHK030 — Is the atomicity of FR-016 token invalidation defined? → Resolved: FR-016 updated with atomicity requirement and failure recovery path. [Ambiguity, Spec §FR-016]

---

## Notes

- CHK002, CHK003, CHK011, CHK024, CHK026 accepted as design-deferred or out-of-scope; no action needed before planning.
- CHK009 and CHK013 remain open but are low-priority; planning can proceed.
- FR-011 is the primary scope guard — now scoped to specific components; see gap-resolution clarifications.
