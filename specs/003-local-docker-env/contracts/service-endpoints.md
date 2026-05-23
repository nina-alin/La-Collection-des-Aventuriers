# Contract: Service Endpoints

**Feature**: 003-local-docker-env
**Type**: Running stack interface contract

Ports and URLs exposed by the running `docker compose up -d` stack.

---

## Host-Accessible Endpoints

| Service | Default URL / Port | Override Variable | Protocol |
|---------|-------------------|-------------------|----------|
| Symfony application | `http://localhost:8000` | `APP_PORT` | HTTP |
| PostgreSQL (direct access) | `localhost:5432` | `DB_PORT` | PostgreSQL wire protocol |

---

## Inter-Container Endpoints (internal Docker network)

These are used by services to communicate within the Docker Compose network. Not accessible from the host.

| From | To | Address | Protocol |
|------|----|---------|----------|
| `nginx` | `php` | `php:9000` | FastCGI |
| `php` | `database` | `database:5432` | PostgreSQL |

---

## Service Health

| Service | Health Check | Ready When |
|---------|-------------|------------|
| `database` | `pg_isready -d app -U app` | All other services may depend on it |
| `php` | None (depends on `database` healthy) | After `database` passes health check |
| `nginx` | None | After `php` starts |
| `node` | None | Immediately on start (no startup dependency) |

---

## Verification Commands

```bash
# All containers running
docker compose ps

# Application reachable
curl -I http://localhost:8000

# Database reachable from PHP container
docker compose exec php php bin/console doctrine:schema:validate

# Database reachable from host (requires psql or GUI tool)
psql -h localhost -p 5432 -U app -d app
```
