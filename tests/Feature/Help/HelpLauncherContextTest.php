<?php

// F1 (PELAN-PEMBAIKAN §2) — konteks HelpLauncher mesti kekal merentas kitaran Livewire.
// Punca asal (RR-01-02): render() membaca request(), yang semasa AJAX ialah
// POST /livewire/update → guide null → Pembantu Diwan hilang pada 19/25 halaman produksi.

use App\Livewire\HelpLauncher;
use App\Models\GuidanceProgress;
use App\Models\HelpEvent;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid');
    $this->super = User::query()->create([
        'name' => 'Super', 'email' => 'super-f1@ujian.test',
        'password' => bcrypt('kata-laluan-ujian'), 'is_superadmin' => true, 'is_active' => true,
    ]);
});

/**
 * Mount komponen dengan query params muat-penuh.
 *
 * Nota: harness Livewire mencipta requestnya sendiri (`/livewire-unit-test-endpoint/…`),
 * jadi `originPath` di sini ialah endpoint itu — bukan halaman sebenar. Nilai path SEBENAR
 * diuji melalui HTTP penuh (#1, #3, #4); Testable digunakan untuk membuktikan konteks
 * KEKAL merentas kitaran update.
 */
function mountLauncher(string $panel, array $params = []): Testable
{
    return Livewire::withQueryParams($params)->test(HelpLauncher::class, ['panel' => $panel]);
}

/**
 * Tukar request semasa kepada `POST /livewire/update` — inilah keadaan sebenar semasa
 * kitaran AJAX Livewire, dan punca asal RR-01-02: kod lama membaca `request()` di dalam
 * `render()`, jadi konteks bertukar menjadi laluan endpoint itu dan guide hilang.
 * Ujian selepas panggilan ini gagal pada kod lama dan lulus pada kod F1.
 */
function simulateLivewireAjaxRequest(): void
{
    app()->instance('request', Request::create('/livewire/update', 'POST'));
}

it('#1 render pada halaman tenant menghasilkan guide betul', function () {
    $this->actingAs($this->admin);
    Filament::setTenant($this->mam);

    $this->get('/app/mam/peti-masuk')
        ->assertOk()
        ->assertSee('data-guide-id="tenant.peti-masuk"', false)
        ->assertSee('asal='.urlencode('/app/mam/peti-masuk'), false);
});

it('#2 konteks kekal selepas kitaran update Livewire', function () {
    $this->actingAs($this->admin);
    Filament::setTenant($this->mam);

    $component = mountLauncher('app', ['panduan' => 'tenant.peti-masuk']);
    $component->assertSee('data-guide-id="tenant.peti-masuk"', false);
    $originAsal = $component->get('originPath');

    // (i) kitaran telemetri: skipRender → TIADA HTML dalam respons, tetapi DB bertambah
    $before = HelpEvent::query()->count();
    $component->call('guidanceProgress', 'tenant.peti-masuk', 'progressed', 1, 'page-content');
    expect($component->effects)->not->toHaveKey('html')
        ->and(HelpEvent::query()->count())->toBe($before + 1);

    // (ii) kitaran update LAIN, dengan request kini POST /livewire/update (keadaan sebenar
    // AJAX). Kod lama membaca request() di sini → guide hilang. Kod F1 guna sifat.
    simulateLivewireAjaxRequest();
    $component->set('showButton', true)
        ->assertSee('data-guide-id="tenant.peti-masuk"', false)
        ->assertSet('originPath', $originAsal)
        ->assertDontSee('livewire/update', false);
});

it('#3 halaman admin mengekalkan guide selepas update', function () {
    $this->actingAs($this->super);

    // (a) muat penuh sebenar: mount menawan path → guide halaman betul (11 halaman
    // superadmin inilah yang paling teruk terjejas oleh RR-01-02).
    $this->get('/admin/mosques')
        ->assertOk()
        ->assertSee('data-guide-id="admin.mosques"', false)
        ->assertSee('asal='.urlencode('/admin/mosques'), false);

    // (b) konteks kekal merentas kitaran AJAX
    $component = mountLauncher('admin', ['panduan' => 'admin.mosques']);
    $component->call('guidanceProgress', 'admin.mosques', 'progressed', 1, 'page-content');
    simulateLivewireAjaxRequest();
    $component->set('showButton', true)
        ->assertSee('data-guide-id="admin.mosques"', false)
        ->assertDontSee('livewire/update', false);
});

it('#4 root helpUrl tiada dua garis miring', function () {
    // RR-01-11: '/'.request()->path() pada root memberi '//'. Kini '/'.trim(path,'/') = '/'.
    $this->get('/')
        ->assertOk()
        ->assertSee('asal=%2F', false)
        ->assertDontSee('asal=%2F%2F', false);
});

it('#5a auto-start ialah one-shot untuk started/dismissed/completed', function (string $event) {
    $this->actingAs($this->admin);
    Filament::setTenant($this->mam);

    $component = mountLauncher('app', ['panduan' => 'tenant.dashboard']);
    $component->assertSee('data-auto-start="1"', false)
        ->assertSee('data-guide-id="tenant.dashboard"', false);

    $component->call('guidanceProgress', 'tenant.dashboard', $event, 0, 'page-content');
    // Kitaran update lain yang me-render: pencetus padam TETAPI konteks kekal.
    $component->set('showButton', true)
        ->assertSee('data-auto-start="0"', false)
        ->assertSee('data-guide-id="tenant.dashboard"', false);
})->with(['started', 'dismissed', 'completed']);

it('#5b muat penuh baharu dengan URL sama menghidupkan semula auto-start', function () {
    $this->actingAs($this->admin);
    Filament::setTenant($this->mam);

    mountLauncher('app', ['panduan' => 'tenant.dashboard'])
        ->call('guidanceProgress', 'tenant.dashboard', 'dismissed', 0, 'page-content')
        ->set('showButton', true)
        ->assertSee('data-auto-start="0"', false);

    // mount() baharu = muat halaman penuh baharu
    mountLauncher('app', ['panduan' => 'tenant.dashboard'])
        ->assertSee('data-auto-start="1"', false);
});

it('#5c pencetus dipadam walaupun guide tidak lagi kelihatan (padam sebelum guard)', function () {
    $this->actingAs($this->admin);
    Filament::setTenant($this->mam);

    $component = mountLauncher('app', ['panduan' => 'tenant.dashboard']);
    $component->assertSee('data-auto-start="1"', false);

    $eventsBefore = HelpEvent::query()->count();
    $progressBefore = GuidanceProgress::query()->count();

    // Guide panel ADMIN tidak kelihatan kepada pengguna tenant → findVisible() null.
    // Namun pencetus untuk guide yang DIMINTA mesti tetap padam bila eventnya tiba.
    $component->call('guidanceProgress', 'admin.mosques', 'started', 0, 'page-content');
    expect(HelpEvent::query()->count())->toBe($eventsBefore)
        ->and(GuidanceProgress::query()->count())->toBe($progressBefore);

    // Guide diminta sendiri: padam berlaku walaupun laluan telemetri terhenti pada guard.
    $component->call('guidanceProgress', 'tenant.dashboard', 'started', 0, 'page-content');
    $component->set('showButton', true)->assertSee('data-auto-start="0"', false);
});

it('#6 telemetri masih ditulis: help_events + guidance_progress', function () {
    $this->actingAs($this->admin);
    Filament::setTenant($this->mam);

    $component = mountLauncher('app');
    $component->call('guidanceProgress', 'tenant.peti-masuk', 'completed', 3, 'page-content');

    expect(HelpEvent::query()->where('guide_id', 'tenant.peti-masuk')->count())->toBe(1)
        ->and(GuidanceProgress::query()
            ->where('user_id', $this->admin->id)
            ->where('guide_id', 'tenant.peti-masuk')->count())->toBe(1);
});

it('#7a keenam-enam sifat Locked menolak tamper dari klien', function (string $property, $value) {
    $this->actingAs($this->admin);
    Filament::setTenant($this->mam);

    mountLauncher('app')->set($property, $value);
})->with([
    ['panel', 'admin'],
    ['mosqueId', 999],
    ['originPath', '/admin/mosques'],
    ['requestedGuideId', 'admin.mosques'],
    ['requestedStep', 5],
    ['launchPending', true],
])->throws(CannotUpdateLockedPropertyException::class);

it('#7b launchPending Locked menolak KEDUA-DUA arah', function () {
    $this->actingAs($this->admin);
    Filament::setTenant($this->mam);

    expect(fn () => mountLauncher('app', ['panduan' => 'tenant.dashboard'])
        ->set('launchPending', false))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('#8 panduan tanpa kebenaran tidak menghasilkan guide mahupun auto-start', function () {
    $this->actingAs($this->admin);
    Filament::setTenant($this->mam);

    // Pengguna tenant meminta guide panel admin melalui ?panduan=
    mountLauncher('app', ['panduan' => 'admin.mosques'])
        ->assertDontSee('data-guide-id="admin.mosques"', false)
        ->assertDontSee('"steps"', false);
});

it('#9 badge taskCount disegarkan pada kitaran render biasa', function () {
    $this->actingAs($this->admin);
    Filament::setTenant($this->mam);

    // Kitaran biasa me-render (berbeza dgn kitaran telemetri yang skipRender) —
    // ini yang mengekalkan badge segar; tingkah laku itu didokumen dlm komponen.
    mountLauncher('app')
        ->set('showButton', true)
        ->assertSee('data-diwan-help-runtime', false);
});

it('#10 auto-start awam one-shot pada /log-masuk', function () {
    $component = mountLauncher('public', ['panduan' => 'public.login']);
    $component->assertSee('data-auto-start="1"', false);

    $component->call('guidanceProgress', 'public.login', 'dismissed', 0, 'page-content');
    $component->set('showButton', true)->assertSee('data-auto-start="0"', false);
});

it('#11 penjaga: mod SPA kekal mati pada kedua-dua panel', function () {
    // Jika penjaga ini bertukar merah, laksanakan spesifikasi beku PELAN §2.2 nota 4
    // (remount per-route atau setOrigin() bervalidasi server) SEBELUM SPA dihidupkan —
    // originPath ditawan pada mount(), jadi navigasi SPA akan meninggalkannya basi.
    expect(Filament::getPanel('app')->hasSpaMode())->toBeFalse()
        ->and(Filament::getPanel('admin')->hasSpaMode())->toBeFalse();

    $hits = [];
    $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
    foreach ($dir as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')
            && str_contains(file_get_contents($file->getPathname()), 'wire:navigate')) {
            $hits[] = $file->getPathname();
        }
    }
    expect($hits)->toBe([]);
});
