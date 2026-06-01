# Feature Specification: Catalogue Page & Advanced Filtering

**Feature Branch**: `015-catalogue-advanced-filtering`

**Created**: 2026-06-01

**Status**: Draft

**Design Reference**: `design/pages/catalogue.html` — authoritative source for all visual states (Desktop/Mobile, panel open/closed, grid/list view). CSS integration MUST match this mockup exactly.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Browse and Filter the Catalogue (Priority: P1)

A user opens the catalogue page on desktop. A two-column layout shows the filter panel on the left and the results grid on the right. The user selects an editor from the "ÉDITEUR & COLLECTION" section, adjusts the paragraph range slider, and clicks "Appliquer". The grid refreshes showing only matching books, and active filter chips appear above the results.

**Why this priority**: Core use case. Without functional filtering, the page has no unique value over a plain list.

**Independent Test**: Open catalogue page, apply at least two filters via the panel, click "Appliquer", verify result count updates and chips appear.

**Acceptance Scenarios**:

1. **Given** the catalogue page is open on desktop, **When** the user selects two editors and clicks "Appliquer", **Then** the result grid updates to show only books from those editors and two chips appear in the toolbar.
2. **Given** filters are active, **When** the user clicks the "×" on a chip, **Then** that filter is removed and the grid refreshes immediately without requiring "Appliquer".
3. **Given** active filters exist, **When** the user clicks "TOUT EFFACER (X)", **Then** all chips are removed and the full unfiltered catalogue is restored.
4. **Given** the panel has unsaved filter changes, **When** the user clicks "Effacer" inside the panel, **Then** the draft state is cleared without closing the panel and without affecting the already-applied results.

---

### User Story 2 - Filter on Mobile via FAB Modal (Priority: P1)

A user opens the catalogue page on a mobile device. The left panel is hidden. A floating action button "FILTRER & TRIER" with a badge showing the active filter count is pinned at the bottom of the screen. Tapping it opens a full-screen filter modal. The user adjusts filters and taps "Appliquer — X". The modal closes, the grid updates, and the FAB badge updates.

**Why this priority**: Mobile is a primary access pattern; the FAB/modal replaces the desktop panel entirely.

**Independent Test**: Open catalogue on a viewport ≤ 768 px, verify FAB visible and panel hidden, tap FAB, apply one filter, verify modal closes and grid updates.

**Acceptance Scenarios**:

1. **Given** a mobile viewport, **When** the page loads, **Then** the left filter panel is not visible and the FAB is pinned at the bottom.
2. **Given** the FAB is visible, **When** the user taps it, **Then** the filter modal opens in full-screen.
3. **Given** the modal is open with draft filter changes, **When** "Appliquer" is tapped, **Then** the modal closes, the grid refreshes, and the FAB badge shows the new active filter count.
4. **Given** 3 filters are active, **When** the user views the FAB, **Then** the badge shows "3".

---

### User Story 3 - Contextual In-Page Search (Priority: P2)

A user types in the in-page search bar (distinct from the global navbar search). Autocomplete suggestions appear in a dropdown grouped by category (books, authors). Pressing Enter or selecting a suggestion adds a search chip to the active filters and updates the results grid.

**Why this priority**: Speeds up catalogue navigation without requiring the filter panel.

**Independent Test**: Type a partial book title in the in-page search, verify dropdown appears with grouped suggestions, press Enter, verify search chip appears and grid filters accordingly.

**Acceptance Scenarios**:

1. **Given** the user starts typing in the search bar, **When** at least one character is entered, **Then** a dropdown appears with results grouped by category (e.g., "LIVRES — 3 RÉSULTATS", "AUTEURS — 1 RÉSULTAT").
2. **Given** the autocomplete dropdown is open, **When** the user presses Enter, **Then** the dropdown closes, a search chip appears in the toolbar, and the grid is filtered.
3. **Given** a search chip is active, **When** the user clicks its "×", **Then** the text filter is removed and the grid restores to the previous filtered state.

---

### User Story 4 - Toggle Grid / List View (Priority: P2)

A user clicks the list-view icon in the results toolbar. Cards transform from vertical grid cards to horizontal list rows. Clicking the grid icon reverts to the grid layout. The selected view persists for the session.

**Why this priority**: Secondary display preference; does not block core filtering functionality.

**Independent Test**: Click the list toggle icon, verify layout changes to horizontal rows; click grid icon, verify revert.

**Acceptance Scenarios**:

1. **Given** the default grid view is active, **When** the user clicks the list icon, **Then** results display as horizontal rows.
2. **Given** list view is active, **When** the user clicks the grid icon, **Then** results return to vertical card grid.
3. **Given** a view mode is selected, **When** the user applies a filter, **Then** the view mode does not change.

---

### User Story 5 - Sort Results (Priority: P3)

A user changes the sort order via the quick-sort dropdown in the toolbar (e.g., "Trier — A → Z"). The grid reorders immediately. The filter panel's "TRIER PAR" accordion reflects the same selection.

**Why this priority**: Improves browse experience but results are still accessible without sorting.

**Independent Test**: Change sort from the toolbar dropdown, verify grid reorders and the panel accordion shows the matching sort radio selected.

**Acceptance Scenarios**:

1. **Given** the default sort is active, **When** the user selects "A → Z" from the toolbar dropdown, **Then** the grid reorders alphabetically and the "TRIER PAR" radio in the panel switches to "Ordre alphabétique".
2. **Given** the user selects a sort in the panel's "TRIER PAR" section, **When** "Appliquer" is clicked, **Then** the toolbar dropdown reflects the same sort.

---

### Edge Cases

- What happens when no results match the applied filters? → Display an empty-state message with a "Réinitialiser les filtres" shortcut.
- What happens if the paragraph range slider min value exceeds the max? → Clamp: min cannot exceed max, max cannot go below min.
- What happens when the "Voir + X autres éditeurs" list is fully expanded? → Button is hidden once all editors are visible.
- What happens when the user applies filters then resizes the viewport from mobile to desktop? → Applied filters persist; panel opens in collapsed state showing active filters via chips.
- What happens with 0 expected results in the "Appliquer" button? → Button shows "Appliquer — 0" and remains clickable (applying clears the grid to an empty state).

---

## Clarifications

### Session 2026-06-01

- Q: When "Appliquer" is clicked and the grid is loading results, what visual feedback is shown? → A: Skeleton card placeholders replace the current grid content during load.
- Q: Can unauthenticated (guest) users browse and filter the catalogue? → A: Guests can browse and filter; the "STATUT DANS MA COLLECTION" section is hidden entirely for guests.
- Q: How is the live result count on "Appliquer — X" computed (no REST API)? → A: Symfony UX Live Component — filter panel is a Live Component; each draft state change triggers a server re-render with a DQL COUNT query updating the button label automatically.
- Q: How many books per page in the results grid? → A: 24 books per page.
- Q: What accessibility level is required? → A: WCAG 2.1 AA keyboard navigability for filter panel and results grid; no formal audit required.
- Q: When the user closes the filter panel (desktop: collapse; mobile: dismiss modal) without clicking "Appliquer", what state does the panel show when reopened? → A: Draft is reset to the last applied state — closing without applying is an implicit cancel.
- Q: Is the editor internal search field (FR-017) client-side or server-side filtering? → A: Server-side — each keystroke triggers a Live Component re-render fetching matching editors from the database.
- Q: Minimum character count before in-page search autocomplete dropdown appears? → A: 1 character.
- Q: If the server request fails when "Appliquer" is clicked, what happens? → A: Show error toast, restore previous grid content (skeleton replaced by last valid results).
- Q: Is there a debounce delay on Live Component requests for FR-017 (editor search) and FR-023 (draft count updates)? → A: 300 ms debounce on both triggers.
- Q: When a chip "×" is clicked (FR-011) and the user is on page > 1, does pagination reset? → A: Stay on current page; if page no longer exists after filtering, jump to last available page.
- Q: How many editors are shown initially before "Voir + X autres" (FR-018)? → A: 5 editors shown initially; button reveals the remainder.
- Q: What are the min/max bounds for the paragraph range slider (FR-019)? → A: Dynamic — computed from actual min/max paragraph counts in the database at page load.
- Q: When slider bounds are dynamic (FR-019), what happens to preset pills (FR-020) whose fixed values fall outside the actual range? → A: Presets outside the dynamic bounds are hidden at render time.
- Q: Should applying filters update the browser URL (query params) to enable deep linking and browser back-button support? → A: Yes — all active filters are serialized to URL query params on each "Appliquer" click, enabling deep linking, shareable filtered URLs, and browser back/forward navigation.
- Q: When a search chip already exists and the user submits a new search query, what happens to the existing chip? → A: Replace — the new search replaces the existing search chip; only one search chip exists at a time. `FilterDraftState.searchQuery` remains single-valued.
- Q: If the debounced DQL COUNT query (FR-023, updating "Appliquer — X" label) fails, what does the button show? → A: Show "Appliquer" (label only, no count) until the next successful COUNT query; no error toast for this background failure.

### Session 2026-06-01 (Checklist Gap Analysis)

- Q: What is the exact pixel breakpoint separating desktop (two-column) from mobile (FAB) layout? → A: ≥ 880 px = desktop two-column layout with filter rail. ≤ 879 px = filter panel hidden, FAB shown. (Derived from design CSS: `@media (min-width: 880px)`.) The "≤ 768 px" figure in User Story 2 was imprecise narrative; 880 px is authoritative.
- Q: Which filter accordion sections display an active-criteria badge and what does it show? → A: "ÉDITEUR & COLLECTION" shows a numeric count of checked editors (e.g. "3"). "FORMAT · PARAGRAPHES" shows the active range value (e.g. "200-450"). "TRIER PAR", "CRÉATEUR", and "STATUT DANS MA COLLECTION" sections display no badge.
- Q: Are book card states (owned, favourite, wishlist) mutually exclusive or combinable? → A: Combinable. Cards can simultaneously display multiple `.cover-marks` icons (e.g. owned checkmark + favourite heart). The three mark types are: owned (green checkmark), favourite (red heart), wishlist (amber cart). Marks stack horizontally in the top-right corner.
- Q: What does the "hover" state of a book card consist of — is there a metadata overlay? → A: No metadata overlay. The hover state is a lift effect only: `translateY(-2px)`, `box-shadow: var(--shadow-md)`, and `border-color: var(--border-strong)`. FR-026's reference to "metadata overlay" was imprecise; the design reference shows no overlay panel.
- Q: When "Effacer" (FR-025a) is clicked, what state does it reset the draft to — empty/initial or last applied state? → A: Last applied state. "Effacer" is an in-panel cancel that discards pending changes, identical in intent to closing the panel without applying (FR-025c). It does NOT reset to an empty state.
- Q: "Voir + X autres éditeurs" — what does X represent, and is there a scrollable container or max-height? → A: X is the count of editors not yet visible in the list. The list extends within the panel's scrollable `.filters-body` container (which has `overflow-y: auto` and `max-height: calc(100vh - 96px)`); no secondary max-height is applied to the editors list itself.
- Q: Does changing sort order from the toolbar dropdown require clicking "Appliquer", or is it immediate? → A: Immediate from the toolbar. Selecting a sort option from the toolbar `<select>` reorders the grid directly without requiring "Appliquer". Sort selection inside the panel's "TRIER PAR" accordion is part of draft state and commits only when "Appliquer" is clicked. The two sync bidirectionally: toolbar change updates the panel radio; panel change + apply updates the toolbar.
- Q: The toolbar sort dropdown and the panel "TRIER PAR" radio buttons appear to have different option labels. Intentional subset or should they unify? → A: Unified. The panel "TRIER PAR" section is the authoritative sort definition. The toolbar mirrors the same options with shorter labels. Canonical mapping (panel label → toolbar label): "Note moyenne (10/10)" → "Trier · Note décroissante"; "Ordre alphabétique" → "Trier · A → Z"; "Année · parution France" → "Trier · Parution récente"; "Année · édition originale" → "Trier · Parution ancienne"; "Récemment ajouté au wiki" → "Trier · Récemment ajouté". Toolbar always exposes all five options.
- Q: Are card title and author text truncated, and if so how? → A: Card titles are clamped to 2 lines (`-webkit-line-clamp: 2`). Author text is clamped to 1 line. This is specified by the design reference. Chip label text has no explicit truncation; chips wrap to new lines within the `.filter-chips` flex container. No maximum chip label width is defined.
- Q: Chip "×" buttons and slider handles are 18×18 px in the design — below WCAG 2.5.5 Level AAA (44×44 px target). Accepted tradeoff? → A: Accepted design tradeoff. Visible sizes match the design reference exactly. No expanded transparent hit areas are required.
- Q: Is the exclusion of view mode from URL query params a deliberate product decision? → A: Yes. Explicitly stated in Assumptions: "View mode (grid/list) persists for the session but not across page reloads; it is not included in URL query params."
- Q: Does the in-page search chip count toward the FAB badge number? → A: Yes. All active filter criteria — including the search chip — are counted in the FAB badge and the "TOUT EFFACER (X)" count.
- Q: When the page loads from a deep-linked URL with filter params, does the skeleton show before first results? → A: Yes. URL params are parsed server-side during initial page render; the Live Component renders with the initial ActiveFilterState already applied, but the grid area shows skeleton placeholders until the first results hydration completes client-side.
- Q: Zero autocomplete results — hide dropdown or show "Aucun résultat"? → A: Hide the dropdown entirely. No empty-state row is shown.
- Q: Rapid consecutive "Appliquer" clicks — disable button while in-flight? → A: Yes. The "Appliquer" button MUST be disabled from click until the grid update completes or error recovery (FR-026b) is triggered.
- Q: Error handling for (A) editor search (FR-017) Live Component failure mid-typing and (B) in-page autocomplete (FR-007) request failure? → A: Both fail silently. (A) The last visible editor list remains unchanged; no error shown. (B) The autocomplete dropdown is hidden; the search input remains editable. No toast for either background failure.
- Q: Error handling when slider bounds DB fetch fails at page load (FR-019)? → A: A non-blocking error toast is shown. The slider falls back to a hardcoded range (0 § min, 999 § max). Preset pills remain visible since all preset values fall within 0–999.
- Q: Mobile filter modal (FR-005) — is focus trapping required? → A: Yes. When the modal is open, keyboard focus MUST be trapped within the overlay (Tab/Shift+Tab cycle inside the modal; Escape closes and returns focus to the FAB).
- Q: Are `aria-live` regions required for dynamic content updates? → A: Yes. The result count (FR-009), chip add/remove (FR-010/011), and skeleton→results transition MUST be announced via `aria-live="polite"` regions so screen reader users are informed of grid updates.
- Q: Does the "STATUT DANS MA COLLECTION" section render in the DOM for guests, or absent entirely? → A: Absent from the DOM. Server-side rendering omits the section for unauthenticated users entirely.
- Q: What happens if the user's auth session expires mid-filtering? → A: Handled by the auth middleware (feature 002). The next Live Component request returns a redirect response; the Live Component framework reloads the page. Not a scenario specific to this feature.
- Q: Pagination on "Appliquer" (FR-024) and "TOUT EFFACER" (FR-012) — do they reset to page 1? → A: Yes. Both Appliquer and TOUT EFFACER reset pagination to page 1. The reset is reflected in URL params. Chip removal (FR-011) stays on the current page.
- Q: What happens when backend filter data endpoints (features 006/009) are unavailable at page load? → A: A non-blocking error toast is shown. Filter panel sections (editors, paragraph bounds, collection statuses) render empty. The results grid shows the unfiltered catalogue with default sort. The user can still browse and use in-page search.

---

## Requirements *(mandatory)*

### Functional Requirements

#### Layout & Responsive

- **FR-001**: On wide viewports (≥ 880 px), the page MUST display a two-column layout: collapsible filter panel anchored left, results area on the right.
- **FR-002**: The filter panel MUST be collapsible/expandable via a chevron button; collapsed state hides panel content and expands the results area.
- **FR-003**: On narrow viewports (≤ 879 px), the filter panel MUST be hidden and replaced by a floating action button (FAB) pinned at the bottom of the screen.
- **FR-004**: The FAB MUST display a badge with the count of currently active filters (including the search chip); badge MUST update when filters change, including immediately when a chip is removed (FR-011).
- **FR-005**: Tapping the FAB MUST open the filter interface as a full-screen modal overlay. When the modal is open, keyboard focus MUST be trapped within the overlay (Tab/Shift+Tab cycle inside; Escape closes and returns focus to the FAB).

#### In-Page Search

- **FR-006**: The in-page search bar MUST be distinct from the global navbar search and scope results to the catalogue.
- **FR-007**: The search bar MUST display an autocomplete dropdown with results grouped by category as the user types. The dropdown MUST appear after the first character is entered (minimum threshold: 1 character). If the autocomplete request fails, the dropdown is hidden silently (no error toast). If the query matches zero results, the dropdown is hidden entirely (no empty-state row).
- **FR-008**: Submitting the search (Enter key or selection) MUST add a search chip to the active filter bar and update the results grid. Only one search chip exists at a time; submitting a new query replaces the existing search chip.

#### Results Toolbar

- **FR-009**: The toolbar MUST display a count of filtered results versus the total catalogue size (e.g., "186 LIVRES — SUR 2 481 FICHES"). This count MUST be announced via an `aria-live="polite"` region whenever it changes.
- **FR-010**: Each active filter criterion MUST generate a chip in the toolbar; chips MUST show a label and a "×" button. Chip add and removal MUST be announced via an `aria-live="polite"` region.
- **FR-011**: Clicking the "×" on a chip MUST remove that filter and immediately refresh the grid (no "Appliquer" required). Pagination stays on the current page; if the current page no longer exists after filtering, the user is redirected to the last available page. The FAB badge MUST update immediately when a chip is removed.
- **FR-012**: A "TOUT EFFACER (X)" button MUST clear all active filters at once, refresh the grid, reset pagination to page 1, and update URL params to reflect the cleared state (pushing a history entry).
- **FR-013**: A quick-sort dropdown in the toolbar MUST allow changing the sort order with immediate grid reorder (no "Appliquer" required). Its state MUST stay bidirectionally synchronized with the "TRIER PAR" section in the filter panel: selecting a toolbar option updates the panel radio; committing a panel sort via "Appliquer" updates the toolbar dropdown. Toolbar option labels: "Trier · Note décroissante", "Trier · A → Z", "Trier · Parution récente", "Trier · Parution ancienne", "Trier · Récemment ajouté" (mapping to the five panel radio options in FR-016).
- **FR-014**: Two view-toggle buttons (grid / list) MUST be present; clicking one MUST instantly switch the results layout.

#### Filter Panel — Accordion Sections

- **FR-015**: Each filter section MUST be an accordion: collapsible with a chevron indicator. Active-criteria badges: "ÉDITEUR & COLLECTION" MUST show a numeric count of checked editors; "FORMAT · PARAGRAPHES" MUST show the active range value (e.g., "200-450"). All other sections display no badge.
- **FR-016**: "TRIER PAR" MUST offer five mutually exclusive radio buttons: "Note moyenne (10/10)", "Ordre alphabétique", "Année · parution France", "Année · édition originale", "Récemment ajouté au wiki". These are the authoritative sort options; the toolbar dropdown mirrors them with abbreviated labels (see FR-013 mapping).
- **FR-017**: "ÉDITEUR & COLLECTION" MUST include an internal search field that filters the visible list of editors in real time via a server-side Live Component re-render (DQL query per keystroke, debounced at 300 ms); results are NOT filtered from a pre-loaded client-side list. If the Live Component request fails, the last visible editor list remains unchanged with no error shown (silent degradation).
- **FR-018**: The editors list MUST render checkboxes with editor name and associated book count. The list MUST initially show 5 entries; a "Voir + X autres" button MUST progressively reveal all remaining entries. The button is hidden once all editors are visible.
- **FR-019**: "FORMAT — PARAGRAPHES" MUST include a dual-handle range slider with dynamic text labels updating at each handle position. Slider bounds (min/max) MUST be computed from the actual minimum and maximum paragraph counts in the database, fetched at page load. If the bounds fetch fails, a non-blocking error toast is shown and the slider falls back to a hardcoded range of 0 § min / 999 § max; all preset pills remain visible.
- **FR-020**: Preset pills (e.g., "< 200 §", "200–400 §") MUST update the slider position when clicked; moving the slider MUST deactivate any active pill. Preset pills whose fixed values fall entirely outside the dynamic slider bounds MUST be hidden at render time.
- **FR-021**: "STATUT DANS MA COLLECTION" MUST include a primary dropdown ("Tous les livres", "Dans ma collection", "À acheter (chasse)", "À lire", "Lus", "Pas dans ma collection") and two boolean toggle switches ("Uniquement mes favoris", "Cacher les fiches en modération"). This section MUST be absent from the DOM entirely for unauthenticated (guest) users (server-side omission, not CSS hiding); all other filter sections remain available.

#### Panel Footer — Batch Filtering

- **FR-022**: Interactions within the filter panel (checking boxes, moving sliders) MUST NOT trigger live grid updates; they MUST update a draft/pending state only. The "Appliquer" button MUST be disabled from the moment it is clicked until the grid update completes or error recovery (FR-026b) is triggered; it MUST visually reflect the disabled state.
- **FR-023**: The "Appliquer" button MUST show the live expected result count (e.g., "Appliquer — 186") updating as draft state changes. This count is computed via a Symfony UX Live Component re-render triggered on each draft state mutation (debounced at 300 ms), running a DQL COUNT query server-side. If the COUNT query fails, the button MUST display "Appliquer" (label only, no count) until the next successful response; no error toast is shown for this background failure.
- **FR-024**: Clicking "Appliquer" MUST commit the draft state, refresh the grid, generate chips, reset pagination to page 1, close the modal on mobile, and serialize all active filter state (including page=1 reset) to URL query params (enabling deep linking, browser back/forward, and shareable filtered URLs). On page load, any filter params present in the URL MUST be parsed and applied as the initial ActiveFilterState; the grid area MUST show skeleton placeholders until the first results hydration completes client-side.
- **FR-025a**: Clicking "Effacer" MUST reset the draft state within the panel without closing the panel and without affecting the already-applied grid results.
- **FR-025c**: Closing the filter panel without clicking "Appliquer" (desktop: chevron collapse; mobile: modal dismiss) MUST silently reset the draft state to the last applied state. No confirmation prompt is shown.
- **FR-026b**: If the server request triggered by "Appliquer" fails (network error or 5xx), the skeleton MUST be replaced by the previous grid content and a non-blocking error toast MUST be displayed. No retry button is shown.

#### Results Grid & Pagination

- **FR-025b**: When "Appliquer" is clicked, the current grid MUST immediately be replaced by skeleton card placeholders until results arrive; no spinner overlay is used. The skeleton→results transition MUST be announced via an `aria-live="polite"` region.
- **FR-026**: Book cards MUST support the following visual states: normal; hover (lift effect: `translateY(-2px)`, stronger shadow, stronger border — no overlay panel); favourite (red heart mark in top-right corner); owned (green checkmark mark in top-right corner); wishlist (amber cart mark). States are combinable: a card may simultaneously show owned + favourite marks. Card title text is clamped to 2 lines; author text to 1 line.
- **FR-027**: The page MUST include a pagination block with "Précédent", numbered page buttons, "Suivant", and a textual indicator (e.g., "Page 3 sur 24"). Results are paginated at 24 books per page.

### Key Entities

- **FilterDraftState**: Pending user selections within the panel not yet applied to the grid. Attributes: sort, editors[], paragraphRange{min, max}, collectionStatus, onlyFavorites, hideModeration, searchQuery. Lifecycle: initialized from ActiveFilterState on panel open; reset back to ActiveFilterState if the panel is closed without applying.
- **ActiveFilterState**: Committed filters currently shaping the displayed results. Mirrors FilterDraftState fields; drives chip generation and FAB badge count.
- **ResultChip**: A UI token representing one active filter. Attributes: label, filterKey, onRemove callback.
- **BookCard**: A displayable book entry. Attributes: title, author, editor, paragraphCount, coverUrl, averageRating, isFavorite, isOwned, status.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can apply a multi-criteria filter combination and see updated results in under 1 second after clicking "Appliquer".
- **SC-002**: The active filter state (chips, FAB badge, sort dropdown) is always consistent — no scenario where a chip exists but the panel does not reflect it.
- **SC-003**: On mobile, 100% of filter interactions are reachable through the FAB modal without requiring a desktop layout.
- **SC-004**: Removing a chip via "×" refreshes the grid without any full-page reload visible to the user.
- **SC-005**: The "Appliquer" button result count is accurate: it matches the count shown in the results toolbar after apply.
- **SC-006**: Grid/list toggle switches layout with no content loss or duplication.
- **SC-007**: Filter panel accordions, checkboxes, sliders, and chips are fully keyboard-navigable (Tab, Enter, Space, arrow keys) conforming to WCAG 2.1 AA. All interactive elements carry appropriate ARIA labels.

---

## Assumptions

- The design reference file `design/pages/catalogue.html` is the authoritative visual specification; pixel-level fidelity to this mockup is required.
- Filter data (editors list, paragraph range bounds, collection statuses) is available from existing backend endpoints introduced in prior features (006-collection-entity, 009-book-review-rating).
- The authenticated user context (favorites, owned books) is provided by the session established in 002-user-auth-oauth2 and 004-rbac-roles-permissions. Unauthenticated guests may browse and filter the catalogue freely; the "STATUT DANS MA COLLECTION" filter section is hidden for guests.
- "Batch filtering" (draft state, no live refresh) is a deliberate UX choice. The filter panel is implemented as a Symfony UX Live Component; each draft state change triggers a server re-render running a DQL COUNT query to update the "Appliquer — X" button label. The results grid itself does NOT re-render until "Appliquer" is clicked.
- Pagination: "Appliquer" and "TOUT EFFACER" both reset to page 1 (explicit in FR-024 and FR-012). Chip removal (FR-011) stays on current page. These are different actions with different pagination behavior.
- The in-page search autocomplete queries a different endpoint than the global omnibox (012-omnibox-global-search) and returns catalogue-scoped results only.
- View mode (grid/list) persists for the session but not across page reloads; it is not included in URL query params.
- Active filter state IS serialized to URL query params on each "Appliquer" click. On page load, URL params are parsed to rehydrate the initial ActiveFilterState.
