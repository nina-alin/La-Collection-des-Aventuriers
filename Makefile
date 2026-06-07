.DEFAULT_GOAL := help

# Panther E2E defaults — override on the command line, e.g.:
#   make test-e2e PANTHER_BASE_URI=http://localhost:8080
PANTHER_BASE_URI    ?= http://localhost:8000
PANTHER_CHROMEDRIVER ?= ./chromedriver

.PHONY: help up down restart ps logs shell db-shell \
        migrate migrate-diff migrate-status migrate-validate \
        fixtures cache cc \
        test test-unit test-functional test-e2e \
        assets-dev assets-watch assets-build \
        install

# ─── Docker ──────────────────────────────────────────────────────────────────

up: ## Start all containers (detached)
	docker compose up -d

down: ## Stop and remove containers
	docker compose down

restart: ## Restart all containers
	docker compose restart

ps: ## Show running containers
	docker compose ps

logs: ## Follow logs (all services). Usage: make logs s=php
	docker compose logs -f $(s)

shell: ## Open a shell in the php container
	docker compose exec php sh

db-shell: ## Open psql in the database container
	docker compose exec database psql -U app -d app

# ─── Symfony ─────────────────────────────────────────────────────────────────

cc: ## Clear Symfony cache
	docker compose exec php php bin/console cache:clear

cache: cc ## Alias for cc

install: ## Install PHP and JS dependencies
	docker compose exec php composer install
	docker compose run --rm node npm install

# ─── Doctrine ────────────────────────────────────────────────────────────────

migrate: ## Run pending migrations
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

migrate-diff: ## Generate a new migration from entity changes
	docker compose exec php php bin/console doctrine:migrations:diff

migrate-status: ## Show migration status
	docker compose exec php php bin/console doctrine:migrations:status

migrate-validate: ## Validate ORM mappings match DB schema
	docker compose exec php php bin/console doctrine:schema:validate

fixtures: ## Load fixtures (WARNING: clears existing data)
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction

# ─── Tests ───────────────────────────────────────────────────────────────────

test: ## Run all tests
	docker compose exec php php bin/phpunit

test-unit: ## Run unit tests only
	docker compose exec php php bin/phpunit tests/Unit

test-functional: ## Run functional tests only
	docker compose exec php php bin/phpunit tests/Functional

test-e2e: ## Run Panther E2E tests (host only). Override: make test-e2e PANTHER_BASE_URI=http://localhost:8080
	PANTHER_EXTERNAL_BASE_URI=$(PANTHER_BASE_URI) \
	PANTHER_CHROME_DRIVER_BINARY=$(PANTHER_CHROMEDRIVER) \
	PANTHER_NO_SANDBOX=1 \
	php bin/phpunit tests/E2E --no-coverage

# ─── Assets ──────────────────────────────────────────────────────────────────

assets-dev: ## Build assets (development)
	docker compose run --rm node npm run dev

assets-watch: ## Watch and rebuild assets on change
	docker compose run --rm node npm run watch

assets-build: ## Build assets (production)
	docker compose run --rm node npm run build

# ─── Help ────────────────────────────────────────────────────────────────────

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
