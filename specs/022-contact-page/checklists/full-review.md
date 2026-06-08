# Full Review Checklist: Page "Nous Contacter" fonctionnelle

**Purpose**: Validate requirements quality across all domains (form UX, backend/security, navigation, visual conformance) before implementation — thorough release-gate depth with ambiguity flagging
**Created**: 2026-06-08
**Feature**: [spec.md](../spec.md)

## Form UX & Validation Requirements

- [x] CHK001 — Are visual "required" indicators (asterisks) defined for each field's initial state before any user interaction? [Gap, Completeness]
  > **PASS** — design/contact.html defines it exactly: prenom*, nom*, email*, raison*, message* have asterisks; pseudo has none. JS dims prenom/nom asterisks when pseudo is filled.
- [x] CHK002 — Is the identity rule "au moins l'un des deux blocs est complet" in FR-002 semantically consistent with the edge case defining whitespace-only pseudonyme as invalid? [Consistency, Spec §FR-002 vs §Edge Cases]
  > **PASS** — JS uses `pseudo.value.trim().length > 0` for both the asterisk toggle and submission check. Consistent.
- [x] CHK003 — Are the exact error message texts specified for each distinct validation failure (missing identity, invalid email, missing raison, empty message)? [Clarity, Gap]
  > **PASS** — design is authoritative per FR-007. Email: "Merci d'indiquer une adresse de courriel valide." Other fields: visual `is-invalid` class only (no text message). Both are defined in design/contact.html.
- [x] CHK004 — Are loading/pending states for the submit button defined for the duration of the POST request? [Gap]
  > **RESOLVED** — FR-008 updated: submit button is disabled during fetch request to prevent double submission.
- [x] CHK005 — Are maximum character lengths defined for all text fields (prénom, nom, pseudonyme, email, message)? [Gap]
  > **RESOLVED** — FR-006 updated: prénom/nom/pseudo → 100 chars, email → 254 chars (RFC 5321), message → 5 000 chars.
- [x] CHK006 — Is the form's scroll and focus behavior after validation errors appear defined (e.g., scroll to first error, auto-focus)? [Gap]
  > **PASS** — design JS calls `firstInvalid.focus()`. Browser default scroll-to-focus applies.
- [x] CHK007 — Is the behavior when both Prénom+Nom AND Pseudonyme are filled defined consistently between FR-003 and the Edge Cases? [Consistency, Spec §FR-003 vs §Edge Cases]
  > **PASS** — Both agree: pseudo filled → prenom/nom optional (asterisks dim, validation skipped). Design JS confirms.
- [x] CHK008 — Can "SC-004 : immédiatement visible après un envoi réussi" be objectively measured — is a maximum display delay defined? [Ambiguity, Spec §SC-004]
  > **RESOLVED** — "Pas de rechargement de page requis" is the testable criterion; fully covered by FR-008 (AJAX POST). No additional timing threshold needed.
- [x] CHK009 — Can "SC-001 : en moins de 3 minutes" be objectively verified — is it a usability benchmark, a load-time SLA, or an acceptance test criterion? [Ambiguity, Spec §SC-001]
  > **RESOLVED** — SC-001 removed from spec (no backing FR, not testable).
- [x] CHK010 — Are requirements defined for form behavior when JavaScript is disabled (server-side rendering of validation errors)? [Gap, Coverage]
  > **RESOLVED** — JS is a hard prerequisite (FR-006b). Template must include a `<noscript>` notice. A full server-rendered HTML fallback is out of scope (would conflict with the verbatim-JS constraint and require a separate code path).
- [x] CHK011 — Is the confirmation display behavior beyond "s'affiche" specified (duration, dismissibility, placement relative to form)? [Clarity, Spec §FR-008]
  > **PASS** — design shows `.form-success` appears below form actions, stays visible until reset (no auto-hide, no close button).
- [x] CHK012 — Is the reset behavior for non-connected users consistent between FR-009 ("tous les champs sont vidés") and the implicit assumption that there are no default values to restore? [Consistency, Spec §FR-009 vs §Assumptions]
  > **PASS** — Consistent. Assumptions explicitly cover both cases (connected: restore pre-fill; disconnected: clear all).

## Backend & Security Requirements

- [x] CHK013 — Is there a conflict between FR-007 (which does not specify an HTTP status for validation failure) and the Clarifications section ("retourne 422 + JSON") — which is authoritative? [Conflict, Spec §FR-007 vs §Clarifications]
  > **RESOLVED** — FR-007 updated to explicitly state HTTP 422. Clarifications entry updated to match.
- [x] CHK014 — Are HTTP status codes defined for all server response scenarios: success (200?), CSRF failure (403), validation failure (422?), Mailer failure (500)? [Completeness, Spec §FR-007, §FR-016]
  > **RESOLVED** — All four status codes now explicit: 200 (FR-008), 403 (FR-007), 422 (FR-007), 500 (FR-016).
- [x] CHK015 — Is the CSRF rejection response format defined as JSON or HTML (FR-007 implies JSON context but does not specify format for 403)? [Clarity, Spec §FR-007]
  > **RESOLVED** — FR-007 updated: 403 returns `{success: false, message: "Requête invalide."}` in JSON.
- [x] CHK016 — Are input sanitization requirements defined for user-supplied fields (XSS prevention on message content before emailing)? [Gap, Security]
  > **RESOLVED** — Documented as developer responsibility in Assumptions: Twig auto-escapes all output; email HTML risk is low. No explicit FR added.
- [x] CHK017 — Are email header injection prevention requirements defined for fields used in mail headers? [Gap, Security]
  > **RESOLVED** — Documented as developer responsibility in Assumptions: Symfony Mailer (symfony/mime) sanitises header values at the framework level. No explicit FR added.
- [x] CHK018 — Is the email subject format `[Contact] {raison} — {prénom nom / pseudo}` defined for the case where both prénom+nom AND pseudonyme are provided — which identifier takes precedence? [Clarity, Spec §FR-008]
  > **RESOLVED** — FR-008 updated: pseudo takes precedence. Subject format clarified to `[Contact] {raison} — {pseudo si renseigné, sinon prénom nom}`.
- [x] CHK019 — Are rate limiting requirements defined for the `/contact` POST endpoint? [Gap]
  > **RESOLVED** — Explicitly deferred to a dedicated security ticket (documented in Assumptions).
- [x] CHK020 — Are Symfony Mailer configuration requirements fully specified beyond `CONTACT_EMAIL_FROM` and `CONTACT_EMAIL_TO` (SMTP host, port, DSN)? [Completeness, Spec §FR-008]
  > **PASS** — `MAILER_DSN` is already configured in `config/packages/mailer.yaml` and pre-exists. Only the two new vars are needed.
- [x] CHK021 — Is "sans exposer les détails techniques" in FR-016 quantified — what constitutes a technical detail? [Ambiguity, Spec §FR-016]
  > **PASS** — FR-016 specifies the exact response payload: `{success: false, message: "Une erreur est survenue, veuillez réessayer."}`. The constraint is implicit but the concrete response is defined.
- [x] CHK022 — Are logging requirements defined for form submissions (success, Mailer failure, CSRF rejection, validation failure)? [Gap]
  > **RESOLVED** — Explicitly deferred to a dedicated ticket (documented in Assumptions).

## Navigation & Internal Linking Requirements

- [x] CHK023 — Are requirements defined for all existing "Devenir modérateur" references beyond the footer — are other templates or pages that may reference this link covered? [Completeness, Spec §FR-011]
  > **PASS** — Only one Twig template references it: `templates/components/Layout/Footer.html.twig:40`. The occurrences in `design/mentions-legales.html`, `design/cgu.html`, `design/confidentialite.html` are static prototypes outside the Twig layer. FR-011 scope is correct.
- [x] CHK024 — Is the Symfony route name for `/contact` defined in requirements (for use in `path()` calls)? [Gap]
  > **RESOLVED** — Route name `app_contact` confirmed by convention (matches `app_mentions_legales` pattern). Documented in Assumptions.
- [x] CHK025 — Are the sidebar panel link labels in FR-013 confirmed to exactly match the labels in `design/contact.html`? [Consistency, Spec §FR-013]
  > **PASS** — "Suggérer un livre", "Salle de modération", "Conditions d'utilisation" all match design/contact.html exactly.
- [x] CHK026 — Is the temporary `#` link for "Conditions d'utilisation" documented with an explicit follow-up ticket reference? [Completeness, Spec §FR-013]
  > **RESOLVED** — Acceptable gap: the intent ("ticket CGU dédié") is documented. No ticket ID exists yet; creation is out of scope for this ticket.
- [x] CHK027 — Are the link destinations in FR-013 consistent with the acceptance scenarios in User Story 4? [Consistency, Spec §FR-013 vs §US4]
  > **PASS** — All three links consistent across FR-013 and US4 scenarios.

## Visual Conformance Requirements

- [x] CHK028 — Is "visuellement identique" in FR-015 and SC-006 quantified with a testable tolerance? [Ambiguity, Spec §FR-015, §SC-006]
  > **RESOLVED** — FR-015 and SC-006 updated: component-level match (layout, spacing, colors, typography); minor inter-browser rendering differences acceptable.
- [x] CHK029 — Are dark mode and light mode requirements addressed explicitly, or is it assumed `design/contact.html` fully covers both themes? [Clarity, Spec §SC-006]
  > **PASS** — SC-006 explicitly names both modes. Design uses CSS vars + `[data-theme="dark"]` overrides throughout.
- [x] CHK030 — Are responsive/mobile breakpoints specified with concrete viewport widths? [Clarity, Gap]
  > **PASS** — Design defines: 480px, 560px, 720px, 920px. Spec delegates to design as source of truth.
- [x] CHK031 — Is there a requirement specifying whether the JavaScript in `design/contact.html` must be used verbatim or may be refactored? [Ambiguity, Spec §Assumptions]
  > **PASS** — Assumptions explicitly state "repris tel quel dans le template Twig (ou dans un fichier JS séparé)". Verbatim is a hard constraint.

## Acceptance Criteria Quality & Cross-Cutting

- [x] CHK032 — Are all success criteria in §SC traceable to at least one functional requirement? [Traceability, Spec §SC]
  > **RESOLVED** — SC-001 removed. SC-004 covered by FR-008. Remaining SC-002→FR-010, SC-003→FR-007, SC-005→FR-011/FR-012, SC-006→FR-015. All traceable.
- [x] CHK033 — Is the "no database persistence" constraint explicitly stated in Requirements, or only in Assumptions? [Clarity, Spec §Key Entities, §Assumptions]
  > **PASS** — Stated in both Key Entities ("Aucune persistance en base n'est spécifiée") and Assumptions. Acceptable; no FR for persistence is also implicit confirmation.
- [x] CHK034 — Are accessibility requirements defined for the form (ARIA attributes, keyboard navigation, screen reader support)? [Gap]
  > **RESOLVED** — Implicitly covered by FR-015 (design is authoritative). Design implements `aria-invalid="true"` on invalid fields, `role="status"` on the success banner, and `focus()` on first invalid field. These must be preserved verbatim per FR-031/Assumptions.
- [x] CHK035 — Is the pré-remplissage behavior for connected users without a pseudonyme in their profile consistent between the Edge Cases section and FR-010? [Consistency, Spec §FR-010 vs §Edge Cases]
  > **PASS** — Edge cases: "champ Pseudonyme laissé vide". FR-010: "pré-remplis avec les données de son profil" (no pseudo = no data = field left empty). Consistent.

---

## Additional Finding (Resolved)

**CONFLICT — Dropdown options: FR-005 vs design/contact.html** → **RESOLVED**

FR-005 initial draft listed 7 options; `design/contact.html` had 9. The two extras ("Je souhaite signaler une erreur dans une fiche", "Je souhaite suggérer un livre ou une œuvre") are intentional. FR-005 updated to include all 9 options.
