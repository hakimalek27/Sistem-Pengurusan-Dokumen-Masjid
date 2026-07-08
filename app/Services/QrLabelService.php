<?php

namespace App\Services;

use App\Models\Record;
use App\Models\RegistryFile;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * §9.C.6 — Jana PDF label QR (dompdf) untuk rekod/fail. QR → {APP_URL}/r/{ulid}.
 */
class QrLabelService
{
    public function recordPdf(Record $record): string
    {
        $record->loadMissing('registryFile');
        $url = rtrim((string) config('app.url'), '/').'/r/'.$record->ulid;
        $reference = $record->registryFile
            ? $record->registryFile->file_no.'('.$record->enclosure_no.')'
            : substr($record->ulid, -6);

        return $this->buildPdf([[
            'url' => $url,
            'reference' => $reference,
            'title' => mb_substr((string) $record->title, 0, 40),
        ]]);
    }

    /** Label untuk fail fizikal (§9.C.6). */
    public function filePdf(RegistryFile $file): string
    {
        return $this->buildPdf([[
            'url' => rtrim((string) config('app.url'), '/').'/app/'.$file->mosque->slug,
            'reference' => $file->file_no,
            'title' => mb_substr((string) $file->title, 0, 40),
        ]]);
    }

    protected function buildPdf(array $labels): string
    {
        $cells = '';
        foreach ($labels as $label) {
            $svg = QrCode::format('svg')->size(120)->margin(0)->generate($label['url']);
            $img = 'data:image/svg+xml;base64,'.base64_encode($svg);
            $cells .= '<div style="display:inline-block;border:1px solid #333;padding:8px;margin:4px;width:200px;text-align:center;">'
                .'<img src="'.$img.'" width="120" height="120"><br>'
                .'<strong>'.e($label['reference']).'</strong><br>'
                .'<span style="font-size:10px;">'.e($label['title']).'</span>'
                .'</div>';
        }

        $html = '<html><body style="font-family:sans-serif;">'.$cells.'</body></html>';

        return Pdf::loadHTML($html)->output();
    }
}
