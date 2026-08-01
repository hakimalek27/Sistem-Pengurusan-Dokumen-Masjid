<?php

// Pusingan 7 — verifikasi rekod muat naik UI
use App\Models\Record;

$r = Record::latest('id')->first();
echo 'Rekod #'.$r->id
    .' | mosque='.$r->mosque_id
    .' | title='.$r->title
    .' | status='.$r->status->value
    .' | channel='.($r->channel?->value ?? (string) $r->channel ?: 'null')
    .' | virus='.($r->virus_scan_status ?? 'null')
    .' | ocr='.($r->ocr_status?->value ?? 'null')
    .' | ocrLen='.strlen((string) $r->ocr_text)
    .' | kataKunci='.(str_contains((string) $r->ocr_text, 'KATA-KUNCI-RR7-MUATNAIK') ? 'JUMPA' : 'TIADA')
    .PHP_EOL;
echo 'Fail blob: '.$r->files()->count().' | mime='.($r->files()->first()?->mime ?? '?').PHP_EOL;
