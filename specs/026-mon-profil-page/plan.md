# Implementation Plan: Mon Profil — Tableau de Bord Utilisateur

**Branch**: `026-mon-profil-page` | **Date**: 2026-06-11 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/026-mon-profil-page/spec.md`

## Summary

Private user dashboard at `/profil` displaying KPIs, list management with per-list visibility toggling, followed authors & collections, settings (pseudo, email double opt-in, avatar upload, region, OAuth unlink, password change), role/permissions panel, and RGPD-compliant soft-delete account deletion with GhostUser reassignment. Requires 5 DB migrations, 4 new services, 2 new entities, and a login event listener.

## Technical Context

**Language/Version**: PHP 8.3

**Primary Dependencies**: Symfony 7.2, Doctrine ORM 3.x, Twig, Bootstrap, Stimulus (Symfony UX)

**Storage**: PostgreSQL (Platform.sh managed)

**Testing**: PHPUnit (`tests/`)

**Target Platform**: Platform.sh (Linux server)

**Project Type**: Symfony web application

**Performance Goals**: Page load < 2s (SC-001), toggle response < 3s (SC-002), unfollow animation < 1s (SC-003)

**Constraints**: CSRF on all mutations; `#[IsGranted]` on all protected routes; no new JS frameworks; Twig only; Bootstrap only

**Scale/Scope**: Single-user profile page; DB ops bounded to one user's data

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Complémentarité Stricte | ✅ PASS | Profile management + list visibility — no forum/news features. Taverne link is external-only (FR-010). |
| II. Architecture Symfony LTS | ✅ PASS | Thin controllers; business logic in `AccountDeletionService`, `LoginStreakService`, `EmailChangeService`; Doctrine ORM; DI throughout. |
| III. Workflow de Validation | ✅ PASS | No new user-submitted editorial content. List visibility and account settings are personal, not editorial. |
| IV. RBAC — Trois Niveaux | ✅ PASS | All mutation routes protected by `#[IsGranted('ROLE_USER')]` + CSRF. GhostUser is protected by guard in `AccountDeletionService`. |
| V. Sécurité & Tests | ✅ PASS | Tests required for `AccountDeletionService`, `LoginStreakService`, `EmailChangeService`, and controller routes. |

**Post-design re-check**: No violations introduced. `UserListVisibility` and `UserContributorSubscription` entities are personal data — not editorial content — so Principle III is unaffected.

## Project Structure

### Documentation (this feature)

```text
specs/026-mon-profil-page/
├── plan.md              ← this file
├── research.md          ← Phase 0 output
├── data-model.md        ← Phase 1 output
├── quickstart.md        ← Phase 1 output
├── contracts/
│   └── routes.md        ← Phase 1 output
└── tasks.md             ← Phase 2 output (speckit-tasks)
```

### Source Code

```text
src/
├── Controller/
│   └── ProfileController.php          (extended — new routes)
├── Entity/
│   ├── UserListVisibility.php         (new)
│   ├── UserContributorSubscription.php (new)
│   └── Enum/
│       └── UserListType.php           (new)
├── Repository/
│   ├── UserListVisibilityRepository.php (new)
│   └── UserContributorSubscriptionRepository.php (new)
├── Service/
│   ├── AccountDeletionService.php     (new)
│   ├── LoginStreakService.php         (new)
│   ├── EmailChangeService.php        (new)
│   └── ProfileKpiService.php         (new)
├── EventListener/
│   └── LoginStreakListener.php        (new)
└── Twig/
    └── Components/Profile/            (new Stimulus components if needed)

templates/
└── profile/
    └── dashboard.html.twig            (new — main profil page)

migrations/
├── VersionXXXX_user_profil_fields.php (M1: loginStreak, lastLoginDate, pendingEmail, emailChangeToken, emailTokenExpiresAt)
├── VersionXXXX_moderation_log_nullable_moderator.php (M2: nullable moderatorId)
├── VersionXXXX_user_list_visibility.php (M3: user_list_visibility table)
├── VersionXXXX_user_contributor_subscription.php (M4: user_contributor_subscription table)
└── VersionXXXX_ghost_user_data.php   (M5: INSERT ghost user row)

tests/
├── Controller/
│   └── ProfileControllerTest.php      (new)
├── Service/
│   ├── AccountDeletionServiceTest.php (new)
│   ├── LoginStreakServiceTest.php      (new)
│   └── EmailChangeServiceTest.php     (new)
└── Entity/
    └── UserListVisibilityTest.php     (new — optional)
```

## Implementation Notes

### Key design decisions (from research.md)

1. **`UserList` = `UserListVisibility` entity** — virtual lists in `UserBook` (boolean flags); visibility tracked in new `UserListVisibility(user, listType, isPublic)` with `UserListType` enum. Upsert on toggle.

2. **Author following = `UserContributorSubscription`** — does NOT exist yet (spec assumption incorrect). `ContributorRepository::applyFilters()` `onlyFollowed` path is scaffolded but unimplemented — must wire it up. **Collection following = `UserCollectionSubscription`** — DOES pre-exist (`src/Entity/UserCollectionSubscription.php`, `src/Repository/UserCollectionSubscriptionRepository.php`). No new entity or migration needed for collections. T043 adds only the missing `findFollowedByUser()` and `findByUserAndCollection()` query methods.

3. **Streak listener** — `LoginSuccessEvent` → `LoginStreakListener` → `LoginStreakService`. Timezone-aware via `User.timezone` (fallback UTC). First login: `loginStreak = 1`, `lastLoginDate = today`.

4. **Anonymisation uniqueness** — `email` and `pseudo` have UNIQUE constraints. Use `"[deleted]-{uuid}"` for both fields to avoid collisions on multiple deletions.

5. **GhostUser UUID** — define `const GHOST_EMAIL = 'ghost@deleted.local'` directly on `AccountDeletionService` or a `GhostUser` value class. Query by email in anonymisation rather than hard-coding UUID.

6. **`ModerationLog::$moderatorId`** — change type to `?string`. Migration drops NOT NULL constraint.

7. **Avatar upload** — `public/uploads/avatars/{userUuid}.{ext}`. Delete old file before saving new one. Server-side MIME validation only (client-side cropping is pre-send JS).

8. **Email change confirmation route** — unauthenticated (token-based). On confirm: swap `email` ← `pendingEmail`, clear token fields, log user out (session invalidation), redirect to login with success flash.

9. **Pagination** — query string `?tab=collection&page=2`. 20 books/page. Full page reload per page (no AJAX).

10. **Profil public visibility** — `GET /profil/{pseudo}` already exists; must filter out lists where `UserListVisibility.isPublic = false` (or missing row = private).

## Complexity Tracking

| Deviation | Justification |
|-----------|---------------|
| Email confirm route `GET /profil/email/confirm/{token}` mutates data without CSRF or `#[IsGranted]` (Constitution IV) | The one-time token IS the authentication and CSRF-equivalent mechanism: 32-byte cryptographically random, 24h TTL, single-use. GET is intentional (email client link-click). Standard email change confirmation pattern — token replaces both session auth and CSRF for this single action. Classified as acceptable pattern deviation, not a violation. |
| KPI business logic in `ProfileKpiService` rather than controller queries (Constitution II reinforcement) | Monthly trend and acceptance rate calculations are non-trivial business logic. Extracted to `ProfileKpiService` (T044) per Constitution II thin-controller mandate. |
