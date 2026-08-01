# Pusingan 04 — CODEX — 1 Ogos 2026

## A. Semakan penemuan pusingan terdahulu

Semua semakan aplikasi production dibuat secara baca sahaja. Ujian mutasi dibuat pada salinan
tempatan `127.0.0.1:8080` dalam transaksi yang di-rollback; `Notification`, `Mail` dan `Queue`
di-fake. Tiada kod aplikasi production atau data production diubah.

| ID | Verdict Codex | Bukti pusingan ini |
|---|---|---|
| RR-01-01 | **SAH** | Playwright `channel: chrome` production, context bersih desktop: `/log-masuk` 200, satu-satunya target `help-launcher`, selepas 3.6s popover memaparkan `Tindakan belum tersedia` untuk `Masukkan identiti`. Mobile juga direkodkan pada round sebelumnya. |
| RR-01-02 / RR-02-01 | **SAH** | Kiraan bebas Codex daripada `crawl.json`: 274 halaman 200, 124 tanpa `guideId`, 150 ada, kadar 45.26%; superadmin 11. Production smoke juga menunjukkan `data-help-url=/bantuan?asal=%2Flivewire%2Fupdate` dan guide hilang pada `/log-masuk` selepas render Livewire. Kod punca kekal `HelpLauncher::render()` membaca `request()->path()`/query. |
| RR-01-03 | **SAH** | Production Chrome submit kosong pada `/daftar` tanpa mencipta rekod memaparkan empat mesej Inggeris: `The name/state/code/slug field is required.` |
| RR-01-04 | **SAH** | Production fallback popover sebenar memaparkan `← Previous` dan `1 of 1`; `help.js::showUnavailableGuide()` hanya menetapkan `doneBtnText`. |
| RR-01-05 | **SAH**, lokasi diperincikan | Label hard-coded berada di `UsersTable.php:81`, `MosquesTable.php:50`, `ViewMosque.php:16` dan turut `TetapanPlatform.php:43`; nilai ialah `Edit`, `Edit Tenant` atau `Edit Tetapan`. |
| RR-01-06 / RR-03-04 | **SAH** | Kiraan bebas katalog: 83 guide, 473 langkah, 79 guide sepenuhnya generik, 30 langkah khusus, 205 `page-content` dan 238 `page-primary`. Axe/Chrome tidak menunjukkan ralat runtime, tetapi sorotan generik masih tidak menunjuk kawalan sebenar. |
| RR-01-07 / RR-03-03 | **SAH** | `nextButtonLabel()` menyemak next target tanpa fallback generik; `onNextClick()` menyemak dengan fallback generik. Ini menerangkan label `Buat pada skrin` yang boleh terus maju pada langkah generik. |
| RR-01-08 | **SAH** | Katalog `screen.muat-naik-dokumen` bermula pada `page-content`, tiada arahan pembukaan modal upload dalam `startGuide`; tour boleh meminta pilih fail walaupun modal belum dibuka. |
| RR-01-09 | **SAH** | Langkah dashboard menyebut nama masjid/menu kiri tetapi sasaran masih `page-content`/`page-primary`; percanggahan arahan dan sorotan kekal. |
| RR-01-10 | **SAH** | Katalog masih menggunakan placeholder `Langkah N`; 444/473 langkah tiada pemisah tajuk `;`, lalu title runtime boleh mengulang arahan. |
| RR-01-11 | **SAH** | Production landing `/` menghasilkan href bantuan `/bantuan?asal=%2F%2F` pada desktop dan mobile. |
| RR-02-02 | **TIDAK SAH** | Diulang dengan CDP pada satu dokumen yang sama: 20 kemas kini Livewire HelpCenter. Listener `document` kekal 35→35, `window` 72→72; runtime/launcher kekal satu, `data-help-booted` kekal satu, 0 error. Lihat `bukti/pusingan-04/cdp-livewire-soak.json`. Navigasi penuh sebelum ini tidak boleh digunakan untuk membuat kiraan leak kerana setiap halaman mempunyai listener Filament sendiri. |
| RR-02-03 / RR-03-02 | **TIDAK DAPAT DISAHKAN sebagai fokus leak** | Ujian Playwright Chrome sebenar pada `tenant.dashboard`: 10 tekanan Tab berulang hanya melalui `×`, `Buka panduan penuh` dan butang tindakan di dalam popover; tiada fokus keluar. ESC menutup popover dan overlay, 0 error. `aria-modal` tidak ditetapkan, jadi boleh dijadikan hardening, tetapi dakwaan fokus terlepas tidak berjaya direplikasi dalam flow yang sama. |
| RR-02-04 | **DITUTUP-LULUS** | Ujian tulis tempatan sebenar, bukan sekadar GET: semua cubaan ID/objek asing ditolak; aliran tenant sendiri klasifikasi → minit → kelulusan → pelupusan selesai. Lihat `write-path-results.json`. |
| RR-02-05 / RR-03-01 | **SAH, TINGGI** | Render sendiri `InboxNewItemNotification::toMail()->render()` mengesahkan subjek BM tetapi body mempunyai `Hello`, `Regards` dan `All rights reserved`. Artefak 9/9 notification yang boleh dibina mengesahkan pola sama: `bukti/pusingan-03/notifikasi-bahasa.txt`. |

## B. Skop dan kaedah

### Production Chrome smoke

- `https://bakwim.my`, Playwright `channel: 'chrome'`, context berasingan untuk desktop 1440×900 dan mobile 390×844.
- Enam route awam setiap viewport: `/`, `/log-masuk`, `/daftar`, `/bantuan`, `/app/login`, `/admin/login`.
- Semua 12 respons ialah HTTP 200, overflow mendatar 0, tiada `pageerror` atau console error aplikasi.
- Carian `/bantuan` dengan `daftar masjid` memberi 1 hasil dalam skop Orang Awam pada desktop dan mobile.
- Request Cloudflare `/cdn-cgi/rum` yang `ERR_ABORTED` direkod sebagai analytics request, bukan error JavaScript/aplikasi.
- Screenshot dan JSON: `bukti/pusingan-02/production-smoke.json` serta `desktop-*.png`/`mobile-*.png`.

### Livewire, security dan workflow tulis

- Kiraan bebas 124/274 disimpan dalam `bukti/pusingan-04/rr-01-02-kiraan-bebas.txt`.
- CDP `DOMDebugger.getEventListeners` sebelum/selepas 20 kemas kini Livewire pada dokumen yang sama.
- Fixture tempatan menggunakan tenant seeded `mam` dan `man`; objek sementara bertajuk `AUDIT-RR4`.
- Probe tulis meliputi klasifikasi fail asing, minit penerima asing, pelulus asing, keputusan oleh pengguna asing, kelulusan pelupusan asing dan pelaksanaan asing. Semua ditolak.
- Aliran sah tempatan meliputi klasifikasi, dua penerima minit, permohonan/keputusan kelulusan, batch pelupusan, sijil dan status rekod. Transaksi DB di-rollback dan sijil sementara dipadam.
- Kawalan regresi: 22 test feature berkaitan lulus, 111 assertion: `InboxClassifyTest`, `DataIntegrityTest`, `OfficeUatFlowTest`, `ExportTest`, `RetentionEngineTest`.

### Eksport

- `ExportService` dijana sebenar untuk rekod tenant `mam` dan `man`.
- Kedua-dua ZIP mempunyai `metadata.csv` dan `senarai.pdf`; PDF diekstrak dengan `pdftotext`.
- Kandungan `mam` hanya mempunyai rujukan/tajuk `MAM`; kandungan `man` hanya `MAN`; tiada tajuk tenant asing dikesan.
- Artefak pemeriksaan: `bukti/pusingan-04/export-results.json`.

### Accessibility

- axe-core 4.10.3 disuntik ke salinan tempatan melalui Playwright Chrome.
- Lima page teras, desktop dan mobile: dashboard, Peti Masuk, Rekod, Carian, Bantuan.
- Semua 10 page mempunyai HTTP 200, overflow 0 dan 0 console error.
- Satu violation nyata pada desktop dan mobile: `link-name` serious pada `.fi-ta-cell-duplikat > .fi-ta-col`, iaitu `<a>` kosong ke `/app/mam/peti-masuk/4`.
- Axe turut menanda contrast sebagai `incomplete` pada beberapa node dan `aria-prohibited-attr` sebagai `incomplete` di Bantuan; ini perlu semakan manual, bukan dikira violation muktamad.
- Artefak: `bukti/pusingan-04/axe-results.json` dan 10 screenshot `a11y-*.png`.

## C. Penemuan baharu

### RR-04-01 · SEDERHANA / A11Y · Pautan kosong dalam kolum Duplikat Peti Masuk

**Lokasi:** `/app/mam/peti-masuk`, desktop dan mobile; `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:66-69`.

**Langkah ulang:** buka Peti Masuk pada data seeded, jalankan axe-core, atau navigasi dengan pembaca skrin pada kolum `Duplikat`.

**Jangkaan:** sel yang tiada amaran duplikat tidak menghasilkan pautan tanpa nama; jika sel itu pautan rekod, ia mempunyai nama yang boleh didengar.

**Sebenar:** axe menemui:

```html
<a href="http://127.0.0.1:8080/app/mam/peti-masuk/4" class="fi-ta-col">
    <div class="fi-ta-text"></div>
</a>
```

`TextColumn::make('duplikat')->state(... ? '⚠' : '')` menghasilkan state kosong, tetapi Filament masih membungkus sel sebagai link rekod. Impak ialah pengguna pembaca skrin mendengar pautan tanpa tujuan/nama; klik keyboard boleh membawa ke rekod tanpa konteks. Pembaikan perlu sama ada sembunyikan kolum bila state kosong, beri label yang konsisten, atau gunakan kolum status yang bukan link.

## D. Cadangan penambahbaikan tanpa melaksana

1. Simpan origin, guide ID dan langkah sebagai state `HelpLauncher` yang kekal merentas request Livewire; ini menutup kehilangan panduan 124/274 termasuk 11 superadmin.
2. Tambah `lang/ms/{validation,auth,passwords,pagination}.php` dan terjemahkan template notifikasi Laravel/vendor supaya e-mel tidak bermula `Hello` dan berakhir `Regards`.
3. Tambah `<main data-help-target="page-content">` pada layout tetamu atau ubah target `public.login` kepada form sebenar.
4. Jadikan predicate label dan `onNextClick` sama; label tindakan mesti sepadan dengan tindakan yang benar-benar diperlukan.
5. Tambah target khusus pada guide utama; kekalkan overlay generik hanya sebagai senarai semak, bukan sorotan kawasan besar.
6. Mulakan tour upload pada `inbox-upload`, buka/target modal apabila langkah memerlukan pemilih fail, dan elakkan langkah terakhir `target=null` yang menunggu tanpa mekanisme selesai.
7. Pertimbangkan `aria-modal="true"` dan dokumentasikan/ujikan focus trap walaupun fokus trap terbina dalam berjaya pada flow Chrome yang diuji.
8. Betulkan kolum `Duplikat` supaya state kosong tidak menghasilkan link tanpa nama.
9. Gantikan label `Edit`/`Edit Tenant`/`Edit Tetapan` dengan Bahasa Melayu.

## E. Liputan dan had

**Dibuktikan:** production public desktop/mobile, help search, login tour fallback, validation, root help URL, independent Livewire count, CDP same-document soak, access/write tenant isolation, workflow classification/minit/approval/disposal fixture, export CSV/PDF, axe 5 page teras × 2 viewport, regression feature suite.

**Masih belum dibuktikan penuh:** muat naik fail sebenar production/ClamAV/OCR/search; intake WhatsApp/e-mel dengan gateway sebenar; modal superadmin mutation; PDF/CSV melalui klik browser production; load/volumetric test; audit penuh semua route role secara production authenticated. Pusingan 1 mengaudit authenticated matrix pada salinan commit sama, kerana fixture production tidak boleh dicipta tanpa menyentuh data production.

Chrome MCP khusus Codex tidak tersedia dalam sesi ini (`agent.browsers.get('extension')` memulangkan `Browser is not available: extension`). Fallback yang digunakan ialah Playwright dengan `channel: 'chrome'`, bukan Chromium bundled, dan semua keputusan itu ditanda jelas.

Production masih mempunyai tiket ujian Claude `SUP-260801-HXQ0DIOL` yang perlu ditutup/dipadàm oleh pemilik melalui panel superadmin. Saya tidak menyentuhnya kerana protokol melarang mutasi production.

## F. Status

**SIAP PUSINGAN 4.** Penemuan baharu RR-04-01 direkodkan. Isu fokus leak RR-03-02 tidak berjaya direplikasi dan listener leak RR-02-02 ditolak berdasarkan CDP same-document. Giliran diserahkan kepada **Claude — Pusingan 5** untuk semakan terakhir terhadap RR-04-01, semua verdict, dan keputusan sama ada liputan sudah cukup untuk `FINAL-RUMUSAN.md`.
