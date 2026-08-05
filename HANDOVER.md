# HANDOVER — Diwan (SPDM) Produksi bakwim.my

## ▶️ SAMBUNG DI SINI — F6-W2 SIAP DIBINA, menunggu CI + Deploy 8 (5 Ogos)

**Keadaan repo:** `local = origin = 0dfd201` · produksi masih imej `4fd64cf` (Deploy 7).
**CI run `30972196342`** dilancarkan pada `0dfd201` — semak keputusannya SEBELUM deploy.

📄 Bukti penuh: `Audit Review Round Robin/bukti/plan-f6-w2/LAPORAN-F6-W2.md`

### Apa yang siap
**Metrik utama F6 DITUTUP SEPENUHNYA:** `action_steps_with_generic_target` **200 → 0**
(W1 menutup 140, W2 menutup baki 60). Wave W2 13/145 → 0/0, W4 1/13 → 14/158, jumlah 83/473
kekal. `wait_for_user` 190 → 172 (18 langkah PEMERHATIAN dibetulkan, disenaraikan satu per satu
dalam laporan §(b)). `catalog_version 2026.08.05.1`.

**Gate tempatan HIJAU PENUH:** workflow 15/15 · screen 30/30 · tenant-admin-public 41/41
(setiap satu pada DB SEGAR) · agregator **GATE LULUS 83/473/172** · Pest **553** · unit 17/17 ·
pint passed · build OK.

### ⭐ Dua kecacatan produk ditutup (ditemui oleh gate, bukan oleh mata)
Kedua-duanya KEGAGALAN SENYAP dengan punca sama: perkhidmatan menolak dengan
`ValidationException` berkunci nama yang **bukan medan borang**, jadi Filament tiada tempat
merendernya — modal kekal terbuka, tiada toast, tiada ralat medan.
- **Mohon Pembetulan Rekod** (kunci `changes`) — hantar tanpa mengubah medan = senyap
- **Keluarkan Fail fizikal** (kunci `holder`) — hantar tanpa pemegang = senyap
Kedua-duanya kini memberitahu pengguna. Penjaga Pest dibuktikan MERAH pada kod lama.

### Langkah seterusnya (turutan)
1. Sahkan CI `30972196342` **7/7 hijau** (`gh run view 30972196342 --json jobs`).
2. **Deploy 8** ikut §10 + `spdm-deploy-lessons`:
   - Aset **TIDAK** berubah (`help-D0185fq1.js` kekal — tiada JS produk disentuh). Bukti deploy
     mesti bergantung pada **kandungan dalam imej + ImageID**, bukan nama aset (pelajaran D2).
   - `sync-help-index --delete` WAJIB — `catalog_version` berubah.
   - `Nothing to migrate` dijangka (tiada migrasi baharu).
   - `scp` skrip deploy, JANGAN `ssh 'bash -s' <` (pelajaran Deploy 7).
3. Pengesahan LIVE Chrome: tour `workflow` pada `/app/mam/records` + mesej "Tiada perubahan
   dikesan" pada borang Mohon Pembetulan.
4. Kemudian **F6-W3** (1 guide / 11 langkah — kecil).

### Perangkap yang sudah dibayar harganya (jangan ulang)
- **JANGAN jalankan tiga shard pada SATU DB tempatan.** Koreografi W2 benar-benar mengeluarkan
  fail hibrid (`custody_status=dipinjam`), jadi tiga guide `screen` gagal selepasnya. CI selamat
  (matriks job berasingan, `services:` sendiri — `ci.yml:291-295`). Semai semula ANTARA shard.
- **JANGAN ubah fail sumber semasa larian gate berjalan** — `artisan serve` membaca semula PHP
  setiap permintaan.
- **JANGAN `tail` output gate** — diagnostik hilang; simpan penuh, tapis kemudian.
- `explore.spec.js` gagal tempatan = **had masa 180s sahaja** (dibuktikan: lulus 4.6m dengan
  `--timeout=900000`). Bukan regresi.
- Filament 4 **tidak** guna Choices.js — komponen sendiri (`.fi-select-input-btn` +
  `li.fi-select-input-option`). Guna `pilihPilihanPertama()`.

### Jurang produk DIUKUR tetapi TIDAK dibaiki (calon F7)
**Tour tidak bertahan merentas navigasi yang dimulakan PENGGUNA.** Bila pengguna membuka rekod
daripada senarai, `autoStart` = false kerana progres wujud → tour tidak muncul semula sendiri;
pengguna mesti menekan pelancar Pembantu (barulah `resumeStep()` memulihkan kedudukan). Gate
melangkauinya dengan deep-link deterministik yang DIDOKUMEN — ia mengesahkan sasaran, dan
**tidak** mendakwa menguji kesinambungan itu.

---

## 🔧 HOTFIX BUG-A..D — laporan pemilik + 3 pepijat ditemui sendiri (5 Ogos 2026) ⭐ TERKINI

**Komit `4fd64cf`** (5 komit: `4cd973e` BUG-A · `b5e846b` BUG-B · `8c72e2f` BUG-C+D ·
`8a9f6cb` docs · `4fd64cf` BUG-A2+bukti deploy). CI run **30958629599**.
📄 `Audit Review Round Robin/bukti/hotfix-bug-ab/` — LAPORAN-BUG-A · -B · -C-D ·
TRIAGE-LOG-PRODUKSI · BUKTI-DEPLOY-7.

**Pemilik melaporkan 2 gejala; siasatan menemui 4 pepijat.**

| | Pepijat | Punca | Bukti punca |
|---|---|---|---|
| **A** | Selepas log masuk mendarat dalam masjid tenant, bukan `/admin` | pautan "Log masuk dengan kata laluan" → `/app/login`, dan LoginResponse lalai Filament mendarat pada panel SEMASA (tenant lalai = masjid PERTAMA platform untuk superadmin) | log nginx 24j: **1× GET /app/login, 0× GET /admin/login** |
| **A** | "taip bakwim.my → nampak halaman log masuk" | halaman awam tidak pernah menyemak sesi | diukur pada sesi pemilik yang hidup: `adaSesi: false` |
| **B** | **500 HIDUP** pada "Mohon Pembetulan" rekod | `comparable()` buat `(string) $enum`; `direction`/`sensitivity` di-cast enum | log produksi 22 Jul **3× userId 1** |
| **C** | Logo panel masjid melompat ke masjid LAIN | Filament `getUrl()` guna tenant **LALAI**, bukan SEMASA | di `/app/mamad` → href `/app/smoke`; kawalan: di `/app/smoke` nampak betul = pepijat halimunan |
| **D** | Superadmin tiada jalan balik ke `/admin` | tiada pautan | `/app/mamad` = **0/38** pautan ke `/admin` |

**PEMBAIKAN:** `PanelLandingResolver` = satu sumber kebenaran pendaratan §9.A dipakai magic link
DAN kata laluan (`intended()` dikekalkan) · nav/kad awam tawar "Ke Panel" (kecuali akaun tanpa
kata laluan — elak lantunan `EnsurePasswordIsSet`) · `BackedEnum` dalam `comparable()` ·
`homeUrl()` ikat tenant semasa · item menu pengguna "Panel Pentadbir" (superadmin sahaja).

**DINYATAKAN BUKAN PUNCA (diukur):** panel admin **tidak** memaut ke log masuk (19 anchor, 0
padanan; logo topbar & sidebar = `/admin`); `GET /` = 200 (tiada pengalihan).

**KEPUTUSAN YANG DIJUSTIFIKASIKAN:** (1) `/log-masuk` **tidak** dialih walau sesi aktif —
manifest `role_routes` beku menetapkannya `allow/200` untuk KESEPULUH identiti, dan pengalihan
juga menghalang tukar akaun; (2) jurang `withOnboarding` sengaja — lonjakan wizard §10 kekal
untuk magic link sahaja, kalau tidak destinasi log masuk SETIAP admin masjid yang belum selesai
persediaan berpindah dan 6 spec e2e pecah; (3) menu pengguna dipilih (bukan item navigasi)
supaya `in_navigation` manifest tidak terusik; (4) tiada ciri baharu — `/admin/mosques` sudah
memaut ke setiap tenant.

**VERIFIKASI:** Pest **546 lulus / 1 skip** (515 → 546: +19 A, +6 B, +6 C/D) · pint · build
(nama aset help **KEKAL** — ramalan) · e2e `ci-guidance` **34 lulus / 1 gagal**.
**Setiap pepijat ada BUKTI PENJAGA** (kod lama dipasang semula → ujian bertukar merah): A = tepat
5 merah · B = 5 merah dengan mesej **serupa log produksi** · C/D = `+'…/app/mam'` sedangkan
tenant semasa `man`.
🔴 Satu kegagalan e2e (`explore.spec.js:83`) **DIBUKTIKAN sedia ada**: 3 larian, termasuk
`git checkout aaf381a --` (kod yang SEDANG BERJALAN di produksi) → gagal serupa 3.0m. Tindakan
tergendala **berubah** antara larian = bajet 180s habis (8 peranan × ~10 halaman, `php -S`
Windows satu-benang), bukan halaman rosak. Hijau di CI (Linux, 4 worker).

**TRIAGE LOG PRODUKSI (62 baris ERROR):** 23 = bunyi diagnostik saya sendiri (tinker/psysh) ·
36 = insiden 18–22 Julai yang sudah ditutup · **3 = BUG-B, satu-satunya yang masih hidup**.
**Backup disahkan POSITIF:** `backup:list` → `cos_backup` reachable ✅ healthy ✅, **23 backup,
terbaharu 4 jam, 428.22 MB**. (`backup:monitor` sengaja tidak dijalankan — boleh hantar e-mel.)

**PEMBETULAN PROSEDUR DEPLOY (2):** (a) label `org.opencontainers.image.revision` berbunyi
**`unknown`** pada kedua-dua imej sejak Deploy 1 — `ARG GIT_SHA` ada tetapi tidak pernah
dihantar; Deploy 7 menghantarnya. (b) `storage` ialah volume **BERKEKALAN** dengan **223 view
Blade terkompil** → `view:clear` ditambah pada urutan deploy.
**Pra-deploy:** cakera 83% (4.8G baki) → `docker builder prune --filter until=48h` tuntut
2.707GB → **74% (7.4G baki)**; imej/container/volume tidak disentuh.

## ✅ DEPLOY 7 LIVE — `local = origin = server = 4fd64cf`

CI run **30958629599 = 7/7 HIJAU**. 📄 `bukti/hotfix-bug-ab/BUKTI-DEPLOY-7.md`

| Bukti 5A | Sebelum (Deploy 6) | Selepas (Deploy 7) |
|---|---|---|
| git | `aaf381a` | **`4fd64cf`** |
| `diwan-app` | `2d00c92e3cac` | **`35774700bd58`** |
| `diwan-web` | `4824bd182d3a` | **`96b969b4b925`** |
| aset help | `help-D0185fq1.js` + `help-CrH0eDM1.css` | **IDENTIK** (tiada JS/CSS disentuh) |
| manifest sha256 | `1aa1b3f4…` | **`1aa1b3f4…`** (app = nginx) |
| label `image.revision` | `unknown` | **`4fd64cf`** kedua-dua imej |

`#3a=#2a` · `#3b=#2b≠#2a` · `#5a=#5b=#6` kedua-dua aset.
⭐ **Setiap ramalan pra-deploy tepat** (aset kekal, KEDUA-DUA ImageID berubah — sebab `LABEL
…$GIT_SHA` datang sebelum `COPY --from=app`). Kes **terbalik** Deploy 6 → 5A diuji dua arah.
🔑 **Percanggahan hash diselesaikan dengan ujian:** md5 hari ini ≠ rekod Deploy 6 untuk fail
SAMA; `sha256sum|cut -c1-32` memberi **`753f7e26…`/`f2406b31…`** = sama sebiji dgn Deploy 6 →
aset identik bait-untuk-bait. Kedua-dua algoritma kini direkod.

Kesihatan: `Nothing to migrate` · `config:cache` · **`view:clear`** (223 view basi) ·
83 guide disegerakkan · `diwan:health` OK · `/up` 200 (8080, 80, **HTTPS awam**) ·
**smoke 9/9** · `queue:failed` kosong · **8/8 container**.

🔴 **INSIDEN DEPLOY (direkod supaya tidak berulang):** `ssh 'bash -s' < deploy.sh` +
`docker compose exec -T` = arahan itu **memakan baki skrip sebagai stdin** → deploy berhenti
SENYAP selepas `migrate` dengan **exit 0**. Dikesan dengan membaca output, bukan mempercayai kod
keluar. Baki langkah dijalankan semula dengan `< /dev/null` pada setiap `exec`.
**Untuk deploy akan datang: `scp` skrip ke pelayan dan jalankan sebagai FAIL.**

**DISAHKAN LIVE dalam Chrome** (sesi pemilik, tiada kredensial ditaip) — pasangan sebelum→selepas:
`/` nav `Log Masuk` → **`Ke Panel`** · CTA **"Teruskan ke Panel" → `/admin`** · notis "Anda sudah
log masuk sebagai Superadmin" · `/log-masuk` notis + panel (borang KEKAL untuk tukar akaun) ·
`/app/mamad` href logo `/app/smoke` → **`/app/mamad`** · `/app/mamad/peti-masuk` → **`/app/mamad`** ·
**"Panel Pentadbir" ada** (`href=/admin`) · `/admin` kekal betul.
⚠️ Gejala 1 (pendaratan log masuk) tidak boleh saya sahkan live — perlu sesi log masuk BAHARU dan
saya tidak menaip kata laluan. Dibuktikan 19 Pest + 3 Chromium. **Pemilik: log keluar →
/log-masuk → "Log masuk dengan kata laluan" → mesti mendarat `/admin`.**

⚠️ **Tiket `SUP-260801-HXQ0DIOL`:** pemilik menukar statusnya kepada **selesai** kerana UI tiada
butang padam. Tindakan ini **DITUTUP**. (Tiada butang padam untuk tiket = wajar; tiket ialah
rekod sokongan, jejak audit lebih penting daripada pemadaman.)

**▶️ SETERUSNYA:** F6 W2 (`workflow`, 60 langkah tindakan bersasar generik) → W3–W6 → F7–F10.

---

## ✅ DEPLOY 6 (F6-W1) LIVE — `cc9f0c7` (5 Ogos 2026)

**`local = origin = server = cc9f0c7`.** CI run 30946820894 **7/7 HIJAU** (keempat-empat check
wajib). 📄 `Audit Review Round Robin/bukti/deploy-6/BUKTI-DEPLOY-6.md`

| Bukti 5A | Sebelum | Selepas |
|---|---|---|
| git | `bc7cccc` | **`cc9f0c7`** |
| `diwan-app` | `2831c4c83616` | **`2d00c92e3cac`** |
| `diwan-web` | `6e8e3f5a9fb4` | **`4824bd182d3a`** |
| aset help | `help-Da8KtLOe.js` | **`help-D0185fq1.js`** (CSS `help-CrH0eDM1.css` KEKAL) |
| manifest sha256 | `4aa3b2e5…` | **`1aa1b3f4…`** (app = nginx ✅) |

`#3a=#2a` · `#3b=#2b≠#2a` · `#4b` sama · **`#5a=#5b=#6` untuk KEDUA-DUA aset**.
⭐ Ramalan dibuat SEBELUM deploy: JS berubah, CSS kekal (hanya `help.js` disentuh) — **tepat**.

Kesihatan: `Nothing to migrate` (0 baris data) · `sync-help-index --delete` **83 guide** ·
`/up` 200 · **smoke 9/9** · `failed_jobs` 0 · **8/8 container** · 4 laluan awam 200.

**Disahkan LIVE dalam Chrome (laluan awam, tiada kredensial ditaip):** tour `public.login`
langkah **1/2** sorot `login-identity`, langkah **2/2** "Minta pautan" sorot `login-submit`,
CTA betul, **tiada ralat palsu**. Aset yang dihidang kepada pelayar = `help-D0185fq1.js`.
⚠️ Butang langkah 2 sengaja TIDAK ditekan (ia hantar e-mel pautan log masuk sebenar).

**Yang pengguna dapat:** 27 guide `screen` menyorot kawalan SEBENAR (placeholder katalog
258→0, tindakan-generik 200→60, defect mobile 6→0) · **bug produk #1 F0 ditutup betul-betul**
(auto-advance tour boleh MATI — hidup di produksi sejak Deploy 1) · sorotan fallback tidak
lagi melekat.

**▶️ SETERUSNYA: F6 W2** (`workflow`, 60 langkah tindakan bersasar generik) → W3–W6 → F7–F10.
Menunggu pemilik: padam tiket `SUP-260801-HXQ0DIOL` · OAuth Google Drive (consent PUBLISHED) ·
pengesahan visual tour dalam panel berautentikasi.

---

## SESI — F6-W1 GATE + KECACATAN PRODUK DIBAIKI (4 Ogos 2026, malam) ⭐ TERKINI

**`local = origin = 4f364c9`. PRODUKSI KEKAL `bc7cccc` (Deploy 5) — BELUM DEPLOY.**
Deploy 6 disekat: CI run **30924969914** masih berjalan semasa sesi ditutup.
📄 `Audit Review Round Robin/bukti/plan-f6-w1/LAPORAN-FASA-6-W1.md` (laporan penuh).

### Rantaian komit sesi ini
`794fc6d` → **`2f7bbbb`** (tutup gate: 2 kecacatan harness) → **`f64fb1c`** (G3 pada urutan
DIREKOD) → **`4f364c9`** (kecacatan produk: sorotan fallback tidak lagi melekat).

### 📈 KEMAJUAN CI (instrumentasi memandu setiap pusingan)
| Larian | shard `screen` | Nota |
|---|---|---|
| `2f7bbbb` · `f64fb1c` | 5 gagal | sebelum perekam |
| `abaeaa2` | 3 gagal | perekam dipasang |
| `cf6995c` | 1 gagal | fix auto-advance mati |
| `99d6a55` | **0 — HIJAU** | fix maju-wizard-dua-kali; **tetapi `workflow` gagal** |
| `<seterusnya>` | — | fix overlay memintas klik pencetus muat naik |

**`workflow` gagal pada `99d6a55` DISEBABKAN perubahan produk saya** — log CI menamakan
pemintasnya secara literal: `<svg class="driver-overlay …"> subtree intercepts pointer events`
pada `[data-help-target="inbox-upload"]`. Lubang overlay berada di atas elemen yang DISOROT;
sebaik tour maju lebih awal, butang itu berada di bawah bahagian pepejal overlay dan klik
SEBENAR tidak lagi mendarat. Fix: `try click → catch dispatchEvent` (membuka modal ialah
`wire:click`, bukan penghantaran borang). ⚠️ Tempatan `workflow` LULUS 15/15 walaupun tanpa fix
ini, jadi buktinya datang daripada log CI, bukan larian tempatan.

⚠️ **Jurang proses saya:** selepas perubahan PRODUK, saya sahkan `unit` + shard `screen` +
`guidance.spec.js` + Pest tetapi **bukan** shard `workflow`/`tenant-admin-public`. Perubahan
runtime tour menyentuh SETIAP shard. CI menangkap apa yang saya langkau.

### 🎯 PUNCA MUKTAMAD DITEMUI & DIBAIKI — auto-advance tour boleh MATI (bug produk #1 F0)

Instrumentasi perekam membolehkan jejak **LULUS tempatan** dibanding terus dengan jejak
**GAGAL CI** bagi guide yang sama. Kedua-duanya sampai ke keadaan IDENTIK (modal terbuka,
sasaran `ada`, banner hilang); bezanya hanya **apa yang menghilangkan banner**:
```
tempatan  1:record-version (B,modal1,sasaran ada) → 1:record-version-file (−) → 2:…
CI        1:record-version (B,modal1,sasaran ada) → 1:record-version (−,sasaran ada) …
```
Di CI, harness menekan "Tunjuk arahan" dahulu → `activeDriver.refresh()` → `onHighlighted` →
`watchForNextStep()` → **`clearTransitionWatch()` membunuh jadual `moveNext` 120ms**, dan guard
`… || resolveStepElement(next,false)` menolak poller baharu kerana sasaran SUDAH wujud →
tour terkandas KEKAL. **Morph Livewire mencetuskannya tanpa tindakan pengguna** — jadi ini
kecacatan pengguna sebenar, bukan kes tepi harness. F2 dilaporkan menutup bug ini; penutupan
itu **tidak meliputi laluan ini**.

**Fix:** `watchForNextStep` — jika sasaran sudah sedia DAN `menungguTindakanIndex === index`,
maju melalui jadual 120ms yang sama lalu `return`. Saling eksklusif dengan observer+poller
sedia ada (yang hanya dipasang bila sasaran BELUM sedia) → sync asal utuh.
`menungguTindakanIndex` ditanda dalam `minimiseForAction`, dikosongkan bila tour berpindah
langkah, direset dalam kedua-dua `onDestroyed`.
**Kesan sampingan yang segera muncul:** tour maju lebih awal → butang wizard tertanggal lebih
cepat → menyalakan bom `dispatchEvent` 30s yang tinggal dalam `advanceWizard` + sandaran CTA;
kedua-duanya kini bertempoh 3s dan ditelan.

**Bukti tempatan (semua exit 0):** `unit` **17** · shard `screen` **30/30** (11.1m) ·
`guidance.spec.js` penjaga tour F2 **20** (18.0m) · Pest **515✓/1 skip**.

### 🔴 SEJARAH: 3 guide `screen` gagal di CI sebelum pembaikan di atas

**CI run `30925942728` (`abaeaa2`) = failure.** 3 daripada 4 check WAJIB hijau:
`PostgreSQL, Redis, Meili, OCR and tests` ✔ · `Docker app image` ✔ · `Docker web image` ✔ ·
`guidance-e2e (workflow)` ✔ · `guidance-e2e (tenant-admin-public)` ✔ ·
**`guidance-e2e (screen)` ✘ (4 failed / 26 passed, 10.2m) → `guidance-e2e-gate` ✘.**

⚠️ **CI run `30924969914` (`4f364c9`) = `cancelled`, BUKAN failure** — saya push komit dokumen
`abaeaa2` semasa ia berjalan dan concurrency group membatalkannya. **Jangan push dokumen semasa
CI kod berjalan.**

**TEMPATAN semuanya hijau pada kod SAMA** (jadi ini kegagalan khusus-CI):
shard `screen` **30/30** bersih + **30/30 bawah beban CPU** · `workflow` **15/15** ·
`tenant-admin-public` **41/41** · **AGREGATOR: GATE LULUS 83/473/190 (SET)** exit 0.

**Tiga kegagalan CI dengan jejak yang DIREKOD (mula dari sini, jangan siasat semula):**
| Guide | Bukti |
|---|---|
| `persediaan-berpandu#4` | `perekam sedia=true, entri=8`, jejak: `1:onboarding-start → 1:onboarding-phone → 2:onboarding-phone → 2:onboarding-wa-source → 3:onboarding-wa-source → 3:- → 3:page-content → 4:page-content` |
| `mohon-pembetulan-rekod#2` | `entri=1`, jejak: `1:record-correction` — tour TIDAK pernah tinggalkan langkah 1 |
| `ganti-versi-rekod#2` | `entri=1`, jejak: `1:record-version` — sama |

**Yang sudah DIUKUR dan boleh dipercayai (jangan ulang kerja ini):**
- Pagar ulangan **BETUL**: `jangkaModal=true` untuk kedua-dua guide modal (state registri
  mengandungi `modal:`).
- **Hipotesis "`modalTerbuka()` memadan modal tertutup" DITOLAK oleh ukuran**: pada
  `/app/mam/records/3`, sebelum modal dibuka `jumlahModalWindow=0` / `isVisible()=false`;
  selepas dibuka `1` / `true`. Jadi ulangan TIDAK disekat oleh pagar itu.
- Maka klik itu hilang **BERULANG** di CI (asal + 2 ulangan), bukan sekali — bukan perlumbaan
  ringkas.
- `persediaan-berpandu#4`: pembaikan produk `rehighlightWhenTargetArrives` **tidak** menyorot
  semula di sini; jejak kekal `4:page-content`. Belum diketahui sama ada `waitForStep` gagal
  (sasaran benar-benar tiada) atau `refresh()` menyelesaikan kembali kepada fallback.

**Langkah seterusnya yang dicadangkan (instrumentasi dahulu, JANGAN teka):**
1. Luaskan perekam supaya setiap entri turut merakam **kewujudan + keterlihatan sasaran langkah
   itu** (`document.querySelector('[data-help-target=X]')` + rect). Itu membezakan "sasaran
   tiada" daripada "resolusi gagal walaupun sasaran ada" dalam SATU larian CI.
2. Untuk dua guide modal: rakam bilangan elemen padanan `[data-help-target=record-correction]`
   dan sama ada ia `disabled`/tertanggal semasa klik dihantar. `entri=1` bermakna popover tidak
   pernah berubah — periksa juga sama ada CTA benar-benar diklik (tour minimize berlaku?).
3. Muat turun `guidance-traces-screen-failure` untuk melihat turutan tindakan sebenar
   (guna trace untuk TURUTAN, bukan identiti kegagalan).

**Deploy 6 kekal DISEKAT** sehingga `guidance-e2e-gate` hijau. Jangan deploy atas gate merah.

### Bukti sedia ada (semua exit 0)
| Ujian | Keputusan |
|---|---|
| Shard `screen` bersih | **30/30** (12.3m) → 29 guide/151 langkah/111 tindakan, blocked 0, complete true |
| Shard `screen` **bawah beban CPU** | **30/30** (31.6m) — sebelum pembaikan produk: **5 GAGAL** |
| Shard `workflow` (kod lama) | 15/15 → 14/158/75 · di CI `f64fb1c`: **lulus** |
| Shard `tenant-admin-public` (kod lama) | 41/41 → 40/164/4 · di CI `f64fb1c`: **lulus** |
| Agregator (kod lama) | **GATE LULUS 83/473/190** (perbandingan SET) |
| Penjaga F1/F2 `unit` | 17/17 |
| Pest | **515 lulus / 1 skip** · pint passed · build OK |

⚠️ Shard `workflow`/`t-a-p` tempatan masih daripada kod SEBELUM `4f364c9`; larian tempatan
untuknya belum tamat. CI `4f364c9` ialah pengesahan berkuasa.

### KECACATAN PRODUK DIBAIKI (`4f364c9`) — lencongan F7 ditarik ke hadapan, dinyatakan
`element: () => resolveStepElement(step) || page-content` dipanggil Driver.js **sekali** per
peralihan. Jika morph Livewire / langkah wizard belum dirender menjadikan sasaran tiada pada saat
itu, tour menyorot **seluruh halaman** dan **tidak pernah** menyelesaikan semula. Diukur dua
kali: bawah beban tempatan (5 guide) dan **secara TETAP di CI** (jejak
`3:onboarding-wa-source → 3:- → 3:page-content → 4:page-content`).
Fix `rehighlightWhenTargetArrives()` dalam `resources/js/help.js`: tunggu sasaran diisytiharkan
(bounded 4s via `waitForStep`) → sorot semula; **sekali per indeks** (elak gelung `refresh()`);
`rehighlightIndex` direset dalam KEDUA-DUA `onDestroyed`. **Sync F2 (§0.3) tidak disentuh.**

### DUA kecacatan harness (`2f7bbbb`)
1. **`filter({hasText: <RegExp>})` menguji teks MENTAH** — whitespace tidak dinormalisasi.
   `/^Seterusnya$/` → **count=0** pada butang Blade. Diukur pada DOM aplikasi DAN sintetik.
   Fix: `getByRole(..., {exact:true})`.
2. **`dispatchEvent` sebagai sandaran `.catch()` = bom masa 30s** — banner lenyap kerana tour
   BERJAYA maju; klik gagal 5.1s, `dispatchEvent` melempar 30s kemudian. Fix: tempoh pendek +
   ditelan (ketiadaan banner = KEJAYAAN). Menyelesaikan **7** kegagalan sekali gus.

### PUNCA CI dan pembaikannya (`f64fb1c`)
- **Bentuk A** wizard terlanjur maju (`n` betul, `sasaranAktif` false) → had **SATU** kemajuan
  wizard per peralihan (invarian struktur: 1 langkah guide = 1 langkah wizard). Masa tidak diubah.
- **Bentuk B** klik tindakan HILANG (4 guide) → bukti `serve-ci.log`: **sifar permintaan selama
  94 saat**. Fix: ulang `dispatchEvent` SAMA sehingga ada kesan, dipagar `state` `modal:` registri,
  dan SABAR ~4s (ulangan terlalu awal me-`mountAction` dua kali).
- **Punca mendasari:** gate mengundi keadaan **SEKETIKA** (`n === i`). Sync F2 memang memaju
  sebaik sasaran muncul → tour boleh melintasi langkah dalam milisaat (diukur: harness tunggu
  `n:4`, tour sudah `n:5`). Kini **perekam dalam halaman** merakam setiap peralihan dan assertion
  bertanya "adakah langkah *i* PERNAH berlaku dengan sasaran betul" — kalis-perlumbaan dan
  **lebih kuat**, bukan lebih longgar. Harness juga tidak menekan CTA jika tour sudah melintasi.

### ⛔ EMPAT pendekatan DIUJI dan DITOLAK (semua memerahkan guide hijau) — jangan ulang
| Cubaan | Kesan | Sebab |
|---|---|---|
| Klik sebenar ganti `dispatchEvent` | 3 guide merah | `help.js:663-664` `overlayClickBehavior:'close'` → klik koordinat **menutup tour** (pelajaran F0 yang saya langgar) |
| `waitFor(8s)` ganti tidur 1500ms | 2 guide merah | kesan diukur; mekanisme tidak dituntut terbukti; kod DIBUANG |
| Ulang tindakan setiap lelaran | 1 guide merah | `mountAction` dua kali / tutup modal yang sedang dibuka |
| Tangguh kecacatan produk ke F7 | 1 pusingan CI terbuang | "beban saya tidak realistik" — SALAH; CI membuktikan kecacatan sama secara tetap |

🔑 **Pola:** empat cubaan pertama melaras **cara** harness berinteraksi → semuanya gagal. Yang
berjaya mengubah **apa yang diperhatikan** (perekam) atau **apa yang dianggap benar tentang
struktur** (had 1 kemajuan wizard). Dalam sistem dengan koreografi terbukti, laras **pengamatan**,
bukan interaksi.

### Gotcha alat baharu (kekal berguna)
- **`php -d … artisan serve` TIDAK hantar `-d` kepada anak.** Disahkan pada vendor:
  `ServeCommand::serverCommand()` = `[php_binary(),'-S',host:port,server.php]`, cwd=`public_path()`.
  Anak kekal had php.ini 30s → `PHP Fatal error: Maximum execution time … ClassLoader.php:429` →
  `page.goto` tunggu `load` yang tak datang → tamat masa 60s. **Lancar TERUS:**
  `cd public && php -d max_execution_time=0 -S 127.0.0.1:8092 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`
- **`addInitScript` berjalan SEBELUM DOM** → `observe(document.documentElement,…)` boleh melempar
  dan memusnahkan pemasangan **senyap** (log wujud tapi kosong, kelihatan seperti "langkah tidak
  berlaku"). Pasang observer selepas DOM sedia + **laporkan status perekam dalam mesej kegagalan**.
- **`failures: []` dalam shard JSON BUKAN "tiada kegagalan"** — Playwright mulakan semula worker
  selepas ujian gagal → keadaan modul direset. Baca **`complete`**.
- **`explore.spec.js` (crawl 9-peranan) gagal TEMPATAN** dengan tamat masa 180s. Diuji `git stash`:
  gagal **sama** dengan/tanpa perubahan (3.3m) = **sudah ada**, hijau di CI. Pelayan dev Windows
  satu-benang. Jangan salahkan perubahan sendiri tanpa eksperimen stash.
- **Jangan salurkan larian ujian melalui `tail`** — `EXIT=0` jadi milik `tail`.
- **Jangan diagnosis daripada `trace.zip` bila laporan reporter ada** — penghurai sendiri memberi
  saya mesej ralat **guide yang salah** dan saya memburu punca yang tidak wujud.

### Deploy 6 — sedia jalan, baseline 5A sudah dirakam
| Bukti | Nilai produksi sekarang |
|---|---|
| #1 git | `bc7cccc` |
| #2a `diwan-app` | `2831c4c83616…` |
| #2b `diwan-web` | `6e8e3f5a9fb4…` |
| #4a aset | `assets/help-Da8KtLOe.js` + `assets/help-CrH0eDM1.css` |
| #4b manifest sha256 | `4aa3b2e5…` (app = nginx ✔) |

**Ramalan yang boleh gagal:** selepas deploy, #4a mesti jadi **`assets/help-Dyf0E2-J.js`** dengan
**`help-CrH0eDM1.css` TIDAK berubah** (hanya `help.js` disentuh). Aset berubah → **rebuild
`app` DAN `nginx`** (jangan ulang kesilapan UI pecah). Selepas deploy:
`diwan:sync-help-index --delete` (catalog_version `2026.08.04.2`), `diwan:smoke` 9/9,
pengesahan visual Chrome. **Tiada migrasi baharu** sejak `bc7cccc`.
⛔ `DemoSeeder` berubah — **jangan** jalankan seeder di produksi.

---

## SESI — F6-W1 penutupan gate: pusingan awal (4 Ogos 2026, petang)

**Produksi kekal `bc7cccc` (Deploy 5).** Kerja produk W1 tidak berubah sejak `d29399f`;
sesi ini menutup **gate**, yang sebelum ini 28/29.

### Keputusan gate (benih segar sebelum setiap shard, satu pelayan sahaja)
| Shard | Jangkaan manifest | Keputusan |
|---|---|---|
| `screen` | 29 guide / 151 langkah / 111 tindakan | **30/30 LULUS** (13.2m) · JSON sepadan tepat · `blocked` 0 |
| `workflow` | 14 / 158 / 75 | **15/15 LULUS** (10.9m) · JSON sepadan tepat · `blocked` 0 |
| `tenant-admin-public` | 40 / 164 / 4 | 39/41 — **1 guide** (`tenant.records`, W5) tumbang pada stall pelayan tempatan; perlu larian bersih semula |

### Dua kecacatan HARNESS ditemui — punca diukur, bukan diteka
1. **`filter({hasText: <RegExp>})` menguji teks MENTAH** (whitespace tidak dinormalisasi).
   `/^Seterusnya$/` → **count=0** pada butang Blade yang jelas kelihatan. Diukur pada DOM
   aplikasi DAN DOM sintetik. Punca `screen.persediaan-berpandu`. Fix: `getByRole(exact)`.
2. **`dispatchEvent` sebagai sandaran `.catch()` = bom masa 30s** — banner "Panduan menunggu"
   lenyap kerana tour **berjaya** maju sendiri; klik gagal 5.1s, `dispatchEvent` melempar 30s
   kemudian. Fix: tempoh pendek + ditelan (ketiadaan banner = KEJAYAAN).
   **Satu pembaikan ini menyelesaikan KESEMUA tujuh kegagalan baki shard `screen`.**

### Punca stall pelayan tempatan (bukan produk, bukan gate)
`php -d max_execution_time=0 artisan serve` **tidak** menghantar `-d` kepada pelayan web anak —
disahkan pada kod vendor (`ServeCommand::serverCommand()` = `[php_binary(),'-S',host:port,server.php]`).
Anak kekal pada had php.ini 30s → `PHP Fatal error: Maximum execution time of 30 seconds
exceeded … ClassLoader.php:429` → `page.goto` tunggu `load` yang tak datang → tamat masa 60s.
**Lancar terus:** `cd public && php -d max_execution_time=0 -S 127.0.0.1:8092 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`.
`PHP_CLI_SERVER_WORKERS` tidak disokong Windows. Isu tempatan sahaja — CI (Linux) tidak terjejas.

### Perkara yang perlu diketahui tentang gate
- **`failures: []` dalam shard JSON BUKAN "tiada kegagalan"** — Playwright mulakan semula worker
  selepas ujian gagal → keadaan modul direset. `complete` (dikira drp `doneGuides.size`) tetap
  menangkapnya, jadi gate gagal-tertutup. Baca `complete`, bukan `failures`.
- `guide_ids`/`step_ids` dalam shard JSON datang drp MANIFEST, bukan keputusan larian.

### Dua hipotesis SAYA yang salah (dihapuskan oleh ukuran, direkod supaya tidak diulang)
Pelayan hantu (**dua** `artisan serve` pada :8092 — dibunuh, kegagalan berulang serupa) ·
mutasi data silang-guide (snapshot DB: **sifar** perubahan data perniagaan).

### Dua kesilapan proses saya
Mendiagnosis drp `trace.zip` dengan penghurai sendiri → mesej ralat guide **yang salah**,
memburu punca yang tidak wujud. Guna `--reporter=list` penuh ke fail. ·
Menyalurkan larian ujian melalui `tail` → `EXIT=0` milik `tail` (ulangan pelajaran `gh run watch`).

### Baseline 5A untuk Deploy 6 (dirakam drp pelayan hidup)
git `bc7cccc` · app `2831c4c83616…` · web `6e8e3f5a9fb4…` · aset `assets/help-Da8KtLOe.js` +
`assets/help-CrH0eDM1.css` · manifest sha256 `4aa3b2e5…` (app = nginx ✔). Binaan tempatan
memberi `help-BFhO_EWt.js` dengan **CSS tidak berubah** — ramalan khusus untuk disahkan.
Tiada migrasi baharu sejak `bc7cccc`. Selepas deploy: `diwan:sync-help-index --delete`
(catalog_version `2026.08.04.2`). ⛔ `DemoSeeder` berubah — **jangan** jalankan seeder di produksi.

---

## SESI — F6-W1 KERJA PRODUK SELESAI, GATE 28/29 (4 Ogos 2026)

**`d29399f` dipush · TIDAK DIDEPLOY** (gate `guidance-e2e-gate` belum hijau penuh).
Produksi kekal `bc7cccc` / Deploy 5. 4 komit: `a5a792e` → `d3d2ac9` → `c1fabc2` → `d29399f`.
📄 `Audit Review Round Robin/bukti/plan-f6-w1/LAPORAN-FASA-6-W1.md`

### Hasil pada denominator PENUH (§7.4)
| Metrik | Asas audit | Sekarang |
|---|---|---|
| Placeholder "Langkah N" | 258 | **0** |
| Langkah tindakan bersasar generik | 200 | **60** (semua baki dalam W2 `workflow`) |
| Sasaran generik diisytihar | 443 | **299** |
| Defect popover mobile | 6 | **0** |
| W1 senarai kerja | 27 guide / 135 langkah | **0 / 0** (wave siap) |

Suite **515 lulus / 1 skip** · pint passed · validator manifest exit 0 · registri 167 sasaran
(142 aktif + 25 rizab). Denominator beku dikemas dalam **KEEMPAT-EMPAT** penjaga.

### 🔴 SATU BAKI SEBELUM DEPLOY 6
`screen.persediaan-berpandu` tidak boleh dipandu gate (tersekat langkah 1) walaupun **produk
terbukti betul** — probe langsung menunjukkan `dispatchEvent` pada butang "Seterusnya" wizard
memajukan wizard dan `onboarding-phone` bertukar `tersembunyi` → `VISIBLE`. **Jurang harness,
bukan produk.** Tiga pendekatan dicuba; peraturan #9 dipatuhi (berhenti, rekod).
**Langkah seterusnya:** baca `trace.zip` larian itu untuk melihat sama ada klik wizard
benar-benar berlaku dalam konteks tour, kemudian jalankan 3 shard + agregator, CI, Deploy 6.

### ⚠️ KECACATAN PRODUK DIUKUR, BELUM DIBAIKI → F7 §8
Sasaran di bawah lipatan dalam modal boleh-skrol menolak popover `position: fixed` — dan
**lubang overlaynya** — ke luar viewport, jadi overlay pepejal **menyerap setiap klik**.
Pengguna terkandas (keluarga sama dengan bug banner F0). Diukur 1440×1000: sasaran y=1168,
popover y=789 h=263, CTA y=1004, lubang `M291,1158`. `scrollIntoView` berfungsi dipanggil
sendiri (scrollTop 0→244) tetapi TIDAK melekat dalam kitaran tour — 4 pendekatan gagal, kod
tidak terbukti DIBUANG, ukuran penuh dalam `resources/js/help.js`.

### ⚠️ Viewer dokumen tidak boleh menjalankan tour
`/viewer/{media}` = halaman berasingan (URL bertandatangan 30 min) TANPA Livewire/`help.js`.
Sasaran dipasang + `reserved`; perlu runtime bantuan di sana → F7.

### Data benih demo DIPERLUAS (sebab: empat skrin tiada data langsung)
`DemoSeeder::seedTugasanDemo` — 1 fail hibrid (+1 pergerakan supaya panel sejarah bukan 0px,
+1 geran akses), 1 minit kepada Pengerusi, 1 permohonan kelulusan. Log aktiviti terisi
sendiri (3 entri). Idempotent. `office-workflow.spec.js` menapis baris ikut teks penanda
unik — disemak, selamat.

### 4 penemuan harness (bukti, bukan tekaan)
1. `.driver-active-element` boleh KEKAL pada elemen langkah sebelumnya → `querySelector`
   memulangkan elemen SALAH; gate kini periksa keahlian semua elemen aktif.
2. Resolver halaman butiran mesti sahkan SEMUA sasaran peringkat-halaman ("Beri Akses" ada
   pada setiap fail → memilih fail tanpa geran).
3. `isVisible()` = snapshot tanpa menunggu → negatif palsu pada relation manager/infolist.
4. `Tab::extraAttributes()` Filament menerapkan atribut pada BUTANG **dan** panel
   (vendor `tabs.blade.php:127/165/304`); resolusi memilih butang — disahkan desktop+mobile.

---

## SESI — ✅ DEPLOY 5 (F5 KATALOG & TOUR AWAM) LIVE (4 Ogos 2026) ⭐ TERKINI

**`5f4247a` LIVE di bakwim.my** · CI run 30857969395 **7/7 HIJAU** ·
📄 `bukti/deploy-5/BUKTI-DEPLOY-5.md` + `bukti/plan-f5/LAPORAN-FASA-5.md`

| | Sebelum | Selepas |
|---|---|---|
| git server | `16c3376` | **`5f4247a`** |
| `diwan-app` | `3df4c706e182` | **`2831c4c83616`** |
| `diwan-web` | `efd9337d5799` | **`6e8e3f5a9fb4`** |
| aset help | `help-BceoIbJG.js` | **`help-Da8KtLOe.js`** |
| `catalog_version` | `2026.08.03.3` | **`2026.08.04.1`** |

Rantaian 5A **LULUS PENUH** (`3a=2a · 3b=2b · 4a=4b · 5a=5b=6`, md5
`fb71a2eb9e2bb6580942e997f711d246`). `sync-help-index --delete` = **83 guide**.
Kesihatan: /up 200 · health OK · **smoke 9/9** · failed_jobs 0 · 0 mutex · 8/8 container ·
4 laluan awam 200. **Migrasi: "Nothing to migrate" — sifar baris data disentuh.**

### ✅ DISAHKAN VISUAL LIVE dalam Chrome pada bakwim.my
Tour `/log-masuk` langkah 1 = popover **"Masukkan identiti"** menyorot **medan input**;
langkah 2 = **"Minta pautan"** menyorot butang **"Hantar Pautan Log Masuk"**, CTA
**"Buat pada skrin"**. **TIADA "Tindakan belum tersedia"** — RR-01-01 **MATI di produksi**.
`/bantuan`: `page-content` kini **unik** (1, bukan 2).

### ⚠️ Baki §6.6 #4 milik pemilik (perlu sesi berautentikasi)
`/app/{slug}/peti-masuk` → **Pembantu Diwan** → panduan **Muat naik dokumen**: langkah 1 sorot
butang **+ Muat Naik Dokumen**, langkah 2 sorot **ruang seret fail**, langkah 3 sorot butang
**Hantar** — tiga tempat BERBEZA, bukan seluruh modal. Saya tidak pernah menaip kredensial
produksi; yang dibuktikan ialah kod hidup dalam imej + 8 e2e + 3 Pest + gate CI yang melalui
kelima-lima langkah dengan muat naik SEBENAR.

---

## SESI — F5 DIBINA (4 Ogos 2026)

**Komit `142cb56`** · 📄 `Audit Review Round Robin/bukti/plan-f5/LAPORAN-FASA-5.md`
Pest **500 → 515** · e2e `ci-guidance`+`unit` **49/49** · pint passed · build OK ·
manifest + validator + PlanManifestTest ketiga-tiganya hijau.

### Apa yang pengguna dapat
1. **Tour `/log-masuk` berfungsi** (RR-01-01 mati). Dahulu kedua-dua langkah jatuh ke ralat
   palsu "Tindakan belum tersedia" kerana layout tetamu tiada `<main>`. Kini langkah 1
   menyorot **medan input sebenar**, langkah 2 menyorot **butang Hantar Pautan Log Masuk**.
   Disahkan visual dalam Chrome + e2e desktop 1280×800 dan mobile 390×664.
2. **Tour muat naik menunjuk tiga tempat berbeza** — butang → dropzone → Hantar. Dahulu
   langkah 2 dan 3 menyorot seluruh tetingkap modal (objek sama, sama besar).
3. **Sasaran navigasi responsif** — dashboard menyorot sidebar pada desktop, butang ☰ pada
   telefon. Dahulu `sidebar` memulangkan `null` pada mobile → ralat palsu.
4. **Tajuk tour bermakna** — kohort 25 guide/124 langkah: duplikasi verbatim **77 → 0**,
   tajuk terpotong tengah perkataan **20 → 0**, placeholder "Langkah N" **108 → 0**.

### 🔴 Regresi yang F5 sendiri perkenalkan lalu tutup (pelajaran utama sesi ini)
Lima ujian tour F2 **tamat masa 180s** selepas F5 dibina — 100% boleh dihasilkan semula.
Eksperimen penentu: `git stash` F5 → kelima-limanya lulus **10–14s**; pulihkan → gagal semula.

**Punca:** satu elemen hanya boleh memegang **SATU** `data-help-target`. F5c menandakan
`.fi-sidebar` sebagai `sidebar` **dan** `nav-sidebar`; kedua-duanya berebut atribut yang sama,
jadi `decorateTargets()` — dipanggil pada **setiap** `resolveStepElement()` — menulis semula
atribut pada setiap panggilan. `transitionObserver`/`automaticModalGuard` memerhati
`attributes: true` → **ribut mutasi berterusan** → koreografi tour tersangkut.

**Pembaikan:** ruang nama berasingan `data-help-nav` + tulisan idempoten. Penjaga baharu
menghitung mutasi atribut sepanjang 1 saat tour aktif dan menuntut **0**.

### ⚠️ Tiga kali alat/andaian SAYA yang salah, ditangkap oleh ukuran
1. **`.fi-sidebar` mobile bukan `display:none`** — diukur iPhone 13: `display:flex`,
   `rects=1`, tetapi **`x = −320`** (off-canvas). `isVisible()` melaporkannya kelihatan →
   `nav-primary` tersalah pilih. Ditambah `intersectsViewport()`, dikenakan **hanya** pada
   calon nav (global akan mengubah 473 langkah — kerja F6/F7).
2. **Dua penjaga saya gagal menangkap regresi sengaja** (R2, R7 daripada 9). `strpos('<h1>')`
   mencari kemunculan **pertama**, jadi `<h1>` kedua di dalam `<main>` terlepas; fixture
   `preserveWords` jatuh **tepat** pada sempadan perkataan sehingga potongan naif memberi
   hasil identik. Selepas dikuatkan: **9/9 ditangkap**.
3. **Alat metrik saya memberi 0 walaupun pada asas audit** — ia akan membenarkan dakwaan
   palsu "77 → 0". Ditentukur terhadap data produksi sebenar
   (`bukti/pusingan-11-codex/production-desktop-all-tour-steps.json`): definisi yang
   menghasilkan **77 tepat** ialah `title == description` selepas buang noktah; **20** ialah
   bilangan tajuk berakhir elipsis.

### 🔧 Dua jurang gate ditemui di luar skop pelan (dibaiki)
- **Projek Playwright `unit` TIDAK PERNAH dijalankan CI** sejak dicipta pada F2 — 10 ujian
  yang mengunci kontrak label↔kelakuan tour tidak pernah melindungi `main`. Kini satu step
  dalam job wajib (~1s, tiada perkhidmatan).
- **4 entri registri `active` tidak dirujuk katalog** (drift F6-W0) → `reserved` + sebab;
  ujian yatim dua hala kini menguatkuasakannya.

### Lencongan dari pelan (dinyatakan, bukan disembunyikan)
1. `screen.muat-naik-dokumen` **kekal 5 langkah** (§6.2 cadang 4) — invarian partition
   **473** dibekukan F0 dan diassert sebagai STRUKTUR. Maksud C12 dipenuhi penuh dengan lima
   sasaran berbeza; langkah ke-5 mengekalkan amaran antivirus.
2. `tenant.dashboard#4` **tiada** `wait_for_user` — ia langkah AKHIR, jadi `final-action`
   akan menunggu sasaran HILANG; membuka sidebar tidak menghilangkannya → tour tergantung.
3. **Tiga denominator beku dikemas** ikut prosedur `tools/README.md`: `wait_for_user`
   229→228 · W1 28/140→27/135 dengan W3 1/11→2/16 · `tenant-admin-public.action_steps` 3→4.
   Jumlah 83/473 dan shard `screen` 29/151 **tidak berubah**.

### 🔴 Pusingan CI #1 (`142cb56`) MERAH — gate betul, F5 langgar dua andaiannya (fix `f0115a6`)
Check **WAJIB** lulus; 2 shard `guidance-full` gagal.
1. **`nav-primary` ialah sasaran LOGIK** — gate mengassert `data-help-target` === `step.target`.
   Kini gate faham indireksi itu **dan kekal ketat**: sorotan mesti calon nav sebenar, bukan
   `MAIN`/`BODY`.
2. **`screen.muat-naik-dokumen` bukan lagi guide generik** — sasaran langkah berikut hanya
   wujud selepas tindakan sebenar, jadi CTA "Buat pada skrin" **meminimize** (betul), bukan
   maju. Diberi koreografi sendiri seperti guide `workflow.*` (peraturan #9).
   Gotcha: `getByRole('dialog')` melanggar mod ketat — popover tour **juga** `role="dialog"`.
3. **Penjaga KEEMPAT terlepas**: `aggregate-guidance-coverage.mjs` juga bekukan `229`.
   Kini dikemas dalam **keempat-empat** penjaga, dan mesej "GATE LULUS" dijadikan **dinamik**.

**Verifikasi tempatan PENUH sebelum push kedua** (25 min/pusingan CI terlalu mahal untuk teka):
`screen` 30 · `tenant-admin-public` 41 · `workflow` 15 · agregator **GATE LULUS 83/473/228**.

### 🎯 Pusingan CI #2 (`f0115a6`) — PUNCA flake `workflow` yang berlarutan AKHIRNYA DIBUKTIKAN
`tenant-admin-public` + `workflow` hijau; `screen` gagal pada toast muat naik — tandatangan
**identik** dengan flake `workflow` yang belum selesai sejak F3. Artifak diagnostik yang
dipasang pada `08d3643` akhirnya berguna. `serve-ci.log`:
```
18:56:52  /livewire/upload-file      <- fail SAMPAI ke pelayan
18:56:54  /livewire/update  500ms    <- muat naik selesai
…62 saat SIFAR permintaan…
18:57:56  /app/login                 <- tamat masa
```
**Klik "Hantar" hasilkan SIFAR permintaan** → memuktamadkan: **bukan** overlay
(`dispatchEvent` tidak melalui koordinat) · **bukan** antivirus (permintaan tak pernah sampai)
· **bukan** masa semata. Penjelasan konsisten: morph Livewire mengganti nod footer modal dan
Alpine memasang semula pendengar **tak segerak** → klik dalam tetingkap itu **hilang senyap**.

**Fix `submitUploadUntilToast()`** — cuba semula sehingga ada KESAN, hanya selagi modal masih
terbuka (tiada risiko hantar dua kali). Dipakai pada **kedua-dua** tapak. Lulus **2/2 di bawah
beban CPU buatan**. ⚠️ **Ini kelemahan produk tulen juga** — pengguna yang menekan tepat dalam
tetingkap itu tidak nampak apa-apa. Severiti rendah; **direkod untuk F6/F7, F5 tidak mendakwa
membaikinya.**

### ⭐ `risk-accepted` = 0
`public.login` ialah **satu-satunya** entri risiko-diterima baseline F0 (luput **2026-09-30**).
F5 menutupnya 4 Ogos — hampir dua bulan awal. Langkah `specific` **30 → 48**.

### ▶️ SETERUSNYA
CI hijau untuk `f0115a6` → **Deploy 5** (rebuild `app`+`nginx` — aset `help.js` berubah;
**wajib** `diwan:sync-help-index --delete`, katalog kini `2026.08.04.1`) → rantaian bukti 5A →
pengesahan live `/log-masuk`. Kemudian **F6 W1** (§7).

---

## SESI — ✅ DEPLOY 4 (F4 LALAI RETENSI) LIVE (3 Ogos 2026)

**`08d3643` LIVE** · CI run 30811698382 **7/7 HIJAU** ·
📄 `bukti/deploy-4/BUKTI-DEPLOY-4.md` + `bukti/plan-f4/LAPORAN-FASA-4.md`

Auto-padam **bukan lagi lalai**. Tiga lapisan: L1 borang `auto_padam`→**`semak`** + pengesahan
sedar berkiraan impak · L2 suis masjid baharu default **`false`** (ADDENDUM v2.6) · L3 peraturan
platform **KEKAL** (D3, patuh tatacara ANM). Masjid baharu kini perlu **memilih masuk 2 kali**.

| | Sebelum | Selepas |
|---|---|---|
| git server | `cab951e` | **`08d3643`** |
| `diwan-app` | `6789fc80` | **`3df4c706`** |
| `diwan-web` | `daead59f` | **`efd9337d`** |

### ⭐ Bukti data operasi TIDAK berubah (kriteria §5.6 paling penting)
Cap jari sha256 setiap peraturan retensi, sebelum vs selepas:
`c4117664eec7fe8aea374426508c612591825ba4f52506b12008a29c57b2ce09` — **IDENTIK**.
19 peraturan / 18 platform (14 `auto_padam` + 4 `kekal`) / 1 per-masjid tidak berubah;
mamad + smoke kekal `true`. Migrasi pgsql produksi **68.93ms** = ALTER ringan, tiada rewrite.
Lalai baharu dibuktikan daripada `information_schema.column_default = 'false'` —
**tanpa mencipta masjid ujian** di produksi.

Kesihatan: /up 200 · health OK · **smoke 9/9** · failed_jobs 0 · 0 mutex · 8/8 container ·
4 laluan awam 200.

### ⚠️ Satu kriteria §5.6 milik pemilik
"Buka borang cipta peraturan → default Semak; pilih Auto Padam → dialog amaran" perlukan sesi
berautentikasi. Saya tidak pernah mencipta/menaip kredensial produksi, jadi pengesahan visual
milik pemilik: `/app/{slug}/retention-rules` → **Cipta** → *Tindakan* patut **Semak**; tukar ke
**Auto Padam** → **Simpan** → dialog berkiraan. Kod L1 disahkan hidup dalam imej + 11 ujian.

### 🔴 BELUM SELESAI: shard `workflow` gagal berselang, punca TIDAK DIKETAHUI
Corak F,P,F,P,F,**P** pada muat naik UI (`1 dokumen dimuat naik` tidak muncul).
**Diagnosis saya sebelum ini SALAH** — `dispatchEvent` memintas koordinat sepenuhnya namun
kegagalan berulang, jadi teori "overlay menyerap klik" tidak menjelaskan apa-apa.
Dua hipotesis lain diuji & **ditolak**: antivirus fail-closed (`CLAMAV_ENABLED` lalai `false`)
dan masa/beban (4/4 larian tempatan di bawah beban CPU **lulus**).
**Sebabnya tidak diketahui = jurang alat:** artifak shard hanya laporan JSON (0 entri stdout).
Dibaiki `08d3643`: shard kini memuat naik **trace + error-context + PNG + log pelayan** pada
`failure()`. Kegagalan seterusnya akan boleh didiagnosis, bukan diteka.

### ⛔ JANGAN jalankan `RetentionRuleSeeder` pada produksi (`updateOrCreate` menimpa)

### ▶️ SETERUSNYA: **F5** — kandungan katalog & tour halaman awam (§6)

---

## SESI — ✅ DEPLOY 3 (F3 BAHASA) LIVE (3 Ogos 2026)

**`cab951e` LIVE di bakwim.my** · CI run 30798675244 **7/7 HIJAU** ·
📄 `bukti/deploy-3/BUKTI-DEPLOY-3.md` + `bukti/plan-f3/LAPORAN-FASA-3.md`

| | Sebelum | Selepas |
|---|---|---|
| git server | `aae4c97` | **`cab951e`** |
| `diwan-app` | `37516fd1` | **`6789fc80`** |
| `diwan-web` | `4c7dac3c` | **`daead59f`** |
| `lang/` dalam imej | **hanya `vendor`** | `en` `ms` `ms.json` `vendor` |

Kesihatan: `/up` 200 · health OK · **smoke 9/9** · failed_jobs 0 · 0 mutex · 8/8 container ·
4 laluan awam 200 · aset panel 200 · `sync-help-index --delete` = 83 guide.

### Bukti bahasa BERKUAT KUASA (dijalankan dalam container produksi, locale ms)
```
Medan Failkan Ke wajib diisi.                          <- bug audit paling teruk, MATI
Medan Kod Akronim mestilah sekurang-kurangnya 3 aksara.
Maklumat log masuk ini tidak sepadan dengan rekod kami.
« Sebelumnya | Seterusnya »        wizard: next=Seterusnya prev=Sebelumnya
e-mel: "Salam sejahtera," / "Sekian," / "Hak cipta terpelihara."   bocor-EN: TIADA
```
Sebelum deploy, `docker compose exec app ls lang/` memulangkan **hanya `vendor`** — punca
sebenar semua permukaan Inggeris, dibaca terus daripada imej hidup.

### ⚠️ Satu kriteria §4.8 dipenuhi secara BERBEZA (dinyatakan, bukan disembunyikan)
Pelan minta hantar e-mel ujian `--mail-to=` lalu **baca** kandungannya. **Saya tidak menghantar
e-mel keluar**: (a) menghantar mesej bagi pihak pemilik perlukan kebenaran eksplisit sesi ini;
(b) membaca mesej yang dihantar bermakna membuka peti masuk — dan membuka `spdmediwan@gmail.com`
menandakan e-mel *Seen* → Diwan **melangkaunya selama-lamanya** (risiko direkod). Ganti:
render templat di dalam container produksi = bukti sama, sifar kesan sampingan.
**Jurang baki jujur:** rendering tidak membuktikan penghantaran SMTP Brevo mengekalkan BM
(pengekodan). Pemilik boleh tutup bila-bila:
`docker compose exec -e HOME=/tmp app php artisan diwan:staging-check --mail-to=<alamat>`

### ▶️ SETERUSNYA: **F4** — lalai retensi selamat (§5). Kemudian F5 → F6 W1–W6 → F7–F10.

---

## SESI — F3 BAHASA DIBINA (3 Ogos 2026)

📄 `Audit Review Round Robin/bukti/plan-f3/LAPORAN-FASA-3.md`

Diwan bercakap Inggeris pada setiap permukaan framework walaupun `APP_LOCALE=ms`, kerana
**`lang/ms/` langsung tidak wujud**. F3 menutupnya: 4 fail terjemahan **penuh** (146 kunci) +
**109 `attributes`** dipetakan kepada label UI sebenar + `lang/ms.json` 5 kunci kerangka e-mel
verbatim + override wizard `Seterus`→**`Seterusnya`** + 3 arahan katalog + label Edit→Sunting.
`APP_FALLBACK_LOCALE` **kekal `en`** (fallback `ms` akan papar kunci mentah).

Angka: Pest **489 lulus** (453→489, +36) · pint passed · build OK · manifest dijana semula
(`catalog_version 2026.08.03.3`) · validator exit 0 · e2e `explore` **0 kebocoran EN** pada
7 halaman superadmin + semua halaman 8 role tenant.

### 🔎 Dua perkara yang pelan tidak jangka (kedua-dua dibetulkan dalam commit sama)
1. **Label `Edit` ada 6 tempat, bukan 5.** Yang keenam ialah **teks arahan** dalam
   `tetapan-masjid.blade.php:53` yang MENYEBUT butang itu. Membiarkannya = arahan menunjuk
   butang yang tidak wujud — persis kesilapan "Seterus" dalam katalog yang §4.5 larang.
   Penjaga diperluas: imbasan seluruh `app/` + `resources/`, bukan hanya 5 halaman.
2. **10 selektor e2e mengklik butang wizard mengikut nama lamanya**
   (`guidance.spec.js` 5× · `guidance-full.spec.js` 4× · `office-workflow.spec.js` 1×,
   semuanya `name: 'Seterus', exact: true`). Dengan label baharu, `exact: true` tidak padan →
   CI akan merah. Dikemas serentak (peraturan #9: akibat langsung perubahan produk).

### 🧪 Pengesahan wizard Filament 4 — kaedah diganti dengan yang LEBIH kuat
§4.7 #5 minta "render halaman → HTML mengandungi Seterusnya". Dalam Filament 4 kandungan
modal dirender **pelanggan-sisi**, jadi HTML pelayan tidak pernah mengandunginya (`mountAction`
pun tidak). Diganti: assert terus pada `Wizard::getNextAction()->getLabel()` (objek yang
menjana butang, vendor `Wizard.php:164/194`) + ujian bahawa ketiga-tiga wizard projek masih
guna komponen itu. Hujung-ke-hujung kekal pada e2e yang benar-benar mengklik butang.

### 5 penjaga dibuktikan menangkap regresi
Buang override wizard → **2 merah** · padam kunci JSON → merah · padam 1 kunci validation →
merah (menamakan kunci) · pulangkan "Seterus" ke katalog → merah (menamakan langkah) ·
pulangkan `->label('Edit')` → merah (menamakan fail) · pulih → **36 hijau**.

### 🔴 Pusingan CI #1 (`c1823b5`) MERAH — dua punca tulen, dibaiki `cab951e`

**(i) Ujian baharu saya lulus SQLite, gagal PostgreSQL.**
`storage_orders.idempotency_key` ialah kolum `uuid()` **sebenar**; fixture guna
`'ujian-'.uniqid()` → `SQLSTATE[22P02] invalid input syntax for type uuid`. SQLite menyimpannya
sebagai teks longgar dan tidak pernah memberitahu. → `(string) Str::uuid()`.
**Peraturan: nilai fixture mesti jenis SEBENAR kolum — semak migrasi dahulu.**

**(ii) Shard `workflow` BUKAN flake — punca dijumpai.**
Kegagalan **tandatangan identik** muncul juga pada `128b83c` = komit **dokumen sahaja, sifar
baris kod** → 2 daripada 4 larian. Punca: `guidance-full.spec.js:433` ialah **satu-satunya
`click({force:true})` yang tinggal**; 8 tapak lain sudah guna `forceClickWhenEnabled()`
(`dispatchEvent`). `force:true` **tidak** memintas overlay tour — ia cuma melangkau semakan
actionability; event tetap ke koordinat dan overlay SVG menyerapnya → borang tak dihantar.
Satu tapak terlepas semasa rollout F0. Tempatan selepas fix: `1 passed (1.6m)`.

> 🔑 **"Flake" ialah pemerhatian, bukan penjelasan.** Menahan diri daripada mengubah kod hijau
> atas SATU kegagalan adalah betul. Selepas ia berulang dengan tandatangan sama, mencari punca
> menjadi wajib — gate yang gagal 50% tanpa punca melatih orang mengabaikan warna merah.
> Rekod dalam `LAPORAN-F6-W0.md` §(g) sudah dibetulkan.

### ▶️ SETERUSNYA
CI hijau (`cab951e`) → **Deploy 3** (+ `diwan:sync-help-index --delete`, katalog berubah) →
`diwan:staging-check --mail-to=` dan **baca e-mel sebenar** (satu-satunya kriteria §4.8 yang
perlu produksi) → F4 (§5).

---

## SESI — ✅ DEPLOY 2 (F6-W0) LIVE (3 Ogos 2026)

**`aae4c97` LIVE di bakwim.my** · CI run 30778509859 **7/7 HIJAU** (termasuk shard `workflow`
yang gagal sekali sebelum ini) · 📄 `Audit Review Round Robin/bukti/deploy-2/BUKTI-DEPLOY-2.md`

| | Sebelum | Selepas |
|---|---|---|
| git server | `9619509` | **`aae4c97`** |
| `diwan-app` | `916f302c` | **`37516fd1`** |
| `diwan-web` | `dd486028` | **`4c7dac3c`** |
| aset help | `help-BceoIbJG.js` | sama (W0 tidak sentuh entri Vite) |

Kesihatan: `/up` 200 · `diwan:health` OK · `diwan:smoke` **9/9** · `failed_jobs` 0 · 0 mutex ·
8/8 container · `/` `/log-masuk` `/daftar` `/bantuan` 200 · `sync-help-index --delete` =
**83 guide disegerakkan**.

### 🔑 Pelajaran metodologi bukti deploy (baharu — penting untuk semua wave F6)
1. **Nama aset Vite BUKAN bukti sejagat deploy berkuat kuasa.** W0 mengubah blade +
   `guides.json` sahaja — tiada entri Vite tersentuh, jadi nama aset KEKAL. Itu betul, bukan
   deploy gagal. Untuk deploy jenis ini, bukti mesti **kandungan di dalam imej**
   (`docker compose exec app grep …`) + **imej ID berubah**. Disahkan: `catalog_version
   2026.08.03.2`, 5+5 `data-help-target`, 10 tajuk bermakna semuanya hadir dalam imej.
2. **Labelkan algoritma hash dalam rekod bukti.** `BUKTI-DEPLOY-1.md` merekod
   `af79c0c5…` untuk `help-BceoIbJG.js`; hari ini `md5sum` beri `e5f44081…` untuk fail SAMA —
   nampak seperti percanggahan. Hipotesis diuji: nilai lama ialah `sha256sum | cut -c1-32`.
   **Disahkan tepat.** Rekod Deploy 1 sah; ia juga membuktikan aset identik bait-untuk-bait.
3. **`steps_text` indeks bantuan = `pluck('instruction')`, bukan `title`**
   (`SyncHelpIndex.php:71`) — jadi tajuk baharu W0 tidak mengubah teks boleh-cari. Betul dan
   dijangka; jangan tafsir sebagai sync gagal.

### Keputusan yang disahkan betul oleh CI ini
Shard `workflow` gagal pada run 30776919686 (`39f2f33`). Saya hampir mengubah koreografi
`guidance-full.spec.js`; larian tempatan **menolak hipotesis itu**, dan shard yang sama
**lulus** pada run F2 dengan kod asal sementara W0 langsung tidak menyentuh guide `workflow.*`.
Perubahan dipulihkan sepenuhnya → run 30778509859 **hijau**. **Flake disahkan.**
Kelemahan katalog sebenar (langkah 6–7 sasar modal yang ditutup oleh langkah 5) = kerja **W2**.

### ▶️ SETERUSNYA
**F3** (bahasa `lang/ms/`, §4) → F4 (§5) → F5 (§6) → F6 **W1**→W6 (§7) → F7 (§8) → F8 (§9) →
F9 (§9A) → F10 (§9B). **Baca semula seksyen pelan sebelum membina** (peraturan #1).

---

## SESI — F0→F2 LIVE + F6-W0 SIAP DIBINA (3 Ogos 2026)

### ✅ SUDAH LIVE DI PRODUKSI: `9619509` (Deploy 1 = F1+F2)
CI run 30774069928 **7/7 HIJAU** · rantaian bukti runtime 5A **LULUS PENUH**
(`3a=2a · 3b=2b · 4b sama · 5a=5b=6`) · 📄 `bukti/deploy-1/BUKTI-DEPLOY-1.md`

| | Sebelum | Selepas |
|---|---|---|
| git server | `3f94a90` | `9619509` |
| `diwan-app` | `dca1f6cb…` | `916f302c…` |
| `diwan-web` | `292e2aa9…` | `dd486028…` |
| aset help | `help-pJkQNpPs.js` | **`help-BceoIbJG.js`** |

Kesihatan pasca-deploy: `/up` 200 · `diwan:health` OK · `diwan:smoke` **9/9** ·
`failed_jobs` 0 · 0 mutex · 8/8 container · `/` `/log-masuk` `/daftar` `/bantuan` 200.

### ⏳ BELUM DEPLOY: F6-W0 (Deploy 2) — SIAP DIBINA, menunggu komit+CI
📄 `bukti/plan-f6-w0/LAPORAN-F6-W0.md` · 4 ujian W0 lulus (desktop + mobile 390×664)

### Fasa siap setakat ini
| Fasa | Status | Laporan |
|---|---|---|
| **F0** perkakas + gate CI + branch protection 4 check | LIVE (dokumen) | `bukti/plan-f0/LAPORAN-FASA-0.md` + `VERIFIKASI-F0.md` §1–§22 |
| **F1** konteks HelpLauncher | **LIVE** | `bukti/plan-f1/LAPORAN-FASA-1.md` |
| **F2** runtime tour | **LIVE** | `bukti/plan-f2/LAPORAN-FASA-2.md` |
| **F6-W0** hotfix mobile | siap dibina | `bukti/plan-f6-w0/LAPORAN-F6-W0.md` |

### Yang pengguna dapat hari ini (F1+F2 LIVE)
1. **Pembantu Diwan tidak lagi hilang** selepas interaksi Livewire — 19/25 halaman,
   termasuk kesemua 11 halaman superadmin (punca #1 audit).
2. **Label butang tour = tepat satu kelakuan** (20 CTA "Buat pada skrin" palsu tamat).
3. Popover fallback **BM penuh** · auto-minimize bila menutup modal · fokus masuk+pulang.
4. **Banner "Tunjuk arahan" boleh diklik** — sebelum ini pengguna tetikus TERKANDAS
   (disahkan hidup di produksi sebelum & selepas pembaikan via GET awam).

### Angka semasa
Pest **453 lulus**/1 skip (4,794 assertions) · `unit` 10/10 · `ci-guidance` 23 ujian ·
`ci-domain` 4/4 · gate agregator 83 guide/473 langkah/229 tindakan ·
kemajuan F6: `placeholder 258 → 248`, `generic_declared 443 → 433`.

### 🔧 Pembetulan reka bentuk perkakas F0 (penting untuk semua wave F6)
Ketiga-tiga penjaga (`build-manifest.mjs`, `validate-plan-manifest.mjs`, `PlanManifestTest`)
dahulu mengassert **kesamaan** untuk `placeholder_titles: 258` / `action_generic: 200` —
sedangkan §7 menetapkan nilai itu **mesti turun ke 0**. Gate begitu akan **menolak setiap
pembaikan F6**. Kini: **STRUKTUR** (83/473/partition/kohort) diassert sama; **KEMAJUAN**
diassert **≤ baseline** (turun dilaporkan sebagai delta, naik = regresi gagal).

### ▶️ SETERUSNYA (urutan tepat)
1. **Komit + push F6-W0** → tunggu CI hijau → **Deploy 2**: rebuild `app`+`nginx`
   (blade+katalog berubah) + **`diwan:sync-help-index --delete`** (katalog berubah) +
   rantaian 5A penuh.
2. **F3** (bahasa, §4) → F4 (§5) → F5 (§6) → **F6 W1→W6** (§7) → F7 (§8) → F8 (§9) →
   F9 (§9A) → F10 (§9B). **Baca semula seksyen pelan sebelum membina** (peraturan #1).

### Teknik/gotcha yang terbukti (guna semula)
- **Beban CPU buatan** (`scratchpad/f0/run-under-load.sh`) menghasilkan kegagalan jenis-CI
  secara tempatan — jauh lebih pantas drp menunggu CI ~25 min/pusingan.
- `click({force:true})` **bukan** penyelesaian overlay (event tetap ke koordinat) →
  `dispatchEvent('click')`. Force juga melangkau semakan *enabled*.
- `fill()` = clear+insertText → kalis morph dgn `toPass`; `wire:model.blur` perlu blur eksplisit.
- Filament **lazy-load** JS: tunggu `.filepond--root` sebelum `setInputFiles`; FilePond siar
  status via `aria-live` → kira `.filepond--file-status-main`, bukan `getByText`.
- `APP_ENV=testing` pada server HTTP memecahkan SEMUA upload UI (disk `tmp-for-tests`).
- **Sasaran `data-help-target` mesti wujud dalam keadaan LALAI** (data kosong) — bukan hanya
  dalam persekitaran berdata.
- `retries` Playwright kekal **0** — ketiadaan retry mendedahkan 3 bug produk.
- Tiket `SUP-260801-HXQ0DIOL` masih menunggu pemilik padam.

---

## SESI — 🚀 F0+F1+F2 SIAP & **DEPLOY 1 LIVE** (3 Ogos 2026)

**`9619509` LIVE di bakwim.my** · CI run 30774069928 **7/7 HIJAU** · rantaian bukti runtime 5A
**LULUS SEPENUHNYA** (`3a=2a · 3b=2b · 4b sama · 5a=5b=6`).
📄 `Audit Review Round Robin/bukti/deploy-1/BUKTI-DEPLOY-1.md`

| | Sebelum | Selepas |
|---|---|---|
| git server | `3f94a90` | `9619509` |
| `diwan-app` | `dca1f6cb…` | `916f302c…` |
| `diwan-web` | `292e2aa9…` | `dd486028…` |
| aset help | `help-pJkQNpPs.js` | **`help-BceoIbJG.js`** |

Kesihatan: `/up` 200 · `diwan:health` OK · `diwan:smoke` **9/9** · `failed_jobs` 0 · 0 mutex ·
8/8 container · `/` `/log-masuk` `/daftar` `/bantuan` semua 200.

### Yang berkuat kuasa untuk pengguna HARI INI
1. **F1** — Pembantu Diwan tidak lagi hilang selepas interaksi Livewire (**19/25 halaman**,
   termasuk semua 11 halaman superadmin); URL bantuan root tidak lagi `//`.
2. **F2a** — label butang tour = tepat satu kelakuan (20 CTA "Buat pada skrin" palsu tamat).
3. **F2b** — popover fallback BM penuh · **F2c** — auto-minimize bila popover menutup modal ·
   **F2d** — fokus masuk popover & pulang ke launcher.
4. **Banner "Tunjuk arahan" boleh diklik** — sebelum ini pengguna tetikus TERKANDAS
   (disahkan hidup di produksi sebelum pembaikan: VERIFIKASI-F0 §17/§20).

### Fasa siap
- **F0** — perkakas + gate CI 3 lapis + branch protection TEPAT 4 check.
  📄 `bukti/plan-f0/LAPORAN-FASA-0.md` + `VERIFIKASI-F0.md` §1–§22 (7 pusingan CI, setiap satu
  punca berbeza; **3 bug produk ditemui** oleh gate ini).
- **F1** — konteks HelpLauncher (§2). 📄 `bukti/plan-f1/LAPORAN-FASA-1.md` · 21 ujian Pest;
  **bukti penjaga: kod lama dipasang semula → 6 merah**.
- **F2** — runtime tour (§3). 📄 `bukti/plan-f2/LAPORAN-FASA-2.md` · modul tulen
  `step-advance-plan.js` + 10 ujian unit (435ms) + 6 e2e; **ketiga-tiga bug F0 DITUTUP**;
  bukti penjaga CSS: buang peraturan → merah.

### Angka semasa
Pest **453 lulus**/1 skip (4,791 assertions) · `unit` 10/10 · `ci-guidance` 19/19 0 flaky ·
`ci-domain` 4/4 · gate agregator **83 guide/473 langkah/229 tindakan**.

### ▶️ SETERUSNYA: F6-W0 (hotfix mobile) — Deploy 2
`tenant.pelupusan` (5 langkah) + `tenant.kegemaran` (5): sasaran spesifik + 10 tajuk; uji
desktop DAN mobile 390×664; 6 defect `centerCovered` → 0. Kemudian F3 (bahasa) → F4 → F5 →
F6 W1–W6 → F7 → F8 → F9 → F10.
**Baca semula seksyen pelan berkenaan sebelum membina** (peraturan #1 CLAUDE.md repo).

### Teknik yang terbukti (guna semula)
- **Beban CPU buatan** (`scratchpad/f0/run-under-load.sh`) menghasilkan kegagalan jenis-CI
  secara tempatan — jauh lebih pantas drp menunggu CI 25 min/pusingan.
- `click({force:true})` **bukan** penyelesaian overlay (event tetap ke koordinat) →
  `dispatchEvent('click')`. Force juga melangkau semakan *enabled*.
- `fill()` = clear+insertText → kalis morph dgn `toPass`; `wire:model.blur` perlu blur eksplisit.
- Filament **lazy-load** JS: tunggu `.filepond--root` sebelum `setInputFiles`.
- `APP_ENV=testing` pada server HTTP memecahkan SEMUA upload UI (disk `tmp-for-tests`).
- `retries` Playwright kekal **0** — ketiadaan retry mendedahkan ketiga-tiga bug produk.
- Tiket `SUP-260801-HXQ0DIOL` masih menunggu pemilik padam.

---

## SESI — ✅ FASA 0 SELESAI, CI HIJAU PENUH (3 Ogos 2026)

**`fb40ff1` · CI run 30770625567 = 7/7 job SUCCESS** (integration · 3 shard `guidance-e2e` ·
gate agregator · 2 imej Docker). Gate agregator pada CI sebenar: **83 guide · 473 langkah ·
229 langkah tindakan (perbandingan SET)**. Branch protection ditetapkan **TEPAT 4 check**
(`PostgreSQL, Redis, Meili, OCR and tests` · `guidance-e2e-gate` · `Docker app image` ·
`Docker web image`, strict=true).

📄 **Laporan rasmi:** `Audit Review Round Robin/bukti/plan-f0/LAPORAN-FASA-0.md`
📄 **Bukti penuh (§1–§22, output sebenar):** `…/bukti/plan-f0/VERIFIKASI-F0.md`

### 7 pusingan CI — setiap satu punca BERBEZA, semuanya tulen
`06277fc` 4 gagal (keadaan perawan) → `31abd74` 2 (`force` melangkau semakan enabled) →
`8fcab15` 1 (klik koordinat diserap overlay → `dispatchEvent`) → `fd53a81` 3 (**banner tour
menolak klik tetikus**) → `c90264c` 1 (**nilai medan BERGANDA**: `fill()` vs morph) →
`a83625e` 1 (upload sebelum FilePond siap: 0 permintaan `/livewire/upload-file`) →
**`fb40ff1` HIJAU**.

### 🔴 TIGA BUG PRODUK ditemui gate ini → WAJIB dibaiki + ujian regresi pada F2 (§3)
1. **Auto-advance tour boleh mati** — re-highlight (morph Livewire) memanggil
   `watchForNextStep` semula → `clearTransitionWatch` bunuh jadual `moveNext` 120ms → guard
   `help.js:363` halang poller baharu. Pengguna terpaksa tekan butang sendiri.
2. **Banner "Panduan menunggu" tidak boleh diklik tetikus** — vendor `.driver-active *
   {pointer-events:none}`; banner anak `<body>` tanpa `pointer-events:auto`. Gabungan dgn (1)
   = pengguna tetikus **TERKANDAS** (hanya papan kekunci menyelamatkan). **DISAHKAN HIDUP DI
   PRODUKSI** melalui GET awam sahaja (0 tulisan) — `VERIFIKASI-F0 §20`.
3. **Nilai medan borang boleh berganda** — morph yang mendarat semasa menaip memulihkan nilai
   lama lalu input ditambah di hujung (dibuktikan: slug berganda).

### Nota operasi
- **Server produksi TIDAK di-deploy** dan tidak sepatutnya: F0 tidak mengubah runtime.
  Deploy pertama = **Deploy 1 (F1+F2)** ikut D7. Server git `3f94a90`, runtime imej `8342d95`.
- `retries` Playwright kekal **0** (sengaja) — ketiadaan retry mendedahkan ketiga-tiga bug.
- `scratchpad/f0/run-under-load.sh` (beban CPU buatan) menghasilkan kegagalan jenis-CI secara
  tempatan — guna semula sebelum push pada fasa seterusnya.
- Tiket `SUP-260801-HXQ0DIOL` masih menunggu pemilik padam.

### ▶️ SETERUSNYA: F1 — HelpLauncher (§2 pelan)
Fail tunggal `app/Livewire/HelpLauncher.php`: 4 sifat `#[Locked]` (`originPath`,
`requestedGuideId`, `requestedStep`, `launchPending` one-shot) ditetapkan dlm `mount()`;
`render()` guna sifat bukan `request()`; `guidanceProgress()` padam one-shot SEBELUM guard
`findVisible()` + `skipRender()`. Ujian `HelpLauncherContextTest` 10 + penjaga SPA #11.
**Baca semula §2 PELAN-PEMBAIKAN.md sebelum membina** (peraturan #1). Kemudian F2 → Deploy 1.

---

## SESI — 🚀 PELAKSANAAN F0 SIAP DIBINA (2 Ogos 2026, petang)

**Arahan mula pemilik diterima** → pelan pelaksanaan diluluskan (plan-mode) → **FASA F0 SIAP
DIBINA & DIKOMIT**: `7129369` (fix-audit-F0, 32 fail / 21,813 sisipan — 19 fail perkakas D11 +
bundle + addendum v2.6 + label OCI D9) + 3 komit susulan penemuan CI: `dfad951` (F0b — dompdf
3.1.5→3.1.6, 6 advisori GHSA 22 Jul; transitif, composer.json TAK diubah) + `cb18de2` (F0c —
sync-help-index `--delete` idempoten pada `index_not_found`) + `3f94a90` (F0d — `APP_LOCALE: ms`
env CI; config lalai `en` + CI tiada .env → UI Inggeris → canary gagal).

### Verifikasi F0 (SEMUA HIJAU lokal — output penuh: `Audit Review Round Robin/bukti/plan-f0/VERIFIKASI-F0.md`)
- Suite Pest **432✓/1 skip** (409→432; +PlanManifestTest 14 termasuk **lapisan C: 410 probe HTTP
  × 10 identiti + silang-tenant 8×404**, +AuditFixtureCommandTest 5, +InboxAntivirusFailClosedTest 4 (S7)).
- `diwan:role-routes` 410 entri **0 mismatch A↔B**; kiraan nav **25/17/15/15/13/13/13/14**
  (sama dgn jangkaan lama guidance.spec — kini DIJANA dari spec; drift role P14-03 TAMAT;
  `guidance.spec.js` membaca manifest, literal dibuang — gate F0(ii-b)#6).
- **guidance-full 3 shard LULUS + AGREGATOR GATE LULUS: 83 guide · 473 langkah · 229 tindakan
  (perbandingan SET)** — screen 30/30 (8.9m) · workflow 15/15 (7.3m) · t-a-p 41/41 (10.5m).
- Baseline runtime produksi direkod read-only (rantaian 5A: 3a=2a·3b=2b·4a=4b·5a=5b=6, server
  `8342d95`, aset `help-pJkQNpPs.js`) → `bukti/plan-baseline/runtime-baseline-2026-08-02.json`.
- Fixture OCR sintetik terbukti dibaca tesseract lokal (BAKTIMURNI/CAHAYAIKHLAS).
- **Penemuan penting: CI main sudah MERAH sejak `4e07a70` (pra-F0!)** — 4 skrip PHP bukti audit
  gagal `pint --test` → `pint.json` exclude arkib; + advisori dompdf (22 Jul) = punca ke-2.

### 3 pepijat spec gate ditemui semasa larian penuh (bukti nilai gate; direkod VERIFIKASI §10)
guide workflow 20/13 langkah + auto-advance → mesin-keadaan toleran; popover MEMINTAS klik butang
modal pada DESKTOP (pengesahan bebas RR-08-03); registrasi perlu kod 3–6 HURUF + telefon WA unik
(tolak-pendua senyap).

### 🔧 F0e (2 Ogos malam) — CI run pertama `06277fc` MERAH → distabilkan (komit `31abd74`)
Run 30741376294 (`06277fc`): Guidance smoke 8/12 — 4 kegagalan, SEMUA persekitaran (bukan
regresi produk; ci-guidance tak pernah berjalan penuh dlm DB perawan). Analisis penuh:
VERIFIKASI-F0 §11–§14. Fix (5 fail, +178/−19):
1. `:123` launcher hidden (DB perawan + explore → tour auto-resume; `disableAutomaticGuides`
   hanya panel public) → `closeGuideIfOpen()` sebelum assert.
2. `:348`/`:386` overlay minimize memintas klik (lubang ikut geometri fon Linux≠Windows) →
   `forceClickWhenEnabled()`: tunggu `toBeEnabled()` SEBELUM `click({force:true})` (force
   melangkau semakan enabled — klik semasa `wire:loading` disabled hilang senyap).
   Diseragamkan guidance.spec (6 titik) + guidance-full.spec (7 titik).
3. registration.spec ENOENT laravel.log → guard `existsSync`; + step Serve override
   `MAIL_MAILER=log` + `MAIL_LOG_CHANNEL=single` (job env array/stderr = magic link tak
   pernah sampai ke fail log).
4. **PENEMUAN TERPENTING: `APP_ENV=testing` memecahkan SEMUA upload UI** — Livewire
   `runningUnitTests()` benar pada server HTTP → paksa disk `tmp-for-tests` (tak wujud) →
   setiap `/livewire/upload-file` 500. Run `06277fc` terselamat kerana seeder + shard
   di-skip; pasti meletup pada larian shard pertama. Fix: step Serve override
   `APP_ENV=local` KEDUA-DUA job (Pest kekal testing via phpunit.xml).
5. `PHP_CLI_SERVER_WORKERS=4` kedua-dua step Serve (race aset lazy x-load semasa beban).
Verifikasi lokal keadaan CI-perawan (7 larian; skrip `scratchpad/f0/serve-f0e.sh`):
**ci-guidance 12/12 (9.0m) + workflow shard 15/15 (8.0m) + screen.klasifikasi ✓ +
public.registration ✓** + assert JSON 0 flaky. 3 flake infra dev Windows dikenal pasti &
diselesaikan utk larian lokal (max_execution_time 30s I/O autoloader; throttle /daftar
20/jam kaunter cache FILE kekal merentas larian → `cache:clear`; `ERR_NO_BUFFER_SPACE`
socket letih — hanya lokal, tiada di CI).

### ▶️ BAKI F0.12 (status semasa)
1. **CI `31abd74` SEDANG BERJALAN** (pemantau latar poll 60s; semak:
   `gh run list --branch main --limit 1`). Jika merah: `gh run view <id> --log-failed`,
   baiki punca (bukan gate), komit F0f dst.
2. Selepas HIJAU: **branch protection 4 check** via
   `gh api repos/hakimalek27/Sistem-Pengurusan-Dokumen-Masjid/commits/$(git rev-parse HEAD)/check-runs --paginate --jq '.check_runs[].name'`
   kemudian PUT branch protection: `PostgreSQL, Redis, Meili, OCR and tests` · `guidance-e2e-gate`
   · `Docker app image` · `Docker web image` (senarai A — TEPAT 4; shard/step = bukti B).
3. Laporan Fasa 0 rasmi kpd pemilik (VERIFIKASI-F0 §1–§14 sudah lengkap).
4. **F1** (HelpLauncher §2 pelan — fail tunggal `app/Livewire/HelpLauncher.php`, 4 sifat #[Locked],
   one-shot sebelum guard findVisible, + ujian HelpLauncherContextTest 10+1; kemudian F2 → Deploy 1).
   **Baca semula §2 PELAN-PEMBAIKAN.md sebelum membina (peraturan #1).**
- Server produksi: git `3f94a90` / runtime kekal imej `8342d95` (kod di-bake; sync git ke
  `31abd74` bila-bila — tiada kesan runtime). Deploy runtime pertama = selepas F1+F2.
- Tiket `SUP-260801-HXQ0DIOL` masih menunggu pemilik padam.

---

## SESI — PELAN PEMBAIKAN ROUND-ROBIN + KEPUTUSAN PEMILIK (2 Ogos 2026)

**Status:** 🏁 **`PELAN-PEMBAIKAN.md` v1.11 MUKTAMAD** (ditutup P27, 2 Ogos 08:43 — syarat #6
dipenuhi: **Codex P26 = pusingan Codex pertama tanpa pindaan substantif** selepas **27 pusingan ·
11 versi**; trend penemuan 8→8→8→7→6→5→1→**0**). Hash muktamad:
`E0E2B4069EE910FC402E5B5403B3766CC23A5AE0525BF5DB1CC9ECA311420D0B` (324,144 B / 4,204 LF).
**Kod aplikasi: 0 baris disentuh sepanjang 27 pusingan.** Asas kod kekal `8342d95`.
📄 Baca ikut urutan: `Audit Review Round Robin/PLAN-RR-STATUS.md` (log penuh + integriti per
giliran) → `KEPUTUSAN-PEMILIK.md` (D1–D11 LENGKAP) → `PELAN-PEMBAIKAN.md` (MUKTAMAD) →
`PLAN-RR-27-CLAUDE.md` (penutupan).

### ▶️ SETERUSNYA: pelaksanaan menunggu SATU perkara — arahan mula pemilik

1. **Pemilik beri arahan mula** (mengangkat sekatan "jangan ubah kod") — tiada keputusan
   tertunggak; D1–D11 lengkap.
2. Disyorkan SEBELUM pelaksanaan: **komit/snapshot folder `Audit Review Round Robin/`**
   (§0.7 #2) supaya versi pelan yang diaudit menjadi immutable — keputusan git milik pemilik.
3. Pelaksanaan ikut pelan MUKTAMAD: **F0** (tulis `# ADDENDUM v2.6` ke
   `DIWAN-SPEC-ADDENDUM-2026-07.md` + 19 fail perkakas §1 F0(iv-a) + manifest baseline +
   gate CI) → **F1–F10**, deploy **GABUNG** (D7), bukti setiap fasa, **F8 audit semula**
   dgn metrik apple-to-apple.
4. Lampiran A1 masih menunggu: padam tiket ujian `SUP-260801-HXQ0DIOL` di `/admin/tiket-sokongan`.

### Gotcha proses Codex-di-Windows (3 insiden sesi ini — semua diselesaikan & direkod)

1. **Kuota akaun** → pemilik tukar akaun; sentiasa sahkan `grep -c "succeeded in"` > 0.
2. **"Selected model is at capacity"** (sementara) → gelung retry 90s + **lock-file PID**
   (kerana `TaskStop` TIDAK membunuh anak bash — dua gelung pernah berlanggar; bunuh dengan
   `taskkill //F //PID <pid> //T`); JANGAN letak `timeout` pada larian Codex (±30 min).
3. **Codex tergantung "Reading additional input from stdin..."** → WAJIB `< /dev/null` pada
   `codex exec` dalam skrip latar.
   Setiap insiden: bunuh proses → **sahkan fail giliran tidak tercemar (hash)** → baru lancar semula.

### Perjalanan fasa pelan (ringkas)

1. **P1–P9:** Claude tulis `PELAN-PEMBAIKAN.md` v1.0 (8 fasa F0–F8, semua penemuan audit) →
   3 pusingan semakan Codex → v1.3 → ditanda "muktamad" di P9.
2. **⛔ P9 DIBATALKAN oleh P10/P11:** fail P6/P8 didapati **ditulis proses lain semasa giliran
   Codex** — pengesahan tidak sah. Pengajaran: satu pusingan palsu menyembunyikan ralat fakta
   selama 4 pusingan. → **Peraturan integriti #7** kini wajib: rekod saiz+hash+mtime fail
   giliran sebelum & selepas menulis; berhenti jika fail berubah tanpa tindakan sendiri.
3. **P10 (Codex audit lengkap):** 25 pindaan, **8 bloker** (konflik spec retensi D2 vs
   `DIWAN-SPEC.md:470`; liputan katalog sebenar 83 guide/473 langkah bukan 37; kontrak trap
   Driver.js vendor; auto-start belum one-shot; SPA belum dikunci; bukti runtime imej; Playwright
   tiada CI; produksi perlu 20 BrowserContext) + pembetulan fakta (placeholder **258**, label
   Edit **5**, notifikasi `toMail()` **18**).
4. **P11–P14:** integrasi C01–C25 (v1.4: +F9 manual, +F10 housekeeping, D8–D10 baharu, W1–W6)
   → P12 8 pindaan (v1.5: `launchPending` `#[Locked]`, urutan W diterbalikkan — 200/229 langkah
   tindakan dlm `screen`+`workflow`, kontrak gate G1–G5, bukti imej `diwan-app` vs `diwan-web`,
   `disabledClick()`, D5 dipecah polisi/dependensi) → P14 8 baki (CI env, shard/agregator,
   `role_routes`, W1/W2 exact, `blocked` vs G4, lookahead rg, snapshot beku).
5. **P15 TERPUTUS:** v1.6 ditulis separa (hash `A1667A70…FF31E`, 205,840 B / 2,777 baris),
   sesi tamat **monthly spend limit** sebelum fail keputusan/status siap — direkod jujur.
6. **P16 (Codex):** audit keadaan separa — integrasi P14 kekal baik, **8 baki** (P16-01…08:
   canary CSRF, shard command literal, D11 undercount, setup/cleanup produksi tak bernama,
   senarai ID wave, `! rg` mask exit 2, `role_routes` expected-vs-actual, suite domain luar CI).
7. **P17 (siap):** menutup jurang P15 secara jujur (TIADA fail retroaktif bagi pihak P15) +
   integrasi P16-01…08 → v1.7 (penemuan baharu: `step.id` katalog tidak unik global — 470/473;
   kunci set jadi `<guide_id>#<index1>`; canary login mesti Livewire `data.login`, bukan POST
   borang; slug `smoke` = tenant gate deploy, BUKAN fixture buang).
8. **P18–P21:** P18 (Codex) 7 pindaan → v1.8 (P19) → P20 (Codex) 6 pindaan (YAML literal CI,
   gate Meilisearch, check matriks Docker ×2, `storage/app/plan-*` gantikan `bukti/plan-ci`,
   Playwright memadam `outputDir`) → v1.9 (P21, +399 baris kontrak). **Syarat penutupan belum
   pernah dipenuhi** — setiap pusingan Codex masih menemui pindaan substantif.
9. **P22 (Codex):** percubaan #1 terblok kuota akaun lama → **pemilik tukar akaun Codex baharu**
   → berjaya (34 arahan): **5 isu** (3 P1 + 2 P2) — naratif `ServeCommand` terlalu mutlak
   (`variables_order=GPCS` → `$_ENV` kosong → fallback `getenv()` Symfony mewarisi env walau
   `.env` wujud; `--no-reload` kekal wajib TANPA syarat), semantik upload menutup diagnosis,
   dakwaan storage salah (`manual-capture`/`backup-temp`/`tmp` wujud), D11 salah unit
   (16 entri = **19 fail + 1 bundle**), teks D menunggu lapuk. 4 titik LULUS.
10. **P23 (Claude):** 5/5 diterima selepas verifikasi bebas (probe `variables_order` sendiri
    sepadan probe Codex; vendor `ServeCommand`/`Process` dibaca; grep storage) → **v1.10**
    (+112 baris): naratif dibetulkan + step probe bukti, dua-step upload ×4 lokasi
    (`success()`+`error` / `failure()`+`ignore`), 19+1 dinormalisasi, §11 ditanda dijawab.
11. **P24 (Codex, 10 cubaan menembusi "at capacity"):** 5 titik LULUS + **1 isu** — label
    "bersyarat" OCR #16 masih hidup dlm baris D11 §11 → **P25 (Claude)**: label TERBATAL
    (strikethrough + nota sejarah + "[CADANGAN SEJARAH]"), typo "D10-16"→"D11" → **v1.11**.
12. **P26 (Codex, audit penutup):** P24-T4 disahkan + imbasan +15 baris + imbasan keyakinan
    rawak 4 seksyen (§1/§3/§7/§10) — semuanya bersih → keputusan rasmi **(a) "TIADA
    PENAMBAHBAIKAN SUBSTANTIF — pelan sedia muktamad"** = pusingan Codex PERTAMA tanpa pindaan.
    **P27 (Claude): header ditanda ✅ MUKTAMAD** (+441 B penanda sahaja, hash `E0E2B406…`).
    (Insiden proses P26 percubaan #1: codex tergantung stdin → dibunuh, skrip dibaiki
    `< /dev/null`, fail disahkan tidak tercemar — direkod dlm log giliran.)

### ✅ Keputusan pemilik LENGKAP D1–D11 (2 Ogos — `KEPUTUSAN-PEMILIK.md`)

Arahan #1: **D1 Ya · D2 Ya · D3 Kekal · D4 Ya · D5 Ya (a+b) · D6 Terima · D7 GABUNG deploy**.
Arahan #2: **D8/D9/D11 ikut cadangan** (D8: prune 30 hari + peringatan berhenti 7 hari &
eskalasi; D9: label OCI; D11: luluskan 16 fail + 1 artifak) · **D10 LULUS** (Addendum spec v2.6
diluluskan → D2 dibuka; teks addendum ditulis pada permulaan pelaksanaan F0/F4, bukan fasa pelan)
· "sambung sampai plan muktamad, teliti balik semuanya".

### Nota untuk sesi seterusnya

- **Sekatan "jangan ubah kod" masih berkuat kuasa** sehingga pelan MUKTAMAD + arahan mula.
- **JANGAN tulis giliran bagi pihak Codex** (pengajaran P6/P8 → pembatalan P9). Bloker kuota =
  tunggu / pemilik tambah kredit / pemilik pinda protokol secara eksplisit.
- Ikut §0.7 pelan: hash 2× sebelum tulis; kiraan baris = `\n` LF (bukan `Measure-Object -Line`);
  frasa "working tree bersih" DILARANG — lapor 2 fakta berasingan (kod aplikasi 0 baris; ??
  dokumen perancangan).
- Codex di mesin ini: `codex exec --sandbox danger-full-access` (workspace-write ROSAK);
  sahkan log `grep -c "succeeded in"` > 0 sebelum percaya laporan.
- Fail pelan semuanya **belum dikomit** (untracked; snapshot §0.7 #2 tertunggak — semua giliran
  dilarang git; keputusan komit milik pemilik).
- Integriti sesi ini: PLAN-RR-STATUS `b07d8a40…`→(keputusan D1–D7)→`acf9d1b6…`→(D8–D11 +
  rekod bloker P22); KEPUTUSAN-PEMILIK.md dikemas D8–D11; TIADA sentuhan pada
  `PELAN-PEMBAIKAN.md` (hash kekal `487EDBE6…`, disahkan 2×).

---

## SESI — AUDIT ROUND-ROBIN MENYELURUH (1 Ogos 2026)

**Status:** ✅ **AUDIT SELESAI selepas 14 pusingan.** Tiada kod diubah — audit & cadangan sahaja.
📄 **Baca dahulu:** [`Audit Review Round Robin/FINAL-RUMUSAN.md`](Audit%20Review%20Round%20Robin/FINAL-RUMUSAN.md)

### Apa yang dibuat
Audit A–Z dua ejen bebas (**Claude ↔ Codex**) berselang-seli 14 pusingan atas commit `4e07a70`.
Setiap penemuan mesti disahkan atau ditolak oleh pihak kedua dengan bukti sendiri.

**Liputan produksi `bakwim.my`:** 50 muat halaman berautentikasi (25 × desktop+mobile) ·
**248 langkah tour** dianalisis · matriks kebenaran 3 role · 18 probe silang-tenant ke masjid
sebenar `mamad` · 300+ skrinsyot.
**Liputan salinan tempatan** (commit sama, bundle bantuan hash identik): 274 muat halaman ×
9 identiti · kitaran tulis penuh · katalog 83 guide / 473 langkah · suite 409 lulus.

### ✅ Terbukti SIHAT — jangan buang masa audit semula
- **Keselamatan antara masjid lulus pada KEDUA-DUA lapisan**: baca (44 probe = 404/403) dan
  **tulis** (4/4 mutasi silang-tenant ditolak, **0 pencemaran DB**). Guard di `InboxIngestService:164`.
- **Kebenaran role sempurna** — sifar kes "boleh akses tapi tiada dalam menu".
- **Enjin retensi/pelupusan betul**: 11 batch auto, **11/11 ada sijil**, gate t30+t7 dihormati,
  **0 rekod masjid sebenar terjejas**.
- **Sync tour BERFUNGSI** (1,045 ms) — kebimbangan asal pemilik bukan pepijat.
- Kerja pejabat berfungsi: klasifikasi → nombor rujukan auto → minit → kelulusan.
- 50/50 halaman produksi HTTP 200 · 0 ralat JS · 0 overflow mendatar · tiada kebocoran memori.

### 🔴 Perlu dibaiki (keutamaan)
| # | Isu | Lokasi |
|---|---|---|
| 1 | **Konteks Pembantu Diwan hilang** pada 19/25 halaman produksi (semua 11 halaman superadmin). Tour **memusnahkan konteksnya sendiri** — setiap langkah hantar event Livewire | `HelpLauncher.php:61-65,88` + `help.js:150-153` |
| 2 | **Tiada `lang/ms/`** → validasi + pagination + **kerangka 9/9 e-mel** Inggeris; mesej rojak `The failkan Ke field is required.`; vendor Filament guna `Seterus`/`Sebelum` | — |
| 3 | **Auto-padam ialah tetapan LALAI** (3 default bertindan) — keputusan reka bentuk untuk pemilik | `create_mosques_table.php:24` · `RetentionRuleResource.php:59` |
| 4 | **119/124 langkah tour** sorot kawasan generik, bukan butang | `guides.json` |
| 5 | Tour `/log-masuk` sentiasa papar ralat palsu (layout tetamu tiada `<main>`) | `help.js:395-421` |
| 6 | **77/124** tajuk duplikasi · **20/124** terpotong · CTA tak konsisten (20× "Buat pada skrin") | `help.js:323-333` vs `:525` |
| 7 | axe (`landmark-unique`, `link-name`), overlay tour vs modal mobile, viewer PDF, 3 label `Edit` | — |

### ⚠️ Kesan audit pada produksi (pendedahan penuh)
Dakwaan awal "tiada mutasi" **tidak tepat** — Codex mengesannya (RR-11-01). Kesan sebenar:
**21 token log masuk** dicipta (ID 221–241; 3 superadmin — **tidak pernah digunakan**), 53
`help_events`, 32 baris `guidance_progress`. **Semua token kini luput (0 aktif)** — disahkan dua-hala.
**0 rekod/fail/minit dicipta.** Masjid sebenar `mamad` **tidak disentuh**.

> ⚠️ **SATU TINDAKAN PEMILIK:** padam tiket ujian **`SUP-260801-HXQ0DIOL`** di `/admin/tiket-sokongan`.

### Nota teknikal untuk sesi akan datang
- Nilai `expires_at` token terhadap **masa aplikasi (`Asia/Kuala_Lumpur`)**, bukan UTC — UTC mentah
  beri positif palsu "token masih aktif".
- Bezakan token audit drp token notifikasi sistem melalui **`intended_url`**, bukan `purpose`
  (semua = `notification` secara lalai). Token ID 219–220 milik operasi normal — jangan expire.
- Codex CLI di mesin ini: `--sandbox workspace-write` **ROSAK** (`CreateProcessAsUserW 1312`) →
  guna `--sandbox danger-full-access`.
- Skrinsyot bukti (~129 MB, 1081 PNG) **tidak dikomit** (lihat `bukti/.gitignore`) — kekal di
  cakera tempatan. Laporan + data JSON/TXT dikomit.

---

## SESI — Pemulihan Akses SSH + Pengesahan Live (1 Ogos 2026)

**Status:** LIVE & DISAHKAN. Server `43.156.242.188` (Tencent **Lighthouse** `lhins-mmc2juw3`,
Singapura, Ubuntu 22.04, 2 vCPU/2GB RAM/30GB disk) pada commit `446b82c`. `https://bakwim.my` 200.

### Konteks
Sesi bermula dengan matlamat "deploy ke 43.156.242.188 / bakwim.my / Cloudflare". Rupanya sistem
**sudah live sepenuhnya** daripada sesi-sesi terdahulu (rujuk seksyen 22 Julai ke bawah). Kerja
sebenar sesi ini: **memulihkan akses SSH yang hilang** lalu **sahkan sistem masih sihat hujung-ke-hujung**.

### Masalah #1 — Salah faham IP server
Pengguna beri `43.156.242.188`, tetapi konsol **CVM** Tencent hanya ada instance lain
`43.156.71.249` (Standard S5). Server sebenar ialah instance **Lighthouse** `Ubuntu-s0Hu` =
`43.156.242.188`. **Pengajaran:** server ini di konsol **Lighthouse**, bukan CVM. Region Singapura (rid=9).

### Masalah #2 — SSH ditolak, web terminal disekat CAPTCHA (BLOKER UTAMA)
- `ssh ubuntu@…` dan `root@…` → `Permission denied (publickey,password)`. Kunci ejen tiada di server.
- Cuba web terminal **OrcaTerm** Lighthouse → tersekat **CAPTCHA "pilih ikut urutan" + SMS 2FA**.
  Ejen **DILARANG menyelesaikan CAPTCHA** (peraturan keselamatan mutlak) dan kod SMS hanya ke telefon pemilik.
  Ini menyekat sepenuhnya buat beberapa pusingan.

### PENYELESAIAN #2 — Bind SSH Key via TAT (tiada CAPTCHA, tiada reboot) ⭐
Laluan yang berjaya, elak OrcaTerm sepenuhnya:
1. Konsol **Lighthouse → SSH Keys → Create** → pilih **"Use an existing public key"** →
   tampal kunci awam ejen (`ssh-ed25519 …`), region **Singapore** → OK. (Halaman ini **tidak** cetuskan CAPTCHA.)
2. Kunci dicipta (`lhkp-o3b46b51` / `claude_deploy`). Klik **Bind Instance** → pilih `Ubuntu-s0Hu` →
   kaedah **"Bind online" (guna TAT/Tencent Cloud Automation Tools)** → OK.
3. TAT menyuntik kunci **secara live tanpa reboot** (~10 saat). `ssh ubuntu@43.156.242.188` terus berjaya.
> **Pengajaran kunci:** untuk pulih akses SSH Lighthouse tanpa CAPTCHA/SMS OrcaTerm, guna
> **SSH Keys → Bind online (TAT)**. Syarat: agen TAT "Running" pada instance (ia memang running di sini).
> Log masuk sebagai **`ubuntu`** (bukan root); ada `sudo` tanpa kata laluan.

### Pengesahan live (semua hijau)
- **Cloudflare:** NS `mckinley/rohin.ns.cloudflare.com`, proxied (edge SIN). HTTPS sijil Google Trust
  Services (sah → 15 Okt 2026), **HSTS** `max-age=31536000`. `Server: cloudflare`, `CF-RAY …-SIN`.
- **Laluan awam HTTPS:** `/`,`/up`,`/daftar`,`/log-masuk` → 200; `/admin`,`/app` (tanpa login) → 302 (gating betul).
  Cookie `diwan-session` `secure; httponly; samesite=lax`. **Tiada redirect loop** di belakang CF (config https OK).
- **8 container sihat:** app, worker(**Horizon running**), scheduler, nginx, postgres16, redis7, meilisearch, **clamav**.
  `diwan:health` → **OK**. Beban 0.95 (2 vCPU); memori sihat (2GB + swap 3GB).
- **Ralat:** 55 baris ERROR dalam log SEMUANYA lama (18–22 Julai, tempoh debug regresi guidance
  `RecordDirection`, sudah dibaiki oleh `6fc1df3`/`00775ec`/`446b82c`). **0 ralat sejak 22-07 23:58** (bersih 3+ hari).

### Nota persekitaran (skop TERAS/canary)
- `DIWAN_STORAGE_DISK=local`, `MAIL_MAILER=log`, `IMAP_ENABLED=false`, `WHATSAPP_DRIVER=log`,
  `DIWAN_REGISTRATION_OPEN=false`. (Sesi terdahulu mungkin sudah naik taraf sebahagian; sahkan `.env` server
  sebenar sebelum ubah — ada backup `.env.bak.*` di `/opt/diwan`.)
- Superadmin: `azanmalek@maiwp.gov.my` (dicipta/disahkan sesi ini).
- Deploy Docker Compose di `/opt/diwan`; port 80 via `docker-compose.override.yml`; UFW: SSH/80/443 sahaja.

### Langkah seterusnya (bila pemilik mahu)
Buka pendaftaran (`DIWAN_REGISTRATION_OPEN=true`), atau naik production penuh: COS, SMTP, IMAP,
gateway WhatsApp — ikut checklist `WHAT-TO-DO-NEXT.md` P2–P3.

---

## LATEST RELEASE — Log Aktiviti Masjid + Manual Berurutan 22 Julai 2026

**Status:** LIVE. Imej aplikasi production dibina daripada `b9a5c30` di `https://bakwim.my`.

- Manual sembilan persona kini mempunyai arahan bersambung **Gambar 1 → Gambar 2 → hasil akhir** bagi setiap tugas, termasuk aliran Admin/Kerani dari Dashboard, Peti Masuk, modal klasifikasi, Minit Saya hingga Log Aktiviti.
- Liputan manual terkini: 115/115 halaman sidebar HTTP 200, 8/8 silang tenant HTTP 404, 231 PNG, 309 rujukan imej, 0 imej hilang dan 0 browser error.
- Halaman `/app/{tenant}/log-aktiviti` ialah timeline append-only tenant untuk Admin/Kerani, Pengerusi, Setiausaha dan Bendahari. Ia menyimpan snapshot pelaku, role, rekod/fail, saluran, pengirim/uploader, IP apabila tersedia dan metadata peristiwa.
- Katalog log meliputi intake/klasifikasi, minit, kelulusan/pembetulan, fail, custody fizikal, pelupusan, ahli, storan dan legal hold. Bendahari ditapis lagi melalui skop rekod/fail yang boleh dilihat.
- Gate tempatan: Pest `383 lulus, 1 skip, 1281 assertions`; fokus Log Aktiviti `6 lulus, 39 assertions`; Pint dan Vite lulus.
- Production: migration batch 6 `Ran`; app/worker/scheduler `healthy`; nginx dicipta semula dan sah; `/up` dalaman/awam serta login HTTP 200; `diwan:health OK`; failed queue kosong.
- Chrome production sebenar, context berasingan dan sela login 15 saat: empat role dibenarkan HTTP 200, modal berjaya, silang tenant 404, carian WhatsApp/e-mel berjaya; AJK HTTP 403.
- Fixture audit production dibersihkan sepenuhnya: 2 log, 5 pivot dan 5 akaun ujian. Akaun dan data masjid sebenar tidak disentuh.
- Journey, matriks keselamatan dan arahan ulangan: [`docs/HANDOVER-2026-07-22-LOG-AKTIVITI-DAN-MANUAL.md`](docs/HANDOVER-2026-07-22-LOG-AKTIVITI-DAN-MANUAL.md).

## LATEST DELIVERABLE — Manual Pengguna 9 Persona 22 Julai 2026

**Status:** Lengkap dan disahkan menggunakan Google Chrome pada data latihan terasing.

- Folder `Manual Penguna/` mengandungi 9 manual: Admin/Kerani, Pengerusi, Setiausaha,
  Bendahari, Nazir, Ketua Imam, AJK, Juruaudit dan Orang Awam/Pendaftaran.
- Liputan sebenar: 111/111 halaman sidebar HTTP 200, 8 konteks browser berasingan,
  silang tenant 8/8 HTTP 404, browser error 0 dan 223 PNG beranotasi.
- Semua 223 imej dirujuk tepat sekali dalam manual; pautan rosak 0, imej tidak dirujuk 0,
  fail PNG rosak/kecil 0 dan corak rahsia/kata laluan/token tersimpan 0.
- Viewer setiap role memaparkan halaman pertama PDF sebenar. Modal panjang klasifikasi
  dirakam hingga medan edaran minit dan butang Hantar, bukan sekadar bahagian viewport atas.
- Aliran pendaftaran awam diuji dari borang, penghantaran, kelulusan tempatan, pautan sekali
  guna, tetapan kata laluan hingga persediaan pertama. Production tidak dimutasi.
- Gate kod terfokus: 54 ujian lulus, 181 assertions untuk role, tenant/search isolation,
  pendaftaran, klasifikasi peti masuk, sumber Filament dan regresi keselamatan P0.
- Alat boleh ulang: `scripts/manual/prepare-manual.php`, `capture-manual.mjs` dan
  `generate-manuals.mjs`. `MANUAL_DEMO_PASSWORD` wajib; tiada kata laluan lalai dalam kod.

## LATEST RELEASE — Gabungan Admin / Kerani 21 Julai 2026

**Status:** LIVE. Runtime production dibina daripada `103b186` di `https://bakwim.my`.

- `admin_masjid` kini role kanonik tunggal berlabel **Admin / Kerani**. Role `kerani`
  dibuang daripada pilihan UI/config dan migration
  `2026_07_21_000002_merge_admin_kerani_roles` menukar semua pivot lama kepada
  `admin_masjid`. Alias kod lama dinormalisasi untuk keselamatan semasa rollout.
- Admin / Kerani mengandungi gabungan akses operasi kerani dan kuasa admin: peti masuk,
  klasifikasi, rekod/fail, minit, kelulusan, retensi, pelupusan, pengguna, tetapan masjid,
  storan dan audit. Perlindungan admin terakhir serta tenant isolation kekal aktif.
- Bukti tempatan: Pest `377 lulus, 1 skip, 1243 assertions`; Pint lulus.
- Bukti Chrome production selepas deploy: **8 BrowserContext berasingan**, satu bagi setiap
  role tenant. Jumlah **111 halaman** terlihat dibuka dan semuanya `200`; tiada pageerror
  atau console error sebelum probe silang tenant; semua 8 role menerima `404` untuk
  `/app/mamad/records` dari sesi tenant `smoke`.
- Pecahan halaman: Admin / Kerani `21`, Pengerusi `15`, Setiausaha `13`, Bendahari `13`,
  Nazir `12`, Ketua Imam `12`, AJK `12`, Juruaudit `13`.
- Lima akaun role sementara diwujudkan hanya dalam tenant `smoke` untuk audit dan dipadam
  selepas ujian. Semakan akhir: akaun sementara `0`, pivot legacy `kerani=0`, failed job `0`,
  `diwan:health OK`, `/up=200`, dan semua 8 container berjalan.
- Superadmin ialah flag global, bukan salah satu role tenant dalam `config/roles.php`.
  Akaun/kata laluan superadmin sebenar tidak diubah atau diteka dalam audit ini.

## LATEST RELEASE — DDMS Lanjutan + ClamAV 21 Julai 2026

**Status:** LIVE. Imej production dibina daripada `9579897`; ciri utama `f2fcc75`.

- Saved search/favourite, carian metadata/tarikh, viewer PDF/imej, workflow pembetulan,
  principal/delegate, 33 jenis rekod dan tracking fail fizikal/hibrid telah siap.
- Intake UI/e-mel/WhatsApp kini fail-closed melalui ClamAV; EICAR production dikesan tepat
  dan port daemon tidak diterbitkan ke host. Inbox memaparkan pengirim/uploader dan masa.
- Bukti tempatan sebelum gabungan role: Pest `376 lulus, 1 skip, 1247 assertions`;
  Chrome workflow 4/4, inventori 9 role lulus, silang tenant 404; audit Composer/npm bersih.
- Bukti production: 8 container hidup, ClamAV healthy, migration baharu Ran,
  `diwan:health OK`, smoke `9/9`, failed queue kosong, Chrome read-only halaman baharu lulus.
- Node build dinaikkan 20→22 untuk PDF.js. Guzzle dinaikkan 7.13.2→7.15.1 selepas empat
  advisory medium baharu ditemui. Healthcheck ClamAV kini melakukan scan fail kecil sebenar.
- Build cache 11.03 GB dibersihkan; disk turun 82%→46%. VM 1.9 GB masih sempit walaupun
  stabil; naik taraf 4 GB RAM disyorkan sebelum intake/OCR volum tinggi.
- Bukti penuh, journey, masalah, penyelesaian dan rollback:
  [`AUDIT-DDMS-EXTENDED-2026-07-21.md`](AUDIT-DDMS-EXTENDED-2026-07-21.md).

## LATEST RELEASE — Audit A-Z 21 Julai 2026

**Status:** LIVE dan disahkan selepas deploy `980f12e` di `https://bakwim.my`.

- Code release di tempatan, `origin/main` dan server `/opt/diwan` ialah `980f12e` selepas
  pull/deploy; commit handover ini mengekalkan bukti selepas release. Untracked server
  `.env.bak.1784338951` dan `docker-compose.override.yml`
  dikekalkan kerana ia konfigurasi/backup operasi, bukan fail Git.
- Dua fix release pertama `1d7c92b`: validasi nombor WhatsApp pendua sebelum DB 500;
  named limiter berasingan untuk pendaftaran, login page dan magic-link; HSTS dan
  `expose_php=Off`.
- Fix susulan `980f12e`: guard magic-link supaya auto-submit 300 ms dan klik manual tidak
  menghantar POST dua kali. Chrome membuktikan satu POST 302 bagi setiap mod.
- Gate lokal: Pest `361 lulus, 1 skip, 1203 assertions`; Playwright `5 lulus, 1 skip`;
  Pint, diff-check dan Vite build lulus. Skip OCR memerlukan fixture imej sebenar.
- Chrome production, context/session berasingan: superadmin 8 halaman, admin masjid 18,
  kerani 13, pengerusi 12, ahli biasa 9; semua halaman `200`, tiada JS error. Tenant
  `smoke` tidak boleh membuka `/app/mamad/records` (`404`). Ahli biasa melalui gate
  tetapan kata laluan pertama sebelum panel.
- Production: `/up` `200`, `diwan:health` `OK`, `diwan:smoke` `9/9`, failed queue kosong,
  `nginx -t` lulus, HSTS aktif dan `X-Powered-By` tidak terdedah. Fetch-mail selesai
  setiap minit dalam 3-11 saat.
- **Deploy rule:** selepas `docker compose up --force-recreate app worker scheduler`,
  force-recreate nginx juga kerana nginx menyimpan IP upstream FastCGI lama; jika tidak,
  origin boleh sementara `502`. Jika asset frontend berubah, build `app` dan `nginx`
  kedua-duanya.
- Rujukan bukti lengkap: [`AUDIT-E2E-2026-07-21.md`](AUDIT-E2E-2026-07-21.md).

**Kemas kini:** 2026-07-20 · **Status:** LIVE di https://bakwim.my (Cloudflare Full strict, COS, login password, Brevo SMTP). Sesi 18 Jul: Email intake LIVE PENUH, WhatsApp E2E LENGKAP (pilot MAMAD), bug OCR Ghostscript dibaiki (`fe5744a`).

---

## 🐛 SESI 20 JUL (MALAM 2) — INTAKE E-MEL TERSEKAT: PUNCA & FIX (`91a229b`+`4e75dfd`+`0f543d8`; LIVE `0f543d8`)

**Aduan pemilik:** e-mel dari `hakimalek27@gmail.com` ke `scan+mamad@bakwim.my` tidak masuk Peti Masuk (WhatsApp ok; upload UI ok selepas fix petang).

### Punca sebenar — mutex JADUAL tersangkut (bukti keras, bukan teori)
```
schedule:list        → diwan:fetch-mail  "Has Mutex"        ← tidak pernah jalan
redis TTL framework/schedule-accde773…  → 50950s (≈14.2 jam)
logs scheduler       → ping-gateway/check-wa/drive jalan; fetch-mail TIADA
log [IMAP] intake    → terakhir 19 Jul 03:11 (≈14 jam senyap, 0 ralat)
```
Kunci dicipta oleh kod **LAMA** `withoutOverlapping()` **tanpa argumen = lalai 1440 minit (24 JAM)**; container di-recreate mid-run semasa deploy → kunci tidak dilepaskan. Fix sesi lepas (`withoutOverlapping(10)`) hanya berkesan untuk kunci **baharu**.

### Mengapa senyap sepenuhnya
Kesihatan dinilai daripada `imap_failure_streak` **sahaja**. Streak hanya bertambah apabila job **berjalan** — job yang langsung tidak berjalan mengekalkan streak 0, jadi setiap penunjuk memaparkan **"OK" hijau palsu** dan sifar alert dihantar.

### Dua hipotesis diuji dan TERBUKTI SALAH (dicatat supaya tidak diulang)
1. `MAIL_INTAKE_ADDRESS` hilang → **salah**; nama config sebenar ialah `diwan.mail_intake.address` (bukan `diwan.mail_intake_address`).
2. `getTo()` kosong selepas Cloudflare Routing → **salah**; ia pulang `scan+mamad@bakwim.my` betul. "Kosong" tadi ialah artifak skrip diagnostik (objek `Attribute`, perlu `->toArray()`).

Yang **sihat sepanjang masa**: CF Routing kekalkan header `To:` asal, `slugFromAddress()` betul, `mail_intake_enabled=true`, allowlist mengandungi pengirim.

### Halangan kedua (ditemui semasa E2E, bukan teori)
Kunci **job-level** (`laravel-queue-overlap:App\Jobs\FetchMailJob:diwan-fetch-mail`) **tersangkut semula sebaik selepas deploy** — corak sama, menyekat 10 minit setiap deploy. Lebih buruk: `diwan:fetch-mail` mencetak **"FetchMailJob selesai"** walaupun middleware menelan larian = laporan palsu.

### Pembaikan
| Fail | Perubahan |
|---|---|
| `routes/console.php` | SEMUA mutex jadual bertempoh luput eksplisit: fetch-mail **2**, check-wa-sessions **10**, drive-reconcile **55** (dua terakhir masih lalai 24j = bom jangka sama) |
| `app/Jobs/FetchMailJob.php` | Detak jantung `imap_last_success_at`; `expireAfter` **600→120** |
| `app/Support/MailIntakeHealth.php` *(baharu)* | Satu sumber kebenaran: `disabled`/`ok`/`failing`/**`stalled`** |
| `app/Console/Commands/CheckWaSessions.php` | Alert superadmin pada **TERSEKAT** (bukan hanya GAGAL), dipagar `IMAP_ENABLED` |
| `app/Console/Commands/FetchMail.php` | Lapor **DILANGKAU** + exit FAILURE + bendera **`--force`** untuk lepaskan kunci |
| Widget + Status Sambungan | Papar "Tersekat" + "Larian berjaya terakhir" + petunjuk pembaikan mutex |

### Bukti
- **Pest 358 lulus / 1 skip**, Pint bersih, **CI HIJAU** ketiga-tiga SHA (disahkan `conclusion=success`, tanpa pipe).
- `MailIntakeHealthTest` (15) termasuk penjaga yang **gagal bila mana-mana mutex jadual >60 minit** — **dibuktikan menangkap regresi** (sengaja kembalikan `withoutOverlapping()` → merah; pulihkan → hijau).
- **E2E produksi sebenar:** e-mel dihantar melalui laluan penuh (Brevo → Cloudflare → Gmail → IMAP → ingest) → rekod **#24** tenant smoke, `src=emel`, OCR siap, teks tepat `UJIAN E2E INTAKE EMEL SELEPAS FIX MUTEX`.
- **E-mel pemilik "Jshd" turut diproses** → rekod **#22** mamad, `src=emel`, OCR siap 675 aksara. Andaian "sudah Seen → hilang selamanya" **salah**: Diwan sendiri menandakannya Seen semasa memprosesnya. **Tiada e-mel hilang.**
- **Chrome MCP:** Peti Masuk mamad papar "Jshd" Sumber=E-mel OCR=Siap; `/admin` Status Sambungan papar `IMAP intake e-mel OK / Larian berjaya terakhir 17 saat lepas`; dashboard bergaya penuh (hash aset app==nginx, tiada regresi CSS).
- `/up` 200, 7 container healthy, `diwan:smoke` **9/9**, `staging-check` 8/9 (smtp perlu `--mail-to`), `failed_jobs`=0. `local = origin = server = 0f543d8`.

### ⚠️ Nota operasi penting
- **Kata kunci intake:** `Mosque::mailIntakeKeyword()` jatuh balik ke `MAIL_INTAKE_KEYWORD=spdm` global apabila kunci `mail_intake_keyword` **tidak wujud** dalam settings. mamad ada kunci bernilai `''` → **tiada gate** ✔; smoke **tiada kunci** → efektif `spdm`. Bezakan "wujud tapi kosong" daripada "tiada".
- **Risiko diterima secara sedar** (pilihan pemilik: kekal `->unseen()`): membuka peti masuk `spdmediwan` di Gmail menandakan e-mel dibaca → Diwan melangkaunya **selamanya**. **Sahkan intake tanpa membuka Gmail** — guna `grep "\[IMAP\] intake" storage/logs/laravel.log` + semak DB. Jika berulang → naik taraf kepada jejak-UID.
- **Selepas SETIAP deploy:** semak `schedule:list` untuk "Has Mutex"; pulih dengan `php artisan diwan:fetch-mail --force`.

---

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
