# Requirements Quality Checklist: Page Mentions Légales

**Purpose**: Thorough cross-dimensional quality gate — validates requirement completeness, clarity, consistency, and coverage before implementation planning
**Created**: 2026-06-08
**Reviewed**: 2026-06-08
**Feature**: [spec.md](../spec.md)
**Scope**: All dimensions (UX/Layout, Accessibility, SEO, ScrollSpy/Interactivity, Legal Content)
**Depth**: Thorough (formal gate)
**Priority gates**: ScrollSpy edge cases, Accessibility (WCAG 2.1 AA), SEO, Responsive layout

**Legend**: `[x]` = resolved · `[ ]` = open gap · `[~]` = design/spec discrepancy → needs decision

---

## Requirement Completeness

- [x] CHK001 — Are all sections of legal content enumerated by name? *Resolved: design lists 9 sections (Éditeur, Direction publication, Hébergement, Nature du projet, Propriété intellectuelle, Contributions, Données personnelles, Responsabilité, Contact).*
- [x] CHK002 — Is the exact list of sections in the sommaire defined? *Resolved: 9 items matching the 9 sections, confirmed in design.*
- [x] CHK003 — Is the `app.legal.last_updated` parameter format fully specified? *Resolved: format is `d MMMM Y` (e.g. "3 juin 2026"); fallback when absent = empty string (no replacement text).*
- [x] CHK004 — Are requirements defined for footer link styling (hover, focus states)? *Resolved from design: hover → `var(--brand-primary)`, no text-decoration.*
- [x] CHK005 — Is the initial active state of the sommaire (before any scroll, on page load) specified? *Resolved: first item active on page load. Encoded in FR-010.*
- [x] CHK006 — Are requirements defined for the sommaire column width on desktop? *Resolved from design: `240px` fixed, content column `1fr`.*
- [x] CHK007 — Is the vertical spacing between sections specified? *Resolved from design: `margin-bottom: var(--sp-7)` = 48px.*

## Requirement Clarity

- [x] CHK008 — Is "défilement fluide" defined with a specific behavioral criterion? *Resolved: CSS `scroll-behavior: smooth` on `html`. Smooth scroll is confirmed — design will be updated to match. Encoded in FR-009.*
- [x] CHK009 — Is "délai imperceptible (<100ms)" in SC-003 measured from a clearly defined starting event? *Resolved: Bootstrap Scrollspy triggers on IntersectionObserver threshold crossing (section header reaches the rootMargin boundary). The starting event is implicit in the Scrollspy configuration.*
- [x] CHK010 — Is "largeur maximale lisible (~720px)" quantified? *Resolved from design: `max-width: 70ch` on `.doc-body`. The "~" in the spec intentionally matches this approximation.*
- [x] CHK011 — Is "indicateur latéral" for the active sommaire state visually specified? *Resolved from design: `border-left: 2px solid var(--brand-primary)`, `font-weight: 600`, `color: var(--brand-primary)`, 120ms transition.*
- [x] CHK012 — Is "fond beige clair" tied to a specific design token? *Resolved from design: id-card background is `var(--bg-elevated)` (not "beige clair" literally); callout uses `color-mix(in oklab, var(--cuir-300) 12%, transparent)`. Both use existing tokens.*
- [x] CHK013 — Is "visuellement identifiables (soulignés ou colorés)" for inline links specified with concrete visual rules? *Resolved from design: `color: var(--brand-primary)` + `border-bottom: 1px solid color-mix(in oklab, var(--brand-primary) 35%, transparent)` — both colored AND underlined.*
- [x] CHK014 — Is the absence of `noindex` a passive or active requirement? *Resolved: passive — FR-021 says "NE DOIT PAS inclure", meaning simply don't add the tag.*
- [x] CHK015 — Is the Bootstrap Scrollspy data attribute API specified? *Resolved: Bootstrap Scrollspy confirmed. Design's custom IntersectionObserver is superseded. FR-010 stands.*

## Requirement Consistency

- [x] CHK016 — Does the scrollspy active-state trigger align with Bootstrap Scrollspy's offset behavior? *Resolved: Bootstrap Scrollspy configured with `rootMargin: '-88px 0px -65% 0px'` (matches design intent, now encoded in FR-010).*
- [x] CHK017 — Does the breakpoint align with Bootstrap's `md`? *Resolved: 900px confirmed (design wins). FR-005, FR-006, Assumptions, and User Story scenarios updated to 900px.*
- [x] CHK018 — Are hover/focus state requirements for sommaire links consistent with the design system? *Resolved from design: hover → `color: var(--ink-strong)`, active → `color: var(--brand-primary)` + border. Consistent with design system link patterns.*
- [x] CHK019 — Are breadcrumb styling requirements consistent with the design system? *Resolved from design: `.crumb` uses `var(--font-mono)`, uppercase, `var(--fs-xs)`, `var(--tracking-caps)` — consistent with system conventions.*
- [x] CHK020 — Does FR-006 align with User Story 2, scenario 4 on mobile sommaire behavior? *Resolved: no conflict — both say sommaire appears before content in single-column layout.*

## Acceptance Criteria Quality

- [x] CHK021 — Can SC-002 ("100% des éléments du sommaire déclenchent un défilement fluide") be objectively measured? *Resolved: "fluide" = CSS `scroll-behavior: smooth` on `html`, now specified in FR-009. SC-002 is testable.*
- [x] CHK022 — Is SC-006 ("WCAG 2.1 AA") decomposed into specific, testable success criteria? *Resolved: "WCAG 2.1 AA" reference is sufficient — no decomposition required in this spec.*
- [x] CHK023 — Is SC-004 ("aucune modification du template Twig") verifiable? *Resolved: SC-004 explicitly states "seul `config/services.yaml` est édité" — the boundary is clear.*
- [x] CHK024 — Do all FRs have a corresponding acceptance scenario? *Resolved as intentional: FR-012–014 (component styling) and FR-016–021 (technical accessibility + SEO) are implementation requirements verified by inspection, not user scenarios. The three User Stories cover all user-visible behaviors.*

## Scenario Coverage

- [x] CHK025 — Is a scenario defined for keyboard-only navigation of the sommaire links? *Resolved: covered by WCAG 2.1 AA reference (SC-006) — no dedicated acceptance scenario required. FR-016 specifies semantic HTML; WCAG 2.1 criterion 2.1.1 applies.*
- [x] CHK026 — Is a scenario defined for a screen-reader user? *Resolved: covered by WCAG 2.1 AA reference. FR-017 and FR-018 specify the required ARIA markup; testing against AA is sufficient.*
- [x] CHK027 — Are alternate flow scenarios defined for sommaire behavior on mobile? *Resolved: US1 scenario 4 (layout) and US2 scenario 4 (sommaire as flat list) cover mobile.*
- [x] CHK028 — Are scenarios defined for inline links when target routes don't yet exist? *Resolved: `href="#"` is acceptable as a temporary placeholder. Assumption updated.*

## Edge Cases

- [x] CHK029 — Is the two-simultaneously-visible-sections tie-break formally specified? *Resolved: Edge Cases specifies "L'élément actif doit correspondre à la section dont le titre est le plus proche du haut du viewport."*
- [x] CHK030 — Is the missing `app.legal.last_updated` parameter edge case specified with a concrete fallback? *Resolved: empty string — no replacement text displayed. Encoded in FR-004 and Edge Cases.*
- [x] CHK031 — Is behavior defined for the sommaire when the page content is shorter than the viewport? *Resolved: first item stays active (Gap B decision). No scrolling → no state change. Bootstrap Scrollspy handles this gracefully with no additional spec required.*
- [x] CHK032 — Are requirements defined for long section titles that may overflow the sommaire column? *Resolved as implementation concern: the 240px column with `line-height: 1.4` wraps naturally; no special spec needed.*
- [x] CHK033 — Is the sticky sommaire behavior specified when a sticky site header is present? *Resolved from design: `.toc { position: sticky; top: 88px }` — offset accounts for the sticky header height. Sections also have `scroll-margin-top: 96px`.*
- [x] CHK034 — Is the smooth scroll behavior specified for unsupported browsers? *Resolved: CSS `scroll-behavior: smooth` has baseline support across all modern browsers; no fallback requirement needed.*
- [x] CHK035 — Are requirements defined for the sticky sommaire on very small viewport heights? *Resolved as out of scope: below 900px the sommaire is not sticky (single-column layout). Landscape mobile is below the breakpoint — no sticky behavior applies.*
- [x] CHK036 — Are requirements defined for TableauKeyValue overflow on narrow viewports? *Resolved from design: `.id-row` uses single column (`grid-template-columns: 1fr`) below 520px, then 180px + 1fr above.*

## ⚠ Accessibility Requirements (MANDATORY GATE)

- [x] CHK037 — Is WCAG 2.1 AA decomposed into specific applicable success criteria? *Resolved: "WCAG 2.1 AA" reference is sufficient. No decomposition required.* ← **GATE**
- [x] CHK038 — Are keyboard navigation requirements defined for all interactive elements? *Resolved: WCAG 2.1 AA reference (SC-006, FR-016) covers keyboard navigation. Criterion 2.1.1 requires all functionality to be keyboard-accessible — no further decomposition required.* ← **GATE**
- [x] CHK039 — Is the `aria-current` attribute usage specified with precision? *Resolved: FR-018 specifies `aria-current="true"` — "ou équivalent" is acceptable as it covers ARIA 1.2 equivalents.*
- [x] CHK040 — Are color contrast requirements defined for the active sommaire state? *Resolved: WCAG 2.1 AA (1.4.3, ≥4.5:1 for normal text) applies globally via SC-006. The active state uses `var(--brand-primary)` which the design system has validated for AA compliance (NFR-001 in spec/001-frontend-design-system).* ← **GATE**
- [x] CHK041 — Is the exact element and `aria-label` for the sommaire specified? *Resolved: `<aside aria-label="Sommaire">` confirmed. FR-017 updated.* ← **GATE**
- [x] CHK042 — Are requirements defined for focus management when a sommaire link triggers scroll? *Resolved: standard browser anchor behavior — focus follows the `href="#id"` target. Sections with `id` attributes are natively reachable. No custom focus management required beyond native HTML behavior.*
- [x] CHK043 — Is the AlertBlock icon (triangle "Attention") specified as decorative or semantic? *Resolved: decorative — `aria-hidden="true"`. Encoded in FR-014.*

## ⚠ SEO Requirements (MANDATORY GATE)

- [x] CHK044 — Is the exact `<title>` format specified? *Resolved: FR-019 example + design confirms: "Mentions légales — La Collection des Aventuriers". Pattern: `[Page title] — [App name]`.*
- [x] CHK045 — Is the `<meta name="description">` content scope specified? *Resolved: content text is out of scope for this spec — supplied at content creation time. No length constraint imposed. Encoded in FR-020.* ← **GATE**
- [x] CHK046 — Is the canonical URL behavior confirmed? *Resolved: clarifications explicitly exclude canonical tags.*
- [x] CHK047 — Are Open Graph / Twitter Card meta tags explicitly excluded? *Resolved: clarifications explicitly state "pas de canonical ni d'OG tags".*
- [x] CHK048 — Are structured data / schema.org requirements addressed or explicitly excluded? *Resolved: explicitly out of scope. Encoded in Assumptions and Edge Cases.*

## ⚠ ScrollSpy & Interactivity (MANDATORY GATE)

- [x] CHK049 — Is the Bootstrap Scrollspy offset/rootMargin configuration specified? *Resolved: `rootMargin: '-88px 0px -65% 0px'` encoded in FR-010.* ← **GATE**
- [x] CHK050 — Is ScrollSpy behavior on mobile defined (active or disabled)? *Resolved: disabled below the responsive breakpoint. Encoded in FR-010.* ← **GATE**
- [x] CHK051 — Is a scenario defined for a user arriving via direct anchor URL? *Resolved: Edge Cases specifies it. Design implements `scroll-margin-top: 96px` on sections to handle sticky header offset.*
- [x] CHK052 — Is the Bootstrap component version pinned? *Resolved: design system spec clarification confirms Bootstrap 5.x.*
- [x] CHK053 — Is the active-state transition (instant vs animated) specified? *Resolved from design: `transition: color var(--motion-fast) var(--motion-ease), border-color var(--motion-fast) var(--motion-ease)` = 120ms ease.*

## ⚠ Responsive Layout (MANDATORY GATE)

- [x] CHK054 — Is the layout switch mechanism specified? *Resolved from design: CSS media query `@media (min-width: 900px)`. Note: this is 900px, not the 768px stated in the spec (see CHK017 discrepancy).*
- [x] CHK055 — Is the sommaire column spec complete? *Resolved from design: `240px` fixed width column.*
- [x] CHK056 — Is the sticky sommaire top offset specified? *Resolved from design: `top: 88px`.*
- [x] CHK057 — Is the content max-width per-breakpoint defined? *Resolved from design: `max-width: 70ch` on `.doc-body`, applied globally.*

## Dependencies & Assumptions

- [x] CHK058 — Are dependency routes tracked or validated? *Resolved: `href="#"` acceptable as temporary placeholder. Assumption updated to reflect this.*
- [x] CHK059 — Is the assumption that design tokens cover the required visual properties verified? *Resolved: design uses `--bg-elevated`, `--cuir-300`, `--brand-primary`, `--motion-fast`, etc. — all confirmed in `design/assets/tokens.css`.*
- [x] CHK060 — Is the Bootstrap version documented? *Resolved: Bootstrap 5.x.*
- [x] CHK061 — Is there a requirement for what happens when `design/mentions-legales.html` diverges from the spec? *Resolved: the spec is authoritative. Discrepancies were resolved in the 2026-06-08 clarification session with explicit decisions encoded in spec.md. No further process requirement needed.*

## Ambiguities & Conflicts

- [x] CHK062 — Is "~720px" a hard or soft constraint? *Resolved from design: `70ch` used, consistent with "~720px" being a soft approximation.*
- [x] CHK063 — Are TableauKeyValue and AlertBlock requirements sufficient without the design file? *Resolved: design file now exists and clearly specifies both `.id-card` and `.callout` components.*
- [x] CHK064 — Is a requirement-to-scenario traceability scheme established? *Resolved: FR-001 to FR-021 and SC-001 to SC-007 provide sufficient traceability. User Stories reference FRs implicitly; a formal cross-reference table is not required for this spec.*
