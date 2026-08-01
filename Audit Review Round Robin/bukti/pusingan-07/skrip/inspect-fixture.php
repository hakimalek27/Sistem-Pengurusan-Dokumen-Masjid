<?php

// Pusingan 7 — pemeriksaan fixture selepas migrate:fresh --seed (DB SQLite buangan tempatan)
use App\Models\ClassificationNode;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Models\StorageOrder;
use App\Models\User;

foreach (Mosque::all() as $m) {
    echo $m->id.' '.$m->slug.' '.$m->name.' status='.$m->status->value.PHP_EOL;
}

echo '--- Rekod peti masuk MAM (mosque 1, belum difailkan):'.PHP_EOL;
foreach (Record::where('mosque_id', 1)->whereNull('registry_file_id')->get() as $r) {
    echo $r->id.' | '.$r->title.' | virus='.($r->virus_scan_status?->value ?? 'null').' | ocr='.($r->ocr_status?->value ?? 'null').' | status='.$r->status->value.PHP_EOL;
}

echo '--- Objek MAN: rekod '.Record::where('mosque_id', 2)->min('id').'-'.Record::where('mosque_id', 2)->max('id')
    .' | fail '.RegistryFile::where('mosque_id', 2)->min('id')
    .' | nod '.ClassificationNode::where('mosque_id', 2)->min('id').PHP_EOL;

echo '--- StorageOrder sedia ada: '.StorageOrder::count().PHP_EOL;
echo '--- Pengguna: '.User::pluck('email')->implode(', ').PHP_EOL;
