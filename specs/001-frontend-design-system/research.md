# Research: Frontend Design System Foundation

**Branch**: `001-frontend-design-system` | **Date**: 2026-05-23

---

## Decision 0: Design System Location (RESOLVED)

**Decision**: Design system exported to `design/` at project root. No URL needed — all values are local files.

**Authoritative sources**:

| File | Content |
|------|---------|
| `design/assets/tokens.css` | All design tokens: palettes, semantic tokens, typography, spacing, radius, shadows, motion |
| `design/assets/components.css` | Component CSS: buttons, forms, badges, rating, avatar |
| `design/pages/01-couleurs.html` | Color palettes + Bootstrap SCSS variable mapping (section 1.3) |
| `design/pages/05-cards.html` | Book card (full/row/micro) + Author card styles |
| `design/pages/07-navigation.html` | Bottom nav, tabs, avatars |
| `design/pages/08-feedback.html` | Modal & toast patterns |

**Bootstrap SCSS variable mapping** (verbatim from `01-couleurs.html` §1.3 — copy into `_variables.scss`):
```scss
$primary:   #8b4513; // cuir-500
$secondary: #b8860b; // or-500
$success:   #5b7c3a; // mousse-500
$warning:   #c08a2e; // ambre-500
$danger:    #9a3a2a; // sang-500
$info:      #3a5a7c; // encre-500

$body-bg:           #f5ecd9; // parchemin-100
$body-color:        #2d1810; // parchemin-800
$body-secondary-bg: #ede1c5; // parchemin-200
$body-tertiary-bg:  #fbf6e9; // parchemin-50

$font-family-sans-serif: "Inter", system-ui, -apple-system, sans-serif;
$font-family-monospace:  "IBM Plex Mono", monospace;
$headings-font-family:   "Cinzel", serif;
$headings-font-weight:   600;
$headings-letter-spacing: 0.04em;

$border-radius:    .5rem;
$border-radius-sm: .25rem;
$border-radius-lg: .75rem;
$border-color:     #d9c9a7; // parchemin-300

$focus-ring-color: rgba(139, 69, 19, .30);
$focus-ring-width: .1875rem;
```

**Key design observations**:
- Two themes: Parchemin (light) / Grimoire (dark). Dark mode out of scope for this feature.
- Toast timer: design shows 4s default / 8s warning / persistent danger. **Spec FR-010 overrides: all toasts = 5000ms.**
- Bottom nav: design has 5 slots — Accueil, Catalogue, **FAB central "Suggérer un livre"**, Suggestions, Profil. FAB is a `bottom-nav-fab` styled button, not a standard nav item. Implement as shown in design.
- Toast position: top-right on desktop, **full-width at top on mobile** — never bottom (collision with bottom nav).
- Card cover placeholder: gradient `linear-gradient(135deg, var(--cuir-300), var(--cuir-500))` + striped overlay + decorative interior corner frame.

---

## Decision 1: Twig Component Architecture

**Decision**: Typed PHP classes (`#[AsTwigComponent]`) in `src/Twig/Components/` for all components with any logic or fallback handling.

**Rationale**: Book and Author cards have fallback props ("Sans titre", placeholder image), Rating has a numeric value prop, FlashBag maps flash type to CSS class — all require `#[PostMount]` or computed getters. Anonymous (template-only) components are reserved only for zero-logic elements; none qualify in this feature.

**Fallback pattern**: Use `#[PostMount]` on typed classes (not template-level `|default` filter) so fallback logic is testable in isolation via `mountTwigComponent()`.

```php
#[PostMount]
public function postMount(): void
{
    $this->title = $this->title ?: 'Sans titre';
    $this->coverUrl = $this->coverUrl ?: '/images/placeholder-cover.svg';
}
```

**Alternatives considered**: Template-only fallbacks via `{{ title|default('Sans titre') }}` → rejected for components with business-meaningful defaults (testability); acceptable for purely presentational variants (none in this feature).

**Directory convention**:
```
src/Twig/Components/Book/Card.php  →  component name: Book:Card
templates/components/Book/Card.html.twig  →  auto-resolved
```

**Testing**: `KernelTestCase` + `InteractsWithTwigComponents` trait. Use `mountTwigComponent()` for prop/fallback tests, `renderTwigComponent()` + `.crawler()` for rendered HTML assertions.

**Gotchas**:
- Do NOT mark component classes `readonly` — framework sets public properties via reflection after instantiation
- Cannot use `_self` for macro imports inside component templates; use full template paths
- `InteractsWithTwigComponents` boots the full kernel — keep test fixtures narrow for speed

---

## Decision 2: SCSS Token Architecture + Bootstrap 5 Integration

**Decision**: Dart Sass (`sass` package) + `sass-loader@^16`. Tokens defined as SCSS variables in `assets/styles/tokens/` before the Bootstrap `functions` import, exploiting Bootstrap's `!default` pattern. Bootstrap then emits `--bs-*` CSS custom properties automatically via `_root.scss`.

**Rationale**: Bootstrap 5's `!default` variables are the canonical override point. Defining tokens before `functions` ensures `variables` picks them up without `!important` hacks. Bootstrap then generates corresponding CSS custom properties for runtime theming. Custom tokens with no Bootstrap equivalent are added as additional CSS custom properties in a project-level `:root` block.

**Import order in `app.scss`** (order is mandatory):
```scss
// 1. Project design tokens (must precede Bootstrap functions)
@use "tokens/index";

// 2. Bootstrap infrastructure
@import "bootstrap/scss/functions";
@import "bootstrap/scss/variables";
@import "bootstrap/scss/variables-dark";
@import "bootstrap/scss/maps";

// 3. Bootstrap map overrides (e.g. $theme-colors merge)
@import "bootstrap/overrides";

// 4. Bootstrap mechanics
@import "bootstrap/scss/mixins";
@import "bootstrap/scss/root";     // emits --bs-* CSS custom properties

// 5. Selective Bootstrap components (only what the feature needs)
@import "bootstrap/scss/reboot";
@import "bootstrap/scss/type";
@import "bootstrap/scss/grid";
@import "bootstrap/scss/utilities";
@import "bootstrap/scss/utilities/api";
// components added here as feature grows
```

**Alternatives considered**: Full `@import "bootstrap/scss/bootstrap"` → rejected; ~40-70% CSS size reduction from selective imports; build time is faster.

**Bootstrap JS**: ESM imports within Stimulus controllers only (lazy on `connect()`). Do not import the full Bootstrap JS bundle globally. Exception: navbar toggler uses Bootstrap's native `data-bs-toggle="collapse"` (no custom Stimulus controller needed — Bootstrap's built-in accessibility attributes are sufficient).

---

## Decision 3: Stimulus Controller Architecture

**Decision**: Three custom Stimulus controllers. Bootstrap native JS retained for navbar collapse.

| Controller | File | Responsibility |
|---|---|---|
| `toast` | `toast_controller.js` | Single toast lifecycle: 5s auto-dismiss + manual close |
| `toast-container` | `toast-container_controller.js` | Stack management: max 3, prepend, evict `lastElementChild` |
| `modal` | `modal_controller.js` | Modal open/close, Escape key, body scroll lock |

**Toast implementation**:
- Timer stored as `this.timeoutId` (instance property, not Stimulus value) — cancelled in `disconnect()` to prevent memory leaks
- `toast-container` prepends new toasts (newest visually on top with `flex-direction: column`) and removes `lastElementChild` when count exceeds 3
- Separation: `toast` owns its lifecycle; `toast-container` owns the stack invariant

**Modal**: Custom Stimulus controller; does NOT use Bootstrap's JS modal. Bootstrap modal JS and Stimulus conflict on focus trapping. Custom controller uses CSS transitions + `is-open` class.

**Navbar**: Bootstrap native `data-bs-toggle="collapse"` (ships full accessibility: `aria-expanded`, `aria-controls`, keyboard). No custom Stimulus controller unless custom behavior is added later.

**Registration**: `startStimulusApp()` in `app.js` auto-discovers all `assets/controllers/*_controller.js` files. Never manually register project controllers. `controllers.json` is only for `@symfony/ux-*` packages.

**Twig attribute helpers**: Use `stimulus_controller()`, `stimulus_action()`, `stimulus_target()` Twig helpers from `symfony/stimulus-bundle` — refactor-safe, correct `data-*` attribute generation.

**Alternatives considered**: Alpine.js for toast/modal → rejected; spec mandates Stimulus; constitution prohibits new JS frameworks.

---

## Decision 4: Webpack Encore Configuration

**Decision**: `enableSassLoader()` + `enableVersioning(Encore.isProduction())` + `splitEntryChunks()` + `enableSingleRuntimeChunk()`. Single entry point `app`.

**Rationale**: Single entry point covers all pages via base layout. `splitEntryChunks()` enables shared chunk extraction if multiple entry points are added later. Version hashing via `entrypoints.json` consumed automatically by `encore_entry_link_tags()` / `encore_entry_script_tags()` Twig helpers — no manual cache-busting.

**npm packages required**:
```
bootstrap @popperjs/core @hotwired/stimulus @symfony/stimulus-bundle sass sass-loader@^16
```

**Alternatives considered**: Vite → rejected; spec/constitution specifies Webpack Encore explicitly.

---

## Decision 5: Platform.sh Deployment

**Decision**: PHP 8.3, Node 20 LTS (via `.nvmrc`), `build: flavor: composer` + `hooks.build` for npm, PostgreSQL 16, `platformsh/config-reader` for `DATABASE_URL`.

**Key constraints**:
- `public/build/` is gitignored; Encore runs during Platform.sh build hook (filesystem is read-only at deploy time — build hook is the only window)
- `platformsh/config-reader` auto-generates `DATABASE_URL` from the `database` relationship — no hardcoded credentials
- `APP_SECRET` set via Platform.sh environment variable (never committed)

**`.platform.app.yaml` build hook**:
```yaml
hooks:
  build: |
    set -e
    nvm use 20
    npm ci
    npm run build
```

**Alternatives considered**: Separate Node container → rejected; overkill for an asset build step; single app container with nvm is standard for Symfony + Encore on Platform.sh.

---

## Decision 6: Base Layout Architecture

**Decision**: Single `base.html.twig` at `templates/base.html.twig`. All pages extend it. Navbar, footer, and flash notification zone are rendered via Twig components in the base layout.

**Mobile layout**: Desktop shows `<nav>` + `<footer>`; mobile shows top bar (logo + notification icon) + fixed bottom nav with Accueil/Catalogue/Suggestions/Profile. Implemented as responsive Bootstrap classes within the Navbar and Footer components — no separate mobile templates.

**Footer on mobile**: Hidden via Bootstrap `d-none d-md-block` (hidden below `md` breakpoint, ~768px). Bottom nav appears via `d-block d-md-none`.

**Flash notification zone**: Rendered by `FlashBag` component in `base.html.twig`. Component reads `app.flashes()` and renders one `Toast` component per flash. `toast-container` Stimulus controller manages the stack on the container element.

**Alternatives considered**: Separate mobile templates → rejected; adds duplication without value; Bootstrap responsive utilities sufficient.
