# Pusingan 6 (Pelan) — Codex: Pengesahan v1.2

Tarikh: 2026-08-02 · Asas kod tempatan: commit `8342d95`

## Semakan integrasi 1 (fokus/trap) — verdict + bukti

**Verdict integrasi utama: TEPAT; ujian e2e #3/#4 mencukupi.** Seksyen 3.4 kini konsisten
dengan interaksi halaman sedia ada: popover utama bukan-modal, tiada `aria-modal`, tiada trap,
tetapi mempunyai fokus awal, Tab bebas keluar, dan fokus kembali ke pencetus
(`PELAN-PEMBAIKAN.md:392-406`). Ini menyelesaikan kontradiksi P4; kod semasa memang menetapkan
`disableActiveInteraction: false` (`resources/js/help.js:473`). Fallback `showUnavailableGuide`
sah dipisahkan sebagai modal sebenar dengan `aria-modal`, trap Tab/Shift+Tab, ESC dan fokus
kembali (`PELAN-PEMBAIKAN.md:407-414`; fungsi semasa bermula di `resources/js/help.js:392`).

Ujian #3 mengesahkan semantik/trap fallback serta ketiadaan `aria-modal` pada popover utama
(`PELAN-PEMBAIKAN.md:447-449`). Ujian #4 pula meliputi fokus awal, Tab yang benar-benar boleh
keluar ke halaman, fokus sasaran tindakan, regresi ESC, dan fokus kembali ke launcher
(`PELAN-PEMBAIKAN.md:450-454`). Gabungan ini menguji kedua-dua cabang dan kontrak fokus yang
diminta.

Namun, jadual fail §3.5 masih menyuruh perubahan umum “focus trap + `aria-modal`;
`clearFocusTrap`” tanpa mengehadkannya kepada fallback (`PELAN-PEMBAIKAN.md:416-421`). Ini
bercanggah terus dengan §3.4 dan boleh menyebabkan pelaksana memasang semula trap pada popover
utama. Isu silang ini direkod sebagai P1 dalam imbasan di bawah.

## Semakan integrasi 2 (test-hook) — verdict + bukti

**Verdict: TEPAT, deterministik dan selamat untuk produksi biasa.** Pelan menetapkan
`page.addInitScript` sebelum navigasi, lalu `help.js` hanya mendedahkan
`globalThis.__diwanHelpTest` ketika `window.__DIWAN_E2E__` wujud
(`PELAN-PEMBAIKAN.md:425-435`). Ini menghapuskan kebergantungan pada import langsung yang tidak
serasi: `help.js` ialah entry Vite (`vite.config.js:8`), sedangkan Playwright berjalan sebagai
Node ESM biasa (`package.json:4,8`; `playwright.config.js:1`). Gate dua hala — hook wujud dengan
flag dan `undefined` tanpa flag — secara langsung membuktikan namespace tidak terdedah dalam
boot produksi biasa (`PELAN-PEMBAIKAN.md:431-439`).

Rujukan silang F6 menggunakan hook dan kontrak §3.6 yang sama bagi `resolveStepElement`, tanpa
mencipta mekanisme kedua (`PELAN-PEMBAIKAN.md:914-921`). Kontrak ini memenuhi keperluan P4.

## Semakan integrasi 3 (nginx/manifest) — verdict + bukti

**Verdict integrasi utama: TEPAT dan lengkap pada F0 serta langkah deploy 5.** F0 memakukan
laluan repo penuh bagi manifest dan tools, termasuk data kohort/commit, serta menerangkan bahawa
JSON/tools dikomit tetapi imej dikecualikan (`PELAN-PEMBAIKAN.md:127-135`). Ini sepadan dengan
gitignore sebenar yang hanya mengecualikan format imej
(`Audit Review Round Robin/bukti/.gitignore:1-9`).

Langkah 5 menjalankan kedua-dua `docker compose exec -T nginx nginx -t` dan `nginx -T`,
menerangkan tujuan dump konfigurasi efektif, dan mewajibkan output tersanitasi sebagai bukti
fasa (`PELAN-PEMBAIKAN.md:1071-1076`). Kontrak nginx yang diminta lengkap.

Walau bagaimanapun, rujukan F8 masih menggunakan laluan pendek
`bukti/plan-baseline/manifest.json` (`PELAN-PEMBAIKAN.md:1028-1031`). Ini menghidupkan semula
ambiguiti lokasi yang F0 sengaja hapuskan dan direkod sebagai P2 di bawah.

## Imbasan isu baharu v1.2

1. **P1 — Ringkasan fail F2 bercanggah dengan kontrak fokus muktamad.** §3.4 tegas bahawa trap
   dan `aria-modal` hanya untuk fallback (`PELAN-PEMBAIKAN.md:392-414`), tetapi §3.5 menyenaraikan
   “focus trap + `aria-modal`; `clearFocusTrap`” sebagai perubahan `help.js` tanpa skop fallback
   (`PELAN-PEMBAIKAN.md:416-421`). Ini arahan pelaksanaan, bukan kosmetik. v1.3 perlu menulis
   eksplisit “fallback sahaja” dan menyelaraskan nama cleanup dengan `clearFocusManagement()`
   yang ditetapkan §3.4 (`PELAN-PEMBAIKAN.md:410-411`).

2. **P2 — Rujukan manifest F8 kembali kabur.** F0 menggunakan laluan penuh yang betul
   (`PELAN-PEMBAIKAN.md:127-135`), tetapi disiplin pengukuran F8 kembali kepada laluan relatif
   `bukti/plan-baseline/manifest.json` (`PELAN-PEMBAIKAN.md:1028-1031`). v1.3 perlu menyamakan
   rujukan F8 kepada `Audit Review Round Robin/bukti/plan-baseline/manifest.json` supaya tiada
   folder bukti tersalah cipta di root repo.

Tiada isu substantif baharu lain ditemui dalam tiga integrasi atau rujukan silang §3.6↔§7.3.

## KEPUTUSAN: (b)

**PERLU v1.3** — betulkan dua kontradiksi silang di atas (P1 skop trap/`aria-modal` dalam §3.5;
P2 laluan penuh manifest dalam §9/F8). Integrasi utama v1.2 sendiri tepat, tetapi dua arahan
yang masih bercanggah boleh menghasilkan pelaksanaan atau lokasi bukti yang salah; maka pelan
belum wajar ditanda muktamad.
