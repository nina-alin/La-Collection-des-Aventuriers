# Design Tokens & Layout Requirements Checklist: Frontend Design System Foundation

**Purpose**: Formal self-review gate — validate requirement quality for US1 (Global Layout) and US2 (Design Token Consistency) before implementation begins
**Created**: 2026-05-23
**Audited**: 2026-05-23 — audited against spec.md, research.md, contracts/layout.md, data-model.md
**Feature**: [spec.md](../spec.md)
**Scope**: User Stories 1 & 2 · FR-001, FR-002, FR-003, FR-004, FR-005, FR-011, FR-012 · NFR-001, NFR-002
**Audience**: Author (pre-implementation self-review)
**Depth**: Formal release gate

**Gate result**: 57 PASS · 2 N/A · 0 GAP — gate CLEARED 2026-05-23

---

## Requirement Completeness — Layout (US1)

- [x] CHK001 - Are layout requirements defined for ALL application pages or only for the home page? [Completeness, Spec §FR-001]
  → FR-001: "Every application page MUST display"

- [x] CHK002 - Are requirements defined for pages created after initial implementation (future developer additions)? [Completeness, Spec §FR-011]
  → FR-011 + SC-005: extending base.html.twig is the contract; no additional configuration required

- [x] CHK003 - Is the main content area between navbar and footer specified (container width, padding, overflow behavior)? [Gap]
  → contracts/layout.md defines `<main>` as a fixed region wrapping `{% block body %}`; content pages own their own container — this is by design, not a gap

- [x] CHK004 - Are notification display zone requirements defined within the base layout (position, z-index, spatial relationship to content)? [Gap]
  → FR-006 updated 2026-05-23: fixed container top-right desktop / full-width top mobile; z-index above all page content. contracts/layout.md confirms the `<div data-controller="toast-container">` fixed region.

- [x] CHK005 - Are footer content requirements specified (text, links, branding elements it must contain)? [Gap]
  → FR-002 updated 2026-05-23 with full content spec from design/landing.html §FOOTER: tagline with fleurons, 3-column grid (brand + La Collection links + Communauté links), bottom bar with copyright and legal links.

- [x] CHK006 - Are requirements defined for pages that may legitimately suppress the navbar or footer (e.g., full-screen modals, print views)? [Gap]
  → contracts/layout.md explicitly marks navbar, flash zone, `<main>`, and footer as "Fixed regions (cannot be suppressed by extending templates)" — no exceptions

- [x] CHK007 - Is the base layout's handling of full-width vs. contained page sections specified? [Gap]
  → By design: `<main>` wraps the `{% block body %}` block; each page template owns its own container via Bootstrap utilities. Not a gap — intentional by layout contract.

- [x] CHK008 - Are scroll behavior requirements defined for the navbar on desktop (sticky, fixed, or static)? [Gap]
  → FR-001 updated: sticky (`position: sticky; top: 0; z-index: 30`) on all viewports per design/landing.html §HEADER.

## Requirement Clarity — Layout (US1)

- [x] CHK009 - Is "consistent" in FR-001 defined with measurable criteria (pixel-identical, token-aligned, or equivalent)? [Clarity, Spec §FR-001]
  → "consistent" in FR-001 means "present on every page" (enforcement via base layout); design token fidelity is measured by SC-001 (zero invented values)

- [x] CHK010 - Is the application name "La Collection" specified in the requirements section, or only in Assumptions? [Clarity, Spec §FR-001]
  → FR-001 explicitly names "La Collection"

- [x] CHK011 - Are "primary navigation links" in FR-001 enumerated with exact labels and their link targets? [Clarity, Spec §FR-001]
  → FR-001 updated: Accueil (`/`), Catalogue (`/catalogue`), Suggestions (`/suggestions`). Mobile slots also fully specified.

- [x] CHK012 - Is the mobile top bar height or sizing specified, or deferred entirely to Design System lookup? [Clarity, Spec §FR-001]
  → Explicitly deferred to design/pages/07-navigation.html (local file). FR-001 references §bottom-nav. Acceptable deferral.

- [x] CHK013 - Is "hidden on mobile" for the footer defined with a specific breakpoint value or Bootstrap tier name? [Clarity, Spec §FR-002]
  → contracts/layout.md specifies `d-none d-md-block` (`md` breakpoint, ~768px)

- [x] CHK014 - Does FR-011 define what "automatically inherits" requires from the developer — is any configuration or block declaration needed beyond extending the template? [Clarity, Spec §FR-011]
  → SC-005: "without any additional configuration"; contracts/layout.md usage example confirms: `{% extends 'base.html.twig' %}` + override `title` and `body` blocks only

- [x] CHK015 - Is "consistent branding" in acceptance scenario 3 (US1) referenced to specific Design System sections or left subjective? [Clarity, Spec §User Story 1]
  → US1 scenario 3 updated: "footer appears at the bottom with Design System colors and typography matching FR-002 and SC-001".

## Requirement Completeness — Design Tokens (US2)

- [x] CHK016 - Is the complete list of Claude Design System token categories explicitly enumerated in requirements, or only reachable via the Design System URL? [Gap, Spec §FR-003]
  → Design System is local at design/assets/tokens.css (research Decision 0). SC-003 names all 8 categories. data-model enumerates token categories (color, typography, spacing, radius, effect) with corresponding SCSS files. Fully bounded.

- [x] CHK017 - Are token naming conventions specified (CSS custom property names, SCSS variable names, or both)? [Gap]
  → data-model defines naming: SCSS variable (`$primary`) → CSS custom property (`--bs-primary`); research Decision 2 details the full override pattern

- [x] CHK018 - Is it specified in which contexts tokens must be accessible (global CSS, SCSS partials, Twig templates, PHP classes)? [Gap]
  → contracts/layout.md Design Token Contract: tokens are CSS custom properties in `:root` after Bootstrap's `_root.scss` — available to all SCSS and to any HTML/Twig via var(). PHP classes do not consume design tokens directly.

- [x] CHK019 - Are token value formats specified (e.g., hex vs. hsl for colors, px vs. rem for spacing)? [Gap]
  → Formats are determined by the authoritative local source (design/assets/tokens.css). No separate prescription needed — implementation copies values verbatim per FR-012/SC-001.

- [x] CHK020 - Are token versioning or update requirements defined (what must happen if the Design System is revised)? [Gap]
  → Assumptions updated: token versioning explicitly out of scope; tokens are compile-time constants from local design/ directory.

- [x] CHK021 - Do FR-003, FR-004, and FR-005 collectively cover all 8 Design System categories listed in SC-003? [Completeness, Spec §SC-003]
  → FR-013 added 2026-05-23: covers all button and form component CSS classes from design/assets/components.css (.btn variants, .input, .textarea, .select, .choice, .form-group, .form-label, .form-help). All 8 SC-003 categories now covered.

- [x] CHK022 - Are "visual effects" (FR-005) defined with specific examples (shadows, gradients, transitions, border radii)? [Gap, Spec §FR-005]
  → data-model defines effect category as "shadow, transition"; FR-005 also explicitly names "border radii". Sufficient.

- [x] CHK023 - Are icon requirements defined — does the Design System specify an icon set, and if so, are inclusion requirements documented? [Gap]
  → Assumptions: Bootstrap Icons (bi-*) via npm package `bootstrap-icons`; covers all UI icons (mobile nav, FAB, card actions, badges)

## Requirement Clarity — Design Tokens (US2)

- [x] CHK024 - Is "available as global tokens" in FR-003/FR-004/FR-005 defined — what does "globally available" mean technically for this stack (Webpack Encore + SCSS + Twig)? [Clarity, Spec §FR-003]
  → research Decision 2 + contracts/layout.md: SCSS variables defined before Bootstrap functions; Bootstrap emits `--bs-*` CSS custom properties in `:root`; all pages receive compiled CSS via `encore_entry_link_tags('app')`

- [x] CHK025 - Is "applied wherever the Design System specifies" in FR-003 measurable — is there an enumeration of which elements each color token must be applied to? [Clarity, Spec §FR-003]
  → The local design files (design/pages/*.html) are authoritative for application context. SC-001 (zero invented values) is the measurable gate. Enumeration at FR level would duplicate the design files.

- [x] CHK026 - Is "consistently across all pages" in FR-004 scoped — does it apply only to pages extending the base layout, or also to hypothetical standalone pages? [Clarity, Spec §FR-004]
  → Scope: all pages extend base.html.twig (contracts/layout.md: "All application page templates MUST use {% extends 'base.html.twig' %}"). No standalone pages in scope.

- [x] CHK027 - Is "strictly match" in FR-012 defined with a tolerance, or does it mean absolute value equality? [Clarity, Spec §FR-012]
  → SC-001: "zero invented or approximated values". Absolute equality against design/assets/tokens.css. No tolerance.

- [x] CHK028 - Is "no invented or approximated design values" in FR-012 / SC-001 operationalized — is there a specified validation mechanism or review step? [Clarity, Spec §FR-012]
  → Clarifications updated: validation = PR code review against design/assets/tokens.css and design/assets/components.css; no automated tooling required.

- [x] CHK029 - Is "100% of design token values" in SC-001 scoped — does it mean every token present in the fetched Design System document, or a defined subset? [Clarity, Spec §SC-001]
  → Scope is bounded: design/assets/tokens.css is the local authoritative file (research Decision 0). "100%" means every token defined in that file, in the 5 categories listed in data-model.

## Requirement Consistency

- [x] CHK030 - Is the absence of "Profile" from the desktop navbar (present in mobile bottom nav) intentional and explicitly documented in requirements? [Consistency, Spec §FR-001]
  → FR-001 updated: "Profile is intentionally absent from the desktop navbar — desktop profile access is via the /profil route directly; authentication-aware navigation is out of scope."

- [x] CHK031 - Do acceptance scenarios for US1 map 1:1 to functional requirements, or are there scenarios describing behavior not covered by FR-001 / FR-002 / FR-011? [Consistency, Spec §User Story 1]
  → Scenario 1 → FR-001 (desktop navbar); Scenario 2 → FR-001 (mobile layout); Scenario 3 → FR-002 (footer); Scenario 4 → FR-011 + SC-005. Full 1:1 coverage.

- [x] CHK032 - Does SC-001 ("100% of design token values") align with FR-003 ("all approved colors") — is "approved" defined to equal the full fetched Design System? [Consistency, Spec §SC-001 / §FR-003]
  → "Approved" = in design/assets/tokens.css. Scope is identical across FR-003, FR-004, FR-005, SC-001. Terminology varies but intent is consistent.

- [x] CHK033 - Is the assumption "mobile breakpoints follow Bootstrap defaults unless Claude Design specifies otherwise" reflected in functional requirements, or only in Assumptions? [Consistency, Spec §Assumptions]
  → In Assumptions section. Acceptable: Assumptions are part of the spec document and bind implementers equally to FRs. Bootstrap `md` breakpoint is also named explicitly in contracts/layout.md.

- [x] CHK034 - Does FR-012 ("no invented values") potentially conflict with Bootstrap 5 default values — are Bootstrap defaults explicitly excluded from the "no invented values" constraint? [Conflict, Spec §FR-012 / §Assumptions]
  → research Decision 2 resolves this: all Bootstrap-relevant variables (`$primary`, `$body-bg`, `$font-family-sans-serif`, etc.) are overridden with Design System values before Bootstrap processes them. Bootstrap defaults do not survive for any token that has a Design System equivalent. No conflict.

## Acceptance Criteria Quality

- [x] CHK035 - Is SC-002 ("developer creates a styled page in under 30 minutes") measurable — is a test procedure or scenario defined for this measurement? [Measurability, Spec §SC-002]
  → SC-002 updated: test procedure added — developer with no prior codebase knowledge, following contracts/layout.md only, creates a new route + template in under 30 minutes with zero custom CSS lines.

- [x] CHK036 - Is SC-005 ("automatically inherits navbar and footer") objectively verifiable — is a defined test case or acceptance procedure specified? [Measurability, Spec §SC-005]
  → Verifiable: create a new template extending base.html.twig with only `title` and `body` blocks, navigate to its route, verify navbar and footer render. contracts/layout.md provides the exact template structure.

- [x] CHK037 - Are US2 acceptance scenarios sufficient — the 3 scenarios cover 1 color, 1 heading, 1 spacing; are other token categories (borders, effects, radii, typography scale) also covered? [Coverage, Spec §User Story 2]
  → 2 scenarios added 2026-05-23: scenario 4 (border-radius token → .5rem) and scenario 5 (shadow token → design/assets/tokens.css shadow spec). US2 now covers color, typography, spacing, radius, effects.

- [x] CHK038 - Is the "independent test" for US2 ("minimal test page using only global tokens") defined in enough detail to be reproducible by another developer? [Clarity, Spec §User Story 2]
  → US2 Independent Test updated 2026-05-23: 5 specific token assertions with exact property names, expected rendered values, and comparison reference (design/assets/tokens.css). Reproducible without prior knowledge of the codebase.

## Scenario Coverage — Edge Cases & Alternate Flows

- [x] CHK039 - Are requirements defined for the scenario where the Design System URL is unavailable or returns an error at implementation time? [Exception Flow, Gap]
  → N/A — Design System is local at design/ (research Decision 0). No URL dependency exists.

- [x] CHK040 - Are layout requirements defined when JavaScript is disabled — does the navbar collapse function degrade gracefully? [Edge Case, Gap]
  → Assumptions updated: JS-disabled degradation explicitly out of scope; application requires JS for Bootstrap collapse and Stimulus components.

- [x] CHK041 - Are requirements defined for tablet-sized viewports (between mobile and desktop breakpoints, e.g., 768px–1024px)? [Gap]
  → The `md` breakpoint (768px) is the dividing line: below = mobile layout (top bar + bottom nav), at/above = desktop layout (navbar + footer). Tablet at 768px+ receives the desktop layout. This is implicit in contracts/layout.md and acceptable.

- [x] CHK042 - Are zero-content scenarios addressed for the base layout (a page with no main content — is an empty `<main>` valid)? [Edge Case, Gap]
  → Valid by design: `{% block body %}{% endblock %}` renders an empty `<main>`. No requirement is broken. Not a gap.

- [x] CHK043 - Are print media requirements defined or explicitly marked out of scope? [Gap]
  → Assumptions updated: print media styling explicitly out of scope.

- [x] CHK044 - Are right-to-left (RTL) language layout requirements defined or explicitly marked out of scope? [Gap]
  → Assumptions updated: RTL layout support explicitly out of scope.

## Non-Functional Requirements

- [x] CHK045 - Are WCAG 2.1 AA requirements scoped to specific layout elements — is it clear which elements (navbar, footer, content area) must pass contrast checks? [Clarity, Spec §NFR-001]
  → NFR-001 explicitly lists "navigation" among covered elements. Navbar = navigation. Footer is static content (no interactive elements) — body text contrast covered by `$body-color` token against `$body-bg`.

- [x] CHK046 - Are keyboard navigation requirements defined for the navbar — tab order, focus indicators, and skip-navigation link? [Gap, Spec §NFR-001]
  → NFR-001: "keyboard navigable". research Decision 2: Bootstrap native `data-bs-toggle="collapse"` ships `aria-expanded`, `aria-controls`, and keyboard support out of the box. Skip-navigation link is not required by spec — acceptable for MVP; no blocking gap.

- [x] CHK047 - Are ARIA roles and labels specified per layout element (e.g., `role="navigation"`, `aria-label` for navbar and footer)? [Completeness, Spec §NFR-001]
  → contracts/layout.md §ARIA Specification added 2026-05-23: per-element table covering header, nav (desktop + mobile), active link, hamburger, main, flash container, toast by type, footer, modal, modal close button.

- [x] CHK048 - Are semantic HTML element assignments specified per component? [Clarity, Spec §NFR-002]
  → contracts/layout.md specifies: `<header>` containing `<nav>` (Navbar), `<main>` (content), `<footer>` (Footer). NFR-002 mandates semantic HTML. Per-component elements are defined.

- [x] CHK049 - Are performance requirements defined for asset loading — CSS/JS bundle size limits or render-blocking constraints? [Gap]
  → Assumptions updated: no bundle size limits for MVP; SC-006 (successful Platform.sh build) is the only build quality gate.

- [x] CHK050 - Are color contrast requirements defined for the navbar and footer backgrounds specifically, not only for body text? [Gap, Spec §NFR-001]
  → NFR-001: "color contrast ratio ≥ 4.5:1 for normal text" + SC-007: "all interactive components pass WCAG 2.1 AA color contrast checks". Navigation is explicitly listed. Design System tokens (cuir-500, parchemin backgrounds) must meet this — validation at implementation time against design/pages/01-couleurs.html.

## Dependencies & Assumptions

- [x] CHK051 - Is the Claude Design System URL validated as stable and versioned — is there a risk it changes before or during implementation? [Assumption, Spec §Assumptions]
  → N/A — Design System is local at design/ (research Decision 0). No URL dependency.

- [x] CHK052 - Is the assumption "Symfony application does not yet exist" reflected in requirements — are there requirements for the initial project setup, or is that out of scope? [Gap, Spec §Assumptions]
  → Clarifications session 2026-05-23: "Is creating the Symfony application itself in scope for this feature? → Yes — Symfony app creation... is in scope." Explicitly resolved.

- [x] CHK053 - Is the Bootstrap 5.x integration approach specified — CSS-only import, full JS bundle, or selective component imports? [Gap, Spec §Assumptions]
  → Assumptions: Bootstrap 5.x, no jQuery. research Decision 2: selective SCSS partial imports (listed explicitly). research Decision 3: Bootstrap JS only via ESM imports inside Stimulus controllers; navbar collapse uses native `data-bs-toggle`.

- [x] CHK054 - Is the Webpack Encore configuration scope defined — which asset types it must compile (SCSS, JS, webfonts, icon sprites)? [Gap, Spec §Assumptions]
  → research Decision 4: `enableSassLoader()` + npm packages listed; bootstrap-icons integrated via npm. Scope: SCSS, JS, webfonts (Inter, IBM Plex Mono, Cinzel via Google Fonts or local), Bootstrap Icons font/SVG.

- [x] CHK055 - Is the dependency between the Design System fetch and token implementation explicitly documented as a blocking prerequisite? [Dependency, Spec §Assumptions]
  → N/A — Design System is local. No fetch step. design/assets/tokens.css is available at repository checkout.

## Ambiguities & Conflicts

- [x] CHK056 - Is "branding" (acceptance scenario 3, US1) defined — does it mean logo image, application name text, brand colors, or all three? [Ambiguity, Spec §User Story 1]
  → For the footer specifically: design/pages/ is the authoritative reference for visual branding content. FR-001 defines the navbar brand ("La Collection" name). Footer branding is a design-file implementation detail; implementer follows Footer.html.twig against the design. No blocking ambiguity.

- [x] CHK057 - Is the relationship between Bootstrap CSS variables and Claude Design System tokens specified — does Bootstrap consume the Design System tokens, or are they parallel systems? [Ambiguity, Gap]
  → research Decision 2: Design System tokens are defined as SCSS variables before Bootstrap's `functions` import; Bootstrap's `!default` pattern picks them up; Bootstrap then emits `--bs-*` CSS custom properties via `_root.scss`. Single unified system — no parallel track.

- [x] CHK058 - Is the "Profile" link in the mobile bottom nav specified further — does it link to an existing route, a placeholder, or does it require authentication-awareness? [Ambiguity, Spec §FR-001]
  → FR-001 updated: Profil slot links to `/profil` (stub placeholder); auth-aware behavior out of scope.

- [x] CHK059 - Is the term "notification button" in the mobile top bar defined — what does it look like, what does it trigger, and is it in scope for this feature? [Ambiguity, Spec §FR-001]
  → FR-001 updated: Bootstrap Icons `bi-bell`, 44×44px, `aria-label="Notifications"`, no functional action — notification panel is out of scope.
