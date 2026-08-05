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
 */

use App\Models\MosqueActivityLog;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
});

/** Selektor yang `resources/js/help/page-target-plan.js` gunakan, per laluan. */
dataset('sauh-vendor', [
    'records: carian' => ['/app/mam/records', 'fi-ta-search-field'],
    'registry-files: carian' => ['/app/mam/registry-files', 'fi-ta-search-field'],
    'log-aktiviti: carian' => ['/app/mam/log-aktiviti', 'fi-ta-search-field'],
    'log-aktiviti: tapisan' => ['/app/mam/log-aktiviti', 'fi-ta-filters-dropdown'],
    'minit-saya: tapisan' => ['/app/mam/minit-saya', 'fi-ta-filters-dropdown'],
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
})->with('sauh-vendor');

test('setiap selektor dalam page-target-plan.js diuji oleh dataset di atas', function () {
    // Penjaga terhadap DRIFT: menambah pemetaan baharu tanpa menambah sauhnya di sini akan
    // mengembalikan tepat jurang yang W4 bayar harganya.
    $modul = file_get_contents(base_path('resources/js/help/page-target-plan.js'));
    preg_match_all("/\['(\.[a-z0-9-]+)',\s*'[a-z-]+'\]/i", $modul, $padanan);

    $selektor = collect($padanan[1])->map(fn (string $s): string => ltrim($s, '.'))->unique()->values();
    $diuji = collect(['fi-ta-search-field', 'fi-ta-filters-dropdown']);

    expect($selektor->diff($diuji)->all())->toBe([],
        'ada selektor dalam page-target-plan.js yang tiada sauh dalam dataset `sauh-vendor`');
});
