<?php

namespace App\Services;

use App\Models\Mosque;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * §16.4 — Bina Eksport ZIP: PDF asal + derived + lampiran (folder ikut file_no)
 * + metadata.csv + senarai.pdf. Muat naik ke tenants/{id}/exports (lifecycle 14 hari).
 */
class ExportService
{
    public function build(Mosque $mosque, Collection $records, string $label = 'eksport'): string
    {
        $ulid = strtolower((string) Str::ulid());
        $tmpDir = storage_path('app/tmp/export-'.$ulid);
        @mkdir($tmpDir, 0775, true);
        $zipPath = $tmpDir.'/export.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);

        $csv = "file_no,enclosure,title,record_type,record_date,sensitivity\n";
        $rows = '';

        foreach ($records as $record) {
            $record->loadMissing('registryFile');
            $folder = $record->registryFile?->file_no
                ? Str::slug($record->registryFile->file_no).'-'.$record->enclosure_no
                : substr($record->ulid, -8);

            foreach (['original', 'derived', 'attachments'] as $collection) {
                foreach ($record->getMedia($collection) as $media) {
                    $zip->addFromString(
                        $folder.'/'.$collection.'/'.$media->file_name,
                        Storage::disk($media->disk)->get($media->getPathRelativeToRoot()),
                    );
                }
            }

            $csv .= $this->csvRow([
                $record->registryFile?->file_no,
                $record->enclosure_no,
                $record->title,
                $record->record_type,
                optional($record->record_date)->format('d/m/Y'),
                $record->sensitivity?->value,
            ]);

            $rows .= '<tr><td>'.e(($record->registryFile?->file_no ?? '—').'('.$record->enclosure_no.')')
                .'</td><td>'.e((string) $record->title).'</td><td>'.e(optional($record->record_date)->format('d/m/Y') ?? '—').'</td></tr>';
        }

        $zip->addFromString('metadata.csv', $csv);
        $zip->addFromString('senarai.pdf', $this->senaraiPdf($mosque, $rows, $records->count()));
        $zip->close();

        $path = "tenants/{$mosque->id}/exports/{$ulid}.zip";
        Storage::disk(config('diwan.storage_disk'))->put($path, file_get_contents($zipPath));

        @unlink($zipPath);
        @rmdir($tmpDir);

        return $path;
    }

    protected function csvRow(array $values): string
    {
        return implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $values))."\n";
    }

    protected function senaraiPdf(Mosque $mosque, string $rows, int $count): string
    {
        $html = '<html><body style="font-family:sans-serif;">'
            .'<h2>Senarai Rekod Dieksport — '.e($mosque->name).'</h2>'
            .'<p>Tarikh: '.now()->format('d/m/Y H:i').' | Bilangan: '.$count.'</p>'
            .'<table border="1" cellpadding="5" cellspacing="0" width="100%">'
            .'<tr><th align="left">Rujukan</th><th align="left">Tajuk</th><th align="left">Tarikh</th></tr>'
            .$rows.'</table></body></html>';

        return Pdf::loadHTML($html)->output();
    }
}
