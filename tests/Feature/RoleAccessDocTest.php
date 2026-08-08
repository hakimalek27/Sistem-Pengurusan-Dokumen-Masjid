<?php

/**
 * F8 §9 gate #6 (P14-03) — dokumen akses role mesti KEKAL dijana daripada manifest.
 *
 * Jurang yang ujian ini tutup, DIUKUR bukan diandaikan:
 * `e2e/guidance.spec.js:16-18` sudah membaca `expected_page_counts` daripada manifest sejak F0,
 * jadi sisi spec tidak boleh menyimpang. Tetapi dokumen akses role membawa kiraan BERTULIS,
 * dan kiraan bertulis lapuk secara senyap — `AKSES-PAGE-MENGIKUT-ROLE-PRODUCTION-2026-07-21.md`
 * menunjukkan 21 halaman admin sedangkan kod sekarang mempunyai 25.
 *
 * ⚠️ Beza itu BUKAN percanggahan dan itu dibuktikan: 4 tambahan, 0 hilang, dan keempat-empatnya
 * (`/bantuan`, `/analitik-bantuan`, `/tiket-sokongan`, `/log-aktiviti`) ditambah 2026-07-22 —
 * sehari SELEPAS crawl itu. Dokumen bertarikh itu ialah rekod sejarah dan tidak disentuh.
 *
 * Yang dijaga di sini ialah dokumen DIJANA (`docs/AKSES-PAGE-MENGIKUT-ROLE.md`).
 *
 * ⚠️ Kiraan beku ialah PANEL-SKOP (`app`). Superadmin: 25 pada `app` + 12 pada `admin` = 37.
 * Menjumlahkan dua panel menghasilkan dokumen yang bercanggah dengan manifestnya sendiri —
 * versi pertama penjana ini melakukannya tepat, dan ujian ini menangkapnya.
 */

use Illuminate\Support\Facades\Process;

function manifestRoleRoutes(): array
{
    $laluan = base_path('Audit Review Round Robin/bukti/plan-baseline/manifest.json');
    expect(file_exists($laluan))->toBeTrue('manifest baseline tiada');

    return json_decode(file_get_contents($laluan), true)['role_routes'];
}

function dokAksesRole(): string
{
    $laluan = base_path('docs/AKSES-PAGE-MENGIKUT-ROLE.md');
    expect(file_exists($laluan))->toBeTrue(
        'docs/AKSES-PAGE-MENGIKUT-ROLE.md tiada — jalankan node scripts/audit/generate-role-access-doc.mjs',
    );

    return file_get_contents($laluan);
}

test('dokumen akses role menyatakan ia DIJANA dan menamakan sumbernya', function () {
    $dok = dokAksesRole();

    expect($dok)->toContain('DIJANA')
        ->and($dok)->toContain('role_routes')
        ->and($dok)->toContain('scripts/audit/generate-role-access-doc.mjs');
});

/** Label dokumen → identiti manifest. Dieja di sini supaya label yang berubah = ujian merah. */
const LABEL_KE_IDENTITI = [
    'Superadmin (Pentadbir Platform)' => 'superadmin',
    'Admin / Kerani' => 'admin_masjid',
    'Pengerusi' => 'pengerusi',
    'Setiausaha' => 'setiausaha',
    'Bendahari' => 'bendahari',
    'Nazir' => 'nazir',
    'Ketua Imam' => 'ketua_imam',
    'AJK' => 'ajk',
    'Juruaudit' => 'audit',
    'Orang Awam (tidak log masuk)' => 'public',
];

test('kiraan panel app dalam dokumen sepadan expected_page_counts manifest', function () {
    $rr = manifestRoleRoutes();
    $dok = dokAksesRole();

    // ⚠️ Versi pertama ujian ini hanya meminta ">= 9 baris" dan TIDAK memetakan label dokumen
    // kepada identiti manifest — jadi satu role yang HILANG atau tersalah label masih lulus
    // (Codex pusingan 1 #8). Kini setiap identiti mesti hadir MELALUI labelnya.
    preg_match_all('/^\|\s*([^|]+?)\s*\|\s*(\d+)\s*\|\s*(\d+)\s*\|\s*(\d+|—)\s*\|\s*(✔|✘|—)\s*\|$/mu', $dok, $m, PREG_SET_ORDER);

    $dalamDok = [];
    foreach ($m as $baris) {
        [, $label, $app, $adminPanel, $beku, $tanda] = $baris;
        $label = trim($label);
        if (! isset(LABEL_KE_IDENTITI[$label])) {
            continue;   // baris "Jumlah"
        }
        $dalamDok[LABEL_KE_IDENTITI[$label]] = [
            'app' => (int) $app, 'admin' => (int) $adminPanel, 'beku' => $beku, 'tanda' => $tanda,
        ];
    }

    // Setiap identiti manifest mesti hadir dalam jadual dokumen.
    $hilangDrpDok = array_values(array_diff(array_keys($rr['expected_page_counts']), array_keys($dalamDok)));
    expect($hilangDrpDok)->toBe([],
        'identiti tiada dalam jadual dokumen: '.implode(', ', $hilangDrpDok));

    // Dan setiap baris mesti sepadan kiraan yang DIKIRA SEMULA daripada manifest — bukan
    // sekadar sepadan nilai `expected_page_counts` yang dokumen sendiri cetak.
    $salah = [];
    foreach ($dalamDok as $identiti => $d) {
        $app = collect($rr['entries'])->filter(fn ($e) => $e['identity'] === $identiti
            && $e['expected_access'] === 'allow' && $e['in_navigation'] && $e['panel'] === 'app')->count();
        $admin = collect($rr['entries'])->filter(fn ($e) => $e['identity'] === $identiti
            && $e['expected_access'] === 'allow' && $e['in_navigation'] && $e['panel'] === 'admin')->count();

        if ($d['app'] !== $app) {
            $salah[] = "{$identiti} app dok={$d['app']} dikira={$app}";
        }
        if ($d['admin'] !== $admin) {
            $salah[] = "{$identiti} admin dok={$d['admin']} dikira={$admin}";
        }
        $beku = $rr['expected_page_counts'][$identiti] ?? null;
        if ($beku !== null && ($d['beku'] !== (string) $beku || $d['tanda'] !== '✔')) {
            $salah[] = "{$identiti} beku dok={$d['beku']} manifest={$beku} tanda={$d['tanda']}";
        }
    }
    expect($salah)->toBe([], 'dokumen menyimpang daripada manifest: '.implode(' · ', $salah));
});

test('setiap route disenaraikan di bawah identiti DAN panel yang betul', function () {
    $rr = manifestRoleRoutes();
    $dok = dokAksesRole();

    // ⚠️ Versi pertama menggunakan allowlist GLOBAL: route yang dibenarkan untuk AJK tetapi
    // diletakkan di bawah Juruaudit masih lulus (Codex #8). Kini dokumen dihurai per seksyen
    // identiti DAN per sub-tajuk panel, dan setiap route disemak terhadap pasangan itu.
    $perIdentitiPanel = [];
    $identiti = null;
    $panel = null;
    foreach (explode("\n", $dok) as $b) {
        if (preg_match('/^##\s+(.+?)\s+—\s+\d+\s+page/u', $b, $h)) {
            $identiti = LABEL_KE_IDENTITI[trim($h[1])] ?? null;
            $panel = null;

            continue;
        }
        if (preg_match('/^Panel `(app|admin)`:$/', $b, $h)) {
            $panel = $h[1];

            continue;
        }
        if ($identiti && $panel && preg_match('/^\d+\.\s+`([^`]+)`$/', $b, $h)) {
            $perIdentitiPanel[$identiti][$panel][] = $h[1];
        }
    }

    expect($perIdentitiPanel)->not->toBeEmpty('tiada seksyen identiti dapat dihurai daripada dokumen');

    // ⚠️ Codex pusingan 2 (#9): versi terdahulu hanya mengaudit seksyen yang BERJAYA dihurai.
    // Counterexample: buang seluruh seksyen "Juruaudit" tetapi kekalkan baris ringkasannya —
    // parser kekal tidak kosong dan Juruaudit langsung TIDAK diaudit. Kini setiap pasangan
    // identiti×panel yang manifest jangkakan mesti HADIR sebagai seksyen dalam dokumen.
    $dijangkaSeksyen = [];
    foreach (array_values(LABEL_KE_IDENTITI) as $id) {
        foreach (['app', 'admin'] as $p) {
            $ada = collect($rr['entries'])->contains(fn ($e) => $e['identity'] === $id
                && $e['panel'] === $p && $e['expected_access'] === 'allow' && $e['in_navigation']);
            if ($ada) {
                $dijangkaSeksyen[] = "{$id}/{$p}";
            }
        }
    }
    $adaSeksyen = [];
    foreach ($perIdentitiPanel as $id => $ikutPanel) {
        foreach (array_keys($ikutPanel) as $p) {
            $adaSeksyen[] = "{$id}/{$p}";
        }
    }
    $seksyenHilang = array_values(array_diff($dijangkaSeksyen, $adaSeksyen));
    expect($seksyenHilang)->toBe([],
        'seksyen identiti/panel HILANG daripada dokumen (jadi tidak diaudit langsung): '
        .implode(', ', $seksyenHilang));

    $salah = [];
    foreach ($perIdentitiPanel as $id => $ikutPanel) {
        foreach ($ikutPanel as $p => $routes) {
            $dibenarkan = collect($rr['entries'])
                ->filter(fn ($e) => $e['identity'] === $id && $e['panel'] === $p
                    && $e['expected_access'] === 'allow' && $e['in_navigation'])
                ->pluck('route_template')->unique()->all();

            foreach (array_diff($routes, $dibenarkan) as $r) {
                $salah[] = "{$id}/{$p}: {$r} TIDAK dibenarkan";
            }
            foreach (array_diff($dibenarkan, $routes) as $r) {
                $salah[] = "{$id}/{$p}: {$r} HILANG daripada dokumen";
            }
        }
    }
    expect($salah)->toBe([], 'route tersalah letak atau hilang: '.implode(' · ', array_slice($salah, 0, 12)));
});

test('dokumen adalah keluaran penjana yang TEPAT (jana semula tidak mengubah apa-apa)', function () {
    // Penjaga terkuat: jalankan penjana ke fail sementara dan bandingkan bait.
    // Tanpa ini, seseorang boleh menyunting dokumen dengan tangan dan ujian di atas masih lulus
    // selagi nombornya kebetulan betul.
    $asal = base_path('docs/AKSES-PAGE-MENGIKUT-ROLE.md');
    $sebelum = file_get_contents($asal);

    $hasil = Process::path(base_path())->run('node scripts/audit/generate-role-access-doc.mjs');
    expect($hasil->successful())->toBeTrue('penjana gagal: '.$hasil->errorOutput());

    $selepas = file_get_contents($asal);
    // Pulihkan keadaan fail supaya ujian tidak meninggalkan kesan walaupun ia gagal.
    file_put_contents($asal, $sebelum);

    expect($selepas)->toBe($sebelum,
        'docs/AKSES-PAGE-MENGIKUT-ROLE.md tidak sepadan keluaran penjana — '
        .'ia disunting dengan tangan atau manifest berubah tanpa dijana semula');
});
