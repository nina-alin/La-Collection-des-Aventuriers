# Implementation Plan: Frontend Design System Foundation

**Branch**: `001-frontend-design-system` | **Date**: 2026-05-23 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/001-frontend-design-system/spec.md`

## Summary

Bootstrap 5.x + Claude Design System frontend architecture for a new Symfony 7.x LTS application. Delivers: SCSS design token system, base layout (navbar + footer + flash zone), Stimulus-controlled interactive components (modal, toast), and a Symfony UX Twig Component library (Book card, Author card, Badge, Rating). Webpack Encore pipeline; compiled assets built via Platform.sh build hook.

## Technical Context

**Language/Version**: PHP 8.2+, Symfony 7.x LTS; Node.js 20.x LTS (build only)

**Primary Dependencies**:
- `symfony/symfony` 7.2+
- `symfony/webpack-encore-bundle`
- `symfony/ux-twig-component`
- `symfony/ux-stimulus-bundle`
- `bootstrap` 5.3.x (npm)
- `bootstrap-icons` (npm)
- `@symfony/stimulus-bridge` (npm)
- `sass` + `sass-loader` (npm)

**Storage**: PostgreSQL (Platform.sh managed service) — no schema changes in this feature.

**Testing**: PHPUnit 11.x; Symfony WebTestCase for layout rendering.

**Target Platform**: Platform.sh; build hook `npm ci && npm run build`; compiled assets NOT committed.

**Project Type**: Symfony web application — frontend-first feature; no new entities, no API routes.

**Performance Goals**: SC-006 — successful Platform.sh build is the sole build quality gate.

**Constraints**:
- Bootstrap 5.x only (no jQuery)
- Stimulus for all JS interactivity (no Alpine.js, no vanilla JS)
- All design values from `design/assets/tokens.css` and `design/assets/components.css` — no invented or approximated values (FR-012, SC-001)
- Compiled assets NOT committed to git

**Scale/Scope**: Single Symfony app; 8 design system categories; ~15 Twig component files.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Complémentarité Stricte | ✅ PASS | Frontend infrastructure only — no forum, news, or La Taverne overlap |
| II. Architecture Symfony LTS | ✅ PASS | Twig Component PHP classes are thin value holders; no business logic; DI used; `.platform.*` files updated in same commit as app scaffold |
| III. Workflow de Validation du Contenu | ✅ N/A | No user-submitted content in this feature |
| IV. RBAC | ✅ N/A | No data-mutating routes |
| V. Sécurité et Couverture de Tests | ✅ PASS | PHPUnit tests for component rendering; toast XSS prevention enforced via plain-text rendering (FR-010) |

**Gate Result**: All principles pass or are not applicable. Proceeding to Phase 0.

## Project Structure

### Documentation (this feature)

```text
specs/001-frontend-design-system/
├── plan.md              # This file
├── research.md          # Phase 0 — technology decisions
├── data-model.md        # Phase 1 — component entity model
├── quickstart.md        # Phase 1 — developer onboarding
├── contracts/
│   ├── layout.md        # Base layout API
│   ├── components.md    # Twig component props API
│   └── tokens.md        # Design token reference
└── tasks.md             # Phase 2 — /speckit-tasks output
```

### Source Code (repository root)

```text
la-collection-dont-vous-etes-le-heros/
├── .platform.app.yaml          # Platform.sh app config (build hook, runtime)
├── .platform/
│   ├── routes.yaml
│   └── services.yaml           # PostgreSQL service
├── assets/
│   ├── bootstrap.js            # Stimulus + UX component bootstrap
│   ├── controllers.json        # Symfony UX controller map
│   ├── controllers/
│   │   ├── modal_controller.js     # Focus trap, ESC, backdrop dismiss
│   │   ├── toast_controller.js     # Auto-dismiss 5 s, stack limit 3
│   │   └── toast-container_controller.js  # Stack limit enforcement, mutation observer
│   └── styles/
│       ├── app.scss                # Entry point — imports everything
│       ├── tokens/
│       │   ├── _variables.scss     # Bootstrap overrides (from design/pages/01-couleurs.html §1.3)
│       │   ├── _colors.scss        # --cuir-*, --or-*, --parchemin-*, semantic tokens
│       │   ├── _typography.scss    # --font-*, --fs-*, --lh-*, --tracking-*
│       │   ├── _spacing.scss       # --sp-0 … --sp-9
│       │   └── _effects.scss       # --radius-*, --shadow-*, --ring-focus, --motion-*
│       └── components/
│           ├── _buttons.scss       # .btn, .btn-primary, … (mirrors components.css)
│           ├── _forms.scss         # .input, .textarea, .select, .choice, .form-group
│           ├── _badges.scss        # .badge, .badge-status-*, .badge-role-*
│           ├── _rating.scss        # .rating, .star, .rating-score, .rating-count
│           ├── _cards.scss         # .card-book, .card-author (all densities)
│           ├── _navbar.scss        # .lp-header, .lp-brand, bottom-nav
│           ├── _footer.scss        # .footer, .footer-grid, .footer-bottom
│           ├── _modal.scss         # .modal, .modal-overlay, .modal-header, .danger-accent
│           └── _toast.scss         # .toast, .toast-rail, .toast-timer, variants
├── composer.json
├── package.json
├── webpack.config.js
├── src/
│   ├── Controller/
│   │   └── DefaultController.php       # Stub home/catalogue/suggestions routes
│   └── Twig/
│       └── Components/
│           ├── Book/
│           │   └── Card.php            # title, author, coverUrl, stats, href, loading
│           ├── Author/
│           │   └── Card.php            # name, role, avatarUrl, stats, href, loading
│           ├── Badge.php               # variant (status|role), value
│           ├── Rating.php              # score, count, size (sm|md|lg)
│           ├── Modal.php               # variant (default|danger), title, id, size
│           └── Toast.php               # type, title (optional, default ''), message
└── templates/
    ├── base.html.twig                  # Navbar + main + footer + flash zone
    ├── home/
    │   └── index.html.twig
    └── components/
        ├── Book/
        │   └── Card.html.twig
        ├── Author/
        │   └── Card.html.twig
        ├── Layout/
        │   ├── Navbar.html.twig
        │   ├── Footer.html.twig
        │   └── FlashBag.html.twig
        ├── Badge.html.twig
        ├── Rating.html.twig
        ├── Modal.html.twig
        └── Toast.html.twig
```

**Structure Decision**: Single Symfony application. Twig Components follow `src/Twig/Components/` (PHP class) + `templates/components/` (Twig template) convention per `symfony/ux-twig-component` defaults.

## Complexity Tracking

> No Constitution violations requiring justification.
