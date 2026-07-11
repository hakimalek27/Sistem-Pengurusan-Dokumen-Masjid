<?php

use App\Enums\MinitPriority;
use App\Enums\OrderStatus;
use App\Enums\RecordStatus;
use App\Enums\SourceChannel;
use App\Models\DisposalItem;
use App\Models\RetentionRule;
use App\Models\StorageAddon;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\BillingService;
use App\Services\DisposalBlobService;
use App\Services\DisposalService;
use App\Services\InboxIngestService;
use App\Services\MinitService;
use App\Services\RetentionEngine;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    Notification::fake();
});

it('mengunci struktur klasifikasi yang telah digunakan tetapi membenarkan nyahaktif', function () {
    $mosque = makeMosque('MAM', 'mam');
    $node = makeNode($mosque, '100-4');
    makeFile($mosque, $node);

    expect(fn () => $node->update(['code' => '100-9']))
        ->toThrow(LogicException::class, 'tidak boleh diubah');

    $node->refresh();
    $node->update(['is_active' => false]);
    expect($node->fresh()->is_active)->toBeFalse();

    expect(fn () => $node->delete())
        ->toThrow(LogicException::class, 'tidak boleh dipadam');
});

it('mengira semula retensi selepas create update delete peraturan dan sebelum notis', function () {
    $mosque = makeMosque('MAM', 'mam');
    $kerani = makeMember($mosque, 'kerani');
    $file = makeFile($mosque, makeNode($mosque, '100-4'));
    $record = app(InboxIngestService::class)->ingest($mosque, 'dokumen', 'surat.pdf', 'application/pdf', $kerani, SourceChannel::MuatNaik);
    $record = app(InboxIngestService::class)->fileRecord($record, $file, ['record_date' => now()->subYears(3)], $kerani);
    expect($record->fresh()->retention_due_at)->toBeNull();

    $rule = RetentionRule::query()->create([
        'mosque_id' => $mosque->id,
        'record_type' => 'surat_menyurat',
        'retain_years' => 1,
        'action' => 'auto_padam',
    ]);
    $dueOneYear = now()->subYears(3)->addYear()->startOfDay()->toDateString();
    expect($record->fresh()->retention_due_at->toDateString())->toBe($dueOneYear);

    $rule->update(['retain_years' => 2]);
    expect($record->fresh()->retention_due_at->toDateString())
        ->toBe(now()->subYears(3)->addYears(2)->startOfDay()->toDateString());

    $record->updateQuietly(['retention_due_at' => null]);
    $this->artisan('diwan:run-retention-notices')->assertSuccessful();
    expect($record->fresh()->retention_due_at)->not->toBeNull();

    $rule->delete();
    expect($record->fresh()->retention_due_at)->toBeNull();
});

it('menjadikan invois dan pengesahan bayaran atomic serta idempotent', function () {
    $mosque = makeMosque('MAM', 'mam');
    $bendahari = makeMember($mosque, 'bendahari');
    $superadmin = User::query()->create([
        'name' => 'Superadmin', 'email' => 'super-billing@ujian.test', 'is_superadmin' => true, 'is_active' => true,
    ]);
    $billing = app(BillingService::class);
    $key = (string) Str::uuid();

    $first = $billing->createOrder($mosque, $bendahari, 1, idempotencyKey: $key);
    $repeat = $billing->createOrder($mosque, $bendahari, 1, idempotencyKey: $key);
    expect($repeat->id)->toBe($first->id);

    $addon = $billing->markPaid($first, $superadmin);
    $sameAddon = $billing->markPaid($first->fresh(), $superadmin);

    expect($sameAddon->id)->toBe($addon->id)
        ->and(StorageAddon::query()->withoutGlobalScope('mosque')->where('storage_order_id', $first->id)->count())->toBe(1)
        ->and($first->fresh()->status)->toBe(OrderStatus::Dibayar);
});

it('mengekalkan snapshot dan boleh retry apabila pemadaman blob gagal', function () {
    $mosque = makeMosque('MAM', 'mam');
    $admin = makeMember($mosque, 'admin_masjid');
    $chair = makeMember($mosque, 'pengerusi');
    $file = makeFile($mosque, makeNode($mosque, '100-4'));
    $ingest = app(InboxIngestService::class);
    $record = $ingest->ingest($mosque, 'dokumen asal', 'surat.pdf', 'application/pdf', $admin, SourceChannel::MuatNaik);
    $record = $ingest->fileRecord($record, $file, [], $admin);

    $failingBlobs = Mockery::mock(DisposalBlobService::class);
    $failingBlobs->shouldReceive('deleteRecordMedia')->once()->andThrow(new RuntimeException('COS tidak tersedia'));
    $failing = new DisposalService(app(RetentionEngine::class), $failingBlobs);
    $batch = $failing->prepareManual($mosque, [$record->id], $admin);
    $failing->approveManual($batch, $chair);

    expect(fn () => $failing->executeManual($batch->fresh(), $admin))
        ->toThrow(RuntimeException::class, 'COS tidak tersedia');

    expect($batch->fresh()->status)->toBe('gagal')
        ->and($batch->fresh()->failure_reason)->toContain('COS tidak tersedia')
        ->and($record->fresh()->status)->toBe(RecordStatus::Difailkan)
        ->and($record->fresh()->getFirstMedia('original'))->not->toBeNull()
        ->and(DisposalItem::query()->where('batch_id', $batch->id)->where('state', 'snapshotted')->count())->toBe(1);

    $retry = new DisposalService(app(RetentionEngine::class), new DisposalBlobService);
    $completed = $retry->executeManual($batch->fresh(), $admin);

    expect($completed->status)->toBe('selesai')
        ->and($record->fresh()->status)->toBe(RecordStatus::Dilupus)
        ->and($record->fresh()->getFirstMedia('original'))->toBeNull()
        ->and($completed->items()->count())->toBe(1)
        ->and($completed->items()->first()->state)->toBe('finalized');
});

it('menolak klasifikasi silang tenant pada lapisan service', function () {
    $mam = makeMosque('MAM', 'mam');
    $man = makeMosque('MAN', 'man');
    $kerani = makeMember($mam, 'kerani');
    $record = app(InboxIngestService::class)->ingest($mam, 'dokumen', 'surat.pdf', 'application/pdf', $kerani, SourceChannel::MuatNaik);
    $foreignFile = makeFile($man, makeNode($man, '100-4'));

    expect(fn () => app(InboxIngestService::class)->fileRecord($record, $foreignFile, [], $kerani))
        ->toThrow(ValidationException::class);
});

it('menolak pelulus dan penerima minit yang tidak layak tenant atau sensitiviti', function () {
    $mam = makeMosque('MAM', 'mam');
    $man = makeMosque('MAN', 'man');
    $setiausaha = makeMember($mam, 'setiausaha');
    $kerani = makeMember($mam, 'kerani');
    $ajk = makeMember($mam, 'ajk');
    $foreignChair = makeMember($man, 'pengerusi');
    $record = makeRecord($mam, makeFile($mam, makeNode($mam, '800-1', 'sulit'), 'sulit'), 'sulit');

    expect(fn () => app(ApprovalService::class)->request($record, $setiausaha, $foreignChair))
        ->toThrow(ValidationException::class);

    expect(fn () => app(MinitService::class)->create(
        $record,
        $kerani,
        [$ajk->id],
        [],
        'Sila ambil tindakan',
        MinitPriority::Biasa,
    ))->toThrow(ValidationException::class);
});
