# Layout Contract

**Branch**: `001-frontend-design-system` | **Date**: 2026-05-23

Defines the contract between `base.html.twig` and the page templates that extend it.

---

## Base Layout Contract

**Template**: `templates/base.html.twig`

### Mandatory blocks

Any template that extends `base.html.twig` MUST override:

| Block | Required | Description |
|-------|----------|-------------|
| `title` | Yes | Page title rendered in `<title>` |
| `body` | Yes | Main page content rendered inside `<main>` |

Any template that extends `base.html.twig` MAY override:

| Block | Default if absent | Description |
|-------|------------------|-------------|
| `stylesheets` | Empty | Per-page additional CSS |
| `javascripts` | Empty | Per-page additional JS |

### Fixed regions (cannot be suppressed by extending templates)

| Region | Element | Notes |
|--------|---------|-------|
| Navbar | `<header>` containing `<nav>` | Always rendered; `Layout:Navbar` component |
| Flash notification zone | `<div data-controller="toast-container">` | Always rendered; `Layout:FlashBag` component |
| Main content wrapper | `<main>` | Wraps `{% block body %}` |
| Footer | `<footer>` | `d-none d-md-block`; `Layout:Footer` component |

### Usage example

```twig
{# templates/home/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Accueil — La Collection{% endblock %}

{% block body %}
    <div class="container py-4">
        <h1>Ma Collection</h1>
        {# page content here #}
    </div>
{% endblock %}
```

No additional configuration required. Extending `base.html.twig` automatically inherits navbar, footer, and global styles. (SC-005, FR-011)

---

## ARIA Specification

Per-element ARIA requirements (NFR-001). Implementation MUST assign these attributes exactly.

| Element | Role | Required ARIA attributes |
|---------|------|--------------------------|
| `<header>` (wrapping nav) | `banner` (implicit) | — |
| `<nav>` desktop navbar | `navigation` (implicit) | `aria-label="Navigation principale"` |
| `<nav>` mobile bottom bar | `navigation` (implicit) | `aria-label="Navigation mobile"` |
| Active nav link | — | `aria-current="page"` |
| Hamburger toggle button | — | `aria-expanded`, `aria-controls` (Bootstrap native via `data-bs-toggle`) |
| `<main>` | `main` (implicit) | — |
| Flash notification container | — | `role="status"` `aria-live="polite"` `aria-atomic="false"` |
| Individual toast — success / info | — | `role="status"` |
| Individual toast — warning / error | — | `role="alert"` |
| `<footer>` | `contentinfo` (implicit) | `aria-label="Pied de page"` |
| Modal dialog | — | `role="dialog"` `aria-modal="true"` `aria-labelledby="<modal-title-id>"` |
| Modal close button | — | `aria-label="Fermer"` |

---

## Design Token Contract

All SCSS token files under `assets/styles/tokens/` MUST:

1. Define values as SCSS variables using the `!default` flag (so they can be overridden in tests if needed)
2. Use exact values from the Claude Design System document — no invented or approximated values (FR-012, SC-001)
3. Be imported via `assets/styles/tokens/_index.scss` before Bootstrap's `functions` partial
4. Map to Bootstrap's named variables where a Bootstrap equivalent exists (`$primary`, `$secondary`, `$body-bg`, etc.)
5. Be available as CSS custom properties in `:root` after Bootstrap's `_root.scss` processes them

All token values are available in `design/assets/tokens.css` and the Bootstrap SCSS mapping in `design/pages/01-couleurs.html §1.3`. No external URL required — design system is local.

---

## Webpack Encore Asset Contract

All application pages rendered via `base.html.twig` receive:

| Asset | Twig helper | Notes |
|-------|-------------|-------|
| Compiled CSS | `encore_entry_link_tags('app')` | All design tokens + Bootstrap + component styles |
| Compiled JS | `encore_entry_script_tags('app')` | Stimulus app + all controllers |

Page templates MUST NOT link additional CSS or JS files directly. Additional per-page assets use the `{% block stylesheets %}` / `{% block javascripts %}` blocks.
