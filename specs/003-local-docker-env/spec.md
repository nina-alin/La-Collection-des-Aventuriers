# Feature Specification: Local Docker Development Environment

**Feature Branch**: `003-local-docker-env`

**Created**: 2026-05-23

**Status**: Draft

**Input**: User description: "Environnement de Développement Local Docker Compose"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Start the Full Stack (Priority: P1)

A developer clones the repository, runs a single command, and within minutes has a fully operational local environment with all services running.

**Why this priority**: Without a working local stack, no development can begin. This is the foundation for all other stories.

**Independent Test**: Run `docker compose up -d` on a machine with images already pulled and verify all containers start, then confirm the application is reachable in a browser.

**Acceptance Scenarios**:

1. **Given** a developer has Docker installed and has cloned the repo with images already pulled, **When** they run `docker compose up -d`, **Then** all containers (PHP, database, Node.js, web server) start without errors within 2 minutes, and `docker compose ps` shows all containers in running state
2. **Given** the stack is running, **When** the developer opens `http://localhost:8000` in a browser, **Then** the Symfony application home page loads successfully
3. **Given** the stack is running with an incorrect `.env.local`, **When** `docker compose up -d` is run, **Then** an error message on stderr identifies the specific missing required variable by name

---

### User Story 2 - Run Database Migrations (Priority: P1)

A developer can execute Doctrine migrations against the local database container to keep the schema in sync with the codebase.

**Why this priority**: Schema migrations are required for any feature involving data. This validates PHP-to-database connectivity.

**Independent Test**: Execute `docker compose exec php php bin/console doctrine:migrations:migrate` and confirm it runs without connection errors.

**Acceptance Scenarios**:

1. **Given** the PHP and database containers are running, **When** the developer runs `doctrine:migrations:migrate`, **Then** all pending migrations apply successfully
2. **Given** the database is up and migrations are up to date, **When** the developer runs the migrate command again, **Then** the output reports "No migrations to execute" without error

---

### User Story 3 - Compile Front-End Assets (Priority: P2)

A developer can install Node.js dependencies and compile front-end assets from inside the Node.js container.

**Why this priority**: Required for working on Bootstrap/design system UI. Can be developed independently of PHP backend work.

**Independent Test**: Run `npm install` then `npm run build` inside the Node.js container and verify compiled assets appear in `public/build/`.

**Acceptance Scenarios**:

1. **Given** the Node.js container is running, **When** the developer runs `npm install`, **Then** all packages install successfully with no unresolved peer dependency errors (npm warnings do not constitute failure)
2. **Given** dependencies are installed, **When** the developer runs `npm run build`, **Then** compiled assets are generated in the `public/build/` directory
3. **Given** the developer is working on CSS/JS, **When** they run `npm run watch`, **Then** the file watcher starts and recompiles on each save

---

### User Story 4 - Persist Data Across Restarts (Priority: P2)

Database data survives a `docker compose down` and `docker compose up` cycle without requiring re-seeding.

**Why this priority**: Prevents developer productivity loss from repeated data setup. Independent of application features.

**Independent Test**: Create a record via the app, run `docker compose down`, run `docker compose up -d`, then verify the record still exists.

**Acceptance Scenarios**:

1. **Given** the developer has inserted data into the local database, **When** they run `docker compose down` followed by `docker compose up -d`, **Then** all previously inserted data is still present
2. **Given** the developer wants a clean slate, **When** they run `docker compose down -v`, **Then** all data volumes are removed and the database starts empty

---

### Edge Cases

- Port conflict on 8000 or 5432: Docker Compose fails fast; developers remap via `APP_PORT` / `DB_PORT` in `.env.local`
- `.env.local` missing entirely: error on stderr names the first missing required variable
- `.env.local` present with wrong credentials (e.g., incorrect DB password): PHP-FPM starts but Symfony application fails with a database connection error at runtime; README must document this as a known failure mode
- Docker image pull fails mid-setup (network interruption): Docker reports pull error and aborts; developer re-runs `docker compose up -d`
- Developer runs `docker compose up` before `composer install` (missing `vendor/`): PHP-FPM container starts but application fails with autoload error; README must document `docker compose exec php composer install` as a required setup step
- Developer runs `npm run build` before `npm install` (empty `node_modules/`): Encore fails with missing module error; README must document the install-before-build sequence
- File permissions (`var/`, `public/build/`): PHP-FPM container runs as host UID/GID via `user: "${UID:-1000}:${GID:-1000}"` in `docker-compose.yaml`

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The environment MUST start all required services (PHP, database, Node.js, web server) with a single `docker compose up -d` command; all services MUST use `restart: unless-stopped`
- **FR-001a**: The PHP-FPM container MUST NOT start application connections until the database container passes its health check; the database container MUST define a `pg_isready` health check and PHP-FPM MUST declare `depends_on` with `condition: service_healthy`
- **FR-002**: PHP container MUST use version 8.3, matching the Platform.sh production environment; minor version (8.3.x) may vary
- **FR-003**: Database container MUST use PostgreSQL 16, matching the Platform.sh production environment; minor version (16.x) may vary
- **FR-004**: Node.js container MUST use version 20 LTS, matching the Platform.sh production build environment; minor version (20.x) may vary
- **FR-005**: PHP container MUST have the following extensions installed: `pdo_pgsql`, `intl`, `opcache`, `apcu`
- **FR-005a**: PHP-FPM container MUST run as the host user via `user: "${UID:-1000}:${GID:-1000}"` to prevent file permission errors on `var/` and `public/build/` mounts; if `UID`/`GID` are unset on the host, the default value of `1000` MUST apply
- **FR-005b**: PHP container MUST include Xdebug 3, disabled by default (`XDEBUG_MODE=off`); developers enable via `.env.local` (`XDEBUG_MODE=debug`) without rebuilding the container; IDE client configuration is out of scope
- **FR-006**: PHP container MUST include Composer for dependency management
- **FR-007**: Database credentials and connection parameters MUST be configurable via a `.env.local` file not committed to version control
- **FR-008**: Database data MUST persist between `docker compose down` / `docker compose up` cycles via a named Docker volume
- **FR-009**: The web server container MUST route HTTP requests to the PHP-FPM container using `public/` as the document root and `index.php` as the passthrough entry point
- **FR-010**: The Symfony application MUST be accessible via `http://localhost:8000`
- **FR-011**: The Node.js container MUST be able to install packages and compile assets via `npm install` and `npm run build`
- **FR-012**: Docker Compose configuration files MUST be isolated to development only and MUST NOT modify or reference Platform.sh configuration files (`.platform.app.yaml`, `.platform/services.yaml`, `.platform/routes.yaml`)
- **FR-013**: A `.env.local.example` file MUST be provided with all required variables and placeholder values to guide new developers, including `APP_PORT` (default: 8000) and `DB_PORT` (default: 5432) override vars
- **FR-013a**: `.env.local` and `docker-compose.override.yaml` MUST be listed in `.gitignore`
- **FR-014**: `.env.local.example` MUST include OAuth2 client ID/secret placeholder variables for the Google provider; README MUST include a section explaining how to register `http://localhost:8000/auth/google/callback` as an authorized redirect URI with Google

### Key Entities *(include if feature involves data)*

- **docker-compose.yaml**: Root-level orchestration file defining all services, volumes, and networks for local development
- **docker/ directory**: Optional directory containing custom Dockerfiles and service-specific configuration (Nginx config, PHP ini overrides); required only if default images are insufficient
- **.env.local**: Developer-local environment variables (gitignored); defines database DSN and credentials
- **.env.local.example**: Committed template of `.env.local` with placeholder values
- **Named Volume**: Docker-managed persistent storage for the PostgreSQL data directory

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A developer with Docker installed and images already pulled can have the full local stack running within 5 minutes of cloning the repository; initial cold image download is excluded from this time budget
- **SC-002**: All five acceptance criteria from the original specification pass without manual intervention
- **SC-003**: A developer with no prior knowledge of the project can complete all setup steps using only the README and `.env.local.example`, with no undocumented manual steps required
- **SC-004**: Data inserted into the local database survives at least 10 consecutive down/up cycles without loss
- **SC-005**: Asset compilation via `npm install && npm run build` in the Node.js container produces functionally equivalent output to the Platform.sh build hook (`npm ci && npm run build`); `npm install` is used locally for developer convenience; `npm ci` is reserved for CI/CD and Platform.sh

## Clarifications

### Session 2026-05-23

- Q: How should OAuth2 provider callback URLs be configured for local dev? → A: Add OAuth2 redirect URI vars to `.env.local.example`; add README section on registering `http://localhost:8000/auth/...` with each provider (Option A)
- Q: How should port conflicts (8000, 5432) be handled? → A: Fail fast on conflict; document `APP_PORT` / `DB_PORT` override vars in `.env.local.example` so developers can remap (Option A)
- Q: How to handle file permission issues between host and PHP container (var/, public/)? → A: Run PHP-FPM as host UID/GID via `user: "${UID:-1000}:${GID:-1000}"` in docker-compose.yaml; document in README (Option A)
- Q: Should Xdebug be included in the PHP container? → A: Include Xdebug 3; disabled by default (`XDEBUG_MODE=off`); enable via `.env.local` (`XDEBUG_MODE=debug`) (Option A)
- Q: What restart policy should containers use? → A: `restart: unless-stopped` on all services (Option A)

### Session 2026-05-23 (checklist gap resolution)

- Q: Which OAuth2 providers are in scope for `.env.local.example`? → A: Google only; GitHub is not covered by spec 002 and is removed from FR-014
- Q: Is Xdebug IDE client configuration (XDEBUG_CLIENT_HOST, port 9003) in scope? → A: Out of scope; XDEBUG_MODE is the only env var specified at spec level
- Q: Which host operating systems are supported? → A: macOS and Linux only; Windows and WSL2 are explicitly out of scope
- Q: Should startup ordering between PHP-FPM and PostgreSQL be specified? → A: Yes — PostgreSQL must pass health check (`pg_isready`) before PHP-FPM starts; specified in FR-001a
- Q: Is docker-compose v1 (`docker-compose`) or v2 (`docker compose`) required? → A: Docker Compose v2 (plugin, `docker compose`); minimum Docker Engine 20.10 / Docker Desktop 4.0
- Q: What is the exact output path for compiled assets? → A: `public/build/` (Webpack Encore output directory)
- Q: Does SC-005 "identical output" require `npm ci` locally? → A: No — `npm install` is used locally; `npm ci` is for CI/CD; output is functionally equivalent when `package-lock.json` is current

## Assumptions

- Developers have Docker Engine 20.10+ or Docker Desktop 4.0+ with the Compose v2 plugin installed on macOS or Linux; Windows and WSL2 are explicitly out of scope
- The host machine exposes port 8000 for the web server and port 5432 for direct database access (for GUI tools like TablePlus or pgAdmin)
- The project uses Nginx (not Apache or Symfony CLI) as the local web server to match a typical FPM deployment model
- `npm` is the package manager (not Yarn), consistent with the `npm ci` call in `.platform.app.yaml` and the absence of `yarn.lock`
- The PHP-FPM container requires a bind-mount for `/var` (Symfony cache, logs, sessions) matching the Platform.sh local mount; `public/` is NOT a Platform.sh mount but requires a bind-mount for Webpack Encore asset output (`public/build/`) and serves as the Nginx document root
- Mobile or staging environment Docker configuration is out of scope; this spec covers local development only
- The `.env.local` file approach follows the existing Symfony dotenv convention; `.env.local` overrides `.env` and is never committed
- Xdebug IDE client configuration (client host, port) is the developer's responsibility and is not documented in this spec
