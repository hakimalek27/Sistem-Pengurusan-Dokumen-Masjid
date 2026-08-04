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
Tests:    6 passed (14 assertions)      tests/Feature/PanelNavigationTest.php
```

### Bukti PENJAGA — ujian menangkap kelakuan lama

`git stash` pada `AppPanelProvider.php` sahaja:

```
⨯ #1 BUG-C: home panel masjid ikut tenant SEMASA, bukan tenant lalai
    Failed asserting that two strings are identical.
    -'/app/man'
    +'http://127.0.0.1:8080/app/mam'          ← tenant LALAI, sedangkan semasa ialah `man`
⨯ #2, ⨯ #3   (bentuk URL vendor)
⨯ #5 BUG-D: item "Panel Pentadbir" tiada
✓ #4 panel /admin tidak terjejas
✓ #6 ahli masjid tidak nampak item itu
```

Baris `+'…/app/mam'` **ialah** pepijat itu, dirakam hitam-putih.

## Nota

- Ujian #4 mendedahkan fakta API yang kini direkod: `Panel::getHomeUrl()` memulangkan **null**
  apabila panel tidak menetapkannya; sandaran `?? getUrl()` berlaku dalam `FilamentManager`.
  Ujian versi pertama saya mengassert pada objek panel sahaja — ia boleh hijau sedangkan UI
  masih salah. Kedua-dua lapisan kini diassert.
- Tiada migrasi, tiada aset, tiada route baharu. Perubahan hanya dalam satu penyedia panel.
- Pengesahan visual selepas deploy (sesi pemilik, tiada kredensial ditaip): buka `/app/mamad`
  dan sahkan href logo = `/app/mamad`, serta item "Panel Pentadbir" muncul dalam menu pengguna.
