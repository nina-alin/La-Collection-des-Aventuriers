# Contract: Environment Variables

**Feature**: 003-local-docker-env
**Type**: Configuration interface contract

This document defines the complete `.env.local` variable contract. All variables listed as **Required** must be present in `.env.local` for the stack to start and function correctly.

---

## Required Variables

| Variable | Example Value | Description |
|----------|--------------|-------------|
| `APP_SECRET` | `a3f8e2c1d...` (32-byte hex) | Symfony encryption secret. Generate: `php -r "echo bin2hex(random_bytes(32));"` |
| `DATABASE_URL` | `postgresql://app:secret@database:5432/app?serverVersion=16&charset=utf8` | Full Symfony DB DSN. Hostname MUST be `database` (Docker service name, not `127.0.0.1`) |
| `GOOGLE_CLIENT_ID` | `123456-abc.apps.googleusercontent.com` | Google OAuth2 client ID from Google Cloud Console |
| `GOOGLE_CLIENT_SECRET` | `GOCSPX-...` | Google OAuth2 client secret |

---

## Optional Override Variables

These have working defaults; override only to resolve port conflicts or enable features.

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_PORT` | `8000` | Host port mapped to Nginx container port 80 |
| `DB_PORT` | `5432` | Host port mapped to PostgreSQL container port 5432 |
| `POSTGRES_DB` | `app` | PostgreSQL database name |
| `POSTGRES_USER` | `app` | PostgreSQL username |
| `POSTGRES_PASSWORD` | `!ChangeMe!` | PostgreSQL password (change in `.env.local` for any shared env) |
| `XDEBUG_MODE` | `off` | Set to `debug` to enable Xdebug step debugging; no container rebuild needed |

---

## Variable Interactions

- `POSTGRES_USER` / `POSTGRES_PASSWORD` / `POSTGRES_DB` set in compose.yaml environment must match the credentials in `DATABASE_URL`
- If `APP_PORT` is overridden (e.g., `APP_PORT=8080`), the application is accessible at `http://localhost:8080` — update Google OAuth2 redirect URI accordingly
- `XDEBUG_MODE=debug` requires the IDE listening on port 9003; `host.docker.internal` resolves to the host on macOS; Linux requires `export UID GID` (see README)

---

## Error Conditions

| Condition | Symptom | Fix |
|-----------|---------|-----|
| `APP_SECRET` blank | Symfony throws `InvalidArgumentException: The "APP_SECRET" parameter is empty` | Set `APP_SECRET` in `.env.local` |
| `DATABASE_URL` uses `127.0.0.1` | PHP container cannot reach PostgreSQL | Change host to `database` in `DATABASE_URL` |
| `APP_PORT` conflict | Docker Compose fails: `Bind for 0.0.0.0:8000 failed: port is already allocated` | Set `APP_PORT=<other port>` in `.env.local` |
| `DB_PORT` conflict | Same as above for port 5432 | Set `DB_PORT=<other port>` in `.env.local` |
| `.env.local` missing entirely | Stack may start but Symfony errors on first request | Copy `.env.local.example` to `.env.local` and fill required values |
