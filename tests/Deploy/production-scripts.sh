#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
BACKUP_SCRIPT="${PROJECT_ROOT}/scripts/backup-db.sh"
DEPLOY_SCRIPT="${PROJECT_ROOT}/scripts/deploy.sh"
CHECKOUT_SCRIPT="${PROJECT_ROOT}/scripts/checkout-deploy-commit.sh"
CD_WORKFLOW="${PROJECT_ROOT}/.github/workflows/cd.yml"
DOCKERFILE="${PROJECT_ROOT}/docker/Dockerfile"
TEST_ROOT=$(mktemp -d /tmp/yii3-template-production-scripts.XXXXXX)
DEPLOY_ROOT="${TEST_ROOT}/deploy"
FAKE_BIN="${TEST_ROOT}/bin"
FAKE_DOCKER_LOG="${TEST_ROOT}/docker-args.log"

cleanup() {
  case "$TEST_ROOT" in
    /tmp/yii3-template-production-scripts.*)
      rm -rf -- "$TEST_ROOT"
      ;;
  esac
}
trap cleanup EXIT

fail() {
  echo "Test script produzione: $*" >&2
  exit 1
}

assert_file_contains() {
  local file=$1 expected=$2
  grep -Fq -- "$expected" "$file" \
    || fail "$file non contiene: $expected"
}

# I default preservano l'installazione esistente; il workflow può
# sovrascriverli tramite GitHub Variables dopo la configurazione del repo.
# shellcheck disable=SC2016
assert_file_contains "$BACKUP_SCRIPT" 'DEPLOY_DIR="${DEPLOY_DIR:-/opt/yii3}"'
# shellcheck disable=SC2016
assert_file_contains "$DEPLOY_SCRIPT" 'DEPLOY_DIR="${DEPLOY_DIR:-/opt/yii3}"'
# shellcheck disable=SC2016
assert_file_contains "$DEPLOY_SCRIPT" 'HEALTH_URL="${HEALTH_URL:-http://127.0.0.1:8080/login}"'
# shellcheck disable=SC2016
assert_file_contains "$CHECKOUT_SCRIPT" 'DEPLOY_REMOTE="${DEPLOY_REMOTE:-origin}"'
# shellcheck disable=SC2016
assert_file_contains "$CHECKOUT_SCRIPT" 'DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"'
# shellcheck disable=SC2016
assert_file_contains "$CD_WORKFLOW" 'CONFIG_VPS_SSH_PORT: ${{ vars.VPS_SSH_PORT }}'
# shellcheck disable=SC2016
assert_file_contains "$CD_WORKFLOW" 'VPS_SSH_PORT="${CONFIG_VPS_SSH_PORT:-22}"'
assert_file_contains "$DOCKERFILE" 'HEALTHCHECK --interval=30s'

mkdir -p "$DEPLOY_ROOT/docker/prod" "$FAKE_BIN"
cat > "${DEPLOY_ROOT}/.env.prod" <<'EOF'
DB_USERNAME="raw-user"
DB_DATABASE="raw_database"
DB_PASSWORD="raw=not-used # compose must parse this"
EOF
printf 'services: {}\n' > "${DEPLOY_ROOT}/docker/prod/compose.yml"
printf 'services: {}\n' > "${DEPLOY_ROOT}/docker/prod/compose.local.yml"

cat > "${FAKE_BIN}/docker" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

if [[ " $* " == *" config --environment "* ]]; then
  printf '%s\n' \
    'DB_USERNAME=resolved-user' \
    'DB_DATABASE=resolved_database' \
    'DB_PASSWORD=resolved=p@ss # with spaces'
  exit 0
fi

if [[ " $* " == *" exec -T "* ]]; then
  printf '%s' "${MYSQL_PWD:-}" > "${FAKE_DOCKER_PASSWORD_LOG:?}"
  printf '<%s>\n' "$@" > "${FAKE_DOCKER_LOG:?}"
  printf '%s\n' '-- isolated SQL dump' 'CREATE TABLE restore_probe (id INT);'
  exit 0
fi

printf 'Chiamata docker inattesa: %s\n' "$*" >&2
exit 1
EOF
chmod +x "${FAKE_BIN}/docker"

backup_output=$(PATH="${FAKE_BIN}:$PATH" \
  FAKE_DOCKER_LOG="$FAKE_DOCKER_LOG" \
  FAKE_DOCKER_PASSWORD_LOG="${TEST_ROOT}/docker-password.log" \
  DEPLOY_DIR="$DEPLOY_ROOT" \
  bash "$BACKUP_SCRIPT")

[ "$(stat -c '%a' "${DEPLOY_ROOT}/backups")" = 700 ] \
  || fail "directory backup senza permessi 0700"
mapfile -t dumps < <(find "${DEPLOY_ROOT}/backups" -maxdepth 1 -type f -name 'db_*.sql')
[ "${#dumps[@]}" -eq 1 ] || fail "atteso un solo dump, trovati ${#dumps[@]}"
[ "$(stat -c '%a' "${dumps[0]}")" = 600 ] \
  || fail "dump senza permessi 0600"
grep -Fq -- 'CREATE TABLE restore_probe' "${dumps[0]}" \
  || fail "dump di test vuoto o inatteso"
grep -Fxq -- '<MYSQL_PWD>' "$FAKE_DOCKER_LOG" \
  || fail "MYSQL_PWD non inoltrata per nome al container"
[ "$(cat "${TEST_ROOT}/docker-password.log")" = 'resolved=p@ss # with spaces' ] \
  || fail "password normalizzata da Compose non inoltrata tramite ambiente"
if grep -Fq -- 'resolved=p@ss # with spaces' "$FAKE_DOCKER_LOG"; then
  fail "la password è comparsa negli argomenti del client Docker"
fi
grep -Fxq -- '<-uresolved-user>' "$FAKE_DOCKER_LOG" \
  || fail "utente normalizzato da Compose non inoltrato"
grep -Fxq -- '<resolved_database>' "$FAKE_DOCKER_LOG" \
  || fail "database normalizzato da Compose non inoltrato"
[[ "$backup_output" != *'resolved=p@ss'* ]] \
  || fail "la password è comparsa nell'output del backup"

echo "Test script produzione: OK"
