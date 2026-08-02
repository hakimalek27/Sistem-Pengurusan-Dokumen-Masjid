# Pusingan 17 — Claude: rekod keputusan P14 + P16 dan integrasi ke pelan v1.7

**Tarikh:** 2026-08-02
**Asas kod:** `8342d95` (working tree kod aplikasi TIDAK disentuh)
**Pelan yang diaudit masuk:** v1.6 **separa**, SHA-256
`A1667A703FB90C57459626DAD46553F360E93097066D8A39B335B89CD44FF31E`
**Pelan yang diserahkan keluar:** **v1.7** — ⏳ **BUKAN MUKTAMAD**
**Giliran seterusnya:** **Codex Pusingan 18** (audit integrasi v1.7)

---

## 0. Ringkasan jujur keadaan (tiada penceritaan semula)

Tiga perkara mesti dinyatakan terus, kerana kesemuanya menjejaskan kebolehpercayaan rekod
round-robin ini:

1. **Pusingan 15 ialah SERAHAN SEPARA.** Claude P15 menyunting badan `PELAN-PEMBAIKAN.md`
   kepada v1.6 (hash `A1667A70…FF31E`, 205 840 B, 2 777 baris) dan mengintegrasikan majoriti
   P14-01…P14-08, tetapi **keluar sebelum** mewujudkan `PLAN-RR-15-CLAUDE.md`, sebelum mengemas
   footer (kekal *"Versi 1.5 … Codex Pusingan 14"*), sebelum menaikkan prasyarat D1–D10 → D1–D11,
   dan sebelum membuang percanggahan Lampiran B #11. Percubaan menyambung gagal dengan mesej
   literal **"You've hit your monthly spend limit"**.
2. **`PLAN-RR-15-CLAUDE.md` TIDAK WUJUD dan tidak dicipta secara retroaktif.** Menulis fail
   bertarikh mundur bagi pihak giliran yang tidak pernah menghasilkan output = memalsukan rekod.
   Rekod keputusan P14 dibawa oleh **fail ini (§3)**, dan rujukan hantu dalam badan pelan
   (§F0(ii-a) v1.6 yang menunjuk `PLAN-RR-15-CLAUDE.md` §3) telah dibuang dalam v1.7.
3. **Percubaan P17 yang pertama juga terputus.** Sesi P17 sebelum ini **sempat menyunting
   `PELAN-PEMBAIKAN.md` menjadi v1.7 sepenuhnya** (termasuk §0.5c, §0.7 peraturan 6, F0(iv),
   F0(iv-a), §7.3/§7.4, §9.1a, §11 D11, Lampiran B #11/#12 dan footer) tetapi **timeout sebelum
   menghasilkan `PLAN-RR-17-CLAUDE.md` dan mengemas `PLAN-RR-STATUS.md`**. Fail ini menutup
   jurang itu. Ia **tidak** mendakwa kerja baharu ke atas badan pelan; ia merekodkan keputusan
   yang sudah tersuntik di sana, ditambah pengesahan bebas yang boleh diulang.

Kesan proses: **dua giliran berturut-turut menghasilkan suntingan pelan tanpa fail keputusan.**
Itulah sebab peraturan baharu §0.7 #6 (giliran terputus direkod sebagai terputus) dimasukkan ke
dalam pelan, dan sebab status muktamad **tidak** ditanda dalam pusingan ini.

---

## 1. Integriti fail (peraturan `PLAN-RR-STATUS.md` #7 + pelan §0.7 #1)

| Fail | Bila | SHA-256 | Saiz (B) | Baris | mtime (tempatan) |
|---|---|---|---:|---:|---|
| `PELAN-PEMBAIKAN.md` | diwarisi daripada P15 (diaudit P16) | `A1667A70…D44FF31E` | 205 840 | 2 777 | — (direkod P16) |
| `PELAN-PEMBAIKAN.md` | **semakan #1 P17 (sebelum baca-untuk-putus)** | `DD58889A…705BBDDBE` | 248 846 | 3 270 | 2026-08-02 05:12:54 |
| `PELAN-PEMBAIKAN.md` | **semakan #2 P17 (sebelum sebarang tulisan)** | `DD58889A…705BBDDBE` | 248 846 | 3 270 | 2026-08-02 05:12:54 |
| `PELAN-PEMBAIKAN.md` | **diserah kepada P18** | `DD58889A…705BBDDBE` | 248 846 | 3 270 | 2026-08-02 05:12:54 |

**Kesimpulan integriti:** hash sepadan pada kedua-dua semakan → tiada proses lain menulis fail
pelan semasa giliran ini. Giliran ini **tidak mengubah `PELAN-PEMBAIKAN.md`** (tiada typo atau
percanggahan yang memerlukan suntingan ditemui — lihat §6), jadi hash keluar = hash masuk.
Hash penuh v1.7:

```
DD58889AEB2D58CA4A2C3E2A9EA040D9C555F7B64FDBF246EBE8699705BBDDBE
```

**Had giliran yang diisytihar:** P17 dihadkan kepada tiga fail (`PELAN-PEMBAIKAN.md`,
`PLAN-RR-17-CLAUDE.md`, `PLAN-RR-STATUS.md`) dan **dilarang** menjalankan git, SSH, deploy atau
sebarang mutasi. Maka P17 **tidak boleh** mencipta snapshot immutable (§0.7 #2a/#2b).
**Tindakan pemilik masih diperlukan:** komit folder `Audit Review Round Robin/` sebelum Codex P18
bermula, atau benarkan P18 mencipta snapshot sebagai langkah pertamanya. Selagi itu tidak dibuat,
§0.7 #4 terpakai kepada semua dakwaan sejarah tentang v1.6 dan v1.7.

---

## 2. Kaedah semakan P17

- Baca penuh: `PLAN-RR-STATUS.md`, `PLAN-RR-14-CODEX.md`, `PLAN-RR-16-CODEX.md`,
  `PELAN-PEMBAIKAN.md` v1.7, `KEPUTUSAN-PEMILIK.md`.
- Pengesahan angka **dikira semula terus daripada `resources/help/guides.json`** pada `8342d95`
  (baca sahaja, tiada mutasi) — bukan dipetik daripada dokumen pusingan terdahulu.
- Tiada ujian mutasi dijalankan; tiada kod aplikasi disunting; tiada git.

---

## 3. Keputusan P14-01…P14-08 (rekod yang tidak pernah ditulis oleh P15)

**Kiraan: 8 TERIMA · 0 TERIMA SEBAHAGIAN · 0 TOLAK.** Lima daripadanya diterima **dan
dikuatkan** dengan bukti tambahan yang P14 sendiri tidak namakan. Peta penuh dalam pelan
**§0.5b**; ringkasan + lokasi integrasi:

| ID | Isu Codex P14 | Keputusan Claude | Bukti penentu | Lokasi integrasi dalam pelan |
|---|---|---|---|---|
| **P14-01** | CI tidak boleh log masuk: `SESSION_DRIVER=array` + `APP_URL` port salah | **TERIMA + DIKUATKAN** | `ci.yml:56` (8080) vs `playwright.config.js:11` (8092); `ArraySessionHandler.php:17` simpan sesi dalam `$storage` milik instance → PHP share-nothing membuang sesi setiap permintaan. **Tambahan:** kehijauan Pest hari ini **bukan** bukti sesi HTTP berfungsi (ujian berkongsi instance dalam proses yang sama) | §1 F0(iv) langkah 1–12; §10 langkah 1 |
| **P14-02** | Gate 473/229/83 perlu shard + agregator, bukan satu job | **TERIMA** | `ci.yml:21` `timeout-minutes: 30` untuk job `integration` yang sudah membawa apt-get OCR + composer + npm + build + migrasi + Pest penuh | §1 F0(iv) lapis 1/2/3; §7.3; §7.4; §10 |
| **P14-03** | Manifest bantuan ≠ manifest akses halaman mengikut role | **TERIMA + DIKUATKAN** | Drift pada **8/8 role**, bukan Admin sahaja: `AKSES-PAGE-…-2026-07-21.md:12-19` = 21/15/13/13/12/12/12/13 vs `e2e/guidance.spec.js:14-21` = 25/17/15/15/13/13/13/14 | §1 F0(ii) set ketiga `role_routes` + F0(ii-b); §9.1 jurang 6/7; §9A.3; §11 D11 |
| **P14-04** | Runner produksi + kitaran hayat fixture belum boleh dijalankan literal | **TERIMA + DIKUATKAN** | **Bahaya yang P14 tidak namakan:** slug `smoke` **bukan** fixture buang — `SmokeE2E.php:33,50` menjadikannya tenant milik gate deploy `diwan:smoke` 9/9, dan `production-readonly.spec.js:28` berlalai kepadanya. Arahan v1.5 "bersihkan tenant fixture `smoke`" akan **memusnahkan gate deploy sendiri** | **§9.1a (baharu)**; §9.1 #4/#5; §10 langkah 6; Lampiran B #12 |
| **P14-05** | W1/W2 masih anggaran; 6 defect mobile ditangguh terlalu lama | **TERIMA + DIKUATKAN** | Partition deterministik menggantikan `~10`/`~6`: **W1 = 28 guide (bukan ~10)**, W3 = **1**. Enam langkah mobile exact daripada `bukti/pusingan-11-codex/production-mobile-all-tour-steps.json` (`centerCovered=true`): `tenant.pelupusan#1` + `tenant.kegemaran#1–5` | §1 F0(ii-a) jadual wave beku; §7.2 (W0–W6); §7.4; §9; §12 dikalibrasi semula |
| **P14-06** | Status `blocked` bercanggah gate kitaran penuh | **TERIMA** | G1 (§7.3) menerima `blocked` sebagai status sah manakala G4 menuntut 83/83 guide melalui kitaran penuh — tanpa peraturan bagi langkah `blocked`; §7.4 pula membenarkan penutupan "dengan baki bersebab" | §7.3 (jadual status + G4 fallback); §7.4; §9 (metrik baharu); §9.3 |
| **P14-07** | Arahan `grep` gate manual tidak sah seperti ditulis | **TERIMA + separa DIKUATKAN** | `grep` BRE memang tiada lookahead negatif → gate §9A.3 **vakum**. **Tetapi cadangan P14 (`! rg …`) juga tidak selamat** — `rg` pulang **exit 2** bila fail/glob tiada. *(Regex PHP §4.7 #6 `/\bSeterus\b(?!nya)/` **sah** — PCRE menyokong lookahead; hanya laluan shell yang rosak.)* ⚠️ Pembetulan P15 itu sendiri **tidak mencukupi** → ditulis semula sekali lagi di bawah P16-06 | §9A.3 dan §3.7 — **kedua-duanya digantikan v1.7** |
| **P14-08** | Bekukan snapshot round-robin + kemas status giliran | **TERIMA** | Fail pelan **untracked** (`git status` = `??`) → tiada versi immutable; dakwaan sejarah P12↔P13 tidak boleh disahkan semula hari ini | **§0.7 (baharu)** + `PLAN-RR-STATUS.md` |

---

## 4. Keputusan P16-01…P16-08 (audit keadaan separa)

**Kiraan: 7 TERIMA · 1 TERIMA SEBAHAGIAN (P16-08) · 0 TOLAK.** Empat diterima **dan dikuatkan**
dengan bukti yang melampaui hujah asal Codex; satu premis Codex **ditolak dengan bukti** dalam
P16-08 (lihat lajur bukti). Peta penuh dalam pelan **§0.5c**.

| ID | Isu Codex P16 | Keputusan Claude | Bukti penentu | Lokasi integrasi dalam pelan |
|---|---|---|---|---|
| **P16-01** | Canary login masih pseudokod `curl`, bukan command boleh-jalan | **TERIMA + DIKUATKAN** | Bukti melebihi hujah CSRF: log masuk ialah **komponen Livewire**, bukan borang POST — `vendor/filament/filament/src/Auth/Pages/Login.php:459` `->livewireSubmitHandler('authenticate')`, `:387-389` `Action::make('authenticate')->submit('authenticate')`; medan bernama **`data.login`** bukan `email` (`app/Filament/Auth/Login.php:21`; disahkan oleh pemilih e2e `guidance.spec.js:45` `input[id="form.login"]`). Maka POST `email`/`password` ke `/app/login` **tidak boleh** berjaya walau token CSRF diekstrak betul | **§1 F0(iv) canary ditulis semula** (spec `e2e/ci-session-canary.spec.js`, tag `@session-canary`, command literal); **§1 F0(iv-a)**; §11 D11 |
| **P16-02** | Shard/agregator tiada pelaksanaan literal | **TERIMA + DIKUATKAN** | **Penemuan baharu P17:** `step.id` katalog **tidak unik global** — 473 langkah → hanya **470** `step.id` unik (`dashboard.1/2/3` wujud dalam `tenant.dashboard` **dan** `admin.dashboard`). Agregator berasaskan `step.id` akan melapor 470/473 (gagal palsu) atau menganggap 3 langkah sudah diliputi guide lain. Kunci set dibekukan sebagai **`<guide_id>#<index1>`** | **§1 F0(iv) lapis 2/3 ditulis semula** (project/spec/command/skema/YAML/agregator); §7.3 G1/G5; §7.4 |
| **P16-03** | Skop D11 mengira artifak terlalu sedikit | **TERIMA** | Dikira: v1.6 sendiri **sudah** mensyaratkan spec produksi §9.1a, wrapper, agregator, validator, canary dan command fixture — tetapi D11 menyenaraikan empat sahaja | **§11 D11 ditulis semula (4 → 14 fail)**; **§1 F0(iv-a) jadual fail** |
| **P16-04** | Setup/cleanup produksi tidak dinamakan + dua kontrak `run_uuid` bercanggah | **TERIMA** | Percanggahan dalaman v1.6 disahkan: jadual §9.1a mewajibkan `-RunUuid <uuid>` **diberi pemanggil**, manakala peraturan wrapper #2 berkata *"`run_uuid` dijana sekali di awal"* — tidak boleh kedua-duanya benar tanpa peraturan keutamaan | **§9.1a ditulis semula** (`diwan:audit-fixture`, argumen, auth, inventory, pengendalian rahsia, `try/finally`, `-CleanupOnly`, kontrak `RunUuid` disatukan); §11 D11 |
| **P16-05** | Senarai ID wave exact belum wujud | **TERIMA + DIKUATKAN** | `PLAN-RR-15-CLAUDE.md` **tidak wujud** → §F0(ii-a) merujuk fail hantu. **P17 menjana semula partition terus daripada `resources/help/guides.json`** dan ia sepadan jadual beku **tanpa perbezaan** (§5) | **§1 F0(ii-a)** (manifest + validator jadi sumber kebenaran); rumusan mudah-baca = **§5 fail ini** |
| **P16-06** | Gate `! rg` menukar ralat kepada lulus | **TERIMA** | Disahkan dengan **menjalankan `rg` 15.2.0 pada mesin ini**: tiada padanan → **rc 1**; laluan tiada → **rc 2**; regex rosak (`rg '('`) → **rc 2**; `! rg … folder/tiada` → cawangan "lulus" diambil. Guard `test -d` v1.6 menutup **satu** punca exit 2 sahaja | **§3.7** dan **§9A.3** kedua-duanya ditulis semula (assert senarai fail > 0 **dan** bezakan rc 1 daripada rc ≥ 2) |
| **P16-07** | `role_routes` perlu expected daripada polisi, bukan belajar hasil rosak | **TERIMA + DIKUATKAN** | v1.6 memang menulis *"nilai direkod daripada tingkah laku sebenar, kemudian dikunci"* = baseline-as-contract. **Penguatan P17:** cadangan P16 sahaja masih boleh **tautologi** jika `expected` dijana dengan memanggil `canAccess()` yang sama yang diprobe | **§1 F0(ii-b) gate ditulis semula** — tiga lapis: `expected_access` (daripada `config/roles.php:55-124` + policy/spec) ↔ `declared_access` (penilaian authorizer) ↔ `actual_status` (probe HTTP); §9.1 jurang 6/7 |
| **P16-08** | Suite domain penting di luar gate CI | **TERIMA SEBAHAGIAN** | `office-workflow` + `ddms-extended` **diterima penuh**. `ocr-upload` diterima **bersyarat**: `e2e/ocr-upload.spec.js:4-6` `test.skip` melainkan `SPDM_OCR_FIXTURE_1/2` + `SPDM_OCR_TERM_1/2` diberi → memasukkannya tanpa fixture komited = **skip senyap = gate palsu**. **Premis "antivirus fixture" DITOLAK dengan bukti:** `config/diwan.php:32` `CLAMAV_ENABLED` lalai **false** dan `AntivirusScanner.php:12` pulang awal bila mati; queue sudah `sync` (`ci.yml:66`) dan tesseract sudah dipasang (`ci.yml:107`) | **§1 F0(iv) project `ci-domain`/`ci-ocr`**; §9 jadual metrik; §10 langkah 1; Lampiran B #12 |

### 4.1 Sisa konsistensi P16 §4 — status

| # | Permintaan P16 §4 | Status dalam v1.7 |
|---|---|---|
| 1 | Header + F0 `D1–D10` → `D1–D11` | ✔ Header baris 16 dan F0(i) kini `D1–D11`; satu-satunya kemunculan `D1–D10` yang tinggal ialah **rekod sejarah** kegagalan P15 dalam §0.7 (sengaja) |
| 2 | `F6 (6 gelombang)` → **7 wave W0–W6** | ✔ §1 urutan fasa: `F6 Sasaran spesifik data-help-target (7 wave W0–W6, 83 guide)`; dua kemunculan "enam gelombang" yang tinggal ialah naratif sejarah v1.4 (log versi + §7.2) |
| 3 | F2 "job CI e2e" → nama gate sebenar | ✔ §3.7 kini menamakan `@session-canary` + `--project=ci-guidance` + `--project=ci-domain` + required `guidance-e2e-gate` |
| 4 | Lampiran B #11 → larang **perkongsian**, bukan job berasingan | ✔ Ditulis semula: `guidance-e2e` **DIWAJIBKAN** wujud dengan `services:`/`env:` sendiri; duplikasi ±60 baris diterima sebagai harga gate deterministik |
| 5 | Footer → v1.7 / giliran Codex seterusnya | ✔ Footer: *"Versi 1.7 — BUKAN muktamad. Giliran seterusnya: Codex Pusingan 18"* |
| 6 | Wujudkan fail rekod yang jujur menggabungkan P14+P16 | ✔ **Fail ini** (§3 + §4). `PLAN-RR-15-CLAUDE.md` sengaja **tidak** dicipta (§0.2) |
| 7 | Kemas `PLAN-RR-STATUS.md` + catat kegagalan spend limit | ✔ Status dikemas dalam giliran ini; kegagalan P15 (spend limit) dan timeout P17-cubaan-1 direkod tanpa disamarkan |

---

## 5. Lampiran ID wave — rumusan mudah-baca (sumber kebenaran tetap manifest)

Dijana semula oleh P17 terus daripada `resources/help/guides.json` (`catalog_version`
`2026.07.22.2`) pada `8342d95`, menggunakan **peraturan deterministik** §1 F0(ii-a):

> `W0` = dua guide yang mengandungi enam langkah popover mobile yang terbukti rosak ·
> `W1` = guide `screen` dengan ≥1 langkah tindakan (`wait_for_user`) bersasar **generik**
> (`page-primary`/`page-content`) · `W2` = guide `workflow` syarat sama · `W3` = baki `screen` ·
> `W4` = baki `workflow` · `W5` = `tenant` + `admin` (tolak dua guide W0) · `W6` = `public`.

⚠️ **Jika senarai ini dan `manifest.json` berbeza, MANIFEST yang betul** dan
`scripts/audit/validate-plan-manifest.mjs` yang memutuskan (§1 F0(ii-a) #4). Senarai di bawah
ialah alat semakan manusia, bukan sumber.

Format: `guide_id (bilangan langkah)`.

### W0 — hotfix mobile, selepas F2 (2 guide · 10 langkah)
`tenant.kegemaran (5)` · `tenant.pelupusan (5)`
*(enam langkah defect: `tenant.pelupusan#1`, `tenant.kegemaran#1–5`)*

### W1 — `screen` bertindakan (28 guide · 140 langkah · 140 langkah tindakan generik)
`screen.balas-dan-edarkan-minit (6)` · `screen.beri-akses-khas-fail-sulit (4)` ·
`screen.buat-keputusan-kelulusan (6)` · `screen.buka-fail-baharu (5)` ·
`screen.butiran-fail-elektronik-fizikal-atau-hibrid (5)` · `screen.butiran-log-aktiviti (5)` ·
`screen.butiran-rekod-dan-tindakan-mengikut-kebenaran (6)` · `screen.cipta-delegasi (6)` ·
`screen.cipta-nod-klasifikasi (6)` · `screen.cipta-peraturan-retensi (5)` ·
`screen.edarkan-minit (5)` · `screen.edit-tetapan-masjid (5)` · `screen.ganti-versi-rekod (4)` ·
`screen.hasil-carian-lanjutan (5)` · `screen.jemput-ahli (5)` ·
`screen.keluarkan-fail-fizikal (5)` · `screen.mohon-kelulusan (5)` ·
`screen.mohon-pembetulan-rekod (5)` · `screen.muat-naik-dokumen (5)` ·
`screen.permohonan-storan-tambahan (4)` · `screen.persediaan-berpandu (5)` ·
`screen.pindah-lokasi-fizikal (4)` · `screen.pindah-rekod-ke-fail-lain (5)` ·
`screen.sedia-senarai-pelupusan (5)` · `screen.tanda-tindakan-minit-selesai (5)` ·
`screen.tetapan-notifikasi (4)` · `screen.tetapkan-kata-laluan (4)` ·
`screen.viewer-dokumen (6)`

### W2 — `workflow` bertindakan (13 guide · 145 langkah · 60 langkah tindakan generik)
`workflow.admin_masjid.betulkan-rekod-salah-tawan-tanpa-memadam-sejarah (13)` ·
`workflow.admin_masjid.muat-naik-semak-dan-klasifikasikan-dokumen-serta-hantar-minit (20)` ·
`workflow.admin_masjid.sediakan-dan-laksanakan-pelupusan-terkawal (13)` ·
`workflow.admin_masjid.urus-fail-fizikal-atau-hibrid-dan-jejak-penjagaan (13)` ·
`workflow.ajk.baca-rekod-dan-selesaikan-tugasan-minit (8)` ·
`workflow.audit.laksanakan-semakan-audit-baca-sahaja (11)` ·
`workflow.bendahari.mohon-storan-tambahan (9)` ·
`workflow.bendahari.urus-rekod-kewangan-dan-minit (10)` ·
`workflow.ketua_imam.laksanakan-arahan-minit (8)` ·
`workflow.nazir.proses-minit-dan-keputusan-kelulusan (9)` ·
`workflow.pengerusi.buat-keputusan-kelulusan-atau-pelupusan (9)` ·
`workflow.pengerusi.terima-baca-balas-dan-selesaikan-minit (12)` ·
`workflow.setiausaha.mohon-kelulusan-dan-pembetulan-rekod (10)`

### W3 — baki `screen` (1 guide · 11 langkah)
`screen.klasifikasi-peti-masuk (11)`

### W4 — baki `workflow` (1 guide · 13 langkah)
`workflow.setiausaha.klasifikasikan-surat-masuk-dan-edarkan-minit (13)`

### W5 — `tenant` + `admin` tolak W0 (35 guide · 146 langkah)
`admin.analitik-bantuan (2)` · `admin.bantuan (2)` · `admin.dashboard (3)` ·
`admin.help-announcements (2)` · `admin.mosques (3)` · `admin.profil-saya (3)` ·
`admin.status-sambungan (3)` · `admin.storage-orders (3)` · `admin.tetapan-platform (3)` ·
`admin.tiket-sokongan (2)` · `admin.users (3)` · `admin.whatsapp-platform (3)` ·
`tenant.ahli-peranan (6)` · `tenant.analitik-bantuan (2)` · `tenant.bantuan (2)` ·
`tenant.carian (7)` · `tenant.classification-nodes (5)` · `tenant.dashboard (4)` ·
`tenant.delegasi (6)` · `tenant.kelulusan (6)` · `tenant.laporan (4)` ·
`tenant.log-aktiviti (5)` · `tenant.minit-saya (6)` · `tenant.pembetulan-rekod (5)` ·
`tenant.penggunaan (5)` · `tenant.persediaan (6)` · `tenant.peti-masuk (6)` ·
`tenant.profil (6)` · `tenant.records (5)` · `tenant.registry-files (6)` ·
`tenant.retensi (5)` · `tenant.retensi-peraturan (5)` · `tenant.sensitive-access-logs (4)` ·
`tenant.tetapan-masjid (6)` · `tenant.tiket-sokongan (2)`

### W6 — `public` (3 guide · 8 langkah)
`public.help (2)` · `public.login (2)` · `public.registration (4)`

---

## 6. Pengesahan bebas P17 (dikira semula, bukan dipetik)

Semua angka di bawah dikira terus daripada `resources/help/guides.json` pada `8342d95` dalam
giliran ini. Ini kali **keempat** denominator teras disahkan secara bebas (P2 → P11 → P13 → P17).

| Kuantiti | Nilai dalam pelan | Nilai dikira P17 | Padan? |
|---|---:|---:|:--:|
| Guide katalog | 83 | **83** | ✔ |
| Langkah katalog | 473 | **473** | ✔ |
| Langkah tindakan (`wait_for_user`) | 229 | **229** | ✔ |
| Langkah bersasar generik (`page-primary`+`page-content`) | 443 | **443** (238 + 205) | ✔ |
| Langkah bersasar **spesifik** sedia ada | 30 | **30** (`classification-*`, `inbox-*`, `registration-*`) | ✔ |
| Langkah tindakan **bersasar generik** | 200 | **200** | ✔ |
| Placeholder tajuk `Langkah N` | 258 | **258** | ✔ |
| `catalog_version` | `2026.07.22.2` | **`2026.07.22.2`** | ✔ |
| Partition wave (guide) | 2/28/13/1/1/35/3 | **2/28/13/1/1/35/3** | ✔ |
| Partition wave (langkah) | 10/140/145/11/13/146/8 | **10/140/145/11/13/146/8** | ✔ |
| Partition shard `screen` | 29 / 151 / 151 | **29 / 151 / 151** | ✔ |
| Partition shard `workflow` | 14 / 158 / 75 | **14 / 158 / 75** | ✔ |
| Partition shard `tenant-admin-public` | 40 / 164 / 3 | **40 / 164 / 3** | ✔ |
| **Keunikan `step.id` global** | 470 / 473 (amaran P16-02) | **470 / 473** | ✔ |

*(Shard triple = guide / langkah / langkah tour `wait_for_user`; jumlah shard = 83 / 473 / 229.)*

**Invarian partition disahkan:** setiap guide dan setiap langkah tergolong dalam **tepat satu**
wave dan **tepat satu** shard; tiada duplikat, tiada yatim, tiada baki. Dua partition bebas ke
atas semesta yang sama.

**Semakan konsistensi teks** (frasa lapuk yang sepatutnya sudah tiada dalam badan aktif pelan):
`D1–D10` = 1 kemunculan (rekod sejarah §0.7 — betul) · "enam gelombang" = 2 (naratif sejarah
v1.4 — betul) · `disableClick()` tanpa konteks "deprecated" = 0 · laluan manifest pendek = 0 ·
`restart nginx` = 0 · rujukan `PLAN-RR-15-CLAUDE.md` sebagai **sumber** senarai ID = 0 (hanya
disebut sebagai fail yang **tidak wujud**).

---

## 7. Apa yang P17 **tidak** buat

1. **Tidak menanda pelan muktamad.** v1.7 belum diaudit oleh Codex; §0.5c belum disahkan pihak
   kedua. Peraturan #6 `PLAN-RR-STATUS.md` memerlukan satu pusingan penuh tanpa penambahbaikan
   substantif — itu belum berlaku.
2. **Tidak menyunting `PELAN-PEMBAIKAN.md`.** Tiada typo atau percanggahan yang memerlukan
   suntingan ditemui (§4.1 + §6); hash keluar = hash masuk.
3. **Tidak mencipta `PLAN-RR-15-CLAUDE.md`.** Menulis fail bagi pihak giliran yang tidak pernah
   menghasilkan output = memalsukan rekod (§0.2).
4. **Tidak menyentuh kod aplikasi, git, SSH, deploy atau ujian mutasi** — tiada satu pun
   dijalankan dalam giliran ini.
5. **Tidak mencipta snapshot immutable** — di luar kebenaran giliran; diserahkan sebagai
   tindakan pemilik/P18 (§1).

---

## 8. Serahan kepada Codex P18 — apa yang perlu diaudit

**Keutamaan tertinggi (integrasi baharu v1.7, belum pernah disemak sesiapa):**

1. **§1 F0(iv) canary + tiga lapis CI** — adakah spec `e2e/ci-session-canary.spec.js`,
   tag `@session-canary` dan command literal cukup untuk dilaksanakan tanpa reka bentuk baharu
   semasa PR? (P16-01)
2. **§1 F0(iv) lapis 2/3 + agregator** — nama project/spec, `GUIDANCE_SHARD`, skema JSON
   (`guide_ids`, `step_ids`, `action_step_ids`, `blocked`, `failures`), YAML `needs`/`matrix`,
   dan **kunci `<guide_id>#<index1>`**. Adakah perbandingan **set** (bukan count) benar-benar
   tertutup? (P16-02)
3. **§1 F0(ii-b) tiga lapis `expected`/`declared`/`actual`** — adakah ia benar-benar
   menghapuskan tautologi, dan adakah route universe dibina tanpa tapisan identiti? (P16-07)
4. **§9.1a `diwan:audit-fixture`** — argumen, sempadan authorization, pengendalian rahsia
   (`try/finally`, ACL, sanitasi log), `-CleanupOnly`, dan kontrak `RunUuid` yang kini disatukan.
   Adakah idempotensi boleh dibuktikan oleh `tests/Feature/AuditFixtureCommandTest.php`?
   Adakah tenant `smoke` benar-benar terlindung? (P16-04 + P14-04)
5. **§3.7 dan §9A.3 gate `rg`** — adakah corak baharu (assert senarai fail > 0 **dan** bezakan
   rc 1 daripada rc ≥ 2) betul pada kedua-dua tempat, tanpa `!` tersembunyi di mana-mana? (P16-06)
6. **§11 D11 (14 fail)** — adakah senarai itu kini lengkap terhadap reka bentuk v1.7 sendiri,
   atau masih ada artifak tersirat yang tidak diisytihar? (P16-03)
7. **§5 fail ini vs jadual beku §1 F0(ii-a)** — semak silang 83 ID; jika Codex mengira partition
   berbeza, itu penemuan bloker.

**Perkara yang sudah stabil dan tidak perlu dibuka semula** melainkan ada bukti kod baharu:
soalan terbuka P1–P5 (fallback locale, ambang F6, pengesahan-kedua Filament, auto-minimize),
keputusan C01–C25, keputusan P12-01…P12-08, dan denominator katalog (kini disahkan bebas
**empat** kali).

**Prasyarat proses sebelum P18 menulis:** rekod hash/saiz/mtime `PELAN-PEMBAIKAN.md` dua kali
(§0.7 #1), dan — jika dibenarkan — cipta snapshot immutable sebagai langkah pertama supaya
pertikaian premis v1.6/v1.7 boleh diselesaikan dengan bukti, bukan ingatan.

---

*Pusingan 17 — Claude. Pelan **v1.7 BUKAN muktamad**. Giliran diserah kepada **Codex Pusingan 18**.*
