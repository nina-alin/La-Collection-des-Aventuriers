# Mobile & Responsive Requirements Quality Checklist: Page Contributeur — Suggestions

**Purpose**: Pre-planning gate — validate mobile and responsive requirement completeness, clarity, and measurability before implementation planning
**Created**: 2026-05-30
**Feature**: [spec.md](../spec.md)

## Requirement Completeness

- [ ] CHK001 - Are breakpoint requirements defined beyond the single 1080px threshold (e.g., intermediate layouts at 768px, 480px, 320px)? [Completeness, Spec §FR-024] → **Intentional** — 1080px is the sole breakpoint; intermediate layouts not required (deferred to design file)
- [x] CHK002 - Is the default active tab on mobile load specified (Action tab or Suivi tab)? [Completeness, Gap] → FR-046
- [x] CHK003 - Are tab indicator state requirements defined (active style, inactive style, badge/count on Suivi tab)? [Completeness, Gap] → FR-044 (badge), FR-026 (visual styles via design file)
- [x] CHK004 - Is "état du formulaire conservé lors du basculement" defined for file upload state at step 3 specifically? [Completeness, Spec §FR-024, Edge Cases] → FR-028 (LiveComponent preserves state server-side; mode switch clears image but tab switch does not)
- [x] CHK005 - Are stepper layout requirements defined for mobile screens (horizontal scroll, vertical stack, collapsed indicator)? [Completeness, Gap] → FR-047
- [x] CHK006 - Is the polling behavior (FR-021) defined for the Suivi tab when it is in background (tab not active) — does polling pause or continue? [Completeness, Gap] → FR-049
- [x] CHK007 - Are requirements defined for the rank bandeau (FR-001) layout on mobile screens? [Completeness, Gap] → FR-064 (semantic structure); visual layout covered by FR-026 design reference

## Requirement Clarity

- [x] CHK008 - Is "commutables via onglets mobiles sans rechargement de page" clarified — is tab switching CSS-only (hidden/visible) or JS-driven state management? [Clarity, Spec §FR-024] → FR-049 (polling continues; LiveComponent on server = JS-driven state preserved)
- [x] CHK009 - Is "sans perte de l'état du formulaire" defined for all 4 wizard steps, or are certain steps (e.g., step 3 with uploaded file) excluded? [Clarity, Spec §FR-024] → FR-028 (tab switch preserves state; mode switch at step 3/4 clears image — these are distinct events)
- [ ] CHK010 - Is the 1080px breakpoint defined as min-width or max-width, and does it apply to viewport or container width? [Clarity, Spec §FR-024] → **Remaining gap** — implementation detail; to be specified in plan
- [ ] CHK011 - Is "écrans < 1080 px" the sole layout breakpoint, or are additional responsive layouts required between 320px and 1080px? [Clarity, Gap] → **Intentional** — single breakpoint confirmed; see CHK001

## Requirement Consistency

- [x] CHK012 - Do WCAG keyboard navigation requirements (FR-025) apply to the mobile tab switcher interface? [Consistency, Spec §FR-024, FR-025] → FR-025 + FR-060 apply to full page
- [x] CHK013 - Is state preservation on tab switch (FR-024) consistent with the edge case definition ("l'état du formulaire doit être conservé lors du basculement")? [Consistency, Spec §FR-024, Edge Cases] → FR-028 (consistent — tab switch preserves, mode switch clears image only)
- [x] CHK014 - Are toast notification positioning requirements (FR-020) defined consistently for both mobile and desktop viewports? [Consistency, Spec §FR-020, Gap] → FR-041 (haut à droite desktop, haut au centre mobile)
- [x] CHK015 - Is the hard cap of 50 suggestions (FR-021, SC-005) display requirement consistent across mobile and desktop? [Consistency, Spec §FR-021, SC-005] → FR-049 (polling same on all viewports; cap applies equally)

## Scenario Coverage

- [x] CHK016 - Are requirements defined for device orientation change (portrait→landscape) mid-wizard — does the layout adapt without losing form state? [Coverage, Gap] → Assumptions: portrait-only target; landscape not optimized
- [x] CHK017 - Are requirements defined for file upload on mobile — are camera and gallery picker options specified in addition to file browse? [Coverage, Gap] → FR-050 (native browser handles camera/gallery via standard input)
- [x] CHK018 - Are requirements defined for mobile behavior when the quota warning (FR-018) is triggered — how is the disabled state communicated on small screens? [Coverage, Gap] → FR-039 (same behavior; FR-041 responsive positioning)
- [x] CHK019 - Are requirements defined for the autocomplete dropdown behavior on mobile (full-screen modal vs inline dropdown, touch targets)? [Coverage, Gap] → FR-048
- [x] CHK020 - Are requirements defined for the pré-remplissage bandeau layout on mobile (collapsible, scrollable, or truncated)? [Coverage, Spec §FR-004] → FR-051

## Acceptance Criteria Quality

- [x] CHK021 - Is "support mobile cible : écrans ≥ 320 px" (Assumptions) testable with a defined device/browser matrix or emulation baseline? [Measurability, Spec §Assumptions] → Assumptions updated; specific matrix deferred to plan
- [x] CHK022 - Can "sans rechargement de page ni perte de l'état du formulaire" (FR-024) be objectively verified without defined test scenarios per step? [Measurability, Spec §FR-024] → FR-028 defines per-step preservation rules; test scenarios in plan
- [ ] CHK023 - Is there a measurable definition of "commutable" — e.g., maximum tab switch latency threshold? [Measurability, Gap] → **Remaining gap** — no latency threshold defined; low priority for pre-planning
