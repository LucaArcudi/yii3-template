#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$PROJECT_ROOT"

ACTIONLINT_IMAGE=${ACTIONLINT_IMAGE:-rhysd/actionlint:1.7.12@sha256:b1934ee5f1c509618f2508e6eb47ee0d3520686341fec936f3b79331f9315667}
SHELLCHECK_IMAGE=${SHELLCHECK_IMAGE:-koalaman/shellcheck:v0.11.0@sha256:61862eba1fcf09a484ebcc6feea46f1782532571a34ed51fedf90dd25f925a8d}

mapfile -d '' SHELL_FILES < <(find scripts tests -type f -name '*.sh' -print0 | sort -z)

docker run --rm \
  -v "$PROJECT_ROOT:/repo" \
  -w /repo \
  "$ACTIONLINT_IMAGE" \
  -color

docker run --rm \
  -v "$PROJECT_ROOT:/repo" \
  -w /repo \
  "$SHELLCHECK_IMAGE" \
  "${SHELL_FILES[@]}"

docker compose --env-file .env.example -f compose.yml config --quiet
docker compose \
  --env-file .env.prod.example \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.example.yml \
  config --quiet
docker compose -f docker/proxy/compose.yml config --quiet

GF_ADMIN_PASSWORD=validation-only \
MYSQLD_EXPORTER_PASSWORD=validation-only \
TELEGRAM_BOT_TOKEN=validation-only \
TELEGRAM_CHAT_ID=validation-only \
  docker compose \
    --env-file docker/monitoring/.env.example \
    -f docker/monitoring/compose.yml \
    config --quiet

echo "File operativi: OK"
