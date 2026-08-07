<?php

/**
 * F7 §8.3 (axe `empty-table-header` minor) — sel header lajur tindakan.
 *
 * Codex P6: `.fi-ta-actions-header-cell` kosong walaupun `aria-label` wujud, dan axe menuntut
 * TEKS atau `aria-hidden`. API semasa `recordActionsColumnLabel()`
 * (`vendor/filament/tables/src/Table/Concerns/HasRecordActions.php:76`);
 * `actionsColumnLabel()` ialah alias `@deprecated` (:162-164) dan tidak digunakan.
 *
 * Dua lapisan penjaga, sengaja:
 *   1. SUMBER — setiap jadual mesti memanggilnya. Ini yang menangkap jadual BAHARU yang
 *      ditambah kemudian; ujian render sahaja tidak akan tahu jadual baharu wujud.
 *   2. RENDER — label benar-benar sampai ke HTML. Penjaga sumber sahaja boleh lulus
 *      walaupun API dipanggil dengan cara yang tidak berkesan.
 */

use App\Filament\App\Resources\Records\Pages\ListRecords;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** Lapisan 1 — penjaga SUMBER ke atas semua jadual, termasuk yang belum wujud hari ini. */
test('setiap jadual Filament menamakan lajur tindakan', function () {
    $jadual = glob(app_path('Filament/*/Resources/*/Tables/*Table.php'));

    expect($jadual)->not->toBeEmpty('tiada fail jadual dijumpai — pemilih glob rosak');

    $tanpaLabel = [];
    $gunaApiLapuk = [];

    foreach ($jadual as $laluan) {
        $sumber = (string) file_get_contents($laluan);

        // Hanya jadual yang MEMANG mempunyai tindakan baris boleh mempunyai sel header itu.
        if (! str_contains($sumber, '->recordActions(')) {
            continue;
        }

        if (! str_contains($sumber, '->recordActionsColumnLabel(')) {
            $tanpaLabel[] = basename($laluan);
        }

        if (preg_match('/->actionsColumnLabel\(/', $sumber)) {
            $gunaApiLapuk[] = basename($laluan);
        }
    }

    expect($tanpaLabel)->toBe([], 'jadual ini tiada `recordActionsColumnLabel()`');
    expect($gunaApiLapuk)->toBe([], 'jadual ini guna alias @deprecated `actionsColumnLabel()`');
});

/**
 * ⚠️ PENJAGA ANTI-FIXTURE-LEMAH: glob di atas mesti benar-benar menemui jadual yang KITA tahu
 * wujud. Jika laluannya berubah, ujian di atas akan lulus atas senarai KOSONG selepas
 * penapisan — dan `expect([])->toBe([])` sentiasa hijau.
 */
test('penjaga sumber benar-benar memeriksa jadual yang diketahui', function () {
    $jadual = array_map('basename', glob(app_path('Filament/*/Resources/*/Tables/*Table.php')));

    expect($jadual)->toContain('RecordsTable.php')
        ->and($jadual)->toContain('InboxTable.php')
        ->and($jadual)->toContain('UsersTable.php')
        ->and(count($jadual))->toBeGreaterThanOrEqual(14);
});

/**
 * Lapisan 2 — label benar-benar dirender pada HTML jadual sebenar.
 *
 * ⚠️ DUA fakta yang perlu diukur, bukan diandaikan, sebelum ujian ini bermakna:
 *
 * 1. **Header tindakan hanya dirender apabila jadual mempunyai BARIS.** Vendor
 *    (`index.blade.php:1577-1594`) memagar sel itu dengan `@if (count($records))`. Jadual
 *    kosong = tiada sel = ujian hijau palsu. Ini keluarga sama seperti "skrin tanpa data
 *    menjadikan gate hijau palsu" (F6-W1).
 * 2. **`makeRecord($mosque, null)` menghasilkan status `PetiMasuk`**, dan
 *    `RecordResource::getEloquentQuery()` MENGECUALIKAN status itu (`status != peti_masuk`).
 *    Fixture pertama saya mempunyai 1 rekod dalam DB tetapi **0 baris kelihatan** — diukur,
 *    bukan disyaki. Rekod mesti DIFAILKAN, jadi ia perlu `RegistryFile`.
 *
 * Kelas `fi-ta-actions-header-cell` yang dipetik dalam pelan hanya wujud pada cabang
 * TANPA label (`:1590-1593`). Apabila label diberi, vendor merender `<th class="fi-ta-header-cell">`
 * biasa — jadi mencari kelas lama itu akan sentiasa gagal selepas pembaikan berjaya.
 */
test('label Tindakan dirender pada header jadual yang mempunyai baris', function () {
    Storage::fake(config('diwan.storage_disk'));

    $mam = makeMosque('MAM', 'mam');
    $admin = makeMember($mam, 'admin_masjid', 'admin@mam.test');
    $nod = makeNode($mam, '100-4', 'dalaman');
    $fail = makeFile($mam, $nod, 'dalaman');
    makeRecord($mam, $fail, 'dalaman');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($mam, isQuiet: true);
    $this->actingAs($admin);

    // Prasyarat diassert secara eksplisit: tanpa baris, sel header tidak wujud langsung dan
    // ujian ini tidak membuktikan apa-apa.
    $jadual = Livewire::test(ListRecords::class)->instance()->getTable();
    expect($jadual->getRecords()->count())->toBeGreaterThan(0,
        'jadual tiada baris kelihatan — header tindakan tidak akan dirender (hijau palsu)');
    expect($jadual->getRecordActionsColumnLabel())->toBe('Tindakan');

    $html = $this->get('/app/mam/records')->assertOk()->getContent();

    expect($html)->toContain('Tindakan')
        ->and($html)->not->toContain('fi-ta-empty-header-cell');
});

afterEach(function () {
    Filament::setTenant(null, isQuiet: true);
});
