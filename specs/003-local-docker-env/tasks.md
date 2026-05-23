---

description: "Task list for Local Docker Development Environment implementation"
---

# Tasks: Local Docker Development Environment

**Input**: Design documents from `/specs/003-local-docker-env/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/ ✅, quickstart.md ✅

**Tests**: Not requested — manual acceptance scenarios defined in spec.md

**Organization**: Tasks grouped by user story. All compose.yaml service additions go in Phase 3 (US1 requires all 4 containers per spec.md acceptance scenario 1).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1–US4)

---

## Phase 1: Setup

**Purpose**: Inspect existing files and create directory structure before any modifications

- [X] T001 Read compose.yaml and .gitignore to document existing content before modifications
- [X] T002 Create docker/php/conf.d/ and docker/nginx/ directory placeholders (touch docker/php/conf.d/.gitkeep docker/nginx/.gitkeep)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Create all supporting config files required by every user story — MUST complete before compose.yaml service work

**⚠️ CRITICAL**: No compose.yaml edits can begin until this phase is complete

- [X] T003 [P] Create docker/php/Dockerfile: base php:8.3-fpm-alpine; apk install postgresql-dev icu-dev $PHPIZE_DEPS; docker-php-ext-install pdo pdo_pgsql intl opcache; pecl install apcu && docker-php-ext-enable apcu; pecl install xdebug && docker-php-ext-enable xdebug; COPY --from=composer:latest /usr/bin/composer /usr/bin/composer; per research.md R-002
- [X] T004 [P] Create docker/php/conf.d/xdebug.ini: zend_extension=xdebug, xdebug.mode=off, xdebug.client_host=host.docker.internal, xdebug.client_port=9003, xdebug.start_with_request=yes; per research.md R-003 (client_host/port are convenience defaults; IDE client config is developer responsibility per spec Assumptions)
- [X] T005 [P] Create docker/nginx/default.conf: server block with root /var/www/html/public, index index.php, try_files $uri $uri/ /index.php$is_args$args, fastcgi_pass php:9000 with $realpath_root SCRIPT_FILENAME, internal on /index.php location, return 404 for arbitrary .php files; per research.md R-005
- [X] T006 [P] Create .env.local.example: APP_SECRET (blank + generation command comment), DATABASE_URL (postgresql://app:!ChangeMe!@database:5432/app?serverVersion=16&charset=utf8), POSTGRES_DB=app, POSTGRES_USER=app, POSTGRES_PASSWORD=!ChangeMe!, APP_PORT=8000, DB_PORT=5432, XDEBUG_MODE=off, GOOGLE_CLIENT_ID (blank placeholder), GOOGLE_CLIENT_SECRET (blank placeholder); per contracts/env-variables.md and research.md R-008/R-009
- [X] T007 Update .gitignore: add .env.local entry (if not already present) and compose.override.yaml entry; per FR-013a

**Checkpoint**: docker/php/Dockerfile, docker/php/conf.d/xdebug.ini, docker/nginx/default.conf, .env.local.example, and .gitignore updates are complete

---

## Phase 3: User Story 1 — Start the Full Stack (Priority: P1) 🎯 MVP

**Goal**: `docker compose up -d` starts all four services (database, php, nginx, node) within 2 minutes; Symfony app is reachable at http://localhost:8000

**Independent Test**: `docker compose up -d` → `docker compose ps` shows all 4 containers in running state → `curl -I http://localhost:8000` returns HTTP 200 or 302

- [X] T008 [US1] Update compose.yaml database service: add pg_isready health check (test: ["CMD-SHELL", "pg_isready -d ${POSTGRES_DB:-app} -U ${POSTGRES_USER:-app}"], interval 10s, timeout 5s, retries 5, start_period 60s), add POSTGRES_DB/PASSWORD/USER env vars, change port to ${DB_PORT:-5432}:5432, add restart: unless-stopped; preserve ###> doctrine/doctrine-bundle ### marker blocks per R-007
- [X] T009 [US1] Add php service to compose.yaml: build context ./docker/php, user: "${UID:-1000}:${GID:-1000}", depends_on: database: condition: service_healthy, bind mount .:/var/www/html, environment XDEBUG_MODE=${XDEBUG_MODE:-off}, extra_hosts host.docker.internal:host-gateway, restart: unless-stopped; per data-model.md php service spec and FR-005a
- [X] T010 [US1] Add nginx service to compose.yaml: image nginx:1.26-alpine, ports ${APP_PORT:-8000}:80, depends_on: [php], bind mounts .:/var/www/html:ro and ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro, restart: unless-stopped; per data-model.md nginx service spec
- [X] T011 [US1] Add node service to compose.yaml: image node:20-alpine, working_dir: /app, command: tail -f /dev/null, bind mount .:/app, restart: unless-stopped; per data-model.md node service spec and FR-004 (edit same compose.yaml as T008–T010; draft after T010, before T012)
- [X] T012 [US1] Add/verify database_data named volume in compose.yaml top-level volumes section; confirm database service volume mount references database_data:/var/lib/postgresql/data:rw; per FR-008

**Checkpoint**: `docker compose up -d` starts all 4 containers; Symfony application loads at http://localhost:8000

---

## Phase 4: User Story 2 — Run Database Migrations (Priority: P1)

**Goal**: `docker compose exec php php bin/console doctrine:migrations:migrate` executes without PHP-to-database connection errors; PostgreSQL is healthy before PHP starts

**Independent Test**: With stack running, run migrate command and confirm success output or "No migrations to execute"

- [X] T013 [US2] Verify compose.yaml php service depends_on block uses `condition: service_healthy` syntax (not bare list); verify database service health check has start_period: 60s to allow PostgreSQL init time per data-model.md
- [X] T014 [US2] Verify .env.local.example DATABASE_URL hostname is 'database' (Docker Compose service name) not '127.0.0.1'; add inline comment explaining hostname must match Docker service name per R-008

**Checkpoint**: PHP container waits for PostgreSQL health check before starting; `doctrine:migrations:migrate` succeeds

---

## Phase 5: User Story 3 — Compile Front-End Assets (Priority: P2)

**Goal**: `docker compose exec node npm install` and `docker compose exec node npm run build` produce compiled assets in public/build/

**Independent Test**: Run `docker compose exec node npm install` then `docker compose exec node npm run build`; confirm public/build/ directory is populated on the host

- [X] T015 [US3] Verify node service in compose.yaml: working_dir is /app, bind mount .:/app is read-write (no :ro) so npm writes node_modules/ and public/build/ to host; image is node:20-alpine per FR-004; confirm .env.local.example has no NODE_ENV or npm-specific variables (npm uses package.json scripts only, per SC-005)

**Checkpoint**: `npm install` and `npm run build` succeed inside node container; public/build/ assets appear on host

---

## Phase 6: User Story 4 — Persist Data Across Restarts (Priority: P2)

**Goal**: Data inserted before `docker compose down` is still present after `docker compose up -d`; `docker compose down -v` wipes all data

**Independent Test**: Insert a record via the app → `docker compose down` → `docker compose up -d` → verify record still exists; then `docker compose down -v` → confirm database starts empty

- [X] T017 [US4] Verify compose.yaml database_data volume is a named Docker-managed volume (top-level volumes: section, no driver_opts with device/host path); confirm docker compose down WITHOUT -v preserves volume data; per FR-008

**Checkpoint**: Named volume persists data across down/up cycles; down -v correctly removes the volume

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Documentation covering all manual steps across all user stories

- [X] T018 Write README.md: prerequisites (Docker Engine 20.10+, macOS/Linux only); first-time setup steps matching quickstart.md (clone, cp .env.local.example, export UID GID Linux note, docker compose pull, docker compose up -d, composer install, doctrine:migrations:migrate, npm install + npm run build, open http://localhost:8000); daily commands section; port conflict resolution (APP_PORT/DB_PORT in .env.local); Xdebug enable instructions (XDEBUG_MODE=debug + docker compose restart php); Google OAuth2 section (register http://localhost:8000/auth/google/callback, fill GOOGLE_CLIENT_ID/SECRET); troubleshooting table from quickstart.md; include SC-004 manual verification procedure (docker compose down → up -d × 10 cycles) in troubleshooting or persistence section
- [X] T019 [P] Validate .gitignore entries: confirm compose.override.yaml and .env.local are listed; confirm .env.local.example is NOT gitignored; run `git status` to verify untracked file handling is correct; run `grep -r "\.platform" compose.yaml docker/` and confirm zero matches (FR-012 verification)

**Checkpoint**: All spec.md acceptance scenarios are achievable using only README and .env.local.example (SC-003)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 — BLOCKS all compose.yaml work
- **US1 (Phase 3)**: Depends on Phase 2 complete — blocked until all support files exist
- **US2 (Phase 4)**: Depends on Phase 3 — verification of Phase 3 compose.yaml outputs
- **US3 (Phase 5)**: Depends on Phase 3 — verification of node service from Phase 3; independent of US2
- **US4 (Phase 6)**: Depends on Phase 3 — verification of volume from Phase 3; independent of US2/US3
- **Polish (Phase 7)**: Depends on all prior phases — documents completed implementation

### User Story Dependencies

- **US1 (P1)**: Requires Phase 2 complete; all four services must exist
- **US2 (P1)**: Requires Phase 3 complete; adds health-check ordering verification
- **US3 (P2)**: Requires Phase 3 complete; independent of US2
- **US4 (P2)**: Requires Phase 3 complete; independent of US2/US3

### Within Each Phase

- Phase 2: T003, T004, T005, T006 can run in parallel (different files); T007 after T001
- Phase 3: T008 → T009 → T010 → T011 → T012 are sequential (same compose.yaml file)
- Phase 4: T013 and T014 can run in parallel (different files)
- Phase 5: T015 only
- Phase 7: T018 and T019 can run in parallel

### Parallel Opportunities

```bash
# Phase 2: All supporting files at once
T003 docker/php/Dockerfile
T004 docker/php/conf.d/xdebug.ini
T005 docker/nginx/default.conf
T006 .env.local.example

# Phase 3: All sequential (same compose.yaml file)
T008 → T009 → T010 → T011 → T012

# Phase 4-6: All verification tasks at once
T013 depends_on verification
T014 DATABASE_URL hostname check
T015 node service bind-mount check + npm env check
T017 volume persistence check
```

---

## Implementation Strategy

### MVP First (US1 + US2 — Full P1 Scope)

1. Complete Phase 1: Read existing files
2. Complete Phase 2: Dockerfile, xdebug.ini, nginx.conf, .env.local.example, .gitignore
3. Complete Phase 3: compose.yaml with all 4 services
4. Complete Phase 4: Verify health check ordering and DATABASE_URL
5. **STOP and VALIDATE**: `docker compose up -d` → all containers Running → `http://localhost:8000` loads → `doctrine:migrations:migrate` succeeds

### Incremental Delivery

1. Phases 1–4: Full P1 delivery (stack starts + migrations work) → demo-able
2. Phase 5: US3 (confirm npm/asset pipeline works)
3. Phase 6: US4 (confirm persistence works)
4. Phase 7: README documentation → SC-003 satisfied

### Single Developer Sequence

Phase 1 → Phase 2 (T003–T006 in parallel, then T007) → Phase 3 (T008→T009→T010, T011 in parallel, then T012) → Phases 4–6 (T013–T017 all in parallel) → Phase 7 (T018, T019 in parallel)

---

## Notes

- [P] tasks = different files, no blocking dependencies between them
- [Story] label maps task to user story for traceability
- Pure infrastructure feature — no application code, no Doctrine entities
- All compose.yaml edits MUST preserve `###> doctrine/doctrine-bundle ###` marker blocks (R-007)
- PHP container does NOT set APP_SECRET/DATABASE_URL in compose.yaml — Symfony dotenv reads them from .env.local at runtime
- After writing compose.yaml, run `docker compose config` to validate YAML syntax before testing
- UID/GID caveat: $UID is not auto-exported on Linux — README must document `export UID GID`
