# Data Model: Local Docker Development Environment

**Feature**: 003-local-docker-env
**Phase**: 1 — Design

This feature introduces no Doctrine entities or database schema changes. The "data model" covers Docker service definitions, volume configurations, bind-mount specifications, and environment variable schemas.

---

## Services

### `database` — PostgreSQL 16

| Field | Value |
|-------|-------|
| Image | `postgres:${POSTGRES_VERSION:-16}-alpine` |
| Status | **Existing** (Symfony Flex skeleton) — modified to add host port exposure |
| Ports | `${DB_PORT:-5432}:5432` (moved from `compose.override.yaml`) |
| Volume | `database_data:/var/lib/postgresql/data:rw` |
| Restart | `unless-stopped` |
| Health check | `pg_isready -d ${POSTGRES_DB:-app} -U ${POSTGRES_USER:-app}` — timeout 5s, retries 5, start_period 60s |

Environment variables:
- `POSTGRES_DB` → `${POSTGRES_DB:-app}`
- `POSTGRES_PASSWORD` → `${POSTGRES_PASSWORD:-!ChangeMe!}`
- `POSTGRES_USER` → `${POSTGRES_USER:-app}`

---

### `php` — PHP 8.3 FPM

| Field | Value |
|-------|-------|
| Image | Custom — `docker/php/Dockerfile` (base: `php:8.3-fpm-alpine`) |
| Build context | `./docker/php` |
| User | `${UID:-1000}:${GID:-1000}` |
| Depends on | `database` (condition: `service_healthy`) |
| Restart | `unless-stopped` |

Extensions installed: `pdo`, `pdo_pgsql`, `intl`, `opcache`, `apcu` (PECL), `xdebug` (PECL)

Bind mounts:
- `.:/var/www/html` — full project root (covers `var/`, `public/build/`, `vendor/`)

Environment variables:
- `XDEBUG_MODE=${XDEBUG_MODE:-off}`
- `APP_ENV`, `APP_SECRET`, `DATABASE_URL` — sourced from `.env.local` via Symfony dotenv (not set in compose.yaml directly)

Extra hosts:
- `host.docker.internal:host-gateway` (Linux compatibility for Xdebug client)

---

### `nginx` — Nginx 1.26

| Field | Value |
|-------|-------|
| Image | `nginx:1.26-alpine` |
| Ports | `${APP_PORT:-8000}:80` |
| Depends on | `php` |
| Restart | `unless-stopped` |

Bind mounts:
- `.:/var/www/html:ro` — project root (read-only; Nginx serves static files from `public/`)
- `./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro`

Document root: `/var/www/html/public`
FastCGI upstream: `php:9000` (TCP)

---

### `node` — Node.js 20

| Field | Value |
|-------|-------|
| Image | `node:20-alpine` |
| Working dir | `/app` |
| Command | `tail -f /dev/null` (keeps container alive for exec) |
| Restart | `unless-stopped` |

Bind mounts:
- `.:/app` — project root (reads `package.json`, writes `node_modules/`, `public/build/`)

Usage: `docker compose exec node npm install` / `npm run build` / `npm run watch`

---

## Volumes

| Name | Type | Purpose |
|------|------|---------|
| `database_data` | Named Docker volume | PostgreSQL data persistence across `down`/`up` cycles |

---

## Configuration Files

| File | Committed | Gitignored | Purpose |
|------|-----------|------------|---------|
| `compose.yaml` | ✅ | — | Service orchestration (all 4 services + volume) |
| `compose.override.yaml` | — | ✅ | Per-developer port overrides (`APP_PORT`, `DB_PORT`) |
| `docker/php/Dockerfile` | ✅ | — | PHP 8.3-fpm-alpine + extensions + Xdebug |
| `docker/php/conf.d/xdebug.ini` | ✅ | — | Xdebug 3 config (mode=off default) |
| `docker/nginx/default.conf` | ✅ | — | Nginx vhost: `public/` docroot, `index.php` passthrough |
| `.env.local.example` | ✅ | — | Template with all required variable names and placeholder values |
| `.env.local` | — | ✅ | Developer-local actual values (already gitignored) |
| `README.md` | ✅ | — | Quickstart + Google OAuth2 redirect URI setup |

---

## Environment Variable Schema

| Variable | Default (`.env` or placeholder) | Required in `.env.local` | Description |
|----------|----------------------------------|--------------------------|-------------|
| `APP_ENV` | `dev` | No | Symfony environment |
| `APP_SECRET` | `` (blank) | **Yes** | Generate: `php -r "echo bin2hex(random_bytes(32));"` |
| `DATABASE_URL` | `postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8` | **Yes** | Override hostname to `database` (Docker service name) |
| `POSTGRES_DB` | `app` | No (has default) | PostgreSQL database name |
| `POSTGRES_USER` | `app` | No (has default) | PostgreSQL username |
| `POSTGRES_PASSWORD` | `!ChangeMe!` | No (has default; change for security) | PostgreSQL password |
| `APP_PORT` | `8000` | No | Host port for Nginx (remap on conflict) |
| `DB_PORT` | `5432` | No | Host port for direct DB access (remap on conflict) |
| `XDEBUG_MODE` | `off` | No | `off` or `debug`; no container rebuild needed |
| `GOOGLE_CLIENT_ID` | `` | **Yes** (for OAuth2 login) | Google OAuth2 client ID |
| `GOOGLE_CLIENT_SECRET` | `` | **Yes** (for OAuth2 login) | Google OAuth2 client secret |
