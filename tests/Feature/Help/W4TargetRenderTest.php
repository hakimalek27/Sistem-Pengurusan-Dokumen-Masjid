<?php

/**
 * F6-W4 (§7.2 gate registry (b)) — sasaran BAHARU W4 mesti benar-benar wujud dalam HTML yang
 * dirender, bukan sekadar tersenarai dalam `resources/help/targets.json`.
 *
 * Sebab ujian ini ada: penjaga yatim hanya membandingkan katalog ↔ registri, jadi ia lulus
 * walaupun atribut tidak pernah sampai ke DOM (pelajaran W2/W3). Lima daripada sasaran ini
 * berada pada halaman yang benih demo tinggalkan KOSONG (`/pembetulan-rekod`,
 * `/sensitive-access-logs`, dan jadual `/retensi`), jadi setiap ujian menyemai fixturenya
 * sendiri — dan dua ujian membuktikan sasaran "keadaan LALAI" tetap wujud TANPA data,
 * iaitu invarian yang pelajaran W1 tuntut.
 *
 * Sasaran `records-search` / `regfiles-search` / `log-search` / `log-filters` /
 * `minit-filters` TIDAK diuji di sini: ia dipasang oleh `decorateTargets()` dalam pelayar,
 * jadi HTML pelayan tidak pernah membawanya. Ia dikunci oleh `e2e/page-target-plan.spec.js`
 * (fungsi tulen) dan oleh shard `workflow` (DOM sebenar).
 */

use App\Models\RecordCorrectionRequest;
use App\Models\SensitiveAccessLog;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
    $this->nod = makeNode($this->mam, '100-4', 'dalaman');
});

test('records-view wujud pada senarai /records', function () {
    $fail = makeFile($this->mam, $this->nod, 'dalaman');
    makeRecord($this->mam, $fail, 'dalaman', 'surat_menyurat', ['title' => 'Rekod untuk dibuka']);

    $html = $this->actingAs($this->admin)->get('/app/mam/records')->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="records-view"'))->toBeGreaterThanOrEqual(1,
        'butang Lihat senarai /records tidak membawa sasaran records-view');
});

test('regfiles-view dan regfiles-medium wujud pada senarai /registry-files', function () {
    makeFile($this->mam, $this->nod, 'dalaman');
    makeFile($this->mam, $this->nod, 'dalaman');

    $html = $this->actingAs($this->admin)->get('/app/mam/registry-files')->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="regfiles-view"'))->toBeGreaterThanOrEqual(1,
        'butang Lihat /registry-files tidak membawa sasaran regfiles-view');
    // Sel Medium guna corak `baris1()` — mesti UNIK walaupun ada dua fail (G2).
    expect(substr_count($html, 'data-help-target="regfiles-medium"'))->toBe(1,
        'regfiles-medium tidak unik — corak baris1() tidak berkuat kuasa');
});

test('regfiles-medium ikut baris pertama SETIAP render, bukan render pertama proses', function () {
    // Regresi CI W3 (run 31001766297): sifat statik hidup selama PROSES. Tanpa set semula
    // dalam `configure()`, render KEDUA dalam proses yang sama mengekalkan ID daripada render
    // pertama; jika baris pertama BERUBAH antara render, tiada sel yang sepadan → 0 padanan.
    //
    // ⚠️ DUA versi pertama ujian ini LULUS walaupun reset dibuang, dan sebab kedua-duanya
    // berbeza daripada yang saya jangka:
    //   (a) menambah fail pada nod SAMA — `defaultSort('file_no')` MENAIK, jadi fail baharu
    //       (`…/2`) tidak pernah menjadi baris pertama dan memo lama masih sah;
    //   (b) menambah fail yang menyusun LEBIH AWAL — memo lama masih menandakan fail LAMA,
    //       yang kini berada pada baris KEDUA, jadi `substr_count` kekal 1. Bilangan tidak
    //       pernah dapat menangkap kecacatan ini: yang berubah ialah KEDUDUKAN.
    // Sebab itu assertion di bawah menguji URUTAN dalam aliran HTML.
    $lama = makeFile($this->mam, $this->nod, 'dalaman');
    $pertama = $this->actingAs($this->admin)->get('/app/mam/registry-files')->assertOk()->getContent();
    expect(substr_count($pertama, 'data-help-target="regfiles-medium"'))->toBe(1);

    $nodAwal = makeNode($this->mam, '050-1', 'dalaman');
    $baharu = makeFile($this->mam, $nodAwal, 'dalaman');
    expect($baharu->file_no)->toBeLessThan($lama->file_no,
        'fixture lemah: fail kedua mesti menyusun SEBELUM yang pertama supaya baris pertama bertukar');

    $kedua = $this->actingAs($this->admin)->get('/app/mam/registry-files')->assertOk()->getContent();
    expect(substr_count($kedua, 'data-help-target="regfiles-medium"'))->toBe(1,
        'render kedua kehilangan regfiles-medium');

    // Jadual dirender baris demi baris, jadi sel yang ditanda mesti muncul SEBELUM nombor
    // fail baris kedua. Tanpa set semula, tanda melekat pada fail LAMA (baris kedua) dan
    // urutan ini terbalik.
    $posTanda = strpos($kedua, 'data-help-target="regfiles-medium"');
    $posBarisKedua = strpos($kedua, e($lama->file_no));
    expect($posBarisKedua)->not->toBeFalse('nombor fail lama tiada dalam HTML — fixture berubah?');
    expect($posTanda)->toBeLessThan($posBarisKedua,
        'regfiles-medium menandakan baris KEDUA — memo statik tidak diset semula antara render');
});

test('tiga sasaran /pembetulan-rekod wujud apabila ada permohonan menunggu', function () {
    $fail = makeFile($this->mam, $this->nod, 'dalaman');
    $rekod = makeRecord($this->mam, $fail, 'dalaman', 'surat_menyurat', ['title' => 'Rekod salah tawan']);

    RecordCorrectionRequest::query()->create([
        'mosque_id' => $this->mam->id,
        'record_id' => $rekod->id,
        'requested_by' => $this->admin->id,
        'reason' => 'Tajuk tersalah taip semasa klasifikasi.',
        'proposed_changes' => ['title' => 'Tajuk yang betul'],
        'status' => 'menunggu',
    ]);

    $html = $this->actingAs($this->admin)->get('/app/mam/pembetulan-rekod')->assertOk()->getContent();

    foreach (['correction-diff', 'correction-status'] as $sasaran) {
        expect(substr_count($html, 'data-help-target="'.$sasaran.'"'))->toBe(1,
            $sasaran.' tidak wujud (atau tidak unik) dalam /pembetulan-rekod');
    }
    expect(substr_count($html, 'data-help-target="correction-decision"'))->toBeGreaterThanOrEqual(1,
        'butang Luluskan tidak membawa sasaran correction-decision');
});

test('sensitive-log-record wujud apabila ada log akses sulit', function () {
    $fail = makeFile($this->mam, $this->nod, 'sulit');
    $rekod = makeRecord($this->mam, $fail, 'sulit', 'surat_menyurat', ['title' => 'Rekod sulit']);

    SensitiveAccessLog::query()->create([
        'mosque_id' => $this->mam->id,
        'is_superadmin' => false,
        'user_id' => $this->admin->id,
        'record_id' => $rekod->id,
        'action' => 'view',
        'ip' => '127.0.0.1',
        'user_agent' => 'ujian',
    ]);

    $html = $this->actingAs($this->admin)->get('/app/mam/sensitive-access-logs')->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="sensitive-log-record"'))->toBe(1,
        'sensitive-log-record tidak wujud (atau tidak unik) dalam /sensitive-access-logs');
});

test('sasaran /retensi wujud walaupun TIADA rekod cukup tempoh (keadaan LALAI)', function () {
    // Pelajaran W1: sasaran yang hanya muncul bila berdata menghasilkan gate hijau palsu.
    // Kedua-dua jadual halaman ini dilindungi `@if isEmpty`, jadi sasaran sengaja diletak
    // pada perenggan penerangan dan pembalut — dan itulah yang diuji di sini.
    $html = $this->actingAs($this->admin)->get('/app/mam/retensi')->assertOk()->getContent();

    foreach (['retention-schedule', 'retention-hold', 'retention-export'] as $sasaran) {
        expect(substr_count($html, 'data-help-target="'.$sasaran.'"'))->toBe(1,
            $sasaran.' tidak wujud (atau tidak unik) dalam /retensi tanpa data');
    }
});

test('sasaran /laporan wujud walaupun tenant tiada rekod (keadaan LALAI)', function () {
    $html = $this->actingAs($this->admin)->get('/app/mam/laporan')->assertOk()->getContent();

    foreach (['report-summary', 'report-export'] as $sasaran) {
        expect(substr_count($html, 'data-help-target="'.$sasaran.'"'))->toBe(1,
            $sasaran.' tidak wujud (atau tidak unik) dalam /laporan tanpa data');
    }
});
