# Edge Cases & Exception Flows Requirements Quality Checklist: Page Contributeur — Suggestions

**Purpose**: Pre-planning gate — validate edge case and exception flow requirement completeness, clarity, and measurability before implementation planning
**Created**: 2026-05-30
**Feature**: [spec.md](../spec.md)

## Requirement Completeness

- [x] CHK001 - Are recovery requirements defined after a network failure mid-wizard (which data is preserved, what error is shown, can user retry)? [Completeness, Gap] → FR-052
- [x] CHK002 - Are requirements defined when the polling endpoint (FR-021) fails — does the UI show an error, retry silently, or degrade gracefully? [Completeness, Gap] → FR-043
- [x] CHK003 - Is behavior defined when server-side image recadrage (auto-crop) fails after upload — what error is shown and is the upload slot cleared? [Completeness, Gap] → FR-054
- [x] CHK004 - Are requirements defined when the uniqueness check endpoint (FR-008) returns a network error — does the field block or allow submission? [Completeness, Gap] → FR-055
- [x] CHK005 - Is behavior defined for partial ISBN input — what states are shown before 10 or 13 digits are reached? [Completeness, Spec §FR-010] → FR-034
- [x] CHK006 - Is the transition from 19→20 pending suggestions defined — does the submit button disable in real-time or only on page reload? [Completeness, Spec §FR-018] → FR-039 (polling refreshes quota within 30s)
- [x] CHK007 - Are requirements defined for concurrent submission from the same user in two browser tabs? [Completeness, Gap] → FR-040 (client-side button disable) + server-side 400 on quota exceeded
- [x] CHK008 - Are requirements defined for session expiration mid-wizard — is the user redirected, warned, or shown a resumable state? [Completeness, Gap] → FR-053
- [x] CHK009 - Are empty-state requirements defined for "Mon suivi" when the contributor has zero suggestions? [Completeness, Gap] → FR-042
- [x] CHK010 - Are requirements defined for the case where ContributorLevel data is missing or not yet computed (new user, no contributions)? [Completeness, Gap] → Assumptions (rang le plus bas affiché, taux = "—")

## Requirement Clarity

- [x] CHK011 - Is the "fiche source supprimée entre le chargement et la soumission" edge case defined with specific error message content, not just "message clair"? [Clarity, Spec §Edge Cases] → FR-056
- [x] CHK012 - Is "sans perdre les données saisies aux étapes précédentes" (FR-015) defined for file upload errors — which data is preserved? [Clarity, Spec §FR-015, Edge Cases] → FR-036 (étapes 1 et 2 préservées; slot upload vidé)
- [x] CHK013 - Is the pending count check (FR-018) defined as client-side, server-side validation, or both — and what happens if they disagree? [Clarity, Spec §FR-018] → FR-039 (client au rendu initial + polling; server authoritative)
- [x] CHK014 - Is "bouton désactivé pendant la soumission" (slow network edge case) defined with specific visual feedback (spinner, disabled state, progress indicator)? [Clarity, Spec §Edge Cases] → FR-040
- [x] CHK015 - Is the autocomplete fallback to "saisie texte libre" (FR-009) defined — is it triggered automatically or requires a user action? [Clarity, Spec §FR-009] → FR-031 (automatique si timeout 3s ou 5xx)

## Requirement Consistency

- [x] CHK016 - Is error handling for unavailable autocomplete (FR-009 fallback to text libre) consistent with the general validation error style defined in FR-007? [Consistency, Spec §FR-007, FR-009] → FR-031 (helper text); FR-034 (consistent state system)
- [x] CHK017 - Is the hard cap of 50 suggestions in FR-021 and SC-005 consistent — does the polling endpoint enforce the same cap as the initial page load? [Consistency, Spec §FR-021, SC-005] → FR-049 (same polling on all viewports; cap enforced by endpoint)
- [x] CHK018 - Are file upload error messages consistent in style and structure with the general field validation error pattern (FR-007)? [Consistency, Spec §FR-007, FR-015] → FR-036 (slot vidé); FR-063 (a11y); style governed by FR-026 design reference
- [x] CHK019 - Is the server-side error response for a deleted source fiche consistent with other submission failure error formats? [Consistency, Gap] → FR-056 (message explicite défini; format cohérent avec FR-057)

## Scenario Coverage

- [x] CHK020 - Are requirements defined for duplicate submission prevention (double-click on submit, form re-submission via browser back)? [Coverage, Gap] → FR-040
- [x] CHK021 - Are requirements defined for the case where the image server-side crop produces a file that exceeds a size or quality threshold? [Coverage, Gap] → Out of scope (Assumptions: server crops 3×4 without quality threshold)
- [x] CHK022 - Are requirements defined when the user reaches step 4 with a network error already active (e.g., uniqueness check unresolved)? [Coverage, Gap] → FR-052 (submit désactivé pendant erreur réseau) + FR-055 (champ neutre si endpoint indisponible)
- [x] CHK023 - Are requirements defined when an autocomplete-selected entity is deleted from the database between selection and submission? [Coverage, Gap] → FR-056
- [x] CHK024 - Are requirements defined for partial failure where some (but not all) fields fail server-side validation on submission? [Coverage, Gap] → FR-057
- [x] CHK025 - Are requirements defined for the "actions contextuelles" rendering when a SuggestionRefusal contains an unrecognized action key not in the predefined list? [Coverage, Spec §FR-023] → FR-045

## Acceptance Criteria Quality

- [x] CHK026 - Are the edge cases in the spec written as requirements or as informal observations — can each be verified against a measurable outcome? [Measurability, Spec §Edge Cases] → Edge cases section updated with FR references; each now traceable
- [x] CHK027 - Is "soumission doit échouer avec un message clair" (deleted source fiche) measurable — is the exact error message or error pattern specified? [Measurability, Spec §Edge Cases] → FR-056
- [x] CHK028 - Is the recidivism scenario for the 20-pending quota (suggestions validated → quota drops → submit re-enabled) covered by a measurable acceptance scenario? [Measurability, Spec §FR-018] → FR-039 (polling 30s refresh covers re-enable)
