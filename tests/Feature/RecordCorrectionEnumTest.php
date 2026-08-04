<?php

use App\Enums\RecordDirection;
use App\Enums\Sensitivity;
use App\Services\RecordCorrectionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * BUG-B — ditemui daripada LOG PRODUKSI, bukan daripada ujian.
 *
 *   [2026-07-22 23:55:31] production.ERROR: Object of class App\Enums\RecordDirection could
 *   not be converted to string ... at app/Services/RecordCorrectionService.php:154
 *   (3 kali, userId 1)
 *
 * `comparable()` hanya mengendalikan DateTimeInterface dan array, lalu `(string) $value`.
 * `Record::direction` dan `Record::sensitivity` di-cast kepada enum, jadi membandingkan nilai
 * SEMASA rekod dengan nilai yang dimohon melemparkan Error maut → 500 kepada pengguna.
 *
 * Sebab suite terlepas: helper `makeRecord()` TIDAK menetapkan `direction` (null → `(string)
 * null` = '' tidak melempar), dan satu-satunya ujian pembetulan yang ada hanya menukar
 * `title`/`our_ref`. Ujian di bawah menggunakan rekod yang BERNILAI — keadaan setiap rekod
 * sebenar di produksi.
 */
beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->mam = makeMosque('MAM', 'mam');
    $this->pemohon = makeMember($this->mam, 'ajk', 'ajk-bugb@ujian.test');
    $this->penyemak = makeMember($this->mam, 'kerani', 'kerani-bugb@ujian.test');
    $this->record = makeRecord($this->mam, makeFile($this->mam, makeNode($this->mam, '100-4')), 'dalaman', 'surat_menyurat', [
        'title' => 'Surat Asal',
        'direction' => RecordDirection::Masuk,
    ]);
});

it('#1 memohon pembetulan `direction` tidak lagi melemparkan Error (punca 500 produksi)', function () {
    expect($this->record->direction)->toBeInstanceOf(RecordDirection::class);

    $request = app(RecordCorrectionService::class)->request(
        $this->record, $this->pemohon, 'Arah rekod tersalah tawan semasa klasifikasi.',
        ['direction' => 'keluar'],
    );

    expect($request->proposed_changes)->toBe(['direction' => 'keluar'])
        ->and($request->status)->toBe('menunggu')
        ->and($this->record->fresh()->direction)->toBe(RecordDirection::Masuk); // belum diluluskan
});

it('#2 `sensitivity` (enum kedua) juga selamat', function () {
    expect($this->record->sensitivity)->toBeInstanceOf(Sensitivity::class);

    $request = app(RecordCorrectionService::class)->request(
        $this->record, $this->pemohon, 'Kandungan sepatutnya sulit.',
        ['sensitivity' => 'sulit'],
    );

    expect($request->proposed_changes)->toBe(['sensitivity' => 'sulit']);
});

it('#3 SEMANTIK: nilai enum yang SAMA masih dikira "tiada perubahan"', function () {
    // Ini yang membezakan pembaikan betul daripada tampalan malas: jika `comparable()`
    // memulangkan sesuatu seperti "App\Enums\RecordDirection::Masuk" untuk enum tetapi
    // "masuk" untuk input borang, setiap permohonan akan lulus sebagai "perubahan" palsu.
    expect(fn () => app(RecordCorrectionService::class)->request(
        $this->record, $this->pemohon, 'Sebab yang sah.',
        ['direction' => 'masuk', 'sensitivity' => 'dalaman'],
    ))->toThrow(ValidationException::class);
});

it('#4 borang menghantar SEMUA medan: hanya yang benar-benar berubah disimpan', function () {
    $request = app(RecordCorrectionService::class)->request(
        $this->record, $this->pemohon, 'Betulkan arah sahaja.',
        ['title' => 'Surat Asal', 'direction' => 'dalaman', 'sensitivity' => 'dalaman'],
    );

    expect($request->proposed_changes)->toBe(['direction' => 'dalaman']);
});

it('#6 INVARIAN: setiap enum dalam app/Enums ialah enum BERSANDAR (backed)', function () {
    // Pembaikan menangkap `\BackedEnum`. Ujian ini menjadikan andaian itu sebagai invarian
    // yang dikuatkuasakan: enum tanpa nilai (pure enum) akan mengembalikan Error maut yang
    // sama, jadi jika seseorang menambahnya, ujian ini merah SEBELUM pengguna kena 500.
    $bukanBersandar = [];

    foreach (glob(app_path('Enums/*.php')) as $laluan) {
        $kelas = 'App\\Enums\\'.basename($laluan, '.php');
        if (! enum_exists($kelas)) {
            continue;
        }
        if (! (new ReflectionEnum($kelas))->isBacked()) {
            $bukanBersandar[] = $kelas;
        }
    }

    expect($bukanBersandar)->toBe([], 'enum tanpa nilai tidak dilindungi comparable(): '.implode(', ', $bukanBersandar));
});

it('#5 kelulusan mengenakan perubahan enum pada rekod', function () {
    $request = app(RecordCorrectionService::class)->request(
        $this->record, $this->pemohon, 'Arah rekod tersalah tawan.',
        ['direction' => 'keluar'],
    );

    app(RecordCorrectionService::class)->review($request, $this->penyemak, true, 'Disahkan.');

    expect($this->record->fresh()->direction)->toBe(RecordDirection::Keluar)
        ->and($request->fresh()->status)->toBe('diluluskan');
});

it('#7 CERITA PENGGUNA: betulkan TAJUK sahaja — borang tetap hantar sensitivity+direction', function () {
    // ViewRecord.php:66-81 — borang "Mohon Pembetulan" menghantar KESEMUA 12 medan dengan
    // nilai lalai daripada rekod, dan `sensitivity` WAJIB (sentiasa bernilai). Jadi setiap
    // penghantaran menyentuh laluan enum walaupun pengguna hanya menukar tajuk. Inilah sebab
    // ciri ini 500 untuk SEMUA pengguna, bukan hanya mereka yang menukar Arah.
    $request = app(RecordCorrectionService::class)->request(
        $this->record, $this->pemohon, 'Tajuk tersalah taip semasa klasifikasi.',
        [
            'title' => 'Surat Dibetulkan',
            'record_type' => 'surat_menyurat',
            'direction' => 'masuk',      // tidak berubah — nilai lalai borang
            'sensitivity' => 'dalaman',  // tidak berubah — nilai lalai borang, WAJIB
        ],
    );

    expect($request->proposed_changes)->toBe(['title' => 'Surat Dibetulkan']);
});
