#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$PROJECT_ROOT"

fail() {
  echo "Supply-chain pins: $1" >&2
  exit 1
}

while IFS= read -r use_line; do
  action_ref=${use_line#*@}
  action_ref=${action_ref%% *}
  [[ "$action_ref" =~ ^[0-9a-f]{40}$ ]] \
    || fail "GitHub Action non fissata a commit: $use_line"
done < <(grep -hRE '^[[:space:]]*uses:' .github/workflows)

while IFS= read -r from_line; do
  [[ "$from_line" =~ @sha256:[0-9a-f]{64}([[:space:]]+AS[[:space:]]+[^[:space:]]+)?$ ]] \
    || fail "base Dockerfile non fissata a digest: $from_line"
done < <(grep -hE '^FROM [^[:space:]]+/' docker/Dockerfile)

if grep -hE \
  '^[[:space:]]*image: (mysql|prom/|grafana/|gcr\.io/cadvisor/|lucaslorentz/)[^[:space:]]+$' \
  compose.yml docker/prod/compose.yml docker/proxy/compose.yml docker/monitoring/compose.yml \
  | grep -Ev '@sha256:[0-9a-f]{64}$' > /dev/null; then
  fail "immagine Compose operativa non fissata a digest"
fi

grep -Eq '^TRIVY_IMAGE := aquasec/trivy:\$\{TRIVY_VERSION\}@sha256:[0-9a-f]{64}$' Makefile \
  || fail "immagine Trivy locale non fissata a digest"

for ci_image in \
  prom/prometheus:v3.1.0 \
  grafana/loki:3.5.0 \
  grafana/alloy:v1.8.1; do
  grep -Eq "${ci_image}@sha256:[0-9a-f]{64}" .github/workflows/ci.yml \
    || fail "immagine CI ${ci_image} non fissata a digest"
done

for image_var in ACTIONLINT_IMAGE SHELLCHECK_IMAGE; do
  grep -Eq "^${image_var}=.*@sha256:[0-9a-f]{64}\\}?[[:space:]]*$" \
    tests/Tooling/validate-operational-files.sh \
    || fail "${image_var} non fissata a digest"
done

echo "Supply-chain pins: OK"
