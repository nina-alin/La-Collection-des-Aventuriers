# Data Model: Feature 026 — Mon Profil Page

## Existing Entities Modified

### `User` — New fields

| Field | Type | Nullable | Default | Notes |
|-------|------|----------|---------|-------|
| `loginStreak` | int | no | 0 | Days of consecutive login |
| `lastLoginDate` | date | yes | null | Null until first login after migration |
| `pendingEmail` | string(180) | yes | null | New email awaiting confirmation |
| `emailChangeToken` | string(64) | yes | null | `bin2hex(random_bytes(32))` |
| `emailTokenExpiresAt` | datetime | yes | null | 24h TTL |

**Existing fields confirmed present**: `deletedAt` ✓, `timezone` ✓, `googleId` ✓, `avatarUrl` ✓, `password` ✓

**Anonymisation mapping** (FR-013):
- `email` → `"[deleted]"` (unique constraint: must handle collision — append UUID suffix)
- `pseudo` / `displayName` → `"[deleted]"` (unique constraint: same issue — append UUID suffix)
- `avatarUrl` → `null`
- `googleId` → `null`
- `pendingEmail`, `emailChangeToken`, `emailTokenExpiresAt` → `null`
- `password` → `null`
- `deletedAt` → `now()`

**Constraint note**: `email` and `pseudo` have `UNIQUE` constraints. Anonymisation must use `[deleted]-{uuid}` pattern to avoid collisions when multiple accounts are deleted.

---

### `ModerationLog` — Field type change

| Field | Change |
|-------|--------|
| `moderatorId` | `string` → `?string` (nullable) |

**Migration**: `ALTER TABLE moderation_log ALTER COLUMN moderator_id DROP NOT NULL`.

---

## New Entities

### `UserListVisibility`

Tracks public/private visibility for each of a user's 4 virtual lists.

```
Table: user_list_visibility
Unique: (user_id, list_type)
```

| Field | Type | Nullable | Default | Notes |
|-------|------|----------|---------|-------|
| `id` | int (PK, auto) | no | — | |
| `user` | FK → User | no | — | ON DELETE CASCADE |
| `listType` | enum(UserListType) | no | — | `collection`, `to_read`, `to_buy`, `favorites` |
| `isPublic` | bool | no | false | |

**Enum `UserListType`** (`App\Entity\Enum\UserListType`):
- `Collection = 'collection'`
- `ToRead = 'to_read'`
- `ToBuy = 'to_buy'`
- `Favorites = 'favorites'`

**Validation**: `listType` must be one of the four enum values. `user` must be the authenticated user (enforced at controller level).

**State transitions**:
- Missing row → treated as `isPublic = false` (private by default)
- Toggle: upsert — INSERT on conflict UPDATE

---

### `UserContributorSubscription`

Tracks which contributors (authors/illustrators/translators) a user follows.

```
Table: user_contributor_subscription
Unique: (user_id, contributor_id)
```

| Field | Type | Nullable | Default | Notes |
|-------|------|----------|---------|-------|
| `id` | int (PK, auto) | no | — | |
| `user` | FK → User | no | — | ON DELETE CASCADE |
| `contributor` | FK → Contributor | no | — | ON DELETE CASCADE |
| `subscribedAt` | datetime_immutable | no | now() | |

---

### `GhostUser` (data migration — not a separate entity class)

Inserted via a dedicated Doctrine migration as a `User` row with fixed UUID.

| Field | Value |
|-------|-------|
| `id` | Fixed UUID constant: `00000000-0000-0000-0000-000000000000` (or a stable v4) |
| `email` | `ghost@deleted.local` |
| `pseudo` | `ancien-aventurier` |
| `displayName` | `un ancien aventurier` |
| `roles` | `["ROLE_USER"]` |
| `status` | `active` |
| `isEmailVerified` | `true` |
| `deletedAt` | `null` |
| `createdAt` | migration timestamp |

**Protection**: `AccountDeletionService` must explicitly check `user.email !== 'ghost@deleted.local'` before processing any deletion. `GhostUser::GHOST_EMAIL = 'ghost@deleted.local'` constant defined in a `GhostUser` value class or as a constant on `User`.

---

## New Services

### `AccountDeletionService`

Orchestrates soft-delete + anonymisation:
1. Guard: reject if target is GhostUser
2. Reassign `Suggestion` (VALIDATED) → GhostUser author
3. Reassign `CorrectionProposal` (PUBLISHED) → GhostUser author
4. Delete `UserBook` rows (cascade or explicit)
5. Delete `Review` rows
6. Anonymise `User` fields
7. Log to `ModerationLog` with `moderatorId = null`, `action = 'ACCOUNT_DELETED'`
8. Invalidate session

### `LoginStreakService`

Called by `LoginSuccessListener` on each successful authentication:
1. Resolve user's "today" via `User.timezone` (fallback UTC)
2. Compare to `lastLoginDate`
3. Apply streak rule (increment / reset / no-op)
4. Persist if changed

### `EmailChangeService`

Handles double opt-in email change:
1. Generate token, set `pendingEmail` + `emailTokenExpiresAt` on User
2. Send confirmation email to new address
3. Confirmation: validate token, not expired, swap `email` ← `pendingEmail`, clear token fields

---

## Repositories

### `UserListVisibilityRepository`

Key methods:
- `findByUserAndType(User $user, UserListType $type): ?UserListVisibility`
- `findAllByUser(User $user): array` — keyed by list type string

### `UserContributorSubscriptionRepository`

Key methods:
- `findByUserAndContributor(User $user, Contributor $c): ?UserContributorSubscription`
- `findFollowedByUser(User $user): array`

---

## Schema changes summary

| Migration | Type | Description |
|-----------|------|-------------|
| M1 | schema | Add `login_streak`, `last_login_date`, `pending_email`, `email_change_token`, `email_token_expires_at` to `user` |
| M2 | schema | Make `moderation_log.moderator_id` nullable |
| M3 | schema | Create `user_list_visibility` table |
| M4 | schema | Create `user_contributor_subscription` table |
| M5 | data | Insert GhostUser row into `user` table |

All five migrations ship in feature 026. M5 must run after M1 (depends on `user` table structure being complete).
