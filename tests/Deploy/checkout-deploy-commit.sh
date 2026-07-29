#!/usr/bin/env bash
set -euo pipefail
export GIT_CONFIG_GLOBAL=/dev/null
export GIT_CONFIG_SYSTEM=/dev/null

PROJECT_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
HELPER="${PROJECT_ROOT}/scripts/checkout-deploy-commit.sh"
TEST_ROOT=$(mktemp -d /tmp/yii3-template-deploy-test.XXXXXX)
REMOTE_REPO="${TEST_ROOT}/remote.git"
SEED_REPO="${TEST_ROOT}/seed"
DEPLOY_REPO="${TEST_ROOT}/deploy"

cleanup() {
  case "$TEST_ROOT" in
    /tmp/yii3-template-deploy-test.*)
      rm -rf -- "$TEST_ROOT"
      ;;
  esac
}
trap cleanup EXIT

fail() {
  echo "TEST FALLITO: $*" >&2
  exit 1
}

assert_head() {
  local expected=$1
  local actual
  actual=$(git -C "$DEPLOY_REPO" rev-parse HEAD)
  [ "$actual" = "$expected" ] || fail "HEAD $actual, atteso $expected"
}

assert_detached_head() {
  if git -C "$DEPLOY_REPO" symbolic-ref -q HEAD > /dev/null; then
    fail "HEAD non è detached"
  fi
}

run_helper() {
  local deploy_sha=$1
  DEPLOY_REPO="$DEPLOY_REPO" DEPLOY_SHA="$deploy_sha" bash -s < "$HELPER"
}

git init --bare --initial-branch=main "$REMOTE_REPO" > /dev/null
git init --initial-branch=main "$SEED_REPO" > /dev/null
git -C "$SEED_REPO" config user.email test@example.test
git -C "$SEED_REPO" config user.name "Deploy Test"

cat > "${SEED_REPO}/.gitignore" << 'EOF'
.env.prod
/docker/prod/compose.local.yml
/backups/
EOF
printf 'first\n' > "${SEED_REPO}/tracked.txt"
printf 'stable\n' > "${SEED_REPO}/stable.txt"
git -C "$SEED_REPO" add .gitignore stable.txt tracked.txt
git -C "$SEED_REPO" commit -m First > /dev/null
git -C "$SEED_REPO" remote add origin "$REMOTE_REPO"
git -C "$SEED_REPO" push -u origin main > /dev/null
FIRST_SHA=$(git -C "$SEED_REPO" rev-parse HEAD)

git -C "$SEED_REPO" switch -c side > /dev/null
printf 'foreign\n' > "${SEED_REPO}/side.txt"
git -C "$SEED_REPO" add side.txt
git -C "$SEED_REPO" commit -m Side > /dev/null
git -C "$SEED_REPO" push origin side > /dev/null
FOREIGN_SHA=$(git -C "$SEED_REPO" rev-parse HEAD)

git -C "$SEED_REPO" switch main > /dev/null
printf 'latest\n' > "${SEED_REPO}/tracked.txt"
git -C "$SEED_REPO" commit -am Latest > /dev/null
git -C "$SEED_REPO" push origin main > /dev/null
LATEST_SHA=$(git -C "$SEED_REPO" rev-parse HEAD)

git clone "$REMOTE_REPO" "$DEPLOY_REPO" > /dev/null
mkdir -p "${DEPLOY_REPO}/docker/prod" "${DEPLOY_REPO}/backups"
printf 'SECRET=preserved\n' > "${DEPLOY_REPO}/.env.prod"
printf 'services: {}\n' > "${DEPLOY_REPO}/docker/prod/compose.local.yml"
printf 'backup\n' > "${DEPLOY_REPO}/backups/db.sql"

MISSING_SHA=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa
for invalid_sha in "" latest abcdef0 "${FIRST_SHA:0:12}" "$MISSING_SHA"; do
  if run_helper "$invalid_sha" > /dev/null 2>&1; then
    fail "SHA inatteso accettato: ${invalid_sha:-<vuoto>}"
  fi
  assert_head "$LATEST_SHA"
done

if run_helper "$FOREIGN_SHA" > /dev/null 2>&1; then
  fail "commit estraneo a main accettato"
fi
assert_head "$LATEST_SHA"

# stable.txt è identico tra LATEST_SHA e FIRST_SHA: senza la guardia dirty,
# Git accetterebbe il checkout preservando la modifica locale.
printf 'stable local change\n' > "${DEPLOY_REPO}/stable.txt"
if run_helper "$FIRST_SHA" > /dev/null 2>&1; then
  fail "checkout con file tracciato sporco ma invariato nel target accettato"
fi
assert_head "$LATEST_SHA"
[ "$(cat "${DEPLOY_REPO}/stable.txt")" = "stable local change" ] \
  || fail "la modifica al file stabile è stata sovrascritta"
printf 'stable\n' > "${DEPLOY_REPO}/stable.txt"

printf 'local change\n' > "${DEPLOY_REPO}/tracked.txt"
if run_helper "$FIRST_SHA" > /dev/null 2>&1; then
  fail "checkout sporco accettato"
fi
assert_head "$LATEST_SHA"
[ "$(cat "${DEPLOY_REPO}/tracked.txt")" = "local change" ] \
  || fail "la modifica locale è stata sovrascritta"
printf 'latest\n' > "${DEPLOY_REPO}/tracked.txt"

printf 'staged change\n' > "${DEPLOY_REPO}/tracked.txt"
git -C "$DEPLOY_REPO" add tracked.txt
if run_helper "$FIRST_SHA" > /dev/null 2>&1; then
  fail "checkout con modifica staged accettato"
fi
assert_head "$LATEST_SHA"
[ "$(cat "${DEPLOY_REPO}/tracked.txt")" = "staged change" ] \
  || fail "la modifica staged è stata sovrascritta"
git -C "$DEPLOY_REPO" restore --staged --worktree tracked.txt

# Questo commit nasce dopo il clone: il checkout può riuscire solo se
# l'helper aggiorna davvero origin/main prima di validare il target.
printf 'remote after clone\n' > "${SEED_REPO}/tracked.txt"
printf 'remote content\n' > "${SEED_REPO}/remote-only.txt"
git -C "$SEED_REPO" add remote-only.txt tracked.txt
git -C "$SEED_REPO" commit -m "Remote after clone" > /dev/null
git -C "$SEED_REPO" push origin main > /dev/null
REMOTE_ONLY_SHA=$(git -C "$SEED_REPO" rev-parse HEAD)

# Un checkout non forzato deve rifiutare il file del target che
# sovrascriverebbe un omonimo untracked presente nel repository di deploy.
printf 'local untracked content\n' > "${DEPLOY_REPO}/remote-only.txt"
if run_helper "$REMOTE_ONLY_SHA" > /dev/null 2>&1; then
  fail "checkout che sovrascrive un file untracked accettato"
fi
assert_head "$LATEST_SHA"
[ "$(cat "${DEPLOY_REPO}/remote-only.txt")" = "local untracked content" ] \
  || fail "il file untracked in conflitto è stato sovrascritto"
rm -- "${DEPLOY_REPO}/remote-only.txt"

run_helper "$REMOTE_ONLY_SHA" > /dev/null
assert_head "$REMOTE_ONLY_SHA"
assert_detached_head

run_helper "$FIRST_SHA" > /dev/null
assert_head "$FIRST_SHA"
assert_detached_head
[ "$(cat "${DEPLOY_REPO}/tracked.txt")" = "first" ] \
  || fail "il checkout non contiene i file del target"
[ "$(cat "${DEPLOY_REPO}/.env.prod")" = "SECRET=preserved" ] \
  || fail ".env.prod non è stato preservato"
[ "$(cat "${DEPLOY_REPO}/docker/prod/compose.local.yml")" = "services: {}" ] \
  || fail "compose.local.yml non è stato preservato"
[ "$(cat "${DEPLOY_REPO}/backups/db.sql")" = "backup" ] \
  || fail "il backup locale non è stato preservato"

echo "Test checkout deploy: OK"
