# Quickstart: Dashboard (018-home-dashboard)

## Prerequisites

- Docker Compose running (`docker compose up -d`)
- Symfony CLI or PHP 8.3 + Composer available
- Database migrations applied
- At least one user account created

## Local Setup

```bash
# 1. Install dependencies (if not already done)
composer install

# 2. Apply migrations (includes User login fields + activity_event table)
php bin/console doctrine:migrations:migrate --no-interaction

# 3. Clear cache
php bin/console cache:clear

# 4. Start dev server
symfony server:start
```

## Smoke Test — Dashboard Sections

Navigate to `http://localhost:8000/` as an authenticated user.

**Header**: Verify date is today's date in "JOUR DD MOIS" format, greeting shows your pseudo in uppercase, subtitle shows new books count since last login (or welcome message on first login).

**KPI Blocks**: Add a book to your collection and reload — "MA COLLECTION" counter increments.

**Quick Access Grid**: Verify 4 cards are always present. Log in as a moderator/admin — verify "ÉDITER UNE FICHE" card appears. Log in as a standard user — verify it is absent from the DOM (inspect source).

**Nouveautés**: Add a new published book via the moderation panel — verify it appears at the top of "LES NOUVEAUTÉS". Verify star rating displays correctly (including half-stars for `.5` averages).

**Activité**: Submit a book review → refresh dashboard → verify a "SOCIAL" event appears in the feed. Add a book to wishlist → verify a "PERSONAL" event appears.

**Forum Banner**: Verify "REJOINDRE LA TAVERNE DES AVENTURIERS" block is present at bottom of page with a working link.

## Running Tests

```bash
# All tests
php bin/phpunit

# Dashboard-specific tests only
php bin/phpunit tests/Unit/Service/DashboardServiceTest.php
php bin/phpunit tests/Integration/EventListener/ActivityEventListenerTest.php
php bin/phpunit tests/Unit/Command/PurgeActivityEventsCommandTest.php
php bin/phpunit tests/Functional/Controller/DashboardControllerTest.php
```

## Testing the Purge Command

```bash
# Insert a stale ActivityEvent (> 30 days old) via fixtures or direct SQL:
# UPDATE activity_event SET created_at = NOW() - INTERVAL '31 days' WHERE id = <id>;

# Run purge command
php bin/console app:purge-activity-events

# Verify the stale record is gone
```

## Platform.sh Cron Verification

After deployment, confirm the monthly purge cron is registered:

```bash
platform crons --project <project-id>
# Expected: purge_activity_events listed with spec "0 3 1 * *"
```
