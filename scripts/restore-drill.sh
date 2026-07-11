#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Guna: $0 /path/ke/backup-diwan.zip" >&2
  exit 2
fi

BACKUP_ZIP="$(realpath "$1")"
[[ -f "$BACKUP_ZIP" ]] || { echo "Fail sandaran tidak ditemui: $BACKUP_ZIP" >&2; exit 2; }

WORKDIR="$(mktemp -d)"
CONTAINER="diwan-restore-drill-$(date +%s)"
LOG_FILE="${RESTORE_DRILL_LOG:-$(pwd)/storage/logs/restore-drill-$(date +%F-%H%M%S).log}"

cleanup() {
  docker rm -f "$CONTAINER" >/dev/null 2>&1 || true
  rm -rf "$WORKDIR"
}
trap cleanup EXIT

mkdir -p "$(dirname "$LOG_FILE")"
exec > >(tee -a "$LOG_FILE") 2>&1

echo "Mula restore drill: $(date --iso-8601=seconds)"
echo "Sandaran: $BACKUP_ZIP"

unzip -q "$BACKUP_ZIP" -d "$WORKDIR"
DUMP_FILE="$(find "$WORKDIR" -type f \( -name '*.sql' -o -name '*.dump' -o -name '*.backup' \) | head -n 1)"
[[ -n "$DUMP_FILE" ]] || { echo "GAGAL: dump PostgreSQL tiada dalam arkib."; exit 1; }

docker run -d --name "$CONTAINER" \
  -e POSTGRES_DB=diwan_restore \
  -e POSTGRES_USER=diwan_restore \
  -e POSTGRES_PASSWORD=restore-only-password \
  postgres:16-alpine >/dev/null

for _ in {1..30}; do
  docker exec "$CONTAINER" pg_isready -U diwan_restore -d diwan_restore >/dev/null 2>&1 && break
  sleep 1
done
docker exec "$CONTAINER" pg_isready -U diwan_restore -d diwan_restore >/dev/null

docker cp "$DUMP_FILE" "$CONTAINER:/tmp/restore-dump"
case "$DUMP_FILE" in
  *.sql) docker exec -e PGPASSWORD=restore-only-password "$CONTAINER" psql -v ON_ERROR_STOP=1 -U diwan_restore -d diwan_restore -f /tmp/restore-dump ;;
  *) docker exec -e PGPASSWORD=restore-only-password "$CONTAINER" pg_restore --exit-on-error --no-owner -U diwan_restore -d diwan_restore /tmp/restore-dump ;;
esac

docker exec -e PGPASSWORD=restore-only-password "$CONTAINER" psql -v ON_ERROR_STOP=1 -U diwan_restore -d diwan_restore -c \
  "SELECT to_regclass('public.mosques') AS mosques_table, to_regclass('public.records') AS records_table;"
docker exec -e PGPASSWORD=restore-only-password "$CONTAINER" psql -v ON_ERROR_STOP=1 -U diwan_restore -d diwan_restore -c \
  "SELECT (SELECT count(*) FROM mosques) AS mosques, (SELECT count(*) FROM records) AS records, (SELECT count(*) FROM users) AS users;"

echo "LULUS restore drill: $(date --iso-8601=seconds)"
echo "Bukti: $LOG_FILE"
