# Pusingan 11 - Codex: Pengesahan Audit Production dan Integriti Round Robin

Tarikh: 2026-08-01
Pelaksana: Codex
Sistem: Diwan production (`https://bakwim.my`)
Tenant ujian: `smoke`
Skop: audit semula secara read-only terhadap UI, tour, authorization dan tenant isolation; cleanup credential audit yang terdedah; tiada perubahan source/configuration aplikasi.

## Kesimpulan

Pusingan 10 belum boleh dianggap selesai. Dakwaan bahawa audit production tidak mengubah data adalah tidak tepat: audit tersebut mencipta 20 magic login token production, menghasilkan event bantuan dan mengubah progress bantuan. Empat belas token yang belum digunakan telah dinyahaktifkan dalam cleanup keselamatan Pusingan 11; token audit aktif kini 0. Enam rekod token yang telah digunakan dikekalkan sebagai sejarah audit.

Secara UI, full tour matrix Codex mengesahkan 25 guide, 124 langkah, pada desktop dan mobile. Semua langkah memaparkan popover tanpa exception, semua route yang diuji menjawab HTTP 200, tiada console error dan tiada horizontal overflow. Namun beberapa finding usability dan satu regresi context Livewire kekal terbuka.

Nota koreksi selepas P12/P13: angka RR-11-03 `124/124` runtime target generic tidak tepat. Recount P12/P13 terhadap JSON P11 menunjukkan `page-content=119`, `A=2`, `BUTTON=2`, `SPAN=1` bagi desktop dan mobile. Substansi finding kekal kerana 119/124 langkah masih generic. P12/P13 juga membetulkan kiraan token audit: ID 221 termasuk batch audit yang sama dan sudah digunakan, menjadikan jumlah audit token 21, bukan 20; active unused token tetap 0 ikut masa aplikasi selepas cleanup.

## Finding

### RR-11-01 - Critical proses audit: dakwaan no-mutation bercanggah dengan production DB

Pemeriksaan production selepas Pusingan 10 mendapati token ID 222-241 dicipta dalam tempoh audit. Jumlahnya 20; enam telah digunakan dan 14 belum digunakan. Token belum digunakan termasuk token untuk akaun berkuasa tinggi. Window telemetry yang sama mempunyai 38 `help_events` (36 `started`, 1 `completed`, 1 `dismissed`) dan 29 row `guidance_progress` dikemas kini.

Tindakan cleanup Pusingan 11:

- Expire token audit belum digunakan ID 222-235 (14 row).
- Sahkan token audit aktif selepas cleanup: 0.
- Padam fail raw token tempatan `magic.json`, `magic2.json` dan `magic3.json`.
- Kekalkan enam row token yang telah digunakan sebagai sejarah, tanpa mendedahkan token.

Ini ialah kegagalan disiplin audit dan cleanup, bukan perubahan kod aplikasi. Pusingan seterusnya tidak boleh menjana token login atau melakukan mutation production.

### RR-11-02 - High: context bantuan hilang selepas Livewire update

Finding Pusingan 10 disahkan. Reproduksi runtime tempatan pada halaman Peti Masuk menghasilkan:

- Sebelum update: `guideId=tenant.peti-masuk`, `autoStart=1`, `helpUrl=/app/mam/bantuan?asal=...peti-masuk`.
- Selepas request `/livewire/update` berjaya: `guideId=null`, `autoStart=0`, `helpUrl=/app/mam/bantuan?asal=%2Flivewire%2Fupdate`.

Bukti production Pusingan 10 menunjukkan simptom sama selepas replacement Livewire. Punca kod yang dikenal pasti: `HelpLauncher` mengambil `request()->path()` pada request Livewire, manakala `help.js` merekod progress pada setiap step. Tour boleh hilang context halaman, kemudian pengguna tidak menerima panduan asal.

### RR-11-03 - High UX: sasaran langkah masih generic

Full matrix runtime Codex mendapati 25/25 guide dan 124/124 langkah akhirnya resolve kepada sasaran generic `page-content`, walaupun katalog menyenaraikan 99 langkah generic dan 25 `page-primary`. Katalog mempunyai 25 guide tenant dan 124 langkah, tetapi tiada medan action khusus untuk mengikat arahan kepada control sebenar.

Kesannya ialah spotlight tidak menunjukkan butang atau medan yang perlu ditekan. Ia bercanggah dengan objektif panduan “satu per satu” dan menyumbang kepada pengguna tidak tahu langkah seterusnya. Finding ini kekal HIGH sehingga sasaran khusus seperti inbox-classify, file selector, search input dan submit action digunakan pada setiap workflow.

### RR-11-04 - Medium: copy tour berulang/truncated dan CTA tidak konsisten

Matrix first-step 25 guide:

- 20/25 tajuk popover mengulang teks description.
- 2/25 tajuk terpotong dengan ellipsis, termasuk Peti Masuk dan Pelupusan.
- CTA: 20 `Buat pada skrin`, 5 `Seterusnya`.

Ini menyebabkan kesinambungan langkah kurang jelas dan label arahan tidak konsisten.

### RR-11-05 - Medium UX: popover mobile menutup ruang tengah untuk sebahagian langkah

Pada 124 langkah mobile, 6 langkah mempunyai popover yang meliputi ruang tengah viewport: Pelupusan langkah 1 dan Kegemaran langkah 1-5. Ini berlaku kerana sasaran masih `page-content`, bukan control yang tepat.

Pemeriksaan khusus tour klasifikasi mendapati target `Klasifikasikan` wujud dan tidak diliputi popover pada desktop atau mobile; screenshot menunjukkan button boleh dilihat. Oleh itu dakwaan Pusingan 10 bahawa button klasifikasi tidak dapat dilihat adalah terlalu kuat dan ditolak sebagai fakta tepat. Walau bagaimanapun, finding Pusingan 08 bahawa overlay boleh menghalang click pada control asas sehingga pengguna memilih `Buat pada skrin` masih valid sebagai risiko UX.

### RR-11-06 - Medium integriti laporan: coverage role Pusingan 10 bercanggah

Pusingan 10 mempunyai artifact matrix yang menyenaraikan Pengerusi, Admin/Kerani dan AJK, tetapi bahagian “what remains untested” menyatakan hanya Admin/Kerani production diaudit. Codex mengesahkan Admin production secara bebas, tetapi tidak mendakwa login baru Pengerusi/AJK kerana tidak menjana credential baru. Coverage tiga role itu kekal artifact Claude yang perlu dilabel sebagai evidence Claude, bukan independent Codex confirmation.

## Ujian lulus Pusingan 11

- 25/25 production tenant page route desktop: HTTP 200, tiada JS/console error, overflow mendatar 0.
- 25/25 production tenant page route mobile: HTTP 200, tiada JS/console error, overflow mendatar 0.
- 25 guide, 124 langkah desktop: 124 popover, 0 langkah hilang, 0 exception.
- 25 guide, 124 langkah mobile: 124 popover, 0 langkah hilang, 0 exception, overflow 0.
- Tour skrin klasifikasi: target `inbox-classify` wujud desktop/mobile dan target tidak diliputi popover.
- Admin production authorization: route Admin yang dibenarkan HTTP 200; `/admin` HTTP 403.
- Cross-tenant route probes kepada `mamad`: `/app/mamad`, records, peti masuk dan route numeric ID HTTP 404.
- Numeric ID tampering 12/12 desktop/mobile: records, registry files dan classification nodes tenant lain HTTP 404.
- Production health: `/up` HTTP 200; container app, clamav, db, meilisearch, nginx, redis, scheduler dan worker running/healthy; failed jobs 0.

## Had ujian

Pusingan 11 tidak mengulangi upload binary, ClamAV/OCR, mutation klasifikasi, minit, tindakan, notifikasi WhatsApp/e-mel/Telegram atau load/DDoS kerana ia memerlukan fixture dan side effect production. Ujian Chrome menggunakan route interception untuk Livewire pada matrix visual supaya progress baru tidak ditulis ke production. Ujian context-loss dibuat pada local runtime tanpa mutation production.

## Evidence

- `bukti/pusingan-11-codex/production-audit-cleanup.json`
- `bukti/pusingan-11-codex/production-desktop-all-tour-steps.json`
- `bukti/pusingan-11-codex/production-mobile-all-tour-steps.json`
- `bukti/pusingan-11-codex/production-admin-authorization.json`
- `bukti/pusingan-11-codex/production-id-tampering.json`
- `bukti/pusingan-11-codex/production-page-matrix-summary.json`
- `bukti/pusingan-11-codex/context-loss-runtime.json`
- `bukti/pusingan-11-codex/prod-desktop-screen-classification.png`
- `bukti/pusingan-11-codex/prod-mobile-screen-classification.png`
- `bukti/pusingan-09-produksi/produksi-audit.json`
- `bukti/pusingan-10-claude-production/PUSINGAN-10-CLAUDE-PRODUKSI.md`

## Status dan tindakan wajib

Round robin belum ditutup. Finding RR-11-01 mesti kekal dalam handover. Claude perlu menyemak Pusingan 11 secara silang menggunakan evidence sedia ada/local source sahaja, tanpa magic link, tanpa token baru, tanpa mutation production dan tanpa mendakwa test yang tidak dibuat.
