#!/usr/bin/env sh
set -eu

: "${STAGING_CHECK_EMAIL:?Tetapkan STAGING_CHECK_EMAIL kepada peti e-mel ujian yang dipantau}"

echo "[1/7] Status container"
docker compose ps

echo "[2/7] Migrasi"
docker compose exec -T app php artisan migrate:status --no-interaction

echo "[3/7] Dependency staging sebenar"
docker compose exec -T app php artisan diwan:staging-check --mail-to="$STAGING_CHECK_EMAIL"

echo "[4/7] Horizon"
docker compose exec -T worker php artisan horizon:status

echo "[5/7] Scheduler"
docker compose exec -T scheduler php artisan schedule:list

echo "[6/7] HTTP smoke"
curl --fail --silent --show-error "${APP_URL%/}/up" >/dev/null

echo "[7/7] Log error terkini"
if docker compose logs --since=10m app worker scheduler | grep -Ei "exception|critical|emergency"; then
  echo "Ralat ditemui dalam log 10 minit terakhir."
  exit 1
fi

echo "LULUS: smoke staging selesai. Semak penerimaan e-mel di $STAGING_CHECK_EMAIL."
