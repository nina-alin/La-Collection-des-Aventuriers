# HTTP Route Contracts: RBAC — Roles & Permissions

**Branch**: `004-rbac-roles-permissions` | **Phase**: 1 | **Date**: 2026-05-24

All data-mutating routes MUST include a valid CSRF token and MUST be annotated with `#[IsGranted]` (Constitution IV).

---

## Moderation Routes (ROLE_MODERATOR minimum)

### GET /moderation

**Access**: `ROLE_MODERATOR` (includes `ROLE_ADMIN` via hierarchy)
**CSRF**: Not applicable (read-only)

**Response (200 OK)**:
```
Twig view listing all PENDING WorkEntry and CorrectionProposal records.
Each item shows: title/type, author pseudo, creation date, action links.
```

---

### POST /moderation/work-entry/{id}/approve

**Access**: `ROLE_MODERATOR`
**CSRF**: Required (`_csrf_token`, token ID: `moderate_{id}`)

**Request body** (form): `_csrf_token`

**Response (302 Redirect → /moderation)**:
- Success: flash `success` → "Entrée approuvée."
- Not found: flash `error` → "Entrée introuvable."
- Invalid state (not PENDING): flash `error` → "Cette entrée ne peut pas être approuvée dans son état actuel."

**Side effects**:
- WorkEntry status → `PUBLISHED`
- ModerationLog entry created (actionType: `APPROVED`)

---

### POST /moderation/work-entry/{id}/reject

**Access**: `ROLE_MODERATOR`
**CSRF**: Required (`_csrf_token`, token ID: `moderate_{id}`)

**Request body** (form): `_csrf_token`, optional `reason` (text)

**Response (302 Redirect → /moderation)**:
- Success: flash `success` → "Entrée rejetée."
- Not found / invalid state: flash `error` (same as above)

**Side effects**:
- WorkEntry status → `REJECTED`
- ModerationLog entry created (actionType: `REJECTED`, `reason` nullable)

---

### POST /moderation/work-entry/{id}/edit

**Access**: `ROLE_MODERATOR`
**CSRF**: Required (`_csrf_token`, token ID: `moderate_{id}`)

**Request body** (form): `_csrf_token`, `title` (string, required)

**Response (302 Redirect → /moderation)**:
- Success: flash `success` → "Entrée modifiée."
- Not found / not PENDING: flash `error`

**Side effects**:
- WorkEntry fields updated (status stays PENDING)
- ModerationLog entry created (actionType: `MODIFIED`)

---

### POST /moderation/correction-proposal/{id}/approve

Same contract as WorkEntry approve, replacing entity type.

**Side effects**:
- CorrectionProposal status → `PUBLISHED`
- ModerationLog entry (actionType: `APPROVED`, targetEntityType: `CorrectionProposal`)

---

### POST /moderation/correction-proposal/{id}/reject

Same contract as WorkEntry reject, replacing entity type.

**Side effects**:
- CorrectionProposal status → `REJECTED`
- ModerationLog entry (actionType: `REJECTED`, targetEntityType: `CorrectionProposal`)

---

## Admin Routes (ROLE_ADMIN only)

### GET /admin/users

**Access**: `ROLE_ADMIN`
**CSRF**: Not applicable (read-only)

**Response (200 OK)**:
```
Twig view listing all non-deleted user accounts.
Each row: display name, email, role badge, status badge, action buttons.
```

---

### POST /admin/users/{id}/role

**Access**: `ROLE_ADMIN`
**CSRF**: Required (`_csrf_token`, token ID: `admin_user_{id}`)

**Request body** (form): `_csrf_token`, `role` (one of: `ROLE_USER`, `ROLE_MODERATOR`, `ROLE_ADMIN`)

**Response (302 Redirect → /admin/users)**:
- Success: flash `success` → "Rôle mis à jour."
- Self-action (FR-014): flash `error` → "Vous ne pouvez pas modifier votre propre rôle."
- Last-admin guard (FR-012/FR-015): flash `error` → "Cette action laisserait la plateforme sans administrateur actif." or "...sans modérateur actif."
- Invalid role: flash `error` → "Rôle invalide."

**Side effects**:
- User.roles array replaced with `["{role}"]`

---

### POST /admin/users/{id}/ban

**Access**: `ROLE_ADMIN`
**CSRF**: Required (`_csrf_token`, token ID: `admin_user_{id}`)

**Request body** (form): `_csrf_token`

**Response (302 Redirect → /admin/users)**:
- Success: flash `success` → "Compte suspendu."
- Self-action (FR-014): flash `error` → "Vous ne pouvez pas suspendre votre propre compte."
- Last-admin guard (FR-012): flash `error` → "Cette action laisserait la plateforme sans administrateur actif."
- Already banned: no-op or flash `info`

**Side effects**:
- User.status → `banned`

---

### POST /admin/users/{id}/delete

**Access**: `ROLE_ADMIN`
**CSRF**: Required (`_csrf_token`, token ID: `admin_user_{id}`)

**Request body** (form): `_csrf_token`

**Response (302 Redirect → /admin/users)**:
- Success: flash `success` → "Compte supprimé."
- Self-action (FR-014): flash `error` → "Vous ne pouvez pas supprimer votre propre compte."
- Last-admin guard (FR-012): flash `error` → "Cette action laisserait la plateforme sans administrateur actif."

**Side effects**:
- User.email → `[deleted]`
- User.displayName → `[deleted]`
- User.deletedAt → now
- WorkEntry.author → NULL (bulk UPDATE for all authored entries)
- CorrectionProposal.author → NULL (bulk UPDATE for all authored proposals)

---

### GET /admin/settings

**Access**: `ROLE_ADMIN`
**CSRF**: Not applicable (read-only stub)

**Response (200 OK)**:
```json
{"message": "Settings UI coming soon"}
```

Content-Type: `application/json`

---

## Navigation Rendering Contract

Navbar renders links conditionally based on `is_granted()` Twig function:

| Link | Condition |
|------|-----------|
| Personal collection links | `is_granted('ROLE_USER')` (all authenticated users) |
| Moderation dashboard | `is_granted('ROLE_MODERATOR')` |
| Administration links | `is_granted('ROLE_ADMIN')` |

No additional route protection is needed at the Twig level — route access_control in `security.yaml` is the authoritative enforcement layer.

---

## Security Access Control Summary

Additions to `security.yaml` `access_control`:

```yaml
- { path: ^/moderation, roles: ROLE_MODERATOR }
- { path: ^/admin,      roles: ROLE_ADMIN }
```

These rules are inserted before the existing catch-all `IS_AUTHENTICATED_REMEMBERED` rule.

---

## CSRF Token Strategy

All POST routes use Symfony's built-in CSRF protection via form `_token` field. Token ID convention: `moderate_{entityId}` for moderation routes, `admin_user_{userId}` for admin routes.

Validation: `$this->isCsrfTokenValid('moderate_'.$id, $token)` in each controller action.
