# Pusingan 24 (Pelan) — Codex: Audit integrasi v1.10

## Integriti input (hash 2x + status kerja dua-fakta)

`PELAN-PEMBAIKAN.md` disemak dua kali sebelum audit; kedua-duanya identik: SHA-256 `0B3B4DF9E2549882E413B15853893E6AB634028D4B58B747B3A8FB3DEC76E21E`, 322,133 B, 4,184 LF, mtime `2026-08-02 07:42:44.926`. Hash sepadan nilai beku.

Status kerja dilapor berasingan: kod aplikasi = **0 baris perubahan**; keseluruhan = `M HANDOVER.md` + fail perancangan tidak dijejak (termasuk laporan round-robin). Asas commit kekal `8342d95`.

## Titik 1 — ServeCommand dan probe `variables_order`

**Verdict: LULUS, dengan nota kosmetik kecil.** Vendor sebenar memetakan hanya kunci `$_ENV` bukan `$passthroughVariables` kepada `false` apabila `.env` wujud (`ServeCommand.php:79-94,181-189`); `--no-reload` memintas pemetaan. Symfony Process menggabungkan env pembina dengan `getenv()` dan hanya membuang nilai `false` (`Process.php:327-332,355-363,1688-1695`). Naratif v1.10 tepat dan tidak lagi mendakwa `.env` sahaja sebagai mekanisme. Probe `ini_get('variables_order')` + kiraan `$_ENV` diletakkan sebelum serve dan direkod pada larian pertama; ia mencukupi untuk membuktikan keadaan runner, manakala `--no-reload` kekal pertahanan deterministik.

## Titik 2 — Upload dua-laluan

**Verdict: LULUS.** Dalam satu matrix shard, `success()` dan `failure()` saling eksklusif, jadi nama `guidance-shard-${{ matrix.shard }}` tidak menyebabkan konflik immutable artifact v4/v7; tepat satu upload berlaku. Agregator `download-artifact` dengan `pattern: guidance-shard-*` menemui kedua-dua laluan kerana nama artifact sama. `guidance-coverage-gate-failure` unik. Semua kemunculan aktif `if-no-files-found: error` (shard, JSON, agregator) berada di bawah step `if: success()`; laluan failure menggunakan `ignore`. Padanan sejarah dalam log tidak mengubah YAML aktif.

## Titik 3 — Kiraan D11

**Verdict: LULUS.** Jadual F0(iv-a) mengandungi 16 entri: #1–#12 (12 fail), #13a/b (2), #14–#15 (2), #16a/b/c (3) = **19 fail fizikal**, ditambah #17 sebagai **1 bundle audit**. Imbasan aktif tidak menemui dakwaan “16 fail repo”; semua padanan ialah sejarah/nota versi atau konteks pembatalan. Angka 19+1 konsisten pada §1, §11 dan §12.

## Titik 4 — §11 dan keputusan pemilik

**Verdict: PERLU PEMBETULAN SUBSTANTIF.** Blok status dan nota kebergantungan §11 sepadan `KEPUTUSAN-PEMILIK.md`: D1–D11 dijawab, D10 membuka D2, dan Lampiran A1 kekal menunggu tindakan. Namun jadual F0(iv-a) yang dirujuk semula dalam baris D11 masih menyebut fixture OCR `#16a-c` sebagai “bersyarat — hanya jika ci-ocr hendak dijadikan required”, sedangkan D11 telah “luluskan semua” dan blok kiraan v1.10 menyatakan ia tidak lagi bersyarat. Kontradiksi ini perlu dinormalkan dalam v1.11 (sama ada hapus label bersyarat atau nyatakan dengan jelas keputusan pemilik mengatasi label lama). D5 (pengecualian bertulis semasa F7) dan D10 (addendum langkah pertama F0/F4) dinyatakan konsisten.

## Titik 5 — Imbasan corak dan silang seksyen

**Verdict: LULUS selain isu Titik 4.** `16 fail repo`, `if-no-files-found: error`, `menunggu`, serta corak P21 `tepat tiga`/`bukti/plan-ci`/`trap '` tidak mempunyai kemunculan aktif yang bercanggah; baki ialah sejarah, komen atau nota transisi. Tiada rujukan seksyen rosak yang lain akibat +112 baris ditemui. Kontradiksi OCR bersyarat ialah satu-satunya drift integrasi baharu yang material.

## Titik 6 — Keputusan keseluruhan

**Verdict: PERLU v1.11** kerana kontradiksi D11/OCR di Titik 4 menjejaskan ketepatan skop yang diluluskan pemilik. Keutamaan **P1**: selaraskan label #16a-c dengan keputusan “lulus semua” dan kiraan 19 fail + 1 bundle; selepas itu ulang imbasan silang §11/F0(iv-a). Tiada isu vendor, upload, kiraan fizikal, atau corak aktif lain memerlukan pindaan.

## KEPUTUSAN: (b) PERLU v1.11

Isu substantif tunggal: kontradiksi label bersyarat OCR (#16a-c) berbanding D11 “luluskan semua” dan kiraan 19+1. P25 perlu membetulkan teks tersebut, mengesahkan semula hash/integriti, dan hanya kemudian menilai penutupan muktamad.

## Integriti output

Fail yang ditulis: `PLAN-RR-24-CODEX.md` dan `PLAN-RR-STATUS.md` sahaja; `PELAN-PEMBAIKAN.md` serta kod aplikasi tidak disentuh. Selepas penulisan, rekod SHA-256/saiz/mtime kedua-dua fail hendaklah dicatat dalam `PLAN-RR-STATUS.md`.
