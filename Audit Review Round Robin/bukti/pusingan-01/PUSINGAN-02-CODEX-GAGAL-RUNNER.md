# PUSINGAN 02 — CODEX

## Seksyen A — Skop dan kaedah

Audit ini meliputi pengesahan RR-01-01 hingga RR-01-11, semakan kod laluan mutasi, aksesibiliti, lifecycle `resources/js/help.js`, kebocoran bahasa, dan ketepatan katalog. Bukti primer sepatutnya datang daripada salinan tempatan (commit 4e07a70), fail bukti pusingan-01, serta ujian UI/route dan semakan kod. Pada pusingan ini runner Windows gagal memulakan PowerShell (`CreateProcessAsUserW: 1312`), maka tiada pemeriksaan fail, HTTP, SQLite atau kod dapat dilaksanakan secara bebas. Tiada kod aplikasi diubah.

## Seksyen B — Pengesahan RR-01

| ID | Verdict | Bukti/ulasan Codex |
|---|---|---|
| RR-01-01 | TIDAK DAPAT DISAHKAN | Bukti pusingan-01 tidak dapat dibaca dalam runner ini; minta URL/artefak mentah dan langkah reproduksi. |
| RR-01-02 | TIDAK DAPAT DISAHKAN | Dakwaan 124/274 dan `request()->path()` memerlukan crawl/render semula. Mekanisme pemulihan (MutationObserver/polling/route context) juga belum dapat disemak. Jangan anggap kiraan disahkan. |
| RR-01-03 | TIDAK DAPAT DISAHKAN | Tiada ujian bebas dapat dijalankan. |
| RR-01-04 | TIDAK DAPAT DISAHKAN | Tiada bukti primer boleh dicapai. |
| RR-01-05 | TIDAK DAPAT DISAHKAN | Tiada bukti primer boleh dicapai. |
| RR-01-06 | TIDAK DAPAT DISAHKAN | Tiada bukti primer boleh dicapai. |
| RR-01-07 | TIDAK DAPAT DISAHKAN | Tiada bukti primer boleh dicapai. |
| RR-01-08 | TIDAK DAPAT DISAHKAN | Tiada bukti primer boleh dicapai. |
| RR-01-09 | TIDAK DAPAT DISAHKAN | Tiada bukti primer boleh dicapai. |
| RR-01-10 | TIDAK DAPAT DISAHKAN | Tiada bukti primer boleh dicapai. |
| RR-01-11 | TIDAK DAPAT DISAHKAN | Tiada bukti primer boleh dicapai. |

Khusus tour: dakwaan “sync tour berfungsi (1045ms)” daripada satu tour tidak mencukupi. Pusingan berikutnya mesti menguji sekurang-kurangnya satu tour setiap keluarga (screen, workflow, role/tenant) selepas navigasi Livewire dan hard reload, dengan bukti console/network dan masa sync.

## Seksyen C — Audit tambahan (status dan ujian diperlukan)

1. Laluan mutasi klasifikasi, minit, kelulusan, pelupusan, billing dan retensi: semak Policy + FormRequest + service + job/controller; pastikan setiap query terikat `mosque_id`/BelongsToMosque, authorization diuji untuk mam/man, dan ID rentas tenant memberi 404/403 tanpa side effect.
2. Aksesibiliti: semua tour mesti boleh dibuka/ditutup/bergerak dengan papan kekunci, fokus dikunci dan dipulihkan, `aria-describedby`/role dialog tepat, ESC berfungsi, serta tiada fokus di belakang overlay. Jalankan axe/Lighthouse dan ujian pembaca skrin.
3. `resources/js/help.js`: semak listener berganda selepas Livewire replacement, MutationObserver disconnect/cleanup, timer/polling clear, dan race antara route update serta render. Ambil heap/listener snapshot selepas 20 navigasi.
4. Bahasa: grep literal English pada mailables, notifications, PDF/CSV exporter dan template; render sampel setiap locale dan semak tajuk, status, validation, pagination serta nama kolum.
5. Katalog bantuan: setiap arahan perlu dipadankan dengan selector/route/label UI sebenar untuk semua role dan tenant; tandakan arahan yang stale atau merujuk butang yang tiada.

## Seksyen D — Risiko utama

R1 (tinggi): konteks bantuan hilang selepas Livewire boleh menjadikan panduan salah halaman. R2 (tinggi): mutasi tenant yang tidak terikat policy/service boleh bocor silang masjid. R3 (sederhana): listener/timer bocor menjejaskan sesi panjang. R4 (sederhana): English fallback pada dokumen rasmi.

## Seksyen E — Keputusan dan permintaan bukti

Tiada penemuan RR-01 boleh diberi verdict SAH/TIDAK SAH tanpa akses bukti primer dalam pusingan ini; semua ditanda TIDAK DAPAT DISAHKAN untuk mengelakkan pengesahan palsu. Sila ulang audit apabila runner pulih dan lampirkan hash/command/output bagi setiap verdict.

## Seksyen F — Permintaan kepada Claude (Pusingan 3)

- Ulang kira RR-01-02 dengan senarai URL 274 halaman, bukti 124 halaman, serta nyatakan sebarang mekanisme pemulihan yang dijumpai.
- Uji sekurang-kurangnya 6 tour bersasar merentas screen/workflow/role/tenant; sertakan trace console/network dan keputusan selepas Livewire.
- Lampirkan matriks route mutasi → Policy → service → tenant scope → ujian cross-tenant.
- Sertakan keputusan axe/keyboard/screen-reader, heap/listener soak, grep English, dan semakan katalog-vs-UI dengan selector/route.

