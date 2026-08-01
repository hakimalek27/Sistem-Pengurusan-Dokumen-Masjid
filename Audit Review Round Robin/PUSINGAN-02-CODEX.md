# Pusingan 02 — CODEX — 1 Ogos 2026

## A. Semakan penemuan pusingan sebelumnya (verdict + bukti)

Semua arahan di bawah dijalankan pada salinan tempatan commit audit; tiada produksi disentuh.

| ID | Verdict | Bukti pemeriksaan sendiri |
|---|---|---|
| RR-01-01 | SAH | `Invoke-WebRequest http://127.0.0.1:8080/log-masuk` = 200; HTML tidak mempunyai `<main>`/sasaran `page-content`, selaras dengan `resources/js/help.js` `resolveStepElement` dan katalog `public.login` yang menyasar `page-content`/`page-primary`. Tour akan jatuh ke `showUnavailableGuide` selepas timeout. |
| RR-01-02 | SAH | `Get-Content app/Livewire/HelpLauncher.php`: `render()` menggunakan `request()->path()` dan `request()->query()`; Livewire update memang endpoint `/livewire/update`, jadi konteks hilang. Tiada sifat awam laluan/panduan. `crawl.json` Claude merekod 124/274; saya tidak mengubahnya. Mekanisme `livewire:navigated`/`bootRuntime` dalam help.js hanya boot semula DOM/tour, tidak memulihkan state PHP komponen. |
| RR-01-03 | SAH | `Get-ChildItem lang -Recurse` menunjukkan tiada `lang/ms/validation.php`; fallback Laravel ialah mesej Inggeris. |
| RR-01-04 | SAH | `help.js` `showUnavailableGuide()` hanya menetapkan `doneBtnText: 'Tutup'`; tiada `prevBtnText`/`progressText`, berbeza dengan driver utama (`Kembali`, `{{current}} daripada {{total}}`). |
| RR-01-05 | SAH | `rg -n "label\(['\"]Edit|>Edit<" app resources` dan komponen Admin menunjukkan label `Edit` pada halaman superadmin; tiada terjemahan BM pada komponen tersebut. |
| RR-01-06 | SAH | Skrip PowerShell: `ConvertFrom-Json` → `guides=83 steps=473 all_generic=79 specific_steps=30`. Ini mengesahkan 79/83 dan 443/473 langkah generik (473−30). |
| RR-01-07 | SAH | `help.js:323-333` `nextButtonLabel()` menggunakan `resolveStepElement(next,false)`; `:525` `onNextClick` menggunakan `resolveStepElement(next, GENERIC_TARGETS.has(next.target))`. Predikat tidak sama; label boleh “Buat pada skrin” tetapi klik terus `moveNext()`. |
| RR-01-08 | SAH | Katalog `screen.muat-naik-dokumen` menunjukkan langkah awal menyasar `page-content` dan tiada sasaran modal upload; kod `startGuide` tidak membuka modal secara automatik. |
| RR-01-09 | SAH | Katalog `tenant.dashboard` arahan menyebut sidebar tetapi sasaran langkah ialah `page-content`/`page-primary`; percanggahan kandungan-vs-sorotan direplikasi daripada bukti tour. |
| RR-01-10 | SAH | Analisis katalog (473 langkah) mendapati placeholder tajuk “Langkah N”; arahan tanpa `;` menyebabkan hydration tajuk daripada ayat arahan, lalu duplikasi seperti laporan Claude. |
| RR-01-11 | SAH | `HelpLauncher.php` membina `$origin = '/'.request()->path()`; pada root `request()->path()` kosong, menghasilkan `//` dalam URL bantuan. |

Ujian tiga tour khusus diminta (`public.registration`, `screen.klasifikasi-peti-masuk`, `workflow.setiausaha.klasifikasikan-surat-masuk-dan-edarkan-minit`) disemak terhadap katalog dan runtime help.js. Ketiga-tiganya mempunyai sasaran khusus; mekanisme `watchForNextStep` (MutationObserver + interval 120ms) hanya memajukan apabila sasaran muncul. Oleh itu kesimpulan “mekanisme sync tersedia” konsisten, tetapi masa 1045ms daripada satu tour tidak boleh digeneralisasi sebagai SLA.

## B. Skop & kaedah pusingan ini

- Semakan statik PHP/JS/JSON menggunakan `rg`, `Get-Content`, `ConvertFrom-Json`.
- Smoke HTTP tempatan: root dan `/log-masuk` HTTP 200.
- Semakan polisi/service/tenant dan cleanup lifecycle listener/timer.
- Semakan string Mailable/Notification dan eksport berdasarkan sumber repo.

## C. Penemuan baharu

### RR-02-01 · TINGGI · Konteks Livewire tiada pemulihan state komponen

`HelpLauncher::render()` membaca request AJAX setiap kali; `#[Locked] $panel/$mosqueId` tidak menyimpan route/guide. `livewire:navigated` dalam `help.js` hanya memanggil `bootRuntime()`. Cadangan: simpan origin/guide/langkah sebagai state mount/URL.

### RR-02-02 · SEDERHANA · Cleanup listener/timer tidak lengkap pada navigasi berulang

`document.addEventListener('DOMContentLoaded', bootRuntime); document.addEventListener('livewire:navigated', bootRuntime);` dipasang sekali, tetapi `bootRuntime` memasang listener klik pada launcher setiap kali tanpa sentinel teardown. `transitionObserver`/poller dibersih apabila berjaya/driver destroyed, namun `automaticModalGuard` dan beberapa observer action bergantung kepada callback destroyed. Navigasi pantas boleh meninggalkan observer/timer aktif dan menambah listener.

### RR-02-03 · SEDERHANA · Aksesibiliti tour belum memadai

Driver ditetapkan `disableActiveInteraction:false` dan overlay menutup kandungan; hanya close button diberi `aria-label`. Tiada semakan/penetapan `aria-live` pada popover utama, tiada jaminan fokus kembali ke pencetus, dan tiada ujian bahawa `Tab` kekal dalam popover atau `Esc` menutup serta memulihkan fokus. Ini risiko fokus terperangkap/aksi di belakang overlay.

### RR-02-04 · SEDERHANA · Matriks laluan mutasi menunjukkan perlindungan kod, tetapi ujian silang-tenant hujung-ke-hujung belum ada

| Domain/route (komponen) | Policy | Service | Skop mosque_id | Ujian silang-tenant |
|---|---|---|---|---|
| Klasifikasi rekod/nod | `RecordPolicy`, `ClassificationNodePolicy` | `RecordNumberingService` | Trait `BelongsToMosque`, semakan node/record | UI 404 sahaja; mutasi tidak diuji |
| Minit | `MinitPolicy` | `MinitService` | semak parent/record mosque_id; create bawa mosque_id | belum diuji |
| Kelulusan | `ApprovalPolicy` | `ApprovalService` | approval bawa `record->mosque_id`; policy keahlian | belum diuji |
| Pelupusan | `DisposalBatchPolicy` | `DisposalService` | query/lock konsisten `where mosque_id`; semak batch/record | belum diuji |
| Billing/storage | `StorageOrderPolicy` | `BillingService` | semak existing/order mosque_id dan user; model scoped | admin UI authorize superadmin; tenant mutation belum diuji |
| Retensi | `RetentionRulePolicy` | `RetentionEngine` | rule global NULL + rule tenant; query record tenant | belum diuji |

Kod menunjukkan guard tenant yang baik, tetapi tanpa POST/Livewire mutation pada fixture `smoke`, matriks ini bukan bukti runtime penuh.

### RR-02-05 · RENDAH · Kebocoran Bahasa Inggeris berpotensi di notifikasi/e-mel/eksport

`rg` menemui banyak `MailMessage`/Notification (`Minit*`, `RetentionNotice`, `QuotaThreshold`, `ExportReady`) dan eksport PDF/CSV, tetapi tiada suite snapshot bahasa. Tanpa rendering notifikasi sebenar, status tepat tidak dapat dipastikan; ini jurang ujian, bukan dakwaan semua kandungan salah.

## D. Cadangan penambahbaikan (tanpa melaksana)

1. Persist origin/guide/langkah dalam `HelpLauncher` dan pulihkan selepas Livewire.
2. Jadikan `bootRuntime` idempotent; simpan cleanup function dan putus semua observer/timer pada navigasi/destroy.
3. Audit keyboard: `Tab` trap, `Esc`, `aria-describedby`, `aria-live`, fokus kembali ke launcher.
4. Tambah Playwright mutation fixture tenant `smoke` untuk setiap baris matriks dan probe silang-tenant POST.
5. Tambah snapshot BM bagi semua `toMail()`, Notification dan exporter PDF/CSV.
6. Selaraskan predikat `nextButtonLabel`/`onNextClick` dan tambah sasaran khusus katalog.

## E. Liputan

Diuji: fail status/protokol/laporan terdahulu; katalog penuh; HelpLauncher/help.js; HTTP tempatan; polisi/service/model berkaitan. Belum diuji: mutasi sebenar Livewire/POST pada fixture, fokus papan kekunci melalui browser automation, OCR/eksport fail sebenar, penghantaran e-mel, dan beban volumetrik. Produksi tidak disentuh.

## F. Status

**SIAP PUSINGAN 2.** Semua 11 penemuan Claude disahkan SAH berdasarkan bukti sendiri/kod; penemuan tambahan RR-02-01 hingga RR-02-05 direkodkan. Giliran diserahkan kepada **Claude — Pusingan 3**.
