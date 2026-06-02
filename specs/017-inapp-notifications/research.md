# Research: Système de Notifications In-App

## Decision 1: Symfony Messenger Transport

**Decision**: Use `doctrine` transport (Doctrine DBAL on existing PostgreSQL)

**Rationale**: No new Platform.sh managed service needed. `doctrine` transport stores messages in a `messenger_messages` table. Fits the project constraint of keeping all persistence in the existing DB. Simpler operational profile than Redis transport for a low-to-medium throughput notification system.

**Alternatives considered**:
- Redis transport: would require adding Redis to `.platform/services.yaml` and `.platform.app.yaml` — additional managed service cost and complexity
- `sync` transport (in-place): would block the request that dispatches the message, violating the decoupled async contract from the spec

**Platform.sh worker config** (`.platform.app.yaml`):
```yaml
workers:
  messenger:
    commands:
      start: php bin/console messenger:consume async --memory-limit=128M --time-limit=3600
```

**Messenger config** (`config/packages/messenger.yaml`):
```yaml
framework:
  messenger:
    transports:
      async:
        dsn: '%env(DATABASE_URL)%'
        options:
          use_notify: true
          check_delayed_interval: 60000
    routing:
      'App\Messenger\Message\NotificationMessage': async
```

---

## Decision 2: Collection Following Mechanism

**Decision**: Create new `UserCollectionSubscription` entity (ManyToMany junction: User ↔ Collection)

**Rationale**: No collection-following entity exists in the current codebase. `UserBook` tracks user-book relationships at the individual book level; it does not represent a subscription to a collection's activity. The spec assumption "Le système de suivi de collections/livres existant" refers to functionality that needs to be built as part of this feature.

**Scope addition confirmed**: `book_activity` notifications require this entity. Without it, the fan-out fan-out (`BookAddedToCollectionListener`) has no subscriber list to query.

**Dispatch point**: The `BookAddedToCollectionEvent` must be dispatched whenever a `Book` is associated to a `Collection`. Inspection candidate: `CollectionService` or wherever the Book→Collection FK is set. Need to identify the write path during implementation.

**Alternatives considered**:
- ManyToMany on `Collection::$followers` (Doctrine-managed): works but less explicit; explicit junction entity preferred for `createdAt` auditability

---

## Decision 3: User Timezone for "Today" Boundary

**Decision**: Add `timezone` field (varchar 50, nullable, default null) to `User` entity. Interpreted as UTC when null.

**Rationale**: FR-004 requires grouping "Nouvelles · aujourd'hui" vs "Plus anciennes" using the user's timezone. The current `User` entity has no timezone field. The Live Component reads `$user->getTimezone() ?? 'UTC'` to build a `\DateTimeZone` for the comparison.

**Validation**: PHP's `\DateTimeZone` constructor accepts standard IANA timezone identifiers (e.g., `Europe/Paris`). The form field will use a `TimezoneType` Symfony form field with region grouping.

**Alternatives considered**:
- JavaScript client-side grouping: requires client rendering, contradicts the server-side Live Component architecture
- Hardcode UTC: violates FR-004 explicitly

---

## Decision 4: unread_count Injection Strategy

**Decision**: `NotificationExtension` implements `Twig\Extension\GlobalsInterface` (via `getGlobals()`), injected with `Symfony\Bundle\SecurityBundle\Security` and `NotificationRepository`. Returns `['unread_count' => 0]` for unauthenticated users.

**Rationale**: Zero AJAX round-trips (matches spec assumption). Count is available in every Twig template without explicit passing. The TwigExtension calls `$repository->countUnreadForUser($user)` — a single `COUNT` query on the indexed `(user_id, is_read, created_at)` index. Execution is lazy: the `Security` service is a proxy and `getUser()` is cheap.

**Alternatives considered**:
- EventSubscriber on `kernel.response`: less idiomatic for Twig globals; harder to test
- AJAX badge refresh: explicitly rejected by spec (v1 no WebSocket/push)

---

## Decision 5: Live Component Architecture (NotificationPanel)

**Decision**: `NotificationPanelComponent` as `#[AsLiveComponent]`, extending `AbstractController`, with:
- `#[LiveAction] markRead(int $id)`: marks one notification read, re-renders component
- `#[LiveAction] markAllRead()`: marks all read, re-renders component
- Both actions carry `#[IsGranted('ROLE_USER')]`

**Rationale**: Matches spec assumption ("Live Component Symfony UX"). Re-render on each server-round-trip ensures coherence (no client-side state cache between opens). Uses existing `symfony/ux-live-component ^2.36` already in `composer.json`.

**Panel open/close**: Handled by existing JS pattern from `profil.html` design (toggle `aria-expanded` + menu-anchor logic). The Live Component's Twig template is embedded in the navbar via `<twig:Notification:NotificationPanel />`.

**Alternatives considered**:
- Turbo Frame: less seamless for actions; Live Component provides built-in action handling
- Full AJAX fetch: requires custom JS; Live Component is already the project's pattern (see `LibraryActionsComponent`)

---

## Decision 6: Idempotence (sourceId unique constraint)

**Decision**: `source_id` varchar(255) column on `Notification`; DB unique constraint on `(user_id, source_id)`; handler catches `UniqueConstraintViolationException` and swallows it silently (no re-queue).

**Rationale**: Prevents duplicate notifications if a Messenger message is redelivered after a transient failure. `source_id` format: `"{type}:{entityId}"` e.g., `"contribution_validated:018b3c2d-..."`.

**Alternatives considered**:
- Application-level dedup check (SELECT before INSERT): race condition prone; DB constraint is the authoritative guard

---

## Decision 7: Rank-Up Detection

**Decision**: `ContributionValidatedListener` dispatches `NotificationMessage` for `contribution_validated`. After dispatching, it also queries `ContributorLevelService::computeRank($recipient)` and compares with the previous rank (stored as `oldRank` fetched before the event). If rank changed, it also dispatches a `NotificationMessage` for `rank_up`.

**Rationale**: The rank is computed dynamically by `ContributorLevelService::computeRank()` using the count of validated suggestions. There's no stored "current rank" field on `User` to diff against. The listener must fetch old rank before the Messenger message is processed (the Messenger handler runs async, so rank may have already updated by then). The listener fetches old rank synchronously, then dispatches both messages.

**Important**: The `ContributionValidatedEvent` is dispatched from `ModerationService::approve(WorkEntry)`. The recipient is `WorkEntry::getAuthor()`. A `RankUpEvent` is a separate domain event dispatched by the listener, not by `ModerationService` directly — preserving the zero-modification-to-existing-services constraint.

**Alternatives considered**:
- Add a stored `rank` field to `User`: persisting computed state; overkill for v1
- Separate `RankUpEvent` detection in a dedicated service: extra complexity; inline detection in `ContributionValidatedListener` is simpler given the co-location of triggers

---

## Decision 8: Notification Pruning (500-cap)

**Decision**: After successful `$em->flush()` in `NotificationMessageHandler`, run: if `$repo->countForUser($user) > 500`, delete oldest `N` notifications inline within the same transaction.

**Rationale**: Matches spec assumption. Keeps the implementation simple — no separate cleanup job. The "N" to delete is `count - 500` to avoid repeated pruning on every message.

**Alternatives considered**:
- Scheduled cleanup command: more reliable but adds infrastructure complexity; per-insert is simpler for v1

---

## Decision 9: Preference Deletion on Disable (FR-019)

**Decision**: When a user disables a notification type via preferences, `NotificationService::deleteUnreadByType($user, $type)` is called immediately in the same request (synchronous, not via Messenger).

**Rationale**: FR-019 requires immediate deletion of unread notifications of the disabled type. Synchronous is safe because the delete is scoped to the authenticated user's own notifications (no cross-user risk) and the count is bounded (max 500).

**Alternatives considered**:
- Soft-delete / hide in query: simpler but wastes storage and complicates queries
- Async via Messenger: delay violates "supprimées immédiatement"

---

## Decision 10: ModerationPending Recipients

**Decision**: `ModerationPendingListener` fetches all users with `ROLE_MODERATOR` from `UserRepository::findByRole('ROLE_MODERATOR')`, then dispatches one `NotificationMessage` per moderator.

**Rationale**: Spec says `moderation_pending` notifications target moderators. No existing "moderator list" service exists; `UserRepository` already has access to the roles JSON column. The fan-out happens at dispatch time (synchronous query + N async Messenger messages).

**Constraint**: If moderator count grows large, this could be slow. For v1 (small moderation team), this is acceptable. Future: dedicated moderator subscription list.
