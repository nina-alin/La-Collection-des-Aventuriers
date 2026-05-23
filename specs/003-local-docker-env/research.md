# Research: Local Docker Development Environment

**Feature**: 003-local-docker-env
**Phase**: 0 — Pre-design research
**Status**: Complete

---

## R-001: Compose file naming — `compose.yaml` vs `docker-compose.yaml`

**Decision**: Use `compose.yaml` (modern Docker convention) and `compose.override.yaml` for the gitignored per-developer override.

**Rationale**: Docker Compose v2 (plugin) supports both `docker-compose.yaml` and `compose.yaml`. Symfony Flex already generated `compose.yaml` and `compose.override.yaml` at project init. The spec uses "docker-compose.yaml" as generic terminology; the project uses `compose.yaml` for consistency with the existing skeleton and the Docker-recommended modern convention.

**Alternatives considered**: Rename to `docker-compose.yaml` — rejected, would break Symfony Flex update compatibility and conflict with the existing committed file.

---

## R-002: PHP 8.3 FPM Alpine image — extensions

**Decision**: `php:8.3-fpm-alpine` base. Install extensions via `docker-php-ext-install` and PECL.

**Exact build steps**:
```dockerfile
RUN apk add --no-cache \
        postgresql-dev \
        icu-dev \
        $PHPIZE_DEPS

RUN docker-php-ext-install pdo pdo_pgsql intl opcache

RUN pecl install apcu \
    && docker-php-ext-enable apcu

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
```

**Alpine runtime dependencies**: If build-time `-dev` packages are removed in a multi-stage build, the final stage needs `libpq` and `icu-libs`. For simplicity, keep build-time deps in a single stage (acceptable for local dev images where size is not critical).

**Composer**: `COPY --from=composer:latest /usr/bin/composer /usr/bin/composer`

**Alpine-specific gotchas**:
- `postgresql-dev` is required for `pdo_pgsql`; without it, configure fails with "cannot find pgsql libraries"
- `icu-dev` is required for `intl`
- `$PHPIZE_DEPS` (autoconf, make, g++) is required before any `pecl install`

---

## R-003: Xdebug 3 — disabled by default

**Decision**: Install Xdebug via PECL, ship an `xdebug.ini` with `xdebug.mode=off`, and let `XDEBUG_MODE` env var override at container startup.

**`docker/php/conf.d/xdebug.ini`**:
```ini
zend_extension=xdebug
xdebug.mode=off
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
xdebug.start_with_request=yes
```

**`XDEBUG_MODE` env var takes full precedence** over ini file at startup — no container rebuild needed to enable/disable.

**Linux note**: `host.docker.internal` does not resolve on Linux without extra configuration. Add `extra_hosts: ["host.docker.internal:host-gateway"]` to the php service in `compose.yaml`. This is harmless on macOS/Docker Desktop.

**Scope**: IDE client configuration (client_host, port 9003) is the developer's responsibility. The spec explicitly places this out of scope (FR-005b, Assumptions).

---

## R-004: UID/GID for PHP-FPM user mapping

**Decision**: `user: "${UID:-1000}:${GID:-1000}"` on the php service. Document `export UID GID` in README for Linux users.

**macOS**: Docker Desktop uses a VM volume translation layer — file ownership issues don't arise. `1000:1000` fallback is harmless.

**Linux**: `$UID` is a bash read-only variable that is **not exported** to the environment by default. Docker Compose reads from shell environment, not bash internals. Fix options (in order of preference):
1. README instructs developers to run `export UID GID` before `docker compose up` (or add to shell profile)
2. Add `UID=$(id -u)` / `GID=$(id -g)` to `.env.local.example` with instructions to set actual values

**Decision**: Document `export UID GID` in README as a Linux-specific setup step.

---

## R-005: Nginx configuration for Symfony

**Decision**: `nginx:1.26-alpine` (stable line). Custom `default.conf` with Symfony front-controller pattern.

**Key config decisions**:
- `fastcgi_pass php:9000` (TCP) — simplest and most reliable for Docker Compose; unix sockets require shared volume with matched UIDs
- `$realpath_root` for `SCRIPT_FILENAME` — resolves symlinks during Symfony cache warmup
- `internal` on the index.php location — blocks direct `/index.php/path` requests
- `location ~ \.php$ { return 404; }` — blocks execution of arbitrary PHP files

**Nginx stable vs mainline**: Nginx uses odd/even versioning — odd = mainline (less stable), even = stable. `1.26` is the current stable series. Pin to `nginx:1.26-alpine` rather than `nginx:alpine` (tracks latest, potentially mainline).

---

## R-006: `compose.override.yaml` gitignore strategy

**Decision**: Gitignore `compose.override.yaml`. Move the existing committed port exposure (`ports: "5432"`) into `compose.yaml` using env-var override: `${DB_PORT:-5432}:5432`.

**Rationale**: FR-013a requires the override file be gitignored for per-developer customization. Port remapping moves to `.env.local` via `DB_PORT` and `APP_PORT` variables (already required by FR-013). The current committed `compose.override.yaml` only contains the Symfony Flex–generated `ports: "5432"` line — this migrates cleanly into `compose.yaml`.

---

## R-007: `compose.yaml` file — expand vs replace

**Decision**: Expand the existing `compose.yaml`, keeping the `###> doctrine/doctrine-bundle ###` marker blocks intact.

**Rationale**: Symfony Flex uses these markers to manage content during `composer update`. Adding new services outside the markers and modifying the database service within the markers preserves future Flex update compatibility.

---

## R-008: `DATABASE_URL` hostname in `.env.local`

**Decision**: `DATABASE_URL` in `.env.local` MUST use `database` as the hostname (Docker service name), not `127.0.0.1`. The existing `.env` uses `127.0.0.1` (for non-Docker / Platform.sh use). The `.env.local.example` must override with `database`.

**Example**: `DATABASE_URL="postgresql://app:secret@database:5432/app?serverVersion=16&charset=utf8"`

---

## R-009: APP_SECRET for local development

**Decision**: `.env.local.example` includes `APP_SECRET` with a placeholder and generation command. The `.env` file has `APP_SECRET=` (blank); Symfony throws a runtime error if blank in dev. Developers must set a local value.

**Generation command**: `php -r "echo bin2hex(random_bytes(32));"`
