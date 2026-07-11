<?php

use App\Enums\ApprovalStatus;
use App\Enums\MinitPriority;
use App\Enums\MinitStatus;
use App\Enums\SourceChannel;
use App\Models\Record;
use App\Services\ApprovalService;
use App\Services\ExportService;
use App\Services\InboxIngestService;
use App\Services\MinitService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('melengkapkan UAT kerani pengerusi setiausaha admin dan audit hujung ke hujung', function () {
    Mail::fake();
    Notification::fake();
    Storage::fake(config('diwan.storage_disk'));

    $mosque = makeMosque('UAT', 'uat-pejabat');
    $node = makeNode($mosque, '100-4');
    $file = makeFile($mosque, $node);
    $kerani = makeMember($mosque, 'kerani');
    $pengerusi = makeMember($mosque, 'pengerusi');
    $setiausaha = makeMember($mosque, 'setiausaha');
    $admin = makeMember($mosque, 'admin_masjid');
    $audit = makeMember($mosque, 'audit');

    $ingest = app(InboxIngestService::class);
    $record = $ingest->ingest(
        $mosque,
        'kandungan PDF UAT',
        'surat-uat.pdf',
        'application/pdf',
        $kerani,
        SourceChannel::MuatNaik,
    );
    $record = $ingest->fileRecord($record, $file, [
        'title' => 'Surat UAT Pejabat',
        'record_type' => 'surat_menyurat',
    ], $kerani);

    $minitService = app(MinitService::class);
    $minit = $minitService->create($record, $kerani, [$pengerusi->id], [], 'Untuk arahan', MinitPriority::Segera);
    $reply = $minitService->replyAndRoute($minit, $pengerusi, [$setiausaha->id], [], 'Sediakan tindakan susulan', MinitPriority::Biasa);
    $minitService->markDone($reply, $setiausaha);

    $approvalService = app(ApprovalService::class);
    $approval = $approvalService->request($record, $setiausaha, $pengerusi, 'Mohon kelulusan akhir');
    $approvalService->decide($approval, $pengerusi, ApprovalStatus::Lulus, 'Diluluskan', '127.0.0.1');

    $exportPath = app(ExportService::class)->build($mosque, collect([$record]), 'uat-pejabat');
    $sulit = makeRecord($mosque, $file, 'sulit', attrs: ['title' => 'Rekod Sulit UAT']);
    $visibleIds = Record::query()->visibleTo($audit, $mosque)->pluck('id');

    expect($record->fresh()->registry_file_id)->toBe($file->id)
        ->and($minit->children()->whereKey($reply->id)->exists())->toBeTrue()
        ->and($reply->fresh()->status)->toBe(MinitStatus::Selesai)
        ->and($approval->fresh()->status)->toBe(ApprovalStatus::Lulus)
        ->and($admin->canIn($mosque, 'export.create'))->toBeTrue()
        ->and(Storage::disk(config('diwan.storage_disk'))->exists($exportPath))->toBeTrue()
        ->and($audit->canIn($mosque, 'audit.view'))->toBeTrue()
        ->and($visibleIds)->toContain($record->id)
        ->and($visibleIds)->not->toContain($sulit->id);
});
