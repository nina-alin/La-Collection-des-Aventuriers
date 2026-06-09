# UX Requirements Quality Checklist: Salle de Modération — Intégration du Design

**Purpose**: Validate visual/UX and technical contract requirements quality before planning
**Created**: 2026-06-09
**Feature**: [spec.md](../spec.md)

## Requirement Completeness — Visual States

- [x] CHK001 - Is the "état vide/célébration" visual appearance fully defined (illustration, message text, call-to-action)? [Gap, Edge Cases] — skeleton display (greyed-out comparator layout); documented in Clarifications
- [x] CHK002 - Are loading skeleton/placeholder requirements specified for the comparator while a suggestion loads? [Gap] — intentionally unspecified; implementation-defined
- [ ] CHK003 - Are spinner visual requirements defined (size, position, inline vs overlay) for approve/refuse button states? [Gap, Spec §FR-017] — design has no spinner variant
- [ ] CHK004 - Is the toast error message content specified (generic string or field-level context)? [Gap, Spec §FR-017]
- [x] CHK005 - Are visual requirements defined for disabled buttons (opacity, cursor, affordance)? [Gap, Spec §FR-002/FR-017] — design system tokens cover this; delegated to design file
- [x] CHK006 - Is the visual appearance of the "INCHANGÉ" fields specified (grisé vs masqué — one or both options, toggle or default state)? [Clarity, Spec §FR-008] — design file shows grisé as default; "masquable" = toggle; both valid
- [x] CHK007 - Is the visual appearance of tag/pill states (estompé, barré, bordure verte) quantified with specific CSS properties or design tokens? [Clarity, Spec §FR-012] — design file defines concrete CSS; SC-008 defers to design file as authority
- [x] CHK008 - Are visual requirements for the diff counter format defined (exact separator, singular/plural form for French)? [Clarity, Spec §FR-014] — design shows example "+5 ajouts · 3 remplacements · 0 suppression"; plural rules implementation-defined

## Requirement Completeness — Mobile & Responsive

- [x] CHK009 - Is the sidebar "La Suite" behavior specified for mobile viewports (< 880px) — hidden, collapsed, or tab-shifted? [Gap, Spec §FR-013/FR-018] — shown stacked below tab panel; FR-018 updated
- [ ] CHK010 - Is the action bar (Modifier/Refuser/Valider) layout specified for mobile viewports? [Gap]
- [ ] CHK011 - Is Vue Tableau display behavior specified for mobile viewports? [Gap] — gestion-table has `min-width: 720px` in design; overflow-x scroll implied but not specified in spec
- [x] CHK012 - Is the breakpoint for "sticky sidebar visible" (SC-005 mentions ≥ 1100px) consistent with the 880px breakpoint for tab layout (FR-013)? [Consistency, Spec §FR-013/SC-005] — confirmed consistent: `.flux` 2-col at ≥1100px (design), tabs at <880px (FR-013); non-overlapping
- [x] CHK013 - Is the behavior of the two-column comparator between 880px and 1100px specified? [Gap, Spec §FR-007/FR-013] — sidebar stacks below comparator (no sticky column); FR-018 updated

## Requirement Clarity — Measurability

- [x] CHK014 - Is "visuellement conforme à la maquette de référence" (SC-008) an objectively measurable success criterion? [Clarity, Spec §SC-008] — design file exists at `design/pages/moderation.html` with explicit CSS classes; conformance = use prescribed classes
- [ ] CHK015 - Is "instantanément" (SC-004) for Vue Flux ↔ Vue Tableau toggle quantified with a time threshold? [Clarity, Spec §SC-004]
- [ ] CHK016 - Is "filtrage dynamique en temps réel" (FR-025) quantified with a debounce delay or response-time threshold? [Clarity, Spec §FR-025]
- [ ] CHK017 - Can "en moins de 60 secondes" (SC-001) be tested objectively, or is it a UX heuristic requiring user testing? [Measurability, Spec §SC-001]

## Requirement Consistency — Cross-Feature Alignment

- [x] CHK018 - FR-002 vs FR-023 consistency for empty-queue case? [Consistency, Spec §FR-002/FR-023] — not a conflict: FR-002 covers empty-queue state (button disabled), FR-023 covers non-empty direct toggle; disjoint scenarios
- [ ] CHK019 - FR-022 says "croix" opens same refuse modal as FR-016 — are CSRF token, validation, next-suggestion behaviors explicitly reused or restated? [Consistency, Spec §FR-016/FR-022]
- [ ] CHK020 - FR-021 says "même comportement que FR-017" — is CSRF token handling for table row validate button explicitly covered? [Consistency, Spec §FR-017/FR-021/FR-030]
- [x] CHK021 - FR-029 vs US-5 consistency? [Consistency, Spec §US-5/FR-029] — not a conflict: both describe same feature (motif de refus read-only); consistent
- [x] CHK037 - Does the spec explicitly state that keyboard shortcuts (V/R/S/M) from the design are out of scope? [Gap, Clarity] — added to Assumptions
- [x] CHK038 - Does the spec explicitly state that Précédente/Suivante navigation arrows are out of scope? [Gap, Clarity] — added to Assumptions

## Requirement Coverage — Interaction Scenarios

- [ ] CHK022 - Are requirements defined for clicking a sidebar queue entry that is already loaded in the comparator? [Gap, Coverage]
- [ ] CHK023 - Are requirements defined for Gestion globale table empty state (no fiches match search/filter)? [Gap, Coverage]
- [ ] CHK024 - Are requirements specified for a suggestion whose `sourceEntityId` refers to a deleted or unpublished entity? [Gap, Edge Cases]
- [x] CHK025 - Are "Modifier" button requirements defined for Vue Tableau context? [Gap, Spec §FR-015/FR-023] — design confirms Modifier is only in action bar (Vue Flux); absent from Vue Tableau row actions by design intent
- [ ] CHK026 - Are requirements specified for sidebar "La Suite" when only one suggestion is in the queue? [Gap, Spec §FR-018]
- [x] CHK039 - Does FR-029 correctly describe the icon — spec said "bouton œil" but design uses an info/exclamation icon? [Conflict, Spec §FR-029] — FR-029 updated to "icône info/exclamation"

## Data Contract Requirements — DiffResult & Normalizers

- [ ] CHK027 - Is the `DiffResult` DTO field structure formally specified for tag/relation fields (does "token-level diff annoté" apply to arrays)? [Clarity, Spec §FR-012/Key Entities]
- [ ] CHK028 - Are the 6 entity normalizer output schemas documented — all fields for BOOK, AUTHOR, ILLUSTRATOR, TRADUCTOR, EDITOR, COLLECTION? [Gap, Spec §Assumptions]
- [ ] CHK029 - Are predefined refusal reasons stored in code (enum/constant) or database? Spec lists 5 options but doesn't specify the source. [Ambiguity, Spec §FR-016/Assumptions]
- [ ] CHK030 - Is the "label FR du champ" static map documented in spec or only implied by implementation? [Clarity, Spec §Key Entities - DiffResult]
- [ ] CHK031 - Are requirements defined for `DiffService` behavior when `formData` contains unexpected nested arrays or null values? [Gap, Edge Cases]

## Non-Functional Requirements Coverage

- [ ] CHK032 - Are accessibility requirements (ARIA roles, keyboard navigation, focus management) specified for approve/refuse modals? [Gap, Coverage] — design has `role="status" aria-live="polite"` on toast but modal accessibility unspecified
- [ ] CHK033 - Are accessibility requirements specified for diff comparator (screen reader announcements for color-coded changes)? [Gap, Coverage]
- [ ] CHK034 - Are performance requirements defined for diff computation on large `formData` payloads? [Gap]
- [ ] CHK035 - Are browser support requirements defined? [Gap]
- [ ] CHK036 - Are requirements specified for rate limiting or double-submit prevention beyond button-disabled? [Gap, Spec §FR-017/FR-030]

## Notes

- CHK002/CHK005/CHK006/CHK007/CHK008/CHK012/CHK014/CHK018/CHK021/CHK025: resolved — design file or clarifications provide answers.
- All original blockers resolved: CHK001, CHK009, CHK013, CHK037, CHK038, CHK039.
- CHK037/CHK038/CHK039: resolved via spec edits (2026-06-09).
