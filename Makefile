SHELL := /bin/bash
# === Variables ===
PHP_BIN = php
CONSOLE = $(PHP_BIN) bin/console
COMPOSER = composer
PNPM = pnpm
PHP_CS_FIXER = $(PHP_BIN) tools/php-cs-fixer/vendor/bin/php-cs-fixer
TWIG_CS_FIXER = $(PHP_BIN) tools/twig-cs-fixer/vendor/bin/twig-cs-fixer
PHPSTAN = $(PHP_BIN) tools/phpstan/vendor/bin/phpstan
RECTOR = $(PHP_BIN) tools/rector/vendor/bin/rector

# === Build Commands ===
pnpm-setup: ## Setup pnpm via corepack (usage: make pnpm-setup VERSION=10.11.0)
	@if [ -z "$(VERSION)" ]; then \
		echo "Error: Please specify a version. Usage: make pnpm-setup VERSION=x.y.z"; \
		exit 1; \
	fi
	corepack enable
	corepack prepare pnpm@$(VERSION) --activate
	@echo "PNPM $(VERSION) has been activated via corepack"

build:
	$(PNPM) run build

production:
	$(PNPM) install --frozen-lockfile
	$(PNPM) run build

watch:
	$(PNPM) run dev

dev:
	$(PNPM) run dev

# === Install & Update ===
setup-dirs: ## Create required runtime directories
	@mkdir -p var/uploads/tus_tmp var/uploads/transfers var/cache/tus var/log
	@echo "✅ Runtime directories created"

install-dev:
	$(COMPOSER) install
	$(COMPOSER) install --working-dir=tools/php-cs-fixer
	$(COMPOSER) install --working-dir=tools/twig-cs-fixer
	$(COMPOSER) install --working-dir=tools/rector
	$(COMPOSER) install --working-dir=tools/phpstan
	$(PNPM) install
	make setup-dirs
	make migrate
	make dev

install-prod:
	$(COMPOSER) install --no-dev --optimize-autoloader
	$(PNPM) install --frozen-lockfile
	make setup-dirs
	make migrate-f
	make build
	make cc-prod

update:
	$(COMPOSER) update
	$(COMPOSER) update --working-dir=tools/php-cs-fixer
	$(COMPOSER) update --working-dir=tools/twig-cs-fixer
	$(COMPOSER) update --working-dir=tools/rector
	$(COMPOSER) update --working-dir=tools/phpstan

autoload: ## Regenerate autoloading according to PSR4
	$(COMPOSER) dump-autoload

autoload-opti: ## Optimize autoloading for caching
	$(COMPOSER) dump-autoload --optimize

outdated: ## Show outdated packages
	$(COMPOSER) outdated

# === Symfony Cache ===
cc-dev:
	$(CONSOLE) cache:clear

cc-prod:
	@echo "Clearing and regenerating production cache..."
	APP_ENV=prod APP_DEBUG=0 $(CONSOLE) cache:clear --env=prod
	@APP_ENV=prod APP_DEBUG=0 $(CONSOLE) about --env=prod >/dev/null 2>&1 || (echo "❌ Cache verification failed: application could not boot" && exit 1)
	@echo "✅ Production cache regenerated successfully"

warmup:
	$(CONSOLE) cache:warmup

purge:
	rm -rf var/cache/* var/logs/*

# === Docker (Mailpit) ===
docker-up:
	docker compose up -d mailer

docker-down:
	docker compose stop mailer

# === Symfony ===
start:
	@docker compose up -d mailer 2>/dev/null || true
	symfony server:start

start-no-tls:
	symfony server:start --no-tls -d

stop:
	symfony server:stop
	@docker compose stop mailer 2>/dev/null || true

start-dev-worker: ## Start the messenger worker (async + scheduler)
	@touch var/.messenger-dev-worker-running
	@trap 'rm -f var/.messenger-dev-worker-running; exit' INT TERM EXIT; \
	while true; do $(CONSOLE) messenger:consume async scheduler_main -vv --time-limit=3600 --memory-limit=512M || sleep 1; done

routes:
	$(CONSOLE) debug:router --show-controllers

sf:
	$(CONSOLE)

# === Database ===
migration:
	$(CONSOLE) make:migration

migrate:
	$(CONSOLE) doctrine:migrations:migrate

migrate-f:
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

migrate-prev:
	$(CONSOLE) doctrine:migrations:migrate prev

migration-generate:
	$(CONSOLE) doctrine:migrations:generate

migration-clean:
	$(CONSOLE) doctrine:migrations:diff

schema-validate:
	$(CONSOLE) doctrine:schema:validate -vvv

# === Tests ===
test:
	$(PHP_BIN) bin/phpunit --testdox --debug

# === Code Quality ===
stan:
	$(PHPSTAN) analyse -c tools/phpstan/phpstan.neon --memory-limit 1G

lint-php:
	$(PHP_CS_FIXER) fix --dry-run --config=.php-cs-fixer.dist.php

lint-js:
	$(PNPM) eslint --config eslint.config.cjs

lint-twig:
	$(TWIG_CS_FIXER)

rector:
	$(RECTOR) process --dry-run -c tools/rector/rector.php

fix-php:
	$(PHP_CS_FIXER) fix --config=.php-cs-fixer.dist.php

fix-js:
	$(PNPM) eslint --config eslint.config.cjs --fix

fix-twig:
	$(TWIG_CS_FIXER) --fix

fix-rector:
	$(RECTOR) process -c tools/rector/rector.php

fix:
	make fix-js
	make fix-twig
	make fix-rector
	make fix-php
	make stan

fd: ## Fix code and build dev assets
	make fix && make dev

# === Setup ===
setup-env: ## Create .env.local from .env.dev.example template
	@if [ -f .env.local ]; then \
		echo "⚠️  .env.local already exists. Overwrite? (yes/no)"; \
		read -p "" confirm && [ "$$confirm" = "yes" ] || (echo "❌ Cancelled." && exit 1); \
	fi
	cp .env.dev.example .env.local
	@echo "✅ .env.local created from .env.dev.example — edit it with your local values"

fixtures-load:
	$(CONSOLE) doctrine:fixtures:load --no-interaction

fixtures-append:
	$(CONSOLE) doctrine:fixtures:load --append --no-interaction

about:
	$(CONSOLE) about

.PHONY: help
help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-30s\033[0m %s\n", $$1, $$2}'
