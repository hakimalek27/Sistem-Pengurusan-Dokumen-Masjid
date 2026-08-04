# LAPORAN BUG-B — 500 pada permohonan pembetulan rekod (enum → string)

**Cara ditemui:** membaca log ralat produksi semasa menyiasat BUG-A — **bukan** daripada audit,
**bukan** daripada ujian, dan **tidak** dilaporkan oleh sesiapa. Pengguna sebenar terkena
tetapi tiada siapa memberitahu.

---

## (a) Apa yang berlaku

`app/Services/RecordCorrectionService.php` membandingkan nilai rekod SEMASA dengan nilai yang
dimohon untuk menolak "perubahan" yang sebenarnya tidak berubah:

```php
$changes = collect($changes)->reject(fn ($value, $field) =>
    $this->comparable($record->{$field}) === $this->comparable($value))->all();
```

`comparable()` mengendalikan `DateTimeInterface` dan array, kemudian `(string) $value`.
Tetapi `Record::casts()` men-cast **`direction` → `RecordDirection`** dan
**`sensitivity` → `Sensitivity`**, jadi nilai rekod tiba sebagai **objek enum**.
`(string) $enum` ialah `Error` maut dalam PHP → **HTTP 500** kepada pengguna.

Kesan pengguna: sesiapa yang memohon pembetulan pada medan **Arah** atau **Sensitiviti**
sebuah rekod yang sudah bernilai (iaitu rekod normal) mendapat ralat pelayan. Aliran
"Mohon Pembetulan" §9.C — yang ada guide tour sendiri (`mohon-pembetulan-rekod`) — mati
separuh jalan.

## (b) Bukti daripada produksi (log aplikasi, dipetik tanpa data peribadi)

```
[2026-07-22 23:55:31] production.ERROR: Object of class App\Enums\RecordDirection could not be
converted to string {"request_id":"79f2a76b-…","userId":1,"exception":"[object] (Error(code: 0):
Object of class App\Enums\RecordDirection could not be converted to string at
/var/www/html/app/Services/RecordCorrectionService.php:154)
[2026-07-22 23:55:36]  (sama)
[2026-07-22 23:58:58]  (sama)
```

Tiga kali dalam empat minit = pengguna mencuba semula, dan gagal setiap kali.
Kod pada baris itu **masih sama** pada `aaf381a`, jadi pepijat masih **HIDUP** — ia tidak
berulang dalam log hanya kerana tiada siapa mencuba aliran itu lagi sejak 22 Julai.

## (c) Mengapa 533 ujian terlepas pandang

Diukur, bukan diteka:
1. Helper ujian `makeRecord()` **tidak menetapkan `direction`** → nilainya `null`, dan
   `(string) null` = `''` **tidak** melempar.
2. Satu-satunya ujian pembetulan yang ada (`DdmsExtendedCapabilitiesTest`) hanya menukar
   `title` dan `our_ref` — `reject()` hanya membanding medan yang DIHANTAR, jadi laluan enum
   tidak pernah dijalankan.

Gabungan dua fakta itu = laluan yang setiap rekod PRODUKSI lalui tidak pernah diuji.

## (d) Pembaikan

```php
if ($value instanceof \BackedEnum) {
    return (string) $value->value;
}
```

`->value` ialah satu-satunya perbandingan yang betul: nilai borang datang sebagai `'masuk'`,
jadi memulangkan apa-apa lain (cth nama kelas) akan menjadikan **setiap** permohonan kelihatan
seperti "perubahan" walaupun tiada apa berubah — pepijat kedua yang lebih senyap. Ujian #3
mengunci semantik ini secara khusus.

## (e) Output verifikasi SEBENAR

**SEBELUM pembaikan** (ujian ditulis dahulu — reproduksi, bukan dakwaan):

```
Tests:    5 failed (3 assertions)
  ⨯ #1 memohon pembetulan `direction` …   Error: Object of class App\Enums\RecordDirection could not be converted to string
  ⨯ #2 `sensitivity` (enum kedua) …       Error: Object of class App\Enums\Sensitivity could not be converted to string
  ⨯ #3 SEMANTIK …                         Failed asserting that an instance of class Error is an instance of ValidationException
  ⨯ #4 borang menghantar SEMUA medan …    Error: Object of class App\Enums\RecordDirection could not be converted to string
  ⨯ #5 kelulusan mengenakan perubahan …   Error: Object of class App\Enums\RecordDirection could not be converted to string
```

Mesej ralat itu **sama perkataan demi perkataan** dengan log produksi = reproduksi tepat.

**SELEPAS pembaikan:**

```
Tests:    6 passed (11 assertions)
```

## (f) Invarian tambahan (menutup KELAS pepijat, bukan hanya kejadian)

Pembaikan menangkap `\BackedEnum`. Enum tanpa nilai (*pure enum*) akan melemparkan Error yang
sama. Ujian **#6** mengimbas `app/Enums/*.php` dan menuntut **setiap** enum ialah *backed* —
jadi jika sesiapa menambah enum tanpa nilai kemudian, ujian merah **sebelum** pengguna kena 500.
Setakat ini: semua enum projek adalah *backed* (ujian hijau).

## (g) Diperiksa dan didapati SELAMAT (tiada perubahan dibuat)

Corak `(string) $value` yang sama dicari di seluruh `app/`:

| Tapak | Keputusan |
|---|---|
| `Laporan::csvSafe()` | **selamat** — pemanggil sudah hantar `$record->sensitivity?->value` / `status?->value`; `csvSafe` hanya menerima rentetan |
| `ExportService:56` · `QrLabelService:26` | `$record->title` (kolum rentetan) |
| `RecordTypeSchema:164` · `SupportRequestService:133` | nilai borang / rentetan |
| `Mosque:61` | `ctype_digit((string) $value)` pada kunci route |

## (h) Nota

- Tiada migrasi, tiada perubahan UI, tiada pakej. Satu fungsi, empat baris + komen.
- Ujian yang dibuang dari kes ini: **tiada**. Ujian sedia ada tidak diubah — hanya ditambah.
- Pengesahan produksi selepas deploy: ulangi aliran "Mohon Pembetulan" pada medan Arah dan
  pastikan tiada 500. Ia memerlukan sesi berautentikasi + rekod sebenar, jadi ia milik pemilik;
  sisi kod dibuktikan oleh reproduksi merah→hijau di atas.
