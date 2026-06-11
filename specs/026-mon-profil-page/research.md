# Research: Feature 026 — Mon Profil Page

## Decision: "UserList" entity strategy

**Decision**: Create a new `UserListVisibility` entity — `(user_id, list_type, is_public)` — plus a `UserListType` backed enum (`collection`, `to_read`, `to_buy`, `favorites`). One row per list type per user, created lazily on first toggle.

**Rationale**: No `UserList` entity exists. Lists are virtual groupings of `UserBook` rows via four boolean flags (`isOwned`, `isToRead`, `isToBuy`, `isFavorite`). A dedicated visibility entity preserves normalization, allows the toggle controller to `upsert` cleanly, and maps 1-to-1 with the spec's "mettre à jour l'entité `UserList`" intent.

**Alternatives considered**: Add four booleans to `User` (`isCollectionPublic`, …) — simpler but bloats the `User` entity and makes future per-list features harder to extend.

---

## Decision: Author-following entity

**Decision**: Create `UserContributorSubscription` entity — mirrors `UserCollectionSubscription` with `(user_id, contributor_id)` unique pair. Also implement the currently no-op `onlyFollowed` filter in `ContributorRepository::applyFilters()`.

**Rationale**: Investigation shows `ContributorFilterState::$onlyFollowed` exists and is passed to `applyFilters()`, but `applyFilters()` does not use it — the filter is scaffolded but unimplemented, and no `UserContributorSubscription` entity exists. The spec assumption "already implemented" is incorrect; feature 026 must deliver it.

**Alternatives considered**: Reuse `UserCollectionSubscription` with a nullable `contributor_id` — rejected; polymorphic nullable FK is fragile.

---

## Decision: Login streak fields on User

**Decision**: Add `loginStreak` (int, default 0) and `lastLoginDate` (date nullable) to `User` entity. Update streak in a `LoginSuccessEvent` listener (Symfony Security `InteractiveLoginEvent` or `LoginSuccessEvent`). Existing `lastLoginAt`/`previousLoginAt` fields are NOT the same — they track timestamps, not dates for streak purposes.

**Rationale**: Spec clarification confirmed these fields don't exist. Streak logic: use `User.timezone` (fallback UTC); if `lastLoginDate` = yesterday → `loginStreak++`; if `lastLoginDate` < yesterday → reset to 1; if `lastLoginDate` = today → no change; if `lastLoginDate` = null → initialize to 1.

**Alternatives considered**: Derive streak from `ActivityEvent` table — rejected; no daily-login events recorded; would require historical backfill with no data.

---

## Decision: Email change token fields on User

**Decision**: Add `pendingEmail` (string 180 nullable), `emailChangeToken` (string 64 nullable), `emailTokenExpiresAt` (datetime nullable) to `User`. Token generation: `bin2hex(random_bytes(32))`. Expiry: 24h. Confirmation route: `GET /profil/email/confirm/{token}`.

**Rationale**: Spec requires double opt-in for email change. No existing token infrastructure for email changes (the existing `EmailVerificationToken` entity covers registration only — separate table, different concern).

**Alternatives considered**: Reuse `EmailVerificationToken` entity — rejected; different TTL, different purpose, different UX flow.

---

## Decision: Account deletion & ModerationLog

**Decision**: Make `ModerationLog::$moderatorId` nullable (currently typed `string`). Use `null` for self-initiated deletions per FR-016. The FK-free string column makes this a one-line type change + migration.

**Rationale**: `ModerationLog` uses a plain string `moderatorId` (not a Doctrine FK), so nullable string is safe. FR-016 explicitly requires `moderator_id = null` for auto-initiated deletions.

**Alternatives considered**: Use sentinel value `"SELF"` — rejected; null is semantically correct and the spec says null explicitly.

---

## Decision: GhostUser provisioning

**Decision**: A dedicated Doctrine migration (separate from the schema migration) inserts the GhostUser row via raw SQL with a fixed UUID constant. The UUID is defined as a PHP constant `GhostUser::GHOST_UUID` in the entity for use in anonymisation code.

**Rationale**: Spec requires "migration Doctrine dédiée". Using a fixed UUID avoids SELECT-then-reference logic in the anonymisation service.

**Alternatives considered**: Data fixtures — rejected; fixtures are for test environments, not production data provisioning.

---

## Decision: `/profil` route (private dashboard)

**Decision**: Add `GET /profil` route to `ProfileController`. This is distinct from the existing `/profil/{pseudo}` public route. Authentication guard via `#[IsGranted('ROLE_USER')]`; unauthenticated users redirected to login (Symfony default firewall behavior).

**Rationale**: Existing `ProfileController` only has `/profile/settings` and `/profil/{pseudo}`. The private dashboard is a new page.

---

## Decision: Avatar upload handling

**Decision**: Use a Symfony Form with `FileType` field. Server-side validation: MIME type check (`image/jpeg`, `image/png`, `image/webp`), max size 2MB. Store in `public/uploads/avatars/{uuid}.{ext}`. Delete previous file on update. Cropping is client-side only (JavaScript canvas before form submit — not validated server-side beyond aspect ratio).

**Rationale**: Spec: "recadrage carré obligatoire côté client avant envoi", local filesystem, `public/uploads/avatars/`.

---

## Decision: Unfollow action (authors & collections)

**Decision**: Two `POST` routes with CSRF protection — `POST /profil/unfollow/contributor/{id}` and `POST /profil/unfollow/collection/{id}` — returning a JSON `{success: true}` response. Stimulus controller handles the animation and toast client-side.

**Rationale**: Spec FR-008: "disparition dynamique (animation + toast) sans rechargement". AJAX POST with CSRF is the Symfony-idiomatic approach for this. No Turbo Streams needed given the design uses direct JS animation.

---

## Decision: List visibility toggle endpoint

**Decision**: `POST /profil/list/{listType}/visibility` with CSRF token in request body. Controller upserts `UserListVisibility`. Returns JSON `{isPublic: bool}` on success.

**Rationale**: Spec FR-005: "Controller Symfony direct — pas d'endpoint API REST". Uses Symfony's `JsonResponse`, but remains a controller action (not API Platform). Rollback on error: JS reverts toggle state from response.

---

## Decision: Pagination for list tabs

**Decision**: Server-side pagination via query string `?tab=collection&page=2`. Each tab is a full page reload (no AJAX pagination). 20 books/page, prev/next links.

**Rationale**: Spec FR-007: "20 par page avec navigation prev/next". Design uses tabs — AJAX pagination would require Turbo Frames or Stimulus complexity not scoped here.

---

## Unresolved items at Phase 0 start → all resolved

None.
