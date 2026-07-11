<?php

use App\Enums\RecordStatus;
use App\Enums\Sensitivity;
use App\Enums\SourceChannel;
use App\Services\InboxIngestService;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->svc = app(InboxIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->kerani = makeMember($this->mam, 'kerani');
    $this->node = makeNode($this->mam, '200-2', 'sulit');
    $this->file = makeFile($this->mam, $this->node, 'sulit');
});

it('failkan rekod: enclosure diperuntuk, difailkan, waris sensitiviti max, mosque_id betul', function () {
    $record = $this->svc->ingest($this->mam, 'dokumen', 'surat.pdf', 'application/pdf', null, SourceChannel::MuatNaik);

    expect($record->status)->toBe(RecordStatus::PetiMasuk)
        ->and($record->sensitivity)->toBe(Sensitivity::Dalaman)
        ->and($record->registry_file_id)->toBeNull();

    $filed = $this->svc->fileRecord($record, $this->file, ['title' => 'Resit Julai'], $this->kerani);

    expect($filed->status)->toBe(RecordStatus::Difailkan)
        ->and($filed->enclosure_no)->toBe(1)
        ->and($filed->sensitivity)->toBe(Sensitivity::Sulit) // max(dalaman, sulit)
        ->and($filed->registry_file_id)->toBe($this->file->id)
        ->and($filed->mosque_id)->toBe($this->mam->id)
        ->and($filed->title)->toBe('Resit Julai')
        ->and($filed->filed_at)->not->toBeNull();
});

it('enclosure_no berturutan untuk beberapa rekod dalam fail sama', function () {
    $r1 = $this->svc->ingest($this->mam, 'a', 'a.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $r2 = $this->svc->ingest($this->mam, 'b', 'b.pdf', 'application/pdf', null, SourceChannel::MuatNaik);

    $f1 = $this->svc->fileRecord($r1, $this->file, [], $this->kerani);
    $f2 = $this->svc->fileRecord($r2, $this->file, [], $this->kerani);

    expect($f1->enclosure_no)->toBe(1)
        ->and($f2->enclosure_no)->toBe(2)
        ->and($this->file->fresh()->enclosure_count)->toBe(2);
});

it('pindah fail merekod audit & memperuntuk enclosure baharu', function () {
    $node2 = makeNode($this->mam, '100-4', 'dalaman');
    $file2 = makeFile($this->mam, $node2, 'dalaman');

    $record = $this->svc->ingest($this->mam, 'x', 'x.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $filed = $this->svc->fileRecord($record, $this->file, [], $this->kerani);

    $moved = $this->svc->moveToFile($filed, $file2, 'Salah fail asal', $this->kerani);

    expect($moved->registry_file_id)->toBe($file2->id)
        ->and(Activity::query()->where('description', 'pindah_fail')->exists())->toBeTrue();
});
