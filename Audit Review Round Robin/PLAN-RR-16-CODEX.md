# Pusingan 16 — Codex: audit keadaan separa v1.6 selepas P15 terhenti

**Tarikh:** 2026-08-02  
**Asas kod:** `8342d95`  
**Pelan diaudit:** v1.6 separa, SHA-256
`A1667A703FB90C57459626DAD46553F360E93097066D8A39B335B89CD44FF31E`  
**Keputusan:** **BELUM MUKTAMAD — Claude P15 tidak menamatkan serahan; v1.7 diperlukan**

## 1. Keadaan serahan

Claude P15 sempat mengintegrasikan sebahagian besar P14-01…P14-08 ke dalam pelan induk, tetapi
proses keluar sebelum:

- mewujudkan `PLAN-RR-15-CLAUDE.md`;
- mengemas kini `PLAN-RR-STATUS.md` kepada P16;
- mengemas footer pelan (masih `Versi 1.5` / `Codex Pusingan 14`);
- mengemas prasyarat atas/F0 daripada D1–D10 kepada D1–D11;
- membuang kontradiksi Lampiran B #11 yang masih melarang job E2E berasingan walaupun F0 kini
  mewajibkan `guidance-e2e` dengan services sendiri.

Percubaan menyambung Claude gagal dengan mesej literal:

> You've hit your monthly spend limit

Codex tidak akan menulis fail bernama Claude atau menanda pelan muktamad bagi pihak Claude.

## 2. Integrasi P14 yang telah masuk dengan baik

- env CI `APP_URL`/`E2E_BASE_URL` 8092 dan `SESSION_DRIVER=file`;
- tiga lapis CI, tiga shard family dan agregator denominator 473/229/83;
- set manifest `role_routes` dan drift 8/8 role;
- runner production bernama, `run_uuid`, tenant unik dan cleanup run-scoped;
- partition W0–W6 berangka exact serta enam defect mobile dipromosi ke W0;
- `blocked=0`, kategori `risk-accepted` dan fallback;
- gate `rg` mula diberi guard kewujudan;
- protokol snapshot/hash dan D11.

Arah di atas dikekalkan. Baki di bawah perlu ditutup sebelum penutupan.

## 3. Penemuan P16

### P16-01 — Canary login masih bukan command boleh-jalan

§F0(iv) menyuruh `curl` GET login, simpan cookie, kemudian POST kredensial. Borang Laravel/Filament
memerlukan token CSRF dan nama medan Livewire/Filament bukan kontrak POST HTML biasa yang telah
ditetapkan dalam pelan. Tiada command mengekstrak token, tiada payload exact dan tiada assertion
exit code. Canary itu akan gagal atau memberi bukti palsu walaupun sesi `file` betul.

**Pindaan:** gunakan spec kecil Playwright `e2e/ci-session-canary.spec.js` yang memanggil helper
login sedia ada, assert redirect `/app/mam`, reload sekali dan assert masih berautentikasi. Tambah
ke project `ci-guidance` dan nyatakan command exact `npx playwright test --project=ci-guidance
--grep @session-canary` sebelum suite. Jika mahu kekal curl, tulis command token/payload penuh
berdasarkan HTML sebenar; jangan tinggalkan pseudokod.

### P16-02 — Job shard/agregator belum mempunyai pelaksanaan literal

Pelan membekukan nama dan denominator shard, tetapi tiada:

- nama project/test file setiap shard;
- command Playwright setiap nilai matrix;
- format/skema JSON artifak;
- nama skrip agregator dan commandnya;
- YAML `needs`, `strategy.matrix`, upload/download artifact dan required gate yang cukup untuk
  dilaksanakan tanpa reka bentuk baharu semasa PR.

**Pindaan:** bekukan `e2e/guidance-full.spec.js`, parameter `GUIDANCE_SHARD`, command exact,
`scripts/audit/aggregate-guidance-coverage.mjs`, skema output (`guide_ids`, `step_ids`,
`action_step_ids`, `blocked`, `failures`) serta YAML ringkas yang menunjukkan services sendiri.
Agregator mesti membanding set ID dengan manifest, bukan hanya count.

### P16-03 — Skop D11 mengira artifak terlalu sedikit

D11 menyenaraikan empat artifak, tetapi reka bentuk v1.6 turut memerlukan sekurang-kurangnya:

- `e2e/ci-session-canary.spec.js`;
- `e2e/guidance-full.spec.js` atau tiga project/spec setara;
- skrip agregator denominator;
- skema/validator manifest `role_routes` + `wave`/`shard`;
- `e2e/production-guidance-readonly.spec.js` (wrapper sahaja tidak mencukupi);
- command pentadbiran setup/cleanup production dan ujian idempotensinya.

**Pindaan:** D11 dan jadual fail F0 mesti menyenaraikan semua fail baharu/diubah serta tujuan,
supaya perubahan test tooling tidak diseludup semasa implementasi.

### P16-04 — Setup/cleanup production masih tidak dinamakan

§9.1a menamakan spec dan wrapper, tetapi hanya berkata "command pentadbiran berasingan" bagi
setup/cleanup. Tiada nama artisan command/API, argumen, output inventory, sempadan authorization,
atau cara wrapper mendapatkan kredensial lapan role + superadmin tanpa mencetak rahsia.

Terdapat juga dua kontrak `run_uuid`: jadual command mewajibkan `-RunUuid <uuid>`, sedangkan
peraturan wrapper berkata UUID dijana di awal. Pilih satu sumber.

**Pindaan:** namakan command exact, contohnya `diwan:audit-fixture prepare|cleanup --run=<uuid>
--json=<path>`, jadikan ia fail/tool D11 yang diuji, dan tetapkan wrapper menjana UUID jika argumen
tidak diberi atau menggunakan nilai diberi untuk recovery. Output rahsia masuk fail private
sementara ber-ACL ketat dan dipadam dalam `finally`; log hanya nama akaun/ID tersanitasi.

### P16-05 — Senarai ID wave exact belum wujud

Pelan memberi formula dan kiraan W0–W6, tetapi §F0(ii-a) menyatakan senarai ID exact ada dalam
`PLAN-RR-15-CLAUDE.md` §3. Fail itu tidak wujud. Tanpa senarai tersebut, angka 83/473 boleh dikira
semula tetapi partition yang hendak dibekukan belum boleh diaudit.

**Pindaan:** P15/P17 mesti menyenaraikan 83 guide mengikut wave atau, lebih baik, masukkan
`wave`/`shard` terus dalam snapshot manifest v1.7 dan lampirkan output validator yang menunjukkan
set union exact tanpa duplicate/missing. Dokumen pusingan hanya merumus, manifest menjadi sumber.

### P16-06 — Gate `! rg` masih menukar ralat kepada lulus

P15 betul bahawa kewujudan input perlu dijaga, tetapi kedua-dua command masih menggunakan
`! rg ...`. Operator `!` menukar **semua** exit bukan sifar kepada berjaya, termasuk exit 2 akibat
ralat I/O/permission/regex. Gate manual hanya mengassert folder wujud, bukan sekurang-kurangnya
satu fail Markdown; folder kosong akan lulus.

**Pindaan:** tangkap status secara eksplisit:

```bash
if rg -n '<pattern>' <files>; then
  echo 'FAIL: padanan terlarang'; exit 1
else
  rc=$?
  [ "$rc" -eq 1 ] || exit "$rc"
fi
```

Sebelum itu, bina array fail dan assert count > 0. Terapkan corak sama kepada bundle dan manual.

### P16-07 — `role_routes` perlu kontrak expected daripada polisi, bukan belajar hasil rosak

Pelan menyebut `expected_status` 200/403/404 dan kemudian berkata nilai direkod daripada tingkah
laku sebenar. Baseline runtime yang salah boleh menjadi kontrak baharu. Generator perlu membina
**expected access daripada role permission + policy/spec**, kemudian probe runtime sebagai
`actual_status`; mismatch mesti gagal. Jangan menukar `expected_status` kepada actual semata-mata.

Untuk negative matrix, route universe dibina sekali tanpa penapisan identiti, kemudian authorizer
dinilai bagi setiap identiti. Jika generator bermula hanya daripada navigasi yang sudah ditapis,
route terlarang akan hilang dan negative matrix tidak lengkap.

### P16-08 — CI project smoke masih meninggalkan suite penting di luar gate

Project `ci-guidance` menyenaraikan `guidance`, `registration`, `explore`, tetapi mengecualikan
`office-workflow`, `ddms-extended` dan `ocr-upload`. Pelan berkata allowlist dengan sebab cukup,
sedangkan permintaan pengguna ialah workflow end-to-end dan F6/F8 menyentuh upload,
klasifikasi, minit, viewer/carian. Full shard G1–G5 tidak semestinya menggantikan assertion domain
dalam tiga spec itu.

**Pindaan:** tentukan project CI berasingan bagi `office-workflow`/`ddms-extended`; masukkan
`ocr-upload` dalam job yang mempunyai OCR/queue/antivirus fixture sesuai. "Perlahan" boleh menjadi
scheduled/nightly tambahan tetapi sebelum deploy F8 semua project relevan mesti menjadi required
gate, bukan allowlist kekal.

## 4. Sisa konsistensi wajib

Claude perlu membetulkan dalam v1.7:

1. header dan F0 `D1–D10` → `D1–D11`;
2. urutan fasa `F6 (6 gelombang)` → **7 wave W0–W6**;
3. F2 "job CI e2e" → nama gate sebenar;
4. Lampiran B #11 → larang perkongsian services, tetapi benarkan/mewajibkan `guidance-e2e`
   dengan services sendiri;
5. footer → v1.7 / giliran Codex seterusnya;
6. wujudkan `PLAN-RR-15-CLAUDE.md` sebagai rekod serahan separa atau `PLAN-RR-17-CLAUDE.md`
   yang menggabungkan keputusan P14+P16 secara jujur;
7. kemas kini `PLAN-RR-STATUS.md` dan catat kegagalan P15 kerana spend limit, bukan menyembunyikannya.

## 5. Status

Round-robin **belum selesai**. Giliran Claude seterusnya terblok oleh had perbelanjaan bulanan.
Apabila akses pulih, Claude perlu menyambung sebagai P17, bukan menulis semula fail Codex, lalu
Codex menjalankan audit P18. Tiada kod aplikasi, commit, push, SSH atau deploy dibenarkan sebelum
pelan benar-benar muktamad dan keputusan D1–D11 dijawab.
