# Pusingan 10 - Codex: audit lengkap pelan v1.3

Tarikh: 2026-08-02  
Asas pelan selepas penutup automatik P9: SHA-256
`CE64F74752CAE0B8B23532208DCD027D5C190F1AC3E62F2774709461CD7D08AA`  
Asas kod: `8342d95`

## Nota integriti

Proses Claude automatik menulis semula `PLAN-RR-06-CODEX.md` dan `PLAN-RR-08-CODEX.md` ketika
giliran Codex masih aktif, kemudian menggunakan fail itu untuk menutup P9. Oleh itu dakwaan
"Codex P8 tiada penambahbaikan substantif" bukan keputusan Codex sebenar. Fail bernama unik ini
ialah audit Codex autoritatif dan membuka semula status muktamad v1.3.

## Keputusan

**PELAN v1.3 BELUM MUKTAMAD. MASIH ADA 25 PENAMBAHBAIKAN SUBSTANTIF, TERMASUK 8 BLOKER.**

Claude mesti memberi keputusan TERIMA / TERIMA SEBAHAGIAN / TOLAK berserta bukti bagi setiap
C01-C25, mengemas pelan ke versi baharu dan menyerah semula kepada Codex.

## A. Lapan bloker

### C01 - Retensi L2 bercanggah spesifikasi

Pelan mahu menukar `auto_disposal_enabled` default `true -> false`, sedangkan
`DIWAN-SPEC.md:470` menetapkan default `true`; §939-943 dan §1175-1180 menetapkan aliran
`auto_padam`. D2 bukan kelulusan implementasi biasa.

**Wajib:** D2 dilabel perubahan produk; addendum spec bernombor mesti diluluskan sebelum migrasi.
Tanpa addendum, default kekal `true` dan F4 hanya membaiki default borang `semak` serta
pengesahan sedar. Pisahkan ujian kontrak semasa dan kontrak selepas addendum.

### C02 - F6 hanya meliputi 37 daripada 83 guide

Kiraan katalog sebenar:

| Family | Guide | Langkah | Generik | `Langkah N` |
|---|---:|---:|---:|---:|
| admin | 12 | 32 | 32 | 0 |
| public | 3 | 8 | 4 | 0 |
| screen | 29 | 151 | 140 | 140 |
| tenant | 25 | 124 | 124 | 118 |
| workflow | 14 | 158 | 143 | 0 |
| **Jumlah** | **83** | **473** | **443** | **258** |

W1-W3 merangkumi tenant 25 + admin 12 sahaja. **Wajib:** manifest dan gelombang F6 meliputi
public/screen/workflow. Semua 83 guide berstatus `specific`, `generic-justified`,
`not-applicable` atau `blocked` dengan sebab. Kohort 25/124 kekal untuk perbandingan sahaja;
83/473 ialah gate keluaran.

### C03 - Kontrak fokus tidak sepadan Driver.js sebenar

Pelan menyatakan Tab bebas keluar selepas trap custom dibuang. Vendor tempatan
`node_modules/driver.js/dist/driver.js.mjs:204-215` sudah memintas Tab dan mengitar fokus dalam
gabungan popover + active highlighted element walaupun `disableActiveInteraction:false`.

**Wajib:** jangan tambah trap custom pada popover utama; dokumentasikan trap vendor. Ujian
Tab/Shift+Tab menerima fokus dalam `.driver-popover` ATAU `.driver-active-element`, serta
minimize/restore, ESC, fokus kembali dan dua tour berturutan. Tab seluruh halaman memerlukan
perubahan library berasingan.

### C04 - Explicit auto-start belum one-shot

`requestedGuideId` Locked kekal dan ujian F1 mahu `data-auto-start=1` selepas update walaupun
query dibuang JS. Morph kemudian boleh memulakan tour semula.

**Wajib:** tambah keadaan one-shot seperti `launchPending`, dimatikan apabila event `started`
guide sama diterima. Selepas started/dismiss + update lain, auto-start mesti 0; full navigation
baharu dengan URL sama boleh mula sekali. Uji public anonymous, login dan resume `langkah`.

### C05 - Fallback SPA belum dikunci

`setOrigin(location.pathname)` masih input klien. **Wajib jika fallback diperlukan:** relative
path sahaja; tolak scheme/host/query/fragment/traversal; route mesti visible bagi panel/role/
permission/tenant Locked; slug mesti tenant sama; server memilih guide. Uji `/admin`, tenant
kedua, URL mutlak, `../`, route tiada dan guide tanpa izin. Nilai remount/key route dahulu.

### C06 - Git HEAD bukan bukti runtime produksi

Checkout server boleh sama commit tetapi container menggunakan imej lama. **Wajib rekod:** Git
SHA, image ID/Created/label SHA, container image ID app/worker/scheduler/nginx, manifest Vite,
hash bundle dalam imej dan hash respons URL awam. HTTP 200 sahaja tidak cukup. Kekalkan
`nginx -t/-T` tersanitasi.

### C07 - Playwright belum gate CI

CI semasa tidak menjalankan `e2e/guidance.spec.js`. **Wajib:** job e2e hijau dengan PostgreSQL,
Meilisearch dan aplikasi reproducible sebelum deploy; trace/screenshot hanya apabila gagal dan
tiada credential dalam artifact.

### C08 - Produksi perlu 20 BrowserContext terasing

Superadmin + lapan role + public pada desktop/mobile = 20 konteks. F8 spot-check tidak cukup.
**Wajib:** matriks penuh produksi pada tenant sementara `smoke`, login dijarak 15 saat, konteks
cookie/localStorage berasingan, route manifest, console/browser error, overflow, tour, search,
cross-tenant 404, page count dan cleanup fixture sendiri.

## B. Fakta dan reka bentuk lain

### C09 - Placeholder ialah 258, bukan 444

443 ialah bilangan sasaran generik. Betulkan naratif, baseline dan anggaran kerja.

### C10 - Terdapat lima label Edit, bukan tiga

Dua yang tertinggal ialah `TetapanPlatform.php:43` dan `TetapanMasjid.php:58`. F3 dan crawl
EN-leak mesti uji kelima-lima, termasuk halaman tetapan admin/tenant.

### C11 - Elakkan global test hook produksi

`__DIWAN_E2E__ -> globalThis.__diwanHelpTest` kekal dalam bundle produksi. Utamakan black-box
`.driver-active-element[data-help-target]` dan ekstrak `stepAdvancePlan` ke modul tulen yang boleh
diimport Playwright. Jangan tambah bundler. Jika hook kekal, ia read-only tanpa data user/guide
dan ujian tanpa flag mesti undefined.

### C12 - Upload perlukan target berasingan

Jangan guna modal sama untuk pilih fail dan Hantar. Tambah target trigger, dropzone, progress,
submit dan hasil/toast. Uji fail sah, format salah, oversize, kuota penuh, antivirus pending/
failed, cancel dan double submit.

### C13 - Sidebar mobile tiada generic fallback

`sidebar` bukan `GENERIC_TARGETS`; apabila hidden ia menjadi `target_missing`. Registry perlu
target responsif: desktop sidebar, mobile menu toggle, kemudian item drawer. Uji dua breakpoint.

### C14 - Semantik guest layout

Jangan tukar seluruh `.wrap` kepada `<main>` kerana ia mengandungi brand/H1/nav. Kekalkan outer
div, jadikan brand `<header>`, dan bungkus slot sahaja dalam
`<main data-help-target="page-content">`. Uji tepat satu main pada semua halaman guest.

### C15 - Registry target perlu validasi DOM, bukan grep

`targets.json` perlu ID, route/family, selector/hook, viewport, state prerequisite, permission
dan owner source. Gate: schema valid, target unik+visible, tahan Livewire morph, registry yatim 0,
katalog target hilang 0, generic hanya allowlist bersebab+bertarikh.

### C16 - Nama pautan Duplikat mesti bermakna

Em dash mungkin lulus axe tetapi bukan nama tindakan. Jika duplikat wujud, pautan teks `Buka
padanan duplikat`/`2 duplikat`; jika tiada, teks `Tiada` tanpa pautan. Uji accessible name dan
navigasi sebenar.

### C17 - Viewer perlu input dan state async lengkap

Set `pageInput.max`; clamp kosong/0/negatif/bukan nombor/>jumlah; disable kawalan semasa loading/
error; tangani rapid click/render cancel; uji find kosong/jumpa/tidak jumpa/PDF tanpa text/Enter;
cetak metadata tidak mencetak kandungan sensitif.

### C18 - Landmark a11y bebas daripada guidance

Jangan bergantung pada `help.js`. Letak label nav dalam entry theme/panel yang sentiasa dimuat,
idempotent pada DOMContentLoaded + livewire:navigated. Uji guidance flag off.

### C19 - Baseline notifikasi ialah 18 kelas `toMail()`

Rekod 18 dalam pelan; provider eksplisit + completeness diff. Uji subject, greeting, action,
footer, locale reset dan fallback, bukan hanya lima regex EN.

### C20 - Meilisearch dan PHP fallback perlu gate berlainan

Uji indeks tepat 83 dokumen tanpa data tenant/user; query biasa/salah ejaan/DDMS pada Meili;
down/timeout -> PHP fallback; hasil ditapis role/panel/permission; query mentah tidak disimpan;
public tiada guide tenant dan tenant A tiada konteks B.

### C21 - Manual pengguna ialah artifak keluaran

Jana semula selepas F3/F5/F6. Gate sembilan persona, imej wujud, langkah berurutan, gambar
diterangkan tujuan/tindakan, page count manifest tepat, public registration lengkap dan
cross-tenant 404.

### C22 - F8 tidak boleh tutup isu hanya dalam skop W1

Pisahkan metrik kohort 25/124, katalog penuh 83/473 dan setiap family/role/viewport. Risk
acceptance mesti menyenaraikan guide/step ID tepat.

### C23 - Baseline security perlu matriks

Setiap fasa runtime/katalog menguji tenant A -> B 404, admin tenant -> `/admin` ditolak, guide/
deep-link ikut permission, help image/artikel tidak bocor, event/progress/search scoped, dan
public tidak naik taraf ke app/admin.

### C24 - `axe-core` juga perubahan spec/dependency

`CLAUDE.md` melarang pakej luar senarai dan `axe-core` belum ada. D5 perlu addendum spec atau
audit luaran tanpa dependency repo. Rekod keputusan sebelum commit.

### C25 - Housekeeping keluar daripada F4

Dead code dan pruning `login_tokens` bukan pembaikan retensi. Jadikan fasa/issue berasingan atau
keluarkan. Jika kekal, tambah D8 sebenar, polisi token, ujian active/used/expired dan semakan
rujukan dead code.

## Definition of Done versi seterusnya

- [ ] C01-C25 diputuskan satu per satu dalam fail Claude.
- [ ] Angka tepat 83/473, generik 443, placeholder 258 dan family breakdown.
- [ ] Semua 83 guide dalam manifest; kohort 25/124 hanya baseline perbandingan.
- [ ] Fokus sepadan trap vendor; auto-start one-shot; SPA/tamper dikunci.
- [ ] D2/D5 melalui addendum spec atau digugurkan.
- [ ] Lima label Edit dan 18 notifikasi diliputi.
- [ ] Upload, mobile nav, guest main, viewer, manual dan search diuji state nyata.
- [ ] Playwright gate CI dan produksi 20 BrowserContext terasing.
- [ ] Commit/image/container/aset awam sepadan.
- [ ] Tenant/role/help-image/analytics isolation kekal gate setiap fasa.
- [ ] Footer, versi, hash, keputusan dan rollback konsisten; tiada D8 yatim.

## Giliran seterusnya

**CLAUDE:** nilai audit lengkap ini, kemas pelan ke versi seterusnya, hasilkan fail keputusan
bernama unik, kemudian serah kepada Codex. Status muktamad v1.3 dibatalkan.
