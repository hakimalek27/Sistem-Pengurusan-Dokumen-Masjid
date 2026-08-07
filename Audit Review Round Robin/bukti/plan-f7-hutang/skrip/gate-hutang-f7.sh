#!/usr/bin/env bash
# Gate tempatan 3 shard — prosedur daripada HANDOVER.md (dibina semula setiap sesi).
# DB SEGAR antara shard (koreografi mengubah fixture); pelayan dilancar TERUS kerana
# `artisan serve` tidak menghantar `-d` kepada anak; output ke FAIL (jangan `tail` — exit
# code jadi milik penapis).
cd "C:/Projek Coding/Sistem Pengurusan Dokumen Masjid" || exit 1
OUT="C:/Users/hakim/AppData/Local/Temp/claude/C--Projek-Coding-Sistem-Pengurusan-Dokumen-Masjid/e88973f3-ecbe-4b56-9c1e-7b82460d73b0/scratchpad/hutang-f7"
mkdir -p "$OUT"

export APP_ENV=local APP_LOCALE=ms APP_URL=http://127.0.0.1:8092 E2E_BASE_URL=http://127.0.0.1:8092 \
       SESSION_DRIVER=file MAIL_MAILER=log MAIL_LOG_CHANNEL=single SCOUT_DRIVER=collection \
       QUEUE_CONNECTION=sync DIWAN_STORAGE_DISK=local BACKUP_DISK=local IMAP_ENABLED=false \
       WHATSAPP_DRIVER=log DIWAN_LOGIN_RATE_LIMIT=100 E2E_ROLE_LOGIN_DELAY_MS=0

bunuh_pelayan() {
  for pid in $(netstat -ano | grep ':8092' | grep LISTENING | awk '{print $NF}' | sort -u); do
    taskkill //F //PID "$pid" //T > /dev/null 2>&1
  done
}

# Payload shard LAMA mesti dibuang: shard yang crash meninggalkan fail lapuk yang kelihatan
rm -f storage/app/plan-f6/shard-*.json
rm -rf storage/app/plan-f6/artifacts
mkdir -p storage/app/plan-f6/artifacts

for SHARD in screen workflow tenant-admin-public; do
  echo "=================== SHARD $SHARD ==================="
  bunuh_pelayan
  php artisan config:clear > /dev/null 2>&1
  php artisan cache:clear  > /dev/null 2>&1
  php artisan migrate:fresh --seed --force --no-interaction > "$OUT/seed-$SHARD.txt" 2>&1
  echo "  seed exit=$?"

  ( cd public && DIWAN_LOGIN_RATE_LIMIT=100 php -d max_execution_time=0 -S 127.0.0.1:8092 \
      ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php \
      > "$OUT/serve-$SHARD.log" 2>&1 & )
  sleep 4

  mkdir -p "storage/app/plan-f6/artifacts/guidance-shard-$SHARD"
  GUIDANCE_SHARD=$SHARD \
  DIWAN_PW_JSON="storage/app/plan-f6/artifacts/guidance-shard-$SHARD/shard-$SHARD.json" \
    npx playwright test --project=guidance-full --reporter=list > "$OUT/shard-$SHARD.txt" 2>&1
  echo "  $SHARD EXIT=$?"
  tail -4 "$OUT/shard-$SHARD.txt"
  bunuh_pelayan
done

# Payload shard ditulis oleh spec ke storage/app/plan-f6/shard-<shard>.json
# (guidance-full.spec.js:1465). CI mengisi direktori artifact dgn download-artifact;
# larian TEMPATAN tidak, jadi glob mesti menunjuk payload terus.
# JANGAN letak komen di TENGAH rantaian backslash - ia memutus sambungan baris (exit 127).
echo "=================== AGREGATOR ==================="
node scripts/audit/aggregate-guidance-coverage.mjs \
  --manifest "Audit Review Round Robin/bukti/plan-baseline/manifest.json" \
  --shards "storage/app/plan-f6/shard-*.json" \
  --out storage/app/plan-f6/coverage-gate.json > "$OUT/agregator.txt" 2>&1
echo "  agregator EXIT=$?"
tail -20 "$OUT/agregator.txt"
