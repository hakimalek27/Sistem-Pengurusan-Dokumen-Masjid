#!/usr/bin/env bash
# Jalankan projek `ci-guidance` secara TEMPATAN — jurang yang CI 31211426672 dedahkan.
# Gate 3 shard (`guidance-full`) TIDAK memuatkan `guidance-f5.spec.js`, jadi perubahan katalog
# boleh lulus gate penuh dan tetap memerahkan check WAJIB. Bila katalog berubah, kedua-duanya
# mesti dijalankan.
cd "C:/Projek Coding/Sistem Pengurusan Dokumen Masjid" || exit 1
OUT="C:/Users/hakim/AppData/Local/Temp/claude/C--Projek-Coding-Sistem-Pengurusan-Dokumen-Masjid/e88973f3-ecbe-4b56-9c1e-7b82460d73b0/scratchpad/ci-guidance"
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

bunuh_pelayan
php artisan config:clear > /dev/null 2>&1
php artisan cache:clear  > /dev/null 2>&1
php artisan migrate:fresh --seed --force --no-interaction > "$OUT/seed.txt" 2>&1
echo "seed exit=$?"

( cd public && DIWAN_LOGIN_RATE_LIMIT=100 php -d max_execution_time=0 -S 127.0.0.1:8092 \
    ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php \
    > "$OUT/serve.log" 2>&1 & )
sleep 4

FILTER="${1:-}"
if [ -n "$FILTER" ]; then
  npx playwright test --project=ci-guidance -g "$FILTER" --reporter=list > "$OUT/hasil.txt" 2>&1
else
  npx playwright test --project=ci-guidance --reporter=list > "$OUT/hasil.txt" 2>&1
fi
echo "ci-guidance EXIT=$?"
tail -12 "$OUT/hasil.txt"
bunuh_pelayan
