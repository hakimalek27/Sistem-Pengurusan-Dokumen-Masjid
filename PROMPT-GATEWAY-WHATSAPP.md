# PROMPT untuk Claude — Kerja pada Gateway `wassap.wehdah.my`

> Salin keseluruhan blok di bawah ini ke sesi Claude yang mempunyai akses kepada server/repo
> **Whatsapp Multi Tenant** (`wassap.wehdah.my`, `C:\Projek Coding\Whatsapp Multi Tenant`).
> Ia lengkap dan berdiri sendiri.

---

## PERANAN
Anda bekerja pada **gateway WhatsApp multi-tenant "Wassap"** (Laravel 12 + Postgres + Livewire `backend/`,
enjin Go whatsmeow `engine/`) yang di-deploy di **https://wassap.wehdah.my**. Satu consumer baharu —
sistem **Diwan (SPDM)** di **https://bakwim.my** — perlu menyediakan (provision), memasang (pair) dan
menghantar/menerima mesej WhatsApp melalui gateway ini. Tugas anda ialah menyiapkan **bahagian gateway**
supaya integrasi itu berfungsi.

## PERATURAN TEGAR (keselamatan produksi)
1. **JANGAN sentuh servis/laluan lain** selain `wassap.wehdah.my`. Ikut `SERVER_RUNBOOK.md` jika ada.
2. **JANGAN echo/print/log nilai rahsia** (secret) di terminal, CI, atau fail dokumentasi. Guna `sudoedit`.
3. **JANGAN commit `.env`/kunci/token**. Jangan dedah kandungan `.env`.
4. Sahkan salinan aktif dahulu: `git remote -v` + `git log -1`. Jangan edit salinan lapuk.
5. Jika kontrak API sedia ada di gateway **berbeza** daripada yang didokumen di bawah — **BERHENTI dan
   laporkan perbezaan**; jangan ubah tingkah laku gateway sedia ada yang mungkin dipakai tenant lain.
6. Selepas setiap perubahan hijau, verifikasi dengan output sebenar dan tampal dalam laporan.

---

## OBJEKTIF SEGERA (satu perkara)
Tetapkan **secret provisioning yang dikongsi** supaya SPDM boleh lulus pengesahan HMAC gateway.

- SPDM menghantar `WHATSAPP_PROVISIONING_SECRET`-nya di dalam setiap permintaan provisioning (via HMAC).
- Gateway mengesahkannya menggunakan env **`DIWAN_PROVISIONING_SECRET`**.
- **Kedua-dua nilai MESTI SAMA.**

### Cara dapatkan nilai (selamat — jangan taip di chat)
Nilai ada di server SPDM. Operator jalankan (di terminal selamat sendiri):
```bash
ssh ubuntu@43.156.242.188 "sudo grep -m1 '^WHATSAPP_PROVISIONING_SECRET=' /opt/diwan/.env"
```
Salin nilai selepas `=` (64 aksara hex). Masukkan ke gateway melalui `sudoedit` (langkah bawah).
> Secret webhook TIDAK perlu ditetapkan manual di gateway — SPDM menghantarnya (`webhookSecret`)
> dalam payload provisioning; gateway simpan per-tenant (terenkripsi) automatik.

### Langkah pelaksanaan (di server gateway)
```bash
# 1. Edit .env gateway secara interaktif (sahkan laluan sebenar dahulu; lazimnya /var/www/wassap.wehdah.my)
sudoedit /var/www/wassap.wehdah.my/.env
#    Tambah/kemas kini baris (tampal nilai dari SPDM, JANGAN echo):
#    DIWAN_PROVISIONING_SECRET=<nilai 64-hex dari SPDM>

# 2. Muat semula config
cd /var/www/wassap.wehdah.my
php artisan optimize:clear
php artisan config:cache
sudo systemctl reload php8.4-fpm   # sesuaikan versi PHP-FPM sebenar

# 3. Sahkan wujud TANPA cetak nilai
grep -Eq '^DIWAN_PROVISIONING_SECRET=.+$' /var/www/wassap.wehdah.my/.env && echo SET || echo MISSING
```

---

## KONTRAK API (mesti dipadankan oleh gateway)
SPDM memanggil endpoint berikut. Sahkan implementasi gateway anda **sepadan tepat**.

### A. Provisioning tenant — gateway MENERIMA daripada SPDM
- `POST /internal/v1/tenants/provision`
- Header:
  - `X-Diwan-Timestamp: <unix timestamp>`
  - `X-Diwan-Signature: sha256=<hex>` di mana
    `hex = hash_hmac('sha256', timestamp + "." + rawBody, DIWAN_PROVISIONING_SECRET)`
- Body JSON (slash TIDAK di-escape):
  ```json
  {
    "externalId": "spdm-production:mosque:<id>",
    "organizationName": "<nama masjid>",
    "apiKey": "sk_<40 aksara>",
    "webhookUrl": "https://bakwim.my/api/webhooks/whatsapp",
    "webhookSecret": "<>=32 aksara>"
  }
  ```
- Gateway MESTI: sahkan HMAC → cipta/simpan tenant dgn `apiKey` yang diberi → simpan
  `webhookUrl`+`webhookSecret` (terenkripsi) untuk tenant ini → pulangkan
  `{"success": true, "data": {"tenantId": "<id>"}}`.
- HMAC salah/tiada → **HTTP 401** (badan kosong/`success:false`).

### B. Sesi & mesej — gateway MENERIMA (auth header `X-API-Key: <apiKey tenant>`)
| Endpoint | Body | Pulangan |
|---|---|---|
| `POST /v1/sessions` | `{device_name, phone?}` | `{success:true, data:{session_id, status}}` |
| `GET /v1/sessions/{session}/qr` | — | `{success:true, data:{qr, ...}}` |
| `GET /v1/sessions/{session}/status` | — | `{success:true, data:{status, phone}}` (`status="connected"` bila paired) |
| `POST /v1/messages/send` | `{session_id, to, message}` | `{success:true, data:{...}}` |
| `GET /health` dan `GET /` | — | 200 (health/ping) |

### C. Webhook masuk — gateway MENGHANTAR ke SPDM bila mesej tiba
- `POST https://bakwim.my/api/webhooks/whatsapp`
- Header tandatangan: `X-Signature: <hex>` **atau** `X-Diwan-Signature: sha256=<hex>` di mana
  `hex = hash_hmac('sha256', rawBody, webhookSecret_tenant_ini)`
- Body JSON mesti mengandungi sekurang-kurangnya:
  ```json
  {
    "event": "message.received",
    "session_id": "<session tenant>",
    "message_id": "<id unik>",
    "from": "<nombor penghantar>",
    "is_group": false,
    "from_me": false,
    "text": "<teks/caption>",
    "media": { "filename": "...", "mime": "...", "content_base64": "..." }
  }
  ```
- SPDM hanya proses `event === "message.received"`; ia abai `from_me`/`is_group`, dedup ikut `message_id`,
  dan hanya terima daripada nombor ahli berdaftar. (Media base64 atau URL boleh — sahkan format sedia gateway.)

---

## VERIFIKASI / GATE (tampal output sebenar)
- [ ] `DIWAN_PROVISIONING_SECRET` = SET (tanpa cetak nilai).
- [ ] `curl -s -o /dev/null -w "%{http_code}" https://wassap.wehdah.my/up` → **200** (tidak terjejas).
- [ ] Provisioning **tanpa** HMAC / HMAC salah → **401**.
  ```bash
  curl -s -o /dev/null -w "%{http_code}\n" -X POST https://wassap.wehdah.my/internal/v1/tenants/provision \
    -H 'Content-Type: application/json' -d '{}'
  ```
- [ ] (Selepas SPDM cuba "Aktifkan WhatsApp") log gateway tunjuk provisioning **berjaya** untuk
  `externalId=spdm-production:mosque:<id>`, tiada `last_error`.
- [ ] Tiada nilai secret muncul dalam shell history/log.

## SELEPAS GATEWAY SIAP (dilakukan di SPDM oleh operatornya — bukan tugas anda)
1. SPDM → Tetapan Masjid → **Aktifkan WhatsApp** (status `linked`).
2. **Pasangkan Nombor** → **scan QR** dengan telefon rasmi masjid → status `connected`.
3. Uji hujung-ke-hujung: ahli hantar `spdm` → slot 10 minit → hantar PDF → rekod masuk Peti Masuk + OCR + carian.
4. Notifikasi keluar sampai ke `phone_wa` ahli yang opt-in.

## RUJUKAN SPDM (sisi consumer — sudah SIAP)
- `WHATSAPP_DRIVER=gateway`, `WHATSAPP_GATEWAY_URL=https://wassap.wehdah.my`,
  `WHATSAPP_WEBHOOK_URL=https://bakwim.my/api/webhooks/whatsapp`, `DIWAN_INSTANCE_ID=spdm-production`.
- Webhook SPDM `POST /api/webhooks/whatsapp` menolak permintaan tanpa HMAC (401) — disahkan.
- Adapter tunggal jika kontrak berbeza: SPDM `app/Services/WhatsAppGateway.php` +
  `WhatsAppIntegrationService.php` (rujukan kontrak, jangan diubah tanpa keperluan).
