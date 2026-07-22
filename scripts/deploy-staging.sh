#!/usr/bin/env bash
set -Eeuo pipefail

if [[ $# -ne 5 ]]; then
  echo "Guna: deploy-staging.sh <ref> <repo-path> <backup.zip> <check-email> <app-url>" >&2
  exit 2
fi

REF="$1"
REPO_PATH="$2"
BACKUP_ZIP="$3"
CHECK_EMAIL="$4"
APP_URL="$5"

cd "$REPO_PATH"

if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "GAGAL: worktree staging mempunyai perubahan tracked yang belum dikomit." >&2
  exit 1
fi

PREVIOUS_REF="$(git rev-parse HEAD)"
DEPLOY_TAG="$(printf '%s' "$REF" | tr -c '[:alnum:]._-' '-')"

rollback() {
  local exit_code=$?
  trap - ERR
  set +e
  echo "ROLLBACK: kembali kepada $PREVIOUS_REF kerana gate gagal."
  git checkout --detach "$PREVIOUS_REF"
  DIWAN_IMAGE_TAG="rollback-$PREVIOUS_REF" docker compose build
  DIWAN_IMAGE_TAG="rollback-$PREVIOUS_REF" docker compose up -d --remove-orphans
  docker compose ps
  exit "$exit_code"
}
trap rollback ERR

git fetch --force origin "$REF"
git checkout --detach FETCH_HEAD

DIWAN_IMAGE_TAG="$DEPLOY_TAG" docker compose build --pull
DIWAN_IMAGE_TAG="$DEPLOY_TAG" docker compose run --rm app php artisan migrate --force --no-interaction
DIWAN_IMAGE_TAG="$DEPLOY_TAG" docker compose up -d --remove-orphans
docker compose exec -T app php artisan diwan:sync-meili
docker compose exec -T app php artisan diwan:sync-help-index

STAGING_CHECK_EMAIL="$CHECK_EMAIL" APP_URL="$APP_URL" ./scripts/staging-smoke.sh

docker compose exec -T app php artisan diwan:failure-drill cos --confirm-production
docker compose exec -T app php artisan diwan:failure-drill smtp --confirm-production
docker compose exec -T app php artisan diwan:failure-drill queue --verify --timeout=45 --confirm-production

./scripts/restore-drill.sh "$BACKUP_ZIP"

trap - ERR
echo "LULUS: deploy staging $REF, smoke, failure drills dan restore drill selesai."
