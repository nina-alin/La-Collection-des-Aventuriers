# Quality Audit Checklist: Menu Profil Utilisateur Responsive

**Purpose**: Deep full-coverage audit of requirement quality — completeness, clarity, consistency, conflict detection, and hidden assumptions across all domains
**Created**: 2026-05-31
**Feature**: [spec.md](../spec.md)
**Depth**: Deep | **Audience**: Reviewer (pre-implementation gate) | **Scope**: All domains

---

## Conflicts & Internal Contradictions

- [x] CHK001 - Is the loading skeleton contradiction resolved? Edge Cases states "Afficher un skeleton pendant le chargement" but FR-007, FR-008, FR-010, and Clarifications all explicitly state "aucun skeleton". Which is authoritative? [Conflict, Spec §FR-007, §FR-008, §FR-010 vs §Edge Cases]
  > **Resolved**: Current spec Edge Cases contain no "skeleton" mention — only "Afficher '—' silencieusement". FR-007/FR-008/FR-010 + Clarifications are consistent: no skeleton, no async fetch.
- [x] CHK002 - Is FR-007/FR-008 error handling aligned with Clarifications? FR-007/FR-008 say data is loaded at render with no mention of failure, but Clarification Q7 introduces a "—" fallback for Repository errors. Are FR-007/FR-008 missing an explicit error-handling clause? [Conflict, Spec §FR-007, §FR-008]
  > **Resolved**: Clarification Q7 + Research Decision 3 supply the error handling: null-check in Twig → display "—". ProfileMenuDto.rankName is typed `?string`; service catches failures and returns null. Sufficient.
- [x] CHK003 - Is the Avatar fallback behavior consistently specified? FR-005 describes avatar display for all connected users, but Edge Cases independently introduces an "initiales ou icône générique" fallback without specifying which one (initials OR generic icon is ambiguous). [Conflict, Spec §FR-005 vs §Edge Cases]
  > **Resolved**: Research Decision 4 is authoritative: render `<span class="avatar">{{ app.user|user_initials }}</span>` using the existing UserInitialsExtension filter.
- [x] CHK004 - Are RBAC DOM-exclusion requirements consistent for all roles? FR-006 specifies badge exclusion for standard users and FR-009 specifies moderation section exclusion for standard users, but neither FR nor Success Criteria addresses what happens when a user holds both Modérateur AND Administrateur roles simultaneously — the Assumption covers badge display but not the moderation section visibility. [Conflict, Spec §FR-006, §FR-009 vs §Assumptions]
  > **Resolved**: `config/packages/security.yaml` defines `ROLE_ADMIN: [ROLE_MODERATOR]`. `is_granted('ROLE_MODERATOR')` returns true for admins. Moderation section is visible to both roles. Badge uses `highestRole` from ProfileMenuDto (ROLE_ADMIN wins). Consistent.

---

## Requirement Completeness

- [x] CHK005 - Is dropdown anchor positioning fully specified? FR-001 says "ancré sous l'avatar" but does not define horizontal alignment (left-aligned, right-aligned, centered), maximum width, or overflow behavior when the avatar is near the viewport edge. [Completeness, Spec §FR-001]
  > **Resolved**: `menus.css` + `design/dashboard.html` are authoritative per FR-016. `.menu-card[data-anchor="user"]::before { right: 22px; }` handles alignment. No new CSS decisions needed.
- [x] CHK006 - Is the drag handle interactivity specified? FR-003 describes "une poignée/barre de drag en haut du panneau" but does not state whether it is interactive (dragging it closes the drawer) or purely decorative. [Completeness, Spec §FR-003]
  > **Resolved**: Drag handle is a CSS `::after` pseudo-element in `menus.css` (line 80–87) — purely decorative, no HTML element, no JS event. FR-003 satisfied by CSS alone.
- [x] CHK007 - Is the swipe-to-close threshold quantified? FR-004 mentions swipe detection via native touch events but defines no minimum displacement, velocity threshold, or angle tolerance for triggering close. [Completeness, Spec §FR-004]
  > **Resolved**: Research Decision 5 + Stimulus contract: threshold = 80px downward (`(endY - startY) > 80`).
- [x] CHK008 - Is animation duration specified? SC-002 states the animation should be "perçue comme instantanée" but provides no concrete millisecond value or CSS transition spec. [Completeness, Spec §SC-002]
  > **Resolved**: SC-002 specifies "≤300ms". `menus.css` uses `transition: transform var(--motion-slow) var(--motion-ease-out)`. Concrete.
- [x] CHK009 - Is z-index or stacking context specified for the dropdown/drawer? Neither FR-001 nor FR-002 mentions how the component stacks against other page elements (sticky headers, modals, toasts). [Completeness, Gap]
  > **Resolved**: `menus.css` handles z-index for the design system. Component reuses existing CSS as-is. No new stacking decisions needed.
- [x] CHK010 - Is max-height or scroll behavior specified for the dropdown on desktop? If menu content exceeds viewport height, no requirements define overflow handling. [Completeness, Gap]
  > **Resolved**: Handled by existing `menus.css`. Design system is source of truth per FR-016.
- [x] CHK011 - Is scroll behavior specified for the mobile drawer? FR-002 describes a full-screen drawer but does not specify whether internal content scrolls or whether the drawer itself has fixed height constraints. [Completeness, Spec §FR-002]
  > **Resolved**: `menus.css` sets `max-height: 88vh; display: flex; flex-direction: column;` on mobile. Scroll is CSS-driven within that constraint.
- [x] CHK012 - Are ARIA requirements specified for the toggle switch? FR-011 defines the toggle behavior but has no ARIA spec (e.g., `aria-checked`, `aria-label`, `role="switch"` or `role="menuitemcheckbox"`). [Completeness, Spec §FR-011, Gap]
  > **Resolved**: Toggle row is `<button class="menu-toggle-row">` (native button semantics). Inner `<input type="checkbox" id="theme-switch-menu">` carries `aria-checked` implicitly via `checked` attribute. `quickstart.md` specifies `role="menuitem"` or `role="button"` for the button. Sufficient.
- [x] CHK013 - Are ARIA requirements specified for the drag handle? FR-003 introduces the drag handle element but no ARIA role or label is defined. [Completeness, Spec §FR-003, Gap]
  > **Resolved**: Drag handle is CSS `::after` pseudo-element only — no HTML element exists, no ARIA needed.
- [x] CHK014 - Are ARIA requirements specified for the mobile sticky footer links? FR-014 defines the footer links but provides no ARIA requirements. [Completeness, Spec §FR-014, Gap]
  > **Resolved**: User Story 7 deleted. FR-014 explicitly states "aucun footer sticky supplémentaire". No mobile footer links in scope.
- [x] CHK015 - Is `aria-controls` linking the trigger to the menu container specified? FR-017 defines `aria-haspopup` and `aria-expanded` on the avatar but omits `aria-controls` (linking trigger to `role="menu"` container), which is part of the WAI-ARIA menubutton pattern. [Completeness, Spec §FR-017, Gap]
  > **Resolved**: FR-017 in current spec explicitly says "aria-controls pointant vers l'identifiant du conteneur menu". Component contract specifies `aria-controls="user-menu"`. Design reference confirms it.
- [x] CHK016 - Is keyboard activation (Enter/Space) for menu items specified? FR-018 covers Tab/arrows/Escape navigation but does not state how items are activated via keyboard (Enter, Space, or both). [Completeness, Spec §FR-018, Gap]
  > **Resolved**: Menu items are native `<a role="menuitem">` elements. Browser default: Enter activates links. No custom JS needed. HTML semantics cover this.
- [x] CHK017 - Is the desktop dropdown focus management specified? FR-019 mandates a focus trap on mobile only. Is the absence of a focus trap on desktop intentional and documented? [Completeness, Spec §FR-019]
  > **Resolved**: FR-019 scopes focus trap explicitly to "drawer mobile". Desktop dropdown follows standard ARIA menubutton pattern (no trap required per WAI-ARIA authoring practices). Intentional and correct.
- [x] CHK018 - Is `aria-hidden="true"` specified for the purely decorative status dot? FR-005 states the pastille is "purement décorative" but no ARIA hiding requirement is defined. [Completeness, Spec §FR-005, Gap]
  > **Resolved**: Implementation adds `aria-hidden="true"` per standard practice for decorative elements. Design reference shows this pattern on `<span class="avatar-role mod">`. No spec change needed.
- [x] CHK019 - Is the localStorage key name specified for theme persistence? FR-012 mandates localStorage but does not define the key name, risking collision with other features. [Completeness, Spec §FR-012, Gap]
  > **Resolved**: Research Decision 7: key `'theme'`, value `'parchment'` or `'dark'`. `quickstart.md` confirms.
- [x] CHK020 - Is localStorage unavailability handled? FR-012 requires localStorage persistence but defines no fallback for private browsing modes or storage-disabled environments. [Completeness, Spec §FR-012, Gap]
  > **Resolved**: Out of scope. Silent fail acceptable — preference simply won't persist. Spec requires localStorage only; no server sync. The menu functions without persistence.
- [x] CHK021 - Is the specific existing logout component identified? FR-013 says "réutiliser le bouton/handler existant — ne pas réimplémenter" but does not name or reference the specific component, creating ambiguity during implementation. [Completeness, Spec §FR-013, Gap]
  > **Resolved**: Logout is `POST {{ path('app_logout') }}` with `{{ csrf_token('logout') }}`. Found in `templates/components/Layout/Navbar.html.twig` lines 81–82. Route defined in `config/routes/security.yaml`.
- [x] CHK022 - Is the session expiry detection mechanism specified? Edge Cases mention redirection "au clic sur un lien" but do not address proactive detection (e.g., polling, token refresh) while the menu is open. [Completeness, Spec §Edge Cases, Gap]
  > **Resolved**: Symfony handles session expiry at request time. "Au clic sur un lien" → Symfony redirects to login. No proactive detection needed or required.
- [x] CHK023 - Is the JS-disabled degradation state defined? The component relies on a Stimulus controller for open/close behavior per FR-021. Is graceful degradation required when JavaScript is disabled? [Completeness, Spec §FR-021, Gap]
  > **Resolved**: Progressive enhancement. Without JS, menu stays closed. Not required — the application already depends on JS for other features.
- [x] CHK024 - Is the Stimulus/Twig boundary explicitly defined? FR-021 states Stimulus is used "uniquement si un comportement JavaScript interactif l'exige," but given FR-004 (swipe), FR-018/FR-019 (keyboard/focus trap), and FR-011 (toggle), a controller is near-certain. Is the scope of the Stimulus controller documented? [Completeness, Spec §FR-021]
  > **Resolved**: `contracts/profile-menu-stimulus.md` fully documents the controller: values, targets, actions, methods, swipe detection, and CSS classes toggled.

---

## Requirement Clarity

- [x] CHK025 - Is "prominent display" or visual hierarchy quantified anywhere? FR-016 delegates all visual hierarchy to maquettes but no measurable spec exists in the document itself for reviewers without access to the mockups. [Clarity, Spec §FR-016]
  > **Resolved**: `design/dashboard.html` is in the repo. T024 cross-checks rendered output against lines 761–832. Reviewers have access to the design file.
- [x] CHK026 - Is "fluide et perçue comme instantanée" measurable? SC-002 uses a subjective perception metric. Can this be restated as a maximum frame-drop count or minimum frame rate? [Clarity, Spec §SC-002]
  > **Resolved**: SC-002 specifies "transition CSS ≤300ms" — concrete and measurable.
- [x] CHK027 - Is "moins de 2 secondes" in SC-001 measuring user perception or technical response time? Both open AND close actions are covered, but the metric conflates animation duration with interaction latency. [Clarity, Spec §SC-001]
  > **Resolved**: Covers the full user interaction cycle (click → menu fully visible/hidden). All data is server-side rendered; animation is CSS-only (≤300ms). The 2s budget is easily met. Sufficient for internal app.
- [x] CHK028 - Is "le rang/titre de l'utilisateur" defined with possible values or a reference to the ranking system? FR-007 references rank display but does not define the domain of values or where the ranking system is specified. [Clarity, Spec §FR-007]
  > **Resolved**: `data-model.md` specifies `ContributorLevelService::computeRank($user)` → `ContributorLevel::getName()`. Rank system exists in codebase.
- [x] CHK029 - Is "le compteur de suggestions validées" defined with its data source query? FR-008 specifies the counter but does not reference the Repository method, field, or query logic. [Clarity, Spec §FR-008]
  > **Resolved**: `data-model.md` specifies `SuggestionRepository::countByStatus($user, SuggestionStatus::VALIDATED)`.
- [x] CHK030 - Is "le compteur de tâches en attente" defined with its data source query? FR-010 introduces the moderation task counter but does not specify which states qualify as "en attente." [Clarity, Spec §FR-010]
  > **Resolved**: `data-model.md` specifies `WorkEntryRepository::countPending()` + `CorrectionProposalRepository::countPending()`, both filtering on `status = 'PENDING'`.
- [x] CHK031 - Is "le rôle le plus élevé" hierarchy exhaustively defined? Assumptions state "Administrateur > Modérateur" but the FR-006 badge requirement does not reference this hierarchy rule, and future roles could be added without a precedence framework. [Clarity, Spec §FR-006 vs §Assumptions]
  > **Resolved**: `data-model.md` defines `highestRole: 'ROLE_ADMIN' | 'ROLE_MODERATOR' | 'ROLE_USER'` as a closed enum. `security.yaml` role hierarchy is the authority for precedence.
- [x] CHK032 - Is "stylisé en rouge/alerte" in FR-013 and SC-003 measurable? No hex value, design token, or reference to the design system is provided. [Clarity, Spec §FR-013]
  > **Resolved**: `.menu-link.logout` CSS class in `menus.css` defines the red styling using design tokens. Design is authoritative.
- [x] CHK033 - Is "respecte strictement les maquettes" (FR-016) testable without the maquettes embedded or linked in the spec? [Clarity, Spec §FR-016]
  > **Resolved**: `design/dashboard.html` is in the repo. T024 explicitly cross-checks against lines 761–832 and asserts CSS class matching.

---

## Requirement Consistency

- [x] CHK034 - Are FR-007/FR-008/FR-010 data-loading requirements consistent with each other? All three use identical "chargé depuis le Repository Symfony au rendu de la page" language — is there a single shared loading strategy requirement that all three should reference, or are they intentionally independent? [Consistency, Spec §FR-007, §FR-008, §FR-010]
  > **Resolved**: Intentionally independent FRs with consistent language. `data-model.md` documents the unified loading strategy (ProfileMenuService aggregates all three).
- [x] CHK035 - Are ARIA navigation requirements consistent across both form factors? FR-018 specifies keyboard navigation globally but FR-019 specifies focus trap for mobile only. The dropdown close behavior (Escape) restores focus per FR-018 but equivalent behavior for the drawer is not independently specified in FR-019. [Consistency, Spec §FR-018, §FR-019]
  > **Resolved**: Stimulus `onKeydown()` handles Escape globally (both form factors). Focus trap in `open()`/`close()` applies when menu is open. Consistent — FR-018 global + FR-019 mobile-only trap.
- [x] CHK036 - Is SC-003 (100% DOM exclusion of RBAC elements) consistent with the implementation approach? SC-003 is a success criterion measured at runtime, but neither FR-006 nor FR-009 explicitly mandates server-side rendering for security (only Twig Component is mentioned in FR-021). Is server-side enforcement documented as a security requirement? [Consistency, Spec §SC-003, §FR-006, §FR-009, §FR-021]
  > **Resolved**: Twig Components render server-side by design. `{% if is_granted(...) %}` is evaluated at render — restricted elements never reach the DOM. FR-021 (Twig Component) + plan.md Constitution check (Principle IV) document this.
- [x] CHK037 - Are close-trigger requirements consistent between user stories and FRs? User Story 1 Scenario 3 specifies "swipe vers le haut" closes the menu, matching FR-004, but User Story 1 does not mention Escape key close — yet FR-004 does not mention Escape either (only FR-018 does). Is Escape closure part of FR-004 or FR-018, and is this consistent? [Consistency, Spec §FR-004 vs §FR-018 vs §User Story 1]
  > **Resolved**: FR-004 covers physical/pointer triggers (backdrop, swipe, re-click). FR-018 covers keyboard triggers (Escape, Tab, arrows). Complementary, not conflicting. Both wired in Stimulus contract. Consistent.

---

## Acceptance Criteria Quality

- [x] CHK038 - Are SC-001 and SC-006 timing criteria verifiable in isolation? Both specify "moins de 2 secondes" but do not define starting/ending measurement points (e.g., click event → first paint vs. click event → animation complete). [Acceptance Criteria, Spec §SC-001, §SC-006]
  > **Resolved**: Measured from user click → menu fully visible/hidden. CSS animation is ≤300ms; logout redirect is Symfony-handled. 2s budget is not at risk. Sufficient.
- [x] CHK039 - Is SC-003 (100% DOM exclusion) verifiable with a specified test method? The criterion is binary but does not reference a method (DOM inspection, automated test, visual diff). [Acceptance Criteria, Spec §SC-003]
  > **Resolved**: T011 (`testStandardUserHasNoBadgeInDom`), T017 (`testModerationSectionAbsentForStandardUser`) are PHPUnit DOM assertion tests. Method documented in tasks.md.
- [x] CHK040 - Is SC-004 theme persistence verifiable across browser restarts vs. page reloads only? "Persiste après rechargement de page" is specified but persistence across browser close/reopen (tab restoration vs. cold start) is not. [Acceptance Criteria, Spec §SC-004]
  > **Resolved**: `localStorage` persists across browser restarts by default (unlike `sessionStorage`). "Après rechargement de page" is satisfied; cold-start persistence is automatic.
- [x] CHK041 - Is SC-005 ("100% visuellement conforme") measurable without a pixel-diff threshold? Visual conformity is subjective unless a maximum deviation or comparison tool is specified. [Acceptance Criteria, Spec §SC-005]
  > **Resolved**: T024 cross-references `design/dashboard.html:761–832`, verifies CSS class presence, and asserts excluded elements absent. Structural conformity is the test method.

---

## Scenario Coverage

- [x] CHK042 - Are requirements defined for unauthenticated user state? All user stories assume a connected user, but no requirement covers what the avatar/trigger looks like or does when no session exists. [Coverage, Gap]
  > **Resolved**: Component is only rendered inside `{% if is_granted('IS_AUTHENTICATED_REMEMBERED') %}` (contracts/component contract + T022). Unauthenticated users never see the component.
- [x] CHK043 - Are requirements defined for concurrent menu open/close interactions? (e.g., rapid repeated clicks on avatar, or triggering open while close animation is still running) [Coverage, Gap]
  > **Resolved**: `toggle()` reads synchronous `openValue` boolean. Rapid clicks toggle state correctly. CSS `transform` transitions can't stack. No race condition.
- [x] CHK044 - Are requirements defined for the menu behavior when a navigation link is clicked? Do links close the menu before navigating, or does navigation implicitly unmount the component? [Coverage, Gap]
  > **Resolved**: Browser navigation replaces the page. Turbo/full-page navigation unmounts the component. Menu state is irrelevant. No special handling needed.
- [x] CHK045 - Are requirements defined for theme toggle interaction while a page transition is in progress? [Coverage, Gap]
  > **Resolved**: `localStorage.setItem` is synchronous. Theme is applied immediately via `document.documentElement.dataset.theme`. No conflict with page transitions.
- [x] CHK046 - Are requirements defined for the mobile drawer behavior when device orientation changes while it is open? [Coverage, Gap]
  > **Resolved**: CSS media query (`@media (max-width: 719px)`) applies automatically on orientation change. The `is-open` class drives the visual state; CSS adapts layout. No JS handling needed.

---

## Edge Case Coverage

- [x] CHK047 - Is the "multiple roles" fallback fully specified? The Assumption covers badge priority (Administrateur > Modérateur) but does not address moderation section visibility when both roles coexist. [Edge Case, Spec §Assumptions]
  > **Resolved**: `security.yaml` hierarchy: `ROLE_ADMIN: [ROLE_MODERATOR]`. Admin users pass `is_granted('ROLE_MODERATOR')` — moderation section visible. Badge shows `ROLE_ADMIN` (highest). Both cases covered.
- [x] CHK048 - Is localStorage storage quota exceeded state covered? FR-012 requires localStorage write for theme but no fallback or silent-fail behavior is specified. [Edge Case, Spec §FR-012, Gap]
  > **Resolved**: Out of scope. localStorage quota failure for a theme preference is negligible. Silent fail acceptable — theme reverts to default on next load.
- [x] CHK049 - Is the avatar image load failure fallback complete? Edge Cases introduce a fallback but leave the choice between "initiales" and "icône générique" open. [Edge Case, Spec §Edge Cases]
  > **Resolved**: Research Decision 4: initials via `user_initials` Twig filter. Unambiguous.
- [x] CHK050 - Are requirements defined for very long user names (first + last name overflow)? FR-005 displays prénom + nom in the header but no truncation or line-wrapping spec is provided. [Edge Case, Spec §FR-005, Gap]
  > **Resolved**: CSS (menus.css + design system) handles text overflow. Design is source of truth per FR-016.
- [x] CHK051 - Is the empty/zero state specified for dynamic counters? FR-008 (suggestions validées = 0) and FR-010 (tâches en attente = 0) — should a zero counter be displayed as "0" or hidden/replaced with "—"? [Edge Case, Spec §FR-008, §FR-010, Gap]
  > **Resolved**: `ProfileMenuDto.validatedCount` and `pendingModerationCount` are non-nullable `int`. Zero is a valid value — display "0 VALIDÉES" / "0 À RELIRE". The "—" fallback applies only to nullable `rankName` (repository error/no rank). No ambiguity.

---

## Non-Functional Requirements

- [x] CHK052 - Are performance requirements defined for server-side data loading? FR-007/FR-008/FR-010 load from Repository at render time synchronously — no maximum acceptable server response time is specified. [Non-Functional, Gap]
  > **Resolved**: Two `COUNT()` DQL queries + one existing `countByStatus()`. Negligible overhead. No performance requirement needed for this scope.
- [x] CHK053 - Are there requirements for the component's impact on Cumulative Layout Shift (CLS)? The dropdown/drawer appears after user interaction but server-side counters loaded at render could affect initial layout. [Non-Functional, Gap]
  > **Resolved**: Component is server-side rendered with static dimensions. No async data injection. No CLS risk.
- [x] CHK054 - Are reduced-motion accessibility requirements specified? FR-002 mandates a "slide-down animation" but no requirement addresses `prefers-reduced-motion` media query behavior. [Non-Functional, Accessibility, Gap]
  > **Resolved**: Out of scope for this feature. `menus.css` owns the animation CSS. Reduced-motion support can be added to `menus.css` independently without touching the component.
- [x] CHK055 - Are touch target size requirements specified for mobile? FR-003 (drag handle), FR-014 (footer links), and menu items have no minimum touch target size (WCAG 2.5.5 recommends 44×44px). [Non-Functional, Accessibility, Gap]
  > **Resolved**: Touch target sizing handled by existing design system CSS. FR-014 footer links are deleted (US7 removed). Out of scope for this feature.
- [x] CHK056 - Are color contrast requirements specified for the role badge and alert button? FR-006 (badge) and FR-013 (rouge/alerte button) have no contrast ratio requirements. [Non-Functional, Accessibility, Gap]
  > **Resolved**: Design system tokens define contrast ratios. `menus.css` + design tokens are authoritative. Out of scope for this feature.

---

## Dependencies & Assumptions

- [x] CHK057 - Is the RBAC system dependency traceable? FR-006/FR-009 depend on the RBAC system (spec `004-rbac-roles-permissions`) — is the role-check API/method referenced explicitly? [Dependency, Spec §FR-006, §FR-009]
  > **Resolved**: Symfony `is_granted()` + `security.yaml` role hierarchy (`ROLE_ADMIN: [ROLE_MODERATOR]`). Fully traceable.
- [x] CHK058 - Is the existing logout handler documented with a reference? Assumption and FR-013 both state "réutiliser le handler existant" but no file path, component name, or route is referenced. [Dependency, Spec §FR-013, §Assumptions]
  > **Resolved**: `POST {{ path('app_logout') }}` + `{{ csrf_token('logout') }}`. Found in `templates/components/Layout/Navbar.html.twig:81–82`. Route in `config/routes/security.yaml` as `app_logout`.
- [x] CHK059 - Is the design mockups location resolvable? FR-016 references "dossier design au niveau de la navbar" but provides no file path or link. Are these mockups committed to the repository? [Dependency, Spec §FR-016, §Assumptions]
  > **Resolved**: `design/dashboard.html` confirmed at project root. Lines 761–832 contain the reference markup.
- [x] CHK060 - Is the rank/title data model referenced? FR-007 displays user rank but no spec reference defines the rank entity, its values, or the Repository method to query it. [Dependency, Spec §FR-007, Gap]
  > **Resolved**: `data-model.md` specifies `ContributorLevelService::computeRank($user)` → `ContributorLevel::getName()`.
- [x] CHK061 - Is the 720px breakpoint assumption validated against existing CSS? The spec states conformance with `design/assets/menus.css` — is this file path verified as existing and authoritative? [Assumption, Spec §Assumptions]
  > **Resolved**: `@media (min-width: 720px)` confirmed at `design/assets/menus.css:19`. File exists and is authoritative.
