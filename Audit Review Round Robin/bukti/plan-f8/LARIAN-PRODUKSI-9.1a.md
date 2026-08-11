# F8 §9.1a — matriks PRODUKSI dijalankan: 20/20 konteks, dan tiga baris ⏸ ditutup dengan angka

**Tarikh:** 11–12 Ogos 2026 · **Pelayan:** bakwim.my (`43.156.242.188`, `/opt/diwan`)
**Pencetus:** pemilik mencipta `.e2e-prod-credentials.local.json` — penyekat tunggal F8 dibuka.
**Produksi kekal `2325bec` (Deploy 14):** F8 ialah fasa PENGUKURAN, tiada kod di-deploy.

## 1. Matriks 20 konteks — `run_uuid 7982012d-8685-464a-879f-bfbd8b69e6e7`

```
SELESAI            20/20   (desktop 10 · mobile 10)
halaman dilawati   396
ralat console unik 0       (8 dikira, kesemuanya 404 probe yang DISENGAJAKAN)
silang-tenant 404  16/16   role tenant × viewport
kontrak            2/2 LULUS  (8 role TEPAT · 20 konteks TEPAT, tiada yang hilang)
amaran_rosak       (tiada)
cleanup delta      mosque_exists=false · run_users=0
```

⭐ **Isolasi tenant disahkan LANGSUNG pada produksi:** setiap satu daripada 16 konteks role
tenant memprob `/app/mamad/records` (tenant yang BUKAN miliknya) dan menerima **404** — bukan
403, bukan halaman kosong. Ini ukuran keselamatan #1 spec (§15.2) pada sistem yang hidup.

**Carian bantuan pada produksi** (tiga pertanyaan §9.1(2), setiap konteks):

```
"Peti Masuk"            →  5 hasil dalam skop Admin / Kerani
"klasfikasi surat"      → 10 hasil        ⬅ toleransi typo Meilisearch BERFUNGSI pada produksi
"zzqqxx-tiada-langsung" →  0 hasil
```

Pertanyaan salah-ejaan itu bermakna: pada larian tempatan ia memberi **0** kerana laluan
fallback PHP tidak bertoleransi typo di bawah 5 aksara. Pada produksi, Meilisearch hidup dan
memberi 10 hasil. Kedua-dua nilai betul untuk persekitarannya masing-masing, dan sekarang
kedua-duanya DIUKUR dan bukan diandaikan.

## 2. Nota E (CTA) — DITUTUP

`cacat_cta` direkod bagi setiap konteks daripada **teks butang popover yang sebenar** (bukan
daripada medan katalog `wait_for_user` — pembetulan Codex P2 #2):

```
{"guide":"tenant.dashboard","langkah":1,"cta":"Seterusnya","wait_for_user":false,"cacat_cta":false}
```

**0 kecacatan CTA merentas 20 konteks** (asas audit: 20). Metrik ini bergantung DOM, jadi ia
memang memerlukan larian produksi — itulah sebabnya ia kekal ⚠️ sehingga sekarang.

## 3. Tiga baris ⏸ — diukur pada `run_uuid 1cde65a0-c866-4d7e-ac3e-fe5d9a470b02`

Spec asal merekod status + `<main>` + overflow per halaman, tetapi **bukan** konteks bantuan,
`asal=`, atau kebocoran EN. Ketiga-tiganya ditambah sebagai rakaman per-halaman (DIREKOD, tidak
diassert — menjadikannya assertion akan menghentikan crawl pada halaman gagal pertama dan
memusnahkan denominatornya).

| Baris §9 | Asas audit | Sasaran | **Produksi** | Status |
|---|---|---|---|---|
| Halaman kekal konteks bantuan | 6/25 | 25/25 | **28/29** (`admin_masjid`) | ✅ |
| `helpUrl` `asal=livewire/update` | ada | 0 | **0** (66/66 halaman) | ✅ |
| EN-leak permukaan UI | ≥5 kelas | 0 | **0** halaman | ✅ |

Butiran:

```
desktop · admin_masjid   29 halaman   guide diisytihar 28/29 · asal BETUL 29/29 · livewire 0 · EN-leak 0
desktop · superadmin     37 halaman   guide diisytihar 12/37 · asal BETUL 37/37 · livewire 0 · EN-leak 0
```

⭐ **`asal` betul pada 66/66 halaman.** Itu metrik F1 yang sebenar: dahulu `render()` membaca
`request()` pada setiap render, jadi respons Livewire menetapkan `asal` kepada `livewire/update`
dan konteks bantuan hilang. Sifar kejadian pada produksi = pembaikan itu hidup.

Senarai kebocoran EN ialah senarai yang SAMA seperti `LocalisationTest`
(`Hello!`, `Regards,`, `All rights reserved`, `Whoops!`) supaya angka produksi dan angka CI
boleh dibandingkan terus, bukan definisi baharu yang dicipta untuk larian ini.

### ⚠️ Dua pemerhatian jujur, TIDAK didakwa sebagai kejayaan

1. **`admin_masjid` 28/29 — bukan 29/29.** Satu halaman tanpa panduan diisytihar: papan pemuka
   tenant (`/app/<tenant>`). Ia bukan kegagalan `asal` (yang betul), jadi ia bukan kecacatan
   F1; ia soalan katalog/keterlihatan. **Dibawa ke F9** dan tidak ditutup di sini.
2. **`superadmin` 12/37.** 25 halaman tenant tidak mengisytiharkan panduan bagi superadmin.
   Penjelasan yang munasabah ialah skop keterlihatan panduan (superadmin bukan ahli tenant),
   dan `asal` betul pada kesemua 37 — tetapi saya **tidak** mengukur sebabnya, jadi saya tidak
   mendakwanya betul mahupun salah. Denominator audit (25) ialah populasi role tenant;
   superadmin direkod sebagai maklumat tambahan.

## 4. Kos sebenar untuk sampai ke sini

Larian ini bukan "jalankan wrapper, baca angka". Empat kecacatan wrapper yang memutasi PRODUKSI
dan satu gantung 4-konteks mesti dibaiki dahulu — lihat
`PENEMUAN-RUNNER-FIXTURE-TERSASAR.md`. Ringkasan:

| | Perkara | Kesan jika tidak dijumpai |
|---|---|---|
| 1 | ACL rahsia menafikan BACA kepada skrip sendiri | fixture tersasar pada produksi, punca halimunan |
| 2 | `FileName='npx'` tidak boleh dilancarkan (Windows) | mati SELEPAS memutasi produksi |
| 3 | ralat `finally` menelan pengecualian asal | punca sebenar tidak pernah kelihatan |
| 4 | satu kegagalan cleanup melangkau baki | 8 kata laluan kekal dalam `/tmp` kontena produksi |
| 5 | evaluasi berlumba dgn pelayaran auto-mula tour | 4 konteks tergantung 8–13 min, tiada had menembak |

Semuanya dijaga: pra-terbang WAJIB sebelum mutasi pertama (counterexample MERAH membuktikan ia
berhenti dengan produksi tidak tersentuh), 2 penjaga Pest baharu, dan redaksi e-mel pada titik
penulisan bukti.

## 5. Kebersihan produksi selepas TIGA kitaran fixture

```
masjid: id=1 mamad · id=2 smoke          ⬅ tenant gate deploy UTUH
akaun @smoke.test: 4                     ⬅ asas (milik tenant `smoke`), sifar baki audit
jumlah pengguna: 9                       ⬅ identik dengan asas
fail /tmp kontena: tiada fail audit
fail rahsia %TEMP%: 0
```

Telemetri yang larian ini TULIS pada produksi (diisytihar dahulu, §9.3): `help_events` dan
`guidance_progress` bertambah bagi akaun fixture — dan kedua-duanya dipadam bersama akaun itu
oleh cleanup ikut ID.
