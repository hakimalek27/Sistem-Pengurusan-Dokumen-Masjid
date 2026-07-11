<?php

namespace App\Jobs;

use App\Enums\OcrStatus;
use App\Models\Record;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * §12 — Pipeline OCR (queue ocr, maxProcesses 1). Payload membawa record_id + mosque_id;
 * queue tidak bergantung pada konteks tenant global. Fail asal TIDAK diubah.
 */
class ProcessOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    public function __construct(public int $recordId, public int $mosqueId) {}

    public function backoff(): array
    {
        return [60];
    }

    public function handle(): void
    {
        $record = Record::query()->withoutGlobalScope('mosque')
            ->where('mosque_id', $this->mosqueId)
            ->find($this->recordId);
        if (! $record) {
            return;
        }

        $media = $record->getFirstMedia('original');
        if (! $media) {
            return;
        }

        $ext = strtolower(pathinfo((string) $media->file_name, PATHINFO_EXTENSION));

        // Office → langkau (indeks metadata sahaja); ekstrak teks Office = Fasa 2.
        if (in_array($ext, ['docx', 'xlsx', 'pptx'], true)) {
            $record->update(['ocr_status' => OcrStatus::Siap, 'ocr_text' => null]);
            $record->searchable();

            return;
        }

        // OCR sebenar memerlukan ocrmypdf (imej Docker §4.4). Jika tiada, biar 'belum'.
        if (! self::toolingAvailable()) {
            Log::info("[OCR] ocrmypdf tiada — langkau record {$record->id} (kekal 'belum').");

            return;
        }

        $record->update(['ocr_status' => OcrStatus::DalamProses]);

        $tmpDir = storage_path('app/tmp/'.$record->ulid);
        @mkdir($tmpDir, 0775, true);

        try {
            $localOriginal = $tmpDir.'/'.$media->file_name;
            file_put_contents($localOriginal, Storage::disk($media->disk)->get($media->getPathRelativeToRoot()));

            // Imej → img2pdf; PDF → guna terus.
            if (str_starts_with((string) $media->mime_type, 'image/')) {
                $inputPdf = $tmpDir.'/input.pdf';
                (new Process(['img2pdf', $localOriginal, '-o', $inputPdf]))->setTimeout(120)->mustRun();
            } else {
                $inputPdf = $localOriginal;
            }

            $outPdf = $tmpDir.'/searchable.pdf';
            $sidecar = $tmpDir.'/sidecar.txt';

            $process = new Process([
                'ocrmypdf', '--skip-text', '-l', config('diwan.ocr_langs', 'msa+eng'),
                '--rotate-pages', '--deskew', '--sidecar', $sidecar, '--output-type', 'pdfa',
                $inputPdf, $outPdf,
            ]);
            $process->setTimeout(240);
            $process->mustRun();

            // Muat naik derived (fail asal TIDAK diubah).
            $record->addMedia($outPdf)->usingFileName('searchable.pdf')->toMediaCollection('derived');

            $text = is_file($sidecar) ? (string) file_get_contents($sidecar) : '';
            $record->update([
                'ocr_text' => mb_substr($text, 0, (int) config('diwan.ocr_text_limit', 1_000_000)),
                'ocr_status' => OcrStatus::Siap,
            ]);
            $record->searchable();
        } catch (\Throwable $e) {
            $record->update(['ocr_status' => OcrStatus::Gagal]);
            Log::warning("[OCR] gagal record {$record->id}: ".$e->getMessage());
        } finally {
            self::deleteDir($tmpDir);
        }
    }

    public static function toolingAvailable(): bool
    {
        $locator = stripos(PHP_OS, 'WIN') === 0 ? 'where' : 'which';

        try {
            return (new Process([$locator, 'ocrmypdf']))->run() === 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected static function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
            $path = $dir.'/'.$item;
            is_dir($path) ? self::deleteDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
