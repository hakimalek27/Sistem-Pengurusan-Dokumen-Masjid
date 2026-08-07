<?php

/**
 * F6-W6 (§7.2 gate registry (b)) — dua sasaran terakhir F6, pada halaman AWAM.
 *
 * `public.help` ialah satu-satunya guide yang masih generik selepas W5, dan kedua-dua
 * langkahnya berada pada `/bantuan` — halaman TETAMU. Jadi ujian ini sengaja TIDAK
 * berautentikasi: jika sasaran hanya wujud selepas log masuk, W6 tidak menyelesaikan apa-apa.
 *
 * Komponen `help-center` DIKONGSI oleh ketiga-tiga panel (awam, tenant, admin), jadi ujian
 * turut mengunci bahawa menambah sasaran pada komponen kongsi itu tidak menghasilkan
 * pendua pada halaman tenant — satu elemen, satu sasaran, satu kali (pelajaran regresi F5c).
 */
test('sasaran W6 wujud tepat sekali pada halaman bantuan AWAM tanpa log masuk', function (string $target) {
    $html = $this->get('/bantuan')->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="'.$target.'"'))->toBe(1,
        "/bantuan: sasaran {$target} mesti wujud TEPAT SEKALI untuk tetamu");
})->with(['help-search-form', 'help-scope']);

/**
 * Sorotan mesti KETAT, bukan sekadar hadir.
 *
 * Kecacatan makna yang direkod dalam LAPORAN-F6-W5.md §17: `help-search` ialah seksyen
 * pembungkus yang meliputi hampir seluruh lajur kandungan (diukur 1199px pada /bantuan,
 * 3211px pada panel tenant), jadi menyorotnya hampir tidak dapat dibezakan daripada
 * menyorot `page-content`. Kedua-dua sasaran W6 mesti berada DI DALAM seksyen itu — itulah
 * yang membuktikan ia lebih ketat, dan ia gagal jika sesiapa memindahkan atributnya ke
 * pembungkus demi kemudahan.
 */
test('sasaran W6 bersarang DI DALAM seksyen help-search, bukan menggantikannya', function () {
    $html = $this->get('/bantuan')->assertOk()->getContent();

    $seksyen = strpos($html, 'data-help-target="help-search"');
    $skop = strpos($html, 'data-help-target="help-scope"');
    $borang = strpos($html, 'data-help-target="help-search-form"');

    expect($seksyen)->not->toBeFalse('seksyen help-search tiada')
        ->and($skop)->toBeGreaterThan($seksyen, 'help-scope mesti DI DALAM seksyen help-search')
        ->and($borang)->toBeGreaterThan($skop, 'help-search-form mesti selepas help-scope');

    // Seksyen pembungkus kekal ada: `tenant.bantuan#1` masih merujuknya (penghalusan
    // sasaran itu diperuntukkan kepada F7, bukan W6).
    expect(substr_count($html, 'data-help-target="help-search"'))->toBe(1);
});

test('katalog public.help tidak lagi membawa sasaran generik', function () {
    $katalog = json_decode(
        (string) file_get_contents(resource_path('help/guides.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $guide = collect($katalog['guides'])->firstWhere('id', 'public.help');
    expect($guide)->not->toBeNull();

    $sasaran = collect($guide['steps'])->pluck('target')->all();
    expect($sasaran)->toBe(['help-search-form', 'help-scope']);

    // Penjaga seluruh katalog: W6 ialah gelombang TERAKHIR, jadi selepasnya tiada satu pun
    // langkah `public` yang masih generik. Ini menangkap regresi yang menambah langkah
    // generik baharu ke dalam family `public` tanpa melalui proses justifikasi.
    $generikAwam = collect($katalog['guides'])
        ->filter(fn (array $g) => $g['panel'] === 'public')
        ->flatMap(fn (array $g) => collect($g['steps'])->map(fn (array $s) => [
            'kunci' => $g['id'],
            'target' => $s['target'],
        ]))
        ->filter(fn (array $s) => in_array($s['target'], ['page-content', 'page-primary'], true))
        ->pluck('kunci')
        ->all();

    expect($generikAwam)->toBe([], 'family `public` mesti 0 langkah generik selepas W6');
});

test('sasaran W6 turut dirender pada panel tenant tanpa pendua', function () {
    $mam = makeMosque('MAM', 'mam');
    $admin = makeMember($mam, 'admin_masjid', 'admin@mam.test');

    $html = $this->actingAs($admin)->get('/app/mam/bantuan')->assertOk()->getContent();

    foreach (['help-search-form', 'help-scope'] as $target) {
        expect(substr_count($html, 'data-help-target="'.$target.'"'))->toBe(1,
            "/app/mam/bantuan: {$target} mesti wujud tepat sekali (komponen dikongsi)");
    }
});
