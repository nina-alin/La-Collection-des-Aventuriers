# Quickstart: Menu Profil Utilisateur

## Files to create

```
src/Dto/ProfileMenuDto.php
src/Service/ProfileMenuService.php
src/Twig/Components/Layout/ProfileMenu.php
templates/components/Layout/ProfileMenu.html.twig
assets/controllers/profile_menu_controller.js
tests/Twig/Components/ProfileMenuTest.php
```

## Files to modify

```
src/Repository/WorkEntryRepository.php          # ADD countPending(): int
src/Repository/CorrectionProposalRepository.php # ADD countPending(): int
templates/components/Layout/Navbar.html.twig    # REPLACE inline .lp-user-menu div with <twig:Layout:ProfileMenu />
```

## No migrations needed

No schema changes. No new entities.

---

## Implementation order

1. Add `countPending()` to both repos (unblocks service)
2. Create `ProfileMenuDto`
3. Create `ProfileMenuService` (depends on dto + repos + ContributorLevelService)
4. Create `ProfileMenu` Twig Component class
5. Create `ProfileMenu.html.twig` template (use design/dashboard.html:771–832 as reference)
6. Create `profile_menu_controller.js` Stimulus controller
7. Replace inline menu in `Navbar.html.twig`
8. Write `ProfileMenuTest.php`

---

## Reference markup (from design/dashboard.html)

The full markup spec is in `design/dashboard.html` lines 761–832.
CSS classes come from `design/assets/menus.css` (already imported in base layout).

Key classes:
- `.menu-anchor` — wrapper with `data-controller="profile-menu"`
- `.menu-card.user-card` — panel (desktop: dropdown, mobile: bottom-sheet via media query in menus.css)
- `.menu-head-user` — avatar + name + role badge header
- `.menu-section` — each group of links
- `.menu-section-label` — uppercase section title
- `.menu-link` — nav item (icon + label + meta)
- `.menu-link.role-action` — moderation link (info-colored)
- `.menu-toggle-row` — theme toggle row
- `.logout-section` / `.menu-link.logout` — red sign-out button
- `.menu-backdrop` — mobile overlay (transparent on desktop)

---

## Theme toggle wiring (localStorage)

```js
// In profile_menu_controller.js connect():
const saved = localStorage.getItem('theme');
const checkbox = this.element.querySelector('#theme-switch-menu');
if (checkbox) checkbox.checked = (saved === 'parchment');

// Toggle handler:
toggleTheme(e) {
  const isOn = e.currentTarget.querySelector('input[type=checkbox]').checked;
  const theme = isOn ? 'parchment' : 'dark';
  document.documentElement.dataset.theme = theme;
  localStorage.setItem('theme', theme);
}
```

---

## ARIA requirements (FR-017 to FR-020)

| Element | Required attributes |
|---|---|
| Trigger `<button>` | `aria-haspopup="menu"`, `aria-expanded` (dynamic), `aria-controls="user-menu"` |
| Menu panel `<div>` | `id="user-menu"`, `role="menu"`, `aria-label="Compte utilisateur"` |
| Each nav link | `role="menuitem"` |
| Theme toggle button | `role="menuitem"` or `role="button"` (acceptable; not a nav link) |

---

## Test coverage required (Principle V)

| Test | Assertion |
|---|---|
| `testStandardUserHasNoBadgeInDom` | No `.badge-role-mod` or `.badge-role-admin` in rendered HTML |
| `testModeratorUserShowsModBadge` | Contains `MODÉRATEUR` text and badge element |
| `testAdminUserShowsAdminBadge` | Contains `ADMINISTRATEUR` text and badge element |
| `testModerationSectionAbsentForStandardUser` | `OUTILS DE MODÉRATION` not in DOM |
| `testModerationSectionPresentForModerator` | `OUTILS DE MODÉRATION` present |
| `testTriggerHasAriaAttributes` | `aria-haspopup="menu"`, `aria-controls="user-menu"` present on trigger |
| `testRankFallbackWhenNull` | When rank is null, `—` appears in `lnk-meta` |
