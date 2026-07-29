#!/usr/bin/env bash
# Allinea il checkout di produzione allo stesso SHA usato come tag immagine.
# Il CD invia questo helper versionato via stdin e imposta DEPLOY_SHA; il
# percorso è configurabile solo per consentire simulazioni in una repo /tmp.
set -euo pipefail
export GIT_TERMINAL_PROMPT=0

: "${DEPLOY_SHA:?DEPLOY_SHA obbligatorio}"

DEPLOY_REPO="${DEPLOY_REPO:-/opt/yii3}"
DEPLOY_REMOTE="${DEPLOY_REMOTE:-origin}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
REMOTE_REF="refs/remotes/${DEPLOY_REMOTE}/${DEPLOY_BRANCH}"
GIT=(git -c "safe.directory=${DEPLOY_REPO}" -C "$DEPLOY_REPO")

if [[ ! "$DEPLOY_SHA" =~ ^[0-9a-f]{40}$ ]]; then
  echo "ERRORE: DEPLOY_SHA deve essere uno SHA Git completo di 40 caratteri" >&2
  exit 1
fi
if [ ! -d "$DEPLOY_REPO" ]; then
  echo "ERRORE: repository di deploy $DEPLOY_REPO inesistente" >&2
  exit 1
fi
if [ "$DEPLOY_REPO" = /opt/yii3 ]; then
  git config --global --get-all safe.directory 2>/dev/null | grep -qx "$DEPLOY_REPO" \
    || git config --global --add safe.directory "$DEPLOY_REPO"
fi
if ! "${GIT[@]}" rev-parse --git-dir > /dev/null 2>&1; then
  echo "ERRORE: $DEPLOY_REPO non è un repository Git" >&2
  exit 1
fi

if ! "${GIT[@]}" diff --quiet --ignore-submodules -- \
  || ! "${GIT[@]}" diff --cached --quiet --ignore-submodules --; then
  echo "ERRORE: $DEPLOY_REPO contiene modifiche a file tracciati; checkout non modificato" >&2
  exit 1
fi

"${GIT[@]}" fetch --no-tags "$DEPLOY_REMOTE" \
  "${DEPLOY_BRANCH}:${REMOTE_REF}"

if ! "${GIT[@]}" cat-file -e "${DEPLOY_SHA}^{commit}" 2>/dev/null; then
  echo "ERRORE: commit $DEPLOY_SHA inesistente dopo il fetch di $DEPLOY_BRANCH" >&2
  exit 1
fi
if ! "${GIT[@]}" merge-base --is-ancestor "$DEPLOY_SHA" "$REMOTE_REF"; then
  echo "ERRORE: commit $DEPLOY_SHA estraneo alla storia di ${DEPLOY_REMOTE}/${DEPLOY_BRANCH}" >&2
  exit 1
fi

"${GIT[@]}" checkout --detach "$DEPLOY_SHA"
ACTUAL_SHA=$("${GIT[@]}" rev-parse HEAD)
if [ "$ACTUAL_SHA" != "$DEPLOY_SHA" ]; then
  echo "ERRORE: checkout $ACTUAL_SHA, atteso $DEPLOY_SHA" >&2
  exit 1
fi

echo "Checkout VPS verificato: $ACTUAL_SHA"
"${GIT[@]}" log -1 --oneline
