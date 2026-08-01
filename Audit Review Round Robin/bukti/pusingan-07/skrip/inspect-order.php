<?php

use App\Models\StorageAddon;
use App\Models\StorageOrder;

$o = StorageOrder::first();
echo 'Order #'.$o->id.' invois='.$o->invoice_no.' gb='.var_export($o->gb, true)
    .' unit='.var_export($o->unit_price_cents, true)
    .' amaun='.var_export($o->amount_cents, true)
    .' tempoh='.var_export($o->period_months, true)
    .' status='.$o->status->value.PHP_EOL;
echo 'Attrs: '.json_encode($o->only(['gb', 'unit_price_cents', 'amount_cents', 'period_months'])).PHP_EOL;

foreach (StorageAddon::all() as $a) {
    echo 'Addon #'.$a->id.' mosque='.$a->mosque_id.' gb='.var_export($a->gb, true).' status='.$a->status.' luput='.$a->expires_at.PHP_EOL;
}
