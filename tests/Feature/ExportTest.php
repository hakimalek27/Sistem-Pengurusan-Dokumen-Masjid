<?php

use App\Enums\SourceChannel;
use App\Models\Record;
use App\Services\ExportService;
use App\Services\InboxIngestService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->export = app(ExportService::class);
    $this->ingest = app(InboxIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->kerani = makeMember($this->mam, 'kerani');
    $this->file = makeFile($this->mam, makeNode($this->mam, '100-4', 'dalaman'), 'dalaman');
});

it('bina Eksport ZIP mengandungi metadata.csv, senarai.pdf & media (§18.35)', function () {
    $r1 = $this->ingest->fileRecord(
        $this->ingest->ingest($this->mam, 'dokumen-satu', 'surat1.pdf', 'application/pdf', null, SourceChannel::MuatNaik),
        $this->file,
        [],
        $this->kerani,
    );
    $r2 = $this->ingest->fileRecord(
        $this->ingest->ingest($this->mam, 'dokumen-dua', 'surat2.pdf', 'application/pdf', null, SourceChannel::MuatNaik),
        $this->file,
        [],
        $this->kerani,
    );

    $path = $this->export->build($this->mam, Record::query()->whereIn('id', [$r1->id, $r2->id])->get(), 'ujian');

    expect(Storage::disk(config('diwan.storage_disk'))->exists($path))->toBeTrue();

    // Buka ZIP & sahkan kandungan.
    $zipContents = Storage::disk(config('diwan.storage_disk'))->get($path);
    $tmp = tempnam(sys_get_temp_dir(), 'zip');
    file_put_contents($tmp, $zipContents);

    $zip = new ZipArchive;
    $zip->open($tmp);
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $zip->close();
    @unlink($tmp);

    expect($names)->toContain('metadata.csv')
        ->and($names)->toContain('senarai.pdf')
        ->and(collect($names)->contains(fn ($n) => str_contains($n, 'surat1.pdf')))->toBeTrue()
        ->and(collect($names)->contains(fn ($n) => str_contains($n, 'surat2.pdf')))->toBeTrue();
});
