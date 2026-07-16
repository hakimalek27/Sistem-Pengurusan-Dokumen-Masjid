<?php

use App\Enums\OcrStatus;
use App\Enums\SourceChannel;
use App\Services\InboxIngestService;
use App\Services\SearchService;
use Illuminate\Support\Facades\Storage;

it('OCR fail sebenar menghasilkan PDF boleh cari dan hasil carian tenant', function () {
    $fixtures = collect([1, 2])->map(function (int $index): ?array {
        $path = getenv("SPDM_OCR_FIXTURE_{$index}") ?: null;
        $term = getenv("SPDM_OCR_TERM_{$index}") ?: null;

        return $path && $term ? compact('path', 'term') : null;
    })->filter()->values();

    if ($fixtures->isEmpty()) {
        $this->markTestSkipped('Tetapkan SPDM_OCR_FIXTURE_1/2 dan SPDM_OCR_TERM_1/2 untuk ujian dokumen sebenar.');
    }

    Storage::fake(config('diwan.storage_disk'));
    config()->set('scout.driver', 'collection');
    $mosque = makeMosque('OCR Masjid', 'ocr-masjid');
    $kerani = makeMember($mosque, 'kerani', 'ocr-kerani@ujian.test');

    foreach ($fixtures as $fixture) {
        expect(is_file($fixture['path']))->toBeTrue("Fail OCR tiada: {$fixture['path']}");

        $record = app(InboxIngestService::class)->ingest(
            $mosque,
            file_get_contents($fixture['path']),
            basename($fixture['path']),
            mime_content_type($fixture['path']) ?: 'image/jpeg',
            $kerani,
            SourceChannel::MuatNaik,
        )->fresh();

        $derived = $record->getFirstMedia('derived');
        $results = app(SearchService::class)->for($kerani, $mosque, $fixture['term']);

        expect($record->ocr_status)->toBe(OcrStatus::Siap)
            ->and(mb_stripos((string) $record->ocr_text, $fixture['term']))->not->toBeFalse()
            ->and($derived)->not->toBeNull()
            ->and(Storage::disk($derived->disk)->exists($derived->getPathRelativeToRoot()))->toBeTrue()
            ->and($results->contains('id', $record->id))->toBeTrue();
    }
});
