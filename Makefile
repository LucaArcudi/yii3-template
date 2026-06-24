CLI_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
$(eval $(sort $(subst :,\:,$(CLI_ARGS))):;@:)

PRIMARY_GOAL := $(firstword $(MAKECMDGOALS))
ifeq ($(PRIMARY_GOAL),)
    PRIMARY_GOAL := help
endif

include docker/.env

# Current user ID and group ID except MacOS where it conflicts with Docker abilities
ifeq ($(shell uname), Darwin)
    export UID=1000
    export GID=1000
else
    export UID=$(shell id -u)
    export GID=$(shell id -g)
endif

export COMPOSE_PROJECT_NAME=${STACK_NAME}
DOCKER_COMPOSE_DEV := docker compose -f docker/compose.yml -f docker/dev/compose.yml
DOCKER_COMPOSE_TEST := docker compose -f docker/compose.yml -f docker/test/compose.yml
TRIVY_VERSION := 0.71.2
TRIVY_IMAGE := aquasec/trivy:${TRIVY_VERSION}
TRIVY_APP_IMAGE := yii3-template-app:latest
TRIVY_CACHE_DIR := $(CURDIR)/.cache/trivy
TRIVY_RUN := docker run --rm -v /var/run/docker.sock:/var/run/docker.sock -v "$(CURDIR):/work" -v "$(TRIVY_CACHE_DIR):/root/.cache/trivy" -w /work $(TRIVY_IMAGE)
TRIVY_IMAGE_SCAN_FLAGS := --format table --exit-code 0 --severity UNKNOWN,LOW,MEDIUM,HIGH,CRITICAL --scanners vuln,misconfig,secret

#
# Development
#

ifeq ($(PRIMARY_GOAL),build)
build: ## Build docker images
	$(DOCKER_COMPOSE_DEV) build $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),up)
up: ## Up the dev environment
	$(DOCKER_COMPOSE_DEV) up -d --remove-orphans
endif

ifeq ($(PRIMARY_GOAL),down)
down: ## Down the dev environment
	$(DOCKER_COMPOSE_DEV) down --remove-orphans
endif

ifeq ($(PRIMARY_GOAL),stop)
stop: ## Stop the dev environment
	$(DOCKER_COMPOSE_DEV) stop
endif

ifeq ($(PRIMARY_GOAL),clear)
clear: ## Remove development docker containers and volumes
	$(DOCKER_COMPOSE_DEV) down --volumes --remove-orphans
endif

ifeq ($(PRIMARY_GOAL),shell)
shell: ## Get into container shell
	$(DOCKER_COMPOSE_DEV) exec app /bin/bash
endif

ifeq ($(PRIMARY_GOAL),yii)
yii: ## Execute Yii command
	$(DOCKER_COMPOSE_DEV) run --rm app ./yii $(CLI_ARGS)
.PHONY: yii
endif

ifeq ($(PRIMARY_GOAL),composer)
composer: ## Run Composer
	$(DOCKER_COMPOSE_DEV) run --rm app composer $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),rector)
rector: ## Run Rector
	$(DOCKER_COMPOSE_DEV) run --rm app ./vendor/bin/rector $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),cs-fix)
cs-fix: ## Run PHP CS Fixer
	$(DOCKER_COMPOSE_DEV) run --rm app ./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --diff
endif

#
# Tests and analysis
#

ifeq ($(PRIMARY_GOAL),test)
test:
	$(DOCKER_COMPOSE_TEST) run --rm app ./vendor/bin/codecept run $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),test-coverage)
test-coverage:
	$(DOCKER_COMPOSE_TEST) run --rm app ./vendor/bin/codecept run --coverage --coverage-html --disable-coverage-php
endif

ifeq ($(PRIMARY_GOAL),codecept)
codecept: ## Run Codeception
	$(DOCKER_COMPOSE_TEST) run --rm app ./vendor/bin/codecept $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),psalm)
psalm: ## Run Psalm
	$(DOCKER_COMPOSE_DEV) run --rm app ./vendor/bin/psalm $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),composer-dependency-analyser)
composer-dependency-analyser: ## Run Composer Dependency Analyser
	$(DOCKER_COMPOSE_DEV) run --rm app ./vendor/bin/composer-dependency-analyser --config=composer-dependency-analyser.php $(CLI_ARGS)
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

#
# Production
#

ifeq ($(PRIMARY_GOAL),prod-build)
prod-build: ## PROD | Build an image
	docker build --file docker/Dockerfile --target prod --pull -t ${IMAGE}:${IMAGE_TAG} .
endif

ifeq ($(PRIMARY_GOAL),prod-push)
prod-push: ## PROD | Push image to repository
	docker push ${IMAGE}:${IMAGE_TAG}
endif

ifeq ($(PRIMARY_GOAL),prod-deploy)
prod-deploy: ## PROD | Deploy to production
	set -euo pipefail \
	docker -H ${PROD_SSH} stack deploy --prune --detach=false --with-registry-auth -c docker/compose.yml -c docker/prod/compose.yml ${STACK_NAME} 2>&1 | tee deploy.log \
	if grep -qiE 'rollback:|update rolled back' deploy.log then \
		FAILED_TASK_ID="$(grep -oiE 'task[[:space:]]+[a-z0-9]+' deploy.log | head -n 1 | awk '{print $2}')" \
		if [ -n "${FAILED_TASK_ID}" ]; then \
			echo "Docker Swarm update rolled back; failing job. Failed task ID: ${FAILED_TASK_ID}" \
			echo "--- docker service logs (${FAILED_TASK_ID}) ---" \
			docker -H ${PROD_SSH} service logs --timestamps --tail 500 "${FAILED_TASK_ID}" || true \
		else \
			echo 'Docker Swarm update rolled back; failing job. Failed task ID: not found in deploy output.' \
		fi \
		exit 1 \
	fi
endif

#
# Other
#

ifeq ($(PRIMARY_GOAL),help)
# Output the help for each task, see https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
help: ## This help.
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
endif
