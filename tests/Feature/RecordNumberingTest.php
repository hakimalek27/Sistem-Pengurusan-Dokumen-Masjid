<?php

use App\Services\RecordNumberingService;

beforeEach(function () {
    $this->svc = new RecordNumberingService;
});

it('memformat file_no mengikut §5.15 (MAM.500-1/2/3)', function () {
    expect($this->svc->formatFileNo('MAM', '500-1/2', 3))->toBe('MAM.500-1/2/3');
});

it('memformat jilid > 1 sebagai " Jld.{n}"', function () {
    expect($this->svc->formatFileNo('MAM', '100-4', 1, 2))->toBe('MAM.100-4/1 Jld.2');
});

it('memberi transaction_no berturutan bawah nod sama', function () {
    $mam = makeMosque('MAM', 'mam');
    $node = makeNode($mam, '100-4');

    $f1 = $this->svc->openFile($mam, $node, 'Fail Pertama');
    $f2 = $this->svc->openFile($mam, $node, 'Fail Kedua');

    expect($f1->transaction_no)->toBe(1)
        ->and($f2->transaction_no)->toBe(2)
        ->and($f1->file_no)->toBe('MAM.100-4/1')
        ->and($f2->file_no)->toBe('MAM.100-4/2');
});

it('memperuntuk enclosure_no berturutan tanpa langkau', function () {
    $mam = makeMosque('MAM', 'mam');
    $node = makeNode($mam, '100-4');
    $file = $this->svc->openFile($mam, $node, 'Fail');

    $nums = collect(range(1, 5))->map(fn () => $this->svc->allocateEnclosureNo($file->fresh()))->all();

    expect($nums)->toBe([1, 2, 3, 4, 5])
        ->and($file->fresh()->enclosure_count)->toBe(5);
});

it('membenarkan transaction_no sama merentas masjid berbeza (skop masjid)', function () {
    $mam = makeMosque('MAM', 'mam');
    $man = makeMosque('MAN', 'man');
    $nodeMam = makeNode($mam, '100-4');
    $nodeMan = makeNode($man, '100-4');

    $fMam = $this->svc->openFile($mam, $nodeMam, 'Fail MAM');
    $fMan = $this->svc->openFile($man, $nodeMan, 'Fail MAN');

    // Kedua-dua boleh mempunyai transaction_no 1 (berskop masjid).
    expect($fMam->transaction_no)->toBe(1)
        ->and($fMan->transaction_no)->toBe(1)
        ->and($fMam->file_no)->toBe('MAM.100-4/1')
        ->and($fMan->file_no)->toBe('MAN.100-4/1');
});

it('membuka jilid baharu: fail lama ditutup, jilid+1 dengan transaksi sama', function () {
    $mam = makeMosque('MAM', 'mam');
    $node = makeNode($mam, '100-4');
    $file = $this->svc->openFile($mam, $node, 'Fail');

    $vol2 = $this->svc->openNextVolume($file);

    expect($file->fresh()->status)->toBe('tutup')
        ->and($vol2->volume)->toBe(2)
        ->and($vol2->transaction_no)->toBe(1)
        ->and($vol2->file_no)->toBe('MAM.100-4/1 Jld.2')
        ->and($vol2->status)->toBe('terbuka');
});
