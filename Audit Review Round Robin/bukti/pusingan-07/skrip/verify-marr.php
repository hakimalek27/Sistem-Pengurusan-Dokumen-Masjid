<?php

use App\Models\ClassificationNode;
use App\Models\Mosque;
use App\Models\User;

$m = Mosque::where('slug', 'marr')->first();
echo 'marr: status='.$m->status->value.' | id='.$m->id.PHP_EOL;
echo 'Nod klasifikasi marr: '.ClassificationNode::where('mosque_id', $m->id)->count().PHP_EOL;

$u = User::where('email', 'admin@marr.test')->first();
echo 'Admin marr: '.($u ? 'wujud #'.$u->id.' nama='.$u->name : 'TIADA').PHP_EOL;
if ($u) {
    $roles = $u->mosques()->get()->map(fn ($x) => $x->slug.':'.($x->pivot->role ?? '?'))->implode(', ');
    echo 'Keahlian: '.$roles.PHP_EOL;
}

$log = storage_path('logs/laravel.log');
$tail = implode(PHP_EOL, array_slice(file($log), -200));
echo 'Magic/jemputan dalam mail log: '.(preg_match('/admin@marr\.test/', $tail) ? 'JUMPA e-mel ke admin@marr.test' : 'tiada dalam 200 baris akhir').PHP_EOL;
