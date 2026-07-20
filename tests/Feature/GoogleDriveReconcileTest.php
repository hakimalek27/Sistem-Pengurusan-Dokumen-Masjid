<?php

use App\Contracts\DriveClient;
use App\Jobs\SyncRecordToDriveJob;
use App\Models\PlatformSetting;
use App\Services\GoogleDrive\DriveConfig;
use App\Services\GoogleDrive\DriveSyncService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeDriveClient;

/*
 * §4.6′ — Reconcile setiap jam (pilih rekod tercicir/berubah per tenant), muat
 * naik + prune DB dump, dan verify liputan.
 */

beforeEach(function () {
    config()->set('scout.driver', 'collection');
    Storage::fake(config('diwan.storage_disk'));
    Storage::fake('cos_backup');
    config()->set('backup.backup.destination.disks', ['cos_backup']); // syncDatabaseDump baca disk ini (elak BACKUP_DISK CI berbeza)

    $this->fake = new FakeDriveClient;
    app()->instance(DriveClient::class, $this->fake);

    PlatformSetting::put('gdrive_enabled', true);
    PlatformSetting::putEncrypted('gdrive_refresh_token', 'rt');
    DriveConfig::forget();
});

it('reconcile hantar sync HANYA untuk rekod tercicir/berubah per tenant', function () {
    Queue::fake();
    $mam = makeMosque('MAM', 'mam');
    $file = makeFile($mam, makeNode($mam, '100-1'));

    $a = makeRecord($mam, $file); // difailkan, belum sync (gdrive_file_id null) → dispatch
    $b = makeRecord($mam, $file);
    $b->forceFill(['gdrive_file_id' => 'x', 'gdrive_synced_at' => now()->addMinutes(10)])->saveQuietly(); // terkini → skip
    $c = makeRecord($mam, $file);
    $c->forceFill(['gdrive_file_id' => 'y', 'gdrive_synced_at' => now()->subMinutes(10)])->saveQuietly(); // berubah → dispatch

    $this->artisan('diwan:drive-reconcile')->assertExitCode(0);

    Queue::assertPushed(SyncRecordToDriveJob::class, fn ($job) => $job->recordId === $a->id);
    Queue::assertPushed(SyncRecordToDriveJob::class, fn ($job) => $job->recordId === $c->id);
    Queue::assertNotPushed(SyncRecordToDriveJob::class, fn ($job) => $job->recordId === $b->id);
    Queue::assertPushed(SyncRecordToDriveJob::class, 2);
});

it('reconcile dimatikan → tiada dispatch', function () {
    Queue::fake();
    $this->fake->connected = false; // mirror tidak bersambung → command langkau
    $mam = makeMosque('MAM', 'mam');
    makeRecord($mam, makeFile($mam, makeNode($mam, '100-1')));

    $this->artisan('diwan:drive-reconcile')->assertExitCode(0);

    Queue::assertNothingPushed();
});

it('syncDatabaseDump muat naik dump terkini + prune ke had', function () {
    $sync = app(DriveSyncService::class);
    $dumpFolder = $sync->dumpFolderId();
    // Pra-isi 3 dump lama di Drive.
    $this->fake->upload($dumpFolder, 'dump-01.zip', 'x', 'application/zip');
    $this->fake->upload($dumpFolder, 'dump-02.zip', 'x', 'application/zip');
    $this->fake->upload($dumpFolder, 'dump-03.zip', 'x', 'application/zip');

    $name = (string) config('backup.backup.name', 'diwan');
    Storage::disk('cos_backup')->put($name.'/dump-04.zip', 'baharu');

    $sync->syncDatabaseDump(2);

    $files = $this->fake->filesUnder($dumpFolder);
    expect($files)->toHaveCount(2)
        ->and($files)->toContain('dump-04.zip')  // terkini dimuat naik
        ->and($files)->toContain('dump-03.zip')  // 2 terkini disimpan
        ->and($files)->not->toContain('dump-01.zip'); // lama dipangkas
});

it('drive-verify mengira layak/sync/tertunggak per masjid', function () {
    $mam = makeMosque('MAM', 'mam');
    $file = makeFile($mam, makeNode($mam, '100-1'));

    makeRecord($mam, $file); // difailkan, belum sync
    $b = makeRecord($mam, $file);
    $b->forceFill(['gdrive_file_id' => 'x'])->saveQuietly(); // sudah sync
    makeRecord($mam, null); // Peti Masuk → tidak layak

    $this->artisan('diwan:drive-verify')->assertExitCode(0);

    $v = data_get($mam->fresh()->settings, 'gdrive_verify');
    expect($v['eligible'])->toBe(2)
        ->and($v['synced'])->toBe(1)
        ->and($v['pending'])->toBe(1);
});
