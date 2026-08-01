<?php

// Pusingan 7 — verifikasi DB selepas wizard klasifikasi UI (rekod #4 MAM)
use App\Models\Minit;
use App\Models\NotificationLog;
use App\Models\Record;

$r = Record::find(4);
echo 'Rekod #4: status='.$r->status->value
    .' | our_ref='.$r->our_ref
    .' | their_ref='.$r->their_ref
    .' | file='.$r->registry_file_id
    .' | enclosure='.$r->enclosure_no
    .' | direction='.($r->direction?->value ?? 'null')
    .' | title='.$r->title.PHP_EOL;

echo 'Minit rekod #4:'.PHP_EOL;
foreach (Minit::where('record_id', 4)->get() as $m) {
    $penerima = $m->recipients()->with('user')->get()->map(fn ($x) => $x->user?->name.'('.$x->kind.')')->implode(', ');
    echo '  #'.$m->id.' mosque='.$m->mosque_id.' priority='.$m->priority->value.' oleh='.$m->author_id.' penerima=['.$penerima.']'.PHP_EOL;
}

echo 'Minit dalam MAN (mesti 0): '.Minit::where('mosque_id', 2)->count().PHP_EOL;
echo 'NotificationLog terkini:'.PHP_EOL;
foreach (NotificationLog::latest('id')->limit(5)->get() as $n) {
    echo '  '.$n->channel.' '.$n->status.' to_user='.$n->user_id.' jenis='.class_basename($n->notification_type ?? '').PHP_EOL;
}
