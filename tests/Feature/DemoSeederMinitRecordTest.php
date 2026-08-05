<?php

/**
 * F6-W3 — invarian benih demo yang CI dedahkan (run 31001766297).
 *
 * `DemoSeeder` menggantung satu minit demo dengan EMPAT penerima tindakan (pengerusi, nazir,
 * ketua_imam, ajk). `MinitService` menolak minit itu sepenuhnya jika mana-mana penerima tidak
 * dibenarkan melihat rekodnya. Jadi rekod yang dipilih mesti:
 *   (a) dipilih secara DETERMINISTIK — `->first()` tanpa `ORDER BY` memberi baris berbeza pada
 *       PostgreSQL berbanding SQLite, dan
 *   (b) bukan `sulit` pada rekod MAHUPUN failnya (sensitiviti efektif = max kedua-duanya).
 *
 * Ujian pertama menggunakan dataset TERKAWAL supaya ia gagal pada kod lama tanpa bergantung
 * kepada nasib susunan baris — iaitu perkara yang menjadikan pepijat asal begitu sukar dilihat.
 */

use App\Enums\RecordStatus;
use App\Enums\Sensitivity;
use App\Models\Minit;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('pemilihan rekod minit demo melangkau rekod sulit walaupun ia yang PERTAMA mengikut id', function () {
    $mam = makeMosque('MAM', 'mam');
    $nod = makeNode($mam, '100-4', 'dalaman');

    // Perangkap: rekod ber-id TERENDAH ialah `sulit` (rekod dan fail). Kod lama
    // (`->first()` tanpa tapisan) akan memilihnya pada SQLite MAHUPUN PostgreSQL.
    $failSulit = makeFile($mam, $nod, 'sulit');
    $sulit = makeRecord($mam, $failSulit, 'sulit', 'surat_menyurat', ['title' => 'Rekod sulit dahulu']);
    $sulit->forceFill(['status' => RecordStatus::Difailkan])->saveQuietly();

    $failBiasa = makeFile($mam, $nod, 'dalaman');
    $biasa = makeRecord($mam, $failBiasa, 'dalaman', 'surat_menyurat', ['title' => 'Rekod dalaman kemudian']);
    $biasa->forceFill(['status' => RecordStatus::Difailkan])->saveQuietly();

    expect($sulit->id)->toBeLessThan($biasa->id, 'fixture lemah: rekod sulit mesti ber-id lebih rendah');

    $dipilih = DemoSeeder::rekodDemoUntukMinit($mam);

    expect($dipilih?->id)->toBe($biasa->id,
        'pemilihan mengambil rekod sulit — ketua_imam/ajk tidak boleh melihatnya dan MinitService akan menolak minit');
});

test('minit demo sebenar boleh dilihat oleh SETIAP empat penerima tindakan', function () {
    $this->seed(DemoSeeder::class);

    $minit = Minit::query()->orderBy('id')->first();
    expect($minit)->not->toBeNull('benih demo tidak menghasilkan sebarang minit');

    foreach (['pengerusi', 'nazir', 'ketua_imam', 'ajk'] as $peranan) {
        $user = User::query()->where('email', "{$peranan}@demo.test")->first();
        expect($user)->not->toBeNull("pengguna demo {$peranan} tiada");
        expect($user->can('view', $minit->record))->toBeTrue(
            "{$peranan} tidak boleh melihat rekod minit demo — MinitService akan menolak minit ini",
        );
    }
});

test('data demo MEMANG mengandungi rekod sulit — perangkapnya nyata, bukan teori', function () {
    $this->seed(DemoSeeder::class);

    $mam = Mosque::query()->where('slug', 'mam')->firstOrFail();
    $calon = Record::query()
        ->where('mosque_id', $mam->id)
        ->where('status', RecordStatus::Difailkan)
        ->get();

    $adaSulit = $calon->contains(fn ($r) => $r->sensitivity === Sensitivity::Sulit
        || $r->registryFile?->sensitivity === Sensitivity::Sulit);

    expect($adaSulit)->toBeTrue(
        'benih demo tidak lagi mengandungi rekod sulit — ujian pertama kehilangan maknanya; '
        .'semak semula sama ada tapisan sensitiviti masih diperlukan',
    );
});
