#!/usr/bin/env bash
# A/B penentu: adakah lonjakan `centerCovered` 6 -> 45 disebabkan KERJA KAMI (sasaran spesifik)
# atau PEMBAURAN persekitaran (audit = produksi/smoke, ukuran = tempatan/mam)?
#
# Katalog LAMA diambil daripada commit Deploy 1 (`9619509`) — sebelum W0/W1 menjadikan sasaran
# spesifik. Mesin, tenant, benih, viewport dan skrip ukur semuanya IDENTIK. Satu pemboleh ubah.
#
# ⚠️ Katalog repo dipulihkan dalam `trap` supaya penghentian di tengah jalan tidak meninggalkan
# katalog lama dalam working tree.
# WAJIB `-e` (Codex P2 #17): tanpanya kegagalan larian A tidak menghentikan larian B, dan
# artifak lama kekal — `ab-lama.json` boleh jadi baki larian terdahulu sedangkan laporan
# mendakwa ia sisi katalog lama. Trap tetap memulihkan katalog pada exit.
set -euo pipefail
cd "C:/Projek Coding/Sistem Pengurusan Dokumen Masjid" || exit 1

ASAL="$(mktemp)"   # katalog semasa disimpan sementara; dipulihkan dalam trap
cp resources/help/guides.json "$ASAL"

pulih() {
  cp "$ASAL" resources/help/guides.json
  php artisan optimize:clear > /dev/null 2>&1
  echo "katalog dipulihkan; git status: $(git status --porcelain resources/help/guides.json | wc -l) baris"
}
trap pulih EXIT

# Artifak lama DIBUANG dahulu: jika larian gagal, ketiadaan fail menjadi isyarat,
# bukan fail basi yang kelihatan sah.
rm -f "Audit Review Round Robin/bukti/plan-f8/ab-lama.json"
rm -f "Audit Review Round Robin/bukti/plan-f8/ab-semasa.json"

echo "=== A: katalog LAMA (9619509, sasaran generik) ==="
git show 9619509:resources/help/guides.json > resources/help/guides.json || exit 1
node -e "const d=require('./resources/help/guides.json'); console.log('  catalog_version', d.catalog_version)"
php artisan optimize:clear > /dev/null 2>&1
DIWAN_LOGIN_RATE_LIMIT=100 AB_LABEL=lama AB_HAD=24 \
  node "Audit Review Round Robin/bukti/plan-f8/skrip/ab-ukur.mjs"

echo
echo "=== B: katalog SEMASA (sasaran spesifik) ==="
cp "$ASAL" resources/help/guides.json
node -e "const d=require('./resources/help/guides.json'); console.log('  catalog_version', d.catalog_version)"
php artisan optimize:clear > /dev/null 2>&1
DIWAN_LOGIN_RATE_LIMIT=100 AB_LABEL=semasa AB_HAD=24 \
  node "Audit Review Round Robin/bukti/plan-f8/skrip/ab-ukur.mjs"
