<?php

use App\Enums\MinitPriority;
use App\Enums\Sensitivity;
use App\Enums\SourceChannel;
use App\Filament\App\Resources\MosqueActivityLogs\MosqueActivityLogResource;
use App\Models\MosqueActivityLog;
use App\Services\InboxIngestService;
use App\Services\MinitService;
use App\Services\MosqueActivityLogger;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    $this->mamNode = makeNode($this->mam, '100-1');
    $this->manNode = makeNode($this->man, '100-1');
    $this->mamFile = makeFile($this->mam, $this->mamNode);
    $this->manFile = makeFile($this->man, $this->manNode);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->mam, isQuiet: true);
});

afterEach(function () {
    Filament::setTenant(null, isQuiet: true);
});

it('membenarkan Log Aktiviti hanya kepada empat role yang ditetapkan', function () {
    $allowed = ['admin_masjid', 'pengerusi', 'setiausaha', 'bendahari'];

    foreach (config('roles.list') as $role) {
        $user = makeMember($this->mam, $role);
        $this->actingAs($user);

        expect(MosqueActivityLogResource::canViewAny())
            ->toBe(in_array($role, $allowed, true), "Log Aktiviti: {$role}");

        $response = $this->get('/app/mam/log-aktiviti');
        in_array($role, $allowed, true)
            ? $response->assertOk()
            : $response->assertForbidden();
    }
});

it('mengasingkan timeline dan snapshot antara dua masjid', function () {
    $mamAdmin = makeMember($this->mam, 'admin_masjid');
    $manAdmin = makeMember($this->man, 'admin_masjid');
    $logger = app(MosqueActivityLogger::class);

    $logger->log($this->mam, 'uji_mam', 'Aktiviti sulit MAM', $mamAdmin);
    $logger->log($this->man, 'uji_man', 'Aktiviti sulit MAN', $manAdmin);

    $this->actingAs($mamAdmin);
    expect(MosqueActivityLogResource::getEloquentQuery()->pluck('description')->all())
        ->toBe(['Aktiviti sulit MAM']);

    $this->get('/app/mam/log-aktiviti')
        ->assertOk()
        ->assertSee('Aktiviti sulit MAM')
        ->assertDontSee('Aktiviti sulit MAN');
});

it('tidak membocorkan tajuk rekod terhad kepada Bendahari melalui log', function () {
    $admin = makeMember($this->mam, 'admin_masjid');
    $bendahari = makeMember($this->mam, 'bendahari');
    $generalRecord = makeRecord($this->mam, $this->mamFile, 'dalaman', attrs: ['title' => 'Rekod Umum']);
    $restrictedFile = makeFile($this->mam, makeNode($this->mam, '800-9', 'sulit'), 'sulit');
    $restrictedRecord = makeRecord($this->mam, $restrictedFile, 'sulit', attrs: ['title' => 'Rahsia Pentadbiran']);
    $logger = app(MosqueActivityLogger::class);

    $logger->log($this->mam, 'general', 'Aktiviti Rekod Umum', $admin, $generalRecord, $generalRecord);
    $logger->log($this->mam, 'restricted', 'Aktiviti Rahsia Pentadbiran', $admin, $restrictedRecord, $restrictedRecord);

    $this->actingAs($bendahari);
    expect(MosqueActivityLogResource::getEloquentQuery()->pluck('description')->all())
        ->toBe(['Aktiviti Rekod Umum']);

    $this->get('/app/mam/log-aktiviti')
        ->assertOk()
        ->assertSee('Aktiviti Rekod Umum')
        ->assertDontSee('Rahsia Pentadbiran');
});

it('menolak snapshot rekod atau fail tenant lain', function () {
    $admin = makeMember($this->mam, 'admin_masjid');
    $manRecord = makeRecord($this->man, $this->manFile);

    expect(fn () => app(MosqueActivityLogger::class)->log(
        $this->mam,
        'cross_tenant',
        'Tidak boleh disimpan',
        $admin,
        $manRecord,
        $manRecord,
    ))->toThrow(InvalidArgumentException::class);

    expect(fn () => app(MosqueActivityLogger::class)->log(
        $this->mam,
        'cross_tenant_subject',
        'Tidak boleh disimpan',
        $admin,
        $this->manFile,
    ))->toThrow(InvalidArgumentException::class);

    expect(MosqueActivityLog::query()->withoutGlobalScope('mosque')->count())->toBe(0);
});

it('merekod perjalanan upload klasifikasi dan minit dengan provenance lengkap', function () {
    $admin = makeMember($this->mam, 'admin_masjid');
    $chair = makeMember($this->mam, 'pengerusi');
    $ingest = app(InboxIngestService::class);

    $record = $ingest->ingest(
        $this->mam,
        'dokumen-log-aktiviti',
        'surat-log.txt',
        'text/plain',
        null,
        SourceChannel::Emel,
        ['from' => 'test123@example.test', 'subject' => 'Surat Log Aktiviti', 'message_id' => 'MSG-1', 'ip' => '203.0.113.8'],
    );

    $ingest->fileRecord($record, $this->mamFile, [
        'record_type' => 'surat_menyurat',
        'title' => 'Surat Log Aktiviti',
        'record_date' => now()->toDateString(),
        'received_date' => now()->toDateString(),
        'direction' => 'masuk',
    ], $admin, Sensitivity::Dalaman);

    app(MinitService::class)->create(
        $record->fresh(),
        $admin,
        [$chair->id],
        [],
        'Untuk perhatian dan tindakan Pengerusi.',
        MinitPriority::Segera,
    );

    $logs = MosqueActivityLog::query()->withoutGlobalScope('mosque')
        ->where('mosque_id', $this->mam->id)
        ->orderBy('id')
        ->get();

    expect($logs->pluck('action')->all())->toBe([
        'record_uploaded', 'record_classified', 'minit_created',
    ])->and($logs[0]->source_channel)->toBe('emel')
        ->and($logs[0]->source_identifier)->toBe('test123@example.test')
        ->and($logs[0]->ip_address)->toBe('203.0.113.8')
        ->and($logs[1]->file_no)->toBe($this->mamFile->file_no)
        ->and($logs[1]->record_reference)->toContain($this->mamFile->file_no)
        ->and($logs[2]->metadata['action_recipients'])->toBe([$chair->name])
        ->and($logs[2]->metadata['priority'])->toBe('segera');
});

it('tidak membenarkan log diubah atau dipadam dan tiada route mutasi', function () {
    $admin = makeMember($this->mam, 'admin_masjid');
    $log = app(MosqueActivityLogger::class)->log($this->mam, 'uji', 'Kekal', $admin);

    expect(fn () => $log->update(['description' => 'Diubah']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);

    $this->actingAs($admin)->get('/app/mam/log-aktiviti/create')->assertNotFound();
    $this->actingAs($admin)->get('/app/mam/log-aktiviti/'.$log->id.'/edit')->assertNotFound();
});
