# Live Component Contract: NotificationPanelComponent

**Class**: `App\Twig\Components\Notification\NotificationPanelComponent`
**Attribute**: `#[AsLiveComponent]`
**Template**: `templates/components/notification/notification_panel_component.html.twig`

---

## Usage (in Navbar Twig template)

```twig
<twig:Notification:NotificationPanel />
```

No props required. Component fetches the authenticated user internally via `Security`.

---

## Component Interface

### Computed Properties (no `#[LiveProp]` needed — fetched fresh on each render)

| Property | Return | Description |
|----------|--------|-------------|
| `getNotifications()` | `Notification[]` | Latest 20 notifications for current user (all types; `moderation_pending` filtered if not ROLE_MODERATOR) |
| `getUnreadCount()` | `int` | Count of unread notifications |
| `getTodayBoundary()` | `\DateTimeImmutable` | Start of "today" in user's timezone (for Twig grouping) |

### LiveActions

#### `markRead(int $id)`

**Attribute**: `#[LiveAction]` `#[IsGranted('ROLE_USER')]`

Marks a single notification as read. The component re-renders after the action.

**Behavior**:
- Calls `NotificationService::markRead($user, $id)` — no-op if not found or already read
- Emits browser event `notification:panel:redirect` with `{url: targetUrl}` so JS can navigate
- If `targetUrl` is null: no redirect, panel stays open after re-render

#### `markAllRead()`

**Attribute**: `#[LiveAction]` `#[IsGranted('ROLE_USER')]`

Marks all notifications as read. Component re-renders (all unread dots disappear, badge = 0).

**Behavior**:
- Calls `NotificationService::markAllRead($user)`
- No redirect — panel stays open

---

## Template Structure

```twig
{# notification_panel_component.html.twig #}
<div {{ attributes }}>
  {# Bell button with badge (badge uses unread_count Twig global) #}
  {# Panel div.menu-card #}
  <header class="menu-head">
    <span class="ttl">Notifications <span class="count-badge">{{ getUnreadCount() }}</span></span>
    <button data-action="live#action" data-live-action-param="markAllRead">Tout marquer lu</button>
  </header>

  <div class="notif-list" role="list">
    {# Skeleton loader shown while component is loading (UX Live Component built-in loading state) #}
    {# Grouped: Nouvelles · aujourd'hui / Plus anciennes #}
    {% for notification in getNotifications() %}
      {# notif-item with type CSS class, unread class, click triggers markRead(id) + redirect #}
    {% else %}
      {# notif-empty state #}
    {% endfor %}
  </div>

  <footer class="menu-foot">
    <a href="{{ path('notification_history') }}">Voir toutes les notifications →</a>
    <button type="button" data-action="navigate" data-url="{{ path('profile_settings') }}#notifications">Préférences</button>
  </footer>
</div>
```

---

## Notification Type → CSS Class Mapping

| NotificationType | CSS class on `.notif-item` | Icon |
|------------------|---------------------------|------|
| `contribution_validated` | `success` | SVG checkmark |
| `book_activity` (single) | `info illustr` | Collection initials on gradient |
| `book_activity` (batch) | `info` | Same initials |
| `moderation_pending` | `warn` | SVG triangle warning |
| `rank_up` | `success` | SVG star |

---

## Loading & Error States

- **Loading**: Symfony UX Live Component renders a `data-live-loading` skeleton overlay — add `data-live-loading="addAttribute" data-loading="[class~=skeleton]"` on the list element
- **Error**: On Live Component `connect:error` browser event, dispatch a `toast` event with `{message: "...", type: "error"}` via existing toast mechanism; show empty state in panel

---

## Security Notes

- Component calls `$this->getUser()` (via `AbstractController` extension) — unauthenticated users never render this component (navbar only shows bell for `is_granted('ROLE_USER')`)
- `markRead(int $id)` uses `NotificationService` which calls `NotificationRepository::findByIdAndUser()` — cross-user access impossible by design
- `moderation_pending` notifications excluded from `getNotifications()` result if `!$this->isGranted('ROLE_MODERATOR')`
