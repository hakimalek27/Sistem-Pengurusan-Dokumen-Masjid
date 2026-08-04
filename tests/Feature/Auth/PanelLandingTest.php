<?php

use App\Filament\Auth\Login;
use App\Models\User;
use App\Services\MagicLinkService;
use App\Services\PanelLandingResolver;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

/**
 * BUG-A (laporan pemilik, 5 Ogos 2026) — dua gejala, satu punca setiap satu:
 *
 * 1. "Selepas log masuk kenapa terus ke masjid tenant, bukan panel admin?"
 *    Punca: pautan "Log masuk dengan kata laluan" pada /log-masuk menuju /app/login,
 *    dan LoginResponse lalai Filament mendarat pada panel SEMASA → tenant lalai.
 *    Dibuktikan dari log nginx produksi: 1× GET /app/login, 0× GET /admin/login.
 *
 * 2. "Taip bakwim.my selepas log masuk → nampak halaman log masuk."
 *    Punca: halaman awam tidak pernah mengenal sesi aktif — hanya ada "Log Masuk".
 *
 * Ujian di bawah mengunci KEDUA-DUANYA, termasuk kontrak yang TIDAK boleh berubah:
 * /log-masuk mesti kekal 200 untuk setiap identiti (manifest role_routes beku F0).
 */
beforeEach(function () {
    cache()->flush(); // kaunter throttle dikongsi (cache redis CI serial)
    RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));
});

/** Superadmin dengan kata laluan (boleh guna borang log masuk kata laluan). */
function bugASuperadmin(): User
{
    return User::query()->create([
        'name' => 'Pentadbir Platform',
        'email' => 'super-buga@ujian.test',
        'password' => bcrypt('kata-laluan-ujian'),
        'is_superadmin' => true,
        'is_active' => true,
    ]);
}

// ── Penyelesai pendaratan (satu sumber kebenaran untuk kedua-dua laluan log masuk) ──

it('#1 superadmin mendarat di /admin', function () {
    expect(app(PanelLandingResolver::class)->urlFor(bugASuperadmin()))->toBe('/admin');
});

it('#2 ahli satu masjid (persediaan selesai) mendarat di /app/{slug}', function () {
    $mam = makeMosque('MAM', 'mam');
    $mam->update(['settings' => array_merge($mam->settings ?? [], ['onboarding_done' => now()->toDateTimeString()])]);
    $user = makeMember($mam, 'ajk');

    expect(app(PanelLandingResolver::class)->urlFor($user))->toBe('/app/mam');
});

it('#3 admin masjid yang belum selesai persediaan dibawa ke wizard (§10 Aliran I)', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'admin_masjid');

    expect(app(PanelLandingResolver::class)->urlFor($user))->toBe('/app/mam/persediaan?mula=1');
});

it('#3b withOnboarding: false melangkau wizard (jurang SENGAJA, bukan terlepas pandang)', function () {
    // Lonjakan wizard ialah tugas pautan JEMPUTAN. Log masuk kata laluan & navigasi
    // "Ke Panel" mendarat di papan pemuka — banner persediaan tetap menuntun pengguna.
    // Tanpa jurang ini, setiap admin masjid yang belum selesai persediaan akan berpindah
    // destinasi log masuknya (perubahan kelakuan produksi di luar skop BUG-A).
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'admin_masjid');

    expect(app(PanelLandingResolver::class)->urlFor($user, withOnboarding: false))->toBe('/app/mam');
});

it('#4 ahli >1 masjid mendarat pada pemilih tenant /app', function () {
    $mam = makeMosque('MAM', 'mam');
    $lain = makeMosque('LAIN', 'lain');
    $user = makeMember($mam, 'ajk');
    $lain->users()->attach($user->id, ['role' => 'ajk', 'joined_at' => now()]);

    expect(app(PanelLandingResolver::class)->urlFor($user))->toBe('/app');
});

it('#5 pengguna tanpa masjid aktif mendarat pada /app', function () {
    $user = User::query()->create([
        'name' => 'Tiada Masjid', 'email' => 'kosong@ujian.test',
        'password' => bcrypt('x'), 'is_active' => true,
    ]);

    expect(app(PanelLandingResolver::class)->urlFor($user))->toBe('/app');
});

// ── Log masuk KATA LALUAN (punca gejala 1) ──────────────────────────────────────────

it('#6 GEJALA 1: superadmin log masuk di /app/login mendarat /admin, BUKAN tenant', function () {
    makeMosque('MAM', 'mam'); // ada masjid → getTenants() superadmin memulangkannya
    $super = bugASuperadmin();

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(Login::class)
        ->set('data.login', 'super-buga@ujian.test')
        ->set('data.password', 'kata-laluan-ujian')
        ->call('authenticate')
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($super->fresh());
});

it('#7 ahli masjid log masuk di /app/login tetap mendarat dalam masjidnya', function () {
    $mam = makeMosque('MAM', 'mam');
    $mam->update(['settings' => array_merge($mam->settings ?? [], ['onboarding_done' => now()->toDateTimeString()])]);
    makeMember($mam, 'ajk', 'ajk-buga@ujian.test');

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(Login::class)
        ->set('data.login', 'ajk-buga@ujian.test')
        ->set('data.password', 'kata-laluan-ujian')
        ->call('authenticate')
        ->assertRedirect('/app/mam');
});

it('#7b KONTRAK: admin masjid (persediaan BELUM) + kata laluan → papan pemuka, bukan wizard', function () {
    // Data demo tidak menetapkan onboarding_done, jadi inilah keadaan SEBENAR yang
    // dipandu oleh 6 spec e2e (`explore`, `guidance`, `ci-session-canary`, `ddms-extended`,
    // `ocr-upload`, `office-workflow`) yang semuanya menunggu URL /app/mam.
    $mam = makeMosque('MAM', 'mam');
    makeMember($mam, 'admin_masjid', 'am-buga@ujian.test');

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(Login::class)
        ->set('data.login', 'am-buga@ujian.test')
        ->set('data.password', 'kata-laluan-ujian')
        ->call('authenticate')
        ->assertRedirect('/app/mam');
});

it('#7c PASANGAN: magic link untuk admin masjid SAMA tetap ke wizard (§10 Aliran I utuh)', function () {
    $mam = makeMosque('MAM', 'mam');
    makeMember($mam, 'admin_masjid', 'am-buga@ujian.test');

    $raw = app(MagicLinkService::class)->sendTo('am-buga@ujian.test');

    $this->post('/masuk/'.$raw)->assertRedirect('/app/mam/persediaan?mula=1');
});

it('#8 superadmin log masuk di /admin/login mendarat /admin', function () {
    bugASuperadmin();
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(Login::class)
        ->set('data.login', 'super-buga@ujian.test')
        ->set('data.password', 'kata-laluan-ujian')
        ->call('authenticate')
        ->assertRedirect('/admin');
});

it('#9 deep-link (intended) MASIH menang ke atas pendaratan peranan', function () {
    makeMosque('MAM', 'mam');
    bugASuperadmin();

    session()->put('url.intended', '/app/mam/peti-masuk');
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(Login::class)
        ->set('data.login', 'super-buga@ujian.test')
        ->set('data.password', 'kata-laluan-ujian')
        ->call('authenticate')
        ->assertRedirect('/app/mam/peti-masuk');
});

it('#10 superadmin yang SUDAH log masuk membuka /app/login dialih ke /admin', function () {
    makeMosque('MAM', 'mam');
    $super = bugASuperadmin();

    $this->actingAs($super)->get('/app/login')->assertRedirect('/admin');
});

it('#11 magic link kekal konsisten: superadmin → /admin (laluan dikongsi)', function () {
    makeMosque('MAM', 'mam');
    bugASuperadmin();

    $raw = app(MagicLinkService::class)->sendTo('super-buga@ujian.test');

    $this->post('/masuk/'.$raw)->assertRedirect('/admin');
});

// ── Halaman awam mengenal sesi (punca gejala 2) ─────────────────────────────────────

it('#12 GEJALA 2: laman utama menawarkan panel apabila sesi aktif', function () {
    makeMosque('MAM', 'mam');
    $super = bugASuperadmin();

    $tetamu = $this->get('/')->assertOk();
    expect($tetamu->getContent())
        ->toContain('>Log Masuk<')
        ->not->toContain('Teruskan ke Panel');

    $html = $this->actingAs($super)->get('/')->assertOk()->getContent();
    expect($html)
        ->toContain('Teruskan ke Panel')
        ->toContain('Anda sudah log masuk sebagai')
        ->toContain('href="'.url('/admin').'"');
});

it('#13 /log-masuk kekal 200 untuk sesi aktif (kontrak role_routes) + tawar panel', function () {
    $mam = makeMosque('MAM', 'mam');
    $mam->update(['settings' => array_merge($mam->settings ?? [], ['onboarding_done' => now()->toDateTimeString()])]);
    $ahli = makeMember($mam, 'ajk', 'ajk-buga@ujian.test');
    $super = bugASuperadmin();

    // Manifest baseline F0 menetapkan /log-masuk = allow/200 untuk KESEPULUH identiti.
    // Sebab itu sesi aktif diberitahu di halaman, bukan dialih (dan tukar akaun kekal boleh).
    $htmlSuper = $this->actingAs($super)->get('/log-masuk')->assertOk()->getContent();
    expect($htmlSuper)
        ->toContain('Anda sudah log masuk sebagai')
        ->toContain('href="'.url('/admin').'"')
        ->toContain('data-help-target="login-identity"'); // borang tukar akaun kekal

    auth()->logout();

    $htmlAhli = $this->actingAs($ahli)->get('/log-masuk')->assertOk()->getContent();
    expect($htmlAhli)->toContain('href="'.url('/app/mam').'"');
});

it('#14 penjaga layout: sesi aktif tidak menambah <main>/<header> kedua', function () {
    $super = bugASuperadmin();

    foreach (['/', '/log-masuk', '/daftar', '/bantuan'] as $path) {
        $html = $this->actingAs($super)->get($path)->assertOk()->getContent();

        expect(substr_count($html, '<main'))->toBe(1, "{$path}: bilangan <main> bukan 1")
            ->and(substr_count($html, '<header'))->toBe(1, "{$path}: bilangan <header> bukan 1")
            ->and(str_contains($html, '<main data-help-target="page-content">'))
            ->toBeTrue("{$path}: <main> tiada sasaran page-content");
    }
});

it('#16 akaun tanpa kata laluan TIDAK ditawar panel (elak lantunan EnsurePasswordIsSet)', function () {
    $mam = makeMosque('MAM', 'mam');
    $mam->update(['settings' => array_merge($mam->settings ?? [], ['onboarding_done' => now()->toDateTimeString()])]);
    $baru = makeMember($mam, 'ajk', 'baru-buga@ujian.test', ['password' => null]);

    // Pengguna ini SUDAH log masuk (magic link) tetapi panel akan melantunkannya balik ke
    // /tetapkan-kata-laluan — halaman yang JUGA memakai layout tetamu. Menawarkan "Ke Panel"
    // di sana = menjemput lantunan.
    expect(PanelLandingResolver::urlForCurrentUser())->toBeNull(); // tetamu

    $this->actingAs($baru);
    expect(PanelLandingResolver::urlForCurrentUser())->toBeNull();

    $html = $this->get('/tetapkan-kata-laluan')->assertOk()->getContent();
    expect($html)->not->toContain('>Ke Panel<');

    // Sebaik kata laluan ditetapkan, tawaran itu muncul.
    $baru->forceFill(['password' => bcrypt('kata-laluan-ujian')])->save();
    $this->actingAs($baru->fresh());
    expect(PanelLandingResolver::urlForCurrentUser())->toBe('/app/mam');
});

it('#15 nav tetamu papar "Ke Panel" hanya apabila log masuk', function () {
    $super = bugASuperadmin();

    expect($this->get('/bantuan')->assertOk()->getContent())->not->toContain('>Ke Panel<');
    expect($this->actingAs($super)->get('/bantuan')->assertOk()->getContent())->toContain('>Ke Panel<');
});
