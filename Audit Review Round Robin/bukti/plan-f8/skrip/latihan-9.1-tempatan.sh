#!/usr/bin/env bash
# F8 §9.1 — LATIHAN tempatan matriks 20 konteks (BUKAN produksi).
#
# Tujuan: membuktikan runner produksi boleh berjalan hujung-ke-hujung SEBELUM pemilik
# membekalkan kredensial produksi, supaya tetingkap kredensial itu tidak dibazirkan pada
# eksperimen. Sasaran ialah pelayan TEMPATAN sahaja — skrip ini menolak apa-apa selain
# 127.0.0.1 supaya ia tidak boleh tersalah tuju ke bakwim.my.
#
# Prasyarat (dijalankan sendiri, bukan oleh skrip ini supaya ia tidak membunuh pelayan orang).
#
# ⚠️ SATU `php artisan serve` TIDAK MENCUKUPI untuk matriks penuh, dan ini diukur:
#   - `php -S` satu-benang + navigasi pantas -> `net::ERR_ABORTED` rawak pada dokumen utama;
#   - `.env` tempatan guna SESSION_DRIVER=database + CACHE_STORE=database atas SQLite, jadi
#     SETIAP permintaan mengambil kunci tulis. 8 permintaan selari = 29,064 ms.
#     Dengan pemacu FAIL pada backend sahaja (.env tidak diubah): 1,064 ms — 27× lebih pantas.
#
# Resipi yang berfungsi (empat backend + proksi round-robin, tiada pakej npm baharu):
#   for P in 8101 8102 8103 8104; do
#     SESSION_DRIVER=file CACHE_STORE=file php artisan serve --host=127.0.0.1 --port=$P --no-reload &
#   done
#   node "Audit Review Round Robin/bukti/plan-f8/skrip/pelayan-berbilang.mjs" 8095 8101 8102 8103 8104
#   E2E_BASE_URL=http://127.0.0.1:8095 bash <skrip ini>
#
# Guna:  bash "Audit Review Round Robin/bukti/plan-f8/skrip/latihan-9.1-tempatan.sh" [keluaran]
set -uo pipefail

BASE="${E2E_BASE_URL:-http://127.0.0.1:8092}"
case "$BASE" in
    http://127.0.0.1:*|http://localhost:*) ;;
    *) echo "TOLAK: latihan ini TEMPATAN sahaja, dapat '$BASE'." >&2; exit 2 ;;
esac

OUT="${1:-Audit Review Round Robin/bukti/plan-f8/latihan-9.1}"
mkdir -p "$OUT"
RUN="$(php -r 'printf("%s-%s-4%s-%s%s-%s", bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), substr(bin2hex(random_bytes(2)),1), dechex(8+random_int(0,3)), substr(bin2hex(random_bytes(2)),1), bin2hex(random_bytes(6)));')"
FIXTURE="$OUT/fixture-$RUN.json"

# Cleanup SENTIASA berjalan — termasuk pada gantung yang dibunuh, kerana tenant fixture yang
# tertinggal ialah kos sebenar (latihan 9 Ogos meninggalkan satu sehingga dibersihkan manual).
bersih() {
    echo "── cleanup run $RUN"
    php artisan diwan:audit-fixture cleanup --run="$RUN" --json="$OUT/cleanup-$RUN.json" --force 2>&1 | tail -3
    rm -f "$FIXTURE"           # kredensial fixture TIDAK kekal pada cakera selepas larian
}
trap bersih EXIT INT TERM

echo "── prepare run $RUN"
php artisan diwan:audit-fixture prepare --run="$RUN" --json="$FIXTURE" 2>&1 | tail -2 || exit 1

export E2E_PRODUCTION=1
export E2E_BASE_URL="$BASE"
export E2E_PROD_TENANT="$(python3 -c "import json,sys;print(json.load(open(sys.argv[1],encoding='utf-8'))['slug'])" "$FIXTURE")"
export E2E_PROD_ROLE_ACCOUNTS="$(python3 -c "import json,sys;print(json.dumps(json.load(open(sys.argv[1],encoding='utf-8'))['role_credentials']))" "$FIXTURE")"
# Superadmin TEMPATAN (benih demo). Pada produksi ia dibekal pemilik melalui wrapper ps1 —
# kredensial produksi TIDAK pernah muncul dalam skrip ini.
export E2E_PROD_SUPERADMIN_EMAIL="${E2E_PROD_SUPERADMIN_EMAIL:-superadmin@diwan.test}"
export E2E_PROD_SUPERADMIN_PASSWORD="${E2E_PROD_SUPERADMIN_PASSWORD:-password}"
# Had kadar log masuk tempatan tidak sama dengan produksi; jarak 15s tidak perlu di sini.
export E2E_PROD_ROLE_LOGIN_DELAY_MS="${E2E_PROD_ROLE_LOGIN_DELAY_MS:-0}"
export E2E_PROD_REPORT="$OUT/route-manifest-TEMPATAN.json"
rm -f "$E2E_PROD_REPORT"

echo "── matriks 20 konteks -> $E2E_PROD_REPORT"

# ── MOD SATU-PROSES-SATU-KONTEKS ────────────────────────────────────────────────────────────
# Gantung tempatan terbukti KUMULATIF dalam satu proses pelayar (LATIHAN-9.1-TEMPATAN.md), jadi
# beri setiap konteks prosesnya SENDIRI: pelayar baharu setiap kali, kaunter kumulatif direset.
# Ini hanya mungkin kerana inventori hidup pada CAKERA dan dikunci pada `run_tenant` — ia
# terkumpul merentas invokasi dengan sendirinya. Kontrak penutup 20 konteks dijalankan di hujung
# dan MASIH menguatkuasakan set penuh, jadi mod ini tidak boleh menyembunyikan konteks yang gagal.
if [ "${LATIHAN_SATU_SATU:-0}" = "1" ]; then
    PERKONTEKS="${LATIHAN_PERKONTEKS_MS:-300000}"
    PERANAN=$(python3 -c "import json,sys;print(' '.join(x['role'] for x in json.load(open(sys.argv[1],encoding='utf-8'))['role_credentials']))" "$FIXTURE")
    : > "$OUT/larian-TEMPATAN.txt"
    for VP in desktop mobile; do
        for ID in public superadmin $PERANAN; do
            printf '── %s · %s\n' "$VP" "$ID" | tee -a "$OUT/larian-TEMPATAN.txt"
            npx playwright test --project=production-readonly --reporter=line \
                --global-timeout "$PERKONTEKS" --grep "$VP . $ID" 2>&1 \
                | grep -E "passed|failed|did not run|Error:|force-killed" | tee -a "$OUT/larian-TEMPATAN.txt"
        done
    done
    echo "── kontrak penutup 20 konteks" | tee -a "$OUT/larian-TEMPATAN.txt"
    npx playwright test --project=production-readonly --reporter=line \
        --global-timeout 120000 --grep "kontrak: TEPAT 20 konteks" 2>&1 | tee -a "$OUT/larian-TEMPATAN.txt"
    KEPUTUSAN=${PIPESTATUS[0]}
else
# Had MENYELURUH (sama seperti wrapper produksi): dikuatkuasakan oleh proses utama Playwright,
# jadi ia berkesan walaupun satu worker terkunci dan mengabaikan had per-ujiannya sendiri.
GT="${LATIHAN_GLOBAL_TIMEOUT_MS:-5400000}"
# LATIHAN_GREP: hadkan kepada sebahagian konteks semasa MENDIAGNOS satu identiti. Larian
# latihan PENUH mesti dijalankan tanpanya — kontrak penutup 20 konteks akan gagal jika tidak,
# jadi larian separa tidak boleh tersilap dibaca sebagai lulus.
npx playwright test --project=production-readonly --reporter=line --global-timeout "$GT" \
    ${LATIHAN_GREP:+--grep "$LATIHAN_GREP"} 2>&1 | tee "$OUT/larian-TEMPATAN.txt"
KEPUTUSAN=${PIPESTATUS[0]}
fi

# Laporan ditulis BERPERINGKAT oleh spec, jadi ia wujud walaupun larian dibunuh separuh jalan.
python3 - "$E2E_PROD_REPORT" <<'PY'
import json, sys, pathlib
p = pathlib.Path(sys.argv[1])
if not p.exists():
    print('LAPORAN TIADA — spec gagal sebelum konteks pertama'); raise SystemExit
d = json.loads(p.read_text(encoding='utf-8'))
inv = d.get('inventory', [])
siap = [e for e in inv if e.get('status') == 'selesai']
print(f"konteks selesai : {len(siap)}/20")
print(f"halaman dilawati: {sum(len(e.get('pages') or []) for e in siap)}")
belum = [f"{e['viewport']}|{e['identity']}" for e in inv if e.get('status') != 'selesai']
print('BELUM selesai   :', ', '.join(belum) or '(tiada)')
PY

echo "── keputusan playwright: $KEPUTUSAN"
exit $KEPUTUSAN
