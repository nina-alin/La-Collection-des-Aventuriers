# Implementation Plan: Omnibox Global Search

**Branch**: `012-omnibox-global-search` | **Date**: 2026-05-31 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/012-omnibox-global-search/spec.md`

---

## Summary

Add a global search omnibox to the navigation bar: a `<form class="sh-search">` with a Stimulus-driven dropdown panel that shows session-history recents, popularity-proxied "Souvent Consultés", and live search results (books + collections + contributors) with skeleton loading, full keyboard navigation (ARIA combobox pattern), and a "Recherche avancée" footer link to `/catalogue`.

**Technical approach**: New `GlobalSearchService` + `SearchController` (JSON API, 2 GET endpoints) + `search_controller.js` (Stimulus) + Navbar Twig template update. No new entities, no DB migration.

---

## Technical Context

**Language/Version**: PHP 8.2 + JavaScript ES2022

**Primary Dependencies**: Symfony 7.2, Doctrine ORM 3, Stimulus 3 (`@hotwired/stimulus`), Webpack Encore 6, Bootstrap 5.3

**Storage**: PostgreSQL (existing — Doctrine ORM, no migration required)

**Entity name mapping**: PHP entity `Contributor` = domain label "Auteur" (spec.md). URL slug pattern `/auteur/:slug` matches the spec throughout.

**Testing**: PHPUnit (via `php bin/phpunit`)

**Target Platform**: Linux server (Platform.sh), Desktop ≥720px + Mobile <720px browsers

**Project Type**: Web application (Symfony + Twig + Stimulus)

**Performance Goals**:
- SC-002: Panel open ≤300ms from `focus` event
- SC-003: Results render ≤500ms from API response receipt
- Debounce 300ms + API timeout 5000ms

**Constraints**:
- Max 8 dynamic results, max 4 popular items
- WCAG 2.1 AA (`role="combobox"`, `aria-activedescendant`, `aria-live="polite"`)
- No new CSS framework, no new JS framework (Constitution IV)
- No new infrastructure service (no Redis, no new DB table)
- Session history: in-memory only, max 5 entries, FIFO dedup-by-move-to-front

**Scale/Scope**: 1 Stimulus controller, 2 API endpoints, 1 service, 3 repository method pairs, 1 DTO class, 1 Navbar template update

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Verdict | Notes |
|-----------|---------|-------|
| I. Complémentarité | ✅ PASSES | Search is core catalog navigation. No discussion/news feature introduced. |
| II. Symfony Architecture | ✅ PASSES | `SearchController` thin (JSON serialization only). Business logic in `GlobalSearchService`. Doctrine ORM exclusively. DI used throughout. No new infrastructure → no `.platform` file changes needed. |
| III. Workflow Validation | ✅ N/A | Read-only search. No content submitted, no `PENDING` workflow touched. |
| IV. RBAC | ✅ PASSES (with requirement) | Both API endpoints protected with `#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]`. No CSRF needed (GET, read-only). |
| V. Sécurité / Tests | ✅ PASSES (with requirement) | PHPUnit tests required: `GlobalSearchServiceTest` (unit) + `SearchControllerTest` (functional). Must cover happy path, empty results, popular fallback, 8-item cap. |

**Post-design re-check**: No violations introduced. No new infrastructure, no content mutation, no privilege escalation surface.

---

## Project Structure

### Documentation (this feature)

```text
specs/012-omnibox-global-search/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── search-api.md   # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit-tasks — not yet generated)
```

### Source Code (repository root)

```text
src/
├── Controller/
│   └── SearchController.php          # NEW — GET /api/search, GET /api/search/popular
├── Service/
│   └── GlobalSearchService.php       # NEW — query, merge, cap logic
├── Dto/
│   └── Search/
│       ├── SearchResultItem.php      # NEW — readonly DTO
│       └── SearchResponse.php        # NEW — readonly DTO (optional, or inline array)
└── Repository/
    ├── BookRepository.php            # EDIT — add findForGlobalSearch(), findMostPopular()
    ├── CollectionRepository.php      # EDIT — add findForGlobalSearch(), findMostPopular()
    └── ContributorRepository.php     # EDIT — add findForGlobalSearch(), findMostPopular()

assets/
├── controllers/
│   └── search_controller.js          # NEW — Stimulus controller (panel, keyboard, debounce)
└── styles/
    └── components/
        └── search.css                # NEW — imports design/assets/search.css + skeleton anim

templates/
└── components/
    └── Layout/
        └── Navbar.html.twig          # EDIT — add <form class="sh-search"> with data-controller

tests/
├── Unit/
│   └── Service/
│       └── GlobalSearchServiceTest.php   # NEW
└── Functional/
    └── Controller/
        └── SearchControllerTest.php      # NEW
```

**Structure Decision**: Single Symfony web application. Frontend in Stimulus (no separate frontend build target). Follows existing project layout: controllers in `src/Controller/`, services in `src/Service/`, Stimulus controllers in `assets/controllers/`.

---

## Complexity Tracking

> **No Constitution violations — table not required.**
