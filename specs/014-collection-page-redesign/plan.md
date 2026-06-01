# Implementation Plan: Refonte Page Collection

**Branch**: `014-collection-page-redesign` | **Date**: 2026-06-01 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/014-collection-page-redesign/spec.md`

## Summary

Full visual redesign of `/collections/{slug}` to match `design/pages/collection.html`. Adds one new Doctrine entity (`CollectionPublishingHistory`), one Stimulus controller for client-side sort/filter, a dedicated DQL query for collection-wide contributor aggregation, and updates the Twig template in full. No new infrastructure services — Platform.sh files unchanged.

## Technical Context

**Language/Version**: PHP 8.3

**Primary Dependencies**: Symfony 7.2 LTS, Doctrine ORM 3.6, Symfony UX Stimulus 2.35, Twig, Bootstrap (project tokens/components system via `design/assets/`)

**Storage**: PostgreSQL via Doctrine ORM

**Testing**: PHPUnit (existing `phpunit.dist.xml`)

**Target Platform**: Platform.sh (Linux)

**Project Type**: Symfony web application, Twig-rendered, server-side pagination preserved

**Performance Goals**: Full page load < 2 s for a 30-tome collection (SC-001), including contributors DQL and Twig render

**Constraints**:
- No new infrastructure (no Redis, no new managed service)
- Client-side sort/filter via Stimulus only (NFR-003)
- Page is public, no auth required
- Twig templates only (no new JS framework)
- `CollectionPublishingHistory` fixtures only (no prod seed)

**Scale/Scope**: Per-collection query over ≤ few hundred tomes; no N+1 expected with DQL aggregation

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| **I — Complémentarité Stricte** | ✅ PASS | Read-only display page; no discussion, forum, or news feature |
| **II — Architecture Symfony LTS** | ✅ PASS | Controller stays thin (delegates to services); Doctrine ORM for new entity; DI throughout; no new infrastructure → Platform.sh files unchanged |
| **III — Workflow Validation** | ✅ PASS | No user content submission in this ticket |
| **IV — RBAC** | ✅ PASS | Page is public (`/collections/{slug}` has no `#[IsGranted]`); no data-mutating routes added |
| **V — Sécurité & Tests** | ⚠ REQUIRES ACTION | PHPUnit tests must cover: `CollectionPublishingHistory` entity, `ContributionRepository` DQL query, `CollectionService` aggregation logic |

**Complexity Tracking**

> No constitution violations.

## Project Structure

### Documentation (this feature)

```text
specs/014-collection-page-redesign/
├── plan.md              ← this file
├── research.md          ← Phase 0 output
├── data-model.md        ← Phase 1 output
├── quickstart.md        ← Phase 1 output
├── contracts/           ← Phase 1 output
└── tasks.md             ← Phase 2 output (/speckit-tasks)
```

### Source Code

```text
src/
├── Entity/
│   └── CollectionPublishingHistory.php          (NEW)
├── Repository/
│   ├── CollectionPublishingHistoryRepository.php (NEW)
│   └── ContributionRepository.php               (MODIFIED — new DQL method)
├── Service/
│   └── CollectionService.php                    (NEW — aggregation logic)
├── Controller/
│   └── CollectionController.php                 (MODIFIED — wire service)
└── DataFixtures/
    └── AppFixtures.php                          (MODIFIED — add publishing history fixtures)

assets/
└── controllers/
    └── collection-sort_controller.js            (NEW — Stimulus sort/filter)

templates/
└── collection/
    └── show.html.twig                           (FULL REWRITE)

migrations/
└── VersionYYYYMMDDHHmmss.php                   (NEW — CollectionPublishingHistory table)

tests/
├── Entity/
│   └── CollectionPublishingHistoryTest.php      (NEW)
└── Repository/
    └── ContributionRepositoryTest.php           (NEW or MODIFIED)
```

---

## Phase 0: Research

*All decisions resolved from spec clarifications and codebase inspection. No external unknowns.*

See [research.md](research.md).

---

## Phase 1: Design

See [data-model.md](data-model.md), [contracts/](contracts/), [quickstart.md](quickstart.md).
