<?php

use App\Enums\RecordStatus;
use App\Enums\SourceChannel;
use App\Services\InboxIngestService;
use App\Services\QrLabelService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->ingest = app(InboxIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->kerani = makeMember($this->mam, 'kerani', 'k@mam.test');
    $this->file = makeFile($this->mam, makeNode($this->mam, '100-4'), 'dalaman');
});

it('ganti versi: rekod baharu, lama=diganti, pautan dua hala (§9.C.4)', function () {
    $old = $this->ingest->ingest($this->mam, 'versi-lama', 'draf.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);
    $old = $this->ingest->fileRecord($old, $this->file);

    $new = $this->ingest->supersede($old, 'versi-baharu-ditandatangani', 'akhir.pdf', 'application/pdf', $this->kerani);

    expect($old->fresh()->status)->toBe(RecordStatus::Diganti)
        ->and($old->fresh()->superseded_by_record_id)->toBe($new->id)
        ->and($new->status)->toBe(RecordStatus::Difailkan)
        ->and($new->ulid)->not->toBe($old->ulid)
        ->and($new->supersedes()->count())->toBe(1)
        ->and($new->getFirstMedia('original'))->not->toBeNull();
});

it('jana PDF label QR untuk rekod (§9.C.6)', function () {
    $record = $this->ingest->ingest($this->mam, 'dok', 'surat.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);
    $record = $this->ingest->fileRecord($record, $this->file);

    $pdf = app(QrLabelService::class)->recordPdf($record);

    expect($pdf)->toBeString()
        ->and(str_starts_with($pdf, '%PDF'))->toBeTrue();
});
