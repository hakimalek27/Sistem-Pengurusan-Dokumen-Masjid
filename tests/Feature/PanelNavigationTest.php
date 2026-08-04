<?php

use App\Models\User;
use Filament\Facades\Filament;

/**
 * BUG-C & BUG-D — ditemui dengan MENGUKUR panel masjid produksi semasa menyiasat BUG-A
 * (bukan dilaporkan, bukan dalam audit).
 *
 * BUG-C: logo topbar/sidebar memakai `filament()->getHomeUrl()`. Lalai Filament
 *   (`HasRoutes::getUrl()`) menyelesaikan tenant sebagai `Filament::getUserDefaultTenant()` —
 *   tenant **LALAI** pengguna, bukan tenant **SEMASA**. `User::getTenants()` memulangkan SEMUA
 *   masjid aktif untuk superadmin, jadi "home" melompat ke masjid PERTAMA platform.
 *   Diukur hidup: berada di /app/mamad, href logo = /app/smoke.
 *
 * BUG-D: superadmin dalam panel masjid tiada jalan balik ke /admin — diukur hidup: 0 daripada
 *   38 pautan. Item MENU PENGGUNA dipilih (bukan item navigasi) supaya medan `in_navigation`
 *   dalam manifest role_routes beku tidak berubah.
 */
function bugCSuperadmin(): User
{
    return User::query()->create([
        'name' => 'Pentadbir Platform',
        'email' => 'super-bugc@ujian.test',
        'password' => bcrypt('kata-laluan-ujian'),
        'is_superadmin' => true,
        'is_active' => true,
    ]);
}

it('#1 BUG-C: home panel masjid ikut tenant SEMASA, bukan tenant lalai', function () {
    $mam = makeMosque('MAM', 'mam');   // tenant lalai superadmin (yang pertama)
    $man = makeMosque('MAN', 'man');   // tenant yang sedang dibuka
    $super = bugCSuperadmin();

    $this->actingAs($super);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($man);

    // Dokumenkan pepijat dengan tepat: tenant lalai BUKAN tenant semasa untuk superadmin.
    expect(Filament::getUserDefaultTenant($super)?->slug)->toBe($mam->slug)
        ->and(Filament::getTenant()->slug)->toBe($man->slug);

    // Blade logo memanggil `filament()->getHomeUrl()` (FilamentManager), yang jatuh balik
    // kepada `Panel::getUrl()` apabila panel tiada homeUrl. Assert LALUAN RENDER itu, bukan
    // hanya penutup panel — kalau tidak, ujian boleh hijau sedangkan UI masih salah.
    expect(Filament::getHomeUrl())->toEndWith('/app/man')
        ->and(Filament::getHomeUrl())->not->toEndWith('/app/mam')
        ->and(Filament::getPanel('app')->getHomeUrl())->toEndWith('/app/man');
});

it('#2 BUG-C: ahli satu masjid tetap mendapat masjidnya sendiri', function () {
    $mam = makeMosque('MAM', 'mam');
    $ahli = makeMember($mam, 'ajk', 'ajk-bugc@ujian.test');

    $this->actingAs($ahli);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($mam);

    expect(Filament::getHomeUrl())->toEndWith('/app/mam');
});

it('#3 BUG-C: tiada tenant (cth halaman log masuk) → pemilih tenant, bukan ralat', function () {
    makeMosque('MAM', 'mam');
    $super = bugCSuperadmin();

    $this->actingAs($super);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant(null);

    expect(Filament::getHomeUrl())->toEndWith('/app');
});

it('#4 BUG-C: panel /admin tidak terjejas (fallback vendor kekal)', function () {
    $super = bugCSuperadmin();

    $this->actingAs($super);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // Panel admin TIDAK menetapkan homeUrl, jadi `Panel::getHomeUrl()` = null dan sandaran
    // `?? getUrl()` dalam FilamentManager yang berkuasa. Kedua-duanya diassert supaya
    // perbezaan itu direkod, bukan ditemui semula pada masa depan.
    expect(Filament::getPanel('admin')->getHomeUrl())->toBeNull()
        ->and(Filament::getHomeUrl())->toEndWith('/admin');
});

it('#5 BUG-D: superadmin dapat item "Panel Pentadbir" dalam panel masjid', function () {
    $mam = makeMosque('MAM', 'mam');
    $super = bugCSuperadmin();

    $this->actingAs($super);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($mam);

    $items = Filament::getPanel('app')->getUserMenuItems();

    expect($items)->toHaveKey('panelPentadbir')
        ->and($items['panelPentadbir']->getLabel())->toBe('Panel Pentadbir')
        ->and($items['panelPentadbir']->getUrl())->toBe('/admin');
});

it('#6 BUG-D: ahli masjid TIDAK nampak item itu (ia 403 untuk mereka)', function () {
    $mam = makeMosque('MAM', 'mam');
    $ahli = makeMember($mam, 'admin_masjid', 'am-bugc@ujian.test');

    $this->actingAs($ahli);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($mam);

    // Penjaga sebenar: /admin memang 403 untuk ahli (canAccessPanel), jadi memaparkan pautan
    // itu kepada mereka bermakna menjemput ralat. Kebenaran panel diuji di tempat lain;
    // di sini kita tuntut UI tidak menawarkannya.
    expect($ahli->canAccessPanel(Filament::getPanel('admin')))->toBeFalse()
        ->and(Filament::getPanel('app')->getUserMenuItems())->not->toHaveKey('panelPentadbir');
});

it('#7 BUG-D pada laluan RENDER: item muncul dalam HTML panel masjid', function () {
    $mam = makeMosque('MAM', 'mam');
    $mam->update(['settings' => array_merge($mam->settings ?? [], ['onboarding_done' => now()->toDateTimeString()])]);
    $super = bugCSuperadmin();

    // Pelajaran ujian #4: objek config boleh betul sedangkan render salah. Assert HTML.
    $html = $this->actingAs($super)->get('/app/mam')->assertOk()->getContent();

    expect(str_contains($html, 'Panel Pentadbir'))->toBeTrue('item menu pengguna tidak dirender')
        ->and(str_contains($html, 'href="/admin"') || str_contains($html, 'href="'.url('/admin').'"'))
        ->toBeTrue('pautan /admin tidak dirender');
});

it('#8 BUG-D: ahli masjid tidak dapat item itu dalam HTML', function () {
    $mam = makeMosque('MAM', 'mam');
    $mam->update(['settings' => array_merge($mam->settings ?? [], ['onboarding_done' => now()->toDateTimeString()])]);
    $ahli = makeMember($mam, 'admin_masjid', 'am-render@ujian.test');

    $html = $this->actingAs($ahli)->get('/app/mam')->assertOk()->getContent();

    expect(str_contains($html, 'Panel Pentadbir'))->toBeFalse('ahli masjid tidak sepatutnya nampak pautan /admin');
});
