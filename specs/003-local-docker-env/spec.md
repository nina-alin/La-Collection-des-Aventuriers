# Feature Specification: Local Docker Development Environment

**Feature Branch**: `003-local-docker-env`

**Created**: 2026-05-23

**Status**: Draft

**Input**: User description: "Environnement de Développement Local Docker Compose"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Start the Full Stack (Priority: P1)

A developer clones the repository, runs a single command, and within minutes has a fully operational local environment with all services running.

**Why this priority**: Without a working local stack, no development can begin. This is the foundation for all other stories.

**Independent Test**: Run `docker-compose up -d` on a clean machine and verify all containers start, then confirm the application is reachable in a browser.

**Acceptance Scenarios**:

1. **Given** a developer has Docker installed and has cloned the repo, **When** they run `docker-compose up -d`, **Then** all containers (PHP, database, Node.js, web server) start without errors within 2 minutes
2. **Given** the stack is running, **When** the developer opens `http://localhost:8000` in a browser, **Then** the Symfony application home page loads successfully
3. **Given** the stack is running with an incorrect `.env.local`, **When** `docker-compose up -d` is run, **Then** a clear error message indicates which required variable is missing

---

### User Story 2 - Run Database Migrations (Priority: P1)

A developer can execute Doctrine migrations against the local database container to keep the schema in sync with the codebase.

**Why this priority**: Schema migrations are required for any feature involving data. This validates PHP-to-database connectivity.

**Independent Test**: Execute `docker-compose exec php php bin/console doctrine:migrations:migrate` and confirm it runs without connection errors.

**Acceptance Scenarios**:

1. **Given** the PHP and database containers are running, **When** the developer runs `doctrine:migrations:migrate`, **Then** all pending migrations apply successfully
2. **Given** the database is up and migrations are up to date, **When** the developer runs the migrate command again, **Then** the output reports "No migrations to execute" without error

---

### User Story 3 - Compile Front-End Assets (Priority: P2)

A developer can install Node.js dependencies and compile front-end assets from inside the Node.js container.

**Why this priority**: Required for working on Bootstrap/design system UI. Can be developed independently of PHP backend work.

**Independent Test**: Run `npm install` then `npm run build` inside the Node.js container and verify compiled assets appear in the expected output directory.

**Acceptance Scenarios**:

1. **Given** the Node.js container is running, **When** the developer runs `npm install`, **Then** all packages install successfully with no unresolved peer dependency errors
2. **Given** dependencies are installed, **When** the developer runs `npm run build`, **Then** compiled assets are generated in the `public/` directory
3. **Given** the developer is working on CSS/JS, **When** they run `npm run watch`, **Then** the file watcher starts and recompiles on each save

---

### User Story 4 - Persist Data Across Restarts (Priority: P2)

Database data survives a `docker-compose down` and `docker-compose up` cycle without requiring re-seeding.

**Why this priority**: Prevents developer productivity loss from repeated data setup. Independent of application features.

**Independent Test**: Create a record via the app, run `docker-compose down`, run `docker-compose up -d`, then verify the record still exists.

**Acceptance Scenarios**:

1. **Given** the developer has inserted data into the local database, **When** they run `docker-compose down` followed by `docker-compose up -d`, **Then** all previously inserted data is still present
2. **Given** the developer wants a clean slate, **When** they run `docker-compose down -v`, **Then** all data volumes are removed and the database starts empty

---

### Edge Cases

- What happens when port 8000 or 5432 is already in use on the host machine?
- How does the environment behave when `.env.local` is missing entirely?
- What happens if the developer runs `docker-compose up` without having pulled the required Docker images?
- How are file permission issues handled between the host filesystem and the PHP container (especially for `var/` and `public/` directories)?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The environment MUST start all required services (PHP, database, Node.js, web server) with a single `docker-compose up -d` command
- **FR-002**: PHP container MUST use version 8.3, matching the Platform.sh production environment exactly
- **FR-003**: Database container MUST use PostgreSQL 16, matching the Platform.sh production environment exactly
- **FR-004**: Node.js container MUST use version 20 LTS, matching the Platform.sh production build environment exactly
- **FR-005**: PHP container MUST have the following extensions installed: `pdo_pgsql`, `intl`, `opcache`, `apcu`
- **FR-006**: PHP container MUST include Composer for dependency management
- **FR-007**: Database credentials and connection parameters MUST be configurable via a `.env.local` file not committed to version control
- **FR-008**: Database data MUST persist between `docker-compose down` / `docker-compose up` cycles via a named Docker volume
- **FR-009**: The web server container MUST route HTTP requests to the PHP-FPM container
- **FR-010**: The Symfony application MUST be accessible via `http://localhost:8000`
- **FR-011**: The Node.js container MUST be able to install packages and compile assets via `npm install` and `npm run build`
- **FR-012**: Docker Compose configuration files MUST be isolated to development only and MUST NOT modify or reference Platform.sh configuration files (`.platform.app.yaml`, `.platform/services.yaml`, `.platform/routes.yaml`)
- **FR-013**: A `.env.local.example` file MUST be provided with all required variables and placeholder values to guide new developers

### Key Entities *(include if feature involves data)*

- **docker-compose.yaml**: Root-level orchestration file defining all services, volumes, and networks for local development
- **docker/ directory**: Optional directory containing custom Dockerfiles and service-specific configuration (Nginx config, PHP ini overrides)
- **.env.local**: Developer-local environment variables (gitignored); defines database DSN and credentials
- **.env.local.example**: Committed template of `.env.local` with placeholder values
- **Named Volume**: Docker-managed persistent storage for the PostgreSQL data directory

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A developer with Docker installed can have the full local stack running within 5 minutes of cloning the repository
- **SC-002**: All five acceptance criteria from the original specification pass without manual intervention
- **SC-003**: A developer with no prior knowledge of the project can set up the environment using only the README and `.env.local.example`, without asking for help
- **SC-004**: Data inserted into the local database survives at least 10 consecutive down/up cycles without loss
- **SC-005**: Asset compilation produces output identical to the Platform.sh build hook (`npm ci && npm run build`) with no additional configuration

## Assumptions

- Developers have Docker Desktop (or Docker Engine + Compose plugin) installed on their machine
- The host machine exposes port 8000 for the web server and port 5432 for direct database access (for GUI tools like TablePlus or pgAdmin)
- The project uses Nginx (not Apache or Symfony CLI) as the local web server to match a typical FPM deployment model
- `npm` is the package manager (not Yarn), consistent with the `npm ci` call in `.platform.app.yaml`
- The `var/` and `public/` mount paths defined in `.platform.app.yaml` are mirrored in the Docker volume mounts so file permissions work correctly
- Mobile or staging environment Docker configuration is out of scope; this spec covers local development only
- The `.env.local` file approach follows the existing Symfony dotenv convention already present in the project
