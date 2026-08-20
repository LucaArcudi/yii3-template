#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$PROJECT_ROOT"

WORKFLOW=.github/workflows/cd.yml

fail() {
  echo "CD environment: $1" >&2
  exit 1
}

[[ -f "$WORKFLOW" ]] || fail "$WORKFLOW non trovato"

environment_count=$(
  awk '
    /^  deploy:$/ {
      in_deploy = 1
      next
    }
    in_deploy && /^  [[:alnum:]_-]+:$/ {
      in_deploy = 0
    }
    in_deploy && /^    environment: production$/ {
      count++
    }
    END {
      print count + 0
    }
  ' "$WORKFLOW"
)

[[ "$environment_count" -eq 1 ]] \
  || fail "il job deploy deve dichiarare una sola volta environment: production"

echo "CD environment: OK"
