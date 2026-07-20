# HANDOVER — Diwan (SPDM) Produksi bakwim.my

**Kemas kini:** 2026-07-20 · **Status:** LIVE di https://bakwim.my (Cloudflare Full strict, COS, login password, Brevo SMTP). Sesi 18 Jul: Email intake LIVE PENUH, WhatsApp E2E LENGKAP (pilot MAMAD), bug OCR Ghostscript dibaiki (`fe5744a`).

**Sesi 20 Jul (malam) — 3 BUG PEMILIK + 1 BUG BAHARU DIBAIKI + AUDIT E2E (commit `3459134`+`987a17e`+`6c74f37`+`01aa19c`; LIVE `01aa19c`):** pemilik lapor upload UI gagal, e-mel intake tak masuk, notifikasi Telegram tak sampai. **Punca sebenar (semua disahkan bukti kod + LIVE):** **(A) Upload UI** — `config/livewire.php` TIADA → temp Livewire guna disk lalai `cos` (S3) → pelayar PUT pra-tandatangan ke COS tanpa CORS → SEMUA upload UI gagal (dev=local, tak reproduce). Fix: `config/livewire.php` `temp.disk=local`. **BUKTI LIVE: rangkaian `POST /livewire/upload-file`=200 (bukan COS) → rekod #20 DB+COS+OCR.** **(B) E-mel intake** — kunci job `WithoutOverlapping('diwan-fetch-mail')` tanpa expiry (`expiresAfter=0`) KEKAL selepas container recreate mid-run → fetch-mail dilangkau SELAMANYA (bukan allowlist — pengirim sudah whitelisted!). Fix: `expireAfter(600)` + scheduler `withoutOverlapping(10)` + lepaskan kunci. E-mel pemilik (#18) kini di Peti Masuk. **(C) Telegram** — `notify_telegram` lalai DB=false; webhook `/start` hanya simpan `chat_id` → `via()` SKIP walau "Bersambung". Fix: `/start` set `notify_telegram=true` + wrapper `TelegramChannel` (log NotificationLog sent/failed + telan ralat) + `TestNotification::toTelegram` + TTL 15→60min + balasan token luput. **BUKTI: NotificationLog telegram `sent` to=667224545.** **(E, baharu) OCR imej** — `img2pdf` ABORT pada EXIF putaran tak sah (foto telefon). Fix: `--rotation=ifvalid`. Rekod #18 kini `ocr=siap`. **+ Intake e-mel awam** (permintaan pemilik): mana-mana pengirim diterima (had 10/jam), allowlist 100/jam (`MAIL_ALLOW_PUBLIC_INTAKE`). **+ indicator**: widget/StatusSambungan IMAP "Dimatikan" bila disabled; stat Telegram baharu. **+ backup**: `backup:monitor` harian + `BACKUP_NOTIFY_EMAIL` + `docs/RESTORE-RUNBOOK.md`. Bukti: Pest **342✓/1skip**, Pint, CI HIJAU (`01aa19c`), deploy rebuild app (tiada aset Vite → nginx tak rebuild). **AUDIT E2E MENYELURUH LIVE (Chrome MCP, `AUDIT-E2E-2026-07-20.md`) — 19 fungsi diuji SEBENAR di UI (input→output→hasil DB), TANPA skip** (pemilik tegas: jangan halusinasi/page-load): kitaran teras upload(rangkaian 200→DB→COS→OCR)→klasifikasi(18 medan+Choices.js→difailkan+enclosure+our_ref auto)→edarkan-minit(multi-select→routed)→minit-saya→tanda-selesai; + mohon-kelulusan(#7)/pindah-fail(→fail2)/jemput-ahli(user#8)/tutup-fail/peraturan-retensi(#19)/tambah-storan(order#1+invois+idempotency)/pelupusan(gate retensi betul)/paparan-Teks-OCR/laporan-CSV/kelulusan-lulus; superadmin ubah-kuota/mark-paid(order→dibayar+addon aktif); awam token-magic-tak-sah/secure-file→403. 5 persona log masuk tanpa bounce. **Aksi bergate kata laluan** (Lulus/Mark-Paid): gate "Sahkan Kata Laluan" DISAHKAN render (semakan keselamatan sebenar), keputusan via service (polisi larang taip kata laluan) — telus, bukan skip. **Kaedah pandu borang Filament v4 via Chrome MCP disimpan memori** ([[spdm-deploy-lessons]]): aksi Livewire=`element.click()` JS (klik-ref sintetik tak cetus); combobox=`form_input(ref,text)`; Choices butang=buka+klik `<li>` JS; medan biasa=native setter+events; nombor=`form_input`. ⚠️ **Gotcha deploy baharu**: `git reset` server GAGAL "Permission denied" (fail kod milik root dari deploy lepas) → `sudo chown -R ubuntu:ubuntu app config routes resources tests docs .git` (JANGAN chown storage/.env — container www-data perlu tulis).

**Sesi 20 Jul (petang) — MIRROR GOOGLE DRIVE + MAGIC LINK AUTO-LOGIN + FIX SALIB (commit `1bc5cc0`+`9789bfd`+`c15e8d6`+`b5bff77`+`166f421`+`a0c8844`):** empat kerja pemilik. **(1) Mirror Google Drive per-tenant boleh-browse** (§4.6 dipinda, kelulusan PDPA pemilik): `google/apiclient` (§3.3), `SPDM/Backup/{slug}/{klasifikasi}/{fail}/…`; auto-cipta folder bila masjid diluluskan, auto-upload bila diklasifikasikan (afterCommit), padam bila dilupus (selaras sijil), reconcile setiap jam + DB dump + prune, verify. **ISOLASI** dijamin (id induk tersimpan + assert mosque_id + refetch berskop; ujian tamper silang-tenant = sifar upload). Superadmin `/admin` Tetapan Platform → Sambung/Uji Google Drive (OAuth akaun pemilik). Litar 6j + alert bila token dibatal/kuota penuh. **(2) Magic link auto-login notifikasi** (§15.1″): notifikasi mention (minit/kelulusan/peti masuk) bawa pautan magic PER PENERIMA (TTL 72j) → klik = auto-login terus ke rekod, tiada login manual; **interstisial** (GET tak guna token → bot pratonton WA/TG tak bakar token; POST guna); **fix bounce** (`password_hash_web`); guard open-redirect. **(3) Fix salib landing** → bulan sabit. **(4) Re-OCR rekod Office lama**. Bukti: Pest **326✓/1 skip**, Pint bersih, `npm run build` OK, Playwright registration+office-workflow LULUS. ⚠️ Tindakan pemilik SEKALI: Google Cloud Console → OAuth consent **PUBLISHED** (mod Testing = refresh token mati 7 hari!) + client id/secret → Tetapan Platform → Sambung. Lihat `DIWAN-SPEC-ADDENDUM-2026-07.md` v2.5.

**Sesi 20 Jul — EKSTRAK TEKS OFFICE (Fasa 2) + PENJAJARAN TATACARA ANM + NAIK TARAF OCR (commit `081ff2e`+`0d72f57`):** kajian PENUH 2 dokumen rasmi (Tatacara Pengurusan Rekod DDMS ANM 2020 + Panduan Pengguna DDMS 2.0 MAMPU) → 3 kerja. **(A) Ekstrak teks Office**: `App\Support\OfficeTextExtractor` (PhpWord/PhpSpreadsheet + sandaran native ZipArchive+XMLReader; pptx native; xlsx >8MB→native streaming; korup→null gagal-anggun); `ProcessOcrJob` laluan Office kini ekstrak `ocr_text` (dulu no-op) → **DOCX/XLSX/PPTX kini boleh dicari kandungan penuh**. Pakej baharu `phpoffice/phpword ^1.4`+`phpoffice/phpspreadsheet ^5.9` (kelulusan pemilik, pindaan §3.3). **(B) Penjajaran tatacara**: u.p. **hibrid** (teks bebas + datalist ahli; padan ahli→cadang penerima tindakan+s.k. Pengerusi, `RecordTypeSchema::attentionSuggestion`); **Ruj. Kami auto** = file_no(enclosure) bila kosong (§10); **Tarikh Terima prefill** = tarikh masuk Peti Masuk (carta 8.1); **amaran fail 100 kandungan** (§6.9.1); **s.k. boleh Balas & Edarkan** (§6.4.2, `MinitPolicy::reply`). **(C) Naik taraf OCR**: ocrmypdf +`--clean`+`--optimize 1` (imej Docker); penormal teks (sambung suku kata terpotong, kemas whitespace); **snippet + highlight `<mark>` (escape-selamat)** dalam Carian. Bukti: Pest **296✓/1 skip**, Pint bersih, `npm run build` OK, Playwright **office+explore+registration LULUS** (server e2e tunggal DB buangan). Ujian baharu: OfficeTextExtractionTest(7)+SearchSnippetTest(6)+TatacaraAlignmentTest(7). Lihat `DIWAN-SPEC-ADDENDUM-2026-07.md` v2.4. ⚠️ Nota deploy: `docker compose build app` akan `composer install` pakej PhpOffice baharu (pantau); selepas deploy re-index `scout:import` + re-OCR rekod Office lama jika mahu ia boleh dicari.

**Sesi 19 Jul (lewat) — GATE GO-LIVE + FIX BORANG KLASIFIKASI (fix `268f860`):** jalankan 3 gate baki atas arahan pemilik. **(1) Isolasi silang-tenant di server SEBENAR (data produksi 2 tenant mamad id1/smoke id2):** 6 model (Record/RegistryFile/ClassificationNode/Minit/Approval/StorageOrder) **sifar bocor** `forMosque`; global scope Filament (login Admin MAMAD) → `Record::count()=8` MAMAD sahaja (bukan 12), rekod/fail smoke `find()`=NULL; polisi silang-tenant blok `view`; SearchService fail-closed (bukan ahli→kosong); sesi WA + alias e-mel terasing; RetentionRule platform-NULL (18) dikongsi ikut reka bentuk §5.11. **LULUS PENUH.** **(2) Restore drill:** `backup:run --only-db` → `cos_backup` (ap-jakarta, 3 zip); tarik balik + unzip → dump `postgresql-diwan.sql` 216KB, **32 CREATE TABLE + 32 COPY**, semua jadual utama hadir = BOLEH DIPULIHKAN. **(3) Pantau log:** live 3j = **0 ralat app / 0 nginx 5xx / 0 failed_jobs**; TAPI laravel.log dedah **BUG LIVE** `Filament\Forms\Components\Select::modifyQueryUsing does not exist` — borang **Cipta/Edit Klasifikasi CRASH** (pemilik terkena 12:20, userId 1). **FIX `268f860`:** `->relationship('parent','title')->modifyQueryUsing(...)` (method berantai tak wujud Filament v4) → pindah closure skop ke **argumen ke-3 relationship()** (Select.php:781) + `ClassificationNodeFormTest` (4 ujian). **DISAHKAN LIVE via Chrome** (tenant smoke): borang render penuh + dropdown "Nod Induk" muat nod skop-tenant (Aktiviti & Program, Audit & Pemeriksaan…). Pest **277✓/1 skip**, Pint bersih, deploy rebuild, `/up` 200, 7 container healthy. **Nota:** (a) config `monitor_backups` diperbetul `local`→`cos_backup` (buat `backup:list` jujur; `backup:monitor` belum dijadualkan — hanya `backup:run` 02:30). (b) **OCR 6/13 rekod `gagal`** (ralat "trailer dictionary/xref" = PDF input rosak pada fail ujian pilot; OCR gagal-anggun betul — **perlu semakan pemilik dgn dokumen sebenar**, bukan pepijat kod). ⚠️ Gotcha kekal: magic-link semasa sesi lain aktif → bounce+termakan (jana token baharu selepas log keluar).

**Sesi 19 Jul — PUSINGAN 2 LIVE (HEAD `ae95d6e`):** pasca-ujian pemilik, 6 kumpulan isu dibaiki + deploy. (F0) **CI GitHub HIJAU** (pint + actions v5 + rate-limit CI). (F1) **format dokumen berpusat** `App\Support\AllowedFormats` — hanya PDF/DOC(X)/XLS(X)/PPT(X)/TXT/JPG/PNG; lain ditolak + notifikasi 3 saluran (webp keluar). (F2) **intake e-mel boleh-lihat** — kata kunci kini PILIHAN, penolakan dilog + notifikasi admin (hapus lesap senyap); **emel MAMAD dipulihkan**. (F3) **Telegram via UI superadmin** (token tersulit DB, Set Webhook). (F4) **wizard onboarding muncul** (redirect + banner). (F5) **tema Filament v4 + UI/UX penuh** (logo, nav berkumpulan, badge, jadual, widget). Fasa saya = commit `fasa2-0`…`fasa2-8` (hingga `685415e`). Lihat `DIWAN-SPEC-ADDENDUM-2026-07.md` (v2.3).

**Audit tambahan pemilik (selepas fasa2-8):** `ff5f844` *audit: harden document workflows and UI* (23 fail — middleware `AddSecurityHeaders`, header keselamatan, AppServiceProvider, InboxIngestService, WhatsAppWebhookController, BillingService, ApprovalService, dll.) + `ae95d6e` *ops: enforce nginx limits* (nginx-ssl.conf: rate-limit `diwan_auth` 5r/m, `limit_conn` 40, `client_max_body_size` 30M). **Kedua-dua LIVE**: imej app dibina semula 09:06 (header keselamatan disahkan hidup: x-frame-options/x-content-type/referrer-policy/permissions-policy), nginx muat config baharu (nginx -t OK). **Keselarasan: `local = origin = server = ae95d6e`**, `/up` 200. Bukti gabungan: Pest **269✓/1 skip**, staging 9/9, smoke 9/9, Playwright semua LULUS.

**Sesi 19 Jul — REVIEW CODEX + E2E PRODUKSI (HEAD `86264e9`):** review mendalam A-Z kerja Codex (ff5f844+ae95d6e). **12/12 kategori dakwaan SAH** dalam kod. **Penemuan tunggal:** `RetentionRuleForm.php` = **dead code** (Codex keraskan fail mati) — borang hidup `RetentionRuleResource::form()` inline + `getEloquentQuery` skop tenant sudah selamat; ditambah guard eksplisit `EditRetentionRule::mutateFormDataBeforeSave` + `RetentionTenantScopeTest` (2 ujian). Bukti: Pint bersih, Pest **271✓/1 skip**, **CI HIJAU** (86264e9), Playwright **5/5** (🐛 kegagalan "klasifikasi-minit" dulu = **server hantu** 2× bind :8092; bunuh basi → LULUS; ini juga punca flake explore lama). Deploy: `local=origin=server=86264e9`, /up 200, smoke 9/9, staging 8/9 (smtp perlu `--mail-to`). **E2E Chrome produksi (tenant `smoke`):** superadmin panel PENUH (widget/tenant/pengguna/pesanan+modal batal/tetapan platform+Telegram webhook Berjaya/status/profil Telegram Bersambung); **workflow tenant LIVE** — kerani klasifikasi (modal ada SEMUA medan termasuk Edaran Minit) + minit Segera→pengerusi terima→Tanda Selesai; **isolasi** pengerusi-smoke→`/app/mamad`=**404**. ⚠️ Gotcha: magic-link semasa sesi lain aktif → bounce ke login (log keluar dulu); gate `EnsurePasswordIsSet` untuk ahli tanpa password.

**🐛 INSIDEN SPAM WhatsApp + FIX LIVE (`1f113e3`, 19 Jul):** pemilik lapor nombor sesi MAMAD (60176811605) hantar mesej berulang ke nombor asing. **Punca disahkan 100%** (jadual `NotificationLog`): **37 balasan `wa_reject`** ("Maaf, nombor anda tidak berdaftar…") ke **6 nombor** (5 bukan-ahli, cth 60174632511×19, 60173070193×12) dalam letusan pantas — `WhatsAppInboundService` balas SETIAP mesej bukan-ahli **tanpa had kadar** → gelung ping-pong dengan auto-reply pihak lain. **FIX:** `replySuppressed()` — cooldown balasan tolak sekali/nombor/jam (`WHATSAPP_REJECT_COOLDOWN_MINUTES=60`) + pemutus litar sejagat (`WHATSAPP_REPLY_CAP=5`/`WINDOW=10min`) + tolak penerima kosong. Ujian: 6 mesej bukan-ahli → 1 balasan. Pest **272/1 skip**, deploy live, `WA_REJECT` berhenti (0 sejak deploy). Nota: `WhatsAppGateway::send` log ke `NotificationLog` (bukan Docker log) — guna jadual itu untuk audit hantar WA. Pilihan pemilik: naikkan cooldown / buang balasan bukan-ahli terus jika mahu 0 mesej ke orang asing.

**🔒 REKA BENTUK MUKTAMAD — intake WA kata-kunci-dahulu (`48c8b2f`, 19 Jul; LIVE):** siasatan tambahan sahkan 37 event webhook **TIADA `message_id`** (echo/sintetik). Refactor: Diwan **SENYAP sepenuhnya** melainkan penghantar hantar kata kunci TUNGGAL tepat (cth `spdm`), dalam tetingkap intake aktif, atau dokumen dgn kata kunci dlm kapsyen. Mesej biasa/echo/panjang tanpa kata kunci → **TIADA balasan** → gelung mustahil. `spdm`→tetingkap 10min→hantar dokumen. **Submission awam** (orang luar boleh hantar dokumen selepas `spdm`; `WHATSAPP_ALLOW_PUBLIC_INTAKE=false`=ahli sahaja); pengguna berdaftar masjid LAIN diblok (isolasi §18.37); had submission per nombor. Bukti: WhatsAppWebhookTest **17 lulus**, Pest **273/1 skip**, **ujian LIVE server** mesej bukan-kata-kunci → SENYAP disahkan. HEAD `48c8b2f`.

**Sesi 19 Jul — Naik taraf Fasa A–E LIVE (commit `ad45887`):** (A) hint silang-panel log masuk + throttle log IMAP; (B) **log masuk telefon-ATAU-e-mel** kedua-dua panel + **gate kata laluan pertama** + kredensial ahli (e-mel jadi PILIHAN); (C) **wizard onboarding** pendaftaran masjid; (D) **Telegram produksi** (command set-webhook + sambung akaun) + **WhatsApp platform** (alert superadmin) + **pemantauan sesi** (`diwan:check-wa-sessions` /10 min, alert 3-saluran); (E) audit + e2e. Bukti: Pest **234 passed/1 skip**, Pint passed, Playwright semua LULUS, prod **staging-check 9/9 + smoke 9/9 + /up 200**. **IMAP dibaiki** (App Password baru disahkan berfungsi). Lihat `DIWAN-SPEC-ADDENDUM-2026-07.md`.

**Login akaun MAMAD (kini):** boleh guna **telefon** (60176811605 admin / 60189030363 kerani / 60199654974 pengerusi) ATAU e-mel `@mamad.local` + kata laluan di `/app/login`. Akaun sudah ada kata laluan (tidak kena gate).

---

## 1. Infrastruktur

| Item | Nilai |
|---|---|
| Server | Tencent **Lighthouse** `Ubuntu-s0Hu` (lhins-mmc2juw3), Singapore, 2 vCPU / 2GB RAM / 30GB |
| IP awam | **43.156.242.188** (⚠️ bukan 43.156.71.249 — itu CVM lain) |
| SSH | `ssh ubuntu@43.156.242.188` (kunci `claude_deploy`, bind via Lighthouse SSH Keys/TAT) |
| Aplikasi | Docker Compose di `/opt/diwan` |
| Domain | `bakwim.my` — registrar **Exabyte**, NS **Cloudflare** (akaun Hakimalek27@gmail.com) |
| Swap | 3GB (RAM 2GB ketat) |

**Container (7):** app, worker (horizon), scheduler, nginx, db (postgres:16), redis:7, meilisearch:v1.12.

### Nota operasi PENTING
- 🐛 **ASET FRONTEND BERUBAH (blade/Filament/CSS) → WAJIB rebuild KEDUA-DUA imej `app` DAN `nginx`**: imej `nginx` (`diwan-web`) ada salinan `public/build` sendiri. Rebuild `app` sahaja → hash Vite baharu tapi nginx hidang hash lama → origin 404 → Cloudflare 503 → **UI Filament tak bergaya** (landing OK sebab CSS inline). Fix: `docker compose build app nginx && docker compose up -d --force-recreate app worker scheduler nginx`. (Insiden 20 Jul: rebuild app sahaja → UI panel pecah; dibaiki dgn rebuild nginx.) **Sahkan: `curl -sI https://bakwim.my/build/assets/<theme-hash>.css` = 200.**
- 🐛 **Pelajaran CI/deploy 20 Jul (jangan ulang)**: (a) skrin render **pecah/tak-bergaya BUKAN "transient"** — sahkan punca via `read_network_requests` (cari CSS/JS 4xx/5xx). (b) **Jangan pipe `gh run watch --exit-status` ke `tail`** — `$?` jadi exit `tail`, bukan CI; sahkan status sebenar `gh run list --json conclusion`. (c) Edit `composer.json` `extra`/`scripts` selepas `require` → jalankan `composer update --lock` (elak `composer validate` gagal CI). (d) **CI ≠ lokal**: CI guna PostgreSQL+**Redis dikongsi**+BACKUP_DISK berbeza; lokal SQLite+cache array. Route ber-`throttle` → `cache()->flush()` dlm `beforeEach` ujian; ujian baca disk backup → set `config('backup.backup.destination.disks',['cos_backup'])` eksplisit.
- Selepas **recreate `app`** → mesti `docker compose restart nginx` (nginx cache IP upstream → 502 jika tidak).
- Selepas **ubah `.env`** → `docker compose up -d --force-recreate app worker scheduler` (env_file dibaca hanya semasa container start; www-data tak boleh baca `.env` chmod 600 terus).
- `docker-compose.override.yml` (di server sahaja, tidak di git): port 80/443 + mount `docker/certs` + `nginx-ssl.conf`.

---

## 2. Yang SUDAH SIAP (sesi 2026-07-18)

### ✅ SSL Full (strict) + origin cert
- CSR dijana **di server** (`/opt/diwan/docker/certs/origin.key` — kunci privat tak pernah keluar server), ditandatangani oleh Cloudflare Origin CA (sah 15 tahun).
- nginx dengar 443 (`docker/nginx-ssl.conf`), sijil di `docker/certs/origin.{pem,key}`.
- Firewall **Lighthouse** dibuka port 443 (sebelum ni hanya 22/80 — punca 522 asal).
- Cloudflare mod **Full (strict)** + Always Use HTTPS. Universal SSL edge auto-renew selamanya.
- Bukti: `https://bakwim.my/up` → 200, `ssl_verify=0`, `Server: cloudflare`.

### ✅ COS (storan objek)
- Bucket utama `spdm-1455289506` (ap-singapore, private). Backup `spdm-backup-1455289506` (ap-jakarta, private + versioning + SSE-COS).
- Sub-user CAM `diwan-cos` (polisi **QcloudCOSFullAccess** sahaja — least privilege). Kredensial di `/opt/diwan/.env` (`COS_SECRET_ID`/`COS_SECRET_KEY`).
- `DIWAN_STORAGE_DISK=cos`, `FILESYSTEM_DISK=cos`, `BACKUP_DISK=cos_backup`. Diuji tulis/baca/padam kedua-dua bucket.

### ✅ Login kata laluan (fallback magic link)
- `/log-masuk` kini ada pautan **"Log masuk dengan kata laluan"** → `/app/login` (Filament).
- Halaman **Profil** ada aksi **"Tetapkan Kata Laluan"**.
- **Kesan:** boleh log masuk TANPA SMTP. Superadmin `azanmalek@maiwp.gov.my` — password **sudah ditukar** oleh operator (18 Jul; disimpan dalam pengurus kata laluan). Nilai awal dibuang dari dokumen atas sebab keselamatan (pernah ter-commit plaintext).

### ✅ WhatsApp (sisi SPDM sahaja)
- `WHATSAPP_DRIVER=gateway`, `WHATSAPP_GATEWAY_URL=https://wassap.wehdah.my`, `WHATSAPP_WEBHOOK_URL=https://bakwim.my/api/webhooks/whatsapp`, 2 secret 32-byte, `DIWAN_INSTANCE_ID=spdm-production`.
- Webhook `POST /api/webhooks/whatsapp` → **401 tanpa HMAC** (betul).

### ✅ Emel HANTAR (SMTP) — magic link & notifikasi
- **Brevo** (org "Wehdah Solution", akaun percuma 300/hari). `.env`: `MAIL_MAILER=smtp`, `MAIL_HOST=smtp-relay.brevo.com`, `MAIL_PORT=587`, `MAIL_SCHEME=smtp` (STARTTLS), `MAIL_USERNAME=b269ee001@smtp-brevo.com`, `MAIL_PASSWORD=<SMTP key diwan-spdm>`, `MAIL_FROM_ADDRESS=admin@bakwim.my`.
- **Domain `bakwim.my` AUTHENTICATED di Brevo** — DKIM1/DKIM2/DMARC/brevo-code + branded (send/img.send/r.send) semua diimport ke Cloudflare (DNS-only) & disahkan. Emel DKIM-signed + SPF-aligned → inbox, bukan spam.
- Diuji: `MAIL_SENT_OK`. **Magic link kini berfungsi** (selain login password).

### ✅ Bukti ujian (sesi 18 Julai — petang)
- **Pest suite lokal:** `202 passed, 1 skipped (694 assertions)`, 57s (skip = OCR sebenar; tesseract hanya dalam imej Docker).
- **Prod infra `diwan:staging-check` (di server):** `postgresql redis_cache horizon cos ocr meilisearch smtp gateway = LULUS`; `imap` dilangkau (menunggu App Password). `diwan:health = OK`. Bukti COS tulis/baca/padam + SMTP hantar sebenar via Brevo.
- **Playwright e2e (lokal, server :8092 + seed demo, MAIL log):** `registration` (daftar→lulus superadmin→magic link→panel), `office-workflow` (minit/balas/susulan/kelulusan 4 peranan), `explore` panel superadmin = **LULUS**; `ocr-upload` = skip (tiada fixture OCR lokal); crawl 9-peranan = login `waitForURL` timeout pada peranan yang **berubah antara run** (admin_masjid / nazir / bendahari) walau dengan server berbilang-worker (`PHP_CLI_SERVER_WORKERS=10`) → **artifak ujian/persekitaran** (login 9× pantas/IP kena rate-limit, atau timing dev-server), **BUKAN pepijat app**. Logik semua 9 peranan hijau dalam Pest `RoleAuthorizationMatrixTest`; login peranan berjaya dalam `office-workflow` (4 peranan) & `explore` superadmin.

---

## 3. Yang TERTUNGGAK (perlu tindakan pengguna)

### ✅ A. git push — SELESAI
Semua commit di-push ke `origin/main` (HEAD `5bf9db4`) via GCM device-flow selepas token luput dikosongkan. Server boleh `git pull` untuk selaras (kini server guna fail scp + imej rebuild yang setara).

### ✅ B. Emel HANTAR — SELESAI (Brevo authenticated). Lihat seksyen 2.

### 🟢 C. Emel TERIMA / intake — LIVE PENUH (18 Jul petang) ✅
**Mailbox intake:** **`spdmediwan@gmail.com`** (tukar dari spdmdiwan yang bermasalah; guna yang ada "e").

**SIAP & DISAHKAN hujung-ke-hujung:**
- Cloudflare Email Routing ENABLED; destination **VERIFIED** (Claude klik pautan CF dalam inbox — akaun log masuk); **catch-all `*@bakwim.my` → spdmediwan = ACTIVE**.
- Routing diuji: emel → `scan+cfroute@bakwim.my` (Brevo) → CF → **sampai inbox spdmediwan** ✅.
- 2FA aktif + **App Password** dimasukkan oleh pengguna (via `sudoedit`); `IMAP_ENABLED=true`.
- ✅ **`diwan:staging-check` SEMUA LULUS termasuk `imap LULUS`** — SPDM boleh poll `spdmediwan` via IMAP (imap.gmail.com:993 ssl).
- ⚠️ **Gotcha dibaiki:** `.env` ada **2 baris `IMAP_PASSWORD=`** (satu kosong dari auto-set awal Claude, satu bernilai dari pengguna). `env_file` docker ambil yang **TERAKHIR** (kosong) → container `IMAP_PASSWORD` kosong walau `grep -m1` nampak nilai. Fix: padam baris kosong (`sed '/^IMAP_PASSWORD=$/d'`), kekal yang bernilai → recreate → `config:cache` → imap LULUS.

**BAKI (E2E slug penuh — perlu masjid pilot):**
- Cipta masjid pilot → Tetapan Masjid aktifkan intake emel + allowlist pengirim + keyword → hantar emel berlampiran ke `scan+{slug}@bakwim.my` → Peti Masuk + OCR + carian.
- Reka bentuk: alias `scan+{slug}@bakwim.my` (satu peti mel, plus-addressing). Destination lama `spdmdiwan` (Pending) boleh padam di CF.
- 🔐 **Pengguna:** regenerate App Password (nilai tadi muncul dalam chat/transkrip) selepas sistem stabil.

### 🟢 D. WhatsApp — E2E LENGKAP & LULUS (pilot MAMAD; provisioning + pairing + inbound + outbound + OCR-fix, 18 Jul petang)
**SIAP:** gateway `DIWAN_PROVISIONING_SECRET` kini **padan** `WHATSAPP_PROVISIONING_SECRET` SPDM (fingerprint `b5ee6a00d53e1af0`). Probe SPDM-signed → **HTTP 200** `{"success":true,"data":{"tenantId":"10","status":"active","maxDevices":2}}`. Integrasi provisioning SPDM ↔ gateway **HIDUP**.
- Punca asal 401: nilai **fingerprint 16-aksara tersalin sebagai secret** (bukan 64-hex); dibetulkan di gateway + `config:cache`.
- SPDM `WhatsAppIntegrationService::baseRequest()` sudah `->acceptJson()` → hantar `Accept: application/json` (elak 302 gateway pada ralat validasi). **Tiada perubahan kod SPDM diperlukan.** Pengerasan gateway `shouldRenderJsonWhen` = pilihan sahaja.
- ⚠️ Bersihkan: probe cipta tenant junk `spdm-production:mosque:0` (gateway tenantId 10) — boleh padam di gateway.

**✅ E2E LENGKAP & LULUS — pilot MAMAD (Masjid Al-Mukhlisin Alam Damai, slug=mamad, mosque_id=1):**
- Dicipta di server (login panel perlu kata laluan → Claude tak boleh UI): admin+WA **60176811605**, kerani **60189030363**, pengerusi **60199654974**; 40 nod KF; status aktif.
- WhatsApp provision → gateway tenant 11, linked.
- **Pairing kod telefon** (bukan QR): `beginPairing(phone)` → `linking_code` → pengguna taip di telefon → **connected**, wa_number=60176811605 ✅.
- **Outbound**: `WhatsAppGateway::send` → pengerusi → ok=1 ✅.
- **Inbound SEBENAR** (telefon pengguna): kerani hantar `spdm` → slot (`wa_intake_ready`) → hantar dokumen → **rekod Peti Masuk (channel=whatsapp) + OCR siap + `InboxNewItemNotification`** ke admin/kerani ✅. Aliran penuh terbukti.
- Simulasi: `diwan:simulate-whatsapp <session> <phone> <file>` (webhook HMAC sebenar) untuk uji pipeline tanpa telefon.
- Reka bentuk: 1 nombor/sesi per masjid. Gateway sokong `maxDevices=2`, SPDM kuatkuasa 1.

**🐛 BUG PRODUKSI DIJUMPAI + DIBAIKI (hasil E2E ini — go-live blocker):** dokumen dengan **teks bercetak GAGAL OCR** — `ocrmypdf --skip-text --output-type pdfa` **abort pada Ghostscript 10.0.0** (imej php:8.3 bookworm); imej tanpa teks lulus (kosong) menyembunyikan isu. **Fix (`fe5744a`):** `--output-type pdfa`→`pdf` dalam `ProcessOcrJob::runOcrMyPdf` (elak Ghostscript). **Disahkan di produksi selepas rebuild imej:** JPEG berteks → `ocr=siap, ocr_len=109`, teks betul diekstrak + searchable.pdf dijana ✅. PDF/A boleh dipulih dengan naik taraf Ghostscript >10.02.0; fail asal tak diubah.

**Nota:** rekod ujian simulate (MAMAD id 2–4) = artifak, boleh padam. Junk gateway tenant `spdm-production:mosque:0` (tenantId 10, dari probe awal) boleh padam di gateway.

**Login akaun ahli MAMAD (nota operasi, 18 Jul lewat):** 3 ahli guna email **placeholder** `admin@mamad.local` / `kerani@mamad.local` / `pengerusi@mamad.local` (bukan inbox sebenar → **magic link tak berguna**; guna **login password** sahaja di `/app/login`, BUKAN `/admin`). Admin ada password (ditukar operator); kerani/pengerusi asalnya **tiada** password → set via `/admin` → **Pengguna** → edit → medan **Kata Laluan** (auto-hash; model User cast `password => hashed`). Panel `/app` **tidak** paksa pengesahan email (User bukan `MustVerifyEmail`; AppPanelProvider tiada `emailVerification`) → `email_verified_at` kosong TAK halang login. Untuk pengguna SEBENAR nanti: tukar ke email betul mereka supaya magic link + notifikasi email hidup (notifikasi WhatsApp sudah aktif untuk MAMAD).

---

## 4. Semakan penuh (gate) sebelum buka pengguna sebenar
- [x] git push + Pest 234✓/1 skip + Playwright semua LULUS + Pint
- [x] Emel: magic link sampai inbox (Brevo authenticated); IMAP intake LULUS
- [ ] Intake: WA + emel + upload manual → OCR `siap` → carian jumpa (MAMAD terbukti)
- [ ] **Ujian silang tenant (2 masjid) di server sebenar** — carian/slug/signed URL/alias emel/sesi WA terasing (suite Pest membuktikan; belum diuji pd 2 tenant produksi)
- [ ] `backup:run` → objek di bucket backup (restore drill)
- [ ] Log 30–60 min tiada error berulang

### Tindakan pengguna untuk ciri Fasa D (bila mahu aktif)
- **Telegram**: BotFather → cipta bot → `sudoedit .env` (`TELEGRAM_BOT_TOKEN`, `TELEGRAM_BOT_USERNAME`, `TELEGRAM_WEBHOOK_SECRET`) → recreate → `php artisan diwan:telegram-set-webhook` → superadmin & pengguna tekan **Sambung Telegram** (Profil).
- **WhatsApp platform** (alert superadmin): sediakan nombor WA khas → `/admin` → **WhatsApp Platform** → Aktifkan → Pasangkan (QR/kod) → Segerakkan. Alert sesi-terputus akan hantar via nombor ini.
- Nota: `diwan:check-wa-sessions` sudah dijadualkan (/10 min); alert e-mel+Telegram berfungsi tanpa WA platform.

## 5. Rujukan
- Spec: `DIWAN-SPEC.md`. Checklist go-live: `WHAT-TO-DO-NEXT.md`. Bukti audit: `AUDIT-E2E-2026-07-16.md`.
- Memori sesi: `~/.claude/projects/.../memory/spdm-deploy-bakwim.md`.
