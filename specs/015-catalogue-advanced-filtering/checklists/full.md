# Full Coverage Checklist: Catalogue Page & Advanced Filtering

**Purpose**: Pre-planning quality gate — validate requirement completeness, clarity, consistency, and coverage across all dimensions before /speckit-plan
**Created**: 2026-06-01
**Feature**: [spec.md](../spec.md)
**Audience**: Author self-review (Nina)

---

## Requirement Completeness

- [x] CHK001 - Are error handling requirements defined for Live Component failures during the editor search per-keystroke (FR-017)? [Completeness, Gap]
- [x] CHK002 - Are error handling requirements specified when the in-page autocomplete request fails (FR-007)? [Completeness, Gap]
- [x] CHK003 - Are requirements defined for the failure scenario when the slider bounds DB fetch fails at page load (FR-019)? [Completeness, Gap]
- [x] CHK004 - Is the "metadata overlay" content on book card hover specified with exact fields and layout rules (FR-026)? [Completeness, Gap]
- [x] CHK005 - Are requirements defined for rapid consecutive "Appliquer" clicks (concurrent in-flight requests / debounce/disable behavior)? [Completeness, Gap]
- [x] CHK006 - Is it specified whether the "STATUT DANS MA COLLECTION" section is absent from the DOM for guests or hidden via CSS (FR-021)? [Completeness, Spec §FR-021]
- [x] CHK007 - Are requirements defined for URL param values referencing deleted or out-of-range entities (e.g., a non-existent editor ID in query params)? [Completeness, Gap]

## Requirement Clarity

- [x] CHK008 - Is "under 1 second" in SC-001 defined for a specific network/server baseline (e.g., 4G, production load)? [Clarity, Spec §SC-001]
- [x] CHK009 - Is the desktop/mobile breakpoint defined as an explicit pixel value in the FRs? (Only ≤768 px appears in the US-2 narrative, not in FR-001/FR-003.) [Clarity, Spec §FR-001, §FR-003]
- [x] CHK010 - Is "optionally showing a badge of active criteria count" in FR-015 clarified — which accordion sections show the count badge and which do not? [Clarity, Spec §FR-015]
- [x] CHK011 - Are the book card states in FR-026 defined as mutually exclusive or combinable (e.g., can a card be both favourite AND owned simultaneously, and what does it look like)? [Clarity, Spec §FR-026]
- [x] CHK012 - Is "pixel-level fidelity" in Assumptions quantified with an acceptable deviation tolerance or a defined comparison method? [Clarity, Ambiguity]
- [x] CHK013 - Does FR-025 ("Effacer" resets draft) specify the target state — empty/initial state or last applied state? [Clarity, Spec §FR-025]
- [x] CHK014 - Is the "Voir + X autres éditeurs" button fully specified — does X show remaining count, and is a max visible-list height or scrollable container required? [Clarity, Spec §FR-018]

## Requirement Consistency

- [x] CHK015 - Does FR-011 ("pagination stays on current page after chip removal") conflict with the Assumption ("pagination resets to page 1 whenever filters are applied")? Both describe filter change events but specify different pagination behavior. [Conflict, Spec §FR-011 vs §Assumptions]
- [x] CHK016 - Is sort state source-of-truth defined during error recovery — if FR-026b restores the previous grid, does the toolbar dropdown also revert to the sort before "Appliquer" was clicked? [Consistency, Spec §FR-013, §FR-026b]
- [x] CHK017 - Are chip generation requirements consistent between desktop (panel apply) and mobile (modal apply), particularly for the search chip (FR-008) and collection-status chip (FR-021)? [Consistency, Spec §FR-010, §US-2]
- [x] CHK018 - Does FAB badge update timing (FR-004) match chip removal immediacy (FR-011) — is the badge updated synchronously when a chip is removed without "Appliquer"? [Consistency, Spec §FR-004, §FR-011]

## Acceptance Criteria Quality

- [x] CHK019 - Can SC-002 ("active filter state always consistent") be objectively verified — is a specific synchronization invariant or failure scenario defined that would constitute a violation? [Measurability, Spec §SC-002]
- [x] CHK020 - Is SC-007 ("fully keyboard-navigable, WCAG 2.1 AA") quantified with specific interaction patterns (e.g., slider keyboard step increment, modal focus trap, chip tab order)? [Measurability, Spec §SC-007]
- [x] CHK021 - Is there traceable coverage from each FR to at least one acceptance scenario — are FRs 015–023 covered by a user story acceptance scenario or a named edge case? [Completeness, Acceptance Criteria]

## Scenario Coverage

- [x] CHK022 - Are requirements defined for the zero-results autocomplete scenario (FR-007 dropdown with no matching entries after typing)? [Coverage, Gap]
- [x] CHK023 - Are requirements defined for conflicting URL params on page load (e.g., editors selected AND search chip present — which takes precedence or are both applied)? [Coverage, Gap]
- [x] CHK024 - Are browser back/forward navigation requirements defined — does navigating back restore the grid to the previous filter state including skeleton display? [Coverage, Spec §FR-024]
- [x] CHK025 - Are requirements defined for a deep-linked URL opened on a mobile viewport — is the initial FAB badge count derived from the URL params before first render? [Coverage, Gap]
- [x] CHK026 - Are requirements defined for the scenario where a filter is active and the user changes the sort order — does changing sort require a new "Appliquer" or is it immediate? [Coverage, Spec §US-5, §FR-013]

## Edge Case Coverage

- [x] CHK027 - Are truncation or overflow requirements defined for long editor names or book titles in chips, accordion rows, and autocomplete dropdown entries? [Edge Case, Gap]
- [x] CHK028 - Are requirements defined for the preset pills container (FR-020) when all presets fall outside the dynamic bounds — what does the layout look like with zero visible pills? [Edge Case, Spec §FR-020]
- [x] CHK029 - Are minimum touch target size requirements specified for the mobile FAB, modal close button, chip "×" buttons, and slider handles? [Edge Case, Gap]
- [x] CHK030 - Are requirements defined for the race condition where the user modifies draft state while a COUNT query is already in-flight (FR-023 debounced at 300 ms, user changes filter before response)? [Edge Case, Spec §FR-023]

## Non-Functional Requirements

- [x] CHK031 - Are color contrast requirements specified for filter chips, FAB badge, skeleton placeholders, and disabled/inactive states to confirm WCAG 2.1 AA compliance (4.5:1 text, 3:1 UI components)? [Non-Functional, Accessibility, Gap]
- [x] CHK032 - Are screen reader announcement requirements defined for dynamic content updates: result count changes, chip add/remove, and skeleton-to-results transition? [Non-Functional, Accessibility, Gap]
- [x] CHK033 - Is a focus trap requirement defined for the mobile filter modal (FR-005) to prevent keyboard focus escaping the overlay while it is open? [Non-Functional, Accessibility, Gap]
- [x] CHK034 - Are performance requirements defined for the editor search Live Component response time (FR-017) — only debounce delay is specified, but no maximum acceptable server response latency? [Non-Functional, Gap]

## Dependencies & Assumptions

- [x] CHK035 - Is a fallback or degraded-state requirement defined if the filter data endpoints (features 006/009) are unavailable at page load? [Dependency, Assumption]
- [x] CHK036 - Is behavior defined if the auth session expires mid-filtering — does the "STATUT DANS MA COLLECTION" section disappear without page reload, and do in-flight requests fail gracefully? [Dependency, Assumption]
- [x] CHK037 - Is the exclusion of view mode (grid/list) from URL params documented as an explicit product decision with rationale, not just a technical omission? [Assumption, Spec §Assumptions]

## Ambiguities & Conflicts

- [x] CHK038 - Is it specified whether the in-page search chip (FR-008) is included in the FAB active filter badge count (FR-004)? [Ambiguity, Gap]
- [x] CHK039 - Is it specified whether applying filters from URL params on page load triggers the skeleton loading state (FR-025b) or renders results directly without skeleton? [Ambiguity, Gap]
- [x] CHK040 - Is the URL param behavior for "TOUT EFFACER (X)" (FR-012) specified — does it clear URL params and push a browser history entry, or only update in-memory state? [Ambiguity, Spec §FR-012, §FR-024]
