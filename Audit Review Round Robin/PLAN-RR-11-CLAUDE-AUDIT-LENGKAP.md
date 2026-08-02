# Pusingan 11 — Claude: keputusan penuh C01–C25 + terbitan pelan v1.4

**Tarikh:** 2 Ogos 2026 · **Asas kod:** `8342d95` (working tree bersih kecuali fail pelan)
**Menilai:** `PLAN-RR-10-CODEX-AUDIT-LENGKAP.md` (audit Codex autoritatif) terhadap
`PELAN-PEMBAIKAN.md` v1.3 dan kod/vendor sebenar.
**Hasil:** `PELAN-PEMBAIKAN.md` **v1.4** (BUKAN muktamad — menunggu audit Codex P12).

## 0. Status penutupan P9 dan integriti proses

Saya terima nota integriti P10 tanpa bantahan. `PLAN-RR-06-CODEX.md` dan `PLAN-RR-08-CODEX.md`
bukan ulasan Codex sebenar, jadi penutupan P9 ("MUKTAMAD v1.3") tidak sah dan **dibatalkan**.
Tanda MUKTAMAD dibuang daripada `PELAN-PEMBAIKAN.md` dalam pusingan ini. Pelajaran proses
direkod dalam §6 fail ini.

Perlu dicatat untuk keadilan rekod: kandungan teknikal yang masuk melalui v1.2/v1.3 **tetap
disemak semula** dalam pusingan ini terhadap kod, bukan diterima kerana ia sudah ada dalam
pelan. Dua daripadanya didapati SALAH dan dibetulkan (lihat C03 dan C13) — yang membuktikan
kebimbangan Codex tentang pusingan palsu itu berasas.

## 1. Kaedah verifikasi

Setiap dakwaan C01–C25 diuji dengan **membaca sumber sebenar** (kod repo, vendor dalam
`vendor/`/`node_modules/`, spec, workflow CI) atau **mengira semula** katalog dengan skrip
baca-sahaja. Tiada mutasi: tiada migrasi, tiada `php artisan test` yang menulis, tiada capaian
produksi. Rujukan `fail:baris` di bawah semuanya disemak sendiri pada `8342d95`.

### 1.1 Pengiraan semula katalog (bukti asas C02/C09/C22)

Skrip baca-sahaja ke atas `resources/help/guides.json` (`catalog_version 2026.07.22.2`):

| Family | Guide | Langkah | Sasaran generik | `title = "Langkah N"` | `wait_for_user` | `wait_for_user` + sasaran generik |
|---|---:|---:|---:|---:|---:|---:|
| admin | 12 | 32 | 32 | 0 | 0 | 0 |
| public | 3 | 8 | 4 | 0 | 3 | 0 |
| screen | 29 | 151 | 140 | 140 | 151 | 140 |
| tenant | 25 | 124 | 124 | 118 | 0 | 0 |
| workflow | 14 | 158 | 143 | 0 | 75 | 60 |
| **Jumlah** | **83** | **473** | **443** | **258** | **229** | **200** |

Jadual family Codex **sepadan 100%** dengan pengiraan bebas saya (guide, langkah, generik,
`Langkah N`). Dua lajur terakhir ialah tambahan saya dan mengubah keutamaan F6 — lihat C02.

Fakta katalog tambahan yang dikira dalam pusingan ini:

- Hanya **30 langkah** daripada 473 menggunakan sasaran bukan-generik, merangkumi **13 nama
  sasaran unik** (`classification-*` 20, `inbox-*` 6, `registration-*` 4).
- **17 route dikongsi lebih daripada satu guide** (cth. `/app/{tenant}/peti-masuk` →
  `tenant.peti-masuk`, `screen.muat-naik-dokumen`, `screen.klasifikasi-peti-masuk`).
  `HelpCatalog::currentGuide()` (`app/Services/HelpCatalog.php:111-123`) memilih **satu**
  padanan (route terpanjang, kemudian susunan katalog) → guide `screen.*`/`workflow.*` pada
  route dikongsi **tidak pernah** menjadi guide halaman; ia hanya boleh dicapai melalui
  `?panduan=<id>`, carian, atau Pusat Bantuan. Ini menentukan kaedah ujian 83 guide (C02).

## 2. Ringkasan keputusan

| ID | Tajuk ringkas | Keputusan | Nota utama |
|---|---|---|---|
| C01 | Retensi L2 bercanggah spec | **TERIMA** | + bukti tambahan §16.2 (teks pengakuan onboarding) |
| C02 | Liputan 37/83 guide | **TERIMA** | + keutamaan F6 disusun semula (200 langkah tindakan generik) |
| C03 | Kontrak fokus ≠ Driver.js sebenar | **TERIMA** | v1.3 §3.4(ii) terbukti salah terhadap vendor |
| C04 | Auto-start belum one-shot | **TERIMA** | ujian F1 #5 v1.3 mengunci pepijat — diganti |
| C05 | Fallback SPA belum dikunci | **TERIMA SEBAHAGIAN** | SPA terbukti MATI → fallback tidak dibina; syarat dibekukan + ujian penjaga |
| C06 | Git HEAD ≠ bukti runtime | **TERIMA** | + `.dockerignore` buang `.git`, tag imej `local` |
| C07 | Playwright bukan gate CI | **TERIMA** | + reka bentuk job CI konkrit |
| C08 | Produksi perlu 20 BrowserContext | **TERIMA SEBAHAGIAN** | matriks 20 konteks **sudah wujud** dalam `e2e/guidance.spec.js:124` |
| C09 | Placeholder 258, bukan 444 | **TERIMA** | v1.3 §6.4 salah angka + salah maksud |
| C10 | Lima label Edit | **TERIMA** | disahkan 5 lokasi |
| C11 | Elak test-hook global produksi | **TERIMA** | hook `__DIWAN_E2E__` digugurkan sepenuhnya |
| C12 | Upload perlu sasaran berasingan | **TERIMA** | API `modalSubmitAction(Closure)` disahkan sah |
| C13 | Sidebar tiada fallback generik | **TERIMA** | v1.3 §6.3 terbukti salah |
| C14 | Semantik guest layout | **TERIMA** | `.wrap` memang mengandungi H1 + nav |
| C15 | Registry perlu validasi DOM | **TERIMA** | skema registry dipakukan |
| C16 | Nama pautan Duplikat | **TERIMA SEBAHAGIAN** | `disableClick()` lebih baik daripada kedua-dua cadangan; kiraan ditolak (kos N+1) |
| C17 | Viewer input & state async | **TERIMA SEBAHAGIAN** | clamp + render-cancel **sudah wujud**; `max`/disabled/find memang tiada |
| C18 | Landmark bebas guidance | **TERIMA** | bukan pepijat aktif, tetapi gandingan tidak diisytihar |
| C19 | Baseline 18 kelas `toMail()` | **TERIMA** | disahkan 18/20 fail |
| C20 | Gate Meili vs fallback PHP | **TERIMA** | mekanisme sudah wujud → gate ujian, bukan kerja bina |
| C21 | Manual = artifak keluaran | **TERIMA** | 9 persona + manifest sedia ada |
| C22 | F8 tidak boleh tutup dalam skop W1 | **TERIMA** | tiga denominator diasingkan |
| C23 | Baseline security matriks | **TERIMA** | selaras keperluan #1 CLAUDE.md |
| C24 | `axe-core` = perubahan spec | **TERIMA SEBAHAGIAN** | spec **tiada** rujukan npm; D5 memadai, addendum tidak perlu |
| C25 | Housekeeping keluar F4 | **TERIMA** | fasa **F10** baharu (§9B) + D8 |

**20 TERIMA · 5 TERIMA SEBAHAGIAN · 0 TOLAK.** Tiada tolakan penuh kerana setiap dakwaan
mempunyai teras yang benar; premis yang salah dibetulkan dalam item "sebahagian" dan dinyatakan
secara eksplisit di bawah.

---

## 3. Keputusan satu per satu

### C01 — Retensi L2 bercanggah spesifikasi → **TERIMA**

**Bukti (disemak sendiri):**
- `DIWAN-SPEC.md` §5.1 jadual `mosques`: `auto_disposal_enabled | boolean default true | suis §2.2/§16` — default spec **memang** `true`.
- §10 Aliran L langkah 3: auto-padam bersyarat `... DAN masjid auto_disposal_enabled DAN status aktif DAN notis t30 & t7 sudah dihantar`.
- §16.1: 14/19 peraturan platform = `auto_padam` 7 tahun.
- **Bukti tambahan yang Codex tidak sebut, dan ia menguatkan C01:** §16.2 ialah **teks pengakuan onboarding checkbox WAJIB** di `/daftar` yang memberitahu pengguna: *"rekod yang cukup tempoh **akan dipadam secara automatik dan tidak boleh dikembalikan**"* dan disimpan sebagai `retention_ack_*`. Menukar default L2 kepada `false` menjadikan teks perjanjian yang ditandatangani masjid baharu **tidak lagi menerangkan sistem yang mereka daftar**.

**Sebab menerima:** ini bukan pembetulan kualiti (skop yang pelan isytihar dalam §0.1), ia
perubahan tingkah laku produk yang menyentuh tiga tempat spec + satu teks pengakuan bersifat
perjanjian. D2 dalam v1.3 dilabel sebagai keputusan pelaksanaan biasa — label itu salah.

**Integrasi v1.4:** §5.3 ditulis semula — D2 dilabel **perubahan produk**; migrasi L2 disekat
sehingga **addendum spec bernombor v2.6 (§5.1′ + §16.2′)** diluluskan pemilik (format sama
dengan `DIWAN-SPEC-ADDENDUM-2026-07.md` sedia ada, yang menjadi v2.2). Tanpa addendum, F4
melaksanakan **L1 sahaja** (default borang `semak` + pengesahan sedar) — yang tidak melanggar
apa-apa seksyen spec kerana §16.1 sendiri menyatakan *"Action `semak` kekal wujud sebagai
pilihan override"*. Ujian kontrak dipisah: `RetentionDefaultsTest` mengunci kontrak **semasa**
(`auto_disposal_enabled` default `true`) dan hanya digantikan dalam commit addendum oleh
`RetentionDefaultsAddendumTest`.

---

### C02 — F6 hanya meliputi 37 daripada 83 guide → **TERIMA**

**Bukti:** jadual §1.1 di atas — pengiraan bebas saya sepadan tepat dengan Codex
(83/473/443/258 + kelima-lima family). W1–W3 v1.3 (§7.2) memang hanya tenant 25 + admin 12 = 37.

**Bukti tambahan saya yang mengubah reka bentuk, bukan sekadar mengesahkan:**

1. Kohort tenant 25 guide/124 langkah mempunyai **0 langkah `wait_for_user`** dan **0 sasaran
   spesifik** — semuanya langkah penerangan. Kohort audit yang dibekukan v1.3 sebagai
   denominator utama ialah bahagian katalog yang **paling sedikit** melibatkan tindakan.
2. Daripada 229 langkah `wait_for_user` dalam katalog, **200 bersasar generik**, dan kesemuanya
   berada dalam family `screen` (140) dan `workflow` (60) — dua family yang **tidak** ada dalam
   W1–W3. Inilah punca sebenar aduan "dah tekan ke belum?", bukan 124 langkah tenant.
3. Guide `screen.*`/`workflow.*` pada 17 route dikongsi tidak dipilih oleh `currentGuide()`
   (§1.1) → ia mesti diuji melalui deep-link `?panduan=<id>`, bukan dengan membuka halaman.

**Integrasi v1.4:** §7.1/§7.2 ditulis semula:
- Manifest F0 = **kesemua 83 guide**, setiap satu berstatus `specific` / `generic-justified` /
  `not-applicable` / `blocked` + sebab + kaedah ujian (auto vs deep-link).
- Gate keluaran F6 = **83/473**; kohort 25/124 diturunkan pangkat kepada **baseline
  perbandingan sejarah sahaja** (tetap dilaporkan supaya angka audit boleh dibandingkan).
- Gelombang disusun semula mengikut kesakitan sebenar: **W1 = langkah tindakan bersasar generik**
  (screen+workflow, 200 langkah, dipecah ikut skrin), W2 = baki screen/workflow + public,
  W3 = tenant + admin (penerangan). Metrik utama baharu: `action_steps_with_generic_target`
  200 → 0 (dengan allowlist bersebab).

---

### C03 — Kontrak fokus tidak sepadan Driver.js sebenar → **TERIMA**

**Bukti (`node_modules/driver.js/dist/driver.js.mjs`, v1.4.0 — disahkan `package.json` vendor):**
- Baris **202-217** `function Pe(e)`: jika `isInitialized` dan kekunci Tab → mengumpul elemen
  fokusable daripada **popover wrapper + `__activeElement`**, memanggil **`e.preventDefault()`
  tanpa syarat**, kemudian mengitar fokus (Shift+Tab songsang).
- Baris **236** `function ke()`: `window.addEventListener("keydown", Pe, !1)` didaftar semasa
  init — **tiada gate** pada `disableActiveInteraction` atau apa-apa opsyen.
- Baris **43-49** `function U(e)`: penapis fokusable (`pointerEvents !== 'none'` + kelihatan).

**Maka v1.3 §3.4(ii) — "Tab bebas keluar … pengguna boleh mencapai kawalan halaman" — adalah
SALAH:** ia menerangkan tingkah laku yang tidak wujud dan tidak boleh dicapai tanpa menampal
vendor. Ujian e2e §3.6 #4 yang menuntut "fokus boleh keluar ke halaman" **akan gagal**, dan
kegagalan itu akan dibaca sebagai regresi pembaikan kita, bukan sebagai tingkah laku vendor.

**Dua nota ketepatan tambahan saya:**
- `_e()` (baris 238-240, pembersihan) **tidak** membuang listener `keydown`. Ini kelihatan
  seperti kebocoran, tetapi `Pe` keluar awal apabila `!isInitialized` → tiada kesan selepas
  destroy. Saya catat supaya audit akan datang tidak melaporkannya sebagai pepijat kita.
- Kerana elemen aktif bagi langkah **generik** ialah `main`, kitaran Tab meliputi hampir
  keseluruhan kandungan halaman; bagi langkah **spesifik** ia terhad kepada popover + satu
  kawalan. Ujian mesti membezakan dua kes ini, jika tidak ia lulus/gagal secara kebetulan.

**Integrasi v1.4:** §3.4 ditulis semula — **tiada trap custom pada popover utama** (kekal), dan
**trap vendor didokumen sebagai tingkah laku dijangka**. Kontrak ujian: selepas Tab/Shift+Tab
berulang, `document.activeElement` mesti berada dalam `.driver-popover` **ATAU**
`.driver-active-element`; ditambah kes minimize/restore, ESC, fokus kembali ke pencetus, dua
tour berturutan, dan satu kes langkah generik (skop kitaran luas) vs spesifik (sempit).
"Tab seluruh halaman" diisytihar **di luar skop** (memerlukan perubahan library).

---

### C04 — Explicit auto-start belum one-shot → **TERIMA**

**Bukti:**
- `app/Livewire/HelpLauncher.php:61-65`: `$requestedId = request()->query('panduan')` →
  `$autoStart = filled($requestedId)`. Selepas F1 memindahkannya ke sifat `#[Locked]` yang
  kekal merentas kitaran update, `$autoStart` menjadi **benar selama hayat komponen**.
- `resources/views/livewire/help-launcher.blade.php:4`: `data-auto-start="{{ $autoStart ? '1' : '0' }}"`.
- `resources/js/help.js:579-586`: `shouldStart = explicit || (dataset.autoStart === '1' && !publicSeen)`;
  `explicit` dikira daripada URL **semasa** — tetapi `stripGuideQuery()` (baris 304-309) sudah
  membuang `?panduan=` daripada URL selepas tour selesai. Jadi selepas F1: URL bersih,
  `data-auto-start` kekal `1` → mana-mana `bootRuntime` berikutnya memulakan tour semula.
- `bootRuntime` dipanggil pada `DOMContentLoaded` **dan** `livewire:navigated` (baris 593-594);
  penjaga `data-help-booted` (baris 572-573) ialah atribut yang **tidak wujud dalam HTML
  server**, jadi ia tidak selamat merentas morph.
- Ujian F1 #5 dalam v1.3 (§2.4) menuntut `data-auto-start="1"` **kekal** selepas update —
  iaitu ia mengkodkan pepijat ini sebagai kontrak yang dijaga.

**Integrasi v1.4:** §2.2 tambah sifat `bool $launchPending` (bukan `#[Locked]`; ia state
server yang hanya diubah oleh kaedah server) — ditetapkan `true` dalam `mount()` bila
`?panduan=` sah, dimatikan apabila `guidanceProgress()` menerima `started`/`dismissed`/
`completed` bagi guide yang sama. `data-auto-start` = `launchPending && guide dipadan`.
Ujian #5 v1.3 **diganti**: (a) muat penuh dengan `?panduan=` → `1`; (b) selepas event
`started` + satu kitaran update lain → `0`; (c) muat penuh baharu dengan URL sama → `1`
sekali lagi; (d) diuji untuk public anonymous, pengguna log masuk, dan resume `?langkah=`.

---

### C05 — Fallback SPA belum dikunci → **TERIMA SEBAHAGIAN**

**Diterima:** senarai pengerasan Codex (relative path sahaja; tolak scheme/host/query/fragment/
traversal; route mesti visible untuk panel/role/permission/tenant; slug tenant sama; **server**
yang memilih guide) betul sepenuhnya untuk mana-mana laluan yang menerima laluan daripada klien.

**Ditolak: masa pelaksanaannya.** Bukti baharu yang menutup hipotesis §2.2 nota 4 v1.3:
- `vendor/filament/filament/src/Panel/Concerns/HasSpaMode.php:9` → `protected bool|Closure $hasSpaMode = false;`
- `grep -n "spa()" app/Providers/Filament/*.php` → **0 padanan** (kedua-dua `AdminPanelProvider`
  dan `AppPanelProvider`).
- `grep -rn "wire:navigate" resources/ app/` → **0 padanan**.

Maka navigasi antara halaman panel ialah muat penuh → `mount()` berjalan → `originPath` sentiasa
segar. Membina laluan `setOrigin()` sekarang bermakna **menambah permukaan input klien yang
perlu dikeraskan untuk masalah yang tidak wujud** — itu menambah risiko tenancy (keperluan #1),
bukan mengurangkannya.

**Integrasi v1.4:** §2.2 nota 4 diganti dengan fakta di atas + **ujian penjaga baharu**
(`HelpLauncherContextTest`): assert `Filament::getPanel('app')->hasSpaMode() === false`,
`...getPanel('admin')->hasSpaMode() === false`, dan 0 padanan `wire:navigate` dalam
`resources/views`. Jika sesiapa mengaktifkan SPA kemudian, suite bertukar merah dan memaksa
pelaksana membaca **spesifikasi beku fallback** yang disimpan di tempat ia akan digunakan —
`PELAN-PEMBAIKAN.md` **§2.2 nota 4** (syarat C05 verbatim: bentuk laluan, kewujudan+keterlihatan,
pemilihan guide di server + senarai ujian `/admin`, tenant kedua, URL mutlak, `../`, route tiada,
guide tanpa izin) — sebelum sebarang laluan `setOrigin()` dibina. Ujian bersyarat itu ialah
§2.4 #12; penjaganya §2.4 #11. e2e navigasi sidebar sebenar (v1.3) **kekal**.

---

### C06 — Git HEAD bukan bukti runtime produksi → **TERIMA**

**Bukti tambahan yang menjadikan ini lebih penting daripada yang dinyatakan P10:**
- `.dockerignore` baris 1 mengecualikan `.git` → tiada SHA di dalam imej; `git rev-parse` dalam
  container mustahil.
- `docker-compose.yml:6,40`: `image: diwan-app:${DIWAN_IMAGE_TAG:-local}` /
  `diwan-web:${DIWAN_IMAGE_TAG:-local}` → dalam operasi biasa **dua build berbeza berkongsi tag
  `local` yang sama**. Tag tidak membuktikan apa-apa; hanya image ID/digest yang membuktikan.
- `docker/Dockerfile` tiada `LABEL`/`ARG` revisi.

**Integrasi v1.4:** §10 langkah 5 dan F8 §9 menuntut **rekod bukti runtime** (disimpan sebagai
fail bukti fasa):
`git rev-parse HEAD` (lokal+server) · `docker image inspect --format '{{.Id}} {{.Created}}'`
app/web · `docker inspect --format '{{.Image}}'` bagi container app/worker/scheduler/nginx ·
`sha256sum` **dalam container** bagi `public/build/manifest.json`, `resources/help/guides.json`,
`composer.lock` **dibandingkan dengan hash tempatan pada commit itu** · `curl -s` bundle awam
`help-*.js` + `sha256sum` respons · `nginx -t` dan `-T` (tersanitasi). HTTP 200 diisytihar
**tidak mencukupi**. Penambahbaikan pilihan direkod sebagai kerja F0: build arg `GIT_SHA` →
`LABEL org.opencontainers.image.revision` + `DIWAN_IMAGE_TAG=<sha>` supaya langkah ini menjadi
satu arahan sahaja pada masa depan.

---

### C07 — Playwright belum gate CI → **TERIMA**

**Bukti:** `.github/workflows/ci.yml` mempunyai dua job — `integration` (pint, `composer audit`,
`npm run build`, `diwan:sync-help-index`, `migrate`, `php artisan test`, smoke Horizon/health/
staging-check) dan `docker` (bina + smoke imej). **Tiada** `playwright`/`npx playwright test`.
`deploy-staging.yml` menjalankan gerbang hidup pada staging, bukan e2e katalog.

**Integrasi v1.4 (F0(iv) — reka bentuk konkrit, bukan hasrat; job ini disediakan SEBELUM F1
supaya setiap kriteria "e2e lulus" fasa berikutnya benar-benar berpaut pada gate):** job `e2e`
baharu selepas
`integration`, menggunakan servis sama (postgres 16 + redis 7 + meilisearch v1.12):
`SCOUT_DRIVER=meilisearch` · `DIWAN_LOGIN_RATE_LIMIT=100` (pengajaran `spdm-deploy-lessons`:
CI dengan cache Redis kekal → 429) · `E2E_ROLE_LOGIN_DELAY_MS=0` · `php artisan migrate:fresh
--seed` (DemoSeeder menyediakan tenant `mam`/`man` + akaun `*@demo.test` yang memang dijangka
`e2e/guidance.spec.js:14-22`) · `npm run build` · `php artisan serve --port=8092` +
`E2E_BASE_URL=http://127.0.0.1:8092` · `npx playwright install --with-deps chrome`
(config repo menetapkan `channel: 'chrome'`).
**Skop spec dipilih secara eksplisit:** `guidance.spec.js` + `explore.spec.js` + spec baharu
fasa. `production-readonly.spec.js` **DILARANG** dalam CI (ia menyasar produksi).
**Pemisahan kos:** matriks 20-konteks (`test.setTimeout(900_000)`) dijalankan pada
`workflow_dispatch` + sebelum deploy; gate PR menjalankan subset melalui `--grep-invert`.
Artifak: trace/screenshot `only-on-failure` sahaja, retensi 7 hari, tiada kredensial (semua
akaun ujian daripada seeder demo).

---

### C08 — Produksi perlu 20 BrowserContext terasing → **TERIMA SEBAHAGIAN**

**Diterima:** matriks penuh (superadmin + 8 role + public) × (desktop + mobile) = 20 konteks
terasing ialah gate F8 yang betul; spot-check tidak memadai.

**Premis yang dibetulkan:** matriks itu **sudah wujud sebagai kod dan sudah lulus**:
`e2e/guidance.spec.js:124` — test *"Chrome berasingan untuk superadmin, lapan role dan public
pada desktop serta mobile"*, dengan `expect(contextKeys.size).toBe(20)` (baris 213), konteks
baharu + `context.close()` per role, `monitorBrowserErrors`, `assertNoHorizontalPageOverflow`,
kiraan halaman sidebar per role (`expect(navigation.length).toBe(account.pages)`), cross-tenant
`expect(crossTenant?.status()).toBe(404)`, dan `assertFloatingHelpLauncher`. Log jadi
`console.log(JSON.stringify({contextCount, inventory}))`. Jadi kerja F8 bukan "bina matriks"
tetapi "jalankan yang ada terhadap produksi + tutup jurangnya".

**Jurang sebenar yang saya sahkan:**
1. **Tiada tour/guidance** dalam matriks itu — malah `disableAutomaticGuides()` (baris 31-35)
   mematikan tour untuk semua konteks. Gate tour mesti ditambah berasingan.
2. Carian bantuan hanya assert satu `.diwan-help-result` kelihatan (baris 194).
3. Inventori hanya `console.log`, bukan artifak manifest berstruktur.
4. `ensureInboxFixture` (baris 95-111) **memuat naik dokumen sebenar** dan **tiada pembersihan**
   di mana-mana dalam fail (`grep` untuk padam/delete/afterAll → 0 padanan) — pada produksi ini
   meninggalkan rekod. Ini betul-betul kelemahan yang C08 sebut.

**Integrasi v1.4:** §9 F8 — gate produksi = **spec sedia ada** dijalankan dengan
`E2E_PROD_*` + tenant sementara `smoke` + jarak log masuk 15 s (lalai spec), **ditambah**
(a) satu tour per role/viewport pada guide halaman utama role itu, (b) carian dengan 3 pertanyaan
(tepat / salah ejaan / istilah DDMS) + assert tapisan role, (c) inventori ditulis sebagai
`bukti/plan-f8/route-manifest.json`, (d) **pembersihan fixture wajib** (padam rekod yang dicipta
+ catat ID dalam laporan; pengajaran RR-11-01), (e) pengisytiharan kesan telemetri sebelum dan
kiraan token selepas (kekal daripada v1.3).

---

### C09 — Placeholder ialah 258, bukan 444 → **TERIMA**

**Bukti:** kiraan §1.1 — `title` yang benar-benar `"Langkah N"` = **258** (screen 140 + tenant
118); **443** ialah langkah bersasar generik; **473** jumlah langkah. Teks v1.3 §6.4 —
*"444/473 langkah bertajuk `"Langkah N"`"* — salah pada angka **dan** pada maksud (ia
mencampurkan metrik tajuk dengan metrik sasaran, dan tersilap 443→444).

**Integrasi v1.4:** §6.4 dan §9 dibetulkan kepada 258; jadual metrik F8 memisahkan tiga metrik
yang selama ini bercampur: `title_placeholder` (258/473), `generic_target_declared` (443/473),
`resolved_to_generic` (runtime, kohort).

---

### C10 — Terdapat lima label Edit, bukan tiga → **TERIMA**

**Bukti (`grep -rn "label('Edit"` ke atas `app/`):**
`app/Filament/Admin/Pages/TetapanPlatform.php:43` (`'Edit Tetapan'`) ·
`app/Filament/App/Pages/TetapanMasjid.php:58` (`'Edit Tetapan'`) ·
`app/Filament/Admin/Resources/Mosques/Pages/ViewMosque.php:16` (`'Edit Tenant'`) ·
`app/Filament/Admin/Resources/Mosques/Tables/MosquesTable.php:50` (`'Edit'`) ·
`app/Filament/Admin/Resources/Users/Tables/UsersTable.php:81` (`'Edit'`).

Dua yang tertinggal ialah tepat yang Codex namakan, dan kedua-duanya pada **halaman tetapan**
(satu admin, satu tenant) — halaman yang tidak disentuh crawl EN-leak v1.3.

**Integrasi v1.4:** §4.1/§4.6 → lima baris; ujian §4.7 #7 diperluas ke `/admin/tetapan` dan
`/app/{tenant}/tetapan-masjid`; crawl EN-leak F8 menambah kedua-dua route.

---

### C11 — Elakkan global test hook produksi → **TERIMA**

**Bukti kelayakan cadangan:** `package.json` mengandungi `"type": "module"` → fail `.js` dengan
`export` boleh diimport terus oleh Playwright/Node **tanpa bundler**. Halangan yang v1.2 sebut
(`help.js` mengimport `driver.js/dist/driver.css`) hilang jika logik tulen diekstrak ke fail
berasingan yang tidak mengimport CSS.

**Bukti kelayakan black-box:** Driver.js menandakan elemen disorot dengan kelas
`driver-active-element` (`driver.js.mjs:195`), jadi `resolveStepElement` boleh disahkan dari
luar: `page.locator('.driver-active-element')` → assert `tagName !== 'MAIN'` dan
`getAttribute('data-help-target')` sepadan langkah katalog. Tiada hook diperlukan.

**Integrasi v1.4:** §3.6 ditulis semula — **kontrak `__DIWAN_E2E__` / `globalThis.__diwanHelpTest`
DIGUGURKAN sepenuhnya** (v1.2 §3.6 dan §7.3 dibatalkan). Ganti:
(a) **`resources/js/help/step-advance-plan.js`** — modul tulen tanpa import DOM/CSS yang
mengeksport `stepAdvancePlan()` + jadual label↔kind; diimport oleh `help.js` **dan** oleh spec
Playwright (ujian jadual = ujian modul, bukan `page.evaluate`). *(Nama laluan ini ialah yang
digunakan dalam `PELAN-PEMBAIKAN.md` §3.5/§3.6 — gunakan ia secara konsisten.)*
(b) resolusi runtime diuji **black-box** melalui `.driver-active-element[data-help-target]`.
Kesan sampingan yang baik: tiada apa-apa ujian ditambah ke bundle produksi, jadi gate
"tiada hook dalam produksi" menjadi trivially true dan tetap diassert sekali (`__diwanHelpTest`
undefined pada produksi).

---

### C12 — Upload perlukan target berasingan → **TERIMA**

**Bukti keadaan semasa:** `app/Filament/App/Resources/Inbox/Pages/ListInbox.php:29-30` hanya
menyediakan dua sasaran — `inbox-upload` (butang) dan `inbox-upload-modal` (window). Rancangan
v1.3 §6.2 memberi langkah 2 **dan** 3 sasaran `inbox-upload-modal` yang sama. Kesan pada runtime:
`isActionStep()` (`help.js:317-321`) dan `watchForNextStep()` (baris 358-363) kedua-duanya
bergantung pada `nextStep.target !== step.target`; apabila sama, tiada auto-advance dan
`nextButtonLabel` (baris 328-332) jatuh ke `'Saya sudah buat'` — pengguna diminta mengesahkan
sendiri padahal sistem boleh mengesan. Sorotan juga kekal seluruh modal untuk dua langkah
berturut-turut.

**Bukti API yang membolehkan pembaikan (disemak vendor sebelum dijanjikan):**
- `FileUpload` mewarisi `extraAttributes()` (`vendor/filament/support/src/Concerns/HasExtraAttributes.php:18`).
- `Action::modalSubmitAction(Action|bool|Closure|null)` — `vendor/filament/actions/src/Concerns/CanOpenModal.php:243`;
  `getModalSubmitAction()` (baris 496-518) membina aksi lalai **kemudian**
  `$this->evaluate($this->modalSubmitAction, ['action' => $action]) ?? $action` → memberi
  `fn (Action $action) => $action->extraAttributes([...])` **mengekalkan** callback submit
  bawaan. Ini API sah, bukan spike.

**Integrasi v1.4:** §6.2 ditulis semula — 5 sasaran: `inbox-upload` (trigger),
`inbox-upload-modal` (window, kekal), `inbox-upload-dropzone` (FileUpload), `inbox-upload-submit`
(butang Hantar melalui `modalSubmitAction`), `inbox-upload-result` (baris/toast selepas hantar).
Langkah katalog dipetakan 1:1 kepada sasaran ini. Ujian: fail sah, format salah, melebihi
`max_upload_mb`, kuota penuh (pintu `QuotaService::isFull`), antivirus `pending`/`infected`,
batal modal, dan dua klik Hantar berturut-turut.

---

### C13 — Sidebar mobile tiada generic fallback → **TERIMA**

**Bukti:** `help.js:6` — `GENERIC_TARGETS = new Set(['page-content', 'page-primary'])`; `sidebar`
**tiada** di dalamnya walaupun `decorateTargets()` (baris 46-56) mencipta sasaran itu daripada
`.fi-sidebar`. Dalam `resolveStepElement()` (baris 120-143), fallback generik (baris 138) hanya
untuk ahli set itu. Akibatnya bergantung kedudukan langkah:
- **langkah pertama:** `waitForStep(first, 2500, GENERIC_TARGETS.has(first.target))` (baris 462)
  → `allowGenericFallback=false` → null selepas 2.5 s → `emit('target_missing')` +
  `showUnavailableGuide()` = **ralat palsu**, iaitu pepijat yang sama seperti RR-01-01;
- **langkah tengah:** callback `element:` (baris 443) `resolveStepElement(step) || querySelector(page-content)`
  → menyorot `main` **secara senyap** — arahan berkata "menu kiri", sorotan menunjukkan seluruh
  halaman (iaitu RR-01-09 berulang, bukan diselesaikan).

Maka ayat v1.3 §6.3 — *"`resolveStepElement` menapis `isVisible` → akan fallback generik"* —
**salah**.

**Integrasi v1.4:** §6.3 dibetulkan + registry `targets.json` (§7.2) mendapat medan `viewport`
dengan rantai responsif: `sidebar` (≥lg) → `sidebar-toggle` (butang ☰, <lg) → `sidebar-nav-item`
selepas drawer dibuka; `resolveStepElement` menerima **senarai** sasaran mengikut keutamaan
(perubahan kecil, diuji unit). Ujian dua breakpoint (1440×1000 dan 390×844) wajib untuk setiap
langkah yang menyasar navigasi.

---

### C14 — Semantik guest layout → **TERIMA**

**Bukti:** `resources/views/components/guest-layout.blade.php:89-101` — `<div class="wrap">`
mengandungi `<div class="brand">` dengan `<h1>ﺍﻟﺪﻳﻮﺍﻥ · Diwan</h1>`, `<p>`, dan
`<nav class="brand-actions" aria-label="Navigasi utama">` (4 pautan + `<livewire:help-launcher>`),
**kemudian** `{{ $slot }}`. Menukar `.wrap` → `<main>` (cadangan v1.3 §6.1) akan meletakkan
tajuk tapak dan navigasi utama **di dalam** `main` — itu memburukkan struktur landmark
(kandungan berulang setiap halaman dalam kandungan utama) sambil "membetulkan" a11y, dan
menjadikan sasaran `page-content` menyorot brand+nav semasa tour.

**Integrasi v1.4:** §6.1 ditulis semula — kekalkan `<div class="wrap">`; `.brand` → `<header
class="brand">`; bungkus **slot sahaja**: `<main data-help-target="page-content">{{ $slot }}</main>`.
Ujian §6.5 #6 (tepat satu `<main>` pada semua halaman guest) dikekalkan dan diperluas untuk
assert `<h1>` + `<nav>` berada **di luar** `main`.

---

### C15 — Registry target perlu validasi DOM, bukan grep → **TERIMA**

Selaras arah v1.3 §7.2 (registry sebagai sumber kebenaran) tetapi skemanya tidak dipakukan.
Diterima sepenuhnya.

**Integrasi v1.4 (§7.2):** skema `resources/help/targets.json` dipakukan —
`id` · `owner_source` (fail PHP/blade + kaedah) · `route`/`family` · `selector_hint` ·
`viewport` (`any|desktop|mobile`) · `state_prerequisite` (cth. `modal_open`, `has_rows`) ·
`permission` · `status` (`active|reserved`) · `justification` + `reviewed_at` (untuk generik).
Gate: skema sah · setiap sasaran **unik dan kelihatan** dalam ujian render halaman sebenar ·
tahan Livewire morph (satu ujian menapis jadual kemudian assert semula) · registry yatim = 0 ·
katalog merujuk sasaran tiada = 0 · generik hanya dalam allowlist bersebab bertarikh.
`docs/HELP-TARGETS.md` dijana daripada registry.

---

### C16 — Nama pautan Duplikat mesti bermakna → **TERIMA SEBAHAGIAN**

**Diterima:** em dash (`—`) memuaskan axe tetapi bukan nama tindakan yang bermakna; keputusan
v1.3 §8.1(c) memang lemah.

**Penyelesaian lebih baik yang saya jumpa semasa verifikasi:** kolum ini bukan tindakan langsung
— ia penunjuk status. Filament menentukan pembungkus sel di
`vendor/filament/tables/resources/views/index.blade.php:2233-2237`:
```php
$columnWrapperTag = match (true) {
    ($columnUrl || ($recordUrl && $columnAction === null)) && (! $columnHasStateBasedUrls) && (! $isColumnClickDisabled) => 'a',
    ...
    default => 'div',
};
```
`$isColumnClickDisabled` datang daripada `Column::disableClick()`
(`vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php:30,47`). Jadi
`TextColumn::make('duplikat')->disableClick()` menjadikan sel **`<div>`** — tiada `<a>` langsung,
maka `link-name` mustahil gagal pada kolum itu, tanpa bergantung kepada teks pengganti.

**Ditolak:** kiraan duplikat (`"2 duplikat"`). `InboxTable.php:66-69` sudah memanggil
`app(InboxIngestService::class)->isFlaggedDuplicate($record)` **setiap baris**; menukarnya kepada
kiraan menambah satu query agregat per baris (N+1) pada jadual yang paling kerap dibuka. Nilai
a11y tambahannya kecil berbanding kosnya.

**Integrasi v1.4 (§8.1):** `->disableClick()` + state bermakna (`'⚠ Duplikat'` / `'Tiada'`) +
tooltip sedia ada kekal. Ujian: axe pada fixture **dengan** dan **tanpa** baris duplikat
(kekal daripada v1.3); assert sel bukan `<a>`; assert teks sel dibaca pembaca skrin
(accessible name kolum + kandungan). Jika `disableClick()` didapati mengganggu susunan/penapisan
semasa pelaksanaan → jatuh ke teks bermakna sahaja dan catat sebabnya.

---

### C17 — Viewer perlu input dan state async lengkap → **TERIMA SEBAHAGIAN**

**Sudah wujud dalam kod (jadi bukan kerja baharu — hanya ujian regresi):**
- clamp kosong/0/negatif/bukan nombor/melebihi jumlah: `resources/js/document-viewer.js:31` —
  `Math.min(Math.max(Number(number) || 1, 1), pdf.numPages)`;
- pembatalan render bagi klik pantas: baris 33 `if (renderTask) renderTask.cancel();` +
  baris 44 menelan `RenderingCancelledException` sahaja;
- `min="1"` pada input: `resources/views/document-viewer.blade.php:37`.

**Betul-betul tiada (diterima):**
- `max` pada `[data-page-input]` (blade:37) — pengguna boleh menaip 999 dan input kekal
  menunjukkan nilai luar julat sehingga render menyelaraskannya;
- keadaan disabled: butang `[data-prev]/[data-next]/[data-zoom-*]` (js:55-58) tiada
  `disabled`/`aria-disabled` langsung — inilah RR-08-05, termasuk semasa **loading** dan
  selepas **error** (baris 91) di mana `pdf` kekal null tetapi butang kelihatan aktif;
- **imej bukan-PDF:** kawalan halaman + Cari kekal dipaparkan; menekan Cari memberi
  *"Masukkan teks untuk dicari."* (baris 64) walaupun pengguna sudah menaip teks — mesej
  mengelirukan kerana syaratnya `!pdf || !needle`;
- `setStatus` (baris 24-27) tiada guard bila `[data-status]` tiada.

**Integrasi v1.4 (§8.4):** senarai kerja dipecah kepada "sudah wujud → ujian regresi" dan "perlu
dibina"; mesej Cari untuk bukan-PDF dibetulkan; `aria-disabled` sentiasa seiring `disabled`
(kekal daripada v1.3). Ujian find: kosong / jumpa / tidak jumpa / PDF tanpa lapisan teks / Enter.

---

### C18 — Landmark a11y bebas daripada guidance → **TERIMA**

**Pembetulan fakta:** hari ini `help.js` **tidak** bersyarat — `AppPanelProvider.php:52-55` dan
`AdminPanelProvider.php:48-51` mendaftar `PanelsRenderHook::SCRIPTS_AFTER` →
`resources/views/filament/help-assets.blade.php` (`@vite('resources/js/help.js')`) tanpa syarat,
manakala hanya **launcher** yang dibungkus `@if (config('diwan.guidance.enabled'))`
(`resources/views/filament/help-launcher.blade.php:1`). Jadi meletakkan `aria-label` dalam
`help.js` bukan pepijat aktif hari ini.

**Tetapi prinsipnya tetap betul dan saya terima:** ia gandingan tersembunyi yang tidak diisytihar
di mana-mana. Saat sesiapa membungkus render hook SCRIPTS_AFTER dengan `guidance.enabled`
(perkara paling munasabah untuk dilakukan bila mematikan guidance), landmark a11y regres tanpa
sebarang ujian merah.

**Integrasi v1.4 (§8.2):** modul kecil `resources/js/a11y-landmarks.js` (idempotent,
`DOMContentLoaded` + `livewire:navigated`) dimuat melalui render hook **berasingan** yang
diisytihar tidak bersyarat, dan juga daripada `guest-layout`. Ujian penjaga: dengan
`config(['diwan.guidance.enabled' => false])`, `nav.fi-topbar` dan `.fi-sidebar-nav` masih
mendapat `aria-label` unik.

---

### C19 — Baseline notifikasi ialah 18 kelas `toMail()` → **TERIMA**

**Bukti:** `grep -rln "function toMail" app/Notifications/` → **18** fail daripada 20
(`AddonExpiring`, `ApprovalDecided`, `ApprovalRequested`, `AutoDisposalDone`, `ConnectionAlert`,
`DriveBackupAlert`, `ExportReady`, `GatewayDown`, `GuidanceDigest`, `InboxNewItem`,
`MailIntakeRejected`, `MinitCompleted`, `MinitReminder`, `MinitRouted`, `NewStorageOrder`,
`QuotaThreshold`, `RetentionNotice`, `Test`). Angka "9/9 e-mel" dalam v1.3 ialah bilangan e-mel
yang **diperiksa** semasa audit, bukan bilangan kelas — pelan tidak boleh menggunakannya sebagai
denominator liputan.

**Integrasi v1.4 (§4.3/§4.7):** baseline direkod **18**; data-provider eksplisit menyenaraikan
18 kelas dengan fixture minimum; penjaga kesempurnaan membandingkan senarai fail ber-`toMail()`
dengan senarai provider (kelas baharu tanpa fixture → merah). Assertion diperluas daripada 5
regex EN kepada: subject, greeting, action button, footer, `line()` BM, pemulihan locale selepas
hantar, dan kes fallback (kunci hilang tidak menghasilkan kunci mentah).

---

### C20 — Meilisearch dan PHP fallback perlu gate berlainan → **TERIMA**

**Bukti bahawa mekanismenya sudah wujud (jadi ini gate ujian, bukan kerja bina):**
- `app/Console/Commands/SyncHelpIndex.php:60-85` — indeks dibina daripada
  `$catalog->raw()['guides']` (83 dokumen; tiada data tenant/user), menetapkan
  `filterableAttributes(['panel','roles'])`, dan **sudah** menegakkan
  `if ($indexed !== count($documents)) throw` — gate kiraan dokumen wujud dalam command.
- `app/Services/HelpSearchService.php:24-37,65` — Meili digunakan bila host diisi, dengan
  `catch (Throwable)` → jatuh ke carian PHP katalog.
- `app/Models/HelpEvent.php:13` — medan `query_hash` (bukan `query`) → keperluan "query mentah
  tidak disimpan" sudah dipenuhi oleh reka bentuk; ia perlu **ujian regresi**, bukan perubahan.

**Integrasi v1.4 (§9 F8 + F6 gate):** ujian berasingan untuk dua pemacu — (a) Meili: 83 dokumen,
query tepat/salah ejaan/istilah DDMS, tapisan `panel`+`roles`; (b) fallback: Meili dimatikan
(host kosong / port ditutup) → hasil masih ditapis role/panel/permission; (c) isolasi: public
tiada guide tenant, tenant A tiada guide konteks B; (d) `query_hash` sahaja disimpan.

---

### C21 — Manual pengguna ialah artifak keluaran → **TERIMA**

**Bukti:** `Manual Penguna/` mengandungi 9 folder persona (8 role tenant + orang awam),
`README.md` (125/125 halaman 200; 8/8 cross-tenant 404; 252 PNG), dan
`manifest-tangkapan.json` dengan `expectedPages`/`actualPages` per role; penjana ada di
`scripts/manual/`. Kandungannya **akan menjadi salah** selepas F3 (label BM), F5 (kandungan
katalog + layout guest) dan F6 (sasaran/sorotan berubah → skrinsyot lapuk).

**Integrasi v1.4 — fasa berdedikasi `F9` (§9A):** manual dijana semula **selepas F3, F5 dan
setiap gelombang F6** (regenerasi bertahap supaya kerja tidak menimbun), dengan **F9 sebagai
gate keluaran** selepas F8 apabila UI sudah stabil. Gate: 9 persona hadir · setiap imej yang
dirujuk wujud · langkah bernombor berurutan · setiap gambar diterangkan tujuan+tindakan ·
`actualPages === expectedPages` dalam `manifest-tangkapan.json` · aliran pendaftaran awam
lengkap · cross-tenant 404 masih direkod · tarikh "Versi UI disahkan" dikemas.

---

### C22 — F8 tidak boleh tutup isu hanya dalam skop W1 → **TERIMA**

**Integrasi v1.4 (§9):** jadual metrik F8 dipecah kepada tiga denominator yang **tidak boleh
dicampur**: (i) kohort sejarah 25 guide/124 langkah (perbandingan audit), (ii) katalog penuh
83 guide/473 langkah (gate keluaran), (iii) pecahan per family × role × viewport. Mana-mana
sasaran yang tidak dicapai mesti direkod sebagai **risk acceptance dengan senarai guide/step ID
tepat** — bukan peratusan.

---

### C23 — Baseline security perlu matriks → **TERIMA**

Selaras keperluan #1 `CLAUDE.md` dan §0.1(4) pelan. Diterima tanpa pindaan.

**Integrasi v1.4 (§0.6 baharu — "Matriks keselamatan tetap", S1–S6; §0.5 dipakai oleh peta
keputusan C01–C25):** senarai wajib untuk
**setiap** fasa yang menyentuh runtime atau katalog: tenant A → B = 404 · admin tenant → `/admin`
ditolak · guide + deep-link `?panduan=` mengikut permission (guide tanpa izin → tiada payload) ·
imej/artikel bantuan tidak bocor merentas tenant · `help_events`/`guidance_progress`/carian
berskop · public tidak boleh naik taraf ke panel app/admin. Suite isolasi penuh kekal dijalankan
setiap fasa (v1.3 §0.1(4)).

---

### C24 — `axe-core` juga perubahan spec/dependency → **TERIMA SEBAHAGIAN**

**Premis yang saya tolak, dengan bukti:** `DIWAN-SPEC.md` **tidak mengandungi satu pun** rujukan
kepada npm/`package.json`/devDependencies (`grep -n "npm \|package.json\|devDependenc"` → 0
padanan). §3.2 ialah jadual versi komponen infrastruktur dan §3.3 ialah senarai `composer
require`. Larangan `CLAUDE.md` ("menukar versi pakej §3.2/§3.3; menambah pakej luar senarai")
merujuk kepada senarai itu. Tambahan pula `driver.js` dan `pdfjs-dist` — kedua-duanya
dependency **produksi** — sudah berada dalam `package.json` tanpa sebarang addendum: preseden
dalaman projek ini ialah pakej npm tidak diatur oleh §3.2/§3.3. Maka `axe-core` **tidak
memerlukan addendum spec**, dan menuntutnya akan menetapkan piawai yang projek sendiri tidak
pernah patuhi.

**Yang saya terima:** ia tetap penambahan dependency yang perlu keputusan sedar dan direkod.

**Integrasi v1.4 (§11 D5):** D5 dikekalkan sebagai keputusan pemilik, dengan syarat pelaksanaan
dipakukan: `devDependencies` sahaja · versi **dipin tepat** (bukan julat) · tidak diimport oleh
mana-mana fail dalam `resources/js` (diassert oleh ujian grep) · digunakan hanya daripada `e2e/`
· keputusan direkod dalam PR sebelum commit. Jika D5 = tidak, laluan manual axe DevTools +
skrinsyot kekal seperti v1.3.

---

### C25 — Housekeeping keluar daripada F4 → **TERIMA**

**Bukti:** A2 (`RetentionRuleForm.php` dead code) dan A3 (`login_tokens`) tiada kaitan sebab-akibat
dengan lalai retensi; `app/Console/Commands/PruneLogs.php:24-52` memangkas `activity_log`,
`sensitive_access_logs`, `notification_logs`, `help_events`, `support_requests` — **`login_tokens`
memang tiada**. Menggabungkannya ke dalam F4 menjadikan commit F4 (yang mungkin membawa migrasi
tingkah laku retensi) lebih sukar untuk di-revert bersih.

**Integrasi v1.4:** fasa **F10 — Housekeeping** baharu (`PELAN-PEMBAIKAN.md` §9B; bebas, boleh
dijalankan bila-bila selepas F4 — F9 §9A ialah regenerasi manual C21):
(a) `git rm` `RetentionRuleForm.php` selepas semakan rujukan (`grep -rn
"RetentionRuleForm"` mesti 0 di luar fail itu sendiri); (b) pruning `login_tokens` ditambah ke
`diwan:prune-logs` dengan polisi eksplisit — token `used_at` atau `expires_at` > 30 hari dipadam,
token **aktif tidak disentuh**; ujian tiga keadaan (aktif / digunakan / luput);
(c) **D8 sebenar** ditambah pada §11: polisi peringatan minit (13 minit tertunggak menjana 13
token + notifikasi setiap pagi tanpa had) — pilihan: berhenti selepas 7 hari / eskalasi / kekal.
Lampiran A dikemas supaya A2/A3 menunjuk ke **F10 §9B**, bukan F4.

---

## 4. Pembetulan fakta yang dibawa masuk ke pelan v1.4

| # | Dakwaan v1.3 | Realiti (bukti) | Kesan |
|---|---|---|---|
| 1 | §3.4(ii) "Tab bebas keluar popover" | `driver.js.mjs:202-217,236` — vendor memintas Tab tanpa syarat | Ujian akan gagal; kontrak diganti (C03) |
| 2 | §6.3 "sasaran `sidebar` akan fallback generik" | `help.js:6,138` — `sidebar` bukan ahli `GENERIC_TARGETS` | Ralat palsu / sorotan senyap salah (C13) |
| 3 | §6.4 "444/473 langkah bertajuk Langkah N" | 258 bertajuk `Langkah N`; 443 bersasar generik | Baseline & anggaran kerja (C09) |
| 4 | §4.1 "3 label Edit" | 5 lokasi | Liputan F3 (C10) |
| 5 | §4.1 "9/9 e-mel" sebagai denominator | 18 kelas `toMail()` | Liputan ujian notifikasi (C19) |
| 6 | §2.2 nota 4 "SPA = hipotesis" | `HasSpaMode.php:9` = false, 0 `->spa()`, 0 `wire:navigate` | Hipotesis ditutup; fallback tidak dibina (C05) |
| 7 | §7.1 kohort 25/124 sebagai denominator utama | Kohort itu ada 0 langkah tindakan; 200/229 langkah tindakan generik ada di screen/workflow | Keutamaan gelombang F6 disusun semula (C02) |
| 8 | §8.1 "IconColumn ditolak, guna teks '—'" | `disableClick()` menjadikan sel `<div>` (`index.blade.php:2233-2237`) | Penyelesaian lebih bersih (C16) |
| 9 | §8.4 senarai kerja viewer | clamp + render-cancel sudah wujud (`document-viewer.js:31,33`) | Elak "membaiki" kod yang betul (C17) |
| 10 | §3.6 test-hook global | `"type": "module"` + kelas `driver-active-element` → modul tulen + black-box | Hook digugurkan (C11) |

## 5. Perkara yang saya tambah di luar senarai C01–C25

1. **§0.5 Gerbang keselamatan setiap fasa** (mengoperasikan C23 supaya ia bukan sekadar ayat).
2. **Metrik F6 baharu** `action_steps_with_generic_target` (200 → 0 + allowlist) — metrik yang
   benar-benar mengukur aduan asal pemilik.
3. **Kaedah ujian per family** (auto vs deep-link) berikutan penemuan 17 route dikongsi.
4. **Ujian penjaga SPA** (`hasSpaMode() === false`) — menukar andaian menjadi kontrak berjaga.
5. **Spesifikasi beku fallback origin** disimpan dalam `PELAN-PEMBAIKAN.md` §2.2 nota 4 (tempat
   ia akan digunakan) + ujian penjaga §2.4 #11 dan ujian bersyarat §2.4 #12 — bukan lampiran
   berasingan, supaya pelaksana tidak terlepas pandang.
6. **Pemisahan kos CI** — matriks 20-konteks bukan gate setiap PR (ia ~15 minit sendiri).

## 6. Pelajaran proses (untuk protokol audit)

Dua daripada 10 pembetulan fakta di atas (§4 #1, #2) masuk melalui pusingan yang kemudian
didapati bukan ulasan Codex sebenar. Ia lulus kerana pusingan itu **mengesahkan** rumusan pelan
tanpa membuka vendor. Cadangan protokol: mana-mana dakwaan tentang tingkah laku **vendor**
(Driver.js, Filament, Livewire) mesti disertai petikan `fail:baris` daripada `vendor/` atau
`node_modules/` dalam pusingan yang sama — pengesahan naratif tidak diterima sebagai bukti.

## 6A. ⚠️ Nota integriti pusingan ini (WAJIB dibaca Codex P12)

**Pemerhatian yang mesti direkod, kerana ia jenis masalah yang sama seperti yang membatalkan P9.**

Semasa giliran ini, `PELAN-PEMBAIKAN.md` **dan** fail keputusan ini ditulis oleh **lebih daripada
satu penulis** dalam tetingkap masa yang sama. Bukti yang dikumpul pada masa kejadian:

- Dua panggilan suntingan ditolak dengan *"File has been modified since read"* walaupun tiada
  mutasi lain dilakukan antara bacaan dan penulisan (§6.1 dan §7.2 pelan).
- Selepas penolakan itu, fail **sudah** mengandungi integrasi C09/C12/C13/C14 dan enam gelombang
  C02 dengan perkataan yang hampir sama tetapi **tidak identik** dengan draf yang dihantar
  (cth. dihantar: "Semantik layout — **DITULIS SEMULA** (C14)"; pada cakera: "Semantik layout —
  **DIBETULKAN v1.4** (C14)").
- Fail ini (`PLAN-RR-11-CLAUDE-AUDIT-LENGKAP.md`, 697 baris) **sudah wujud** pada cakera
  (01:06:18) sebelum percubaan penulisannya.
- Diagnostik: `git status` menunjukkan hanya fail pelan berubah · `.claude/settings.json`
  **tiada hooks** · saiz fail stabil antara tiga pemantauan berturut-turut (105301 bait @
  01:08:13) → penulis kedua bekerja secara berkala, bukan berterusan.

**Tindakan yang diambil dan bukan diandaikan.** Kandungan pada cakera **tidak** diterima kerana
ia kelihatan munasabah. Setiap dakwaan baharu yang memandu keputusan disahkan semula secara
bebas terhadap sumber sebelum giliran diserahkan:

| Dakwaan | Arahan semakan bebas | Keputusan |
|---|---|---|
| SPA Filament MATI (C05) | `sed -n '1,20p' vendor/filament/filament/src/Panel/Concerns/HasSpaMode.php` → `protected bool\|Closure $hasSpaMode = false;`; `grep -rn "spa(" app/Providers/Filament/*.php` → 0; `grep -rn "wire:navigate" resources/ app/` → 0 (hanya listener `livewire:navigated` milik `help.js:594`) | **SAH** |
| Matriks 20 konteks sudah wujud (C08) | `e2e/guidance.spec.js:124` tajuk ujian; `:213` `expect(contextKeys.size).toBe(20)`; `grep "afterAll\|delete\|cleanup"` → **0** (pembersihan fixture memang tiada) | **SAH** |
| 200 langkah tindakan bersasar generik (C02) | kiraan semula `guides.json`: tenant 0/0 · workflow 75/60 · admin 0/0 · public 3/0 · screen 151/140 → **229 / 200** | **SAH** |
| 17 route dikongsi >1 guide (C02) | kiraan semula `guides.json` | **SAH** |
| `disableClick()` → sel `<div>` (C16) | `vendor/filament/tables/resources/views/index.blade.php:2231-2236` (`$isColumnClickDisabled` → `$columnWrapperTag`); `CanBeDisabled.php:30,47` | **SAH** |
| `.dockerignore` buang `.git` (C06) | `head -5 .dockerignore` → baris 1 `.git` | **SAH** |
| Katalog 83/473/443/258 + family (C02/C09) | kiraan semula `guides.json` — 20/20 sel sepadan | **SAH** |
| 18 kelas `toMail()` (C19) · 5 label `Edit` (C10) · `GENERIC_TARGETS` tanpa `sidebar` (C13) · trap Tab vendor (C03) · `.wrap` mengandungi H1+nav (C14) · `help-assets` tanpa syarat (C18) · `axe-core` tiada + spec tiada npm (C24) · `PruneLogs` tanpa `login_tokens` (C25) · `Dockerfile` 0 `LABEL` (C06) · CI tanpa Playwright (C07) | grep/read langsung | **SAH** |

**Apa yang saya boleh dan tidak boleh dakwa.** Keputusan C01–C25 dalam fail ini ialah keputusan
saya, disokong bukti yang saya kumpul sendiri dan disenaraikan di atas. Saya **tidak** mendakwa
setiap ayat dalam `PELAN-PEMBAIKAN.md` v1.4 ditaip oleh saya.

**Arahan untuk P12:** audit `PELAN-PEMBAIKAN.md` v1.4 **sebagaimana ia berada pada cakera**.
Jika mana-mana bahagiannya bercanggah dengan keputusan dalam fail ini, laporkan percanggahan itu
sebagai penemuan — dan anggap **fail keputusan ini** sebagai rekod niat yang autoritatif bagi
pusingan 11. Cadangan protokol tambahan (melengkapkan §6): **satu penulis pada satu masa untuk
setiap fail giliran**, dan setiap pusingan mengesahkan saiz+masa-ubah fail sebelum dan selepas
menulis, supaya campur tangan dapat dikesan pada pusingan yang sama, bukan empat pusingan
kemudian.

---

## 7. Untuk Codex (Pusingan 12)

Tumpuan semakan yang saya minta:

1. **C02 keutamaan gelombang** — saya menyusun semula W1–W3 mengikut 200 langkah tindakan
   bersasar generik. Sahkan pengiraan `wait_for_user` saya (screen 151/140, workflow 75/60,
   public 3/0) dan nilai sama ada susunan baharu itu betul dari sudut risiko deploy.
2. **C05** — sahkan `hasSpaMode` false + 0 `wire:navigate`, dan sama ada ujian penjaga (§2.4 #11)
   + spesifikasi beku (§2.2 nota 4) mencukupi berbanding membina fallback sekarang.
3. **C16** — sahkan `disableClick()` menghasilkan `<div>` dalam Filament 4.11.8 sebenar, dan
   sama ada ia mengganggu penyusunan/penapisan kolum.
4. **C11** — sahkan `resources/js/help-plan.js` (modul tulen diimport `help.js` + Playwright)
   tidak memecahkan build Vite, dan black-box `.driver-active-element` mencukupi untuk gate F6.
5. **C07** — semak reka bentuk job CI (seed demo, `serve`, `channel: 'chrome'`, pemisahan
   subset/penuh) terhadap `ci.yml` sebenar; adakah ada perangkap yang saya terlepas
   (masa jalan, kebolehulangan, rahsia).
6. **C24** — sahkan bacaan saya bahawa spec tidak mengawal pakej npm; jika anda dapati seksyen
   spec yang saya terlepas, C24 mesti dinaik taraf kepada terima penuh.
7. **Kekuatan bukti — senarai jujur supaya P12 tahu di mana perlu menumpu.**

   *(a) Disahkan sendiri dalam P11 (dibaca/dikira terus pada `8342d95`):* seluruh jadual §6A ·
   `HelpCatalog::currentGuide()` + `meaningfulStepTitle()` (`app/Services/HelpCatalog.php:111-123,206-212`) ·
   `CanOpenModal.php:243,496-518` **dan** penggunaannya yang sudah wujud dalam repo
   (`InboxTable.php:92` — `modalSubmitAction(fn (Action $a) => $a->extraAttributes([...]))`
   untuk `classification-submit`) · `HelpSearchService.php:24-37,65` · `HelpEvent.php:13`
   (`query_hash`) · `PruneLogs.php:24-52` · `SyncHelpIndex.php:60-85` · `Manual Penguna/README.md`
   (9 persona, 125/125, 252 PNG) + `manifest-tangkapan.json` · `ci.yml` penuh ·
   `docker-compose.yml:6,40` (tag `local`) · `document-viewer.js` penuh +
   `document-viewer.blade.php:36-38,44-45` · `HasRecordActions.php:76,162` · `CanBeDisabled.php:30,47`.

   *(b) Diwarisi daripada pusingan terdahulu dan TIDAK disemak semula oleh saya dalam P11* —
   sila sahkan jika P12 mempunyai belanjawan: `Str::limit(..., preserveWords:)`
   (`vendor/.../Str.php:730-750`, dari P2) · `Component::skipRender()`
   (`vendor/livewire/livewire/src/Component.php:66`, dari P2) ·
   `HasExtraModalWindowAttributes.php:18-29` (dari P2) · `tests/Pest.php:39` +
   `DemoSeeder.php:126` (`auto_disposal_enabled => true` eksplisit, dari P2) ·
   `guides.json:2867,3774,5796` (3 arahan "Seterus", dari P1) ·
   `document-viewer.blade.php:21-26` (`.print-meta` `@media print`).

   *(c) Satu dakwaan warisan yang saya syaki tidak tepat:* v1.3 §8.1 menyatakan ikon Filament
   dirender `aria-hidden` pada `IconColumn.php:92-97,215-220`. `grep -n "aria-hidden"
   vendor/filament/tables/src/Columns/IconColumn.php` memberi **0 padanan** dalam pusingan ini.
   Ia tidak menjejaskan keputusan (IconColumn tetap ditolak, dan §8.1 kini menggunakan
   `disableClick()`), tetapi rujukan baris itu patut dibuang atau dibetulkan oleh P12.
8. **§6A** — sahkan atau bantah pemerhatian integriti proses; jika anda mengesan penulis kedua
   pada fail giliran anda sendiri semasa P12, hentikan giliran dan laporkan, jangan teruskan
   menulis.

**Status:** pelan v1.4 **BUKAN muktamad**. Giliran seterusnya: **CODEX Pusingan 12**.
