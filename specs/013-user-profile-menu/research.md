# Research: Menu Profil Utilisateur Responsive

## Decision 1: "Mon Profil" link target

**Decision**: Use `#` (no-op href) with a `TODO` comment in the template for the "Mon Profil" link. The user public profile page does not exist yet in the codebase (no `app_user_profile` or equivalent route found). No controller, no route, no template exists for it.

**Rationale**: FR-013 says to reuse existing handlers; there is no existing profile page route. Implementing a fake route is out of scope for this feature. A placeholder `href="#"` keeps the DOM correct and unblocks the menu component. The route will be wired in the feature that implements the user public profile page.

**Alternatives considered**:
- Link to `suggestions_index` — rejected; wrong page, misleading UX
- Create stub profile route in this feature — rejected; out of scope, adds untested surface
- Leave `Mon Profil` out of DOM — rejected; contradicts FR-007 and US3

---

## Decision 2: Moderation pending count

**Decision**: Add `countPending(): int` to both `WorkEntryRepository` and `CorrectionProposalRepository`. The `ProfileMenuService` sums both: `$count = $weRepo->countPending() + $cpRepo->countPending()`.

**Rationale**: Both repos currently only expose `findPending(): array`, which hydrates full entities unnecessarily for a counter. A `COUNT()` DQL query is the correct approach (same pattern as `SuggestionRepository::countByStatus()`). Adding `countPending()` is a non-breaking additive change.

**Alternatives considered**:
- `count($repo->findPending())` — rejected; hydrates full entities for a number, wasteful
- Single combined repository method — rejected; violates single-responsibility; repos are entity-specific

---

## Decision 3: Rank display when user has no validated suggestions

**Decision**: When `ContributorLevelService::computeRank($user)` returns `null` (zero validated suggestions), display `"—"` in the `lnk-meta` span (consistent with edge-case fallback specified in spec).

**Rationale**: Spec edge-case: "Afficher '—' silencieusement à la place de la valeur". `computeRank()` already returns `?ContributorLevel`, so null-check in Twig is straightforward.

---

## Decision 4: Avatar fallback

**Decision**: When `app.user.avatarUrl` is null, render `<span class="avatar …">{{ app.user|user_initials }}</span>`. The `UserInitialsExtension` Twig filter already exists (`src/Twig/Extension/UserInitialsExtension.php`).

**Rationale**: Spec edge-case: "Afficher un avatar par défaut (initiales ou icône générique)". The existing `user_initials` filter is the project-standard approach.

---

## Decision 5: Stimulus swipe detection

**Decision**: Use native `touchstart`/`touchmove`/`touchend` events on the `.menu-card` element tracked via `data-profile-menu-target="card"`. Track `startY` on `touchstart`, compute delta on `touchend`; dismiss if delta > 80px downward. No external dependency.

**Rationale**: FR-004 explicitly requires "événements touch natifs… sans dépendance externe". Stimulus `connect()` binds listeners; `disconnect()` removes them — clean lifecycle.

---

## Decision 6: Focus trap implementation

**Decision**: Implement focus trap in Stimulus controller. On `open`: collect all focusable elements within the card, intercept `Tab`/`Shift+Tab` to cycle within them. On `close`: restore focus to the trigger button. No external focus-trap library.

**Rationale**: FR-019 requires focus trap on mobile drawer. Constitution forbids new JS frameworks without architectural approval. A ~20-line focus-trap in the Stimulus controller is sufficient for this component's limited focusable surface.

---

## Decision 7: Theme toggle persistence

**Decision**: Theme toggle reads/writes `localStorage.getItem('theme')` (key `'theme'`). Value `'parchment'` = parchment/light; absent or `'dark'` = dark. Toggle sets `document.documentElement.dataset.theme` and persists to localStorage. The existing design system already uses `[data-theme="dark"]` CSS selectors (confirmed in `menus.css`).

**Rationale**: FR-011/FR-012 require localStorage persistence only (no server sync). The `[data-theme]` attribute approach is already established in the design system tokens.

---

## Decision 8: ProfileMenu as isolated Twig Component vs inline in Navbar

**Decision**: `ProfileMenu` is a standalone `#[AsTwigComponent]` class embedded via `<twig:Layout:ProfileMenu />` inside `Navbar.html.twig`, replacing the current inline `.lp-user-menu` div. The `Navbar` Twig Component already uses DI (constructor injection), so adding `ProfileMenuService` there or in a dedicated child component are both viable. Using a dedicated child component keeps `Navbar.php` thin.

**Rationale**: Constitution Principle II requires thin controllers/components. `Navbar.php` currently does nothing except hold `currentRoute`; the new component should not inflate it. A dedicated `ProfileMenu` component also keeps the template and test scope clean.
