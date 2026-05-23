# Data Model: Inscription et Authentification (Classique + Google OAuth2)

**Phase 1 — Entity Design**
**Feature**: `002-user-auth-oauth2` | **Date**: 2026-05-23

---

## Entities

### User

**Table**: `"user"` (quoted — `user` is a reserved word in PostgreSQL)
**Primary key**: UUID v4 (`id`)

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `uuid` | PK, NOT NULL | `Uuid::v4()` generated on `__construct()` |
| `email` | `varchar(180)` | UNIQUE, NOT NULL | HTML5 email validation; lowercased before persist |
| `pseudo` | `varchar(30)` | UNIQUE, NOT NULL | `^[a-zA-Z0-9_]{3,30}$`; checked for availability before creation |
| `password` | `varchar(255)` | NULLABLE | bcrypt cost ≥ 13; NULL for Google-only accounts (FR-014) |
| `roles` | `json` | NOT NULL, default `[]` | Symfony roles array; `getRoles()` always appends `ROLE_USER` (FR-004) |
| `google_id` | `varchar(255)` | UNIQUE INDEX, NULLABLE | Google `sub` claim; NULL for classic-only accounts |
| `display_name` | `varchar(255)` | NULLABLE | Google display name; NULL for classic-only accounts |
| `avatar_url` | `varchar(2048)` | NULLABLE | Google avatar URL; NULL for classic-only accounts |
| `created_at` | `datetime_immutable` | NOT NULL | UTC; set on `__construct()` |

**PHP interfaces**:
- `Symfony\Component\Security\Core\User\UserInterface`
- `Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface`

**Design note**: Google identity fields are embedded directly in `User` rather than a separate entity. This avoids a JOIN on every authentication check. The spec's "Identité Google" key entity maps to these nullable columns.

**Validation constraints** (Symfony Validator):

| Field | Constraints |
|-------|-------------|
| `email` | `NotBlank`, `Email(mode:"html5")` |
| `pseudo` | `NotBlank`, `Regex(pattern:"/^[a-zA-Z0-9_]{3,30}$/")`, `Length(min:3, max:30)` |
| `password` (plain, pre-hash) | `NotBlank` (when not null), `Length(min:8)` |
| `rgpdConsent` (form-only, not persisted) | `IsTrue(message:"Vous devez accepter les conditions pour créer un compte.")` |

**State transitions**:

| Trigger | Resulting state |
|---------|----------------|
| Classic registration (FR-001) | `email + pseudo + hashedPassword + roles=[''] + google_id=null` |
| Google registration (FR-012) | `email + pseudo + password=null + roles=[''] + google_id + display_name + avatar_url` |
| Account fusion (FR-015) | Existing Google account: `password` column updated; Google fields preserved |
| Google login to existing classic account (FR-011) | No mutation — user connected as-is |

---

## Infrastructure Tables (auto-managed)

| Table | Manager | Purpose |
|-------|---------|---------|
| `doctrine_migration_versions` | Doctrine Migrations | Migration tracking |
| `cache_items` | Symfony Cache DBAL adapter | Rate limiter state + brute-force counters |

---

## Migration

Single migration creates:
1. `"user"` table — all columns, UNIQUE indices on `email`, `pseudo`, `google_id`
2. `cache_items` table — created via `DoctrineDbalAdapter::configureSchema()` or explicit SQL

---

## Relationships

No entity relationships in this feature. Future features (Collection, Wishlist) will FK-reference `"user".id`.

---

## Pseudo Generation (FR-018)

When creating a Google account:
1. Derive base pseudo: `name` from Google userinfo, sanitized to `^[a-zA-Z0-9_]{1,30}$` (truncate, replace invalid chars with `_`). If empty after sanitization, use local part of email.
2. Check uniqueness in DB.
3. If taken: append `_2`, `_3`, … (no limit) until unique.
4. Trim to 30 chars before suffix; shorten base to fit if suffix would exceed 30 chars.
