<?php

namespace App\Jobs;

use App\Enums\OcrStatus;
use App\Models\Record;
use App\Support\OfficeTextExtractor;
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

        // Teks biasa → indeks kandungan terus (tiada OCR diperlukan).
        if ($ext === 'txt') {
            $raw = (string) Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
            $record->update([
                'ocr_text' => mb_substr($raw, 0, (int) config('diwan.ocr_text_limit', 1_000_000)),
                'ocr_status' => OcrStatus::Siap,
            ]);
            $record->searchable();

            return;
        }

        // Office (lama & baharu) → ekstrak teks kandungan supaya boleh dicari (§20 Fasa 2).
        // Gagal/tak dapat diekstrak → ocr_text null (rekod kekal sah, metadata sahaja).
        if (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)) {
            $officeText = null;
            $officeTmp = storage_path('app/tmp/office-'.$record->ulid);
            @mkdir($officeTmp, 0775, true);

            try {
                $localOffice = $officeTmp.'/'.$media->file_name;
                file_put_contents($localOffice, Storage::disk($media->disk)->get($media->getPathRelativeToRoot()));
                $officeText = OfficeTextExtractor::extract($localOffice, $ext);
            } catch (\Throwable $e) {
                Log::warning("[OCR] ekstrak teks Office gagal record {$record->id}: ".$e->getMessage());
            } finally {
                self::deleteDir($officeTmp);
            }

            $record->update([
                'ocr_text' => $officeText,
                'ocr_status' => OcrStatus::Siap,
            ]);
            $record->searchable();

            return;
        }

        $isImage = str_starts_with((string) $media->mime_type, 'image/');
        $canUseOcrMyPdf = self::commandAvailable('ocrmypdf')
            && (! $isImage || self::commandAvailable('img2pdf'));
        $canUseTesseract = self::commandAvailable('tesseract')
            && ($isImage || self::commandAvailable('pdftotext') || self::commandAvailable('pdftoppm'));

        // Produksi menggunakan ocrmypdf. Fallback Tesseract/Poppler membolehkan
        // workstation Windows menjalankan OCR imej/PDF end-to-end juga.
        if (! $canUseOcrMyPdf && ! $canUseTesseract) {
            Log::info("[OCR] tooling tiada — langkau record {$record->id} (kekal 'belum').");

            return;
        }

        // Retry job mesti idempotent. Koleksi singleFile menggunakan path derived yang sama;
        // menambah media kedua sebelum media lama dibuang boleh memadam fail baharu sekali.
        $existingDerived = $record->getFirstMedia('derived');
        if ($record->ocr_status === OcrStatus::Siap
            && $existingDerived
            && Storage::disk($existingDerived->disk)->exists($existingDerived->getPathRelativeToRoot())) {
            return;
        }

        // Pulihkan keadaan separa (rekod media wujud tetapi objek hilang/gagal) sebelum jana semula.
        $existingDerived?->delete();

        $record->update(['ocr_status' => OcrStatus::DalamProses]);

        $tmpDir = storage_path('app/tmp/'.$record->ulid);
        @mkdir($tmpDir, 0775, true);

        try {
            $localOriginal = $tmpDir.'/'.$media->file_name;
            file_put_contents($localOriginal, Storage::disk($media->disk)->get($media->getPathRelativeToRoot()));

            $nativeTextPdf = ! $isImage
                ? $this->extractNativeTextPdf($localOriginal, $tmpDir)
                : null;

            if ($nativeTextPdf) {
                [$outPdf, $sidecar] = $nativeTextPdf;
            } elseif ($canUseOcrMyPdf) {
                [$outPdf, $sidecar] = $this->runOcrMyPdf($localOriginal, $tmpDir, $isImage);
            } else {
                [$outPdf, $sidecar] = $isImage
                    ? $this->runTesseractImage($localOriginal, $tmpDir)
                    : $this->runTesseractPdf($localOriginal, $tmpDir);
            }

            // Muat naik derived (fail asal TIDAK diubah).
            $record->addMedia($outPdf)->usingFileName('searchable.pdf')->toMediaCollection('derived');

            $text = is_file($sidecar) ? OfficeTextExtractor::normalizeText((string) file_get_contents($sidecar)) : '';
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

    /**
     * PDF yang mempunyai lapisan teks perlu diindeks terus. Sidecar ocrmypdf
     * dengan --skip-text hanya memuatkan teks halaman yang benar-benar di-OCR,
     * lalu boleh mengosongkan indeks untuk PDF digital yang sah.
     *
     * @return array{0:string,1:string}|null
     */
    protected function extractNativeTextPdf(string $pdf, string $tmpDir): ?array
    {
        if (! self::commandAvailable('pdftotext')) {
            return null;
        }

        $sidecar = $tmpDir.'/native-text.txt';
        (new Process([self::commandPath('pdftotext'), '-layout', $pdf, $sidecar]))
            ->setTimeout(120)
            ->mustRun();

        if (! is_file($sidecar) || trim((string) file_get_contents($sidecar)) === '') {
            @unlink($sidecar);

            return null;
        }

        $outPdf = $tmpDir.'/searchable.pdf';
        if (! copy($pdf, $outPdf)) {
            throw new \RuntimeException('PDF bertulis gagal disalin sebagai fail derived.');
        }

        return [$outPdf, $sidecar];
    }

    public static function toolingAvailable(): bool
    {
        return self::commandAvailable('ocrmypdf') || self::commandAvailable('tesseract');
    }

    /** @return array{0:string,1:string} */
    protected function runOcrMyPdf(string $localOriginal, string $tmpDir, bool $isImage): array
    {
        if ($isImage) {
            $inputPdf = $tmpDir.'/input.pdf';
            // --rotation=ifvalid: abaikan tag putaran EXIF tidak sah (cth nilai 0 pada
            // foto/skrinsyot telefon). Tanpa ini img2pdf ABORT ("Invalid rotation")
            // → OCR gagal pada imej pengguna sebenar (dijumpai 20 Jul, e-mel JPEG).
            (new Process([self::commandPath('img2pdf'), $localOriginal, '--rotation=ifvalid', '-o', $inputPdf]))->setTimeout(120)->mustRun();
        } else {
            $inputPdf = $localOriginal;
        }

        $outPdf = $tmpDir.'/searchable.pdf';
        $sidecar = $tmpDir.'/sidecar.txt';
        // --output-type pdf (bukan pdfa) untuk ELAK Ghostscript pada langkah akhir.
        // Ghostscript 10.0.0–10.02.0 (imej Docker php:8.3 Debian bookworm) ada regресsi yang
        // menyebabkan ocrmypdf --skip-text ABORT pada dokumen yang menghasilkan lapisan teks
        // (mis. dokumen bercetak sebenar), menjadikan OCR gagal di produksi. `pdf` mengekalkan
        // OCR + carian; PDF/A boleh dipulihkan kemudian dengan menaik taraf Ghostscript (>10.02.0).
        // --clean (unpaper, pra-proses sebelum OCR — imej Docker sahaja) + --optimize 1
        // (lossless) meningkatkan ketepatan OCR & kekemasan tanpa menyentuh fail asal.
        $process = new Process([
            self::commandPath('ocrmypdf'), '--skip-text', '-l', config('diwan.ocr_langs', 'msa+eng'),
            '--rotate-pages', '--deskew', '--clean', '--optimize', '1',
            '--sidecar', $sidecar, '--output-type', 'pdf',
            $inputPdf, $outPdf,
        ]);
        $process->setTimeout(240);
        $process->mustRun();

        return [$outPdf, $sidecar];
    }

    /** @return array{0:string,1:string} */
    protected function runTesseractImage(string $image, string $tmpDir): array
    {
        $base = $tmpDir.'/tesseract';
        $this->renderTesseractPage($image, $base);

        return [$base.'.pdf', $base.'.txt'];
    }

    /** @return array{0:string,1:string} */
    protected function runTesseractPdf(string $pdf, string $tmpDir): array
    {
        $outPdf = $tmpDir.'/searchable.pdf';
        $sidecar = $tmpDir.'/sidecar.txt';

        if (! self::commandAvailable('pdftoppm') || ! self::commandAvailable('pdfunite')) {
            throw new \RuntimeException('PDF imbasan memerlukan pdftoppm dan pdfunite untuk fallback OCR.');
        }

        $prefix = $tmpDir.'/page';
        (new Process([self::commandPath('pdftoppm'), '-jpeg', '-r', '200', $pdf, $prefix]))->setTimeout(180)->mustRun();
        $images = glob($prefix.'-*.jpg') ?: [];
        natsort($images);
        if ($images === []) {
            throw new \RuntimeException('PDF tidak menghasilkan halaman untuk OCR.');
        }

        $pagePdfs = [];
        $texts = [];
        foreach (array_values($images) as $index => $image) {
            $base = $tmpDir.'/ocr-page-'.($index + 1);
            $this->renderTesseractPage($image, $base);
            $pagePdfs[] = $base.'.pdf';
            $texts[] = is_file($base.'.txt') ? (string) file_get_contents($base.'.txt') : '';
        }

        (new Process(array_merge([self::commandPath('pdfunite')], $pagePdfs, [$outPdf])))->setTimeout(180)->mustRun();
        file_put_contents($sidecar, implode("\n\f\n", $texts));

        return [$outPdf, $sidecar];
    }

    protected function renderTesseractPage(string $image, string $base): void
    {
        $pdf = self::tesseractProcess([
            self::commandPath('tesseract'), $image, $base, '-l', config('diwan.ocr_langs', 'msa+eng'),
            '-c', 'tessedit_create_pdf=1', '-c', 'tessedit_create_txt=0',
        ]);
        $pdf->setTimeout(240)->mustRun();

        $text = self::tesseractProcess([
            self::commandPath('tesseract'), $image, 'stdout', '-l', config('diwan.ocr_langs', 'msa+eng'),
        ]);
        $text->setTimeout(240)->mustRun();
        file_put_contents($base.'.txt', $text->getOutput());
    }

    protected static function commandAvailable(string $command): bool
    {
        try {
            self::commandPath($command);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Dapatkan path mutlak supaya queue/web Windows tidak bergantung pada PATH
     * proses induk. Linux/Docker kekal menggunakan `which` seperti biasa.
     */
    protected static function commandPath(string $command): string
    {
        static $resolved = [];

        if (isset($resolved[$command])) {
            return $resolved[$command];
        }

        $isWindows = stripos(PHP_OS, 'WIN') === 0;
        $profile = getenv('USERPROFILE') ?: null;
        $candidates = [];

        if ($isWindows && $profile) {
            $candidates[] = $profile.'/scoop/shims/'.$command.'.exe';
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $resolved[$command] = str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            }
        }

        $locator = $isWindows ? getenv('SystemRoot').'\\System32\\where.exe' : 'which';
        $process = new Process([$locator, $command]);
        if ($process->run() === 0 && ($path = trim(strtok($process->getOutput(), "\r\n")))) {
            return $resolved[$command] = $path;
        }

        throw new \RuntimeException("Binary {$command} tidak ditemui.");
    }

    protected static function tesseractProcess(array $command): Process
    {
        $process = new Process($command);
        if ($prefix = self::tessdataPrefix()) {
            $process->setEnv(['TESSDATA_PREFIX' => $prefix]);
        }

        return $process;
    }

    protected static function tessdataPrefix(): ?string
    {
        $candidates = array_filter([
            config('diwan.tessdata_prefix'),
            getenv('TESSDATA_PREFIX') ?: null,
            getenv('USERPROFILE') ? getenv('USERPROFILE').'/scoop/apps/tesseract-languages/current' : null,
        ]);

        foreach ($candidates as $candidate) {
            $path = rtrim((string) $candidate, '/\\');
            if (is_file($path.'/eng.traineddata')) {
                return $path;
            }
        }

        return null;
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
