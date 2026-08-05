<?php

use App\Enums\RecordDirection;
use App\Enums\Sensitivity;
use App\Filament\App\Resources\Records\Pages\ViewRecord;
use App\Models\RecordCorrectionRequest;
use App\Services\RecordCorrectionService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

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

/** Halaman butiran rekod dalam konteks tenant + pengguna berautentikasi. */
function halamanRekod(): Testable
{
    test()->actingAs(test()->pemohon);
    Filament::setCurrentPanel('app');
    Filament::setTenant(test()->mam, isQuiet: true);

    return Livewire::test(ViewRecord::class, [
        'tenant' => test()->mam,
        'record' => test()->record->getKey(),
    ]);
}

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

/**
 * F6-W2 — KEGAGALAN SENYAP, ditemui oleh gate panduan (bukan oleh mata).
 *
 * `RecordCorrectionService::request()` melemparkan ValidationException berkunci **`changes`**
 * apabila tiada satu pun medan benar-benar berubah. Borang "Mohon Pembetulan" tidak mempunyai
 * medan bernama `changes`, jadi Filament tiada tempat untuk merender mesej itu: modal hanya
 * kekal terbuka, tiada toast, tiada ralat medan.
 *
 * Diukur pada larian gate sebelum pembaikan: 5 permintaan `/livewire/update` (jadi klik SAMPAI
 * ke pelayan), `(tiada mesej ralat dirender)`, dan tangkapan skrin memperlihatkan borang penuh
 * dengan butang Hantar yang kelihatan tidak melakukan apa-apa. Ini keluarga yang sama seperti
 * BUG-B: borang pembetulan gagal tanpa memberitahu pengguna.
 */
it('#8 hantar tanpa perubahan → pengguna DIBERITAHU (dahulu senyap sepenuhnya)', function () {
    $halaman = halamanRekod();

    $sebelum = RecordCorrectionRequest::query()->withoutGlobalScope('mosque')->count();

    // Hanya `reason` diisi; setiap medan lain kekal pada nilai SEMASA rekod.
    $halaman->callAction('mohonPembetulan', [
        'reason' => 'Saya rasa ada yang tidak kena dengan rekod ini.',
        'title' => $this->record->title,
        'record_type' => $this->record->record_type,
        'sensitivity' => $this->record->sensitivity->value,
        'direction' => $this->record->direction->value,
    ])->assertNotified('Tiada perubahan dikesan');

    expect(RecordCorrectionRequest::query()->withoutGlobalScope('mosque')->count())
        ->toBe($sebelum, 'permohonan kosong tidak sepatutnya dicipta');
});

it('#9 hantar DENGAN satu perubahan sebenar masih berjaya (penjaga tidak menyekat laluan sah)', function () {
    $halaman = halamanRekod();

    // ⚠️ Render SEMULA halaman selepas kejayaan melemparkan ViewException dalam ujian unit
    // Livewire (`tabs/tab.blade.php`: htmlspecialchars menerima array) — artifak harness,
    // BUKAN pepijat produk: laluan HTTP sebenar merender halaman yang sama dengan jayanya
    // (`FilamentResourcesTest` + `W2TargetRenderTest`). Yang diuji di sini ialah KESAN
    // tindakan, jadi pengecualian render dibiarkan dan keadaan pangkalan data yang diassert.
    try {
        $halaman->callAction('mohonPembetulan', [
            'reason' => 'Tajuk tersalah taip semasa tawanan asal.',
            'title' => 'Surat Asal (dibetulkan)',
            'record_type' => $this->record->record_type,
            'sensitivity' => $this->record->sensitivity->value,
            'direction' => $this->record->direction->value,
        ]);
    } catch (Throwable) {
        // diabaikan dengan sengaja — lihat nota di atas
    }

    $permohonan = RecordCorrectionRequest::query()->withoutGlobalScope('mosque')->latest('id')->first();

    expect($permohonan)->not->toBeNull('perubahan sebenar sepatutnya mencipta permohonan')
        ->and($permohonan->proposed_changes)->toHaveKey('title')
        ->and($permohonan->proposed_changes['title'])->toBe('Surat Asal (dibetulkan)');
});
