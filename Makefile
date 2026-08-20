CLI_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
$(eval $(sort $(subst :,\:,$(CLI_ARGS))):;@:)

PRIMARY_GOAL := $(firstword $(MAKECMDGOALS))
ifeq ($(PRIMARY_GOAL),)
    PRIMARY_GOAL := help
endif

# Current user ID and group ID except MacOS where it conflicts with Docker abilities.
# The canonical root compose consumes LOCAL_UID/LOCAL_GID as build args.
ifeq ($(shell uname), Darwin)
    export LOCAL_UID=1000
    export LOCAL_GID=1000
else
    export LOCAL_UID=$(shell id -u)
    export LOCAL_GID=$(shell id -g)
endif

DOCKER_COMPOSE := docker compose -f compose.yml
TEST_COMPOSE_PROJECT_NAME ?= yii3-template-test
TEST_DB_PORT ?= 33060
DOCKER_COMPOSE_TEST := COMPOSE_PROJECT_NAME=$(TEST_COMPOSE_PROJECT_NAME) DB_PORT=$(TEST_DB_PORT) docker compose -f compose.yml
DOCKER_COMPOSE_TEST_RUN := $(DOCKER_COMPOSE_TEST) run --rm -e APP_ENV=test -e APP_DEBUG=false
TRIVY_VERSION := 0.71.2
TRIVY_IMAGE := aquasec/trivy:${TRIVY_VERSION}
TRIVY_APP_IMAGE := yii3-template-app:latest
TRIVY_PROD_IMAGE := yii3-template-prod:local
TRIVY_CACHE_DIR := $(CURDIR)/.cache/trivy
TRIVY_RUN := docker run --rm -v /var/run/docker.sock:/var/run/docker.sock -v "$(CURDIR):/work" -v "$(TRIVY_CACHE_DIR):/root/.cache/trivy" -w /work $(TRIVY_IMAGE)
TRIVY_IMAGE_SCAN_FLAGS := --format table --exit-code 0 --severity UNKNOWN,LOW,MEDIUM,HIGH,CRITICAL --scanners vuln,misconfig,secret

#
# Development
#

ifeq ($(PRIMARY_GOAL),build)
build: ## Build docker images
	$(DOCKER_COMPOSE) build $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),up)
up: ## Up the dev environment
	$(DOCKER_COMPOSE) up -d --remove-orphans
endif

ifeq ($(PRIMARY_GOAL),down)
down: ## Down the dev environment
	$(DOCKER_COMPOSE) down --remove-orphans
endif

ifeq ($(PRIMARY_GOAL),stop)
stop: ## Stop the dev environment
	$(DOCKER_COMPOSE) stop
endif

ifeq ($(PRIMARY_GOAL),clear)
clear: ## Remove development docker containers and volumes
	$(DOCKER_COMPOSE) down --volumes --remove-orphans
endif

ifeq ($(PRIMARY_GOAL),shell)
shell: ## Get into container shell
	$(DOCKER_COMPOSE) exec app /bin/bash
endif

ifeq ($(PRIMARY_GOAL),yii)
yii: ## Execute Yii command
	$(DOCKER_COMPOSE) run --rm app ./yii $(CLI_ARGS)
.PHONY: yii
endif

ifeq ($(PRIMARY_GOAL),composer)
composer: ## Run Composer
	$(DOCKER_COMPOSE) run --rm --no-deps app composer $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),rector)
rector: ## Run Rector
	$(DOCKER_COMPOSE) run --rm --no-deps app ./vendor/bin/rector $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),cs-fix)
cs-fix: ## Run PHP CS Fixer
	$(DOCKER_COMPOSE) run --rm --no-deps app ./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --diff
endif

#
# Tests and analysis
#

ifeq ($(PRIMARY_GOAL),test)
test: ## Run Codeception in an isolated Docker Compose project
	@set -eu; \
		cleanup() { $(DOCKER_COMPOSE_TEST) down --volumes --remove-orphans; }; \
		trap 'status=$$?; trap - EXIT; cleanup || true; exit $$status' EXIT; \
		cleanup; \
		$(DOCKER_COMPOSE_TEST) build app; \
		$(DOCKER_COMPOSE_TEST) up -d --wait db; \
		$(DOCKER_COMPOSE_TEST_RUN) -e XDEBUG_MODE=off app ./yii migrate:up -y; \
		$(DOCKER_COMPOSE_TEST_RUN) -e XDEBUG_MODE=off app ./vendor/bin/codecept run $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),test-coverage)
test-coverage: ## Run Codeception coverage in an isolated Docker Compose project
	@set -eu; \
		cleanup() { $(DOCKER_COMPOSE_TEST) down --volumes --remove-orphans; }; \
		trap 'status=$$?; trap - EXIT; cleanup || true; exit $$status' EXIT; \
		cleanup; \
		$(DOCKER_COMPOSE_TEST) build app; \
		$(DOCKER_COMPOSE_TEST) up -d --wait db; \
		$(DOCKER_COMPOSE_TEST_RUN) -e XDEBUG_MODE=off app ./yii migrate:up -y; \
		$(DOCKER_COMPOSE_TEST_RUN) -e XDEBUG_MODE=coverage app ./vendor/bin/codecept run --coverage --coverage-html --disable-coverage-php $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),codecept)
codecept: ## Run Codeception
	$(DOCKER_COMPOSE_TEST_RUN) -e XDEBUG_MODE=off app ./vendor/bin/codecept $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),psalm)
psalm: ## Run Psalm
	$(DOCKER_COMPOSE) run --rm --no-deps app ./vendor/bin/psalm $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),composer-dependency-analyser)
composer-dependency-analyser: ## Run Composer Dependency Analyser
	$(DOCKER_COMPOSE) run --rm --no-deps app ./vendor/bin/composer-dependency-analyser --config=composer-dependency-analyser.php $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),trivy)
trivy: ## Run Trivy filesystem and configuration scans
	$(MAKE) trivy-fs
	$(MAKE) trivy-config
endif

ifeq ($(PRIMARY_GOAL),trivy-fs)
trivy-fs: ## Run Trivy filesystem scan
	$(TRIVY_RUN) fs --config trivy.yaml --scanners vuln,secret .
endif

ifeq ($(PRIMARY_GOAL),trivy-config)
trivy-config: ## Run Trivy configuration scan
	$(TRIVY_RUN) fs --config trivy.yaml --scanners misconfig .
endif

ifeq ($(PRIMARY_GOAL),trivy-image)
trivy-image: ## Run Trivy image scan for yii3-template-app:latest
	$(TRIVY_RUN) image $(TRIVY_IMAGE_SCAN_FLAGS) $(TRIVY_APP_IMAGE)
endif

ifeq ($(PRIMARY_GOAL),trivy-gate)
trivy-gate: ## Run the blocking Trivy gate (fixable HIGH/CRITICAL, fs + prod image) as in CI
	$(TRIVY_RUN) fs --config trivy.yaml --scanners vuln --severity HIGH,CRITICAL --ignore-unfixed --exit-code 1 .
	DOCKER_BUILDKIT=1 docker build --file docker/Dockerfile --target prod --pull --tag $(TRIVY_PROD_IMAGE) .
	$(TRIVY_RUN) image --scanners vuln --severity HIGH,CRITICAL --ignore-unfixed --exit-code 1 $(TRIVY_PROD_IMAGE)
endif

#
# Other
#

ifeq ($(PRIMARY_GOAL),help)
# Output the help for each task, see https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
help: ## This help.
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
endif
