# Implementation Plan: Local Docker Development Environment

**Branch**: `003-local-docker-env` | **Date**: 2026-05-23 | **Spec**: specs/003-local-docker-env/spec.md

**Input**: Feature specification from `/specs/003-local-docker-env/spec.md`

## Summary

Extend the Symfony Flex–generated `compose.yaml` skeleton with three additional services (PHP 8.3 FPM, Nginx, Node.js 20) to form a complete local development environment matching Platform.sh production versions. Provide a custom PHP Dockerfile with all required extensions, an Nginx vhost config, a `.env.local.example` template, and a README quickstart covering setup and Google OAuth2 redirect URI registration.

## Technical Context

**Language/Version**: PHP 8.3 FPM (Alpine), Node.js 20 LTS (Alpine), Nginx 1.26 (Alpine)

**Primary Dependencies**: Docker Compose v2 plugin (Docker Engine 20.10+ / Docker Desktop 4.0+); macOS and Linux only

**Storage**: PostgreSQL 16 — named volume `database_data` (existing in `compose.yaml`)

**Testing**: Manual acceptance scenarios — `docker compose up -d`, `docker compose ps`, browser at `http://localhost:8000`, `doctrine:migrations:migrate`, `npm install && npm run build` inside the `node` container

**Target Platform**: Developer workstation — macOS and Linux (Windows/WSL2 explicitly out of scope)

**Project Type**: Local development infrastructure — DevOps configuration files, no application code

**Performance Goals**: Full stack starts within 2 minutes with pre-pulled images (SC-001: within 5 min from clone, cold image pull excluded)

**Constraints**:
- Docker Compose files MUST NOT touch or reference Platform.sh config (FR-012)
- PHP-FPM MUST run as host UID:GID (FR-005a)
- Xdebug disabled by default; enabled via `XDEBUG_MODE=debug` in `.env.local` (FR-005b)
- PostgreSQL MUST pass health check before PHP-FPM starts (FR-001a)
- `compose.override.yaml` and `.env.local` MUST be gitignored (FR-013a)

**Scale/Scope**: 4 Docker services, 1 named volume, ~6 new configuration files

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I — Complémentarité Stricte | ✅ PASS | Pure DevOps infrastructure; no application features added |
| II — Architecture Symfony LTS | ✅ PASS | No backend code changes; FR-012 prohibits touching Platform.sh files; Docker DB service mirrors the **existing** PostgreSQL 16 in `.platform/services.yaml` — no new managed service added |
| III — Workflow de Validation | N/A | No content workflow changes |
| IV — RBAC Trois Niveaux | N/A | No route or security changes |
| V — Sécurité et Couverture | ✅ PASS | No application business logic; `.env.local` gitignored; credentials never committed; acceptance scenarios in `spec.md` cover verification |

**Note on Principle II (infrastructure sync rule)**: The constitution requires Platform.sh files be updated when infrastructure is added. The local Docker PostgreSQL 16 service mirrors the **existing** managed service already declared in `.platform/services.yaml`. No new production services are introduced. No Platform.sh file updates required.

**Post-design re-check**: All five gates still pass. The `docker/` directory and `compose.yaml` changes are isolated to local development and do not affect any Symfony service, Doctrine entity, or Platform.sh configuration.

## Project Structure

### Documentation (this feature)

```text
specs/003-local-docker-env/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   ├── env-variables.md     # .env.local variable contract
│   └── service-endpoints.md # Running stack URL/port contract
└── tasks.md             # Phase 2 output (/speckit-tasks command)
```

### Source Code (repository root)

```text
compose.yaml                        # Extended: add php, nginx, node services; expand db port
compose.override.yaml               # NOT a committed deliverable — developer-created locally for port overrides; gitignored via FR-013a
docker/
├── php/
│   ├── Dockerfile                  # PHP 8.3-fpm-alpine + extensions + Xdebug
│   └── conf.d/
│       └── xdebug.ini              # Xdebug 3 config (mode=off default)
└── nginx/
    └── default.conf                # Nginx vhost: public/ docroot, index.php passthrough
.env.local.example                  # Committed template with all required variables
.env.local                          # Gitignored — developer-local actual values
README.md                           # New or updated — quickstart + Google OAuth2 setup
```

**Structure Decision**: Single project layout (existing Symfony structure). Docker configuration isolated in `docker/` subdirectory per Symfony convention. `compose.yaml` at root uses the modern Docker Compose v2 file naming already established by Symfony Flex. No new `src/`, `tests/`, or application directories — this feature is purely infrastructure configuration.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No violations. No complexity entries required.
