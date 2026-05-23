# Feature Specification: Frontend Design System Foundation

**Feature Branch**: `001-frontend-design-system`

**Created**: 2026-05-23

**Status**: Draft

**Input**: User description: "Bootstrap + Claude Design System frontend architecture integration for Symfony application"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Consistent Global Layout (Priority: P1)

A developer opens any page of the application and sees a fully branded interface: a navigation bar at the top displaying the application identity, and a footer at the bottom. The layout is visually consistent with the approved design system across all pages.

**Why this priority**: This is the foundational visual contract of the application. Without it, no page can be considered complete or ready for users. All subsequent features depend on this structure existing.

**Independent Test**: Navigate to the home page. Verify the header contains the application branding and navigation links. Verify the footer displays expected content. A blank styled page is a valid MVP for this story.

**Acceptance Scenarios**:

1. **Given** the application is running on desktop, **When** a visitor opens any page, **Then** a navigation bar appears at the top with the application name and primary navigation links (Accueil, Catalogue, Suggestions)
2. **Given** the application is running on mobile, **When** a visitor opens any page, **Then** a top bar shows the logo and notification button, and a fixed bottom navigation bar shows a 5-slot layout: Accueil, Catalogue, FAB "Suggérer un livre" (central), Suggestions, Profil
3. **Given** the application is running, **When** a visitor opens any page on desktop, **Then** a footer appears at the bottom with Design System colors and typography matching FR-002 and SC-001
4. **Given** a new page template is created by a developer, **When** the template extends the base layout, **Then** it automatically inherits the navbar, footer, and global styles

---

### User Story 2 - Design Token Consistency (Priority: P2)

A developer creates a new page or UI element and has immediate access to all approved colors, typography scales, spacing units, and visual effects from the Claude Design System without needing to look them up or define them manually.

**Why this priority**: Design token consistency prevents visual drift across the application. Without a shared source of truth for design values, individual pages will diverge from the approved design over time.

**Independent Test**: Create a minimal test page at a dedicated route. Apply and verify all of the following using only global tokens — no inline or hardcoded values: (1) `background: var(--brand-primary)` on a `<div>` — must render `#8b4513` (cuir-500, `design/assets/tokens.css`); (2) `<h1>` — must use `var(--font-display)` (Cinzel, weight 600); (3) `padding: var(--sp-4)` — must match spacing scale in tokens.css; (4) `border-radius: var(--radius-md)` — must render `.5rem`; (5) `box-shadow: var(--shadow-md)` — must match shadow token. Each value verified against `design/assets/tokens.css` with no rounding or approximation.

**Acceptance Scenarios**:

1. **Given** the design system is integrated, **When** a developer applies the primary brand color to any element, **Then** the displayed color exactly matches the approved Claude Design System value (`#8b4513`, cuir-500)
2. **Given** the design system is integrated, **When** a developer uses a heading style, **Then** the font family, size, and weight match the approved typography specifications (Cinzel, weight 600, letter-spacing 0.04em)
3. **Given** the design system is integrated, **When** a developer uses a spacing unit, **Then** the resulting spacing matches the approved spacing scale (`design/assets/tokens.css` `--sp-*` tokens)
4. **Given** the design system is integrated, **When** a developer applies a border-radius token (e.g., `var(--radius-md)`), **Then** the rendered radius matches the approved Design System radius value (`.5rem`) with no rounding
5. **Given** the design system is integrated, **When** a developer applies a shadow token (e.g., `var(--shadow-md)`), **Then** the rendered shadow matches the approved Design System effect specification in `design/assets/tokens.css`

---

### User Story 3 - System Notifications (Priority: P3)

A user completes an action (e.g., adds a book to their collection) and immediately sees a styled notification message confirming the outcome. The notification uses the correct visual style for its type: green for success, red for errors, yellow for warnings, and blue for informational messages.

**Why this priority**: Notifications are a core feedback mechanism. Users need clear visual confirmation that their actions succeeded or failed. Without this, the application feels broken or untrustworthy.

**Independent Test**: Trigger a success and an error notification. Verify each uses the correct color from the Design System and is visually distinguishable from the other.

**Acceptance Scenarios**:

1. **Given** the system generates a success notification, **When** the user views the page, **Then** the notification displays in the success color with appropriate visual styling from the Design System
2. **Given** the system generates an error notification, **When** the user views the page, **Then** the notification displays in the error color and is clearly distinguishable from other notification types
3. **Given** multiple notifications exist, **When** the user views the page, **Then** all notifications are visible and individually styled by their type

---

### User Story 4 - Reusable UI Component Library (Priority: P4)

A developer building a new feature finds ready-to-use UI components for the application domain: book cards, author cards, rating badges, navigation elements, modals, and toast notifications — all matching the Claude Design System exactly, requiring no custom styling work.

**Why this priority**: Component reuse accelerates feature development and guarantees visual consistency. Without prebuilt components, each developer reinvents the same UI patterns, leading to inconsistency and wasted effort.

**Independent Test**: Render one Book card and one Author card using the provided components. Verify their visual appearance matches the corresponding Claude Design System specifications.

**Acceptance Scenarios**:

1. **Given** the component library is available, **When** a developer renders a Book card component, **Then** the card displays with layout, typography, and colors matching the Claude Design System book card specification
2. **Given** the component library is available, **When** a developer renders an Author card component, **Then** the card displays correctly styled per the Design System
3. **Given** the component library is available, **When** a developer uses a badge or rating component, **Then** the visual output matches the Design System badge/rating specifications
4. **Given** the component library is available, **When** a developer triggers a modal or toast notification, **Then** it renders with the correct Design System styling and behavior

---

### Edge Cases

- ~~What happens when a flash notification message is very long (multi-line text)?~~ Notification height expands to show full text; no truncation.
- ~~How does the navigation bar display on narrow mobile screens?~~ Mobile: top bar (logo + notification button) + fixed bottom nav (Accueil, Catalogue, Suggestions, Profile).
- ~~What happens if a developer renders a component without providing required data (e.g., a Book card without a title)?~~ Show placeholder: missing text → fallback string ("Sans titre" / "Auteur inconnu"); missing image → placeholder graphic.
- ~~What does a Book/Author card display while data is loading?~~ Skeleton state: animated placeholder shimmer matching the card layout dimensions.
- ~~How do multiple simultaneous toast notifications stack or queue?~~ Stack visibly newest-on-top, max 3 visible, oldest removed when limit exceeded.

## Requirements *(mandatory)*

### Non-Functional Requirements

- **NFR-001**: All interactive components (buttons, modals, toasts, navigation) MUST meet WCAG 2.1 AA standards: color contrast ratio ≥ 4.5:1 for normal text, keyboard navigable, ARIA roles/labels on interactive elements; modals MUST implement a focus trap while open and restore focus to the trigger element on close (WCAG 2.1 AA §2.4.3)
- **NFR-002**: All components MUST use semantic HTML elements appropriate to their role

### Functional Requirements

- **FR-001**: Every application page MUST display a consistent navigation bar at the top containing the application name "La Collection" and the primary navigation links on desktop: Accueil (`/`), Catalogue (`/catalogue`), Suggestions (`/suggestions`). Profile is intentionally absent from the desktop navbar — desktop profile access is via the `/profil` route directly; authentication-aware navigation is out of scope. The navbar MUST be sticky (`position: sticky; top: 0; z-index: 30`) on all viewports, matching `design/landing.html` §HEADER. On mobile: a top bar shows the logo and a notification button (Bootstrap Icons `bi-bell`, 44×44px, `aria-label="Notifications"`, no functional action — notification panel is out of scope for this feature); a fixed bottom navigation bar shows a 5-slot layout — Accueil (`/`), Catalogue (`/catalogue`), FAB central "Suggérer un livre" (slot 3), Suggestions (`/suggestions`), Profil (`/profil` stub placeholder) — matching `design/pages/07-navigation.html` §bottom-nav. Tapping the FAB navigates to `/suggestions/nouveau` (no modal — full page navigation). The active navigation link MUST be highlighted using a Twig route check (`app.request.pathInfo` or route name comparison) in the base layout template — no JavaScript or per-controller variable required.
- **FR-002**: Every application page MUST display a consistent footer at the bottom (desktop); footer is hidden on mobile (bottom nav replaces it). The footer MUST contain: (1) a centered tagline "Façonné par et pour les passionnés des Livres Dont Vous Êtes le Héro." with decorative fleurons (❦) above and below; (2) a three-column grid — brand column (logo + description "Catalogue collaboratif des livres dont vous êtes le héros, romans graphiques à choix et fictions interactives."), "La Collection" links column (Le catalogue · Les auteurs · Les éditions · Les collections), "Communauté" links column (Suggérer un livre · Les contributeurs · Modération · Devenir modérateur · La Taverne (forum)); (3) a bottom bar with "© [year] · La Collection des Aventuriers" and legal links: Mentions légales, CGU, Confidentialité, Cookies. Visual structure follows `design/landing.html` §FOOTER.
- **FR-003**: All approved Design System colors MUST be available as global tokens and applied wherever the Design System specifies their use
- **FR-004**: All approved Design System typography styles MUST be available as global tokens and applied consistently across all pages
- **FR-005**: All approved Design System spacing units, border radii, and visual effects MUST be available as global tokens
- **FR-006**: System notification messages MUST be displayed with visual styling differentiated by type: success, error, warning, and informational. Toasts MUST appear in a fixed container positioned top-right on desktop and full-width at the top on mobile (never at the bottom — would collide with the fixed bottom nav); the container z-index MUST render above all page content.
- **FR-007**: Reusable Book card components MUST be available matching the Claude Design System card specification (`design/pages/05-cards.html §.card-book`); missing title renders "Sans titre", missing cover renders the Design System placeholder (striped `linear-gradient(135deg, var(--cuir-300), var(--cuir-500))` + `.cover-frame` corner decoration); a skeleton/loading state MUST be provided — the shimmer gradient sweeps `var(--bg-elevated)` → `var(--bg-sunken)` → `var(--bg-elevated)` in a 1.5 s linear infinite animation, with placeholder zones matching the card's visual structure (cover area, title block, metadata row, badge row). Cards render hover transitions as defined in the Design System; an optional `href` prop enables link behavior (card title becomes `<a>`; card gains `aria-label` matching the title; `:focus-within` shows `--ring-focus` outline). Active/pressed state is out of scope.
- **FR-008**: Reusable Author card components MUST be available matching the Claude Design System card specification (`design/pages/05-cards.html §.card-author`); missing name renders "Auteur inconnu", missing avatar renders the Design System placeholder (96×96 px circle with `radial-gradient(circle at 35% 30%, var(--cuir-300), var(--cuir-500) 60%, var(--cuir-700))` and initials in 32 px display font); a skeleton/loading state MUST be provided using the same shimmer pattern as FR-007, with placeholder zones matching the Author card layout (portrait circle, name line, role line, stats grid). Same hover/focus/link behavior as FR-007.
- **FR-009**: Badge and rating display components MUST be available matching the Design System specification (`design/assets/components.css`). Badge variants: `.badge` (generic) plus status variants (`.badge-status-pending`, `.badge-status-validated`, `.badge-status-rejected`, `.badge-status-archived`) and role variants (`.badge-role-user`, `.badge-role-mod`, `.badge-role-admin`). Rating is **display-only** for this feature: star icons (★, partial fill via CSS `--fill` custom property), numeric score (`.rating-score`), and vote count (`.rating-count`); sizes `size-sm` / `size-md` / `size-lg` as defined. The interactive `.rating-input` variant is **out of scope** for this feature.
- **FR-010**: Modal and toast notification components MUST be available matching the Design System specification (`design/pages/08-feedback.html`). **Modal**: single generic component with two CSS variants — standard and `.danger-accent` (4 px danger color bar at top) — controlled via a `variant` prop (`default` | `danger`). Modal MUST support three dismiss methods: close button (`aria-label="Fermer"`), ESC key, and click on the overlay backdrop; all three are required. Focus MUST be trapped within the modal while open and restored to the trigger element on close (see NFR-001). **Toast**: notifications MUST auto-dismiss after **5 seconds** and provide a manual close button; when a toast is forcibly removed (stack limit exceeded), its auto-dismiss timer is cancelled by the Stimulus `disconnect()` lifecycle — no leaked timers. Multiple simultaneous toasts stack visibly newest-on-top with a maximum of 3 visible at once (oldest removed when limit exceeded). Long toast messages expand vertically — no truncation (consistent with Key Entities §Flash Notification). The toast container MUST declare `aria-live="polite"` and `aria-atomic="false"`; error and warning toast elements MUST additionally use `role="alert"` for assertive screen-reader announcement; toasts MUST NOT steal keyboard focus on insertion. The Toast Twig component MAY be used independently of FlashBag via direct invocation — the developer supplies `type`, `title`, and `message` props. Notification message body MUST be rendered as plain text only (no HTML) to prevent XSS.
- **FR-011**: New page templates MUST be creatable by extending a single base layout without duplicating header/footer markup
- **FR-012**: All visual output MUST strictly match the Claude Design System — no invented or approximated design values are permitted
- **FR-013**: All button and form component CSS classes defined in `design/assets/components.css` MUST be available globally: button variants (`.btn`, `.btn-primary`, `.btn-secondary`, `.btn-ghost`, `.btn-danger`, `.btn-sm`, `.btn-lg`, `.btn-icon`, `.btn-block`), form controls (`.input`, `.textarea`, `.select`), choice controls (`.choice`), form layout helpers (`.form-group`, `.form-label`, `.form-help`). All property values MUST strictly match `design/assets/components.css` — no invented or approximated values.

### Key Entities

- **Design Token**: A named, globally accessible value from the Claude Design System (color, font, spacing, radius). Has a name, value, and category.
- **Base Layout**: The shared page structure inherited by all application pages. Contains navigation, main content area, footer, and notification display zone.
- **UI Component**: A reusable, self-contained visual building block (Book card, Author card, Badge, Rating, Modal, Toast, Navbar, Footer) implemented as a Symfony UX Twig Component (typed PHP class + Twig template). Belongs to a component category.
- **Flash Notification**: A transient system message tied to a user action. Has a type (success/error/warning/info) that determines its visual style. Dismisses automatically after **5 seconds** and supports manual close via a dismiss button. Notification height expands to accommodate long text; no truncation. Message body is always plain text — no HTML rendering.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of design token values (colors, typography, spacing, radii, effects) from the Claude Design System are defined and available globally — zero invented or approximated values
- **SC-002**: A developer can create a fully styled new application page by extending the base layout and using existing components in under 30 minutes with no custom styling required. Test procedure: a developer with no prior codebase knowledge, following `contracts/layout.md` usage example only, creates a new route + template; the task must complete within 30 minutes with zero lines of custom CSS written.
- **SC-003**: All 8 component categories defined in the Design System (Colors, Typography, Spacing/Effects, Buttons/Forms, Book/Author Cards, Badges/Ratings, Navigation/Avatars, Modals/Toasts) have at least one corresponding reusable component available; Book/Author card components each include a skeleton loading variant. **Category mapping**: for token-only categories (Colors, Typography, Spacing/Effects), the global SCSS token system (`assets/styles/tokens/`) constitutes the reusable component — no dedicated Twig class is required. "Book/Author Cards" is one category label covering two distinct Twig components: `Book:Card` (FR-007) and `Author:Card` (FR-008). SC-003 is satisfied when all 8 categories have at least one implementation — a single minimal implementation per category meets the criterion.
- **SC-004**: Flash notifications of all 4 types (success, error, warning, info) display with visually distinct styles that pass a side-by-side comparison with the Design System specification
- **SC-005**: Any new page that extends the base layout automatically inherits the navbar and footer without any additional configuration by the developer
- **SC-006**: The application build process completes successfully in a cloud deployment environment (Platform.sh) without manual intervention; asset compilation runs via Platform.sh build hook (`npm ci && npm run build`); compiled assets are NOT committed to git
- **SC-007**: All interactive components pass WCAG 2.1 AA color contrast checks (≥ 4.5:1 for normal text, ≥ 3:1 for large text) and are keyboard-navigable

## Clarifications

### Session 2026-05-23

- Q: Which asset management tool should be used? → A: Webpack Encore (symfony/webpack-encore-bundle, Node/npm pipeline, SCSS support)
- Q: How do flash/toast notifications dismiss? → A: Both auto-dismiss after timeout and manual close button
- Q: How are reusable UI components implemented in Twig? → A: Symfony UX Twig Components (symfony/ux-twig-component, typed PHP+Twig)
- Q: What accessibility compliance level is required? → A: WCAG 2.1 AA (color contrast ≥4.5:1 for text, keyboard navigation, ARIA on interactive elements)
- Q: What are the primary navigation links in the navbar? → A: Accueil, Catalogue, Suggestions
- Q: Which JavaScript interactivity approach should be used for modals, toast auto-dismiss, and navbar collapse? → A: Stimulus (symfony/ux-stimulus-bundle)
- Q: Which Bootstrap version should be used? → A: Bootstrap 5.x (no jQuery, modern CSS custom properties)
- Q: How do multiple simultaneous toast notifications behave? → A: Stack visibly newest-on-top, max 3 visible; oldest removed when limit exceeded
- Q: How does navigation display on mobile? → A: Top bar with logo + notification button; 5-slot bottom navigation bar: Accueil, Catalogue, FAB "Suggérer un livre" (central, slot 3), Suggestions, Profil — per `design/pages/07-navigation.html` §bottom-nav
- Q: How do Book/Author card components handle missing required data? → A: Show placeholder (missing text → fallback string e.g. "Sans titre"; missing image → placeholder graphic)

### Session 2026-05-23 (continued)

- Q: What is the toast auto-dismiss timeout duration? → A: 5 seconds
- Q: What happens when a flash notification message is very long (multi-line text)? → A: Notification height expands to show full text; no truncation
- Q: What happens when the FAB "Suggérer un livre" is tapped on mobile? → A: Navigates to a dedicated `/suggestions/nouveau` page
- Q: Where does Webpack Encore asset compilation run in deployment? → A: Platform.sh build hook (`npm ci && npm run build`); compiled assets NOT committed to git
- Q: Are flash/toast notification messages rendered as plain text or HTML? → A: Plain text only — no HTML rendered in notification body (XSS prevention)
- Q: Are loading/skeleton states in scope for Book/Author card components? → A: In scope — cards MUST display a skeleton/placeholder state while data loads
- Q: Is creating the Symfony application itself in scope for this feature? → A: Yes — Symfony app creation (symfony new, composer.json, initial project scaffold) is in scope for this feature
- Q: Which icon library should be used for UI icons (mobile nav icons, FAB icon, card actions, badges)? → A: Bootstrap Icons (bi-*) — SVG sprite or webfont, no additional external dependency
- Q: How should the active navigation link be determined and rendered? → A: Twig route check — compare `app.request.pathInfo` or route name in base layout template; no JS or PHP controller variable required
- Q: How is "no invented or approximated design values" (FR-012 / SC-001) validated? → A: Code review against `design/assets/tokens.css` and `design/assets/components.css` during PR review — no automated tooling required for this feature

## Assumptions

- The Symfony application does not yet exist; creating the application from scratch (running `symfony new`, configuring `composer.json`, installing base dependencies) is **in scope** for this feature
- Asset management will use **Webpack Encore** (symfony/webpack-encore-bundle) with a Node/npm pipeline, supporting SCSS compilation and Bootstrap integration; compiled assets are NOT committed to git and are built via Platform.sh build hook (`npm ci && npm run build`)
- The application's navigation bar will display the application name "La Collection" as its primary brand identifier with primary navigation links: Accueil, Catalogue, Suggestions
- The Claude Design System URL provided (`https://api.anthropic.com/v1/design/h/6HOW05yv9g076lLPgk0_cA`) is the authoritative source for all design token values — it must be fetched and read before any implementation begins
- Mobile responsiveness is in scope but mobile-specific breakpoints follow Bootstrap defaults unless Claude Design specifies otherwise
- Dark mode is out of scope for this initial foundation
- The component library covers the 8 categories listed in the feature description; additional components are out of scope
- Authentication and user-specific navigation states (logged in / logged out) are out of scope for the base layout at this stage
- All UI components are implemented using Symfony UX Twig Components (symfony/ux-twig-component); plain Twig includes are not used for the component library
- All JavaScript interactivity (modal open/close, toast auto-dismiss, navbar collapse) is handled via **Stimulus** (symfony/ux-stimulus-bundle); no Alpine.js or vanilla JS custom scripts
- Bootstrap version is **5.x** (no jQuery dependency; CSS custom properties; modern utility API)
- Icon library is **Bootstrap Icons** (bi-*); used for all UI icons including mobile nav icons, FAB icon, card actions, and badges; integrated via npm package (`bootstrap-icons`); no other icon library is in scope
- Design System token versioning and update procedures are **out of scope** for this feature; tokens are treated as compile-time constants from the local `design/` directory
- Graceful degradation when JavaScript is disabled is **out of scope**; the application requires JavaScript for Bootstrap navbar collapse and Stimulus interactive components
- Print media styling is **out of scope** for this feature
- Right-to-left (RTL) layout support is **out of scope** for this feature
- CSS/JS bundle size limits are not defined for this MVP; SC-006 (successful Platform.sh build) is the only build quality gate
