# Pusingan 23 (Pelan) — Claude: Keputusan P22 + integrasi ke v1.10

**Tarikh:** 2026-08-02 · **Asas kod:** `8342d95`
**Input:** `PLAN-RR-22-CODEX.md` (`099D990C…805020D`, 13,264 B — log Codex disahkan:
34 arahan `succeeded in`) ke atas `PELAN-PEMBAIKAN.md` v1.9
**Output:** `PELAN-PEMBAIKAN.md` **v1.10** — ⏳ **BUKAN MUKTAMAD** (P22 menemui pindaan
substantif; syarat #6 belum dipenuhi)
**Giliran seterusnya:** **Codex Pusingan 24** (audit integrasi v1.10)

## 1. Integriti fail (§0.7 #1 + peraturan #7)

| Fail | Bila | SHA-256 | Saiz | LF | mtime |
|---|---|---|---:|---:|---|
| `PELAN-PEMBAIKAN.md` | pra-tulis #1 (sebelum percubaan P22) | `487EDBE6…B05E96` | 311,998 | 4,072 | 06:15:57 |
| `PELAN-PEMBAIKAN.md` | pra-tulis #2 | `487EDBE6…B05E96` | 311,998 | 4,072 | 06:15:57 |
| `PELAN-PEMBAIKAN.md` | pra-tulis #3 (sejurus sebelum edit P23) | `487EDBE6…B05E96` | 311,998 | 4,072 | 06:15:57 |
| `PELAN-PEMBAIKAN.md` | **selepas tulis (v1.10)** | **`0B3B4DF9E2549882E413B15853893E6AB634028D4B58B747B3A8FB3DEC76E21E`** | 322,133 | 4,184 | 07:42:44 |

Delta: **+10,135 B · +112 baris.** Kiraan baris = bilangan bait `0x0A`. Tiada penulis lain
dikesan (hash identik pada ketiga-tiga semakan pra-tulis, merentas percubaan P22 yang gagal
kuota dan percubaan #2 yang berjaya).

Status kerja dua-fakta (§0.7 #7): (a) `git status` kod aplikasi (app/resources/tests/e2e/config/
.github/docker/composer/package) = **0 baris**; (b) status keseluruhan = `M HANDOVER.md` + fail
perancangan `??` (kini termasuk `PLAN-RR-23-CLAUDE.md` ini) — working tree keseluruhan tidak
bersih. `git log -1` = `8342d95`.

## 2. Konteks pusingan: kuota + akaun baharu (jujur)

Percubaan P22 #1 gagal — kuota Codex habis (*"try again at Aug 9th"*; `succeeded in`=0, tiada
fail ditulis, **tiada giliran dipalsukan**). Pemilik menukar akaun Codex; ujian ringan lulus;
percubaan #2 berjaya penuh (34 arahan, laporan lengkap). Kedua-dua percubaan direkod dalam
`PLAN-RR-STATUS.md`.

## 3. Keputusan P22 satu per satu

**Kiraan: 5 isu DITERIMA (3 P1 + 2 P2) · 4 titik DISAHKAN-tiada-pindaan · 0 TOLAK.**
Setiap satu diverifikasi bebas sebelum diterima — bukan diambil atas dasar kepercayaan:

| ID | Isu P22 | Keputusan | Verifikasi bebas Claude | Integrasi v1.10 |
|---|---|---|---|---|
| P22-T1 | Naratif `ServeCommand`/`$_ENV` terlalu mutlak | **TERIMA (P1)** | Probe sendiri: `variables_order=GPCS` · `array_key_exists('PATH', $_ENV)=false` · `getenv('PATH')!==false=true` — **sepadan probe Codex**. Vendor dibaca sendiri: `ServeCommand.php:79-94` (passthrough), `:181-189` (pemetaan HANYA atas ahli `$_ENV`), Symfony `Process.php:1688-1692` (`getDefaultEnv` bermula dari `getenv()`), `:355-363` (hanya nilai `false` dibuang) | §1 F0(iv)(d-1) #1 ditulis semula (mekanisme penuh + dua syarat luaran + `--no-reload` kekal dengan sebab betul + step probe bukti); rujukan `:1520`; komen §9.2 |
| P22-T2 | Gate Meilisearch | **SAHKAN — tiada pindaan** | Mesej sebenar `SyncHelpIndex.php:87` dibaca sendiri — `grep -qF` memadan corak `{count} guide disegerakkan ke indeks {uid}.` hanya bila count=83 dan uid betul; `stats()` assert `:83-86` mendahuluinya | — |
| P22-T3 | `stats.skipped===0` | **SAHKAN — tiada pindaan** | Hujah reporter `suite.allTests()` konsisten dengan tingkah laku Playwright yang diketahui; bukti Codex `runner/index.js:3916-3929` munasabah dan tidak bercanggah dengan apa-apa dalam pelan | — |
| P22-T4 | Nama check matriks + `gh api` | **SAHKAN — tiada pindaan** | Konsisten dengan `ci.yml:160` yang dibaca P21/P20; nota pagination disimpan | — |
| P22-T5 | Upload `error` menutup diagnosis kegagalan awal | **TERIMA (P1)** | Logik dinilai: `if: always()` + `error` memang menjadikan step terakhir merah dengan "No files were found" bila Playwright gagal sebelum menulis JSON — kegagalan asal tenggelam dalam ringkasan. Reka bentuk dua-step dibekukan: `success()`+`error` (gate P20-04 kekal: lulus-tapi-bukti-hilang = gagal) vs `failure()`+`ignore` (+`serve-ci.log`/`help-index-ci.log` dinaikkan untuk diagnosis) | Upload lapis 1, **kedua-dua** upload shard, upload agregator — semuanya dipisah dua-step |
| P22-T5b | Dakwaan eksklusiviti `storage/app` salah | **TERIMA (P2)** | Grep sendiri: `config/filesystems.php:54` (`manual-capture`), `config/backup.php:185` (`backup-temp`), `ProcessOcrJob.php:68,118` + `ExportService.php:21` (`tmp/*`) — **semuanya wujud** | §1 F0(iv)(g): dakwaan dikecilkan kepada keunikan awalan `plan-*` (0 padanan); pilihan lokasi kekal |
| P22-T6 | D11 "16 fail" salah unit; selepas "lulus semua" ≥19 fail | **TERIMA (P1)** | Jadual F0(iv-a) dibaca sendiri: #13 memang 2 fail; #16 memang wildcard; kiraan fizikal: #1-#12=12 + #13a/b=2 + #14/#15=2 + #16a/b/c=3 = **19 fail repo** + #17 = 1 bundle | Jadual dipecah #13a/b + #16a/b/c (nama exact; `terms.json` = fail dikomit, bukan env ad-hoc — deterministik); blok KIRAAN DINORMALISASI; §11 D11 row + §12 F0 row dikemas. **Skop kelulusan pemilik TIDAK berubah — hanya label unit** |
| P22-T7 | Ayat "menunggu jawapan" lapuk | **TERIMA (P2)** | Grep sendiri pra-P23 telah menemui subset ini (`:3925`, `:3991`); senarai penuh Codex (`:20`, `:578`, `:3917`, `:3934-3941`, `:3952`) disahkan | Header prasyarat + F0(i) + tajuk §11 + blok status baharu + nota kebergantungan (kini rekod "diselesaikan") — semuanya merujuk `KEPUTUSAN-PEMILIK.md`. **Lampiran A1 dikekalkan menunggu** (tindakan pemilik, bukan keputusan D) |

Nota tafsiran P22-T6: Codex menyebut "sekurang-kurangnya tiga fail lagi" untuk #16 kerana
"istilah" boleh jadi ≥1 fail — v1.10 memuktamadkannya sebagai **satu** fail `terms.json`
(dikomit, dibaca CI untuk mengisi `SPDM_OCR_TERM_1/2`), menjadikan kiraan tepat 19, bukan
"≥19". Ini keputusan reka bentuk kecil dalam semangat cadangan Codex (deterministik & dikomit).

## 4. Perubahan v1.10 (ringkasan lokasi)

1. Header: versi 1.10 + log versi entri v1.10 + prasyarat "D1–D11 LENGKAP diterima".
2. §0.5f baharu: peta keputusan P22 (jadual di atas, bentuk ringkas).
3. §1 F0(i): jawapan pemilik direkod (D10 → addendum langkah pertama F0/F4; D5 → pengecualian
   bertulis semasa F7; D11 → gate dibuka).
4. §1 F0(iv)(d-1) #1: naratif `ServeCommand` ditulis semula + step probe `variables_order`.
5. §1 F0(iv)(d-1)/(d): semua upload dipisah dua-step (lapis 1, shard ×2, agregator).
6. §1 F0(iv)(g): dakwaan storage dikecilkan kepada awalan `plan-*`.
7. §1 F0(iv-a): jadual dipecah #13a/b + #16a/b/c + blok KIRAAN DINORMALISASI (19 fail + 1 bundle);
   naratif selepas jadual dikemas (16 = entri, bukan fail).
8. §11: tajuk + blok status "SEMUA DIJAWAB" + nota kebergantungan → rekod diselesaikan;
   D11 row dilabel diluluskan + kiraan normalisasi.
9. §12: F0 row → 19 fail + D11 ✅.

## 5. Imbasan konsistensi berprogram (selepas edit)

`D11 mesti dijawab` = **0** · `menunggu jawapan pemilik` = 2 (kedua-duanya rujukan meta yang
menerangkan pembetulan — log versi + peta §0.5f) · `Terpulang pemilik` = 1 (lajur cadangan
sejarah D5, dilindungi blok nota §11) · `16 fail repo` = 6 kemunculan, semuanya log
versi/jadual peta sejarah (nota sejarah unit di F0(iv-a) menerangkannya) · corak P21
(`tepat tiga`/`bukti/plan-ci`/`trap '`) kekal 0 aktif.

## 6. Untuk Codex Pusingan 24 — fokus yang dicadangkan

1. **§1 F0(iv)(d-1) #1 (naratif baharu)** — sahkan setiap dakwaan mekanisme terhadap vendor
   sebenar sekali lagi; adakah step probe `variables_order` diletakkan dengan betul dan cukup?
2. **Dua-step upload** — sahkan tiada konflik nama artifak (nama sama `guidance-shard-N` pada
   dua step `success()`/`failure()` — hanya satu berjalan setiap larian; adakah `download-artifact`
   pattern masih menemui kedua-dua bentuk nama, termasuk `guidance-coverage-gate-failure`?);
   adakah `actions/upload-artifact@v7` menerima dua step nama sama dalam satu job tanpa isu?
3. **Kiraan 19 + 1** — kira semula sendiri daripada jadual F0(iv-a) v1.10; sahkan tiada
   kemunculan aktif "16 fail" yang tinggal di luar konteks sejarah.
4. **§11** — adakah blok status + nota kebergantungan konsisten dengan `KEPUTUSAN-PEMILIK.md`
   sebenar (baca fail itu)?
5. Imbasan corak: `16 fail repo` (aktif vs sejarah), `if-no-files-found: error` (setiap
   kemunculan mesti berada di bawah `if: success()`), `menunggu`.
6. Jika semuanya tepat dan tiada isu substantif baharu → isytihar
   **"TIADA PENAMBAHBAIKAN SUBSTANTIF — pelan sedia muktamad"**; jika tidak → PERLU v1.11.

---

*Fail disunting giliran ini: `PELAN-PEMBAIKAN.md`, `PLAN-RR-23-CLAUDE.md`, `PLAN-RR-STATUS.md`
— tiada yang lain. Tiada kod aplikasi disentuh; tiada git/SSH/deploy/mutasi.*
