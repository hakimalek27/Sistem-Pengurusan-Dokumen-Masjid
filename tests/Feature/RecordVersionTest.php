<?php

use App\Enums\RecordStatus;
use App\Enums\SourceChannel;
use App\Models\Record;
use App\Services\InboxIngestService;
use App\Services\QrLabelService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->ingest = app(InboxIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->kerani = makeMember($this->mam, 'kerani', 'k@mam.test');
    $this->file = makeFile($this->mam, makeNode($this->mam, '100-4'), 'dalaman');
});

it('ganti versi: rekod baharu, lama=diganti, pautan dua hala (§9.C.4)', function () {
    $old = $this->ingest->ingest($this->mam, 'versi-lama', 'draf.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);
    $old = $this->ingest->fileRecord($old, $this->file, [], $this->kerani);

    $new = $this->ingest->supersede($old, 'versi-baharu-ditandatangani', 'akhir.pdf', 'application/pdf', $this->kerani);

    expect($old->fresh()->status)->toBe(RecordStatus::Diganti)
        ->and($old->fresh()->superseded_by_record_id)->toBe($new->id)
        ->and($new->status)->toBe(RecordStatus::Difailkan)
        ->and($new->ulid)->not->toBe($old->ulid)
        ->and($new->supersedes()->count())->toBe(1)
        ->and($new->getFirstMedia('original'))->not->toBeNull();
});

it('ganti versi menolak format fail tidak dibenarkan', function () {
    $old = $this->ingest->ingest($this->mam, 'versi-lama', 'draf.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);
    $old = $this->ingest->fileRecord($old, $this->file, [], $this->kerani);

    expect(fn () => $this->ingest->supersede($old, 'kandungan-zip', 'arkib.zip', 'application/zip', $this->kerani))
        ->toThrow(ValidationException::class);

    expect($old->fresh()->status)->toBe(RecordStatus::Difailkan)
        ->and(Record::query()->count())->toBe(1);
});

it('ganti versi menolak apabila kuota masjid penuh', function () {
    $old = $this->ingest->ingest($this->mam, 'versi-lama', 'draf.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);
    $old = $this->ingest->fileRecord($old, $this->file, [], $this->kerani);
    $this->mam->update([
        'storage_quota_bytes' => 10,
        'storage_used_bytes' => 10,
    ]);

    expect(fn () => $this->ingest->supersede($old, 'versi-baharu', 'akhir.pdf', 'application/pdf', $this->kerani))
        ->toThrow(ValidationException::class);

    expect($old->fresh()->status)->toBe(RecordStatus::Difailkan)
        ->and(Record::query()->count())->toBe(1);
});

it('jana PDF label QR untuk rekod (§9.C.6)', function () {
    $record = $this->ingest->ingest($this->mam, 'dok', 'surat.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);
    $record = $this->ingest->fileRecord($record, $this->file, [], $this->kerani);

    $pdf = app(QrLabelService::class)->recordPdf($record);

    expect($pdf)->toBeString()
        ->and(str_starts_with($pdf, '%PDF'))->toBeTrue();
});
