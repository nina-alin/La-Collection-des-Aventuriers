# Implementation Plan: Dashboard (018-home-dashboard)

**Branch**: `018-home-dashboard` | **Date**: 2026-06-03 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/018-home-dashboard/spec.md`

## Summary

Build the authenticated home dashboard (route `/`): a server-rendered Symfony page aggregating 6 sections (header with contextual subtitle, 3 KPI blocks, quick-access card grid with RBAC-gated moderation card, 5 recent catalogue entries, 10-event community feed, forum banner). Backed by a new `DashboardService`, a new `ActivityEvent` entity fed by Symfony event listeners, and two Doctrine migrations (User login timestamps + activity_event table).

## Technical Context

**Language/Version**: PHP 8.3

**Primary Dependencies**: Symfony 7.2 LTS, Doctrine ORM 3.6, Twig, Bootstrap, Webpack Encore, Symfony UX (Turbo, Twig Component), Gedmo SoftDeleteable, PHPUnit 12

**Storage**: PostgreSQL 16 (Platform.sh managed)

**Testing**: PHPUnit 12 (`tests/Unit/`, `tests/Integration/`, `tests/Functional/`)

**Target Platform**: Platform.sh (Linux, PHP-FPM)

**Project Type**: Symfony SSR web application

**Performance Goals**: Full dashboard load < 3 s after authentication (SC-001). Fresh Doctrine queries per visit — no application-level cache layer (per spec assumption and clarification).

**Constraints**: Mobile-first responsive layout (FR-021); WCAG 2.1 AA (SC-008); no JavaScript framework additions; no application cache; role check performed on every page render.

**Scale/Scope**: Single-team collaborative encyclopedia, small-to-medium user base.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Assessment |
|-----------|--------|------------|
| I. Complémentarité Stricte | ✅ PASS | Dashboard is a personal collection hub. The forum banner is a navigational link to "La Taverne des Aventuriers", not a competing feature. No general discussion, news publishing, or forum functionality is introduced. |
| II. Architecture Symfony LTS | ✅ PASS | Thin `DashboardController` (HTTP only) → `DashboardService` (all business logic). Doctrine ORM exclusively. Full dependency injection. Platform.sh config files updated in the same commit as infrastructure changes (Platform.sh cron addition). |
| III. Workflow Validation | ✅ PASS | Dashboard is read-only. No content submission on this page. |
| IV. RBAC | ✅ PASS | Route protected with `#[IsGranted('ROLE_USER')]`. Moderation card gated via `Security::isGranted()` inside `DashboardService`, surfaced as `DashboardData::$isModerator`. CSRF not applicable (GET-only page). |
| V. Sécurité et Tests | ✅ PASS | PHPUnit tests required for: `DashboardService` (unit), `ActivityEventListener` (integration), `PurgeActivityEventsCommand` (unit), `DashboardController` (functional). |

**No violations. No entries in Complexity Tracking table.**

## Project Structure

### Documentation (this feature)

```text
specs/018-home-dashboard/
├── plan.md              ← this file
├── research.md          ← Phase 0 output
├── data-model.md        ← Phase 1 output
├── quickstart.md        ← Phase 1 output
├── contracts/
│   └── ui-contract.md   ← Phase 1 output
└── tasks.md             ← Phase 2 output (/speckit-tasks — not yet created)
```

### Source Code Layout

```text
src/
├── Command/
│   └── PurgeActivityEventsCommand.php          [NEW]
├── Controller/
│   └── DashboardController.php                 [NEW — replaces DefaultController::home()]
├── Dto/
│   └── DashboardData.php                       [NEW]
├── Entity/
│   ├── ActivityEvent.php                       [NEW]
│   ├── Enum/
│   │   └── ActivityEventType.php               [NEW]
│   └── User.php                                [MODIFIED — add lastLoginAt, previousLoginAt]
├── Event/
│   ├── BookPublishedEvent.php                  [NEW]
│   ├── BookAddedToWishlistEvent.php            [NEW]
│   ├── ReviewSubmittedEvent.php                [NEW]
│   └── SuggestionModeratedEvent.php            [NEW]
├── EventListener/
│   └── ActivityEventListener.php               [NEW — handles all 4 ActivityEvent types]
├── EventSubscriber/
│   └── AuthenticationEventSubscriber.php       [MODIFIED — rotate login timestamps]
├── Repository/
│   ├── ActivityEventRepository.php             [NEW]
│   ├── BookRepository.php                      [MODIFIED — +countPublished, +findRecentlyPublished]
│   ├── SuggestionRepository.php                [MODIFIED — +countGlobalPending, +countRecentlyValidatedByUser, +countAllByUser]
│   └── UserBookRepository.php                  [MODIFIED — +countOwnedByUser, +countOwnedAddedSince, +countToReadByUser, +countToBuyByUser]
├── Service/
│   └── DashboardService.php                    [NEW]
└── Twig/
    └── Extension/
        └── RatingExtension.php                 [NEW — rating_stars filter]

templates/
└── home/
    └── index.html.twig                         [MODIFIED — full dashboard replacing stub]

migrations/
├── VersionXXXAddUserLoginTimestamps.php        [NEW]
└── VersionXXXCreateActivityEvent.php           [NEW]

.platform.app.yaml                              [MODIFIED — add purge_activity_events cron]

tests/
├── Functional/Controller/
│   └── DashboardControllerTest.php             [NEW]
├── Integration/EventListener/
│   └── ActivityEventListenerTest.php           [NEW]
└── Unit/
    ├── Command/
    │   └── PurgeActivityEventsCommandTest.php  [NEW]
    ├── Service/
    │   └── DashboardServiceTest.php            [NEW]
    └── Twig/
        └── RatingExtensionTest.php             [NEW]
```

**Structure Decision**: Standard Symfony single-project layout. No structural deviation from existing conventions.

## Complexity Tracking

> *No Constitution violations — table empty.*
