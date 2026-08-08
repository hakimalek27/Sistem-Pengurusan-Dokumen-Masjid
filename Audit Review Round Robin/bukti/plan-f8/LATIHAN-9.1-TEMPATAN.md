# F8 §9.1 — latihan matriks TEMPATAN: apa yang berjaya, dan apa yang TERGANTUNG

**Tarikh:** 9 Ogos 2026 · **Sasaran:** `http://127.0.0.1:8092` (BUKAN produksi)
**Tujuan:** melatih matriks 20 konteks tanpa kredensial produksi, supaya larian produksi kelak
menjadi satu arahan dan bukan satu eksperimen.

## ✅ Yang DISAHKAN berfungsi

| Kontrak §9.1a | Bukti |
|---|---|
| `diwan:audit-fixture prepare` cipta tenant `smoke-<uuid>` + 8 akaun role | tenant id 3, akaun id 12–19, satu per role |
| kata laluan rawak, **tidak pernah** ke stdout | stdout memaparkan e-mel + id sahaja; *"Kredensial ditulis ke fail --json sahaja"* |
| slug mesti berawalan `smoke-` | `E2E_PROD_TENANT` divalidasi `^smoke-<uuid>$` oleh spec |
| runner boleh menemui spec | `Total: 1 test in 1 file` selepas pembaikan project (lihat `PENEMUAN-RUNNER-TIDAK-BOLEH-JALAN.md`) |
| `cleanup` padam ikut **inventori** | `{"users":8,"mosques":1,"login_tokens":0}` |
| `cleanup` **IDEMPOTENT** | larian kedua: `{"users":0,"mosques":0,"login_tokens":0}` · **exit 0** |
| tiada sisa | `smoke-*` baki **0** · akaun `@smoke.test` baki **0** |
| tenant lain tidak disentuh | tenant tinggal: `mam`, `man` — tepat seperti sebelum latihan |

Itu **tujuh** item kontrak §9.1a yang sebelum ini hanya "dalam spec"; kini ia diukur.

## 🔴 Yang TERGANTUNG — latihan TIDAK selesai

Larian sebenar spec tidak tamat. Fakta yang diukur, bukan disimpulkan:

```
~41 muatan halaman berlaku   (41× setiap aset Filament dalam log pelayan)
02:33:21  POST /livewire/update   <- tiada status, tiada `Closing`
02:33:22 → 03:33:34              60 MINIT sifar permintaan, proses klien masih HIDUP
laporan `route-manifest-TEMPATAN.json`  TIDAK PERNAH ditulis
timeout Playwright 30 min        tiada output reporter (tersekat dalam pembongkaran)
```

### Punca yang paling mungkin, dan mengapa saya tidak mendakwa lebih

Pelayan `php -S` ialah **satu-benang**. Satu `POST /livewire/update` yang tidak kembali
menjadikan setiap permintaan berikutnya barisan yang tidak pernah dilayan — jadi keseluruhan
larian berhenti. Ini keluarga masalah yang sama seperti yang direkod berulang kali dalam projek
ini untuk gate e2e tempatan.

Saya **tidak** dapat mengesahkan permintaan MANA yang menyekat, kerana tiada bukti separa
tertinggal. Itu sendiri penemuan kedua.

## 🔴 Penemuan reka bentuk: §9.1 ialah SATU ujian monolitik

`e2e/production-guidance-readonly.spec.js` melaksanakan keseluruhan matriks
(10 identiti × 2 viewport × ~40 route) sebagai **satu** `test()`. Akibatnya, diukur pada latihan
ini:

- satu gantung → **seluruh** larian hilang, tanpa hasil separa;
- artifak bukti ditulis **hanya di hujung** → gantung memberi **sifar** bukti;
- timeout 30 minit terlalu panjang untuk maklum balas dan terlalu pendek untuk 800+ muatan
  halaman pada pelayan perlahan — ia gagal pada kedua-dua arah.

⚠️ **Pada PRODUKSI kesannya lebih buruk**, dan itu sebabnya ini penting: gantung akan
membazirkan satu-satunya tetingkap kredensial pemilik, meninggalkan tenant fixture hidup, dan
tidak menghasilkan apa-apa yang boleh dianalisis. Pemilik kemudian diminta membekalkan
kredensial semula.

**Cadangan (BUKAN dilaksanakan dalam F8 — perubahan spec, perlu keputusan):**
1. pecahkan kepada satu `test()` per identiti (10 ujian) atau per identiti×viewport (20) —
   maka "19/20 lulus, 1 gantung" menjadi hasil yang berguna;
2. tulis `route-manifest.json` **secara berperingkat** (corak yang sudah dipakai oleh
   `ukur-mobile-kohort-f8.mjs` dan `ukur-runtime-kohort-f8.mjs` selepas ia dibunuh dua kali);
3. untuk latihan tempatan, jangan guna `php -S` bagi spec ini — ia satu-benang.

## Nota kejujuran

Latihan ini tidak menghasilkan matriks 20 konteks. Ia menghasilkan **tujuh pengesahan kontrak
§9.1a** dan **dua penemuan reka bentuk** yang tidak akan ditemui tanpa mencuba menjalankannya.
Status §9.1 dalam `SUSULAN-PEMBAIKAN.md` kekal ⏸/🔴 — bukan ✅.
