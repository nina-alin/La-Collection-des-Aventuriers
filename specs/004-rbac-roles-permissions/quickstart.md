# Quickstart: RBAC — Roles & Permissions

**Branch**: `004-rbac-roles-permissions` | **Date**: 2026-05-24

This guide covers how to exercise the RBAC feature locally after implementation.

---

## Prerequisites

- Local Docker stack running (`docker compose up -d`)
- Database migrated (`docker compose exec php bin/console doctrine:migrations:migrate --no-interaction`)
- Test user accounts created (see below)

---

## 1. Create Test Accounts

Run the fixture or create accounts manually via the registration flow, then promote via the console or admin UI.

### Via Symfony console (after migration)

```bash
# Promote a user to moderator (replace email as needed)
docker compose exec php bin/console app:user:role user@example.com ROLE_MODERATOR

# Promote a user to admin
docker compose exec php bin/console app:user:role admin@example.com ROLE_ADMIN
```

> **Note**: The `app:user:role` console command is out of scope for this feature. For testing, manually update the `roles` column in the database or use the admin UI once implemented.

### Direct SQL (for local testing)

```sql
-- Promote to moderator
UPDATE "user" SET roles = '["ROLE_MODERATOR"]' WHERE email = 'moderator@example.com';

-- Promote to admin
UPDATE "user" SET roles = '["ROLE_ADMIN"]' WHERE email = 'admin@example.com';
```

---

## 2. Test Role-Based Access (User Story 1)

| Scenario | Steps | Expected |
|----------|-------|----------|
| ROLE_USER blocked | Log in as ROLE_USER, navigate to `/moderation` | HTTP 403 |
| ROLE_MODERATOR allowed | Log in as ROLE_MODERATOR, navigate to `/moderation` | HTTP 200 |
| ROLE_ADMIN allowed | Log in as ROLE_ADMIN, navigate to `/admin/users` | HTTP 200 |
| Unauthenticated redirect | Log out, navigate to `/moderation` | Redirect to `/connexion` |

---

## 3. Test Conditional Navigation (User Story 2)

Log in with each role type and inspect the navigation bar:

| Role | Expected nav items |
|------|--------------------|
| ROLE_USER | Personal collection links only; no moderation link |
| ROLE_MODERATOR | Collection links + "Modération" link |
| ROLE_ADMIN | Collection links + "Modération" link + "Administration" links |

---

## 4. Test PENDING Enforcement (User Story 3)

1. Log in as ROLE_USER
2. Submit a new WorkEntry (via the submission form)
3. Check the database: `SELECT status FROM work_entry WHERE ...` → must be `PENDING`
4. Attempt with `status=PUBLISHED` in the payload → must still be stored as `PENDING`

---

## 5. Test Moderation Dashboard (User Story 4)

1. Log in as ROLE_MODERATOR
2. Navigate to `/moderation`
3. Verify all PENDING entries are listed
4. Approve one entry → status becomes `PUBLISHED`, removed from queue
5. Reject one entry (optionally with a reason) → status becomes `REJECTED`
6. Check `moderation_log` table for entries

```sql
SELECT * FROM moderation_log ORDER BY created_at DESC LIMIT 10;
```

---

## 6. Test Admin User Management (User Story 5)

1. Log in as ROLE_ADMIN
2. Navigate to `/admin/users`
3. Promote a ROLE_USER to ROLE_MODERATOR → verify the promoted user can access `/moderation` without re-login
4. Ban a user → verify they cannot authenticate
5. Test self-action guard: try to ban/demote your own account → must receive error
6. Test last-admin guard: try to demote or delete the only admin account → must receive error

---

## 7. Test Settings Stub (FR-013)

```bash
curl -H "Cookie: <session_cookie>" http://localhost:8000/admin/settings
```

Expected response: `{"message": "Settings UI coming soon"}` with HTTP 200.

Without an authenticated ROLE_ADMIN session: HTTP 302 redirect to login.

---

## 8. Run PHPUnit Tests

```bash
docker compose exec php bin/phpunit
```

All tests must pass. Key test files for this feature:

```
tests/Unit/Entity/UserTest.php                              (extended)
tests/Unit/Entity/WorkEntryTest.php                         (new)
tests/Unit/Entity/CorrectionProposalTest.php               (new)
tests/Unit/Entity/ModerationLogTest.php                     (new)
tests/Unit/Service/ModerationServiceTest.php               (new)
tests/Unit/Service/UserManagementServiceTest.php           (new)
tests/Integration/Controller/ModerationControllerTest.php  (new)
tests/Integration/Controller/AdminControllerTest.php       (new)
tests/Integration/Security/BannedUserTest.php              (new)
```
