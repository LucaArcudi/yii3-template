#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$PROJECT_ROOT"

WORKFLOW=.github/workflows/ci.yml

fail() {
  echo "CI image workflow regression: $1" >&2
  exit 1
}

assert_contains() {
  local expected=$1

  grep -Fq -- "$expected" "$WORKFLOW" || fail "missing: $expected"
}

assert_not_contains() {
  local unexpected=$1

  if grep -Fq -- "$unexpected" "$WORKFLOW"; then
    fail "unexpected: $unexpected"
  fi
}

assert_count() {
  local expected=$1
  local count=$2
  local actual

  actual=$(grep -Fc -- "$expected" "$WORKFLOW" || true)
  [[ "$actual" == "$count" ]] || fail "expected $count occurrence(s) of '$expected', found $actual"
}

assert_contains "  image:"
assert_contains "    name: Verify and publish app image"
assert_contains "    needs: test"
assert_contains "          persist-credentials: false"
assert_count "docker build --file docker/Dockerfile --target prod" 1
# I valori cercati sono intenzionalmente letterali: non devono espandersi
# durante questa regressione statica.
# shellcheck disable=SC2016
assert_contains 'docker build --file docker/Dockerfile --target prod --pull --tag "$IMAGE_TAG_SHA" .'
# shellcheck disable=SC2016
assert_contains 'docker run --rm --entrypoint sh "$IMAGE_TAG_SHA" -c'
# shellcheck disable=SC2016
assert_contains 'IMAGE_TAG_SHA: ${{ steps.image-tags.outputs.sha }}'
# shellcheck disable=SC2016
assert_contains 'image-ref: ${{ steps.image-tags.outputs.sha }}'
assert_count "if: github.event_name == 'push' && github.ref == 'refs/heads/main'" 2
# shellcheck disable=SC2016
assert_contains 'docker push "$IMAGE_TAG_SHA"'
# shellcheck disable=SC2016
assert_contains 'docker push "$IMAGE_TAG_LATEST"'
assert_not_contains "  publish-image:"
assert_not_contains "yii3-template-prod:ci-check"

echo "CI image workflow: OK"
