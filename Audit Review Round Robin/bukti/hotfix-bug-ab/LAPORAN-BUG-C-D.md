# LAPORAN BUG-C & BUG-D — navigasi panel masjid untuk superadmin

**Cara ditemui:** mengukur panel masjid produksi semasa menyiasat BUG-A. Soalan yang membawa
kepadanya: *"kalau superadmin kini mendarat di /admin, ada jalan sah masuk ke masjid?"* —
jawapannya ada (`/admin/mosques` memaut ke setiap tenant), tetapi ukuran yang sama mendedahkan
dua kecacatan **arah balik**.

---

## BUG-C — "home" dalam panel masjid melompat ke masjid LAIN

### Diukur hidup di produksi

```
Berada di : https://bakwim.my/app/mamad     (Papan pemuka - Diwan)
href logo : https://bakwim.my/app/smoke     ← tenant BERBEZA
```

**Pengukuran KAWALAN — mengapa tiada siapa menyedarinya:**

| Berada di | href logo | Keputusan |
|---|---|---|
| `/app/smoke` (tenant **lalai** platform) | `/app/smoke` | nampak betul — pepijat **halimunan** |
| `/app/mamad` | `/app/smoke` | tenant salah |
| `/app/mamad/peti-masuk` | `/app/smoke` | tenant salah, walaupun sedang bekerja dalam halaman |

Kawalan ini penting: ia membuktikan puncanya "tenant lalai", bukan "logo rosak". Sesiapa yang
menguji pada masjid pertama platform akan melihat kelakuan yang betul dan menyimpulkan tiada
masalah.

### Punca

Logo topbar dan sidebar merender `filament()->getHomeUrl()`
(`vendor/filament/.../livewire/topbar.blade.php:98`, `sidebar.blade.php:82`).
`FilamentManager::getHomeUrl()` jatuh balik kepada `Panel::getUrl()`, dan `getUrl()`
menyelesaikan tenant seperti ini:

```php
if ((! $tenant) && $hasTenancy && $this->auth()->hasUser()) {
    $tenant = Filament::getUserDefaultTenant($this->auth()->user());   // ← LALAI, bukan SEMASA
}
```

Untuk ahli biasa dengan satu masjid, lalai = semasa, jadi pepijat ini **tidak kelihatan**.
Untuk **superadmin**, `User::getTenants()` memulangkan **SEMUA** masjid aktif, jadi tenant lalai
ialah masjid **pertama platform**. Kesannya: sedang bekerja dalam masjid A, tekan logo → dibuang
ke masjid B. Ahli berbilang-masjid turut terjejas.

### Pembaikan

`AppPanelProvider` menetapkan `->homeUrl()` yang mengikat kepada **tenant semasa**
(`Filament::getTenant()`), dengan `url()` supaya bentuk URL kekal sama seperti vendor —
satu-satunya perbezaan ialah tenant yang dipilih. Panel `/admin` tidak disentuh.

---

## BUG-D — superadmin tiada jalan balik ke `/admin`

### Diukur hidup di produksi

```
/app/mamad  → 38 pautan, 0 menuju /admin
/admin      → 19 pautan, 0 menuju /app        (dashboard; senarai Tenant ADA)
```

Superadmin yang masuk ke panel masjid hanya boleh keluar dengan menaip URL. Ini melengkapkan
kekeliruan yang pemilik laporkan: mendarat dalam masjid **dan** tiada jalan balik.

### Pembaikan

Item **menu pengguna** "Panel Pentadbir" → `/admin`, kelihatan hanya apabila `is_superadmin`.

**Mengapa menu pengguna dan bukan item navigasi:** medan `in_navigation` dalam manifest
`role_routes` beku dikira daripada `ClassName::shouldRegisterNavigation()`
(`RoleRoutes::declaredAccess()` baris 376). Item navigasi baharu berisiko mengubah medan itu;
item menu pengguna **tidak** menyentuh manifest sama sekali. Diperiksa sebelum memilih mekanisme,
bukan selepas.

Ahli masjid tidak melihatnya kerana `/admin` memang **403** untuk mereka
(`User::canAccessPanel()`), dan memaparkan pautan yang membawa kepada ralat adalah lebih buruk
daripada tidak memaparkannya.

---

## Output verifikasi SEBENAR

```
Tests:    8 passed (19 assertions)      tests/Feature/PanelNavigationTest.php
```

Ujian **#7/#8** mengassert pada **laluan RENDER**, bukan objek config: HTML `/app/mam` yang
dihantar pelayan MENGANDUNGI "Panel Pentadbir" + `href` `/admin` untuk superadmin, dan **TIDAK**
mengandunginya untuk ahli masjid. (Sebab tambahan: pertanyaan DOM pertama saya pada produksi
memulangkan senarai kosong untuk menu pengguna — nama kelas Filament 4 berbeza daripada tekaan
saya, bukan kerana item dirender pelanggan-sisi. Ujian render ini menutup jurang itu.)

### Bukti PENJAGA — ujian menangkap kelakuan lama

`AppPanelProvider.php` sahaja dipulihkan kepada `aaf381a` (kod yang SEDANG berjalan di produksi):

```
Tests:    4 failed, 4 passed (14 assertions)

⨯ #1 home panel masjid ikut tenant SEMASA          ← PEPIJAT: dapat …/app/mam, semasa `man`
⨯ #3 tiada tenant → pemilih tenant                 ← vendor tetap memilih tenant lalai
⨯ #5 item "Panel Pentadbir" (objek menu)
⨯ #7 item "Panel Pentadbir" (HTML dirender)
✓ #2 ahli satu masjid mendapat masjidnya sendiri
✓ #4 panel /admin tidak terjejas
✓ #6 / ✓ #8 ahli masjid tidak nampak item itu
```

⭐ **#2 LULUS dengan kod lama, dan itu BETUL** — untuk ahli satu-masjid, tenant lalai **ialah**
tenant semasa, jadi vendor berkelakuan betul untuk mereka. Bukti penjaga ini dengan sendirinya
menunjukkan mengapa pepijat itu halimunan selama ini, dan mengesahkan pembaikan **tidak**
mengubah kelakuan bagi majoriti pengguna — hanya bagi superadmin dan ahli berbilang-masjid.

⚠️ Versi PERTAMA ujian ini mengassert kesamaan rentetan penuh (`toBe('/app/mam')`), jadi #2/#3
gagal atas **bentuk URL** vendor (mutlak vs relatif) — bunyi yang menyembunyikan isyarat. Selepas
assertion ditukar kepada `toEndWith` (bebas-hos) dan `homeUrl()` memakai `url()`, kegagalan yang
tinggal semuanya bermakna. Penjaga yang gagal atas sebab yang salah tetap merah — tetapi ia tidak
membuktikan apa-apa.

## Nota

- Ujian #4 mendedahkan fakta API yang kini direkod: `Panel::getHomeUrl()` memulangkan **null**
  apabila panel tidak menetapkannya; sandaran `?? getUrl()` berlaku dalam `FilamentManager`.
  Ujian versi pertama saya mengassert pada objek panel sahaja — ia boleh hijau sedangkan UI
  masih salah. Kedua-dua lapisan kini diassert.
- Tiada migrasi, tiada aset, tiada route baharu. Perubahan hanya dalam satu penyedia panel.
- Pengesahan visual selepas deploy (sesi pemilik, tiada kredensial ditaip): buka `/app/mamad`
  dan sahkan href logo = `/app/mamad`, serta item "Panel Pentadbir" muncul dalam menu pengguna.
