<?php

use App\Contracts\DriveClient;
use App\Enums\SourceChannel;
use App\Jobs\SyncRecordToDriveJob;
use App\Models\ClassificationNode;
use App\Models\Mosque;
use App\Models\PlatformSetting;
use App\Models\Record;
use App\Models\User;
use App\Services\DisposalService;
use App\Services\GoogleDrive\DriveConfig;
use App\Services\GoogleDrive\DriveSyncService;
use App\Services\InboxIngestService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeDriveClient;

/*
 * §4.6′ — Mirror per-tenant ke Google Drive: susunan folder, ISOLASI silang-tenant,
 * ganti versi, pelupusan, sanitasi, toggle mati. Guna FakeDriveClient (tiada Google).
 */

beforeEach(function () {
    config()->set('scout.driver', 'collection');
    Storage::fake(config('diwan.storage_disk'));
    Queue::fake(); // elak ProcessOcrJob + biar kita panggil syncRecord terus

    $this->fake = new FakeDriveClient;
    app()->instance(DriveClient::class, $this->fake);

    PlatformSetting::put('gdrive_enabled', true);
    PlatformSetting::putEncrypted('gdrive_refresh_token', 'rt');
    DriveConfig::forget();

    $this->sync = app(DriveSyncService::class);
});

function mirrorFiledRecord(Mosque $mosque, User $filer, string $content = 'kandungan dokumen ujian'): Record
{
    $fungsi = makeNode($mosque, '100', 'dalaman', 'fungsi');
    $akt = ClassificationNode::query()->create([
        'mosque_id' => $mosque->id, 'parent_id' => $fungsi->id, 'level' => 'aktiviti',
        'code' => '100-1', 'title' => 'Mesyuarat Agung', 'default_sensitivity' => 'dalaman', 'is_active' => true,
    ]);
    $file = makeFile($mosque, $akt);

    $svc = app(InboxIngestService::class);
    $record = $svc->ingest($mosque, $content, 'surat.pdf', 'application/pdf', $filer, SourceChannel::MuatNaik);

    return $svc->fileRecord($record, $file, ['title' => 'Surat Penting'], $filer);
}

it('cipta folder masjid pada resolusi pertama (SPDM/Backup/{slug})', function () {
    $mam = makeMosque('MAM', 'mam');
    $id = $this->sync->mosqueFolderId($mam);

    expect($this->fake->pathOf($id))->toBe('SPDM/Backup/mam')
        ->and($mam->fresh()->gdrive_folder_id)->toBe($id);
});

it('rekod difailkan → mirror ke rantaian folder klasifikasi yang betul', function () {
    $mam = makeMosque('MAM', 'mam');
    $admin = makeMember($mam, 'admin_masjid', 'a@mam.test');
    $record = mirrorFiledRecord($mam, $admin);

    $this->sync->syncRecord($record);

    $fileId = $record->fresh()->gdrive_file_id;
    expect($fileId)->not->toBeNull();
    $path = $this->fake->pathOf($fileId);
    expect($path)->toStartWith('SPDM/Backup/mam/100 - Nod 100/100-1 - Mesyuarat Agung/')
        ->and($path)->toContain('MAM.100-1-1') // '/' dalam file_no disanitasi jadi '-'
        ->and($path)->toEndWith('1 - Surat Penting.pdf');
});

it('ISOLASI: job dgn mosque_id salah tidak mirror rekod tenant lain (sifar upload)', function () {
    $mam = makeMosque('MAM', 'mam');
    $man = makeMosque('MAN', 'man');
    $adminMan = makeMember($man, 'admin_masjid', 'a@man.test');
    $recordB = mirrorFiledRecord($man, $adminMan); // rekod milik MAN

    $before = count($this->fake->nodes);

    // Palsukan: cuba sync rekod MAN melalui tenant MAM.
    (new SyncRecordToDriveJob($recordB->id, $mam->id))->handle($this->sync);

    expect($recordB->fresh()->gdrive_file_id)->toBeNull()
        ->and(count($this->fake->nodes))->toBe($before); // tiada apa dicipta
});

it('ganti versi → versi baharu dapat fail Drive SENDIRI (versi lama kekal)', function () {
    $mam = makeMosque('MAM', 'mam');
    $admin = makeMember($mam, 'admin_masjid', 'a@mam.test');
    $old = mirrorFiledRecord($mam, $admin);
    $this->sync->syncRecord($old);
    $oldDriveId = $old->fresh()->gdrive_file_id;

    $new = app(InboxIngestService::class)->supersede($old, 'kandungan versi baharu', 'surat-v2.pdf', 'application/pdf', $admin);
    $this->sync->syncRecord($new);
    $newDriveId = $new->fresh()->gdrive_file_id;

    expect($newDriveId)->not->toBeNull()
        ->and($newDriveId)->not->toBe($oldDriveId)
        ->and($this->fake->exists($oldDriveId))->toBeTrue()  // versi lama kekal
        ->and($this->fake->exists($newDriveId))->toBeTrue();
});

it('pindah fail → fail Drive dipindah ke folder baharu (id sama)', function () {
    $mam = makeMosque('MAM', 'mam');
    $admin = makeMember($mam, 'admin_masjid', 'a@mam.test');
    $record = mirrorFiledRecord($mam, $admin);
    $this->sync->syncRecord($record);
    $driveId = $record->fresh()->gdrive_file_id;
    $pathBefore = $this->fake->pathOf($driveId);

    // Fail sasaran baharu dalam aktiviti berbeza.
    $akt2 = ClassificationNode::query()->create([
        'mosque_id' => $mam->id, 'parent_id' => makeNode($mam, '200', 'dalaman', 'fungsi')->id,
        'level' => 'aktiviti', 'code' => '200-1', 'title' => 'Kewangan', 'default_sensitivity' => 'dalaman', 'is_active' => true,
    ]);
    $target = makeFile($mam, $akt2);

    app(InboxIngestService::class)->moveToFile($record->fresh(), $target, 'susun semula', $admin);
    $this->sync->syncRecord($record->fresh());

    expect($record->fresh()->gdrive_file_id)->toBe($driveId) // id sama
        ->and($this->fake->pathOf($driveId))->not->toBe($pathBefore)
        ->and($this->fake->pathOf($driveId))->toContain('200-1 - Kewangan');
});

it('pelupusan → kolum Drive dinull + engine padam fail', function () {
    $mam = makeMosque('MAM', 'mam');
    $admin = makeMember($mam, 'admin_masjid', 'a@mam.test');
    $record = mirrorFiledRecord($mam, $admin);
    $this->sync->syncRecord($record);
    $driveId = $record->fresh()->gdrive_file_id;
    expect($this->fake->exists($driveId))->toBeTrue();

    // Lupus rekod (executeBatch): kolum Drive dinull oleh hook.
    app(DisposalService::class)->executeBatch(
        $mam,
        Record::query()->whereKey($record->id)->get(),
        'manual',
        $admin,
    );

    expect($record->fresh()->gdrive_file_id)->toBeNull();

    // Engine padam fail Drive (apa yang DeleteDriveFileJob lakukan).
    $this->sync->deleteFiles([$driveId]);
    expect($this->fake->exists($driveId))->toBeFalse();
});

it('sanitasi segmen: "/" jadi "-", had panjang', function () {
    expect($this->sync->sanitize('MAM.900-1/1'))->toBe('MAM.900-1-1')
        ->and($this->sync->sanitize("baris\nbaris"))->toBe('baris baris')
        ->and(mb_strlen($this->sync->sanitize(str_repeat('a', 200))))->toBe(120);
});

it('mirror dimatikan (tidak bersambung) → syncRecord no-op', function () {
    $this->fake->connected = false;
    $mam = makeMosque('MAM', 'mam');
    $admin = makeMember($mam, 'admin_masjid', 'a@mam.test');
    $record = mirrorFiledRecord($mam, $admin);

    $this->sync->syncRecord($record);

    expect($record->fresh()->gdrive_file_id)->toBeNull()
        ->and(count($this->fake->nodes))->toBe(1); // hanya 'root'
});
