#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$PROJECT_ROOT"

fail() {
  echo "Makefile workflow regression: $1" >&2
  exit 1
}

assert_contains() {
  local output=$1
  local expected=$2
  local label=$3

  [[ "$output" == *"$expected"* ]] || fail "$label does not contain: $expected"
}

for target in build up down stop clear shell yii composer rector cs-fix psalm composer-dependency-analyser; do
  target_output=$(make --no-print-directory -n "$target")
  assert_contains "$target_output" "docker compose -f compose.yml" "make $target"
done

test_output=$(make --no-print-directory -n test)
assert_contains "$test_output" "COMPOSE_PROJECT_NAME=yii3-template-test" "make test"
assert_contains "$test_output" "DB_PORT=33060" "make test"
assert_contains "$test_output" "build app" "make test"
assert_contains "$test_output" "up -d --wait db" "make test"
assert_contains "$test_output" "./yii migrate:up -y" "make test"
assert_contains "$test_output" "./vendor/bin/codecept run" "make test"
assert_contains "$test_output" "down --volumes --remove-orphans" "make test"

override_output=$(make --no-print-directory -n test \
  TEST_COMPOSE_PROJECT_NAME=custom-test \
  TEST_DB_PORT=34000)
assert_contains "$override_output" "COMPOSE_PROJECT_NAME=custom-test" "make test override"
assert_contains "$override_output" "DB_PORT=34000" "make test override"

if grep -Eq 'docker/(compose\.yml|dev/compose\.yml|test/compose\.yml)|docker stack|PROD_SSH' Makefile; then
  fail "Makefile still references a legacy Compose or Docker Swarm"
fi

for legacy_file in \
  docker/.env \
  docker/compose.yml \
  docker/dev/compose.yml \
  docker/test/compose.yml; do
  [[ ! -e "$legacy_file" ]] || fail "legacy file still exists: $legacy_file"
done

for legacy_target in prod-build prod-push prod-deploy; do
  if make --no-print-directory -n "$legacy_target" >/dev/null 2>&1; then
    fail "legacy target still exists: $legacy_target"
  fi
done

echo "Makefile workflow: OK"
