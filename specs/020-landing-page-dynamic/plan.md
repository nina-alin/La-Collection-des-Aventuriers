# Implementation Plan: Landing Page Publique Dynamique

**Branch**: `020-landing-page-dynamic` | **Date**: 2026-06-07 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/020-landing-page-dynamic/spec.md`

## Summary

Replace the current `/` route (protected dashboard) with a split: a public landing page for unauthenticated visitors (with live stats pill, animated counters, and CSS-driven marquee populated from two new `/api/public/*` endpoints) and a separate `/dashboard` route for authenticated users. The new `LandingController` redirects authenticated users to the dashboard; the existing `DashboardController` moves to `/dashboard`.

## Technical Context

**Language/Version**: PHP 8.2 / Symfony 6.4 LTS

**Primary Dependencies**: Symfony 6.4, Doctrine ORM, Stimulus (Hotwired), Webpack Encore, Bootstrap, SCSS

**Storage**: PostgreSQL via Doctrine ORM — **no new migrations needed** (feature is purely read-only from existing entities)

**Testing**: PHPUnit (existing test suite)

**Target Platform**: Platform.sh (Linux)

**Project Type**: Symfony MVC web application with Stimulus frontend

**Performance Goals**: Landing page load < 2 s; authenticated redirect < 500 ms (SC-001, SC-006)

**Constraints**: No new CSS frameworks; no new JS frameworks; no infrastructure changes to `.platform.app.yaml` / `.platform/routes.yaml` / `.platform/services.yaml`

**Scale/Scope**: 1 public page + 2 public JSON API endpoints; 3 new Stimulus controllers; 2 DTOs

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-checked after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Complémentarité Stricte | ✅ PASS | Landing promotes the collection platform; no forum/news feature |
| II. Architecture Symfony LTS | ✅ PASS | `LandingController` thin (no business logic); `LandingService` encapsulates all data assembly; Doctrine repos only; DI throughout; no infrastructure files changed |
| III. Workflow de Validation | ✅ PASS | Feature is read-only (no user content submission) |
| IV. RBAC | ✅ PASS | `/api/public/*` routes registered as `PUBLIC_ACCESS` in `security.yaml`; no data mutations → no CSRF tokens required on these endpoints |
| V. Tests | ✅ PASS | `LandingServiceTest` and `PublicApiControllerTest` required before merge |

**No violations → Complexity Tracking table omitted.**

## Project Structure

### Documentation (this feature)

```text
specs/020-landing-page-dynamic/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── api-public.md
└── tasks.md             # Phase 2 output (/speckit-tasks command)
```

### Source Code (repository root)

```text
src/
├── Controller/
│   ├── LandingController.php          (new)   — GET /, redirects if authenticated
│   └── PublicApiController.php        (new)   — GET /api/public/stats + /api/public/marquee
├── Service/
│   └── LandingService.php             (new)   — assembles LandingStatsDto + MarqueeItemDto[]
└── Dto/
    ├── LandingStatsDto.php            (new)
    └── MarqueeItemDto.php             (new)

templates/
└── landing/
    └── index.html.twig               (new)   — full landing page, follows design/landing.html

assets/
├── controllers/
│   ├── landing-stats_controller.js    (new)   — fetch /api/public/stats; animate counters via IntersectionObserver
│   └── theme_controller.js            (new)   — landing page theme toggle (parchment/dark)
└── styles/pages/
    └── _landing.scss                  (new)   — landing-specific CSS extracted from design/landing.html

tests/
└── Unit/Service/
    └── LandingServiceTest.php         (new)
```

**Modified files:**

```text
src/Controller/DashboardController.php
  — Remove class-level #[IsGranted]; change Route('/', name:'home') → Route('/dashboard', name:'app_dashboard')
  — Add method-level #[IsGranted('ROLE_USER')] on home()

templates/base.html.twig
  — Add anti-flash theme script inline in <head> (before CSS, reads localStorage 'theme')

config/packages/security.yaml
  — Add { path: ^/api/public/, roles: PUBLIC_ACCESS } to access_control
  — Update form_login default_target_path: / → /dashboard

assets/styles/app.scss
  — Import _landing.scss
```

**Structure Decision**: Single Symfony project (Option 1). Backend adds a Service + 2 Controllers + 2 DTOs. Frontend adds 2 Stimulus controllers + 1 SCSS partial.
