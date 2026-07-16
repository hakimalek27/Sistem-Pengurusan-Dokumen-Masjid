<?php

use App\Enums\OcrStatus;
use App\Enums\SourceChannel;
use App\Jobs\ProcessOcrJob;
use App\Services\InboxIngestService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->ingest = app(InboxIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
});

it('dokumen Office (docx) dilangkau OCR → status siap, teks kosong (§12)', function () {
    $record = $this->ingest->ingest(
        $this->mam,
        'kandungan-docx-palsu',
        'nota-mesyuarat.docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        null,
        SourceChannel::MuatNaik,
    );

    // ProcessOcrJob dijalankan segerak (sync) dalam ingest; laluan Office tidak perlukan tesseract.
    expect($record->fresh()->ocr_status)->toBe(OcrStatus::Siap)
        ->and($record->fresh()->ocr_text)->toBeNull();
});

it('OCR imej BM sebenar mengekstrak teks (§18.7 — perlu ocrmypdf/tesseract)', function () {
    if (! ProcessOcrJob::toolingAvailable()) {
        $this->markTestSkipped('ocrmypdf/tesseract tiada pada mesin ini — OCR sebenar dijalankan dalam imej Docker §4.4 (queue ocr).');
    }

    // Jana imej dengan teks BM menggunakan GD.
    $img = imagecreatetruecolor(1000, 300);
    imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
    $black = imagecolorallocate($img, 0, 0, 0);
    imagestring($img, 5, 40, 60, 'PERMOHONAN MENGGUNAKAN DEWAN SERBAGUNA', $black);
    imagestring($img, 5, 40, 120, 'Rujukan Kami: MAM.500-1/2/3', $black);

    $tmp = tempnam(sys_get_temp_dir(), 'ocr').'.jpg';
    imagejpeg($img, $tmp);
    imagedestroy($img);

    $record = $this->ingest->ingest($this->mam, file_get_contents($tmp), 'surat.jpg', 'image/jpeg', null, SourceChannel::WhatsApp);
    @unlink($tmp);

    ProcessOcrJob::dispatchSync($record->id, $record->mosque_id);

    $fresh = $record->fresh();
    $derived = $fresh->getFirstMedia('derived');
    expect($fresh->ocr_status)->toBe(OcrStatus::Siap)
        ->and(str_contains(strtoupper((string) $fresh->ocr_text), 'DEWAN'))->toBeTrue()
        ->and($derived)->not->toBeNull()
        ->and(Storage::disk($derived->disk)->exists($derived->getPathRelativeToRoot()))->toBeTrue();

    // Retry queue tidak boleh mengganti rekod media lalu memadam fail pada path singleFile sama.
    ProcessOcrJob::dispatchSync($record->id, $record->mosque_id);

    $afterRetry = $record->fresh();
    $derivedAfterRetry = $afterRetry->getFirstMedia('derived');
    expect($afterRetry->ocr_status)->toBe(OcrStatus::Siap)
        ->and($derivedAfterRetry?->id)->toBe($derived->id)
        ->and(Storage::disk($derivedAfterRetry->disk)->exists($derivedAfterRetry->getPathRelativeToRoot()))->toBeTrue();
});

it('PDF bertulis menghasilkan derived PDF dan teks boleh diindeks', function () {
    if (! ProcessOcrJob::toolingAvailable()) {
        $this->markTestSkipped('Tooling OCR/PDF tiada pada mesin ini.');
    }

    $pdf = app('dompdf.wrapper')
        ->loadHTML('<h1>MINIT MESYUARAT PENGURUSAN MASJID</h1><p>Tindakan susulan setiausaha.</p>')
        ->output();

    $record = $this->ingest->ingest(
        $this->mam,
        $pdf,
        'minit-pengurusan.pdf',
        'application/pdf',
        null,
        SourceChannel::MuatNaik,
    )->fresh();

    $derived = $record->getFirstMedia('derived');
    expect($record->ocr_status)->toBe(OcrStatus::Siap)
        ->and(str_contains(strtoupper((string) $record->ocr_text), 'MESYUARAT'))->toBeTrue()
        ->and($derived)->not->toBeNull()
        ->and(Storage::disk($derived->disk)->exists($derived->getPathRelativeToRoot()))->toBeTrue();
});
