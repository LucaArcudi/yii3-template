#!/usr/bin/env bash
# Deploy di produzione. Gira SUL VPS, invocato dal CD (o a mano) come
# `bash /opt/yii3/scripts/deploy.sh` dal checkout già allineato dallo step
# "Update repo files": la logica eseguita è sempre quella dell'ultimo commit.
#
# Il CD passa APP_IMAGE=<registry>:<sha del commit>: si deploya il tag
# immutabile, non latest — run riproducibili e rollback deterministico.
# Senza APP_IMAGE nell'ambiente (deploy manuale) vale quello di .env.prod:
# le variabili di shell hanno precedenza sull'--env-file di compose.
#
# Se l'avvio della nuova versione fallisce (up, invariante immagine o
# health check), l'app viene RIPRISTINATA automaticamente sull'immagine
# che girava prima del deploy; le migration non vengono annullate
# (idempotenti e additive: per il restore c'è il runbook §9.4) e il run
# fallisce comunque, perché il deploy non è avvenuto.
#
# Perché uno script e non un heredoc nel workflow: uno script inviato via
# stdin viene DIVORATO dal primo comando che legge stdin — docker compose
# run/exec lo fanno — e la shell remota esce a metà con exit 0. È successo:
# i deploy del 2026-07-04 risultavano verdi ma morivano subito dopo il
# migrate, senza mai ricreare l'app. Qui lo script è letto da file e ogni
# comando docker ha comunque stdin rediretto, cintura e bretelle.
set -euo pipefail

cd /opt/yii3

C=(docker compose --env-file .env.prod -f docker/prod/compose.yml -f docker/prod/compose.local.yml)

if [ -n "${APP_IMAGE:-}" ]; then
  export APP_IMAGE
  echo "Immagine richiesta: ${APP_IMAGE}"
fi

# Immagine attualmente in esecuzione, per il rollback automatico: si
# preferisce il digest al tag (latest si muove, il digest no).
PREV_IMAGE=""
CID_BEFORE=$("${C[@]}" ps -q app < /dev/null || true)
if [ -n "$CID_BEFORE" ]; then
  PREV_IMAGE_ID=$(docker inspect --format '{{.Image}}' "$CID_BEFORE")
  PREV_IMAGE=$(docker image inspect --format '{{if .RepoDigests}}{{index .RepoDigests 0}}{{end}}' "$PREV_IMAGE_ID")
  [ -n "$PREV_IMAGE" ] || PREV_IMAGE="$PREV_IMAGE_ID"
  echo "Immagine di rollback: ${PREV_IMAGE}"
fi

"${C[@]}" pull < /dev/null

# Migration del framework con l'immagine appena pullata, PRIMA di avviare
# la nuova versione dell'app. Idempotenti: nessun effetto se già applicate.
# Un fallimento qui non richiede rollback: la vecchia app non è stata toccata.
"${C[@]}" run --rm -T app ./yii migrate:up -y < /dev/null

health_check() {
  # -f fa fallire su HTTP >= 400; i retry coprono l'avvio del container
  # appena ricreato. X-Forwarded-Proto simula il proxy TLS: senza, il
  # cookie Secure di sessione risponde 500.
  curl -fsS -m 10 \
    --retry 12 --retry-delay 5 --retry-all-errors \
    -H 'X-Forwarded-Proto: https' \
    -o /dev/null \
    http://127.0.0.1:8080/login
}

start_and_check() {
  # NB: chiamata dentro `if !`, dove set -e è sospeso: ogni passo deve
  # fallire esplicitamente con || return 1.
  "${C[@]}" up -d --wait --wait-timeout 120 < /dev/null || return 1

  # Il pull da solo non basta (già visto: app rimasta sull'immagine vecchia):
  # l'app viene SEMPRE ricreata esplicitamente.
  "${C[@]}" up -d --wait --wait-timeout 120 --force-recreate --no-deps app < /dev/null || return 1

  # Invariante del deploy: il container app DEVE girare l'immagine a cui
  # punta ora il suo tag; altrimenti fallire rumorosamente qui.
  local cid running want
  cid=$("${C[@]}" ps -q app < /dev/null) || return 1
  running=$(docker inspect --format '{{.Image}}' "$cid") || return 1
  want=$(docker image inspect --format '{{.Id}}' "$(docker inspect --format '{{.Config.Image}}' "$cid")") || return 1
  if [ "$running" != "$want" ]; then
    echo "ERRORE: app su immagine $running, attesa $want"
    return 1
  fi
  echo "Invariante immagine OK: $(echo "$running" | cut -c8-19)"

  "${C[@]}" ps < /dev/null

  health_check || return 1
  echo "Health check OK"
}

if ! start_and_check; then
  echo "ERRORE: deploy fallito." >&2
  if [ -z "$PREV_IMAGE" ]; then
    echo "Nessuna immagine precedente registrata (primo deploy?): niente rollback, intervento manuale (runbook §9.1)." >&2
    exit 1
  fi
  echo "Rollback automatico su ${PREV_IMAGE}..." >&2
  if APP_IMAGE="$PREV_IMAGE" "${C[@]}" up -d --wait --wait-timeout 120 --force-recreate --no-deps app < /dev/null && health_check; then
    echo "Rollback OK: l'app è tornata su ${PREV_IMAGE}." >&2
    echo "Le migration NON vengono annullate: se la release ne conteneva, valutare il restore del backup pre-deploy (runbook §9.4)." >&2
  else
    echo "ROLLBACK FALLITO: intervento manuale immediato (runbook §9.1 e §9.3)." >&2
  fi
  exit 1
fi

echo "Deploy OK${APP_IMAGE:+ (${APP_IMAGE})}"
