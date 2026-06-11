# Route Contracts: Feature 026 — Mon Profil Page

## New Routes

### Private Dashboard

| Attribute | Value |
|-----------|-------|
| Method | `GET` |
| Path | `/profil` |
| Name | `profile_dashboard` |
| Auth | `ROLE_USER` (redirect to login if unauthenticated) |
| Template | `profile/dashboard.html.twig` |

### List Visibility Toggle

| Attribute | Value |
|-----------|-------|
| Method | `POST` |
| Path | `/profil/list/{listType}/visibility` |
| Name | `profile_list_visibility` |
| Auth | `ROLE_USER` |
| CSRF token | `list_visibility_{listType}` (in request body) |
| `listType` | one of: `collection`, `to_read`, `to_buy`, `favorites` |
| Success response | `{"isPublic": true\|false}` — HTTP 200 |
| Error (CSRF/not found) | HTTP 400/403 |
| Error (server) | HTTP 500 + JSON error message |

### Unfollow Contributor

| Attribute | Value |
|-----------|-------|
| Method | `POST` |
| Path | `/profil/unfollow/contributor/{id}` |
| Name | `profile_unfollow_contributor` |
| Auth | `ROLE_USER` |
| CSRF token | `unfollow_contributor_{id}` |
| Success response | `{"success": true}` — HTTP 200 |
| Not subscribed | `{"success": true}` — idempotent |
| Error | HTTP 400/403 |

### Unfollow Collection

| Attribute | Value |
|-----------|-------|
| Method | `POST` |
| Path | `/profil/unfollow/collection/{id}` |
| Name | `profile_unfollow_collection` |
| Auth | `ROLE_USER` |
| CSRF token | `unfollow_collection_{id}` |
| Success response | `{"success": true}` — HTTP 200 |
| Error | HTTP 400/403 |

### Update Pseudo

| Attribute | Value |
|-----------|-------|
| Method | `POST` |
| Path | `/profil/settings/pseudo` |
| Name | `profile_update_pseudo` |
| Auth | `ROLE_USER` |
| CSRF token | `update_pseudo` |
| On success | Redirect to `profile_dashboard` with flash success |
| On error | Redirect back with flash error |

### Request Email Change

| Attribute | Value |
|-----------|-------|
| Method | `POST` |
| Path | `/profil/settings/email` |
| Name | `profile_request_email_change` |
| Auth | `ROLE_USER` |
| CSRF token | `email_change` |
| On success | Flash "Un lien de confirmation a été envoyé à {newEmail}" |

### Confirm Email Change

| Attribute | Value |
|-----------|-------|
| Method | `GET` |
| Path | `/profil/email/confirm/{token}` |
| Name | `profile_confirm_email` |
| Auth | none (token is the auth mechanism) |
| On success | Redirect to login with flash success |
| On expired/invalid | Redirect to `profile_dashboard` with flash error |

### Update Avatar

| Attribute | Value |
|-----------|-------|
| Method | `POST` |
| Path | `/profil/settings/avatar` |
| Name | `profile_update_avatar` |
| Auth | `ROLE_USER` |
| CSRF token | `update_avatar` |
| Constraints | Max 2MB, MIME: image/jpeg, image/png, image/webp |
| On success | JSON `{"avatarUrl": "/uploads/avatars/xxx.jpg"}` |
| On error | JSON `{"error": "message"}` — HTTP 422 |

### Update Region

| Attribute | Value |
|-----------|-------|
| Method | `POST` |
| Path | `/profil/settings/region` |
| Name | `profile_update_region` |
| Auth | `ROLE_USER` |
| CSRF token | `update_region` |
| On success | Redirect to `profile_dashboard` with flash |

### Change Password

| Attribute | Value |
|-----------|-------|
| Method | `POST` |
| Path | `/profil/settings/password` |
| Name | `profile_update_password` |
| Auth | `ROLE_USER` |
| CSRF token | `update_password` |
| Guard | Blocked if `user.password === null` → HTTP 400 |
| Body params | `currentPassword`, `newPassword` (min 8 chars) |
| On success | Redirect to `profile_dashboard` with flash "Mot de passe mis à jour" |
| On error | Redirect back with flash error (wrong current password / validation failure) |

### Unlink Google OAuth

| Attribute | Value |
|-----------|-------|
| Method | `POST` |
| Path | `/profil/settings/unlink-google` |
| Name | `profile_unlink_google` |
| Auth | `ROLE_USER` |
| CSRF token | `unlink_google` |
| Guard | Blocked if `user.password === null` → HTTP 400 with message |
| On success | Flash "Compte Google délié" |

### Delete Account

| Attribute | Value |
|-----------|-------|
| Method | `POST` |
| Path | `/profil/delete-account` |
| Name | `profile_delete_account` |
| Auth | `ROLE_USER` |
| CSRF token | `delete_account` |
| Body param | `confirmation = "SUPPRIMER"` (exact, case-sensitive) |
| Guard | Reject if token invalid OR confirmation mismatch |
| Guard | Reject if user is GhostUser |
| On success | Invalidate session → redirect to `/` with flash |
| On error | Redirect to `profile_dashboard` with flash error |

## Unchanged Routes

- `GET /profil/{pseudo}` → `profile_public` (unchanged, existing)
- `GET /profile/settings` → `profile_settings` (existing notification settings, unchanged)
