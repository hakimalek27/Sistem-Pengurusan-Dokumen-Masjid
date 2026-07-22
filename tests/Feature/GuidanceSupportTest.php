<?php

use App\Models\GuidanceProgress;
use App\Models\SupportRequest;
use App\Services\AntivirusScanner;
use App\Services\GuidanceService;
use App\Services\HelpCatalog;
use App\Services\SupportRequestService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('local');
    config()->set('diwan.clamav.enabled', false);
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    $this->mamAdmin = makeMember($this->mam, 'admin_masjid');
    $this->manAdmin = makeMember($this->man, 'admin_masjid');
});

it('mengasingkan progress mengikut pengguna dan tenant', function () {
    $guide = app(HelpCatalog::class)->findVisible('tenant.dashboard', 'app', $this->mamAdmin, $this->mam);
    app(GuidanceService::class)->record($this->mamAdmin, 'app', $this->mam, $guide, 'completed', 3);

    expect(GuidanceProgress::query()->where('user_id', $this->mamAdmin->id)->where('mosque_id', $this->mam->id)->count())->toBe(1)
        ->and(GuidanceProgress::query()->where('user_id', $this->manAdmin->id)->count())->toBe(0)
        ->and(app(HelpCatalog::class)->findVisible('tenant.peti-masuk', 'app', $this->manAdmin, $this->mam))->toBeNull();
});

it('menyambung tour ditutup dan mengulang tour yang telah selesai dari awal', function () {
    $guide = app(HelpCatalog::class)->findVisible('tenant.peti-masuk', 'app', $this->mamAdmin, $this->mam);
    $guidance = app(GuidanceService::class);
    $guidance->record($this->mamAdmin, 'app', $this->mam, $guide, 'dismissed', 2);

    expect($guidance->resumeStep($this->mamAdmin, 'app', $this->mam, $guide))->toBe(2);

    $guidance->record($this->mamAdmin, 'app', $this->mam, $guide, 'completed', 3);
    expect($guidance->resumeStep($this->mamAdmin, 'app', $this->mam, $guide))->toBe(0);
});

it('mencipta tiket tenant dengan lampiran private dan antivirus metadata', function () {
    $this->actingAs($this->mamAdmin);
    $file = UploadedFile::fake()->createWithContent('bukti.txt', 'bukti tanpa data dokumen');

    $ticket = app(SupportRequestService::class)->create([
        'category' => 'upload', 'subject' => 'Upload gagal secara konsisten',
        'expected' => 'Fail diterima', 'actual' => 'Validasi dipaparkan',
        'route_template' => '/app/mam/records/123?token=rahsia', 'request_id' => 'req-123',
        'browser_context' => ['browser' => 'Chrome', 'password' => 'jangan-simpan'],
        'query_consent' => false, 'unmatched_query' => 'jangan-simpan-query',
    ], $this->mamAdmin, $this->mam, 'app', $file);

    expect($ticket->mosque_id)->toBe($this->mam->id)
        ->and($ticket->route_template)->toBe('/app/{tenant}/records/{id}')
        ->and($ticket->browser_context)->not->toHaveKey('password')
        ->and($ticket->unmatched_query)->toBeNull()
        ->and($ticket->attachments)->toHaveCount(1)
        ->and($ticket->attachments->first()->scan_status)->toBe('disabled');
    Storage::disk('local')->assertExists($ticket->attachments->first()->path);
});

it('menolak tiket silang tenant dan akses tiket tenant lain', function () {
    $this->actingAs($this->manAdmin);

    expect(fn () => app(SupportRequestService::class)->create([
        'category' => 'lain', 'subject' => 'Cubaan silang tenant', 'expected' => 'Ditolak', 'actual' => 'Cubaan',
    ], $this->manAdmin, $this->mam, 'app'))->toThrow(ValidationException::class);

    $ticket = SupportRequest::query()->create([
        'reference' => 'SUP-TEST-MAM', 'mosque_id' => $this->mam->id, 'user_id' => $this->mamAdmin->id,
        'panel' => 'app', 'role' => 'admin_masjid', 'category' => 'lain', 'subject' => 'MAM sahaja',
        'expected' => 'MAM', 'actual' => 'MAM', 'status' => 'baharu',
    ]);

    expect($this->manAdmin->can('view', $ticket))->toBeFalse()
        ->and($this->mamAdmin->can('view', $ticket))->toBeTrue();
});

it('menolak kategori yang diubah suai dan lampiran yang dikesan antivirus', function () {
    $this->actingAs($this->mamAdmin);
    $payload = [
        'category' => 'kategori-palsu', 'subject' => 'Cubaan ubah kategori',
        'expected' => 'Kategori ditolak', 'actual' => 'Nilai telah diubah',
    ];

    expect(fn () => app(SupportRequestService::class)->create(
        $payload, $this->mamAdmin, $this->mam, 'app',
    ))->toThrow(ValidationException::class);

    config()->set('diwan.clamav.enabled', true);
    config()->set('diwan.clamav.fail_closed', true);
    $scanner = Mockery::mock(AntivirusScanner::class);
    $scanner->shouldReceive('scan')->once()->andReturn([
        'status' => 'infected', 'signature' => 'Eicar-Test-Signature', 'message' => 'FOUND',
    ]);
    $this->app->instance(AntivirusScanner::class, $scanner);
    $payload['category'] = 'antivirus';

    expect(fn () => app(SupportRequestService::class)->create(
        $payload,
        $this->mamAdmin,
        $this->mam,
        'app',
        UploadedFile::fake()->createWithContent('bukti.txt', 'kandungan ujian'),
    ))->toThrow(ValidationException::class);
    expect(SupportRequest::query()->count())->toBe(0);
});

it('memadam fail lampiran private bersama tiket selepas tempoh retensi', function () {
    $this->actingAs($this->mamAdmin);
    $ticket = app(SupportRequestService::class)->create([
        'category' => 'lain', 'subject' => 'Tiket lama untuk retensi',
        'expected' => 'Dipadam selepas tempoh', 'actual' => 'Masih tersedia',
    ], $this->mamAdmin, $this->mam, 'app', UploadedFile::fake()->createWithContent('lama.txt', 'bukti lama'));
    $path = $ticket->attachments->first()->path;
    $ticket->forceFill(['created_at' => now()->subMonths(25)])->save();

    $this->artisan('diwan:prune-logs')->assertSuccessful();

    expect(SupportRequest::query()->find($ticket->id))->toBeNull();
    Storage::disk('local')->assertMissing($path);
});

it('mengembalikan request id pada respons tanpa mempercayai nilai klien', function () {
    $response = $this->withHeader('X-Request-ID', 'nilai-palsu')->get('/');

    $response->assertOk()->assertHeader('X-Request-ID');
    expect($response->headers->get('X-Request-ID'))->not->toBe('nilai-palsu');

    $this->get('/laluan-tidak-wujud')->assertNotFound()->assertHeader('X-Request-ID');
});
