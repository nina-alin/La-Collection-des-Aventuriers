# Developer Quickstart: Local Docker Environment

**Feature**: 003-local-docker-env

---

## Prerequisites

- Docker Engine 20.10+ or Docker Desktop 4.0+ with Compose v2 plugin (`docker compose version`)
- macOS or Linux (Windows/WSL2 not supported)
- Git

---

## First-Time Setup

### 1. Clone and enter the project

```bash
git clone <repo-url>
cd la-collection-dont-vous-etes-le-heros
```

### 2. Create your local environment file

```bash
cp .env.local.example .env.local
```

Edit `.env.local` and fill in:
- `APP_SECRET` — generate with: `php -r "echo bin2hex(random_bytes(32));"`
- `DATABASE_URL` — hostname must be `database` (the Docker service name)
- `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` — see Google OAuth2 section below

### 3. Export UID/GID (Linux only)

```bash
export UID GID
```

Add to your `~/.bashrc` or `~/.zshrc` to make permanent. Not required on macOS.

### 4. Pull images and start the stack

```bash
docker compose pull
docker compose up -d
```

### 5. Install PHP dependencies

```bash
docker compose exec php composer install
```

### 6. Run database migrations

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

### 7. Install and build front-end assets

```bash
docker compose exec node npm install
docker compose exec node npm run build
```

### 8. Open the application

Visit `http://localhost:8000`

---

## Daily Commands

```bash
# Start stack (after initial setup)
docker compose up -d

# Stop stack (data persists)
docker compose down

# Wipe all data (clean slate)
docker compose down -v

# Watch for JS/CSS changes
docker compose exec node npm run watch

# Run Symfony console commands
docker compose exec php php bin/console <command>

# Open a shell in PHP container
docker compose exec php sh

# Open a shell in Node container
docker compose exec node sh
```

---

## Port Conflicts

If ports 8000 or 5432 are in use, add to `.env.local`:

```dotenv
APP_PORT=8080
DB_PORT=5433
```

Then restart: `docker compose down && docker compose up -d`

---

## Enable Xdebug

Add to `.env.local`:

```dotenv
XDEBUG_MODE=debug
```

Restart PHP container: `docker compose restart php`

No rebuild needed. Configure your IDE to listen on port 9003.

---

## Google OAuth2 Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/) → APIs & Services → Credentials
2. Create an OAuth 2.0 Client ID (Web application type)
3. Add `http://localhost:8000/auth/google/callback` as an **Authorized redirect URI**
   - If using a custom `APP_PORT`, substitute accordingly: `http://localhost:<APP_PORT>/auth/google/callback`
4. Copy the Client ID and Client Secret into `.env.local`:
   ```dotenv
   GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=your-client-secret
   ```

---

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| `vendor/` missing — autoload error | `composer install` not run | `docker compose exec php composer install` |
| `public/build/` missing — 500 on CSS/JS | `npm install && npm run build` not run | Run build steps (step 7 above) |
| Wrong DB password — connection error at runtime | `.env.local` credentials don't match compose | Check `POSTGRES_PASSWORD` matches in both `DATABASE_URL` and `.env.local` |
| Port already allocated | Port 8000 or 5432 in use | Set `APP_PORT` / `DB_PORT` in `.env.local` |
| Permission errors on `var/` | `UID`/`GID` not exported (Linux) | Run `export UID GID` then restart stack |
