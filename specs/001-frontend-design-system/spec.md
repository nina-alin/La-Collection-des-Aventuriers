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
2. **Given** the application is running on mobile, **When** a visitor opens any page, **Then** a top bar shows the logo and notification button, and a fixed bottom navigation bar shows Accueil, Catalogue, Suggestions, Profile
3. **Given** the application is running, **When** a visitor opens any page on desktop, **Then** a footer appears at the bottom with consistent branding
4. **Given** a new page template is created by a developer, **When** the template extends the base layout, **Then** it automatically inherits the navbar, footer, and global styles

---

### User Story 2 - Design Token Consistency (Priority: P2)

A developer creates a new page or UI element and has immediate access to all approved colors, typography scales, spacing units, and visual effects from the Claude Design System without needing to look them up or define them manually.

**Why this priority**: Design token consistency prevents visual drift across the application. Without a shared source of truth for design values, individual pages will diverge from the approved design over time.

**Independent Test**: Create a minimal test page. Apply a primary color, a heading style, and a button using only globally available design tokens. Verify they match the Claude Design System specifications.

**Acceptance Scenarios**:

1. **Given** the design system is integrated, **When** a developer applies the primary brand color to any element, **Then** the displayed color exactly matches the approved Claude Design System value
2. **Given** the design system is integrated, **When** a developer uses a heading style, **Then** the font family, size, and weight match the approved typography specifications
3. **Given** the design system is integrated, **When** a developer uses a spacing unit, **Then** the resulting spacing matches the approved spacing scale

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
- ~~How do multiple simultaneous toast notifications stack or queue?~~ Stack visibly newest-on-top, max 3 visible, oldest removed when limit exceeded.

## Requirements *(mandatory)*

### Non-Functional Requirements

- **NFR-001**: All interactive components (buttons, modals, toasts, navigation) MUST meet WCAG 2.1 AA standards: color contrast ratio ≥ 4.5:1 for normal text, keyboard navigable, ARIA roles/labels on interactive elements
- **NFR-002**: All components MUST use semantic HTML elements appropriate to their role

### Functional Requirements

- **FR-001**: Every application page MUST display a consistent navigation bar at the top containing the application name "La Collection" and the primary navigation links: Accueil, Catalogue, Suggestions (desktop). On mobile: top bar shows logo + notification button only; a fixed bottom navigation bar shows Accueil, Catalogue, Suggestions, Profile links.
- **FR-002**: Every application page MUST display a consistent footer at the bottom (desktop); footer is hidden on mobile (bottom nav replaces it)
- **FR-003**: All approved Design System colors MUST be available as global tokens and applied wherever the Design System specifies their use
- **FR-004**: All approved Design System typography styles MUST be available as global tokens and applied consistently across all pages
- **FR-005**: All approved Design System spacing units, border radii, and visual effects MUST be available as global tokens
- **FR-006**: System notification messages MUST be displayed with visual styling differentiated by type: success, error, warning, and informational
- **FR-007**: Reusable Book card components MUST be available matching the Claude Design System card specification; missing title renders "Sans titre", missing cover renders a placeholder graphic
- **FR-008**: Reusable Author card components MUST be available matching the Claude Design System card specification; missing name renders "Auteur inconnu", missing avatar renders a placeholder graphic
- **FR-009**: Badge and rating display components MUST be available matching the Design System specification
- **FR-010**: Modal and toast notification components MUST be available matching the Design System specification; toast notifications MUST auto-dismiss after **5 seconds** and provide a manual close button; interactivity implemented via Stimulus controllers; multiple simultaneous toasts stack visibly newest-on-top with a maximum of 3 visible at once (oldest removed when limit exceeded)
- **FR-011**: New page templates MUST be creatable by extending a single base layout without duplicating header/footer markup
- **FR-012**: All visual output MUST strictly match the Claude Design System — no invented or approximated design values are permitted

### Key Entities

- **Design Token**: A named, globally accessible value from the Claude Design System (color, font, spacing, radius). Has a name, value, and category.
- **Base Layout**: The shared page structure inherited by all application pages. Contains navigation, main content area, footer, and notification display zone.
- **UI Component**: A reusable, self-contained visual building block (Book card, Author card, Badge, Rating, Modal, Toast, Navbar, Footer) implemented as a Symfony UX Twig Component (typed PHP class + Twig template). Belongs to a component category.
- **Flash Notification**: A transient system message tied to a user action. Has a type (success/error/warning/info) that determines its visual style. Dismisses automatically after **5 seconds** and supports manual close via a dismiss button. Notification height expands to accommodate long text; no truncation.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of design token values (colors, typography, spacing, radii, effects) from the Claude Design System are defined and available globally — zero invented or approximated values
- **SC-002**: A developer can create a fully styled new application page by extending the base layout and using existing components in under 30 minutes with no custom styling required
- **SC-003**: All 8 component categories defined in the Design System (Colors, Typography, Spacing/Effects, Buttons/Forms, Book/Author Cards, Badges/Ratings, Navigation/Avatars, Modals/Toasts) have at least one corresponding reusable component available
- **SC-004**: Flash notifications of all 4 types (success, error, warning, info) display with visually distinct styles that pass a side-by-side comparison with the Design System specification
- **SC-005**: Any new page that extends the base layout automatically inherits the navbar and footer without any additional configuration by the developer
- **SC-006**: The application build process completes successfully in a cloud deployment environment (Platform.sh) without manual intervention
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
- Q: How does navigation display on mobile? → A: Top bar with logo + notification button; bottom navigation bar with Accueil, Catalogue, Suggestions, Profile links
- Q: How do Book/Author card components handle missing required data? → A: Show placeholder (missing text → fallback string e.g. "Sans titre"; missing image → placeholder graphic)

### Session 2026-05-23 (continued)

- Q: What is the toast auto-dismiss timeout duration? → A: 5 seconds
- Q: What happens when a flash notification message is very long (multi-line text)? → A: Notification height expands to show full text; no truncation

## Assumptions

- The Symfony application does not yet exist and will be created fresh as part of or before this feature
- Asset management will use **Webpack Encore** (symfony/webpack-encore-bundle) with a Node/npm pipeline, supporting SCSS compilation and Bootstrap integration
- The application's navigation bar will display the application name "La Collection" as its primary brand identifier with primary navigation links: Accueil, Catalogue, Suggestions
- The Claude Design System URL provided (`https://api.anthropic.com/v1/design/h/6HOW05yv9g076lLPgk0_cA`) is the authoritative source for all design token values — it must be fetched and read before any implementation begins
- Mobile responsiveness is in scope but mobile-specific breakpoints follow Bootstrap defaults unless Claude Design specifies otherwise
- Dark mode is out of scope for this initial foundation
- The component library covers the 8 categories listed in the feature description; additional components are out of scope
- Authentication and user-specific navigation states (logged in / logged out) are out of scope for the base layout at this stage
- All UI components are implemented using Symfony UX Twig Components (symfony/ux-twig-component); plain Twig includes are not used for the component library
- All JavaScript interactivity (modal open/close, toast auto-dismiss, navbar collapse) is handled via **Stimulus** (symfony/ux-stimulus-bundle); no Alpine.js or vanilla JS custom scripts
- Bootstrap version is **5.x** (no jQuery dependency; CSS custom properties; modern utility API)
