# Implementation Plan: Page Mentions Légales

**Branch**: `021-mentions-legales-page` | **Date**: 2026-06-08 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/021-mentions-legales-page/spec.md`

## Summary

Create a public `/mentions-legales` route with a structured legal page: two-column layout (TOC + body) on desktop (≥900px), single-column on mobile, Bootstrap 5.3 Scrollspy with `rootMargin: '-88px 0px -65% 0px'` (desktop only), two new Twig include partials (`IdCard`, `Callout`), and the last-updated date externalized as a Symfony parameter. Wire the footer link.

## Technical Context

**Language/Version**: PHP 8.2 / Symfony 7.2 LTS

**Primary Dependencies**: Symfony 7.2, Twig, Bootstrap 5.3 (ScrollSpy), Symfony UX Twig Component

**Storage**: N/A — no database entities; one Symfony parameter for the last-updated date

**Testing**: PHPUnit / Symfony WebTestCase (smoke test: GET /mentions-legales → 200)

**Target Platform**: PHP web application on Platform.sh

**Project Type**: Symfony/Twig web application

**Performance Goals**: Static page, no specific goals — no DB queries

**Constraints**:
- Breakpoint at **900px** (not Bootstrap `md` 768px) — per design mockup
- Header height **88px** used as Scrollspy `rootMargin` top offset
- No new JS frameworks; no `twig/intl-extra` (date stored as pre-formatted string)

**Scale/Scope**: Single page addition; no authenticated access required

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Verdict | Notes |
|---|---|---|
| I. Complémentarité Stricte | ✅ PASS | Legal/compliance page — not a competing feature |
| II. Architecture Symfony LTS | ✅ PASS | Thin `LegalController`, no business logic, no Doctrine, no infrastructure change |
| III. Workflow de Validation du Contenu | ✅ N/A | No user-submitted content |
| IV. RBAC — Trois Niveaux de Droits | ✅ N/A | Public read-only page; no data mutation, no CSRF/IsGranted needed |
| V. Sécurité et Couverture de Tests | ✅ PASS | Route smoke test covers primary "logic" (the route itself); no business logic exists to cover further |

**Post-design re-check**: No violations introduced. No Platform.sh config changes needed (no new services).

## Project Structure

### Documentation (this feature)

```text
specs/021-mentions-legales-page/
├── plan.md              # This file
├── research.md          # Phase 0 — date formatting, Scrollspy, partials decisions
├── data-model.md        # Phase 1 — LegalPageConfig param, Section, IdCard, Callout
├── quickstart.md        # Phase 1 — how to update the date
├── contracts/
│   └── http-routes.md   # Phase 1 — GET /mentions-legales contract
└── tasks.md             # Phase 2 output (/speckit-tasks — NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
src/Controller/
└── LegalController.php                        # NEW — GET /mentions-legales, thin controller

templates/
├── legal/
│   ├── mentions-legales.html.twig             # NEW — main page template
│   ├── _id_card.html.twig                     # NEW — IdCard (TableauKeyValue) partial
│   └── _callout.html.twig                     # NEW — Callout (AlertBlock) partial
└── components/
    └── Layout/
        └── Footer.html.twig                   # MODIFIED — wire Mentions légales footer link

config/
└── services.yaml                              # MODIFIED — add app.legal.last_updated param

tests/
└── Functional/
    └── Controller/
        └── LegalControllerTest.php            # NEW — smoke test GET /mentions-legales → 200
```

**Structure Decision**: Single Symfony web application (standard layout). No new subdirectories outside of established conventions. The `templates/legal/` directory follows the same pattern as `templates/landing/`, `templates/catalogue/`, etc.

### Key implementation notes

**`LegalController`** — receives `$legalLastUpdated` injected via `services.yaml` `bind` or explicit argument. Passes it to the template as `lastUpdated`. Zero service dependencies.

**`mentions-legales.html.twig`** — extends `base.html.twig`. Overrides `{% block title %}`, `{% block meta %}`, and `{% block page_wrapper %}` (to remove the default `<main class="px-4 px-md-5 mx-md-5">` wrapper and use the doc-specific layout instead). Layout: `<div class="doc-layout">` grid (240px TOC + 1fr body at ≥900px). Scrollspy initialized via inline `<script>` at bottom.

**`_id_card.html.twig`** — receives `rows` (array of `{key, value}`). Iterates with `{% for row in rows %}`. Used via `{% include 'legal/_id_card.html.twig' with { rows: [...] } %}`.

**`_callout.html.twig`** — receives `content` (raw HTML string). Triangle SVG icon carries `aria-hidden="true"`. Used via `{% include 'legal/_callout.html.twig' with { content: '...' } %}`.

**Scrollspy JS** — conditional init:
```js
if (window.innerWidth >= 900) {
    bootstrap.ScrollSpy(document.body, {
        target: '#toc-nav',
        rootMargin: '-88px 0px -65% 0px'
    });
}
```
First TOC item gets `aria-current="true"` and Bootstrap's active class on page load.

**`scroll-behavior: smooth`** on `html` element — added via a scoped `<style>` block in the template (or as a page-specific CSS addition to avoid polluting global styles).

**Footer link** — replace `<a href="#">Mentions légales</a>` with `<a href="{{ path('app_mentions_legales') }}">Mentions légales</a>`.

**`services.yaml`** — add `app.legal.last_updated: "3 juin 2026"` under `parameters`. Wire to controller via:
```yaml
App\Controller\LegalController:
    arguments:
        $legalLastUpdated: '%app.legal.last_updated%'
```
