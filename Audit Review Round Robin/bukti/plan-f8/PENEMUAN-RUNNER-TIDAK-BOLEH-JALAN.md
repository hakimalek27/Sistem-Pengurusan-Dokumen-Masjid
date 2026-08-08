# F8 🔴 PENEMUAN TERBESAR — runner produksi §9.1a TIDAK BOLEH BERJALAN

**Tarikh:** 9 Ogos 2026 · **Keterukan:** TERUK · **Status:** DIBAIKI + disahkan boleh jalan

## Apa yang rosak

`scripts/audit/run-production-guidance-readonly.ps1` ialah **satu-satunya titik masuk** yang
§9.1a benarkan untuk larian produksi F8. Arahan tepatnya (baris 87 sebelum pembaikan):

```powershell
$psi.Arguments = 'playwright test e2e/production-guidance-readonly.spec.js --workers=1'
```

Playwright menapis ujian mengikut **project**. Spec itu **sengaja** diletakkan di luar setiap
project supaya CI tidak pernah menjalankannya, dan `PlanManifestTest.php:172` mengallowlistkannya
dengan sebab *"HANYA melalui wrapper"*. Tetapi tiada project mempunyai `testMatch` untuknya, jadi:

```
$ npx playwright test e2e/production-guidance-readonly.spec.js --workers=1
Error: No tests found.
```

**Disahkan empirikal**, bukan disimpulkan daripada dokumentasi.

## Mengapa ia penting

Allowlist itu **bercanggah dengan dirinya sendiri**: spec dikecualikan daripada project KERANA
ia berjalan melalui wrapper — sedangkan wrapper memerlukan project untuk menjalankannya.

Akibatnya: larian produksi F8 akan gagal **pada saat pemilik akhirnya membekalkan kredensial**,
selepas semua kerja persediaan, dan pada satu-satunya masa kredensial produksi berada dalam
persekitaran. Itu masa paling buruk untuk menemui kecacatan perkakas.

⚠️ Ia **tidak** gagal secara senyap, dan itu kredit kepada reka bentuk F0: playwright keluar
dengan kod 1, dan `assert-playwright-json --min-tests 1` akan menolak laporan kosong. Jadi ia
gagal-tertutup. Tetapi gagal-tertutup pada perkakas yang tidak boleh berjalan tetap bermakna
§9.1a **tidak boleh diselesaikan**.

## Mengapa ia tidak pernah ditangkap

Ketiga-tiga penjaga menyemak perkara yang BERBEZA daripada "bolehkah ia berjalan":

| Penjaga | Apa yang ia sahkan |
|---|---|
| `PlanManifestTest` "setiap spec dalam project ATAU allowlist" | spec **tidak terlepas** daripada liputan — ia dalam allowlist ✔ |
| wrapper `-CleanupOnly` / validasi env | kredensial dan cleanup betul ✔ |
| `assert-playwright-json --min-tests 1` | laporan tidak kosong — tetapi hanya SELEPAS larian |

Tiada satu pun bertanya: **adakah arahan itu menemui ujian?** Dan larian sebenar belum pernah
berlaku, kerana ia menunggu kredensial. Jadi kecacatan itu duduk di belakang satu kebergantungan
luaran — tempat paling selamat untuk pepijat bersembunyi.

## Pembaikan

1. `playwright.config.js` — project `production-readonly` ditambah dengan `testMatch` untuk spec
   itu. Ia **TIDAK** dirujuk oleh `.github/workflows/ci.yml`, jadi CI kekal tidak pernah
   menjalankannya. Ia hanya memberi wrapper sesuatu untuk dipilih.
2. Wrapper menggunakan `--project=production-readonly`.
3. `PlanManifestTest` kekal hijau: penjaganya `$inProject || $inAllowlist`, dan kini kedua-duanya
   benar.

**Disahkan selepas pembaikan:**

```
$ npx playwright test --project=production-readonly --workers=1 --list
  [production-readonly] › production-guidance-readonly.spec.js:76:1 ›
      matriks produksi read-only: 10 identiti × 2 viewport = 20 konteks
Total: 1 test in 1 file
```

## Dan kemudian: latihan PENUH secara tempatan

Membetulkan penemuan ini membuka sesuatu yang lebih bernilai — matriks §9.1 boleh dilatih
**sepenuhnya tanpa kredensial produksi**:

```
php artisan diwan:audit-fixture prepare --run=<uuid>   → tenant smoke-<uuid> + 8 akaun role
superadmin: benih demo tempatan (BUKAN produksi)
E2E_BASE_URL=http://127.0.0.1:8092
```

Fixture berkelakuan seperti spec: kata laluan rawak, ditulis **hanya** ke fail `--json`, stdout
memaparkan e-mel + id sahaja. Latihan itu mengesahkan spec, wrapper, fixture dan cleanup pada
data tempatan — jadi larian produksi kekal satu arahan, bukan satu eksperimen.

🔑 **Pelajaran:** perkakas yang menunggu kebergantungan luaran mesti dilatih terhadap sasaran
TEMPATAN dahulu. "Ia belum pernah dijalankan" bukan status neutral — ia bermakna tidak diketahui,
dan §9.1a mendapati "tidak diketahui" bermaksud "rosak" selama beberapa fasa.
