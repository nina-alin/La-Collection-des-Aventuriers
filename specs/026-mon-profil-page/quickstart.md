# Quickstart: Feature 026 — Mon Profil Page

## Prerequisites

- Feature branch `026-mon-profil-page` checked out
- Local database running, migrations up to date

## Run Migrations

```bash
# Schema migrations (M1–M4)
php bin/console doctrine:migrations:migrate --no-interaction

# Verify GhostUser inserted (M5)
php bin/console doctrine:query:sql "SELECT email, pseudo FROM \"user\" WHERE email = 'ghost@deleted.local'"
```

## Test the Dashboard

1. Log in as any `ROLE_USER`
2. Navigate to `/profil` → private dashboard
3. Verify KPIs, list tabs, and sections render

## Test List Visibility Toggle

```bash
# CSRF token required — use browser UI
# Toggle "Ma Collection" to public → check /profil/{pseudo} shows collection
```

## Test Login Streak

```bash
# Force streak test:
php bin/console doctrine:query:sql "UPDATE \"user\" SET last_login_date = CURRENT_DATE - INTERVAL '1 day', login_streak = 5 WHERE email = 'your@test.com'"
# Then log in → streak should increment to 6
```

## Test Account Deletion

1. Create a test user with validated suggestions
2. Log in, navigate to Zone de Danger
3. Type "SUPPRIMER" in the modal, confirm
4. Verify: session ended, redirected to `/`, user row anonymised in DB
5. Verify: suggestions now owned by `ghost@deleted.local` user

## Run Tests

```bash
php bin/phpunit tests/Controller/ProfileControllerTest.php
php bin/phpunit tests/Service/AccountDeletionServiceTest.php
php bin/phpunit tests/Service/LoginStreakServiceTest.php
php bin/phpunit tests/Service/EmailChangeServiceTest.php
```

## Key Files

| File | Purpose |
|------|---------|
| `src/Controller/ProfileController.php` | All profile routes |
| `src/Service/AccountDeletionService.php` | Soft-delete + anonymisation |
| `src/Service/LoginStreakService.php` | Streak update logic |
| `src/Service/EmailChangeService.php` | Double opt-in email change |
| `src/Entity/UserListVisibility.php` | List visibility per user |
| `src/Entity/UserContributorSubscription.php` | Author following |
| `src/Entity/Enum/UserListType.php` | Enum for list types |
| `src/EventListener/LoginStreakListener.php` | Hook into login success |
| `templates/profile/dashboard.html.twig` | Main profil page |
| `migrations/VersionXXXX_GhostUser.php` | GhostUser data migration |
