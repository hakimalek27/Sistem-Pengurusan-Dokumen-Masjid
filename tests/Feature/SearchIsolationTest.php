<?php

use App\Models\FileAccessGrant;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('scout.driver', 'collection');
    Storage::fake(config('diwan.storage_disk'));
    $this->svc = app(SearchService::class);

    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');

    $this->recMamDalaman = makeRecord($this->mam, makeFile($this->mam, makeNode($this->mam, '100-4', 'dalaman'), 'dalaman'), 'dalaman', 'surat_menyurat', ['title' => 'DEWAN SERBAGUNA permohonan MAM']);
    $this->recMamSulit = makeRecord($this->mam, makeFile($this->mam, makeNode($this->mam, '200-2', 'sulit'), 'sulit'), 'sulit', 'rekod_kewangan', ['title' => 'RAHSIA kewangan MAM']);
    $this->recMan = makeRecord($this->man, makeFile($this->man, makeNode($this->man, '100-4', 'dalaman'), 'dalaman'), 'dalaman', 'surat_menyurat', ['title' => 'DEWAN SERBAGUNA permohonan MAN']);
});

it('carian MAM tidak memulangkan dokumen MAN (§18.3/§18.18)', function () {
    $admin = makeMember($this->mam, 'admin_masjid');

    $results = $this->svc->for($admin, $this->mam, 'DEWAN');

    expect($results->pluck('mosque_id')->unique()->values()->all())->toBe([$this->mam->id])
        ->and($results->contains('id', $this->recMan->id))->toBeFalse();
});

it('ajk TIDAK nampak rekod sulit dalam carian (§18.18)', function () {
    $ajk = makeMember($this->mam, 'ajk');

    $results = $this->svc->for($ajk, $this->mam, 'RAHSIA');

    expect($results->contains('id', $this->recMamSulit->id))->toBeFalse();
});

it('kerani (peranan istimewa) nampak rekod sulit dalam carian', function () {
    $kerani = makeMember($this->mam, 'kerani');

    $results = $this->svc->for($kerani, $this->mam, 'RAHSIA');

    expect($results->contains('id', $this->recMamSulit->id))->toBeTrue();
});

it('bendahari boleh mencari rekod sulit kewangan tetapi bukan rekod sulit lain', function () {
    $other = makeRecord(
        $this->mam,
        makeFile($this->mam, makeNode($this->mam, '800-1', 'sulit'), 'sulit'),
        'sulit',
        'surat_menyurat',
        ['title' => 'RAHSIA pengurusan MAM'],
    );
    $bendahari = makeMember($this->mam, 'bendahari');

    $results = $this->svc->for($bendahari, $this->mam, 'RAHSIA');

    expect($results->contains('id', $this->recMamSulit->id))->toBeTrue()
        ->and($results->contains('id', $other->id))->toBeFalse();
});

it('geran fail individu turut berkuat kuasa dalam carian OCR', function () {
    $ajk = makeMember($this->mam, 'ajk');
    $admin = makeMember($this->mam, 'admin_masjid');
    FileAccessGrant::query()->create([
        'registry_file_id' => $this->recMamSulit->registry_file_id,
        'user_id' => $ajk->id,
        'granted_by' => $admin->id,
    ]);

    $results = $this->svc->for($ajk, $this->mam, 'RAHSIA');

    expect($results->contains('id', $this->recMamSulit->id))->toBeTrue();
});

it('allowedSensitivities mengecualikan sulit untuk peranan bukan istimewa', function () {
    $ajk = makeMember($this->mam, 'ajk');
    $kerani = makeMember($this->mam, 'kerani');

    expect($this->svc->allowedSensitivities($ajk, $this->mam))->toBe(['umum', 'dalaman'])
        ->and($this->svc->allowedSensitivities($kerani, $this->mam))->toContain('sulit');
});

it('carian fail-closed untuk pengguna yang bukan ahli tenant', function () {
    $outsider = User::query()->create([
        'name' => 'Orang Luar',
        'email' => 'search-outsider@ujian.test',
        'is_active' => true,
    ]);

    $results = app(SearchService::class)->for($outsider, $this->mam, 'rekod');

    expect($results)->toBeEmpty();
});
