COMPOSE = docker compose
APP = $(COMPOSE) exec app

# Test runs always target the dockerized Postgres, regardless of what the
# mounted .env points at (Herd/DBngin users point .env at their host DB).
TEST_ENV = -e DB_HOST=postgres -e DB_PORT=5432 -e DB_USERNAME=root -e DB_PASSWORD=secret

.PHONY: up down build setup dev tinker test queue bash logs

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

build:
	$(COMPOSE) build

## One-command onboarding for new contributors.
setup:
	cp -n .env.example .env || true
	$(COMPOSE) up -d --build
	$(APP) composer install
	$(APP) php artisan key:generate
	$(APP) php artisan migrate
	$(APP) npm install
	@echo "Done. Run 'make dev' for Vite, then open http://localhost:8000"

## Vite dev server inside the app container (PHP is present, so the
## Wayfinder plugin works). Host-based devs can keep running `npm run dev`
## on their machine instead.
dev:
	$(APP) npm run dev -- --host 0.0.0.0

tinker:
	$(APP) php artisan tinker

test:
	$(COMPOSE) exec $(TEST_ENV) app php artisan test --parallel

queue:
	$(APP) php artisan queue:listen

bash:
	$(APP) /bin/bash

logs:
	$(COMPOSE) logs -f app
