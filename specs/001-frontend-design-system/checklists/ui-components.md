# UI Components Checklist: Frontend Design System Foundation

**Purpose**: Validate UI component requirements quality before implementation (author pre-implementation gate)
**Created**: 2026-05-23
**Resolved**: 2026-05-23
**Feature**: [spec.md](../spec.md)
**Focus**: FR-007–010, SC-003 — Book/Author cards, Badge/Rating, Modal/Toast

---

## Requirement Completeness

- [x] CHK001 — Are the visual appearance requirements for Book card skeleton state specified beyond "animated placeholder shimmer"? Is the shimmer color, animation timing, and layout structure defined? [Completeness, Gap — FR-007]
  > **Resolved**: FR-007 updated — shimmer uses `var(--bg-elevated)` → `var(--bg-sunken)` → `var(--bg-elevated)` gradient, 1.5 s linear infinite, placeholder zones matching cover area / title block / metadata row / badge row.

- [x] CHK002 — Are the visual appearance requirements for Author card skeleton state specified beyond "matching the card layout dimensions"? [Completeness, Gap — FR-008]
  > **Resolved**: FR-008 updated — same shimmer pattern as FR-007, zones matching portrait circle / name line / role line / stats grid.

- [x] CHK003 — Is the placeholder graphic for missing Book cover image specified? (dimensions, color, icon, or Design System reference?) [Completeness, Gap — FR-007]
  > **Resolved by design**: `design/pages/05-cards.html §.card-book .cover` — striped `linear-gradient(135deg, var(--cuir-300), var(--cuir-500))` + `.cover-frame` corner decoration. Documented in FR-007 and research.md §Decision 0.

- [x] CHK004 — Is the placeholder graphic for missing Author avatar specified? Is it distinct from the Book cover placeholder or the same graphic? [Completeness, Gap — FR-008]
  > **Resolved by design**: `design/pages/05-cards.html §.card-author .author-portrait` — 96×96 px circle, `radial-gradient(circle at 35% 30%, var(--cuir-300), var(--cuir-500) 60%, var(--cuir-700))`, initials in 32 px display font. Distinct from book cover placeholder. Documented in FR-008.

- [x] CHK005 — Are all Badge variants enumerated in the requirements? (e.g., genre badge, status badge — or a single generic badge component?) [Completeness, Gap — FR-009]
  > **Resolved by design**: `design/assets/components.css §BADGES` defines: `.badge` (generic), `.badge-status-pending/validated/rejected/archived`, `.badge-role-user/mod/admin`. All 8 variants enumerated in updated FR-009.

- [x] CHK006 — Is the Rating component specified as display-only or interactive (user-selectable)? If interactive, are selection requirements defined? [Completeness, Ambiguity — FR-009]
  > **Resolved**: FR-009 updated — display-only (`.rating`) for this feature; `.rating-input` interactive variant explicitly out of scope. Both exist in `design/assets/components.css §STAR RATING`.

- [x] CHK007 — Are Modal variants enumerated? (e.g., confirmation, info, form-hosting — or a single generic modal?) Are all variants that developers will need covered? [Completeness, Gap — FR-010]
  > **Resolved by design**: `design/pages/08-feedback.html` defines two CSS variants: standard modal and `.danger-accent` (4 px danger accent bar). Single generic component, `variant` prop (`default` | `danger`). Form-hosting modal is satisfied by the same component (body accepts arbitrary content). Documented in updated FR-010.

- [x] CHK008 — Are Modal dismiss behaviors specified? (close button, ESC key, click-outside — which are required?) [Completeness, Gap — FR-010]
  > **Resolved**: FR-010 updated — all three required: close button (`aria-label="Fermer"`), ESC key, overlay click.

---

## Requirement Clarity

- [x] CHK009 — Is "matching the Claude Design System card specification" traceable to a specific section of the Design System for Book cards? (e.g., design file path, section name, or reference) [Clarity, Traceability — FR-007]
  > **Resolved**: FR-007 now references `design/pages/05-cards.html §.card-book` explicitly.

- [x] CHK010 — Is "matching the Claude Design System card specification" traceable to a specific section for Author cards? [Clarity, Traceability — FR-008]
  > **Resolved**: FR-008 now references `design/pages/05-cards.html §.card-author` explicitly.

- [x] CHK011 — Is "matching the Design System specification" traceable to a specific section for Badge and Rating components? [Clarity, Traceability — FR-009]
  > **Resolved**: FR-009 now references `design/assets/components.css §BADGES` and `§STAR RATING` explicitly.

- [x] CHK012 — Is the Rating display format specified? (numeric value, star icons, progress bar, or other visual metaphor?) [Clarity, Gap — FR-009]
  > **Resolved by design**: Star icons (★ Unicode, partial fill via CSS `--fill` custom property), numeric score (`.rating-score`, monospace), vote count (`.rating-count`), three sizes (`size-sm/md/lg`). All documented in updated FR-009.

- [x] CHK013 — Does SC-003 "at least one corresponding reusable component" define what counts as sufficient coverage — or could a single minimal component satisfy the criterion even if the Design System defines multiple variants? [Clarity, Measurability — SC-003]
  > **Resolved**: SC-003 updated — explicitly states a single minimal implementation per category meets the criterion; token-only categories (Colors, Typography, Spacing/Effects) are satisfied by the SCSS token system with no Twig class required.

---

## Scenario Coverage & Edge Cases

- [x] CHK014 — Are interaction state requirements (hover, focus, active) defined for Book and Author card components? [Coverage, Gap — FR-007/FR-008]
  > **Resolved**: FR-007/FR-008 updated — hover transitions per design system; `:focus-within` shows `--ring-focus` outline; active/pressed state explicitly out of scope.

- [x] CHK015 — Are card components specified as display-only or navigable/clickable? If clickable, are the navigation targets and behavior defined? [Coverage, Gap — FR-007/FR-008]
  > **Resolved**: FR-007/FR-008 updated — optional `href` prop; when provided, card title becomes `<a>` link with `aria-label` matching title. Navigation target is caller-supplied — the component does not constrain the destination URL.

- [x] CHK016 — Does the spec address what happens to active auto-dismiss timers for toasts that are forcibly removed when the 3-toast limit is exceeded? [Coverage, Edge Case — FR-010]
  > **Resolved**: FR-010 updated — timer cancelled by Stimulus `disconnect()` lifecycle on element removal; no leaked timers. Architectural detail in research.md §Decision 3.

- [x] CHK017 — Are requirements defined for the scenario where a developer manually triggers a Toast (outside of the Flash notification system)? Or is Toast exclusively rendered from Symfony flash messages? [Coverage, Gap — FR-010]
  > **Resolved**: FR-010 updated — Toast MAY be used independently of FlashBag; developer supplies `type`, `title`, and `message` props directly.

- [x] CHK018 — Does the spec address what happens when a Toast message is very long? (The edge case is defined for Flash notifications in Key Entities, but FR-010 does not explicitly reference it.) [Coverage, Consistency — FR-010]
  > **Resolved**: FR-010 updated — "Long toast messages expand vertically — no truncation (consistent with Key Entities §Flash Notification)."

---

## Non-Functional Requirements Coverage

- [x] CHK019 — Are ARIA requirements specified for Book and Author card components? (NFR-001 targets interactive components; cards may be display-only but linked — is their accessibility behavior defined?) [Coverage, Gap — NFR-001/FR-007/FR-008]
  > **Resolved**: FR-007/FR-008 updated — when `href` prop is provided, card gains `aria-label` matching the title. Display-only cards are `<article>` (semantic HTML per NFR-002); no additional ARIA needed for non-navigable cards.

- [x] CHK020 — Are Modal accessibility requirements specified for focus management? (focus trap on open, focus restoration on close — required by WCAG 2.1 AA but not stated in FR-010 or NFR-001) [Coverage, Gap — NFR-001/FR-010]
  > **Resolved**: NFR-001 updated — focus trap on open + restore on close explicitly required. FR-010 cross-references NFR-001 for this requirement.

- [x] CHK021 — Are ARIA requirements specified for the Toast container and individual Toast components? (role="alert" or role="status", live region behavior — critical for screen reader announcements) [Coverage, Gap — NFR-001/FR-010]
  > **Resolved**: FR-010 updated — container: `aria-live="polite"` + `aria-atomic="false"`; error/warning toasts: `role="alert"` (assertive); toasts must not steal focus on insertion.

---

## Requirement Consistency

- [x] CHK022 — Does the 8-category count in SC-003 ("Colors, Typography, Spacing/Effects, Buttons/Forms, Book/Author Cards, Badges/Ratings, Navigation/Avatars, Modals/Toasts") treat Book and Author cards as one category or two? Is this consistent with FR-007 and FR-008, which define them as separate requirements? [Consistency, Ambiguity — SC-003]
  > **Resolved**: SC-003 updated — "Book/Author Cards" is one category label but maps to two distinct Twig components: `Book:Card` (FR-007) and `Author:Card` (FR-008). No inconsistency — the category count (8) is a design system grouping; implementation delivers both components.

---

**All 22 items resolved. Spec updated. Gate: PASS — proceed to implementation.**
