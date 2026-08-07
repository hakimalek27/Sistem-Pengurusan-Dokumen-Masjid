#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════════════════
# DEPLOY 12 — F6-W6 ke bakwim.my
#
# Dijalankan SEBAGAI FAIL di pelayan (scp dahulu). JANGAN `ssh 'bash -s' < skrip`:
# `docker compose exec` menelan baki skrip sebagai stdin dan deploy berhenti SENYAP
# dengan exit 0 (pelajaran Deploy 7). Setiap `exec` mesti `< /dev/null`.
#
# JANGAN jalankan git dengan `sudo` di /opt/diwan — ia meninggalkan fail milik root yang
# menyekat deploy berikutnya DUA KALI (pelajaran Deploy 8).
#
# RAMALAN ASET (ditulis SEBELUM deploy — semak satu per satu selepasnya, pelajaran Deploy 8).
#
# ⚠️ W6 menyentuh Blade + JSON SAHAJA — tiada entri Vite tersentuh, jadi nama aset
# DIJANGKA KEKAL: assets/help-D_qumira.js + assets/help-Cfwb6f_j.css (sama seperti Deploy 11).
# Itu BUKAN tanda deploy gagal. Untuk jenis deploy ini bukti utama ialah **kandungan DALAM
# imej** (#2a katalog: catalog_version + kiraan generik) + **ImageID berubah** + label
# revisi — corak yang sudah terbukti tiga kali (Deploy 2, 7, 8).
#
# Rebuild `app` DAN `nginx` tetap WAJIB: imej nginx membawa salinan `public/build` SENDIRI
# dan Blade yang berubah mesti masuk ke dalam imej app.
# `catalog_version` 2026.08.07.1 -> 2026.08.08.1 -> sync-help-index --delete WAJIB.
# ⛔ JANGAN jalankan seeder pada produksi.
#
# Nilai jangkaan #2a selepas deploy:
#   catalog_version 2026.08.08.1 | guide 83 | langkah 473 | generik 59
# ═══════════════════════════════════════════════════════════════════════════════════════
set -euo pipefail

SHA="${1:?guna: deploy-12.sh <git-sha-penuh>}"
cd /opt/diwan

echo "═══ 0. Keadaan SEBELUM (untuk rantaian bukti 5A) ═══"
git log -1 --format='git pelayan : %h %s'
sudo docker inspect diwan-app-1 --format 'app  ImageID={{slice .Image 7 19}}'
sudo docker inspect diwan-nginx-1 --format 'web  ImageID={{slice .Image 7 19}}'
sudo docker image inspect "$(sudo docker inspect diwan-app-1 --format '{{.Image}}')" \
  --format 'revisi app sebelum = {{index .Config.Labels "org.opencontainers.image.revision"}}'
df -h / | tail -1

echo "═══ 1. Prune cache build jika cakera >80% ═══"
GUNA=$(df / | tail -1 | awk '{print $5}' | tr -d '%')
echo "  penggunaan cakera: ${GUNA}%"
if [ "$GUNA" -gt 80 ]; then
  sudo docker builder prune -af
  df -h / | tail -1
fi

echo "═══ 2. Tarik kod (TANPA sudo) ═══"
git fetch origin
git merge --ff-only "origin/main"
git log -1 --format='git pelayan kini: %h %s'
test "$(git rev-parse HEAD)" = "$SHA" || { echo "GAGAL: HEAD != $SHA"; exit 1; }

echo "═══ 3. Bina app + nginx dengan GIT_SHA ═══"
sudo env GIT_SHA="$SHA" docker compose build app nginx

echo "═══ 4. Migrasi DARI IMEJ BAHARU sebelum trafik ═══"
sudo docker compose run --rm --no-deps app php artisan migrate --force < /dev/null

echo "═══ 5. Naikkan app + worker + scheduler, force-recreate nginx ═══"
sudo docker compose up -d app worker scheduler
sudo docker compose up -d --force-recreate nginx

echo "═══ 6. Bersihkan cache view (volume storage BERKEKALAN) ═══"
sudo docker compose exec -T app php artisan view:clear      < /dev/null
sudo docker compose exec -T app php artisan config:clear    < /dev/null
sudo docker compose exec -T app php artisan config:cache     < /dev/null
sudo docker compose exec -T app php artisan route:cache      < /dev/null

echo "═══ 7. Segerakkan indeks bantuan (catalog_version berubah) ═══"
sudo docker compose exec -T app php artisan diwan:sync-help-index --delete < /dev/null

echo "═══ 8. Keadaan SELEPAS + rantaian bukti 5A ═══"
sudo docker inspect diwan-app-1 --format 'app  ImageID={{slice .Image 7 19}}'
sudo docker inspect diwan-nginx-1 --format 'web  ImageID={{slice .Image 7 19}}'
sudo docker image inspect "$(sudo docker inspect diwan-app-1 --format '{{.Image}}')" \
  --format 'revisi app selepas = {{index .Config.Labels "org.opencontainers.image.revision"}}'

echo "--- #2a katalog DALAM imej app (bukti kandungan, bukan nama fail) ---"
sudo docker compose exec -T app php -r '$d=json_decode(file_get_contents("resources/help/guides.json"),true);
$s=0; $gen=0; foreach($d["guides"] as $g){ foreach($g["steps"] as $t){ $s++; if($t["target"]==="page-content"||$t["target"]==="page-primary") $gen++; } }
echo "catalog_version ".$d["catalog_version"]." | guide ".count($d["guides"])." | langkah ".$s." | generik ".$gen.PHP_EOL;' < /dev/null

echo "--- #3a nama aset EXACT daripada manifest dalam imej app ---"
sudo docker compose exec -T app php -r '$m=json_decode(file_get_contents("public/build/manifest.json"),true);
foreach($m as $k=>$v){ if(strpos($k,"help")!==false){ echo $k." -> ".$v["file"]; if(!empty($v["css"])) echo "  css: ".implode(",",$v["css"]); echo PHP_EOL; } }' < /dev/null

echo "--- #3b hash aset DALAM imej app ---"
sudo docker compose exec -T app sh -lc 'sha256sum public/build/assets/help-*.js public/build/assets/help-*.css | cut -c1-32,65-' < /dev/null

echo "--- #4b hash aset yang SAMA di dalam imej nginx ---"
sudo docker compose exec -T nginx sh -lc 'sha256sum /var/www/html/public/build/assets/help-*.js /var/www/html/public/build/assets/help-*.css | cut -c1-32,65-' < /dev/null

echo "--- #5a/#5b/#6 hash BADAN yang dihidang kepada awam (curl -fsS, bukan -sI) ---"
for f in $(sudo docker compose exec -T app sh -lc 'ls public/build/assets/ | grep "^help-"' < /dev/null | tr -d '\r'); do
  printf '  %-28s %s\n' "$f" "$(curl -fsS "https://bakwim.my/build/assets/$f" | sha256sum | cut -c1-32)"
done

echo "═══ 9. Kesihatan ═══"
sudo docker compose ps --format '{{.Name}}  {{.State}}'
sudo docker compose exec -T app php artisan diwan:health < /dev/null
sudo docker compose exec -T app php artisan diwan:smoke  < /dev/null
curl -fsS -o /dev/null -w 'GET /up -> %{http_code}\n' https://bakwim.my/up
# `tinker` memerlukan HOME boleh-tulis; tanpanya psysh gagal dengan
# "Writing to directory /var/www/.config/psysh is not allowed" dan skrip keluar 1
# walaupun deploy sudah berjaya sepenuhnya (berlaku pada Deploy 10).
sudo docker compose exec -T -e HOME=/tmp app php artisan tinker --execute='echo "failed_jobs=".DB::table("failed_jobs")->count().PHP_EOL;' < /dev/null

echo "═══ DEPLOY 12 SELESAI ═══"
