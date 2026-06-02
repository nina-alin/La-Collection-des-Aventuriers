# Implementation Plan: Personal Library Lists (Listes Livre)

**Branch**: `016-personal-library-lists` | **Date**: 2026-06-01 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/016-personal-library-lists/spec.md`

## Summary

Implement functional toggle buttons for personal book lists (Ma Collection, À lire, À acheter, Favoris) on the book detail page. Requires migrating `UserBook.status` from a mutually-exclusive enum to 4 independent boolean fields, a new `BookLibraryActionsComponent` Live Component, a `UserBookService` encapsulating business rules (auto-coherence, cascade delete when all false), and enhanced toast-container Stimulus controller to receive events dispatched from the PHP component.

## Technical Context

**Language/Version**: PHP 8.2 / Symfony 7.2

**Primary Dependencies**: symfony/ux-live-component ^2.36, symfony/ux-twig-component ^2.35, Doctrine ORM, Symfony Security, Bootstrap 5

**Storage**: PostgreSQL (Platform.sh), Doctrine ORM — migration drops `status` enum column, adds `is_owned`, `is_to_read`, `is_to_buy` booleans

**Testing**: PHPUnit (symfony/browser-kit, symfony/css-selector)

**Target Platform**: Platform.sh web application

**Project Type**: Symfony web application with Twig + Symfony UX

**Performance Goals**: <300 ms server response per toggle (SC-001)

**Constraints**: CSRF via Symfony UX (automatic), `#[IsGranted]` on all mutating LiveActions, no new JS frameworks

**Scale/Scope**: Per-user per-book; `UserBook` rows deleted when all 4 flags become false

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I — Strict Complementarity | ✅ PASS | Personal collection management is explicitly within scope |
| II — Symfony LTS Architecture | ✅ PASS | Business logic in `UserBookService`; component is thin (delegates to service); Doctrine ORM only; DI used throughout |
| III — Content Validation Workflow | ✅ PASS | UserBook status flags are personal user expressions (like Review scores), not editorial content; no PENDING state needed |
| IV — RBAC | ✅ PASS | Each `#[LiveAction]` method carries `#[IsGranted('ROLE_USER')]`; CSRF handled by Symfony UX automatically |
| V — Security & Test Coverage | ✅ PASS | PHPUnit tests required for `UserBookService` (business rules) and functional tests for Live Component actions |

**Re-check post-design**: See data-model.md — no violations introduced.

## Project Structure

### Documentation (this feature)

```text
specs/016-personal-library-lists/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   ├── BookLibraryActionsComponent.md
│   └── UserBookService.md
└── tasks.md             # Phase 2 output (/speckit-tasks)
```

### Source Code (repository root)

```text
src/
├── Entity/
│   ├── UserBook.php                           # MODIFIED — status enum → 4 booleans
│   └── Enum/
│       └── UserBookStatus.php                 # REMOVED — replaced by booleans
├── Repository/
│   └── UserBookRepository.php                 # MODIFIED — add findByUserAndBook()
├── Service/
│   └── UserBookService.php                    # NEW — toggle logic, auto-coherence, cascade delete
└── Twig/Components/Book/
    └── LibraryActionsComponent.php            # NEW — Live Component (#[AsLiveComponent])

templates/
├── components/Book/
│   └── LibraryActionsComponent.html.twig     # NEW — 4 action-toggle buttons
└── livre/
    └── show.html.twig                         # MODIFIED — replace static actions-grid with <twig:Book:LibraryActionsComponent>

assets/controllers/
└── toast-container_controller.js             # MODIFIED — listen for 'toast' browser event

migrations/
└── VersionXXXXXXXXXXXXXX.php                # NEW — drop status, add is_owned/is_to_read/is_to_buy

tests/
├── Unit/Service/
│   └── UserBookServiceTest.php               # NEW — business logic tests
└── Functional/
    └── BookLibraryActionsTest.php            # NEW — auth, toggle, auto-coherence, idempotence
```

**Structure Decision**: Single Symfony project layout. Backend: service + Live Component. Frontend: Twig template + Stimulus enhancement. No new projects or packages.

## Complexity Tracking

> No Constitution violations — table not required.
