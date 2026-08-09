#!/usr/bin/env bash
# F8 §9.1 — jalankan SEBAHAGIAN konteks matriks, satu proses setiap satu, TANPA menyentuh fixture.
#
# Mengapa berasingan daripada `latihan-9.1-tempatan.sh`: skrip itu menyediakan DAN membersihkan
# fixture setiap larian, jadi ia mesti tamat dalam satu invokasi. Pada mesin ini larian panjang
# tidak bertahan (tugas latar dihentikan dari luar berulang kali), jadi matriks perlu dijalankan
# sebagai beberapa KETULAN pendek yang berkongsi satu fixture. Inventori hidup pada cakera dan
# dikunci pada `run_tenant`, jadi ketulan terkumpul dengan sendirinya.
#
# Fixture disediakan dan DIBERSIHKAN oleh pemanggil:
#   php artisan diwan:audit-fixture prepare --run=<uuid> --json=<fixture.json>
#   ... jalankan ketulan ...
#   php artisan diwan:audit-fixture cleanup --run=<uuid> --force
#
# Guna:  FIXTURE=<fixture.json> bash .../jalan-konteks.sh desktop:public desktop:superadmin …
set -uo pipefail

: "${FIXTURE:?FIXTURE=<laluan fixture.json> wajib}"
BASE="${E2E_BASE_URL:-http://127.0.0.1:8095}"
case "$BASE" in http://127.0.0.1:*|http://localhost:*) ;; *) echo "TOLAK: tempatan sahaja ($BASE)" >&2; exit 2 ;; esac

OUT="${OUT_DIR:-Audit Review Round Robin/bukti/plan-f8/latihan-9.1}"
export E2E_PRODUCTION=1
export E2E_BASE_URL="$BASE"
export E2E_PROD_TENANT="$(python3 -c "import json,sys;print(json.load(open(sys.argv[1],encoding='utf-8'))['slug'])" "$FIXTURE")"
export E2E_PROD_ROLE_ACCOUNTS="$(python3 -c "import json,sys;print(json.dumps(json.load(open(sys.argv[1],encoding='utf-8'))['role_credentials']))" "$FIXTURE")"
export E2E_PROD_SUPERADMIN_EMAIL="${E2E_PROD_SUPERADMIN_EMAIL:-superadmin@diwan.test}"
export E2E_PROD_SUPERADMIN_PASSWORD="${E2E_PROD_SUPERADMIN_PASSWORD:-password}"
export E2E_PROD_ROLE_LOGIN_DELAY_MS="${E2E_PROD_ROLE_LOGIN_DELAY_MS:-0}"
export E2E_PROD_REPORT="$OUT/route-manifest-TEMPATAN.json"

PERKONTEKS="${PERKONTEKS_MS:-240000}"

# ⚠️ Penjaga yang lahir daripada kesilapan SEBENAR: satu larian pengesahan DUA konteks ditulis
# ke atas laporan yang sudah mengandungi larian LENGKAP 20/20, dan commit berikutnya
# menggantikan artifak bukti dengan versi 2/20. Ia dipulihkan daripada git, tetapi corak itu
# akan berulang. Menimpa hasil LENGKAP dengan larian tenant BERBEZA kini perlu izin eksplisit.
if [ -f "$OUT/route-manifest-TEMPATAN.json" ]; then
    LENGKAP=$(python3 -c "
import json,sys
try: d=json.load(open(sys.argv[1],encoding='utf-8'))
except Exception: print(''); raise SystemExit
siap=[e for e in d.get('inventory',[]) if e.get('status')=='selesai']
print(d.get('run_tenant','') if len(siap)>=20 else '')
" "$OUT/route-manifest-TEMPATAN.json" 2>/dev/null)
    if [ -n "$LENGKAP" ] && [ "$LENGKAP" != "$E2E_PROD_TENANT" ] && [ "${IZIN_TIMPA:-0}" != "1" ]; then
        echo "TOLAK: $OUT/route-manifest-TEMPATAN.json mengandungi larian LENGKAP 20/20" >&2
        echo "       (tenant $LENGKAP). Larian ini guna tenant BERBEZA dan akan memusnahkannya." >&2
        echo "       Guna OUT_DIR=<laluan lain> untuk larian pengesahan, atau IZIN_TIMPA=1." >&2
        exit 3
    fi
fi

selesai_kah() {
    python3 -c "
import json,sys
try: d=json.load(open(sys.argv[1],encoding='utf-8'))
except Exception: sys.exit(1)
for e in d.get('inventory',[]):
    if e.get('viewport')==sys.argv[2] and e.get('identity')==sys.argv[3] and e.get('status')=='selesai':
        sys.exit(0)
sys.exit(1)" "$E2E_PROD_REPORT" "$1" "$2" 2>/dev/null
}

for SPEC in "$@"; do
    VP="${SPEC%%:*}"; ID="${SPEC##*:}"
    if selesai_kah "$VP" "$ID"; then echo "── $VP · $ID  (sudah selesai, dilangkau)"; continue; fi
    printf '── %s · %s\n' "$VP" "$ID"
    npx playwright test --project=production-readonly --reporter=line \
        --global-timeout "$PERKONTEKS" --grep "$VP . $ID" > "$OUT/log-$VP-$ID.txt" 2>&1 &
    PID=$!
    MAKS=$(( PERKONTEKS / 2000 )); N=0
    while kill -0 "$PID" 2>/dev/null; do
        # Inventori ditulis secara ATOMIK, jadi sebaik konteks muncul sebagai `selesai` buktinya
        # sudah selamat pada cakera dan bakinya hanya pembongkaran 300s yang tidak mengukur apa2.
        if selesai_kah "$VP" "$ID"; then kill "$PID" 2>/dev/null; echo "   ✔ selesai (ditamatkan awal)"; break; fi
        N=$((N + 1)); [ "$N" -ge "$MAKS" ] && { echo "   ✘ HAD MASA"; kill "$PID" 2>/dev/null; break; }
        sleep 2
    done
    wait "$PID" 2>/dev/null
    grep -E "passed|failed|Error:" "$OUT/log-$VP-$ID.txt" 2>/dev/null | head -2 | sed 's/^/     /'
done

PYTHONIOENCODING=utf-8 python3 -c "
import json,sys
d=json.load(open(sys.argv[1],encoding='utf-8'))
s=[e for e in d['inventory'] if e['status']=='selesai']
print(f\"── inventori: {len(s)}/20 selesai · {sum(len(e.get('pages') or []) for e in s)} halaman · rosak={d.get('amaran_rosak') or '-'}\")
" "$E2E_PROD_REPORT"
