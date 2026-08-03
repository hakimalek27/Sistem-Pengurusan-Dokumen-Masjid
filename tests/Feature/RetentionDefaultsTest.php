<?php

use App\Enums\RetentionAction;
use App\Filament\App\Resources\RetentionRules\Pages\CreateRetentionRule;
use App\Filament\App\Resources\RetentionRules\Pages\EditRetentionRule;
use App\Livewire\RegisterMosque;
use App\Models\Mosque;
use App\Models\RetentionRule;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

/**
 * F4 §5.5 — lalai retensi selamat. Menutup RR-08-01 + RR-09-01.
 *
 * Audit mendapati auto-padam ialah tingkah laku LALAI melalui TIGA lapisan bertindan.
 * Enjin retensi sendiri betul (7 ujian `RetentionEngineTest` kekal tidak berubah) — yang
 * dibaiki di sini ialah lalai:
 *   L1 borang peraturan  : `auto_padam` → `semak` + pengesahan sedar
 *   L2 suis per-masjid   : `auto_disposal_enabled` default `true` → `false` (ADDENDUM v2.6)
 *   L3 peraturan platform: KEKAL (keputusan pemilik D3 — patuh tatacara ANM §16.1)
 */
beforeEach(function () {
    cache()->flush(); // had kadar /daftar dikongsi antara ujian (Redis CI)
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');

    Filament::setTenant($this->mam, isQuiet: true);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($this->admin);
});

afterEach(function () {
    Filament::setTenant(null, isQuiet: true);
});

/** Aksi borang vendor adalah `protected` — baca melalui refleksi, bukan diubah suai. */
function formAction(object $page, string $method): Action
{
    $r = new ReflectionMethod($page, $method);
    $r->setAccessible(true);

    return $r->invoke($page);
}

/*
|--------------------------------------------------------------------------
| 1. L1 — lalai borang (§5.5 #1)
|--------------------------------------------------------------------------
*/

test('borang cipta peraturan bermula dengan Semak, bukan Auto Padam', function () {
    Livewire::test(CreateRetentionRule::class)
        ->assertSet('data.action', RetentionAction::Semak->value);
});

/*
|--------------------------------------------------------------------------
| 2. L1 — pengesahan sedar pada KETIGA-TIGA laluan simpan (§5.5 #2)
|--------------------------------------------------------------------------
*/

test('auto_padam mencetuskan pengesahan pada ketiga-tiga laluan simpan', function () {
    $rule = RetentionRule::query()->create([
        'mosque_id' => $this->mam->id,
        'record_type' => 'surat_menyurat',
        'retain_years' => 7,
        'action' => RetentionAction::Semak,
    ]);

    $cipta = Livewire::test(CreateRetentionRule::class)->set('data.action', 'auto_padam');
    $sunting = Livewire::test(EditRetentionRule::class, ['record' => $rule->getKey()])
        ->set('data.action', 'auto_padam');

    foreach ([
        [$cipta, 'getCreateFormAction'],
        [$cipta, 'getCreateAnotherFormAction'],
        [$sunting, 'getSaveFormAction'],
    ] as [$komponen, $kaedah]) {
        $action = formAction($komponen->instance(), $kaedah);

        expect($action->isConfirmationRequired())->toBeTrue("{$kaedah} tiada pengesahan")
            ->and($action->getModalHeading())->toBe('Sahkan peraturan pemadaman automatik')
            ->and((string) $action->getModalDescription())
            ->toContain('PEMADAMAN KEKAL automatik');
    }
});

test('semak TIDAK mencetuskan pengesahan (brek hanya untuk yang berbahaya)', function () {
    $komponen = Livewire::test(CreateRetentionRule::class)->set('data.action', 'semak');

    foreach (['getCreateFormAction', 'getCreateAnotherFormAction'] as $kaedah) {
        expect(formAction($komponen->instance(), $kaedah)->isConfirmationRequired())
            ->toBeFalse("{$kaedah} sepatutnya tiada pengesahan untuk semak");
    }
});

test('simpan TIDAK putus — callback vendor kekal terpasang untuk kedua-dua nilai', function (string $action) {
    // Penjaga terhadap kesilapan yang pelan §5.2 beri amaran: memanggil ->action()/->submit()
    // semula pada aksi vendor memutuskan fungsi simpan sepenuhnya.
    Livewire::test(CreateRetentionRule::class)
        ->set('data.record_type', 'surat_menyurat')
        ->set('data.retain_years', 7)
        ->set('data.action', $action)
        ->call('create')
        ->assertHasNoFormErrors();

    $rule = RetentionRule::query()->withoutGlobalScope('mosque')
        ->where('mosque_id', $this->mam->id)->latest('id')->first();

    expect($rule)->not->toBeNull('peraturan tidak disimpan — callback simpan vendor terputus')
        ->and($rule->action)->toBe(RetentionAction::from($action))
        ->and($rule->mosque_id)->toBe($this->mam->id);
})->with(['semak', 'auto_padam']);

/*
|--------------------------------------------------------------------------
| 3. L2 — kontrak ADDENDUM v2.6 (§5.5 #3 kontrak (b))
|--------------------------------------------------------------------------
| Kontrak (a) lama (`=== true` + teks §16.2 asal) DIGANTIKAN, bukan ditambah —
| rujuk "ADDENDUM v2.6" dalam mesej commit (peraturan #9).
*/

test('masjid BAHARU tanpa override bermula dengan pelupusan automatik DIMATIKAN', function () {
    // Sengaja TIDAK menggunakan makeMosque(): pembantu itu menetapkan
    // auto_disposal_enabled => true secara eksplisit, jadi ia tidak akan sesekali
    // menguji lalai DB yang sebenar.
    $baharu = Mosque::query()->create([
        'name' => 'Masjid Baharu',
        'slug' => 'baharu',
        'code' => 'BHR',
        'status' => 'aktif',
    ]);

    expect($baharu->fresh()->auto_disposal_enabled)->toBeFalse();
});

test('masjid SEDIA ADA tidak disentuh oleh perubahan lalai', function () {
    // `->change()` hanya menukar default kolum; baris yang sudah ada kekal.
    expect($this->mam->fresh()->auto_disposal_enabled)->toBeTrue();
});

test('teks pengakuan /daftar menerangkan keadaan sebenar (§16.2 dipinda v2.6)', function () {
    // Pengakuan berada pada LANGKAH 3 stepper (`@if ($step === 1) … @elseif ($step === 2) …`),
    // jadi GET awal tidak akan sesekali memaparkannya — halaman mesti dipandu ke langkah itu.
    $this->get('/daftar')->assertOk();

    $html = Livewire::test(RegisterMosque::class)
        ->set('step', 3)
        ->html();

    foreach ([
        'Pelupusan automatik dimatikan secara lalai untuk masjid baharu',
        'disenaraikan untuk semakan dan pelupusan manual oleh masjid',
        'Akta Arkib Negara 2003',
    ] as $frasa) {
        expect(str_contains($html, $frasa))->toBeTrue("teks pengakuan tiada: \"{$frasa}\"");
    }
});

/*
|--------------------------------------------------------------------------
| 4. Kiraan impak — tenant-scoped (§5.5 #4, penjaga isolasi §0.6 S1)
|--------------------------------------------------------------------------
*/

test('kiraan impak auto_padam mengira tenant sendiri sahaja', function () {
    $man = makeMosque('MAN', 'man');

    $nodeMam = makeNode($this->mam, '100');
    $fileMam = makeFile($this->mam, $nodeMam);
    makeRecord($this->mam, $fileMam, type: 'surat_menyurat');
    makeRecord($this->mam, $fileMam, type: 'surat_menyurat');

    // Tenant asing: 5 rekod jenis SAMA — mesti TIDAK dikira.
    $nodeMan = makeNode($man, '100');
    $fileMan = makeFile($man, $nodeMan);
    for ($i = 0; $i < 5; $i++) {
        makeRecord($man, $fileMan, type: 'surat_menyurat');
    }

    $page = Livewire::test(CreateRetentionRule::class)
        ->set('data.record_type', 'surat_menyurat')
        ->set('data.retain_years', 7)
        ->set('data.action', 'auto_padam')
        ->instance();

    $ayat = (string) formAction($page, 'getCreateFormAction')->getModalDescription();

    expect($ayat)->toContain('pada masa ini: 2.')
        ->and($ayat)->not->toContain(': 7.')   // 2 + 5 = kebocoran silang-tenant
        ->and($ayat)->toContain('selepas 7 tahun');
});

test('kiraan impak mengikut awalan klasifikasi, juga tenant-scoped', function () {
    $man = makeMosque('MAN', 'man');

    $file200 = makeFile($this->mam, makeNode($this->mam, '200'));
    $file300 = makeFile($this->mam, makeNode($this->mam, '300'));
    makeRecord($this->mam, $file200);
    makeRecord($this->mam, $file200);
    makeRecord($this->mam, $file300);                        // luar awalan
    makeRecord($man, makeFile($man, makeNode($man, '200'))); // tenant asing

    $page = Livewire::test(CreateRetentionRule::class)
        ->set('data.classification_prefix', '200')
        ->set('data.action', 'auto_padam')
        ->instance();

    $ayat = (string) formAction($page, 'getCreateFormAction')->getModalDescription();

    expect($ayat)->toContain('klasifikasi bermula "200"')
        ->and($ayat)->toContain('pada masa ini: 2.');
});

/*
|--------------------------------------------------------------------------
| 5. Migrasi boleh dibalikkan (§5.5 #5)
|--------------------------------------------------------------------------
*/

test('migrasi lalai boleh dirollback dan dijalankan semula', function () {
    // Menguji pemacu SEBENAR, bukan mengandaikan mekanisme dalaman SQLite (§5.3).
    Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]);

    $selepasRollback = Mosque::query()->create([
        'name' => 'Selepas Rollback', 'slug' => 'rollback', 'code' => 'RBK', 'status' => 'aktif',
    ]);
    expect($selepasRollback->fresh()->auto_disposal_enabled)->toBeTrue('down() tidak memulihkan lalai true');

    Artisan::call('migrate', ['--force' => true]);

    $selepasMigrate = Mosque::query()->create([
        'name' => 'Selepas Migrate', 'slug' => 'migrate', 'code' => 'MGR', 'status' => 'aktif',
    ]);
    expect($selepasMigrate->fresh()->auto_disposal_enabled)->toBeFalse('up() tidak menetapkan lalai false');
});
