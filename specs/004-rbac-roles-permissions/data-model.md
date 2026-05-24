# Data Model: RBAC — Roles & Permissions

**Branch**: `004-rbac-roles-permissions` | **Phase**: 1 | **Date**: 2026-05-24

---

## Entity Overview

| Entity | Change | Purpose |
|--------|--------|---------|
| `User` | Extended | Add `status` and `deletedAt` fields |
| `WorkEntry` | New | Catalogue item with publication lifecycle |
| `CorrectionProposal` | New | User-submitted correction with lifecycle |
| `ModerationLog` | New | Append-only audit trail |

---

## User (Extended)

**Table**: `"user"` (already exists)

### New Fields

| Field | Doctrine type | DB column | Default | Nullable | Notes |
|-------|--------------|-----------|---------|----------|-------|
| `status` | `string(10)` | `status` | `'active'` | No | Values: `active`, `banned` |
| `deletedAt` | `datetime_immutable` | `deleted_at` | — | Yes | NULL = not deleted |

### Existing Fields (unchanged)

`id`, `email`, `pseudo`, `password`, `roles` (JSON), `googleId`, `displayName`, `avatarUrl`, `createdAt`

### Business Rules

- **Status values**: `active` (default on creation) | `banned` (set by admin; blocks new sessions and ongoing requests)
- **Soft-delete**: `deletedAt` set to now; `email` and `displayName` replaced with `[deleted]`; row retained
- **Roles JSON**: always a single-element array — `["ROLE_USER"]`, `["ROLE_MODERATOR"]`, or `["ROLE_ADMIN"]`
- **Role invariant (FR-016)**: `setRoles()` always replaces the full array; never appends

### Validation Rules

- `status` MUST be one of `active` | `banned` (enforced by service, not entity constraint)
- `deletedAt` non-null → `email` and `displayName` MUST equal `[deleted]` (enforced by `UserManagementService`)

### State Transitions

```
active → banned       (admin action, FR-010; blocked if self-action FR-014)
active → deleted      (soft-delete: deleted_at set, FR-011; blocked if self-action FR-014)
banned → active       (future: unban — out of scope for this feature)
```

---

## WorkEntry (New)

**Table**: `work_entry`

| Field | Doctrine type | DB column | Default | Nullable | Notes |
|-------|--------------|-----------|---------|----------|-------|
| `id` | `uuid` | `id` | `Uuid::v4()` | No | Primary key |
| `title` | `string(255)` | `title` | — | No | Entry title |
| `status` | `string(10)` | `status` | `'PENDING'` | No | PENDING / PUBLISHED / REJECTED |
| `author` | `ManyToOne User` | `author_id` | — | Yes | Nullified on user soft-delete |
| `createdAt` | `datetime_immutable` | `created_at` | `new \DateTimeImmutable()` | No | |

### Relationships

- `author` → `User` (ManyToOne, nullable, no cascade, no FK `ON DELETE` — nullified programmatically by `UserManagementService`)

### Business Rules

- **Default status**: `PENDING` always set on creation, regardless of request payload (FR-005)
- **Terminal states**: `PUBLISHED` and `REJECTED` are terminal; no further transitions allowed (spec edge case)
- **Allowed transitions**: `PENDING → PUBLISHED` (moderator approve), `PENDING → REJECTED` (moderator reject)

### Validation Rules

- `status` MUST be one of `PENDING` | `PUBLISHED` | `REJECTED`
- `title` NOT blank

### State Machine

```
PENDING → PUBLISHED   (moderator approval, FR-007)
PENDING → REJECTED    (moderator rejection, FR-007)
PUBLISHED → ∅         (terminal)
REJECTED  → ∅         (terminal)
```

---

## CorrectionProposal (New)

**Table**: `correction_proposal`

| Field | Doctrine type | DB column | Default | Nullable | Notes |
|-------|--------------|-----------|---------|----------|-------|
| `id` | `uuid` | `id` | `Uuid::v4()` | No | Primary key |
| `workEntry` | `ManyToOne WorkEntry` | `work_entry_id` | — | No | Target entry being corrected |
| `proposedContent` | `json` | `proposed_content` | — | No | Correction data blob |
| `status` | `string(10)` | `status` | `'PENDING'` | No | PENDING / PUBLISHED / REJECTED |
| `author` | `ManyToOne User` | `author_id` | — | Yes | Nullified on user soft-delete |
| `createdAt` | `datetime_immutable` | `created_at` | `new \DateTimeImmutable()` | No | |

### Relationships

- `workEntry` → `WorkEntry` (ManyToOne, not nullable, cascade=none)
- `author` → `User` (ManyToOne, nullable, no cascade)

### Business Rules

- **Default status**: `PENDING` always (FR-005)
- **Terminal states**: `PUBLISHED` and `REJECTED` are terminal (spec edge case)
- **Allowed transitions**: mirror WorkEntry

---

## ModerationLog (New)

**Table**: `moderation_log`

| Field | Doctrine type | DB column | Default | Nullable | Notes |
|-------|--------------|-----------|---------|----------|-------|
| `id` | `uuid` | `id` | `Uuid::v4()` | No | Primary key |
| `moderatorId` | `string(36)` | `moderator_id` | — | No | UUID string, no FK — survives moderator deletion |
| `actionType` | `string(10)` | `action_type` | — | No | APPROVED / REJECTED / MODIFIED |
| `targetEntityType` | `string(100)` | `target_entity_type` | — | No | e.g. `WorkEntry`, `CorrectionProposal` |
| `targetEntityId` | `string(36)` | `target_entity_id` | — | No | UUID string, no FK |
| `reason` | `text` | `reason` | — | Yes | Free-text, null when not provided |
| `createdAt` | `datetime_immutable` | `created_at` | `new \DateTimeImmutable()` | No | |

### Relationships

None — intentionally. No FK constraints. Audit log is append-only and must survive referenced entity deletion (FR-007, spec edge cases).

### Business Rules

- **Append-only**: No UPDATE or DELETE ever permitted (enforced by `PreUpdate`/`PreRemove` Doctrine lifecycle callbacks throwing exceptions)
- **Immutable**: `createdAt` set in constructor; no setter exposed
- **MODIFIED entry**: created when moderator edits content of a PENDING entity (regardless of whether a status transition follows)
- **reason** field: nullable; stored as `null` when not provided (never empty string)

### Action Type Semantics

| actionType | Trigger |
|------------|---------|
| `APPROVED` | Moderator transitions PENDING → PUBLISHED |
| `REJECTED` | Moderator transitions PENDING → REJECTED |
| `MODIFIED` | Moderator edits content of a PENDING entity (separate entry from any subsequent APPROVED/REJECTED) |

---

## Role Hierarchy

Symfony `security.yaml` configuration (not a DB entity):

```
ROLE_ADMIN
  └── ROLE_MODERATOR
        └── ROLE_USER
```

Access check on `ROLE_MODERATOR` passes for both `ROLE_MODERATOR` and `ROLE_ADMIN` authenticated users.

---

## Database Migration Plan

Single migration `Version20260524000000.php`:

1. `ALTER TABLE "user" ADD status VARCHAR(10) NOT NULL DEFAULT 'active'`
2. `ALTER TABLE "user" ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL`
3. `COMMENT ON COLUMN "user".deleted_at IS '(DC2Type:datetime_immutable)'`
4. `CREATE TABLE work_entry (...)` with all fields
5. `CREATE TABLE correction_proposal (...)` with all fields
6. `CREATE TABLE moderation_log (...)` with all fields
7. FK constraints: `work_entry.author_id → "user".id` (no ON DELETE — nullify programmatically), `correction_proposal.author_id → "user".id`, `correction_proposal.work_entry_id → work_entry.id`

---

## Repository Additions

### UserRepository

| Method | Signature | Purpose |
|--------|-----------|---------|
| `countActiveAdministrators` | `(): int` | Guard for FR-012 last-admin check |
| `countAccountsWithModerationCapability` | `(): int` | Guard for FR-015 last-moderator check |
| `findActiveByEmail` | `(string): ?User` | Exclude deleted users from UserProvider |

Both count methods use native SQL with PostgreSQL JSONB operator:
```sql
SELECT COUNT(*) FROM "user"
WHERE status = 'active' AND deleted_at IS NULL
  AND roles::jsonb @> '["ROLE_ADMIN"]'::jsonb
```

### WorkEntryRepository

| Method | Signature | Purpose |
|--------|-----------|---------|
| `findPending` | `(): WorkEntry[]` | Moderation dashboard queue |

### CorrectionProposalRepository

| Method | Signature | Purpose |
|--------|-----------|---------|
| `findPending` | `(): CorrectionProposal[]` | Moderation dashboard queue |
