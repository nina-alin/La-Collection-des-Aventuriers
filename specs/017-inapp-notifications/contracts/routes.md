# Route Contracts: Système de Notifications In-App

## GET /notifications

**Name**: `notification_history`
**Controller**: `NotificationController::index()`
**Auth**: `#[IsGranted('ROLE_USER')]`
**Purpose**: Full notification history page with pagination

**Query parameters**:
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| page | int | 1 | Pagination page number (1-based) |

**Response**: Rendered `notification/index.html.twig`

**Template variables**:
| Variable | Type | Description |
|----------|------|-------------|
| notifications | Paginator | Paginated `Notification[]` for current user, all types, reverse-chron |
| currentPage | int | Active page number |
| totalPages | int | Total pages |

**Behavior**:
- Clicking a notification on this page triggers a POST to `/notifications/{id}/read` then redirects to `targetUrl`
- If `targetUrl` is null or target no longer exists, redirect to `/` with info flash

---

## POST /notifications/{id}/read

**Name**: `notification_mark_read`
**Controller**: `NotificationController::markRead(int $id)`
**Auth**: `#[IsGranted('ROLE_USER')]`
**CSRF**: `_csrf_token` field required (`notification_read_{id}`)
**Purpose**: Mark a single notification read and redirect to its target

**Request body** (form-encoded):
| Field | Type | Required |
|-------|------|----------|
| _csrf_token | string | yes |
| redirect_to | string | no — overrides `targetUrl` if provided |

**Response**: Redirect to `targetUrl` (or `/` if none)

**Error handling**:
- 403 on invalid CSRF
- 404 if notification not found for authenticated user (repository filters by user)
- If `targetUrl` is null: redirect to `/` with info flash "Cette notification n'a plus de cible."

---

## POST /notifications/read-all

**Name**: `notification_mark_all_read`
**Controller**: `NotificationController::markAllRead()`
**Auth**: `#[IsGranted('ROLE_USER')]`
**CSRF**: `_csrf_token` field required (`notifications_read_all`)
**Purpose**: Mark all notifications read (fallback non-JS path; normally handled by LiveAction)

**Response**: Redirect to `referer` or `/notifications`

---

## GET /profile/settings#notifications (existing profile page)

**No new route** — notification preferences section is a partial injected into the existing profile settings page. The form posts to:

## POST /profile/settings/notifications

**Name**: `profile_notification_preferences`
**Controller**: `ProfileController::saveNotificationPreferences()` (existing controller, new method)
**Auth**: `#[IsGranted('ROLE_USER')]`
**CSRF**: `_csrf_token` field required
**Purpose**: Save notification type preferences; delete unread of disabled types (FR-019)

**Request body** (form-encoded):
| Field | Type | Notes |
|-------|------|-------|
| contribution_validated | bool | checkbox presence = enabled |
| book_activity | bool | |
| moderation_pending | bool | only relevant for ROLE_MODERATOR |
| rank_up | bool | |
| _csrf_token | string | |

**Response**: Redirect to profile settings with success flash

**Side effect**: For each type toggled OFF, calls `NotificationService::deleteUnreadByType($user, $type)` synchronously.
