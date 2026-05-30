# UX & Form Requirements Quality Checklist: Page Contributeur — Suggestions

**Purpose**: Pre-planning gate — validate UX and form requirement completeness, clarity, and measurability before implementation planning
**Created**: 2026-05-30
**Feature**: [spec.md](../spec.md)

## Requirement Completeness

- [x] CHK001 - Are transitions between wizard steps specified (forward/backward navigation, data persistence per step)? [Completeness, Gap] → FR-028
- [x] CHK002 - Are all 4 stepper visual states defined (active, done, locked/disabled, error)? [Completeness, Gap] → FR-029
- [x] CHK003 - Is step navigation behavior defined when required fields are incomplete (can user navigate backward freely? forward?) [Completeness, Gap] → FR-028
- [x] CHK004 - Are loading states defined for autocomplete endpoint calls in relational fields? [Completeness, Spec §FR-009] → FR-031
- [x] CHK005 - Are empty-state requirements defined for the autocomplete dropdown (no results found)? [Completeness, Gap] → FR-031
- [x] CHK006 - Is the pré-remplissage bandeau content fully specified (which fields, format, layout)? [Completeness, Spec §FR-004] → FR-051 (mobile); User Story 2 scenario 1 lists fields
- [x] CHK007 - Are toast notification requirements fully specified (duration, dismissal trigger, screen position)? [Completeness, Spec §FR-020] → FR-041
- [ ] CHK008 - Are the "champs obligatoires" explicitly listed per entity type (Livre, Auteur, Éditeur, etc.) for step 2? [Completeness, Spec §FR-006] → **Deferred to plan phase**
- [x] CHK009 - Are requirements defined for the "créer une nouvelle entité à la volée" flow within relational autocomplete fields? [Completeness, Spec §FR-009] → FR-031
- [x] CHK010 - Are requirements defined for drag-and-drop zone visual states (drag-over, invalid file type, uploading progress)? [Completeness, Spec §FR-013] → FR-035
- [x] CHK011 - Are requirements defined for co-auteur addition and removal within the wizard? [Completeness, Spec §FR-009] → FR-033
- [x] CHK012 - Are requirements defined when a user abandons the wizard mid-step (browser back, page close)? [Completeness, Gap] → FR-030

## Requirement Clarity

- [x] CHK013 - Is "retour visuel" in FR-007 quantified with specific icon definitions, colors, and display timing? [Clarity, Spec §FR-007] → FR-034
- [x] CHK014 - Is "visuellement distinguées" in FR-017 (diff display) defined with specific visual properties (color, strikethrough, badge)? [Clarity, Spec §FR-017] → Covered by design reference FR-026 (design/pages/suggestions.html is the visual authority)
- [x] CHK015 - Is "message d'aide contextuel" defined with content rules for each field state (valid, invalid, checking)? [Clarity, Spec §FR-007] → FR-034
- [x] CHK016 - Is "erreur bloquante" vs "erreur non bloquante" distinction formally defined with a rule governing which errors block submission? [Clarity, Spec §FR-012, FR-019] → FR-034
- [x] CHK017 - Is the 500 ms threshold in SC-002 measured from a defined event (keyup, blur, debounce end)? [Clarity, Spec §SC-002] → SC-002 updated + FR-034
- [x] CHK018 - Are "champs obligatoires non renseignés" (FR-019) defined — does this mean any empty required field or only fields with active errors? [Clarity, Spec §FR-019] → FR-034 (empty required field = blocking error)
- [x] CHK019 - Is "bouton Soumettre désactivé" behavior fully specified when multiple disabling conditions coexist (errors + quota + in-flight)? [Clarity, Spec §FR-012, FR-018, FR-019] → FR-039, FR-040

## Requirement Consistency

- [x] CHK020 - Are validation states (is-valid, is-invalid, is-checking) applied consistently across all field types (text, relational, date, ISBN, file)? [Consistency, Spec §FR-007] → FR-034
- [x] CHK021 - Do FR-008 (unicité Titre) and FR-010 (ISBN) use the same icon/state system defined in FR-007? [Consistency, Spec §FR-007, FR-008, FR-010] → FR-034
- [x] CHK022 - Is the "bouton Soumettre désactivé" condition consistent across FR-012, FR-018, and FR-019 without conflicting triggers? [Consistency, Spec §FR-012, FR-018, FR-019] → FR-039, FR-040
- [x] CHK023 - Are date field validation rules consistent for Parution France and Édition originale (same format, same range bounds)? [Consistency, Spec §FR-011] → FR-011 already specifies both equally
- [x] CHK024 - Is behavior when switching mode (Correction → Nouvelle fiche) consistent with User Story 2 scenario 3 and the wizard-step requirements? [Consistency, Spec §FR-004, User Story 2] → FR-028

## Scenario Coverage

- [x] CHK025 - Are requirements defined for switching from "Correction" to "Nouvelle fiche" at step 3 or step 4 (not only at step 1)? [Coverage, Gap] → FR-028
- [x] CHK026 - Are requirements defined when multiple autocomplete relational fields fail simultaneously? [Coverage, Gap] → FR-031 (each field falls back independently) + FR-052
- [x] CHK027 - Are requirements defined for the preview (step 4) when optional fields are empty — which fields are omitted vs shown as empty? [Coverage, Spec §FR-016] → FR-037
- [x] CHK028 - Are requirements defined for the diff display when the source fiche has fields absent in the correction form? [Coverage, Spec §FR-017] → FR-038
- [x] CHK029 - Are requirements defined for the case where autocompletion returns a result matching the source fiche exactly (no diff)? [Coverage, Gap] → FR-038 (0 champs modifiés → submit still active)

## Acceptance Criteria Quality

- [x] CHK030 - Is "prévisualisation en direct" (FR-016) measurable — are the specific fields shown in the preview enumerated? [Measurability, Spec §FR-016] → FR-037
- [x] CHK031 - Is "exactement N champs modifiés" (SC-004) measurable given that form data may include nested or relational fields? [Measurability, Spec §SC-004] → FR-038 (each top-level field = 1, relational = 1)
- [x] CHK032 - Is SC-001 ("moins de 5 minutes") measurable under defined conditions (network speed, pre-filled data or not, device type)? [Measurability, Spec §SC-001] → SC-001 updated with measurement conditions
- [x] CHK033 - Can "l'aperçu reflète les données saisies" (FR-016, User Story 1 scenario 2) be objectively verified — are specific fields and formats listed? [Measurability, Spec §FR-016] → FR-037
