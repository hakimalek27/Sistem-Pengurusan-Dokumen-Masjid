# Pusingan 4 (Pelan) — Codex

## Verdict keseluruhan

Pelan v1.1 mengintegrasikan sebahagian besar 25 perkara P2 dengan tepat: 7 daripada 9 kelompok
baseline lengkap, manakala dua kelompok masih separa. Pengubahsuaian Claude tentang endpoint
telemetri, parity empat fail locale, dan ujian tajuk adalah wajar. Namun, reka bentuk perangkap
fokus pada popover utama bercanggah dengan statusnya sebagai dialog bukan-modal yang membenarkan
interaksi halaman; mekanisme ujian fungsi modul dalam `page.evaluate` belum dipakukan; dan gate
deploy masih tidak melaksanakan `nginx -T` yang diminta. **Keputusan: perlu v1.2.**

## (1) Audit integrasi — jadual 9 item senarai semak P2 → status integrasi dalam v1.1

| # | Baseline P2 | Status | Bukti dan penilaian |
|---|---|---|---|
| 1 | Betulkan peta ID | ✅ | RR-02-04/RR-04-02 dimasukkan sebagai ditutup/terbukti sihat dan RR-11-04 disatukan (`PELAN-PEMBAIKAN.md:48-85`); ini sepadan keputusan Claude (`PLAN-RR-03-CLAUDE.md:29-31`). |
| 2 | Kontrak `skipRender` + tiada `$wire.set()` Locked | ✅ | Respons tanpa HTML, telemetri DB, dan update lain dipisahkan (`PELAN-PEMBAIKAN.md:206-210,230-252`); fallback Locked kini kaedah server-validated, bukan `$wire.set()` (`PLAN-RR-03-CLAUDE.md:32-38`). |
| 3 | Semantik modal, fokus, minimize, interaction + timer cancel | ⚠️ | Minimize sudah overlap-aware dan boleh dibatal (`PELAN-PEMBAIKAN.md:354-368`), `aria-modal` hanya fallback, dan fokus kembali dirancang (`:384-400`). Tetapi popover utama disebut **bukan-modal** serta membenarkan interaksi halaman (`:384-386`) sambil tetap memerangkap Tab/Shift+Tab di popover (`:389-393`). Ini menghalang pengguna papan kekunci mencapai sasaran halaman sehingga minimize dan menghidupkan semula konflik yang P2 nyatakan (`PLAN-RR-02-CODEX.md:40,61`). |
| 4 | Fallback `en`, parity stabil, fixture notifikasi | ✅ | Fallback `en` dikekalkan (`PELAN-PEMBAIKAN.md:479-484`); parity dikecilkan kepada empat fail diterbitkan (`:548-558`); fixture eksplisit + completeness guard mengganti refleksi buta (`:507-514`). |
| 5 | Override Action Filament melalui `parent`, create-another | ✅ | Ketiga-tiga override mengambil `parent::get*FormAction()`, termasuk create-another, dan larangan mengganti callback dinyatakan (`PELAN-PEMBAIKAN.md:612-638`); ujian simpan turut dirancang (`:686-690`). |
| 6 | Manifest 25/124, dua metrik, gate per wave | ✅ | Manifest dan denominator dibekukan (`PELAN-PEMBAIKAN.md:122-127,824-835`); gate W1 berasaskan inventori dan sasaran akhir ber-allowlist (`:882-888`). |
| 7 | `recordActionsColumnLabel`; bukan IconColumn | ✅ | Pilihan utama ialah teks pautan eksplisit, IconColumn ditolak (`PELAN-PEMBAIKAN.md:914-927`), dan API terus `recordActionsColumnLabel('Tindakan')` dipilih (`:944-951`). |
| 8 | Migrasi dahulu, recreate nginx, `-t/-T`, aset luar | ⚠️ | Migrasi imej baharu sebelum trafik dan force-recreate app/worker/scheduler/nginx adalah tepat (`PELAN-PEMBAIKAN.md:1033-1044`); aset luaran diperiksa (`:1045-1047`). Namun hanya `nginx -t` disebut (`:1044`), bukan dump efektif `nginx -T` yang baseline P2 wajibkan (`PLAN-RR-02-CODEX.md:25,67`). |
| 9 | Jurang ujian + acceptance/rollback boleh ukur | ✅ | Jurang utama diagih ke F1–F8 dan rollback migrasi dilatih (`PLAN-RR-03-CLAUDE.md:76-87`; `PELAN-PEMBAIKAN.md:1048-1050`). Beberapa smoke manual seperti reduced-motion/screen-reader tidak disenaraikan literal, tetapi acceptance risiko utama sudah boleh diukur; isu fungsi browser di §3 bawah perlu dipakukan, bukan menambah test-runner baharu. |

Secara kelompok, jadual ini mengesan kesemua 25 perkara P2: 11 pembetulan fakta, dua penemuan
tertinggal, kelompok risiko per fasa, empat alternatif reka bentuk, dan jurang ujian. Tiada perkara
P2 lain yang berubah makna selain dua status ⚠️ di atas dan mekanisme ujian modul di §3.

## (2) Penilaian pengubahsuaian Claude — setuju/tidak + sebab

- **Telemetri endpoint — setuju dengan Claude.** Konteks server-side menyelesaikan punca;
  `skipRender()` sudah diposisikan sebagai pelengkap pilihan (`PELAN-PEMBAIKAN.md:206-210`).
  Endpoint/komponen baharu akan meluaskan permukaan tanpa keperluan fungsi yang belum dipenuhi.
- **Regex tajuk — setuju dengan Claude.** Gate “124 tajuk eksplisit, tiada elipsis” ialah ujian
  deterministik untuk kohort, manakala fallback diuji terus dengan input berdiakritik dan
  `preserveWords` (`PELAN-PEMBAIKAN.md:785-794`). Ini lebih kukuh daripada meneka potongan
  perkataan BM/Unicode melalui regex.
- **Parity empat fail terbitan — setuju.** Empat fail ialah subset operasional yang stabil dalam
  kawalan repo, bukan keseluruhan bahasa vendor (`PELAN-PEMBAIKAN.md:470-484,551-554`). Jika
  salah satu fail sengaja diterbitkan, parity penuh dalam fail itu munasabah supaya mesej tidak
  jatuh ke bahasa fallback secara senyap.

## (3) Jurang baharu v1.1 — senarai + bukti + cadangan

1. **P1 — Perangkap fokus bukan-modal bercanggah dengan interaction-enabled.** Pelan menyatakan
   popover bukan-modal kerana halaman mesti boleh diinteraksi, tetapi Tab dikitar hanya dalam
   popover (`PELAN-PEMBAIKAN.md:384-393`). Kod semasa memang menetapkan
   `disableActiveInteraction: false` (rujukan pelan `:370-372`; konfigurasi semasa
   `resources/js/help.js:424-475`). **Cadangan:** popover utama mengurus fokus awal dan fokus
   kembali tanpa trap; trap penuh hanya fallback modal. Jika trap mahu dikekalkan bagi sesuatu
   keadaan, keadaan itu mesti benar-benar modal (`aria-modal=true` + interaksi luar dimatikan).

2. **P1 — Laluan `page.evaluate` untuk fungsi ES module belum deterministik.** `help.js` ialah
   entry Vite ES module (`vite.config.js:5-9`) dan kini tiada export/global test hook
   (`resources/js/help.js:1-6,593-595`). Pelan menawarkan dua alternatif — dedah pada runtime
   dev/e2e *atau* import spec melalui bundler (`PELAN-PEMBAIKAN.md:412-418`) — tetapi Playwright
   repo hanya menjalankan spec Node ESM tanpa bundler khas (`package.json:4-8`;
   `playwright.config.js:1-19`), sementara `help.js` mengimport CSS/Driver.js
   (`resources/js/help.js:1-3`). `resolveStepElement` F6 juga diminta terus melalui
   `page.evaluate` (`PELAN-PEMBAIKAN.md:889-895`) walaupun fungsi itu module-private.
   **Cadangan:** pilih satu kontrak: expose namespace test-only, contohnya
   `globalThis.__diwanHelpTest` apabila penanda E2E eksplisit hadir, berisi
   `stepAdvancePlan` dan `resolveStepElement`; assert hook tidak wujud dalam produksi biasa.
   Jangan bergantung pada import terus spec tanpa menambah transform/bundler yang nyata.

3. **P2 — Verifikasi nginx belum lengkap.** Deploy hanya menyebut `nginx -t`
   (`PELAN-PEMBAIKAN.md:1042-1047`), sedangkan `-T` diperlukan untuk membuktikan konfigurasi
   efektif selepas bind-mount/recreate (`PLAN-RR-02-CODEX.md:25,67`). **Cadangan:** tambah
   `docker compose exec -T nginx nginx -t` dan `docker compose exec -T nginx nginx -T`, simpan
   output tersanitasi sebagai bukti setiap deploy app.

4. **Disahkan boleh laksana — bukan jurang:** service `app` memang wujud dan dibina daripada
   target imej app (`docker-compose.yml:1-6,22-25`), jadi corak
   `docker compose run --rm -T app ...` serasi dengan struktur Compose repo. Docker CLI tiada
   pada mesin semakan ini (output arahan: `docker: The term 'docker' is not recognized`), maka
   arahan tidak dijalankan tempatan; ia mesti dibuktikan pada host deploy. Lokasi
   `bukti/plan-baseline` pula munasabah relatif kepada folder laporan: ignore bukti hanya imej
   (`Audit Review Round Robin/bukti/.gitignore:1-9`), jadi JSON dan tools boleh dikomit. Nyatakan
   laluan repo penuh `Audit Review Round Robin/bukti/plan-baseline/...` untuk mengelak penciptaan
   folder `bukti/` baharu di root.

## (4) Keputusan: senarai keperluan v1.2 mengikut keutamaan

1. **P1:** selesaikan kontradiksi fokus: tiada trap pada popover utama bukan-modal; trap hanya
   pada fallback yang benar-benar modal, dengan ujian keyboard sasaran halaman dan fokus-kembali.
2. **P1:** tetapkan satu mekanisme test hook browser yang eksplisit untuk `stepAdvancePlan` dan
   `resolveStepElement`, termasuk gate bahawa hook tidak terdedah dalam produksi biasa.
3. **P2:** tambah kedua-dua `nginx -t` dan `nginx -T` melalui `compose exec -T`, serta jelaskan
   laluan penuh manifest di bawah `Audit Review Round Robin/bukti/plan-baseline/`.

