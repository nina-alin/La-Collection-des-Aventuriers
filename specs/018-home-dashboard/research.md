# Research: Dashboard (018-home-dashboard)

## Decision 1 — Login Timestamp Strategy

**Decision**: Add two fields to the `User` entity: `lastLoginAt` and `previousLoginAt`. On each successful login (`LoginSuccessEvent`), `previousLoginAt ← lastLoginAt`, then `lastLoginAt ← now`. The dashboard header uses `previousLoginAt` to count new fiches since the last *prior* session.

**Rationale**: The spec explicitly states that "dernière connexion" refers to the previous session, not the current one. Storing both timestamps on the entity is the simplest persistent solution with no session storage needed. A first-time user has `previousLoginAt = null`, which maps to the "generic welcome message" edge case.

**Alternatives considered**:
- Storing `previousLoginAt` in the session on login — discarded: session is gone if the browser is closed; not durable.
- A single `lastLoginAt` updated on logout — discarded: users may not log out explicitly; OAuth sessions may never trigger logout.

## Decision 2 — ActivityEvent Write Strategy

**Decision**: Write `ActivityEvent` records **synchronously** in Symfony event listeners, directly via the `EntityManagerInterface`. No Messenger bus for this path.

**Rationale**: The spec mandates SSR with fresh DB queries on every page load. Messenger async delivery would create a race: if the user visits the dashboard before the message is processed, the event is missing. Sync write guarantees consistency. Activity events are low-volume (one per user action) and the write path is not performance-critical.

**Alternatives considered**:
- Symfony Messenger async — discarded: race condition risk; delivery delay breaks dashboard freshness guarantee.
- Doctrine lifecycle callbacks (PostPersist / PostUpdate) — discarded: coupling entity to infrastructure; difficult to test and to pass contextual data (actor user).

## Decision 3 — Monthly ActivityEvent Purge

**Decision**: Implement a `PurgeActivityEventsCommand` (Symfony Console Command) triggered by Platform.sh cron configuration in `.platform.app.yaml`.

**Rationale**: The purge is a scheduled infrastructure operation, not a user-triggered one. A Console Command is the idiomatic Symfony mechanism. Platform.sh natively supports cron entries in `.platform.app.yaml`. No Symfony Scheduler package is introduced (would add unnecessary dependency).

**Alternatives considered**:
- Symfony Scheduler component — discarded: adds a dependency and requires a running worker process; cron on Platform.sh is simpler and already in use.
- Doctrine event on `ActivityEvent::createdAt` — discarded: Doctrine lifecycle events are not designed for bulk purge operations.

**Platform.sh cron entry** (to be added to `.platform.app.yaml`):
```yaml
crons:
    purge_activity_events:
        spec: "0 3 1 * *"   # 03:00 UTC on the 1st of each month
        cmd: "php bin/console app:purge-activity-events"
```

## Decision 4 — Half-Star Rating Display

**Decision**: Implement a `RatingExtension` Twig extension with a `rating_stars(float score)` filter that converts a 0–10 score (as stored in `Review::score`) to a 0–5 display scale, rounds to the nearest 0.5, and returns a structured array for the template to render full/half/empty star icons via Bootstrap Icons.

**Rationale**: The spec requires "demi-étoiles" (FR-014). Score is stored as 1–10 integer in `Review`. Average is 0–10 float. Display is 0–5 with 0.5 resolution. A reusable Twig filter keeps templates clean and is testable in isolation.

**Rounding formula**: `round($avg / 2 * 2) / 2` → round to nearest 0.5 on a 0–5 scale.

**Alternatives considered**:
- JavaScript star renderer — discarded: dashboard is fully SSR; no client-side rendering.
- Macro in Twig — discarded: not unit-testable; harder to reuse across templates.

## Decision 5 — Book Count for "PARCOURIR LE WIKI" Card

**Decision**: Add `BookRepository::countPublished(): int` (filters `status = BookStatus::PUBLISHED` and respects the Gedmo SoftDeleteable filter). The existing `countAll()` (`$this->count([])`) does not filter by status and includes PENDING books, so it is not suitable for the wiki card subtitle.

**Rationale**: The "PARCOURIR LE WIKI" subtitle should reflect the count of publicly visible entries, not internal drafts.

**Alternatives considered**:
- Reuse `countAll()` — discarded: includes PENDING and REFUSED books invisible to users.

## Decision 6 — ActivityEvent Listener Dispatch Points

**Decision**: Create 4 new Symfony domain events and dispatch them from existing services:

| Event | Dispatched from | ActivityEvent type |
|-------|----------------|-------------------|
| `ReviewSubmittedEvent` | `ReviewService` after `flush()` | `SOCIAL` |
| `BookPublishedEvent` | `ModerationService` when Book status → PUBLISHED | `CONTRIBUTION` |
| `SuggestionModeratedEvent` | `ModerationService` when suggestion validated or refused | `MODERATION` |
| `BookAddedToWishlistEvent` | `UserBookService` when `isToBuy` set true | `PERSONAL` |

Each event carries the actor `User` and enough context data (book title, slug, badge label) to populate the `ActivityEvent` record without additional DB queries in the listener.

**Rationale**: Following the existing pattern (`ContributionValidatedEvent` → listener). Keeps services decoupled from the ActivityEvent infrastructure. Events are thin value objects; listeners write to `activity_event` table.

**Alternatives considered**:
- Calling `ActivityEventService` directly from existing services — discarded: tighter coupling; harder to disable or extend.
- Doctrine PostPersist on `Review`, `Suggestion` — discarded: Doctrine lifecycle events cannot safely access other repositories or flush; contextual data (actor) not available.

## Decision 7 — DashboardController vs DefaultController

**Decision**: Move the home route `GET /` from `DefaultController` into a dedicated `DashboardController`. The stub routes for `/suggestions` and `/suggestions/nouveau` in `DefaultController` are placeholders from earlier sprints — they are handled by `SuggestionController` and will be removed from `DefaultController`.

**Rationale**: `DefaultController` currently returns a placeholder template. The dashboard requires injecting `DashboardService` and `Security`. A dedicated controller follows Constitution Principle II (thin controllers).

**Alternatives considered**:
- Updating `DefaultController` in-place — discarded: creates mixed responsibilities; `DefaultController` has no semantic identity.
