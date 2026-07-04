#!/usr/bin/env bash
# Deploy di produzione. Gira SUL VPS, invocato dal CD (o a mano) come
# `bash /opt/yii3/scripts/deploy.sh` dal checkout già allineato dallo step
# "Update repo files": la logica eseguita è sempre quella dell'ultimo commit.
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

"${C[@]}" pull < /dev/null

# Migration del framework con l'immagine appena pullata, PRIMA di avviare
# la nuova versione dell'app. Idempotenti: nessun effetto se già applicate.
"${C[@]}" run --rm -T app ./yii migrate:up -y < /dev/null

"${C[@]}" up -d --wait --wait-timeout 120 < /dev/null

# Il pull da solo non basta (già visto: app rimasta sull'immagine vecchia):
# l'app viene SEMPRE ricreata esplicitamente.
"${C[@]}" up -d --wait --wait-timeout 120 --force-recreate --no-deps app < /dev/null

# Invariante del deploy: il container app DEVE girare l'immagine a cui
# punta ora il suo tag; altrimenti fallire rumorosamente qui.
CID=$("${C[@]}" ps -q app)
RUNNING=$(docker inspect --format '{{.Image}}' "$CID")
WANT=$(docker image inspect --format '{{.Id}}' "$(docker inspect --format '{{.Config.Image}}' "$CID")")
if [ "$RUNNING" != "$WANT" ]; then
  echo "ERRORE: app su immagine $RUNNING, attesa $WANT"
  exit 1
fi
echo "Invariante immagine OK: $(echo "$RUNNING" | cut -c8-19)"

"${C[@]}" ps

# -f fa fallire il deploy su HTTP >= 400; i retry coprono l'avvio del
# container appena ricreato. X-Forwarded-Proto simula il proxy TLS:
# senza, il cookie Secure di sessione risponde 500.
curl -fsS -m 10 \
  --retry 12 --retry-delay 5 --retry-all-errors \
  -H 'X-Forwarded-Proto: https' \
  -o /dev/null \
  http://127.0.0.1:8080/login

echo "Health check OK"
