<?php

// Read-only source records; generated ZIP/PDF objects are deleted before exit.
$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Mosque;
use App\Models\Record;
use App\Services\ExportService;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

$disk = Storage::disk(config('diwan.storage_disk'));
$mam = Mosque::withoutGlobalScopes()->where('slug', 'mam')->firstOrFail();
$man = Mosque::withoutGlobalScopes()->where('slug', 'man')->firstOrFail();
$mamRecords = Record::withoutGlobalScopes()->where('mosque_id', $mam->id)->where('status', 'difailkan')->orderBy('id')->take(2)->get();
$manRecords = Record::withoutGlobalScopes()->where('mosque_id', $man->id)->where('status', 'difailkan')->orderBy('id')->take(1)->get();
$service = app(ExportService::class);
$output = [];

foreach ([['mam', $mam, $mamRecords, $manRecords], ['man', $man, $manRecords, $mamRecords]] as [$label, $mosque, $records, $foreignRecords]) {
    $path = $service->build($mosque, $records, 'audit-rr4-'.$label);
    $zipBytes = $disk->get($path);
    $tmpZip = tempnam(sys_get_temp_dir(), 'rr4-zip-');
    $tmpPdf = tempnam(sys_get_temp_dir(), 'rr4-pdf-');
    file_put_contents($tmpZip, $zipBytes);
    $zip = new ZipArchive;
    $zip->open($tmpZip);
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $csv = $zip->getFromName('metadata.csv') ?: '';
    file_put_contents($tmpPdf, $zip->getFromName('senarai.pdf') ?: '');
    $zip->close();
    $pdfTextPath = $tmpPdf.'.txt';
    $exitCode = 0;
    exec('pdftotext '.escapeshellarg($tmpPdf).' '.escapeshellarg($pdfTextPath), $unused, $exitCode);
    $pdfText = is_file($pdfTextPath) ? file_get_contents($pdfTextPath) : '';
    $foreignLeaks = [];
    foreach ($foreignRecords->pluck('title')->filter() as $title) {
        if (str_contains($csv, $title) || str_contains($pdfText, $title)) {
            $foreignLeaks[] = $title;
        }
    }
    $output[$label] = [
        'records_selected' => $records->pluck('title')->values()->all(),
        'zip_entries' => $names,
        'metadata_csv' => $csv,
        'pdf_text' => $pdfText,
        'foreign_titles_detected' => $foreignLeaks,
        'status' => $exitCode === 0 && in_array('metadata.csv', $names, true) && in_array('senarai.pdf', $names, true) && $foreignLeaks === [] ? 'PASS' : 'FAIL',
    ];
    $disk->delete($path);
    @unlink($tmpZip);
    @unlink($tmpPdf);
    @unlink($pdfTextPath);
}

file_put_contents(__DIR__.'/export-results.json', json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
