<?php

use App\Models\Mosque;
use App\Models\StorageOrder;

foreach (Mosque::all() as $m) {
    echo $m->id.' '.$m->slug.' '.$m->name.' status='.$m->status->value.PHP_EOL;
}
foreach (StorageOrder::all() as $o) {
    echo 'Order #'.$o->id.' mosque='.$o->mosque_id.' invois='.$o->invoice_no.' status='.$o->status->value.' gb='.($o->blocks * 10).PHP_EOL;
}
