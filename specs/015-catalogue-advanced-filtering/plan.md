# Implementation Plan: Catalogue Page & Advanced Filtering

**Branch**: `015-catalogue-advanced-filtering` | **Date**: 2026-06-01 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/015-catalogue-advanced-filtering/spec.md`

## Summary

Full implementation of the `/catalogue` route — currently a stub rendering `home/index.html.twig`. Delivers a Symfony-rendered catalogue page with a Symfony UX Live Component filter panel (draft state + live DQL COUNT + server-side editor search), paginated book grid, URL-serialized active filter state for deep linking, mobile FAB modal (Stimulus), grid/list toggle (Stimulus), in-page search autocomplete (Stimulus), and per-user book marks (owned/favourite/wishlist) via a new `UserBook` entity.

## Technical Context

**Language/Version**: PHP 8.3

**Primary Dependencies**: Symfony 7.2 LTS, Doctrine ORM 3.6, Symfony UX Live Component 2.36, Symfony UX Twig Component 2.35, Symfony UX Turbo 2.36, Twig, Bootstrap (project design tokens/components via `design/assets/`)

**Storage**: PostgreSQL via Doctrine ORM

**Testing**: PHPUnit (existing `phpunit.dist.xml`)

**Target Platform**: Platform.sh (Linux)

**Project Type**: Symfony web application, Twig-rendered, server-side filtered/paginated catalogue

**Performance Goals**: Results grid refresh < 1 s after "Appliquer" (SC-001); DQL COUNT debounced at 300 ms updates button label without perceptible lag

**Constraints**:
- No new infrastructure (no Redis, no new managed service) → Platform.sh files unchanged
- Twig templates only; Bootstrap + project tokens only (no new CSS framework)
- Symfony UX Live Component already installed (`symfony/ux-live-component: ^2.36`)
- Doctrine `Paginator` for pagination (project pattern: `CollectionRepository`, `ReviewRepository`)
- 24 books per page

**Scale/Scope**: Full catalogue (~2 481 books per SC-001); DQL filter queries with indexed columns; no N+1 with proper JOIN strategy

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| **I — Complémentarité Stricte** | ✅ PASS | Catalogue browsing and filtering is personal collection management / encyclopedic browsing; no forum/news feature |
| **II — Architecture Symfony LTS** | ✅ PASS | Thin `CatalogueController` delegates to `CatalogueService`; Live Component for draft state; Doctrine ORM for all DB; DI throughout; no new infrastructure → Platform.sh files unchanged |
| **III — Workflow Validation** | ✅ PASS | No user-submitted content; all routes are GET-only. `UserBook` write operations (marking books) are out of scope for this feature |
| **IV — RBAC** | ✅ PASS | `/catalogue` is public (no `#[IsGranted]`); "STATUT DANS MA COLLECTION" section absent from DOM for guests (server-side conditional); no data-mutating route added |
| **V — Sécurité & Tests** | ⚠ REQUIRES ACTION | PHPUnit tests must cover: `UserBook` entity, `UserBookStatus` enum, `BookRepository` DQL filter/COUNT/bounds methods, `CatalogueController` smoke test, `CatalogueFilterPanelComponent` COUNT logic |

**Post-Phase-1 re-check**: All principles still pass. No new infrastructure required. `UserBook` entity adds one migration (local Doctrine ORM only).

## Project Structure

### Documentation (this feature)

```text
specs/015-catalogue-advanced-filtering/
├── plan.md              ← this file
├── research.md          ← Phase 0 output
├── data-model.md        ← Phase 1 output
├── quickstart.md        ← Phase 1 output
├── contracts/           ← Phase 1 output (URL query param schema)
└── tasks.md             ← Phase 2 output (/speckit-tasks)
```

### Source Code

```text
src/
├── Controller/
│   └── CatalogueController.php                          (NEW — replaces catalogue stub in DefaultController)
├── Entity/
│   ├── UserBook.php                                     (NEW — user↔book marks: status + isFavorite)
│   └── Enum/
│       └── UserBookStatus.php                           (NEW)
├── Repository/
│   ├── BookRepository.php                               (MODIFIED — filter DQL, COUNT, paragraph bounds, paginated filter)
│   ├── EditorRepository.php                             (NEW or MODIFIED — name search + book count aggregate)
│   └── UserBookRepository.php                           (NEW)
├── Service/
│   └── CatalogueService.php                             (NEW — orchestrate filter query + paragraph bounds + UserBook lookup)
├── Dto/
│   └── ActiveFilterState.php                            (NEW — immutable DTO hydrated from URL params)
├── Twig/
│   ├── Components/
│   │   ├── Book/
│   │   │   └── Card.php                                 (MODIFIED — add isFavorite, isOwned, isWishlist props)
│   │   └── Catalogue/
│   │       └── FilterPanelComponent.php                 (NEW — LiveComponent: draft state, COUNT, editor search)
│   └── Extension/
│       └── CatalogueExtension.php                       (NEW — Twig helper: activeFilterState → URL query string)
└── DataFixtures/
    └── AppFixtures.php                                   (MODIFIED — add UserBook fixtures)

assets/
└── controllers/
    ├── catalogue-fab_controller.js                       (NEW — FAB ↔ full-screen modal, focus trap, Escape)
    ├── catalogue-view_controller.js                      (NEW — grid/list toggle + sessionStorage persistence)
    └── catalogue-search_controller.js                    (NEW — in-page autocomplete dropdown)

templates/
├── catalogue/
│   └── index.html.twig                                  (NEW — full page: panel + toolbar + grid + pagination)
└── components/
    ├── Book/
    │   └── Card.html.twig                               (MODIFIED — cover-marks for owned/favourite/wishlist)
    └── Catalogue/
        └── FilterPanelComponent.html.twig               (NEW — Live Component template)

migrations/
└── VersionYYYYMMDDHHmmss.php                            (NEW — user_book table)

tests/
├── Entity/
│   └── UserBookTest.php                                 (NEW)
├── Repository/
│   └── BookRepositoryTest.php                           (NEW or MODIFIED)
└── Controller/
    └── CatalogueControllerTest.php                      (NEW)
```

**Structure Decision**: Single Symfony web application. `Catalogue/` namespace for the new Twig Live Component mirrors the `Suggestion/` convention (`src/Twig/Components/Suggestion/WizardComponent.php`). `ActiveFilterState` DTO lives in `src/Dto/` consistent with any future DTO placement. `CatalogueService` follows the thin-controller principle (Principle II).

## Complexity Tracking

> No constitution violations requiring justification.
