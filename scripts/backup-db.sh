#!/usr/bin/env bash
# Backup del DB di produzione. Gira SUL VPS, invocato dal CD (o a mano)
# come `bash /opt/yii3/scripts/backup-db.sh`.
set -euo pipefail
umask 077

DEPLOY_DIR="${DEPLOY_DIR:-/opt/yii3}"
if [[ "$DEPLOY_DIR" != /* ]] || [ "$DEPLOY_DIR" = / ]; then
  echo "ERRORE: DEPLOY_DIR deve essere un percorso assoluto specifico" >&2
  exit 1
fi
cd "$DEPLOY_DIR"

BACKUP_DIR="${DEPLOY_DIR}/backups"
install -d -m 700 "$BACKUP_DIR"
# Corregge anche una directory creata da una versione precedente.
chmod 700 "$BACKUP_DIR"

C=(docker compose --env-file .env.prod -f docker/prod/compose.yml -f docker/prod/compose.local.yml)

# Compose interpreta il formato dotenv (quote, spazi, # ed = inclusi). Le
# tre variabili vengono prima rimosse dall'ambiente host, che altrimenti ha
# precedenza sull'--env-file e potrebbe nascondere una rotazione incompleta.
COMPOSE_ENV=$(env -u DB_USERNAME -u DB_DATABASE -u DB_PASSWORD \
  "${C[@]}" config --environment < /dev/null)

compose_env_value() {
  local key=$1 line
  while IFS= read -r line; do
    case "$line" in
      "${key}="*)
        printf '%s' "${line#*=}"
        return 0
        ;;
    esac
  done <<< "$COMPOSE_ENV"
  return 1
}

DB_USERNAME=$(compose_env_value DB_USERNAME || true)
DB_DATABASE=$(compose_env_value DB_DATABASE || true)
DB_PASSWORD=$(compose_env_value DB_PASSWORD || true)
[ -n "$DB_PASSWORD" ] || { echo "DB_PASSWORD assente in .env.prod"; exit 1; }

OUT="${BACKUP_DIR}/db_$(date +%F_%H-%M-%S).sql"

# MYSQL_PWD tiene la password fuori dagli argomenti sia di Docker sia di
# mysqldump; --single-transaction evita lock sulle tabelle dell'app live.
MYSQL_PWD="$DB_PASSWORD" "${C[@]}" exec -T -e MYSQL_PWD db \
  mysqldump --no-tablespaces --single-transaction \
  -u"${DB_USERNAME:-yii3_template}" "${DB_DATABASE:-yii3_template}" \
  > "$OUT" < /dev/null || { rm -f "$OUT"; exit 1; }

# Un dump vuoto è un backup finto: meglio fallire qui che al restore.
[ -s "$OUT" ]
chmod 600 "$OUT"

# Retention 14 giorni, SOLO sui dump automatici: il glob stretto sul
# timestamp risparmia i backup rinominati a mano (es. *_before_rotation).
find "$BACKUP_DIR" -maxdepth 1 -name 'db_????-??-??_??-??-??.sql' -mtime +14 -delete

find "$BACKUP_DIR" -maxdepth 1 -type f -printf '%TY-%Tm-%Td %TH:%TM %10s %p\n' \
  | sort \
  | tail
