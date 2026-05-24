# Research: RBAC — Roles & Permissions

**Branch**: `004-rbac-roles-permissions` | **Phase**: 0 | **Date**: 2026-05-24

All clarifications were resolved in `spec.md` during the clarification session. This document records the architecture decisions that informed Phase 1 design.

---

## Decision 1 — Banned-user enforcement mechanism

**Decision**: KernelEvents::REQUEST subscriber (priority 8, fires after authentication, before access control) performs a DB status check on every authenticated request.

**Rationale**: The spec explicitly requires enforcement on *every authenticated request*, not only at login time. `UserChecker` fires only during authentication (login/refresh), so it cannot block a user whose account is banned after an existing session was established. `KernelEvents::REQUEST` with a subscriber is the Symfony-standard way to intercept every request post-authentication. The existing `AuthenticationEventSubscriber` demonstrates the same pattern is already in use in this project.

**Belt-and-suspenders complement**: A Symfony `UserChecker` will also be added to block banned users at login time (prevents a banned user from opening a new session). The subscriber handles active-session revocation; the UserChecker handles new-session prevention.

**Alternatives considered**: Custom authenticator state check — rejected because it fires only on new authentication events, not on every request.

---

## Decision 2 — Soft-deleted user filtering in UserProvider

**Decision**: Override `UserRepository::loadUserByIdentifier` to throw `UsernameNotFoundException` when `deletedAt IS NOT NULL`.

**Rationale**: Deleted users must appear non-existent to the system, not merely inactive. `loadUserByIdentifier` is the entry point for user resolution; throwing here prevents authentication entirely and avoids the need for downstream guards. Symfony's `UserProvider` also calls `refreshUser` on each request; if the user entity re-loads a deleted user, the same exception path applies (Doctrine entity reload triggers provider re-resolution). The banned-user subscriber handles the "active but locked" case; `loadUserByIdentifier` exclusion handles the "no longer exists" case.

---

## Decision 3 — ModerationLog append-only enforcement

**Decision**: Doctrine `PreUpdate` and `PreRemove` lifecycle callbacks on the `ModerationLog` entity throw an exception if modification or deletion is attempted. Service-level discipline is the primary control; lifecycle events are the safety net.

**Rationale**: Service-level discipline (never calling `remove()` or `flush()` after mutation on a log entity) is sufficient for normal operation. Lifecycle events add an ORM-layer invariant that cannot be bypassed by any code path — including future code, maintenance scripts, or accidental direct entity manipulation.

---

## Decision 4 — Last-admin / last-moderator guard query

**Decision**: Native SQL repository methods using PostgreSQL JSONB operator `@>` for role-based counting.

**Rationale**: Doctrine's DQL `JSON_CONTAINS` is MySQL-specific and unavailable on PostgreSQL without a custom extension. The project targets PostgreSQL 16 exclusively (Platform.sh + local Docker). A `createNativeQuery` with `roles::jsonb @> '["ROLE_ADMIN"]'::jsonb` is idiomatic PostgreSQL, testable, and readable. Two repository methods: `countActiveAdministrators()` and `countAccountsWithModerationCapability()` (counts ROLE_ADMIN + ROLE_MODERATOR).

---

## Decision 5 — Role storage and FR-016 single-role invariant

**Decision**: `setRoles()` always replaces the entire roles array. The stored value is always exactly one non-hierarchical role: `["ROLE_USER"]`, `["ROLE_MODERATOR"]`, or `["ROLE_ADMIN"]`. `getRoles()` appends `ROLE_USER` unconditionally (existing implementation is correct — Symfony requires it).

**Rationale**: Symfony's `role_hierarchy` config expands roles at authorization-check time. The stored JSON array must contain only the *primary* role. FR-016 is enforced by the `UserManagementService` (only calls `setRoles()` with a single-element array) and by the `UserRegistrationService` (already sets `["ROLE_USER"]` on creation).

---

## Decision 6 — Role hierarchy configuration

**Decision**: Declare in `security.yaml`:
```yaml
role_hierarchy:
  ROLE_ADMIN: [ROLE_MODERATOR]
  ROLE_MODERATOR: [ROLE_USER]
```

**Rationale**: Standard Symfony role inheritance. ROLE_ADMIN automatically passes all ROLE_MODERATOR and ROLE_USER access checks without duplicating role assignments on entities. FR-002 requires exactly this hierarchy.

---

## Decision 7 — Content entity author reference on soft-delete

**Decision**: `WorkEntry.author` and `CorrectionProposal.author` are `nullable: true` ManyToOne references to `User`. On soft-delete, `UserManagementService` issues a bulk UPDATE setting `author = NULL` for all content rows authored by the deleted user before anonymizing the user record.

**Rationale**: FR-011 requires nullifying author FK on deletion. Cascade nullify is not natively supported in Doctrine without `ON DELETE SET NULL` DDL constraint — using a service-level bulk UPDATE is explicit and auditable. No content is deleted.

---

## Decision 8 — WorkEntry scope in this feature

**Decision**: `WorkEntry` is introduced as a minimal entity sufficient to drive the moderation workflow: `id`, `title`, `status`, nullable `author`, `createdAt`. Full catalogue fields (series, publisher, year, etc.) are out of scope and will be expanded in a future spec.

**Rationale**: The moderation workflow requires the entity to exist with a lifecycle status, but full book/CYOA catalogue fields are a separate domain feature. Introducing a skeleton here avoids blocking RBAC implementation on an unrelated catalogue spec.

---

## All NEEDS CLARIFICATION Items

None — all were resolved in `spec.md` clarification sessions (2026-05-24). No open unknowns remain.
