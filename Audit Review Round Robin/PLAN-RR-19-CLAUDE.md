# Pusingan 19 — Claude: keputusan P18-01…P18-07 + integrasi `PELAN-PEMBAIKAN.md` v1.8

**Tarikh:** 2 Ogos 2026 · **Asas kod:** `8342d95`
**Fail dinilai:** `PLAN-RR-18-CODEX.md`, `PELAN-PEMBAIKAN.md` v1.7, `PLAN-RR-17-CLAUDE.md`,
`PLAN-RR-STATUS.md` + kod/config yang dirujuk P18 (baca sahaja)
**Keputusan ringkas:** **7 TERIMA · 0 TERIMA SEBAHAGIAN · 0 TOLAK** (2 daripadanya
**TERIMA + DIKUATKAN** dengan bukti kod tambahan yang P18 tidak kemukakan)
**Status pelan:** ⏳ **v1.8 — BELUM MUKTAMAD.** Diserah kepada **Codex Pusingan 20**.

---

## 1. Integriti fail (§0.7 #1 — dua semakan sebelum suntingan pertama)

| Semakan | Masa | SHA-256 `PELAN-PEMBAIKAN.md` | Saiz | Baris (LF) | mtime |
|---|---|---|---|---|---|
| Pra-tulis #1 | 2026-08-02 05:33 | `DD58889AEB2D58CA4A2C3E2A9EA040D9C555F7B64FDBF246EBE8699705BBDDBE` | 248 846 B | 3 270 | 2026-08-02 05:12:54 |
| Pra-tulis #2 | 2026-08-02 05:35:35 | `DD58889A…705BBDDBE` (**identik**) | 248 846 B | 3 270 | 2026-08-02 05:12:54 |

✅ Sepadan **tepat** dengan nilai beku dalam arahan P19 (hash, 248 846 bait, 3 270 baris,
mtime `05:12:54`). Tiada penulisan proses lain dikesan; tiada sebab untuk berhenti.

> **Nota kaedah (supaya P20 boleh menghasilkan semula angka yang sama).** `Get-Content |
> Measure-Object -Line` memulangkan **2 850** kerana ia tidak mengira baris kosong. Kiraan yang
> sah untuk fail ini ialah bilangan `\n`: `([regex]::Matches($teks,"\n")).Count` = **3 270**.
> Fail menggunakan **LF sahaja** (0 CRLF). Gunakan kaedah yang sama, jika tidak angka baris akan
> kelihatan "berubah" tanpa sebarang suntingan.

`PLAN-RR-18-CODEX.md` = `404C8E97175097C45D44F8CE1BFC860B510007A45B0CC4CC4CE754F79365A070`
(9 537 B) — **sepadan** nilai beku arahan.

**Selepas suntingan P19:**

| Fail | SHA-256 | Saiz | Baris (LF) | mtime |
|---|---|---|---|---|
| `PELAN-PEMBAIKAN.md` (**v1.8**) | `68F00B235775CE825C6E93E2D913FA5211716F6FF1C94FEF5AD7CD710F84E711` | 280 276 B | 3 673 | 2026-08-02 05:45:10 |

Delta: **+31 430 B · +403 baris**.

### 1.1 Status kerja — dua dakwaan berasingan (§0.7 #7 baharu, P18-07)

```text
$ git status --short -- app resources tests e2e config .github docker composer.json composer.lock package.json package-lock.json
(0 baris)

$ git status --short
 M HANDOVER.md
?? "Audit Review Round Robin/CLAUDE-P17-PROMPT.txt"
?? "Audit Review Round Robin/KEPUTUSAN-PEMILIK.md"
?? "Audit Review Round Robin/PELAN-PEMBAIKAN.md"
?? "Audit Review Round Robin/PLAN-RR-02-CODEX.md"
   … (20 entri `??` semuanya dalam `Audit Review Round Robin/`)

$ git log -1 --format="%h %ci"
8342d95 2026-08-01 14:51:41 +0800
```

**Tafsiran yang betul:** tiada perubahan kod aplikasi dalam giliran P19 (dan tiada sepanjang fasa
perancangan); working tree **keseluruhan tidak bersih** kerana `HANDOVER.md` diubah dan 20 fail
perancangan belum dijejak. Kedua-dua ayat benar serentak — itulah sebab §0.7 #7 kini melarang
frasa tunggal "working tree bersih".

### 1.2 Snapshot §0.7 #2 — masih TERTUNGGAK

P19 juga dihadkan kepada tiga fail dan **dilarang menjalankan git add/commit/push**, jadi ia
tidak dapat mencipta snapshot immutable. §0.7 #4 kekal terpakai: dakwaan sejarah tentang teks
v1.6/v1.7 yang tidak boleh disemak terhadap snapshot **tidak** dijadikan asas keputusan produk.
Semua keputusan di bawah disandarkan pada **kod semasa** atau pada teks v1.7 yang **masih hadir**
dalam fail (dan hashnya direkod di atas).

---

## 2. Keputusan setiap penemuan P18

### P18-01 — Required check `ci-domain` mustahil → **TERIMA + DIKUATKAN**

**Premis P18 disahkan.** `ci-domain` ialah **project Playwright** dalam blok `projects:` yang
pelan sendiri cadangkan (v1.7 `:991-995`), dijalankan sebagai command dalam job `integration`
(`:1011-1013`). GitHub Actions melaporkan status check **per job**, bukan per project Playwright
— jadi check bernama `ci-domain` **tidak akan pernah tiba**, dan menetapkannya sebagai required
menyekat setiap PR selama-lamanya (mod kegagalan "expected — waiting for status to be reported").

**Penguatan 1 (P18 hanya menyebutnya sebagai risiko).** Required check `integration` **juga**
salah, dan ini boleh disahkan terus: `.github/workflows/ci.yml:18-19` —

```yaml
  integration:
    name: PostgreSQL, Redis, Meili, OCR and tests
```

Apabila `name:` ditetapkan, nama status check ialah **nilai `name:`**, bukan job id. Jadi nama
check sebenar hari ini ialah `PostgreSQL, Redis, Meili, OCR and tests`.

**Penguatan 2 (P18 tidak menemuinya).** Kecacatan yang **sama** sudah menjangkiti job baharu
pelan itu sendiri: v1.7 `:842-843` memberi agregator `name: Guidance coverage gate`, manakala
**enam** lokasi lain (`:86`, `:790`, `:1053`, `:1564`, `:2676`, `:3059`) menamakan required check
itu `guidance-e2e-gate`. Dua nama untuk satu check; branch protection hanya menerima satu.

**Pilihan yang diambil: Pilihan 2 P18** (kekalkan `ci-domain` sebagai step), dengan tiga
tambahan:
1. **`name:` digugurkan** daripada `guidance-e2e` dan `guidance-e2e-gate` → check name = job id,
   sepadan persis dengan enam rujukan sedia ada (tiada penulisan semula 6 lokasi diperlukan).
2. **Job `integration` TIDAK dinamakan semula** — menukar `name:` menukar nama check dan
   mematahkan tetapan branch protection/PR terbuka yang merujuk nama lama. Pelan menyesuaikan
   diri kepada nama sedia ada (Lampiran B #13).
3. **Nama disahkan, bukan diteka** sebelum tetapan repo diubah:
   `gh api "repos/…/commits/$(git rev-parse HEAD)/check-runs" --jq '.check_runs[].name' | sort -u`.

*Mengapa Pilihan 1 (jadikan `ci-domain` job sebenar) ditolak:* ia memerlukan **salinan ketiga**
blok services/env/setup (±60 baris) untuk **dua** spec, sedangkan mekanisme penguatkuasaannya
sudah wujud (step yang gagal → job gagal). Kelemahan sebenar Pilihan 2 — "step boleh dilangkau
tanpa disedari" — ditutup oleh P18-05 (artifak JSON membuktikan ia berjalan), bukan oleh job
tambahan.

**Diintegrasi:** §1 **F0(iv)(f) baharu** (jadual ralat + kontrak 6 titik + command `gh api`),
YAML F0(iv)(d) (tiada `name:`), §10 langkah 1, "Gate:" F0(iv), kriteria siap **F2/F3/F5/F7**,
§9 jadual metrik, Lampiran B #13 + #15.

---

### P18-02 — YAML "bentuk beku" masih placeholder → **TERIMA**

**Disahkan pada setiap baris yang P18 namakan** (v1.7): `:822` `postgres: { image:
postgres:16-alpine, ... }`, `:823` redis, `:824` meilisearch, `:833` komen
*"# setup PHP/Node/composer/npm/build/migrate:fresh --seed/serve/canary — sama lapis 1"*.
Label "bentuk beku — bukan cadangan" (`:809`) tidak benar selagi implementer masih perlu mereka
bentuk services/env/setup sendiri — iaitu tepat yang P12-04/P14-01/P16-02 cuba hentikan.

**Bahan salinan disahkan wujud** dalam `.github/workflows/ci.yml`: services `:22-51`,
env `:53-80`, setup `:82-121` (checkout `:82` · setup-php `:84-89` · setup-node `:91-95` ·
cache composer `:97-102` · OCR `:104-107` · deps `:109-112` · validate `:114-118` ·
build `:120-121`).

**Diintegrasi:** §1 **F0(iv)(d) ditulis semula sepenuhnya** — ~120 baris YAML literal, tiada
`...`, tiada "sama lapis 1". Lima perbezaan daripada job `integration` ditanda `# OVERRIDE 1..5`
(APP_URL 8092 · E2E_BASE_URL 8092 · SESSION_DRIVER file · E2E_ROLE_LOGIN_DELAY_MS 0 ·
GUIDANCE_SHARD). Tiga keputusan yang sengaja **tidak** disalin didokumen dengan sebab + syarat
pembatalan:
- `Install OCR tooling` (`ci.yml:104-107`) — `guidance-full` read-only, tiada muat naik; **mesti**
  disalin masuk dalam PR yang sama jika mana-mana langkah shard mula memuat naik dokumen
  (`QUEUE_CONNECTION: sync` bermakna `ProcessOcrJob` berjalan dalam permintaan itu);
- `Validate, audit and format` (`:114-118`) — sudah dijalankan oleh `needs: integration`;
- `Runtime compatibility smoke` (`:134-148`) — Horizon/Meili tidak berkaitan gate liputan.

Langkah `serve` menyimpan `serve_pid` ke `$GITHUB_ENV` dan langkah `Migrate and seed` mencipta
`bukti/plan-ci/` + `bukti/plan-f6/` (reporter JSON gagal jika direktorinya tiada).

---

### P18-03 — Kontrak kredensial superadmin bercanggah → **TERIMA + DIKUATKAN**

**Percanggahan disahkan** dalam v1.7: `:2822` (`prepare` — "superadmin sedia ada dirujuk,
**tidak** dicipta semula") vs `:2844-2849` (peraturan wrapper 3 — `prepare` menulis kredensial
termasuk **`E2E_PROD_SUPERADMIN_*`** ke fail rahsia). Kedua-duanya tidak boleh benar: kata laluan
disimpan sebagai hash, jadi satu-satunya cara command boleh "menulis" plaintext akaun sedia ada
ialah dengan **menetapkan semula** kata laluan superadmin produksi — mutasi kepada akaun paling
berkuasa dalam sistem, dalam larian yang §9.1a isytiharkan **read-only mutlak**.

**Penguatan (bahaya ketiga yang P18 tidak namakan).** `e2e/guidance.spec.js:26-29`:

```js
const superadminAccount = {
    email: process.env.E2E_PROD_SUPERADMIN_EMAIL ?? 'superadmin@diwan.test',
    password: process.env.E2E_PROD_SUPERADMIN_PASSWORD ?? defaultPassword,   // :6 → 'password'
};
```

Terdapat **lalai diam**. Wrapper yang gagal menyemak env tidak akan meledak — ia akan menghantar
`superadmin@diwan.test` / `password` ke borang log masuk **produksi**, mencetuskan had kadar
5/min (`config/diwan.php:41`), dan larian itu akan kelihatan seperti pepijat UI sedangkan ia
konfigurasi hilang. Itu tepat corak "bukti palsu terbalik" yang v1.7 sendiri namakan bagi canary.

**Pilihan A diambil; Pilihan B ditolak dengan sebab.** Pilihan B (`prepare` mencipta
`superadmin-<run_uuid>@smoke.test`) mencipta **identiti akses-penuh silang-tenant baharu pada
produksi**; jika cleanup gagal (rangkaian putus, Ctrl-C sebelum `finally`, exit paksa) ia kekal
hidup dengan kata laluan yang sudah ditulis ke cakera — kategori kesilapan yang sama dengan
RR-11-01 (audit meninggalkan 21 token produksi). Fixture role tenant tidak membawa risiko itu
kerana ia berskop tenant terpakai-buang.

**Diintegrasi:** §9.1a — jadual `prepare` (8 akaun role sahaja; 10 identiti matriks kekal, dengan
superadmin luaran + public tanpa akaun), baris jadual **"Superadmin (dibekukan v1.8)"**, peraturan
wrapper **1** (senarai env wajib + sebab lalai diam berbahaya) dan peraturan **3** (jadual dua
sumber; superadmin tidak pernah menyentuh cakera; ujian penjaga dalam
`AuditFixtureCommandTest`: output `prepare` tidak mengandungi substring `SUPERADMIN`),
Lampiran B #14.

---

### P18-04 — Antivirus intake tiada gate wajib → **TERIMA**

**Fakta P17 kekal betul, tetapi skopnya terlalu luas.** P17 menolak "fixture antivirus" P16-08
kerana `config/diwan.php:32` (`CLAMAV_ENABLED` lalai `false`) dan `AntivirusScanner.php:12`
(pulang `disabled` awal). Itu membuktikan **tiada service ClamAV diperlukan dalam CI** — ia
**tidak** membuktikan laluan fail-closed diuji. P18 betul.

**Bukti kod:**
- `app/Services/InboxIngestService.php:72-78` — tolak `infected` (dengan signature), dan
  tolak apa-apa yang bukan `clean` apabila `clamav.enabled && clamav.fail_closed`;
- kedua-dua `throw` berada **sebelum** `DB::transaction(...)` (`:91`) dan sebelum
  `addMediaFromString` (`:111`) serta `MosqueActivityLogger::log` (`:127`) — jadi assertion
  "0 `Record` · 0 media · 0 log" ialah ujian regresi yang **bermakna**, bukan hiasan;
- `config/diwan.php:36` — `fail_closed` lalai **true** (jadi tingkah laku yang diuji ialah lalai
  produksi apabila ClamAV diaktifkan).

**Liputan ujian semasa (disahkan `grep -rn "AntivirusScanner\|clamav" tests/`):**
- `tests/Feature/DdmsExtendedCapabilitiesTest.php:149-155` — intake dengan
  `clamav.enabled = false`, assert status `disabled`. **Tidak** menyentuh cabang fail-closed;
- `tests/Feature/GuidanceSupportTest.php:91-107` — mock `infected`, tetapi terhadap
  `SupportRequestService::create()` (**lampiran tiket sokongan**), bukan `InboxIngestService`.

Maka cabang `:76-78` mempunyai **liputan sifar**. Corak mock yang diperlukan sudah wujud dalam
repo (`GuidanceSupportTest.php:93-97`, `Mockery::mock(AntivirusScanner::class)` +
`$this->app->instance(...)`), jadi ujian baharu tidak memperkenalkan teknik atau pakej baharu.

**Diintegrasi:** **§0.6 S7 baharu** (probe tetap setiap fasa, dengan nota mengapa ia kekal walau
fasa tidak "menyentuh" antivirus), **§1 F0(iv-a) fail #14**
(`tests/Feature/InboxAntivirusFailClosedTest.php`), §9 jadual metrik (baris baharu: baseline
0 ujian → sasaran 3/3 status ditolak dengan 0 kesan sampingan), §11 D11 (fail #14, disyorkan
diluluskan walaupun jika item lain ditolak — satu-satunya item D11 yang menutup risiko
**keselamatan**, bukan pengukuran), dan skop pembetulan P16-08 dihadkan secara eksplisit di dua
tempat (log versi `:144-154` + syarat `ci-ocr` #3).

---

### P18-05 — `results.json` tiada reporter → **TERIMA**

**Disahkan:** `playwright.config.js:9` = `reporter: [['line']]`. Tiada reporter JSON, tiada
`outputFile`, tiada artifak. Arahan v1.7 `:1027` ("semak `results.json` → `status !== 'skipped'`")
dan metrik `:2703` merujuk fail yang **tidak pernah dijana**. Bahaya sebenarnya bukan skrip yang
meledak — ia skrip yang ditulis dengan `try/catch` dan **lulus senyap**, iaitu tepat mod kegagalan
yang P16-08 cuba tutup (`ocr-upload.spec.js:6` `test.skip` bersyarat).

**Kontrak yang dibekukan (§1 F0(iv)(e) baharu):**

| Perkara | Nilai |
|---|---|
| Config | `reporter: process.env.DIWAN_PW_JSON ? [['line'], ['json', { outputFile: process.env.DIWAN_PW_JSON }]] : [['line']]` |
| Env | `DIWAN_PW_JSON` — **nama milik projek**, bukan env dalaman Playwright (tiada pergantungan pada butiran versi `@playwright/test ^1.61.1`, `package.json:11`); ditetapkan **per langkah**, tidak pernah aras job |
| Laluan | `bukti/plan-ci/<project>[-<shard>].json` |
| Skrip | `scripts/audit/assert-playwright-json.mjs` (D11 #15, Node 22 tulen) |
| Assertion | (1) fail wujud + `JSON.parse` — **hilang = gagal**; (2) bilangan ujian ≥ `--min-tests`; (3) tiada `skipped`/`timedOut`/`interrupted` merentas `suites[].specs[].tests[].results[]` (rekursif); (4) setiap `spec.ok === true`, `stats.unexpected === 0`, `stats.flaky === 0`; (5) `errors` kosong; (6) **skema tidak dikenali = gagal keras** |

Assertion (1) dan (6) ialah teras: keduanya menghalang "tiada bukti" daripada ditafsir sebagai
"tiada masalah". Lalai pembangun tempatan **tidak berubah** (tanpa `DIWAN_PW_JSON`, `line` sahaja
— tiada fail sampah dalam repo).

**Diintegrasi:** §1 F0(iv)(e) baharu; command literal lapis 1 (canary/`ci-guidance`/`ci-domain`
kini masing-masing menetapkan `DIWAN_PW_JSON` + memanggil skrip assert dengan `--min-tests`);
syarat `ci-ocr` #2 (command literal penuh dengan 4 env fixture + JSON + assert; nota bahawa
`--forbid-only` menangkap `test.only`, **bukan** `test.skip` bersyarat); YAML F0(iv)(d) (langkah
canary + shard); "Gate:" F0(iv); §9 metrik; §10 langkah 1; §11 D11 fail #15.

---

### P18-06 — Kiraan D11 bercampur → **TERIMA** (angka akhir berbeza daripada cadangan P18, dengan sebab)

**Disahkan:** v1.7 `:123` (*"D11 dikembangkan 4 → 12 artifak"*) dan `:323` (*"§11 D11 ditulis
semula (4 → 12)"*) bercanggah dengan `:1058-1082` dan `:3157` yang sudah menyenaraikan **14**.

**Angka muktamad v1.8: 16 fail repo + 1 artifak audit** — bukan `15 + 1` seperti dicadang P18.
Sebabnya bukan pertikaian: cadangan 15+1 dibuat dengan mengandaikan hanya P18-04 menambah fail.
Menerima **P18-05** menambah satu fail repo lagi (`scripts/audit/assert-playwright-json.mjs`),
kerana Playwright tiada flag "gagal jika di-skip" dan pemeriksaan itu memerlukan kod. Alternatif
yang dipertimbang dan ditolak (didokumen dalam pelan): (a) `node -e` sebaris dalam YAML — tidak
boleh diuji, tidak boleh diguna semula oleh tiga gate; (b) custom reporter — juga fail baharu,
dan lebih terikat kepada API dalaman Playwright.

**Penomboran (v1.8):** #14 = ujian antivirus (baharu) · #15 = skrip assert JSON (baharu) ·
#16 = `tests/fixtures/ocr/*` (dulu #14, kekal **bersyarat**) · #17 = manifest audit (dulu #15,
**bukan** fail repo). Rujukan sedia ada "D11 fail #2" (§9.1a) dan "D11 fail #11" (§9.1a) **tidak**
terjejas.

**Diintegrasi:** log versi (`:123` ditulis semula + blok v1.8), §0.5c baris P16-03, §1 F0(iv-a)
(jadual + nota kiraan + nota kepada pemilik tentang 16 vs 15), §11 D11 (ditulis semula: 16+1,
"Luluskan 1–15; 16 terpulang", angka 12 dan 14 diisytihar **DIBATALKAN**), §12 baris F0.

---

### P18-07 — "Working tree bersih" tidak tepat → **TERIMA**

**Disahkan** (output ditampal §1.1): `git status --short` = `M HANDOVER.md` + 20 fail
perancangan `??`; `git status --short -- app resources tests e2e config .github docker
composer.json composer.lock package.json package-lock.json` = **0 baris**.

**Nota ketepatan terhadap P18:** ayat yang P18 petik (`PLAN-RR-STATUS.md:73`) merujuk keadaan
status **sebelum** Codex P18 mengemasnya. Fail status yang saya terima (P18 sebagai penulis
terakhir) **sudah** mengandungi rumusan yang betul di dua tempat (baris 45 dan 74-76), jadi
sebahagian pembaikan ini telah dilakukan oleh P18 sendiri. Penemuan itu tetap **diterima**, dan
bakinya dinaikkan daripada pembetulan sekali kepada **peraturan proses** supaya ia tidak berulang.

**Diintegrasi:** **§0.7 #7 baharu** — frasa "working tree bersih" **dilarang** dalam fail giliran
dan status; setiap giliran mesti menampal output **kedua-dua** command (kod aplikasi berskop +
repo penuh), selaras `CLAUDE.md:7` (dakwaan tanpa output = tidak siap). `PLAN-RR-STATUS.md`
dikemas mengikut bentuk itu.

---

## 3. Lokasi integrasi (ringkasan pemetaan)

| Penemuan | Seksyen yang diubah dalam `PELAN-PEMBAIKAN.md` v1.8 |
|---|---|
| P18-01 | §1 **F0(iv)(f) baharu** · F0(iv)(d) YAML (`name:` digugurkan) · "Gate:" F0(iv) · §9 metrik · §10 langkah 1 · kriteria siap **F2** (§3.7), **F3** (§4.8), **F5** (§6.6), **F7** (§8.x) · Lampiran B #13, #15 · log versi |
| P18-02 | §1 **F0(iv)(d) ditulis semula** (~120 baris literal + 4 nota kebolehjalanan) · log versi |
| P18-03 | §9.1a jadual command (`prepare` + baris Superadmin) · peraturan wrapper **1** dan **3** · Lampiran B #14 · log versi |
| P18-04 | **§0.6 S7 baharu** + nota · §1 F0(iv-a) **fail #14** · §1 F0(iv) syarat `ci-ocr` #3 (skop dihadkan) · §9 metrik (baris baharu) · §11 **D11** · log versi `:144-154` |
| P18-05 | §1 **F0(iv)(e) baharu** · command literal lapis 1 · syarat `ci-ocr` #2 · YAML F0(iv)(d) · "Gate:" F0(iv) · §9 metrik · §10 langkah 1 · §1 F0(iv-a) **fail #15** · §11 D11 |
| P18-06 | log versi (`:123`) · §0.5c (P16-03) · §1 F0(iv-a) jadual + nota · §11 **D11 ditulis semula** · §12 baris F0 |
| P18-07 | **§0.7 #7 baharu** · `PLAN-RR-STATUS.md` |
| Semua | **§0.5d baharu** — jadual keputusan P18-01…P18-07 dengan bukti dan lokasi |

---

## 4. Imbasan konsistensi (berprogram, selepas suntingan)

| # | Corak dicari | Hasil |
|---|---|---|
| 1 | `4 → 12` / `4 -> 12` sebagai **dakwaan aktif** | **0** — satu-satunya padanan tinggal ialah **petikan sejarah** dalam §0.5d P18-06 (*"masih `"4 → 12"`"*), yang menerangkan apa yang dibetulkan. Sengaja dikekalkan |
| 2 | `12 artifak` | **0** |
| 3 | `14 fail` | **0** |
| 4 | `results.json` sebagai **arahan** | **0** — 5 padanan tinggal semuanya menerangkan **mengapa ia tidak wujud** (log versi, §0.5d, §1 F0(iv)(e), syarat `ci-ocr`, §9 metrik) |
| 5 | `ci-domain` sebagai **required check** | **0** — semua rujukan kini "step dalam job `integration`" atau "wajib hijau"; 2 padanan menerangkan mengapa ia **bukan** check |
| 6 | Placeholder YAML (`, ... }`, `sama lapis 1`) sebagai **kontrak** | **0** — 4 padanan tinggal ialah petikan dalam penjelasan pembetulan (log versi, §0.5d, §1 F0(iv)(d) nota) |
| 7 | `working tree bersih` sebagai **dakwaan** | **0** dalam ketiga-tiga fail yang P19 tulis; 2 padanan dalam pelan ialah larangan itu sendiri. *(`PLAN-RR-11-CLAUDE-AUDIT-LENGKAP.md:3` mengandungi frasa lama — fail sejarah, **di luar** senarai fail yang P19 dibenarkan sunting; direkod di sini, tidak diubah)* |
| 8 | `name: Guidance coverage gate` sebagai nama job baharu | **0** — hanya wujud dalam jadual ralat §1 F0(iv)(f) dan §0.5d yang menerangkannya |
| 9 | Nombor fail D11 (`fail #2`, `#11`, `#14`, `#15`) | konsisten — #2/#11 tidak berubah, #14/#15 merujuk fail baharu di kedua-dua §0.6/§1/§11 |
| 10 | `16 fail repo` | 8 padanan, semuanya sepadan (log versi, §0.5d, F0(iv-a), D11, §12) |

**Semakan fakta kod yang dijalankan P19 (baca sahaja, tiada mutasi):**
`.github/workflows/ci.yml` (penuh) · `playwright.config.js` (penuh) ·
`app/Services/InboxIngestService.php` (penuh) · `app/Services/AntivirusScanner.php` (penuh) ·
`config/diwan.php:25-45` · `tests/Feature/GuidanceSupportTest.php:80-115` ·
`tests/Feature/DdmsExtendedCapabilitiesTest.php:140-160` · `e2e/guidance.spec.js:1-60` ·
`e2e/ocr-upload.spec.js:1-20` · `app/Jobs/ProcessOcrJob.php:1-60` · `package.json` ·
`git status`/`git log`. **Setiap** nombor baris baharu yang dimasukkan ke dalam v1.8 diambil
daripada bacaan ini, bukan daripada pelan.

---

## 5. Mengapa pelan MASIH belum muktamad

1. **Peraturan penutupan belum dipenuhi.** §0.7 / `PLAN-RR-STATUS.md` #6 menuntut **satu pusingan
   penuh tanpa penambahbaikan substantif**. P18 menemui 7 pindaan substantif dan **kesemuanya
   diterima** — jadi pusingan terakhir bukan sahaja tidak kosong, ia menghasilkan +403 baris
   kontrak baharu. Codex P20 mesti mengaudit integrasi ini sebelum sebarang penutupan
   dipertimbangkan.
2. **Kontrak baharu v1.8 belum pernah disemak oleh mana-mana pihak kedua.** YAML literal ~120
   baris, kontrak reporter JSON, skema assertion Playwright, dan pemisahan kredensial superadmin
   semuanya ditulis dalam giliran ini. Sejarah round-robin ini (P6/P8 palsu → penutupan P9
   dibatalkan → P10 menemui 8 bloker) menunjukkan tepat apa yang berlaku apabila integrasi
   dianggap betul tanpa audit bebas.
3. **Keputusan pemilik masih tertunggak dan D11 kini berubah.** D8/D9/D11 belum dijawab dan draf
   Addendum spec v2.6 (D10) belum wujud. **D11 khususnya berubah dalam giliran ini** (14 → 16 fail
   repo, termasuk satu item **keselamatan**) — pemilik mesti melihat senarai muktamad, bukan
   senarai perantaraan.
4. **Snapshot §0.7 #2 masih tidak pernah dibuat** (P15, P17, P18, P19 semuanya dilarang git).
   Selagi folder `Audit Review Round Robin/` tidak dikomit, setiap dakwaan sejarah tentang teks
   v1.6/v1.7/v1.8 kekal **tidak boleh disahkan**, dan itu sendiri kecacatan proses yang belum
   ditutup.

---

## 6. Untuk Codex Pusingan 20 — fokus yang dicadangkan

Semak **tujuh penutupan** di atas, dan khususnya perkara yang P19 cipta baharu (kerana itulah
permukaan risiko terbesar):

1. **YAML F0(iv)(d)** — sahkan setiap nilai terhadap `.github/workflows/ci.yml` sebenar
   (services/env/steps), dan nilai keputusan **tidak** menyalin `Install OCR tooling`: adakah
   mana-mana laluan `guidance-full` yang dirancang boleh memuat naik dokumen?
2. **Kontrak reporter (F0(iv)(e))** — sahkan bentuk JSON reporter `@playwright/test ^1.61.1`
   (`suites`/`specs`/`tests`/`results`/`stats`) dan sama ada assertion (3)/(4) mencukupi untuk
   ujian bersarang `describe`. Jika skema sebenar berbeza, itu pindaan substantif.
3. **Nama status check** — sahkan andaian "tiada `name:` → check name = job id" dan bentuk nama
   check job matriks (`guidance-e2e (screen)`), termasuk sama ada tanda kurung/spasi
   mempengaruhi tetapan branch protection.
4. **§9.1a Pilihan A** — adakah `production-guidance-readonly.spec.js` boleh benar-benar
   berjalan tanpa `prepare` menulis apa-apa superadmin, dan adakah ujian penjaga "output tidak
   mengandungi `SUPERADMIN`" boleh dilaksanakan seperti ditulis?
5. **Ujian antivirus (fail #14)** — adakah assertion "0 log aktiviti" boleh dibuktikan dengan
   bersih memandangkan `MosqueActivityLogger` dipanggil selepas transaksi, dan adakah
   `Storage::fake()` diperlukan untuk membuktikan "0 media"?
6. **Kiraan D11 = 16 + 1** — sahkan tiada fail repo lain diseludup masuk oleh kontrak baharu v1.8
   (khususnya: adakah reporter JSON memerlukan perubahan `.gitignore` untuk `bukti/plan-ci/`?).

---

*Ditulis oleh Claude Pusingan 19. Fail yang disunting dalam giliran ini: **`PELAN-PEMBAIKAN.md`**,
**`PLAN-RR-19-CLAUDE.md`**, **`PLAN-RR-STATUS.md`** — tiada yang lain. Tiada kod aplikasi
disentuh; tiada git/SSH/deploy/ujian mutasi dijalankan.*
