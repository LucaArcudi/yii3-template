#!/usr/bin/env bash

# Disaster-recovery drill su uno stack Compose dedicato. Non usa e non può
# raggiungere il volume dev/prod: project name, porta e volume sono isolati e
# vengono eliminati dal trap anche in caso di errore.
set -euo pipefail
umask 077

PROJECT_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$PROJECT_ROOT"

RESTORE_PROJECT_NAME=${RESTORE_PROJECT_NAME:-yii3-template-restore-test}
RESTORE_DB_PORT=${RESTORE_DB_PORT:-33061}
DB_DATABASE=${DB_DATABASE:-yii3_template}
TEST_ROOT=$(mktemp -d /tmp/yii3-template-restore.XXXXXX)
DUMP_FILE="${TEST_ROOT}/database.sql"

if [[ ! "$RESTORE_PROJECT_NAME" =~ ^yii3-template-restore-[a-z0-9_-]+$ ]]; then
  echo "RESTORE_PROJECT_NAME deve usare il prefisso isolato yii3-template-restore-" >&2
  exit 1
fi
if [[ ! "$RESTORE_DB_PORT" =~ ^[0-9]+$ || ${#RESTORE_DB_PORT} -gt 5 ]] \
  || (( 10#$RESTORE_DB_PORT < 1024 || 10#$RESTORE_DB_PORT > 65535 )); then
  echo "RESTORE_DB_PORT non valida" >&2
  exit 1
fi
if [[ ! "$DB_DATABASE" =~ ^[A-Za-z0-9_]+$ ]]; then
  echo "DB_DATABASE non valido" >&2
  exit 1
fi

compose() {
  COMPOSE_PROJECT_NAME="$RESTORE_PROJECT_NAME" \
  DB_PORT="$RESTORE_DB_PORT" \
  DB_DATABASE="$DB_DATABASE" \
    docker compose -f compose.yml "$@"
}

cleanup() {
  compose down --volumes --remove-orphans > /dev/null 2>&1 || true
  case "$TEST_ROOT" in
    /tmp/yii3-template-restore.*)
      rm -rf -- "$TEST_ROOT"
      ;;
  esac
}
trap cleanup EXIT

compose down --volumes --remove-orphans > /dev/null 2>&1 || true
compose up -d --wait db
compose run --rm app ./yii migrate:up -y

compose exec -T db mysql "$DB_DATABASE" \
  -e "CREATE TABLE restore_probe (id INT PRIMARY KEY, payload VARCHAR(64) NOT NULL); INSERT INTO restore_probe VALUES (1, 'backup-restore-ok');"
compose exec -T db mysqldump --no-tablespaces --single-transaction "$DB_DATABASE" \
  > "$DUMP_FILE" < /dev/null
[ -s "$DUMP_FILE" ]
chmod 600 "$DUMP_FILE"

compose exec -T db mysql \
  -e "DROP DATABASE \`${DB_DATABASE}\`; CREATE DATABASE \`${DB_DATABASE}\`;"
if compose exec -T db mysql "$DB_DATABASE" \
  -Nse 'SELECT payload FROM restore_probe WHERE id = 1' > /dev/null 2>&1; then
  echo "Il database non è stato svuotato prima del restore" >&2
  exit 1
fi

compose exec -T db mysql "$DB_DATABASE" < "$DUMP_FILE"
restored_payload=$(compose exec -T db mysql "$DB_DATABASE" \
  -Nse 'SELECT payload FROM restore_probe WHERE id = 1')
[ "$restored_payload" = backup-restore-ok ] \
  || { echo "Dato sentinella non ripristinato" >&2; exit 1; }
compose run --rm app ./yii migrate:history

echo "Backup/restore isolato: OK"
