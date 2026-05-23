---

description: "Task list for Frontend Design System Foundation"
---

# Tasks: Frontend Design System Foundation

**Input**: Design documents from `/specs/001-frontend-design-system/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/layout.md ✅, contracts/components.md ✅, quickstart.md ✅

**Design System Source**: All token values from `design/assets/tokens.css` and `design/assets/components.css` (local files — no URL fetch required per research.md Decision 0)

**Tests**: PHPUnit tests included — contracts/components.md explicitly defines test surfaces; plan.md constitution check confirms "PHPUnit tests for component rendering" are required.

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no blocking dependencies)
- **[Story]**: User story label ([US1]–[US4])
- File paths are exact per plan.md project structure

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Create the Symfony application from scratch. The app does not yet exist (spec.md Assumptions).

- [X] T001 Create Symfony application at repository root using `symfony new . --version=7.2 --no-git`; verify `public/index.php` and `src/Kernel.php` exist
- [X] T002 [P] Add required PHP packages to `composer.json`: `symfony/ux-twig-component`, `symfony/ux-stimulus-bundle`, `symfony/webpack-encore-bundle`, `symfony/twig-bundle`, `symfony/asset`, `symfony/framework-bundle`, `platformsh/config-reader`; run `composer require` for each
- [X] T003 [P] Create `package.json` at repository root with exact npm packages: `bootstrap@^5.3`, `bootstrap-icons`, `@hotwired/stimulus`, `@symfony/stimulus-bridge`, `@symfony/webpack-encore-bundle`, `sass`, `sass-loader@^16`, `@popperjs/core`; add scripts `"build": "encore production"` and `"dev": "encore dev"`
- [X] T004 Run `npm install` to install JS dependencies and verify `node_modules/bootstrap` and `node_modules/sass` exist
- [X] T005 [P] Create `.platform.app.yaml` at repository root with PHP 8.3 runtime, Node 20 build tool via nvm, `build: flavor: composer`, and build hook `set -e\nnvm use 20\nnpm ci\nnpm run build`; configure `web.locations` to serve `public/`
- [X] T006 [P] Create `.platform/routes.yaml` (HTTP → HTTPS redirect + main app route) and `.platform/services.yaml` (PostgreSQL 16 service named `database`)
- [X] T007 [P] Create `.nvmrc` with content `20`; add `public/build/` and `node_modules/` to `.gitignore`
- [X] T008 Configure `config/packages/asset.yaml` to use Webpack Encore via `json_manifest_path: '%kernel.project_dir%/public/build/manifest.json'`

**Checkpoint**: Symfony app exists. npm and composer dependencies installed. Platform.sh config present.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Webpack Encore pipeline + Stimulus bootstrap + minimal SCSS token foundation. MUST be complete before any user story can produce styled output.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [X] T009 Create `webpack.config.js` at repository root implementing: `Encore.setOutputPath('public/build/')`, `Encore.setPublicPath('/build')`, `Encore.addEntry('app', './assets/bootstrap.js')`, `Encore.enableSassLoader()`, `Encore.enableVersioning(Encore.isProduction())`, `Encore.splitEntryChunks()`, `Encore.enableSingleRuntimeChunk()`; do NOT import the full Bootstrap JS bundle (research.md Decision 4)
- [X] T010 [P] Create `assets/bootstrap.js` with `startStimulusApp()` from `@symfony/stimulus-bridge/lazy-controller-loader`; add `import './styles/app.scss'` to include CSS entry point; auto-discovery of `assets/controllers/*_controller.js` is handled by `startStimulusApp()`
- [X] T011 [P] Create `assets/controllers.json` with empty `controllers: {}` object (Symfony UX package controller map — populated by UX bundle packages)
- [X] T012 Create `assets/styles/tokens/_variables.scss` with Bootstrap SCSS overrides exactly as specified in `research.md Decision 0`: `$primary: #8b4513`, `$secondary: #b8860b`, `$success: #5b7c3a`, `$warning: #c08a2e`, `$danger: #9a3a2a`, `$info: #3a5a7c`, `$body-bg: #f5ecd9`, `$body-color: #2d1810`, `$body-secondary-bg: #ede1c5`, `$body-tertiary-bg: #fbf6e9`, font families (Inter, IBM Plex Mono, Cinzel), border-radius `.5rem`, `$border-color: #d9c9a7`, `$focus-ring-color: rgba(139,69,19,.30)`, `$focus-ring-width: .1875rem`; all with `!default` flag
- [X] T013 [P] Create `assets/styles/tokens/_index.scss` that `@forward`s the `variables` partial only (stub — expanded in US2 to include _colors, _typography, _spacing, _effects)
- [X] T014 Create `assets/styles/app.scss` with mandatory import order from `research.md Decision 2`: (1) `@use "tokens/index"`, (2) Bootstrap functions/variables/variables-dark/maps, (3) empty `// bootstrap overrides` section, (4) Bootstrap mixins/root, (5) selective Bootstrap components (reboot, type, grid, utilities, utilities/api); add `// component imports` placeholder comment at end
- [X] T015 Create `config/packages/twig_component.yaml` enabling Twig Component auto-discovery from `src/Twig/Components/` namespace with template path `templates/components/`; verify `config/bundles.php` includes `Symfony\UX\TwigComponent\TwigComponentBundle` and `Symfony\UX\StimulusBundle\StimulusBundle`

**Checkpoint**: `npm run dev` compiles successfully. Bootstrap CSS loads. Stimulus boots. Token variables are active.

---

## Phase 3: User Story 1 — Consistent Global Layout (Priority: P1) 🎯 MVP

**Goal**: Every page shows branded navbar + footer. A developer can create a new page by extending `base.html.twig`.

**Independent Test**: Navigate to `/`. Verify `<header>` contains "La Collection" brand and nav links (Accueil, Catalogue, Suggestions). Verify `<footer>` renders on desktop. A blank styled page satisfies US1.

### Implementation for User Story 1

- [X] T016 [US1] Create `templates/base.html.twig`: `<!DOCTYPE html>` skeleton with `<html lang="fr">`, `<head>` (charset, viewport, title block, `encore_entry_link_tags('app')`), `<body>` containing `<twig:Layout:Navbar />`, flash zone `<div data-controller="toast-container" role="status" aria-live="polite" aria-atomic="false">` containing `<twig:Layout:FlashBag />`, `<main>{% block body %}{% endblock %}</main>`, `<twig:Layout:Footer />`, `encore_entry_script_tags('app')`, optional stylesheets and javascripts blocks
- [X] T017 [P] [US1] Create `src/Twig/Components/Layout/Navbar.php`: `#[AsTwigComponent]` class with `public ?string $currentRoute = null`; do NOT mark readonly
- [X] T018 [P] [US1] Create `templates/components/Layout/Navbar.html.twig`: desktop `<header>` with sticky `<nav aria-label="Navigation principale">` containing "La Collection" brand + links (Accueil `/`, Catalogue `/catalogue`, Suggestions `/suggestions`) with active state via `app.request.pathInfo` comparison and `aria-current="page"`; mobile top bar (logo + `<button aria-label="Notifications">` with `bi-bell` icon, 44×44px); fixed bottom nav `<nav aria-label="Navigation mobile">` with 5 slots (Accueil, Catalogue, FAB "Suggérer un livre" linking to `/suggestions/nouveau` with `.bottom-nav-fab` class, Suggestions, Profil) per `design/pages/07-navigation.html §bottom-nav`; use `stimulus_controller()` Twig helpers
- [X] T019 [P] [US1] Create `src/Twig/Components/Layout/Footer.php`: `#[AsTwigComponent]` class with no props; static content only; do NOT mark readonly
- [X] T020 [P] [US1] Create `templates/components/Layout/Footer.html.twig`: `<footer aria-label="Pied de page" class="footer d-none d-md-block">` containing: tagline paragraph with decorative fleurons (❦) above and below; three-column `.footer-grid` — brand column ("La Collection" + "Catalogue collaboratif…" description), "La Collection" links column (Le catalogue, Les auteurs, Les éditions, Les collections), "Communauté" links column (Suggérer un livre, Les contributeurs, Modération, Devenir modérateur, La Taverne); `.footer-bottom` bar with `© {{ 'now'|date('Y') }} · La Collection des Aventuriers` and legal links; per `design/landing.html §FOOTER`
- [X] T021 [P] [US1] Create `src/Twig/Components/Layout/FlashBag.php`: `#[AsTwigComponent]` class with no props; reads `app.flashes()` via Twig global; do NOT mark readonly
- [X] T022 [US1] Create `templates/components/Layout/FlashBag.html.twig`: iterates `app.flashes()` and renders `<twig:Toast type="{{ type }}" message="{{ message }}" />` per flash; renders no markup when flash bag is empty
- [X] T023 [US1] Create `src/Controller/DefaultController.php` with routes: `#[Route('/', name: 'home')]` → `home/index.html.twig`; `#[Route('/catalogue', name: 'catalogue_index')]` → `home/index.html.twig` (stub); `#[Route('/suggestions', name: 'suggestions_index')]` → `home/index.html.twig` (stub); `#[Route('/suggestions/nouveau', name: 'suggestions_new')]` → `home/index.html.twig` (stub)
- [X] T024 [US1] Create `templates/home/index.html.twig` extending `base.html.twig`; override `title` block with "Accueil — La Collection"; override `body` block with `<div class="container py-4"><h1>La Collection</h1></div>`
- [X] T025 [P] [US1] Create `assets/styles/components/_navbar.scss`: implement `.lp-header` (sticky, `position: sticky; top: 0; z-index: 30`), `.lp-brand`, desktop nav link active/hover styles, `.bottom-nav` (fixed bottom, `d-block d-md-none`), `.bottom-nav-fab` (central FAB slot styled differently from standard nav items), `.bottom-nav-item`; all values from `design/pages/07-navigation.html`; use CSS custom properties from token system
- [X] T026 [P] [US1] Create `assets/styles/components/_footer.scss`: implement `.footer`, `.footer-grid` (three-column responsive layout), `.footer-bottom`; all values from `design/landing.html §FOOTER`; use CSS custom properties from token system
- [X] T027 [US1] Import `_navbar.scss` and `_footer.scss` in the `// component imports` section of `assets/styles/app.scss`

**Checkpoint**: `symfony serve`, visit `/`. Header shows "La Collection" + nav links. Footer visible on desktop. Flash zone present. Base layout extensible.

---

## Phase 4: User Story 2 — Design Token Consistency (Priority: P2)

**Goal**: All Design System token categories available as global CSS custom properties. Zero invented values.

**Independent Test**: Create `/test-tokens` route. Apply `background: var(--brand-primary)` → must render `#8b4513`; `font-family: var(--font-display)` → Cinzel 600; `padding: var(--sp-4)` → spacing scale value; `border-radius: var(--radius-md)` → `.5rem`; `box-shadow: var(--shadow-md)` → exact shadow token. Verify each against `design/assets/tokens.css`.

### Implementation for User Story 2

- [X] T028 [P] [US2] Create `assets/styles/tokens/_colors.scss`: read `design/assets/tokens.css` and define ALL color palettes as CSS custom properties in `:root`: full `--cuir-*` palette (50–900), `--or-*`, `--parchemin-*`, `--mousse-*`, `--sang-*`, `--encre-*`; semantic tokens (`--brand-primary`, `--brand-secondary`, `--bg-base`, `--bg-elevated`, `--bg-sunken`, `--text-primary`, `--text-secondary`, `--text-muted`, `--border-default`); zero invented values — every value from `design/assets/tokens.css`
- [X] T029 [P] [US2] Create `assets/styles/tokens/_typography.scss`: read `design/assets/tokens.css` and define ALL typography tokens in `:root`: `--font-display` (Cinzel), `--font-body` (Inter), `--font-mono` (IBM Plex Mono); full `--fs-*` scale; `--lh-*` line heights; `--tracking-*` letter-spacing values; all exact from tokens.css
- [X] T030 [P] [US2] Create `assets/styles/tokens/_spacing.scss`: read `design/assets/tokens.css` and define ALL spacing tokens (`--sp-0` through `--sp-9`) and radius tokens (`--radius-sm`, `--radius-md`, `--radius-lg`, `--radius-full`) in `:root`; all values exact from tokens.css
- [X] T031 [P] [US2] Create `assets/styles/tokens/_effects.scss`: read `design/assets/tokens.css` and define ALL shadow tokens (`--shadow-sm`, `--shadow-md`, `--shadow-lg`), focus ring (`--ring-focus`), motion tokens (`--motion-fast`, `--motion-base`, `--motion-slow`) in `:root`; all values exact from tokens.css
- [X] T032 [US2] Update `assets/styles/tokens/_index.scss` to `@forward` all 5 partials: `variables` (Bootstrap overrides, must remain first), `colors`, `typography`, `spacing`, `effects`
- [X] T033 [P] [US2] Create `assets/styles/components/_buttons.scss`: read `design/assets/components.css` and implement all button classes with exact values: `.btn` (base), `.btn-primary`, `.btn-secondary`, `.btn-ghost`, `.btn-danger`, `.btn-sm`, `.btn-lg`, `.btn-icon` (square 44×44px min-size), `.btn-block` (full width); use CSS custom properties from token system; no invented values
- [X] T034 [P] [US2] Create `assets/styles/components/_forms.scss`: read `design/assets/components.css` and implement `.input`, `.textarea`, `.select`, `.choice` (checkbox/radio wrapper), `.form-group`, `.form-label`, `.form-help`; use CSS custom properties; no invented values
- [X] T035 [US2] Import `_buttons.scss` and `_forms.scss` in `assets/styles/app.scss` component imports section; run `npm run dev` and verify zero compilation errors

**Checkpoint**: All token categories defined. `npm run dev` succeeds. Verify 5 token values at `/test-tokens` match `design/assets/tokens.css` exactly.

---

## Phase 5: User Story 3 — System Notifications (Priority: P3)

**Goal**: Flash notifications render correct color per type, auto-dismiss after 5 s, stack max 3, no memory leaks.

**Independent Test**: Trigger a success flash and an error flash. Verify distinct Design System colors. Verify auto-dismiss at 5 s. Verify stack limit: add 4 flashes, confirm only 3 visible with oldest removed.

### Implementation for User Story 3

- [X] T036 [P] [US3] Create `assets/controllers/toast_controller.js`: Stimulus controller; `connect()` stores `this.timeoutId = setTimeout(() => this.dismiss(), this.autoDismissMs)` where `this.autoDismissMs` reads from data attribute (default 5000); `dismiss()` removes element from DOM; `disconnect()` calls `clearTimeout(this.timeoutId)` to prevent timer leaks (research.md Decision 3); `close` action method also calls `dismiss()`; message MUST be set via `textContent` only — never `innerHTML` (FR-010 XSS prevention)
- [X] T037 [P] [US3] Create `assets/controllers/toast-container_controller.js`: Stimulus controller; `connect()` creates `this.observer = new MutationObserver(() => { if (this.element.children.length > 3) this.element.lastElementChild.remove(); })` and calls `this.observer.observe(this.element, { childList: true })`; `disconnect()` calls `this.observer?.disconnect()` to prevent leaks; evicts oldest (last child) when stack exceeds 3 (research.md Decision 3)
- [X] T038 [P] [US3] Create `src/Twig/Components/Toast.php`: `#[AsTwigComponent]` class with `public string $message`, `public string $title = ''`, `public string $type = 'info'`, `public int $autoDismissMs = 5000`; `#[PostMount]` normalizes `type` to one of `['success', 'error', 'warning', 'info']` with fallback to `'info'`; add `getCssClass(): string` returning `'toast-success'|'toast-error'|'toast-warning'|'toast-info'`; do NOT mark readonly — `$title` is optional (empty string = no title rendered, only type label shown)
- [X] T039 [P] [US3] Create `templates/components/Toast.html.twig`: `<div {{ stimulus_controller('toast', {autoDismissMs: autoDismissMs}) }} class="toast {{ cssClass }}" {{ type in ['error','warning'] ? 'role="alert"' : 'role="status"' }}>` containing close button with `{{ stimulus_action('toast', 'close') }}` and `aria-label="Fermer"`, type label, optional title (`{% if title %}<strong class="toast-title">{{ title|e }}</strong>{% endif %}`), `{{ message|e }}` (escaped — never `|raw`); visual structure per `design/pages/08-feedback.html §toast`
- [X] T040 [P] [US3] Create `assets/styles/components/_toast.scss`: `.toast-rail` fixed container (top-right desktop with z-index above all content, full-width top on mobile — `@media (max-width: 767px) { bottom: auto; top: 0; width: 100%; left: 0; right: 0; }`); `.toast` base; `.toast-success`, `.toast-error`, `.toast-warning`, `.toast-info` color variants per `design/pages/08-feedback.html`; toast height expands for long messages (no `overflow: hidden`, no `max-height` truncation); newest-on-top stacking
- [X] T041 [US3] Verify `templates/components/Layout/FlashBag.html.twig` (created in T022) iterates `app.flashes()` correctly and container element wires `{{ stimulus_controller('toast-container') }}`; add `.toast-rail` wrapper div inside `base.html.twig` flash zone to position the fixed container
- [X] T042 [US3] Import `_toast.scss` in `assets/styles/app.scss` component imports section

**Checkpoint**: Add `$this->addFlash('success', 'Test notification')` to DefaultController home action, visit `/`. Green toast appears top-right. Auto-dismisses after 5 s. Close button works. Error flash shows red.

---

## Phase 6: User Story 4 — Reusable UI Component Library (Priority: P4)

**Goal**: Book card, Author card, Badge, Rating, Modal available as Twig components matching Design System exactly, with skeleton states and fallback props.

**Independent Test**: Render `<twig:Book:Card />` and `<twig:Author:Card />` with no props. Verify "Sans titre" / "Auteur inconnu" fallbacks. Verify visual match against `design/pages/05-cards.html`.

### Implementation for User Story 4

- [X] T043 [P] [US4] Create `assets/controllers/modal_controller.js`: `open(event)` stores `this.triggerElement = event.currentTarget`, adds `is-open` class to modal element, sets `document.body.style.overflow = 'hidden'`, calls `this.trapFocus()`; `close()` removes `is-open`, restores `document.body.style.overflow = ''`, returns focus to `this.triggerElement`; `trapFocus()` queries all focusable elements within modal and cycles Tab/Shift+Tab within them; `handleKeydown(event)` on `keydown->modal#handleKeydown` closes on Escape; `handleBackdropClick(event)` closes if `event.target === this.element`; do NOT use Bootstrap's JS modal (research.md Decision 3)
- [X] T044 [P] [US4] Create `src/Twig/Components/Modal.php`: `#[AsTwigComponent]` class with `public string $id`, `public string $title`, `public string $variant = 'default'`, `public string $size = 'md'`; `#[PostMount]` validates `variant` in `['default', 'danger']` (fallback `'default'`) and `size` in `['sm', 'md', 'lg', 'xl']` (fallback `'md'`); do NOT mark readonly
- [X] T045 [P] [US4] Create `templates/components/Modal.html.twig`: `<div id="{{ id }}" {{ stimulus_controller('modal') }} class="modal modal--{{ size }}{{ variant == 'danger' ? ' danger-accent' : '' }}" role="dialog" aria-modal="true" aria-labelledby="{{ id }}-title" {{ stimulus_action('modal', 'handleKeydown', 'keydown') }}>` containing backdrop div with `{{ stimulus_action('modal', 'handleBackdropClick') }}`, modal container with header (`<h2 id="{{ id }}-title">{{ title }}</h2>` + close button `aria-label="Fermer"` with `{{ stimulus_action('modal', 'close') }}`), `{% block modal_body %}{% endblock %}`, `{% block modal_footer %}{% endblock %}`; per `design/pages/08-feedback.html §modal`
- [X] T046 [P] [US4] Create `assets/styles/components/_modal.scss`: `.modal` (hidden default, `.is-open` state shows), `.modal-overlay` (backdrop with click target), `.modal-header`, `.danger-accent::before` (4px danger color bar at modal top); transitions per `design/pages/08-feedback.html`; use token CSS custom properties
- [X] T047 [P] [US4] Create `src/Twig/Components/Book/Card.php`: `#[AsTwigComponent]` class with `public ?string $title = null`, `public ?string $coverUrl = null`, `public ?string $author = null`, `public ?float $rating = null`, `public ?int $bookId = null`, `public bool $loading = false`; `#[PostMount]` sets `$this->title ??= 'Sans titre'`, `$this->coverUrl ??= null` (null means show gradient placeholder), `$this->author ??= 'Auteur inconnu'`; do NOT mark readonly
- [X] T048 [P] [US4] Create `templates/components/Book/Card.html.twig`: when `loading` is true render skeleton shimmer (`<div class="card-book card-book--skeleton">` with placeholder zones for cover area, title block, metadata row, badge row — shimmer via CSS animation class); when false render full card per `design/pages/05-cards.html §.card-book` — cover: `<img>` if `coverUrl` or gradient placeholder div (`style="background: linear-gradient(135deg, var(--cuir-300), var(--cuir-500))"`) with `.cover-frame` corner decoration; title: `<a href="{{ path('home') }}" aria-label="{{ title }}">` if `bookId`, else `<span>`; hover transition per Design System; `aria-label="{{ title }}"` on card if `bookId` present
- [X] T049 [P] [US4] Create `src/Twig/Components/Author/Card.php`: `#[AsTwigComponent]` class with `public ?string $name = null`, `public ?string $avatarUrl = null`, `public ?int $bookCount = null`, `public bool $loading = false`; `#[PostMount]` sets `$this->name ??= 'Auteur inconnu'`; do NOT mark readonly
- [X] T050 [P] [US4] Create `templates/components/Author/Card.html.twig`: when `loading` render skeleton shimmer with zones (96px circle, name line, role line, stats grid); when false render per `design/pages/05-cards.html §.card-author` — avatar: 96×96px `<img>` if `avatarUrl` or radial-gradient circle placeholder `radial-gradient(circle at 35% 30%, var(--cuir-300), var(--cuir-500) 60%, var(--cuir-700))` with "?" initials in 32px display font; name; optional `bookCount`; same hover/focus/link behavior as Book:Card
- [X] T051 [P] [US4] Create `src/Twig/Components/Badge.php`: `#[AsTwigComponent]` class with `public string $label`, `public string $variant = 'primary'`; `#[PostMount]` validates `variant` against allowed: `['primary', 'pending', 'validated', 'rejected', 'archived', 'user', 'mod', 'admin']`; unrecognized → `'primary'`; do NOT mark readonly
- [X] T052 [P] [US4] Create `templates/components/Badge.html.twig`: status variants use `badge-status-{{ variant }}`, role variants use `badge-role-{{ variant }}`, generic uses `badge`; full template: `<span class="badge {{ variant in ['pending','validated','rejected','archived'] ? 'badge-status-' ~ variant : (variant in ['user','mod','admin'] ? 'badge-role-' ~ variant : 'badge') }}">{{ label|e }}</span>`; per `design/assets/components.css §.badge`
- [X] T053 [P] [US4] Create `src/Twig/Components/Rating.php`: `#[AsTwigComponent]` class with `public float $value`, `public int $max = 5`, `public string $size = 'md'`; `#[PostMount]` clamps `$this->value = max(0.0, min($this->value, (float)$this->max))`; validates `size` in `['sm', 'md', 'lg']` (fallback `'md'`); do NOT mark readonly
- [X] T054 [P] [US4] Create `templates/components/Rating.html.twig`: `<div class="rating size-{{ size }}" aria-label="{{ value|number_format(1) }}/{{ max }} étoiles" role="img">` containing star elements with `style="--fill: {{ (value / max * 100)|round }}%"` for partial fill via CSS, `.rating-score` showing `{{ value|number_format(1) }}`, `.rating-count` if count prop provided; display-only — no interactive input; per `design/assets/components.css §.rating`
- [X] T055 [P] [US4] Create `assets/styles/components/_cards.scss`: `.card-book` (all densities: full, row, micro per `design/pages/05-cards.html`), `.card-author`; `@keyframes shimmer { 0%,100% { background-position: -200% 0 } 50% { background-position: 200% 0 } }` sweeping `var(--bg-elevated) → var(--bg-sunken) → var(--bg-elevated)` 1.5s linear infinite; `.cover-frame` corner decoration; hover transitions; `:focus-within { outline: 2px solid var(--ring-focus); }`; all from `design/pages/05-cards.html`
- [X] T056 [P] [US4] Create `assets/styles/components/_badges.scss`: `.badge` (base), `.badge-status-pending`, `.badge-status-validated`, `.badge-status-rejected`, `.badge-status-archived`, `.badge-role-user`, `.badge-role-mod`, `.badge-role-admin`; all per `design/assets/components.css`
- [X] T057 [P] [US4] Create `assets/styles/components/_rating.scss`: `.rating`, `.star` (CSS `--fill` property for partial fill via `clip-path` or gradient technique), `.rating-score`, `.rating-count`, `.size-sm`, `.size-md`, `.size-lg`; per `design/assets/components.css §.rating`
- [X] T058 [US4] Import `_modal.scss`, `_cards.scss`, `_badges.scss`, `_rating.scss` in `assets/styles/app.scss` component imports section
- [X] T059 [P] [US4] Create `public/images/placeholder-cover.svg`: SVG with striped `linearGradient` from cuir-300 (`#c4956a`) to cuir-500 (`#8b4513`) at 135°, plus inner `.cover-frame` corner decoration element; dimensions matching standard card cover ratio per `design/pages/05-cards.html` (FR-007)
- [X] T060 [P] [US4] Create `public/images/placeholder-avatar.svg`: SVG 96×96px viewBox circle with `radialGradient(circle at 35% 30%, #c4956a, #8b4513 60%, #5c2409)` fill and centered "?" text in 32px Cinzel font; per FR-008

**Checkpoint**: Render `<twig:Book:Card />` and `<twig:Author:Card />` with no props — fallbacks "Sans titre" / "Auteur inconnu" show. Visual match against `design/pages/05-cards.html`. Modal opens/closes/ESC/backdrop dismiss works. Focus trapped. Badge and Rating render per design.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Build verification, PHPUnit test coverage, WCAG compliance audit, quickstart validation.

- [X] T061 Audit `assets/styles/app.scss` to confirm all component partials are imported and import order strictly matches `research.md Decision 2`; run `npm run build` (production mode) and verify exit code 0 — this is the SC-006 gate
- [X] T062 [P] Write PHPUnit `WebTestCase` for base layout in `tests/Controller/DefaultControllerTest.php`: GET `/` returns 200; response contains `<nav aria-label="Navigation principale">`; response contains `aria-label="Pied de page"`; response contains `data-controller="toast-container"`
- [X] T063 [P] Write PHPUnit `KernelTestCase` tests using `InteractsWithTwigComponents` trait:
  - `tests/Twig/Components/BookCardTest.php`: mount with no props → assert "Sans titre" and "Auteur inconnu"; mount with `title="Dune"` → assert "Dune"; mount with `loading: true` → assert `card-book--skeleton` class
  - `tests/Twig/Components/AuthorCardTest.php`: same pattern for Author card
  - `tests/Twig/Components/ToastTest.php`: mount with `type='invalid'` → assert normalized to `'info'`; assert `getCssClass()` returns `'toast-info'`; mount with `type='error'` → assert `getCssClass()` returns `'toast-error'`
  - `tests/Twig/Components/BadgeTest.php`: mount with unrecognized `variant='xyz'` → assert normalized to `'primary'`; mount with `variant='pending'` → assert `badge-status-pending` in rendered HTML
  - `tests/Twig/Components/RatingTest.php`: mount with `value=-1` → assert clamped to `0.0`; mount with `value=6, max=5` → assert clamped to `5.0`; mount with `size='invalid'` → assert normalized to `'md'`
  - `tests/Twig/Components/ModalTest.php`: mount with `variant='invalid'` → assert normalized to `'default'`; mount with `size='invalid'` → assert normalized to `'md'`; mount with `variant='danger'` → assert `danger-accent` in rendered HTML
- [X] T064 [P] Verify ARIA attributes in Twig templates match `contracts/layout.md §ARIA Specification` exactly: `aria-label="Navigation principale"` on desktop nav, `aria-label="Navigation mobile"` on bottom nav, `aria-current="page"` on active link, `role="status" aria-live="polite" aria-atomic="false"` on toast container, `role="alert"` on error/warning toasts, `aria-label="Pied de page"` on footer, `role="dialog" aria-modal="true"` on modal
- [X] T065 Create `src/Controller/TokenTestController.php` with `#[Route('/test-tokens', name: 'test_tokens')]` route and `templates/test/tokens.html.twig` extending `base.html.twig`; render five `<div>` elements applying each Design System token category under test (`--brand-primary`, `--font-display`, `--sp-4`, `--radius-md`, `--shadow-md`); used for SC-001 manual verification against `design/assets/tokens.css`
- [X] T066 Run `./vendor/bin/phpunit` and verify all PHPUnit tests pass; fix any regressions before marking complete
- [X] T067 Run quickstart.md scenario: create a test template extending `base.html.twig` using only `<twig:Book:Card />` and `<twig:Author:Card />` with no custom CSS; confirm task completes with zero added CSS lines (SC-002)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 — BLOCKS all user stories
- **US1 (Phase 3)**: Depends on Foundational — independent of US2/US3/US4
- **US2 (Phase 4)**: Depends on Foundational — independent of US1/US3/US4
- **US3 (Phase 5)**: Depends on Foundational — best after US1 (FlashBag wired in base layout)
- **US4 (Phase 6)**: Depends on Foundational — best after US2 (component SCSS uses token custom properties)
- **Polish (Phase 7)**: Depends on all desired user stories complete

### User Story Dependencies

- **US1 (P1)**: Independently testable with minimal Bootstrap token coverage — "blank styled page" valid
- **US2 (P2)**: Independent of US1 — token SCSS compiles separately
- **US3 (P3)**: Soft dependency on US1 (FlashBag in base layout); Toast component itself independent
- **US4 (P4)**: Soft dependency on US2 (card/badge/rating SCSS reference token custom properties)

### Within Each User Story

- PHP component class before Twig template (template uses class prop names)
- SCSS file before app.scss import (file must exist to import)
- Stimulus controller before Twig template wiring (controller must exist for `stimulus_controller()`)

### Parallel Opportunities

- All [P] tasks within a phase have no file conflicts — execute simultaneously
- US1: T017, T019, T021 (3 PHP classes) fully parallel; T025, T026 (2 SCSS files) fully parallel
- US2: T028, T029, T030, T031 (4 token files) fully parallel
- US4: T043, T047, T049, T051, T053, T055, T056, T057, T059, T060 all parallel in first wave

---

## Parallel Example: User Story 4

```
Wave 1 — all parallel (different files):
  T043: modal_controller.js
  T044: Modal.php          T047: Book/Card.php     T049: Author/Card.php
  T051: Badge.php          T053: Rating.php
  T055: _cards.scss        T056: _badges.scss      T057: _rating.scss
  T059: placeholder-cover.svg                      T060: placeholder-avatar.svg

Wave 2 — after Wave 1 PHP classes complete (different files, still parallel):
  T045: Modal.html.twig    T046: _modal.scss
  T048: Book/Card.html.twig
  T050: Author/Card.html.twig
  T052: Badge.html.twig    T054: Rating.html.twig

Wave 3 — final aggregation:
  T058: Import all _*.scss in app.scss
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational — `npm run dev` compiles
3. Complete Phase 3: User Story 1 — branded layout on every page
4. **STOP and VALIDATE**: visit `/`, confirm navbar + footer + flash zone; try extending base layout
5. Deploy to Platform.sh if ready (SC-006 = `npm run build` exit 0)

### Incremental Delivery

1. Setup + Foundational → pipeline compiles
2. US1 → branded layout MVP ← **demo here**
3. US2 → exact Design System token values
4. US3 → flash notification feedback loop
5. US4 → full component library accelerates all future features
6. Polish → production build verified, tests green

---

## Notes

- **Design System values**: Read `design/assets/tokens.css` and `design/assets/components.css` before implementing ANY SCSS — zero invented values permitted (FR-012, SC-001)
- **No `public/build/` commits**: asset compilation runs in Platform.sh build hook only
- **No `readonly` on Twig Component classes**: framework sets public properties via reflection post-instantiation
- **No full Bootstrap JS bundle**: import Bootstrap ESM selectively within Stimulus controllers only (research.md Decision 2)
- **No Bootstrap JS modal**: custom Stimulus `modal` controller required to avoid focus-trap conflict (research.md Decision 3)
- **Toast messages**: render via `{{ message|e }}` / `textContent` only — never `|raw` / `innerHTML` (XSS prevention, FR-010)
- **Active nav link**: detect via `app.request.pathInfo` in Twig — no PHP controller variable needed (FR-001)
- **Navbar collapse**: Bootstrap native `data-bs-toggle="collapse"` only — no custom Stimulus controller (research.md Decision 3)
