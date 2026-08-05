<?php

/**
 * F6-W2 (§7.2 gate registry (b)) — dua sasaran BAHARU mesti benar-benar wujud dalam HTML
 * yang dirender, bukan sekadar tersenarai dalam `resources/help/targets.json`.
 *
 * Penjaga yatim (`HelpCatalogQualityTest §6.5 #7`) hanya membandingkan katalog ↔ registri:
 * ia lulus walaupun atributnya tidak pernah sampai ke DOM. Jurang itulah yang ditutup di
 * sini, dan ia ditutup pada laluan yang sama seperti pengguna sebenar — jadual dengan data.
 *
 * `->extraCellAttributes()` dipasang pada BARIS PERTAMA sahaja (corak `baris1`), jadi ujian
 * turut membuktikan KEUNIKAN (G2): dua baris → tetap satu padanan.
 */

use App\Enums\MinitPriority;
use App\Services\MinitService;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
    $this->node = makeNode($this->mam, '100-4', 'dalaman');
    $this->file = makeFile($this->mam, $this->node, 'dalaman');
});

test('sasaran minit-record wujud TEPAT SEKALI dalam /minit-saya yang dirender', function () {
    // Dua minit → dua baris; hanya baris pertama boleh membawa sasaran.
    foreach (['Rekod minit satu', 'Rekod minit dua'] as $tajuk) {
        $record = makeRecord($this->mam, $this->file, 'dalaman', 'surat_menyurat', ['title' => $tajuk]);
        app(MinitService::class)->create(
            $record, $this->admin, [$this->admin->getKey()], [], 'Sila ambil tindakan.', MinitPriority::Biasa,
        );
    }

    $html = $this->actingAs($this->admin)->get('/app/mam/minit-saya')->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="minit-record"'))->toBe(1,
        'sasaran minit-record tidak wujud (atau tidak unik) dalam /minit-saya');
});

test('sasaran inbox-scan-status wujud TEPAT SEKALI dalam /peti-masuk yang dirender', function () {
    // `registry_file_id` null → status PetiMasuk (rujuk makeRecord), iaitu syarat
    // `InboxResource::getEloquentQuery()`.
    foreach (['Dokumen peti masuk satu', 'Dokumen peti masuk dua'] as $tajuk) {
        makeRecord($this->mam, null, 'dalaman', 'surat_menyurat', ['title' => $tajuk]);
    }

    $html = $this->actingAs($this->admin)->get('/app/mam/peti-masuk')->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="inbox-scan-status"'))->toBe(1,
        'sasaran inbox-scan-status tidak wujud (atau tidak unik) dalam /peti-masuk');
});
