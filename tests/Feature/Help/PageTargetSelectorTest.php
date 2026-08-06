<?php

/**
 * F6-W4 — kelas VENDOR yang `help/page-target-plan.js` bergantung padanya mesti benar-benar
 * wujud dalam HTML halaman berkenaan.
 *
 * Jurang yang ujian ini tutup, DIUKUR pada gate W4 pusingan 1: pemetaan JS menyasar
 * `.fi-ta-filters-trigger-action-ctn` kerana kelas itu kelihatan dalam blade Filament —
 * tetapi ia dirender HANYA `@if ($hasCollapsibleFilters)` (susun atur tapisan boleh-kuncup),
 * bukan pada susun atur LALAI. Akibatnya `log-filters` dan `minit-filters` tidak pernah
 * wujud, dan lima guide `workflow` gagal dengan `sasaran dijangka: -:tiada`.
 *
 * Ujian unit JS (`e2e/page-target-plan.spec.js`) tidak dapat menangkapnya: ia membuktikan
 * pemetaan itu konsisten dengan registri, bukan bahawa selektornya sepadan DOM sebenar.
 * Ujian render PHP pula tidak dapat melihat `data-help-target` bagi sasaran ini kerana
 * `decorateTargets()` berjalan dalam PELAYAR. Yang boleh — dan mesti — disahkan di sini
 * ialah KELAS VENDOR yang menjadi sauh pemetaan itu.
 *
 * Ia juga penjaga naik taraf: jika Filament menukar nama kelas jadualnya, ujian ini merah
 * sebelum shard e2e yang panjang itu merah.
 *
 * F6-W5 meluaskannya kepada panel ADMIN dan papan pemuka. Itu penting kerana W5 ialah wave
 * PERTAMA yang menyasar apa-apa pada panel admin, jadi tiada satu pun sauh admin pernah
 * dibuktikan sebelum ini.
 */

use App\Models\MosqueActivityLog;
use App\Models\User;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
    $this->super = User::query()->create([
        'name' => 'Super', 'email' => 'super@selector.test', 'password' => bcrypt('secret'),
        'is_superadmin' => true, 'is_active' => true,
    ]);
});

/** Selektor yang `resources/js/help/page-target-plan.js` gunakan, per laluan (panel tenant). */
dataset('sauh-vendor-tenant', [
    'records: carian' => ['/app/mam/records', 'fi-ta-search-field'],
    'registry-files: carian' => ['/app/mam/registry-files', 'fi-ta-search-field'],
    'log-aktiviti: carian' => ['/app/mam/log-aktiviti', 'fi-ta-search-field'],
    'log-aktiviti: tapisan' => ['/app/mam/log-aktiviti', 'fi-ta-filters-dropdown'],
    'minit-saya: tapisan' => ['/app/mam/minit-saya', 'fi-ta-filters-dropdown'],
    // F6-W5
    'classification-nodes: carian' => ['/app/mam/classification-nodes', 'fi-ta-search-field'],
    'sensitive-access-logs: carian' => ['/app/mam/sensitive-access-logs', 'fi-ta-search-field'],
    'tiket-sokongan: carian' => ['/app/mam/tiket-sokongan', 'fi-ta-search-field'],
    'papan pemuka: widget statistik' => ['/app/mam', 'fi-wi-stats-overview'],
]);

/** F6-W5 — sauh panel ADMIN (superadmin). */
dataset('sauh-vendor-admin', [
    'admin papan pemuka: widget statistik' => ['/admin', 'fi-wi-stats-overview'],
    'admin/mosques: carian' => ['/admin/mosques', 'fi-ta-search-field'],
    'admin/users: carian' => ['/admin/users', 'fi-ta-search-field'],
    'admin/storage-orders: carian' => ['/admin/storage-orders', 'fi-ta-search-field'],
    'admin/help-announcements: carian' => ['/admin/help-announcements', 'fi-ta-search-field'],
    'admin/tiket-sokongan: carian' => ['/admin/tiket-sokongan', 'fi-ta-search-field'],
]);

test('kelas vendor yang pemetaan JS bergantung padanya wujud dalam HTML', function (string $laluan, string $kelas) {
    // Log aktiviti perlu sekurang-kurangnya satu baris supaya jadual (dan toolbarnya)
    // dirender penuh; `MosqueActivityLog` kosong dalam fixture ujian bersih.
    MosqueActivityLog::query()->create([
        'mosque_id' => $this->mam->id,
        'actor_id' => $this->admin->id,
        'actor_name' => $this->admin->name,
        'action' => 'record_uploaded',
        'description' => 'Dokumen dimuat naik untuk ujian sauh selektor.',
    ]);

    $html = $this->actingAs($this->admin)->get($laluan)->assertOk()->getContent();

    expect(str_contains($html, $kelas))->toBeTrue(
        "{$laluan}: kelas vendor `{$kelas}` tiada dalam HTML — pemetaan `page-target-plan.js` "
        .'tidak akan menemui elemennya dan sasarannya tidak akan pernah wujud.',
    );
})->with('sauh-vendor-tenant');

test('kelas vendor panel admin wujud dalam HTML', function (string $laluan, string $kelas) {
    $html = $this->actingAs($this->super)->get($laluan)->assertOk()->getContent();

    expect(str_contains($html, $kelas))->toBeTrue(
        "{$laluan}: kelas vendor `{$kelas}` tiada dalam HTML panel admin.",
    );
})->with('sauh-vendor-admin');

test('setiap selektor dalam page-target-plan.js diuji oleh dataset di atas', function () {
    // Penjaga terhadap DRIFT: menambah pemetaan baharu tanpa menambah sauhnya di sini akan
    // mengembalikan tepat jurang yang W4 bayar harganya.
    $modul = file_get_contents(base_path('resources/js/help/page-target-plan.js'));
    preg_match_all("/\['(\.[a-z0-9-]+)',\s*'[a-z-]+'\]/i", $modul, $padanan);

    $selektor = collect($padanan[1])->map(fn (string $s): string => ltrim($s, '.'))->unique()->values();
    $diuji = collect(['fi-ta-search-field', 'fi-ta-filters-dropdown', 'fi-wi-stats-overview']);

    expect($selektor->diff($diuji)->all())->toBe([],
        'ada selektor dalam page-target-plan.js yang tiada sauh dalam dataset `sauh-vendor`');
});

test('setiap LALUAN dalam page-target-plan.js mempunyai sauh yang diuji', function () {
    // Penjaga kedua, lebih kuat daripada penjaga selektor: kelas yang sama boleh WUJUD pada
    // satu halaman dan TIADA pada halaman lain (`.fi-ta-search-field` bergantung kepada
    // `searchable()` jadual itu). Menguji selektor sahaja membenarkan laluan baharu
    // menyelinap tanpa bukti — tepat corak yang W4 pusingan 1 dedahkan.
    $modul = file_get_contents(base_path('resources/js/help/page-target-plan.js'));
    preg_match_all("/route: '([^']+)'/", $modul, $padanan);

    $laluanModul = collect($padanan[1])->unique()->sort()->values();
    // Dataset Pest tidak boleh dibaca semula secara programatik di sini, jadi laluan yang
    // BENAR-BENAR diliputi disenaraikan sekali lagi. Senarai ini mesti bergerak serentak
    // dengan kedua-dua dataset di atas; jika modul mendahuluinya, ujian ini merah.
    $laluanDiuji = collect([
        '/app/{tenant}/records', '/app/{tenant}/log-aktiviti', '/app/{tenant}/minit-saya',
        '/app/{tenant}/registry-files', '/app/{tenant}/classification-nodes',
        '/app/{tenant}/sensitive-access-logs', '/app/{tenant}/tiket-sokongan',
        '/app/{tenant}', '/admin', '/admin/mosques', '/admin/users',
        '/admin/storage-orders', '/admin/help-announcements', '/admin/tiket-sokongan',
    ])->sort()->values();

    expect($laluanModul->diff($laluanDiuji)->all())->toBe([],
        'ada laluan dalam page-target-plan.js tanpa sauh yang diuji');
});
