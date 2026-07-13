# Laporan Kesediaan Semasa — Diwan SPDM

**Tarikh:** 13 Julai 2026

**Baseline kod:** `main` — fungsi terakhir `d9fee0c`

**Status yang tepat:** **kod siap dan diuji; SPDM belum live. Gateway WhatsApp live tetapi shared provisioning secret belum dipasang.**

Dokumen lama bertarikh 9 Julai yang menyebut 128 ujian dan OCR sebenar belum diuji telah digantikan oleh laporan ini. Konteks lengkap berada di [`HANDOVER-LENGKAP-A-Z.md`](HANDOVER-LENGKAP-A-Z.md); urutan go-live berada di [`WHAT-TO-DO-NEXT.md`](WHAT-TO-DO-NEXT.md).

## 1. Ringkasan gate

| Kawasan | Status | Bukti/halangan |
|---|---|---|
| Keselamatan P0 | Lulus kod/ujian | authorization, tenant/sensitivity query, append-only log, signed file/artifact |
| Multi-tenant | Lulus kod/ujian | 9-role matrix, route/action/query/job/search/webhook/email isolation |
| Dokumen/OCR | Lulus local + browser | dua imej sebenar -> OCR siap, derived PDF dan carian |
| Workflow pejabat | Lulus kod/ujian | inbox, klasifikasi, rekod/fail, minit, approval, billing, retensi/pelupusan |
| Gateway WhatsApp | Live/separa | health hijau, 2/2 sesi connected; provisioning secret masih missing |
| SPDM production | **Belum** | host/domain/path/SSH belum dikenal pasti |
| IMAP production | **Belum** | App Password/secret dan live host belum tersedia |
| COS/Meili/SMTP/backup production | **Belum dibuktikan** | memerlukan host SPDM production |
| Go-live | **Blocked oleh input/dependency** | ikut runbook `WHAT-TO-DO-NEXT.md` |

## 2. Ujian SPDM

### Keputusan automatik

- Suite penuh penutup pada 13 Julai: **190 passed, 647 assertions**, 123.09 saat.
- Targeted `OcrPipelineTest` pada 13 Julai: **2 passed, 9 assertions**.
- Ujian meliputi tenant isolation, matriks 9 role, sensitiviti, signed download, webhook WhatsApp, notification routing, e-mel intake, OCR/search, data integrity, billing, retensi, pelupusan dan workflow pejabat.
- CI run [`29214854569`](https://github.com/hakimalek27/Sistem-Pengurusan-Dokumen-Masjid/actions/runs/29214854569) direkodkan hijau untuk PostgreSQL 16, Redis 7, Meilisearch, OCR sebenar, full suite, runtime smoke, asset build serta image Docker `app` dan `web`.
- GitHub CLI local kini gagal refresh run dengan `401 Bad credentials`; ini isu credential CLI, bukan keputusan test aplikasi.

### Nota larian 13 Julai

Satu larian penuh pertama dihentikan oleh timeout alat 120 saat. Proses ujian yang sempat bertindih dengan larian kedua menyebabkan satu kegagalan retry OCR pada fake storage: **189 passed, 1 failed**. Selepas tiada proses bertindih, targeted OCR retry lulus **2/2**. Keputusan full suite terakhir selepas dokumentasi hendaklah direkod di bawah:

> **Final verification:** `190 passed (647 assertions)` — lulus, 123.09 saat, selepas memastikan hanya satu runner aktif.

## 3. Bukti OCR sebenar

| Record local | Fail | Status | OCR | Derived | Frasa carian |
|---:|---|---|---:|---|---|
| 14 | `WhatsApp Image 2026-07-10 at 12.39.18.jpeg` | `siap` | 916 aksara | `searchable.pdf` | `MESYUARAT JAWATANKUASA` |
| 15 | `WhatsApp Image 2026-07-10 at 12.39.18 (1).jpeg` | `siap` | 1,165 aksara | `searchable.pdf` | `PENCERAHAN HUKUM` |

Browser E2E:

- Google Chrome upload -> queue OCR -> halaman OCR -> carian MAM: lulus, 32.9 saat.
- Carian tenant MAN bagi frasa unik MAM: tiada hasil, lulus, 20.3 saat.
- In-app Chrome MCP tiada tab/browser terpasang; standalone Chrome melalui Playwright digunakan.

## 4. Jaminan P0 yang dilaksanakan

- `SensitiveAccessLog` tidak boleh update/delete dan resource/policy read-only.
- Strict authorization/policy untuk resource serta custom action.
- Query senarai/carian menapis tenant dan sensitiviti sebelum hasil dipulangkan.
- Secure file, invois, sijil dan eksport memerlukan auth, signed URL dan policy.
- Job OCR/eksport membawa `mosque_id` dan menapis payload tenant.
- WhatsApp session, API key, sender, keyword slot, message dedup dan recipient berada dalam tenant.
- E-mel memerlukan alias tepat, tenant aktif, toggle, allowlist dan keyword.
- Media path mempunyai prefix `tenants/{mosque_id}`.
- Retensi/pelupusan, billing/invois dan membership melalui service yang memvalidasi domain/tenant.

## 5. Keputusan gateway WhatsApp

### Local

- Laravel gateway: **168 passed, 488 assertions**.
- `go vet ./...`: lulus.
- `go test ./...`: lulus.
- OpenAPI linter terdahulu: 100/100 tanpa warning; scorecard B 80.53.

### Production pada 13 Julai 2026

| Semakan | Keputusan |
|---|---|
| Remote HEAD | `d4c9b62` |
| `https://wassap.wehdah.my/up` | HTTP 200 |
| Provision tanpa HMAC | HTTP 401 |
| `wassap-engine` | active |
| `wassap-queue` | active |
| `wassap-scheduler` | active |
| Engine DB | `db_ok=true` |
| Sesi | 2 connected / 2 total |
| `DIWAN_PROVISIONING_SECRET` | **MISSING** |

Backup sebelum deployment gateway:

- DB: `/home/ubuntu/wassap-backups/wassap-wassap_multitenant-20260713-081859.sql.gz`
- Release lama: `/var/www/wassap-old-20260713-081907`
- Binary lama: `engine/bin/engine.pre-diwan-20260713`

## 6. Blocker production SPDM

1. Domain, host/IP, user SSH dan path production SPDM belum diketahui.
2. Shared secret belum dipasang serentak sebagai `WHATSAPP_PROVISIONING_SECRET` dan `DIWAN_PROVISIONING_SECRET`.
3. `WHATSAPP_WEBHOOK_URL` public HTTPS belum wujud.
4. `IMAP_PASSWORD` production belum tersedia; local kosong dan intake tidak aktif.
5. PostgreSQL/Redis/Horizon/COS/Meili/SMTP/IMAP/backup production belum boleh diuji tanpa host SPDM.
6. WhatsApp/e-mel dokumen sebenar ke SPDM live belum boleh diuji tanpa URL live.
7. Backup restore drill production SPDM belum dilakukan.

## 7. Gate yang masih wajib sebelum label “live”

- semua dependency dan secret production sah;
- deploy/migrate/cache/service health hijau;
- provisioning dan QR pairing tenant pilot berjaya;
- muat naik, WhatsApp dan e-mel sebenar masuk tenant tepat;
- OCR dan carian lulus;
- negative cross-tenant matrix lulus;
- notifikasi keluar dari nombor tenant tepat;
- signed download/artefak authorize dengan betul;
- backup dan restore drill lulus;
- rollback tersedia dan bukti Pass/Fail/Evidence disimpan.

## 8. Pengisytiharan akhir

Projek mempunyai rangka domain dan perlindungan tenant yang kuat serta bukti ujian local/CI/browser. Namun, kenyataan **“semua OK/live” belum benar untuk SPDM** sehingga input host dan gate production di atas selesai.

Kenyataan semasa yang sah:

> **Gateway WhatsApp live dan sihat. Kod SPDM siap serta diuji. Production SPDM masih menunggu destinasi deployment, shared secret, IMAP dan verifikasi dependency/live E2E.**
