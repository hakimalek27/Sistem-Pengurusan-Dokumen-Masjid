<?php

/**
 * F6-W3 (§7.2 gate registry (b)) — sasaran BAHARU `inbox-record` mesti benar-benar wujud
 * dalam HTML yang dirender, bukan sekadar tersenarai dalam `resources/help/targets.json`.
 *
 * Corak sama seperti `W2TargetRenderTest`: penjaga yatim hanya membandingkan katalog ↔
 * registri, jadi ia lulus walaupun atribut tidak pernah sampai ke DOM.
 *
 * `inbox-record` menandakan sel TAJUK baris pertama — iaitu "baris baharu" yang langkah 4
 * guide `screen.muat-naik-dokumen` suruh pengguna sahkan. Jadual disusun `created_at desc`,
 * jadi ujian turut mengesahkan bahawa sasaran mendarat pada rekod TERBAHARU, bukan sekadar
 * "sebarang baris" — kalau tidak, tour akan menunjuk dokumen lama selepas muat naik.
 */
beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
});

test('sasaran inbox-record wujud TEPAT SEKALI dalam /peti-masuk yang dirender', function () {
    // `registry_file_id` null → status PetiMasuk (rujuk makeRecord), iaitu syarat
    // `InboxResource::getEloquentQuery()`. Dua baris membuktikan keunikan (G2).
    foreach (['Dokumen lama peti masuk', 'Dokumen baharu peti masuk'] as $tajuk) {
        makeRecord($this->mam, null, 'dalaman', 'surat_menyurat', ['title' => $tajuk]);
    }

    $html = $this->actingAs($this->admin)->get('/app/mam/peti-masuk')->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="inbox-record"'))->toBe(1,
        'sasaran inbox-record tidak wujud (atau tidak unik) dalam /peti-masuk');
});

test('inbox-record menandakan baris TERBAHARU, bukan sebarang baris', function () {
    $lama = makeRecord($this->mam, null, 'dalaman', 'surat_menyurat', ['title' => 'Dokumen lama sekali']);
    $lama->forceFill(['created_at' => now()->subDays(3)])->saveQuietly();

    $baharu = makeRecord($this->mam, null, 'dalaman', 'surat_menyurat', ['title' => 'Dokumen paling baharu']);
    $baharu->forceFill(['created_at' => now()])->saveQuietly();

    $html = $this->actingAs($this->admin)->get('/app/mam/peti-masuk')->assertOk()->getContent();

    // Potong HTML pada kedudukan sasaran, kemudian cari tajuk mana yang paling hampir
    // SELEPASNYA — sel tajuk membawa atribut, jadi teksnya berada dalam sel yang sama.
    $pos = strpos($html, 'data-help-target="inbox-record"');
    expect($pos)->not->toBeFalse('sasaran inbox-record tidak dijumpai');

    // Sel tajuk MEMBAWA atribut, jadi teks tajuk berada dalam sel yang sama — diukur pada
    // HTML sebenar: atribut pada offset X, tajuk terbaharu pada X+1004.
    // ⚠️ `toContain()` Pest menerima needle BERVARIADIK — memberi "mesej" sebagai argumen
    // kedua menjadikannya rentetan KEDUA yang mesti dijumpai, dan ujian gagal atas sebab
    // yang salah. Guna `str_contains()` + `toBeTrue($mesej)` apabila mesej diperlukan.
    $selepasSasaran = substr($html, $pos, 2000);
    expect(str_contains($selepasSasaran, 'Dokumen paling baharu'))->toBeTrue(
        'inbox-record tidak menandakan baris terbaharu — tour akan menunjuk dokumen yang salah selepas muat naik',
    );
    expect(str_contains($selepasSasaran, 'Dokumen lama sekali'))->toBeFalse(
        'inbox-record menandakan baris yang salah (dokumen lama)',
    );
});

/**
 * 🔴 Regresi yang CI dedahkan (run 31001021747) dan ujian tempatan TIDAK boleh lihat.
 *
 * `baris1()` memoi baris pertama dalam sifat STATIK. Komennya berkata "hidup satu permintaan
 * sahaja" — itu benar di bawah php-fpm (satu proses satu permintaan), tetapi PALSU dalam
 * proses ujian dan di bawah pelayan yang kekal hidup (Octane/`artisan serve`). Statik itu
 * hidup selagi PROSES hidup.
 *
 * Sebab ia lulus tempatan tetapi gagal di CI: SQLite mengembalikan kaunter AUTOINCREMENT
 * apabila transaksi `RefreshDatabase` di-rollback, jadi ID rekod bermula semula pada 1 setiap
 * ujian dan kebetulan sepadan memo. Jujukan PostgreSQL **tidak** dirollback, jadi ID terus
 * menaik — memo memegang ID lapuk, `??=` tidak pernah menetapkannya semula, dan TIADA baris
 * padan: `substr_count(...) === 0`.
 *
 * Ujian ini bebas enjin DB: ia merender dua kali dalam SATU proses dengan baris pertama yang
 * BERBEZA. Pada kod lama ia gagal pada kedua-dua enjin.
 */
test('inbox-record ikut baris pertama SETIAP render, bukan render pertama proses', function () {
    makeRecord($this->mam, null, 'dalaman', 'surat_menyurat', ['title' => 'Render pertama'])
        ->forceFill(['created_at' => now()->subDay()])->saveQuietly();

    $html1 = $this->actingAs($this->admin)->get('/app/mam/peti-masuk')->assertOk()->getContent();
    expect(substr_count($html1, 'data-help-target="inbox-record"'))->toBe(1, 'render pertama');
    expect(str_contains(substr($html1, strpos($html1, 'data-help-target="inbox-record"'), 2000), 'Render pertama'))
        ->toBeTrue('render pertama tidak menandakan baris yang betul');

    // Dokumen BAHARU tiba (persis apa yang berlaku selepas muat naik) → baris pertama berubah.
    makeRecord($this->mam, null, 'dalaman', 'surat_menyurat', ['title' => 'Render kedua lebih baharu'])
        ->forceFill(['created_at' => now()])->saveQuietly();

    $html2 = $this->actingAs($this->admin)->get('/app/mam/peti-masuk')->assertOk()->getContent();
    expect(substr_count($html2, 'data-help-target="inbox-record"'))->toBe(1,
        'render kedua kehilangan sasaran — memo statik memegang ID render pertama');
    expect(str_contains(substr($html2, strpos($html2, 'data-help-target="inbox-record"'), 2000), 'Render kedua lebih baharu'))
        ->toBeTrue('render kedua masih menandakan baris LAMA — memo statik tidak diset semula');
});
