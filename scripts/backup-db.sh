#!/usr/bin/env bash
# Backup del DB di produzione. Gira SUL VPS, invocato dal CD (o a mano)
# come `bash /opt/yii3/scripts/backup-db.sh`.
set -euo pipefail

cd /opt/yii3

mkdir -p backups

# Credenziali lette da .env.prod (fonte di verità), NON dall'env del
# container db: un container creato prima dell'ultima rotazione password
# avrebbe ancora quella vecchia (Access denied, successo il 2026-07-04).
DB_USERNAME=$(grep -E '^DB_USERNAME=' .env.prod | head -1 | cut -d= -f2- || true)
DB_DATABASE=$(grep -E '^DB_DATABASE=' .env.prod | head -1 | cut -d= -f2- || true)
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env.prod | head -1 | cut -d= -f2- || true)
[ -n "$DB_PASSWORD" ] || { echo "DB_PASSWORD assente in .env.prod"; exit 1; }

OUT="backups/db_$(date +%F_%H-%M-%S).sql"

# MYSQL_PWD tiene la password fuori dalla riga di comando;
# --single-transaction evita lock sulle tabelle dell'app live.
docker compose \
  --env-file .env.prod \
  -f docker/prod/compose.yml \
  -f docker/prod/compose.local.yml \
  exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
  mysqldump --no-tablespaces --single-transaction \
  -u"${DB_USERNAME:-yii3_template}" "${DB_DATABASE:-yii3_template}" \
  > "$OUT" < /dev/null || { rm -f "$OUT"; exit 1; }

# Un dump vuoto è un backup finto: meglio fallire qui che al restore.
[ -s "$OUT" ]

ls -lh backups | tail
