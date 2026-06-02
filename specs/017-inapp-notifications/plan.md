# Implementation Plan: Système de Notifications In-App

**Branch**: `017-inapp-notifications` | **Date**: 2026-06-02 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/017-inapp-notifications/spec.md`

## Summary

Add a complete in-app notification system: badge on navbar bell icon (server-rendered global via TwigExtension), dropdown panel as a Symfony UX Live Component, a `/notifications` history page, and user preference settings. Notifications are created asynchronously via Symfony Messenger (Doctrine transport) triggered by domain events dispatched from existing service injection points. Four types: `contribution_validated`, `book_activity`, `moderation_pending`, `rank_up`.

## Technical Context

**Language/Version**: PHP 8.2+ (platform targets PHP 8.3 per `.platform.app.yaml`)

**Primary Dependencies**:
- Symfony 7.2 (framework-bundle, security-bundle, twig-bundle, form, validator)
- `symfony/ux-live-component ^2.36` (Live Component NotificationPanel) — already installed
- `symfony/messenger 7.2.*` — **NOT YET INSTALLED** (must be added in this feature)
- `doctrine/orm ^3.6` + `doctrine/doctrine-bundle ^2.18` — already installed
- Symfony EventDispatcher (included via framework-bundle) — already available

**Storage**: PostgreSQL (Platform.sh managed service `database:postgresql`) — no new service needed; Messenger uses `doctrine` transport on the existing DB

**Testing**: PHPUnit 12.5 (already in require-dev)

**Target Platform**: Platform.sh (PHP 8.3 worker + web processes)

**Project Type**: Symfony web application (server-rendered Twig + Symfony UX)

**Performance Goals**:
- `unread_count` badge: ≤200ms perceived render (SC-001) — satisfied by TwigExtension global, no AJAX round-trip
- Mark-read action: ≤300ms (SC-003) — LiveAction on Live Component

**Constraints**:
- No WebSocket / push for v1 — badge reflects state at page load
- Messenger async worker must run on Platform.sh (`.platform.app.yaml` `workers` section)
- All mutating routes: CSRF + `#[IsGranted]`

**Scale/Scope**: Per-user cap of 500 notifications; panel shows 20 most recent; history page paginated

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Complémentarité Stricte | ✅ PASS | Notifications are internal user alerts about personal activity — no forum, no news publishing |
| II. Architecture Symfony LTS | ✅ PASS (with infra note) | Controllers thin; business logic in `NotificationService`; Doctrine ORM exclusively; DI throughout. **Infra addition**: `symfony/messenger` + Doctrine transport worker → `.platform.app.yaml` `workers` section must be updated in same commit |
| III. Workflow de Validation | ✅ PASS | Notifications are system-generated events, not user-submitted editorial content; no PENDING workflow needed |
| IV. RBAC | ✅ PASS | `ROLE_USER` for all notification actions; `moderation_pending` filtered to `ROLE_MODERATOR`; all `#[LiveAction]` methods carry `#[IsGranted('ROLE_USER')]`; preference routes carry `#[IsGranted('ROLE_USER')]` |
| V. Tests | ✅ PASS | PHPUnit tests required for: `Notification` entity, `NotificationPreference` entity, `NotificationMessageHandler`, `NotificationRepository`, event listeners, preference filtering |

**Constitution Check Post-Design**: See bottom of Phase 1 section.

## Project Structure

### Documentation (this feature)

```text
specs/017-inapp-notifications/
├── plan.md              ← this file
├── research.md          ← Phase 0 output
├── data-model.md        ← Phase 1 output
├── quickstart.md        ← Phase 1 output
├── contracts/           ← Phase 1 output
│   ├── routes.md
│   └── live-component.md
└── tasks.md             ← Phase 2 output (/speckit-tasks)
```

### Source Code

```text
src/
├── Controller/
│   └── NotificationController.php           # GET /notifications (history + preferences page)
├── Entity/
│   ├── Enum/
│   │   └── NotificationType.php             # Backed enum: CONTRIBUTION_VALIDATED, BOOK_ACTIVITY, MODERATION_PENDING, RANK_UP
│   ├── Notification.php                     # New entity
│   ├── NotificationPreference.php           # New entity, OneToOne with User
│   ├── UserCollectionSubscription.php       # New entity, User ↔ Collection junction
│   └── User.php                             # +timezone field (nullable varchar 50)
├── Event/
│   ├── ContributionValidatedEvent.php
│   ├── BookAddedToCollectionEvent.php
│   ├── ModerationPendingEvent.php
│   └── RankUpEvent.php
├── EventListener/
│   ├── ContributionValidatedListener.php    # dispatch NotificationMessage (+ check rank-up)
│   ├── BookAddedToCollectionListener.php    # fan-out: one NotificationMessage per subscriber
│   ├── ModerationPendingListener.php        # dispatch NotificationMessage to all ROLE_MODERATOR
│   └── RankUpListener.php                   # dispatch NotificationMessage
├── Messenger/
│   ├── Message/
│   │   └── NotificationMessage.php          # DTO: userId, type, message, targetUrl, sourceId
│   └── Handler/
│       └── NotificationMessageHandler.php   # idempotent insert, prune at 500
├── Repository/
│   ├── NotificationRepository.php
│   ├── NotificationPreferenceRepository.php
│   └── UserCollectionSubscriptionRepository.php
├── Service/
│   └── NotificationService.php             # markRead, markAllRead, getUnreadCount, deleteByType
├── Twig/
│   ├── Components/
│   │   └── Notification/
│   │       └── NotificationPanelComponent.php  # AsLiveComponent, LiveAction markRead/markAllRead
│   └── Extension/
│       └── NotificationExtension.php           # getGlobals() injects unread_count

templates/
├── components/
│   └── notification/
│       └── notification_panel_component.html.twig
├── notification/
│   └── index.html.twig                         # /notifications history page
└── profile/
    └── _notification_preferences.html.twig     # partial for settings

config/packages/
└── messenger.yaml                              # new: async transport + routing

migrations/
└── VersionXXX_notifications.php               # Notification + NotificationPreference + UserCollectionSubscription + User.timezone

.platform.app.yaml                             # ADD workers: messenger-consume section
```

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No constitution violations. The infrastructure addition of Symfony Messenger worker is required by the async dispatch design (spec assumption, clarified 2026-06-02). The existing PostgreSQL database is reused as Doctrine transport — no new managed Platform.sh service.
