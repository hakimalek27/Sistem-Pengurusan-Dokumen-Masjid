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

### 🔴 Keterukan sebenar: ciri itu rosak untuk **SETIAP** permohonan, bukan hanya medan enum

Anggapan pertama saya ialah "hanya menjejaskan pengguna yang menukar Arah/Sensitiviti". Membaca
borang sebenar membuktikan ia lebih buruk. `ViewRecord.php:66-81` — modal "Mohon Pembetulan"
menghantar **kesemua 12 medan** dengan nilai lalai daripada rekod, dan `sensitivity` ialah
`->required()` (setiap rekod sentiasa bernilai). Jadi **setiap** penghantaran melalui laluan enum.

Diuji sebagai cerita pengguna (#7 "betulkan TAJUK sahaja"), dengan kod produksi dipasang semula:

```
⨯ #7 CERITA PENGGUNA: betulkan TAJUK sahaja — borang tetap hantar sensitivity+direction
   Object of class App\Enums\RecordDirection could not be converted to string
Tests: 1 failed (0 assertions)
```

**Kesimpulan: aliran "Mohon Pembetulan" §9.C tidak pernah berfungsi di produksi.** Tiga ralat
dalam log bukan "tiga kes tepi" — ia tiga kali seseorang mencuba ciri itu, dan gagal ketiga-tiganya.
Guide tour `mohon-pembetulan-rekod` mengajar pengguna menekan butang yang pasti gagal.

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

## (c) Mengapa suite penuh (533 ujian ketika BUG-B ditemui) terlepas pandang

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
Tests:    7 passed (12 assertions)
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
- Pengesahan produksi selepas deploy (milik pemilik — perlu sesi berautentikasi + rekod sebenar):
  buka mana-mana rekod → **Mohon Pembetulan** → tukar **tajuk sahaja** → Hantar. Sebelum
  pembaikan langkah itu memberi ralat pelayan; selepasnya ia sepatutnya menyimpan permohonan
  dengan `proposed_changes` mengandungi tajuk sahaja. Sisi kod dibuktikan oleh reproduksi
  merah→hijau di atas (7 ujian, termasuk cerita pengguna itu sendiri).
