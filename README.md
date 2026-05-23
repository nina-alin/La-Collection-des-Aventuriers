# La Collection dont Vous Êtes le Héros

A Symfony application for managing your book collection.

## Prerequisites

- Docker Engine 20.10+ or Docker Desktop 4.0+ with Compose v2 plugin
  - Verify: `docker compose version`
- macOS or Linux (Windows/WSL2 not supported)
- Git

## First-Time Setup

### 1. Clone the repository

```bash
git clone <repo-url>
cd la-collection-dont-vous-etes-le-heros
```

### 2. Create your local environment file

```bash
cp .env.local.example .env.local
```

Edit `.env.local` and set:
- `APP_SECRET` — generate with: `php -r "echo bin2hex(random_bytes(32));"`
- `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` — see [Google OAuth2 Setup](#google-oauth2-setup) below

The `DATABASE_URL` default value works out of the box with the Docker stack.

### 3. Export UID/GID (Linux only)

```bash
export UID GID
```

Add to `~/.bashrc` or `~/.zshrc` to make it permanent. Not required on macOS.

### 4. Pull images and start the stack

```bash
docker compose pull
docker compose up -d
```

All four containers (database, php, nginx, node) should be running within 2 minutes with pre-pulled images.

### 5. Install PHP dependencies

```bash
docker compose exec php composer install
```

### 6. Run database migrations

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

### 7. Build front-end assets

```bash
docker compose exec node npm install
docker compose exec node npm run build
```

### 8. Open the application

Visit http://localhost:8000

---

## Daily Commands

```bash
# Start the stack
docker compose up -d

# Stop the stack (data persists in named volume)
docker compose down

# Wipe all data and start fresh
docker compose down -v

# Watch for JS/CSS changes during development
docker compose exec node npm run watch

# Run Symfony console commands
docker compose exec php php bin/console <command>

# Open a shell in the PHP container
docker compose exec php sh

# Open a shell in the Node container
docker compose exec node sh
```

---

## Port Conflicts

If ports 8000 (Nginx) or 5432 (PostgreSQL) are already in use on your host, override them in `.env.local`:

```dotenv
APP_PORT=8080
DB_PORT=5433
```

Then restart: `docker compose down && docker compose up -d`

If you change `APP_PORT`, update your Google OAuth2 redirect URI accordingly.

---

## Enable Xdebug

Add to `.env.local`:

```dotenv
XDEBUG_MODE=debug
```

Restart the PHP container (no rebuild needed):

```bash
docker compose restart php
```

Configure your IDE to listen for Xdebug connections on port 9003. On Linux, `host.docker.internal` resolves to the host via the `extra_hosts` entry already set in `compose.yaml`.

---

## Google OAuth2 Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/) → APIs & Services → Credentials
2. Create an **OAuth 2.0 Client ID** (Web application type)
3. Add `http://localhost:8000/auth/google/callback` as an **Authorized redirect URI**
   - If using a custom `APP_PORT`, substitute: `http://localhost:<APP_PORT>/auth/google/callback`
4. Copy the credentials into `.env.local`:
   ```dotenv
   GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=your-client-secret
   ```

---

## Data Persistence

PostgreSQL data is stored in a named Docker volume (`database_data`).

- `docker compose down` — stack stops, **data persists**
- `docker compose down -v` — stack stops, **volume deleted**, database starts empty on next `up`

To manually verify persistence across 10 restart cycles (SC-004):

```bash
for i in $(seq 1 10); do
  docker compose down && docker compose up -d
  echo "Cycle $i complete"
done
```

---

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| `vendor/` missing — autoload error | `composer install` not run | `docker compose exec php composer install` |
| `public/build/` missing — 500 on CSS/JS | Assets not compiled | `docker compose exec node npm install && docker compose exec node npm run build` |
| DB connection error at runtime | `.env.local` credentials mismatch | Verify `POSTGRES_PASSWORD` matches in both `DATABASE_URL` and the `POSTGRES_PASSWORD` variable |
| `Bind for 0.0.0.0:8000 failed` | Port already in use | Set `APP_PORT=8080` in `.env.local` and restart |
| `Bind for 0.0.0.0:5432 failed` | Port already in use | Set `DB_PORT=5433` in `.env.local` and restart |
| Permission errors on `var/` | `UID`/`GID` not exported (Linux) | Run `export UID GID` then `docker compose up -d` |
| `APP_SECRET` error on first request | `APP_SECRET` blank in `.env.local` | Generate and set `APP_SECRET` (see step 2) |
