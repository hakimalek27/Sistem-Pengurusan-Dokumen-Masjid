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
