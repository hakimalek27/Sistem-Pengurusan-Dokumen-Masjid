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

test('kiraan panel app dalam dokumen sepadan expected_page_counts manifest', function () {
    $rr = manifestRoleRoutes();
    $dok = dokAksesRole();

    // Baris ringkasan: | Label | app | admin | beku | ✔ |
    preg_match_all('/^\|\s*([^|]+?)\s*\|\s*(\d+)\s*\|\s*(\d+)\s*\|\s*(\d+|—)\s*\|\s*(✔|✘|—)\s*\|$/mu', $dok, $m, PREG_SET_ORDER);
    expect(count($m))->toBeGreaterThanOrEqual(9, 'jadual ringkasan dokumen tidak dapat dihurai');

    $tidakSepadan = [];
    foreach ($m as $baris) {
        [, $label, $app, , $beku, $tanda] = $baris;
        if ($beku === '—') {
            continue;
        }
        if ((int) $app !== (int) $beku || $tanda !== '✔') {
            $tidakSepadan[] = trim($label)." app={$app} beku={$beku} tanda={$tanda}";
        }
    }

    expect($tidakSepadan)->toBe([],
        'kiraan dokumen menyimpang daripada manifest: '.implode(' · ', $tidakSepadan));

    // Dan setiap identiti dalam manifest mesti hadir dalam jadual itu — supaya identiti baharu
    // tidak boleh diperkenalkan tanpa dokumen dikemas.
    foreach (array_keys($rr['expected_page_counts']) as $identiti) {
        if ($identiti === 'public') {
            continue;
        }
        $bilangan = collect($rr['entries'])
            ->filter(fn ($e) => $e['identity'] === $identiti
                && $e['expected_access'] === 'allow'
                && $e['in_navigation']
                && $e['panel'] === 'app')
            ->count();
        expect($bilangan)->toBe($rr['expected_page_counts'][$identiti],
            "manifest tidak konsisten dengan sendiri untuk {$identiti}");
    }
});

test('setiap route yang disenaraikan dokumen benar-benar dibenarkan dalam manifest', function () {
    $rr = manifestRoleRoutes();
    $dok = dokAksesRole();

    $dibenarkan = collect($rr['entries'])
        ->filter(fn ($e) => $e['expected_access'] === 'allow' && $e['in_navigation'])
        ->pluck('route_template')
        ->unique()
        ->all();

    preg_match_all('/^\d+\.\s+`([^`]+)`$/m', $dok, $m);
    $disenarai = array_unique($m[1]);
    expect($disenarai)->not->toBeEmpty('dokumen tidak menyenaraikan sebarang route');

    $hantu = array_values(array_diff($disenarai, $dibenarkan));
    expect($hantu)->toBe([], 'dokumen menyenaraikan route yang manifest TIDAK benarkan: '.implode(', ', $hantu));
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
