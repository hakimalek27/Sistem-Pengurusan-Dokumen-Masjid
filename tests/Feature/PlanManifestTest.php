<?php

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * D11 #12 (PELAN-PEMBAIKAN.md §1 F0(ii-a)/(ii-b)/(iv)) — penjaga manifest baseline:
 * 1. Invarian partition beku (83/473/200/258/6) + keunikan kunci `<guide_id>#<index1>`.
 * 2. Setiap `e2e/*.spec.js` tersenarai dalam project Playwright ATAU allowlist bersebab.
 * 3. role_routes lapisan C — probe HTTP sebenar sebagai SETIAP identiti mesti sepadan
 *    `expected_status` (A daripada spec/roles.php; B `canAccess()` direkod dalam manifest;
 *    C dikuatkuasakan DI SINI pada setiap larian suite — bukan sekali semasa penjanaan).
 * 4. Kiraan halaman per identiti dikira DARIPADA array entri, bukan nombor ditaip (gate #1).
 */
function planManifest(): array
{
    static $manifest = null;

    return $manifest ??= json_decode((string) file_get_contents(
        base_path('Audit Review Round Robin/bukti/plan-baseline/manifest.json'),
    ), true, flags: JSON_THROW_ON_ERROR);
}

test('manifest baseline mematuhi invarian partition beku', function () {
    $m = planManifest();
    $guides = $m['catalogue'];
    expect($guides)->toHaveCount(83);

    $stepKeys = [];
    $totals = ['steps' => 0, 'actionGeneric' => 0, 'placeholder' => 0, 'mobile' => 0];
    $waveGuides = [];
    $waveSteps = [];
    foreach ($guides as $guide) {
        $waveGuides[$guide['wave']] = ($waveGuides[$guide['wave']] ?? 0) + 1;
        expect($guide['shard'])->toBeIn(['screen', 'workflow', 'tenant-admin-public']);
        foreach ($guide['steps'] as $step) {
            $totals['steps']++;
            $waveSteps[$step['wave']] = ($waveSteps[$step['wave']] ?? 0) + 1;
            expect($step['key'])->toBe($guide['guide_id'].'#'.$step['index']);
            expect(isset($stepKeys[$step['key']]))->toBeFalse("kunci berganda: {$step['key']}");
            $stepKeys[$step['key']] = true;
            expect($step['status'])->toBeIn(['specific', 'generic-justified', 'not-applicable', 'risk-accepted', 'blocked']);
            // `blocked` = release blocker (P14-06): manifest baseline TIDAK boleh mengandunginya.
            expect($step['status'])->not->toBe('blocked', "langkah blocked dalam baseline: {$step['key']}");
            if ($step['status'] === 'risk-accepted') {
                foreach (['reason', 'impact', 'fallback', 'ticket', 'owner', 'expires'] as $field) {
                    expect($step[$field] ?? null)->not->toBeEmpty("risk-accepted {$step['key']} tiada {$field}");
                }
            }
            if ($step['wait_for_user'] && $step['generic_declared']) {
                $totals['actionGeneric']++;
            }
            if ($step['title_placeholder']) {
                $totals['placeholder']++;
            }
            if ($step['mobile_defect'] ?? false) {
                $totals['mobile']++;
            }
        }
    }

    // Wave yang KOSONG ialah keadaan sah sejak F6-W1 (semua kerjanya selesai), jadi setiap
    // kunci mesti wujud dengan nilai 0 — jika tidak, "wave siap" kelihatan seperti "wave
    // hilang" dan perbandingan struktur gagal atas sebab yang salah.
    $waveGuides += array_fill_keys(['W0', 'W1', 'W2', 'W3', 'W4', 'W5', 'W6'], 0);
    $waveSteps += array_fill_keys(['W0', 'W1', 'W2', 'W3', 'W4', 'W5', 'W6'], 0);
    ksort($waveGuides);

    // STRUKTUR (skop kerja) mesti tepat; METRIK KEMAJUAN mesti ≤ baseline F0 — pelan §7
    // menjangka 200→0, 258→0, mobile 6→0, jadi mengassert kesamaan akan menolak setiap
    // pembaikan F6. Naik = regresi dan tetap gagal. Selaras dgn build-manifest.mjs +
    // scripts/audit/validate-plan-manifest.mjs (tiga penjaga, satu peraturan).
    expect($totals['actionGeneric'])->toBeLessThanOrEqual(200)
        ->and($totals['placeholder'])->toBeLessThanOrEqual(258)
        ->and($totals['mobile'])->toBeLessThanOrEqual(6);

    // Denominator wave dikemas F5 (4 Ogos 2026): `screen.muat-naik-dokumen` (5 langkah)
    // mendapat sasaran spesifik §6.2 → tiada lagi tindakan generik → `waveOf()` W1 → W3.
    // Jumlah 83/473 dan shard `screen` 29/151 TIDAK berubah. Sebab penuh + 2 denominator
    // lain: `Audit Review Round Robin/bukti/plan-baseline/tools/build-manifest.mjs`.
    // DIKEMAS SEMULA F6-W1 (4 Ogos 2026): kesemua 27 guide `screen` yang berbaki mendapat
    // sasaran spesifik → `waveOf()` memindahkan SEMUANYA W1 → W3. W1 = 0/0 bermakna senarai
    // kerja wave itu KOSONG, iaitu definisi wave itu siap; W3 kini seluruh shard `screen`.
    // DIKEMAS SEMULA F6-W2 (5 Ogos 2026): kesemua 13 guide `workflow` yang berbaki mendapat
    // sasaran spesifik → `waveOf()` memindahkan SEMUANYA W2 → W4. W2 = 0/0 (wave siap) dan
    // W4 = seluruh shard `workflow` 14/158. `wait_for_user` global 190 → 172 (nota (5)).
    expect($totals['steps'])->toBe(473)
        ->and($waveGuides)->toBe(['W0' => 2, 'W1' => 0, 'W2' => 0, 'W3' => 29, 'W4' => 14, 'W5' => 35, 'W6' => 3])
        ->and(array_sum($waveSteps))->toBe(473)
        ->and($waveSteps['W0'])->toBe(10)->and($waveSteps['W1'])->toBe(0)
        ->and($waveSteps['W2'])->toBe(0)->and($waveSteps['W3'])->toBe(151)
        ->and($waveSteps['W4'])->toBe(158)->and($waveSteps['W5'])->toBe(146)->and($waveSteps['W6'])->toBe(8);
});

test('manifest sepadan katalog semasa (guide & langkah — set penuh, bukan kiraan)', function () {
    $m = planManifest();
    $catalog = json_decode((string) file_get_contents(resource_path('help/guides.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($m['catalog_version'])->toBe($catalog['catalog_version'],
        'catalog_version manifest lapuk — jana semula manifest (tools/build-manifest.mjs) selepas katalog berubah');

    $catalogKeys = collect($catalog['guides'])
        ->flatMap(fn (array $g) => collect($g['steps'])->keys()->map(fn (int $i) => $g['id'].'#'.($i + 1)))
        ->sort()->values()->all();
    $manifestKeys = collect($m['catalogue'])
        ->flatMap(fn (array $g) => collect($g['steps'])->pluck('key'))
        ->sort()->values()->all();

    expect($manifestKeys)->toBe($catalogKeys, 'set kunci langkah manifest ≠ katalog — jana semula manifest');
});

/**
 * F6-W3 — allowlist justifikasi per-langkah (§7.2 gate registri (f), §7.3 G5).
 *
 * Sebelum W3, SETIAP langkah generik menerima sebab yang dijana automatik ("penambahbaikan
 * dijadualkan Wn"). Sebaik wave itu ditutup, ayat itu bercanggah dengan dirinya sendiri dan
 * tiada penjaga dapat membezakan "dijustifikasikan" daripada "belum dibuat". Ujian ini
 * mengunci perbezaan itu daripada sisi PHP juga, supaya ia dijalankan pada SETIAP larian
 * suite dan bukan hanya semasa manifest dijana semula.
 */
test('setiap langkah generik dalam wave TERTUTUP membawa justifikasi eksplisit bertarikh', function () {
    $m = planManifest();
    $tertutup = $m['invariants']['justified_waves'] ?? null;
    expect($tertutup)->toBeArray('manifest tiada `justified_waves` — jana semula manifest')
        ->and($tertutup)->toBe(['W0', 'W1', 'W2', 'W3', 'W4', 'W5', 'W6']);

    $allow = json_decode((string) file_get_contents(resource_path('help/step-justifications.json')), true, flags: JSON_THROW_ON_ERROR);
    $kunciAllow = collect($allow['justifications'])->pluck('key');
    expect($kunciAllow->duplicates())->toBeEmpty('allowlist mengandungi kunci berganda');

    $perlu = [];
    foreach ($m['catalogue'] as $guide) {
        foreach ($guide['steps'] as $step) {
            if (! $step['generic_declared'] || ! in_array($step['wave'], $tertutup, true)) {
                continue;
            }
            $perlu[] = $step['key'];

            // Status aras-guide tidak diterima: setiap satu mesti dinamakan sendiri.
            expect($step['status'])->toBeIn(['generic-justified', 'not-applicable'],
                "{$step['key']}: langkah generik dalam wave tertutup mesti dijustifikasikan");
            expect($step['reason'] ?? '')->not->toStartWith('Baseline pra-F6',
                "{$step['key']}: masih membawa sebab BASELINE automatik, bukan justifikasi sebenar");
            expect(strlen((string) ($step['reason'] ?? '')))->toBeGreaterThanOrEqual(40,
                "{$step['key']}: sebab terlalu pendek untuk bermakna");
            expect($step['since'] ?? '')->toMatch('/^\d{4}-\d{2}-\d{2}$/',
                "{$step['key']}: `since` mesti tarikh YYYY-MM-DD");
        }
    }

    // Dua arah — allowlist tidak boleh mengumpul entri yatim/basi secara senyap.
    sort($perlu);
    $kunci = $kunciAllow->sort()->values()->all();
    expect($kunci)->toBe($perlu, 'set kunci allowlist ≠ set langkah generik dalam wave tertutup');

    // Langkah `wait_for_user` DILARANG `generic-justified` (§7.3) — ia mesti `risk-accepted`.
    foreach ($m['catalogue'] as $guide) {
        foreach ($guide['steps'] as $step) {
            if ($step['status'] === 'generic-justified' && $step['wait_for_user']) {
                throw new RuntimeException("{$step['key']}: langkah tindakan tidak boleh `generic-justified` (§7.3)");
            }
        }
    }
})->group('plan-manifest');

test('setiap spec e2e tersenarai dalam project CI atau allowlist bersebab', function () {
    // Allowlist "sengaja di luar CI" — mesti ada sebab bertulis + tarikh semakan semula (F0(iv)).
    $allowlist = [
        'production-readonly.spec.js' => 'Ditujukan kepada PRODUKSI sahaja (E2E_PRODUCTION gate) — dijalankan manual, bukan CI. Semak semula: 2026-09-30 (F8)',
        // Mempunyai project `production-readonly` dalam playwright.config.js, tetapi project itu
        // BERSYARAT kepada E2E_PRODUCTION — jadi ia tidak wujud semasa larian CI, dan `$inProject`
        // (padanan teks pada config) tidak boleh membuktikan ia pernah dijalankan CI. Allowlist
        // kekal sebagai rekod yang jujur: CI memang tidak pernah menjalankannya.
        'production-guidance-readonly.spec.js' => 'Matriks produksi F8 — HANYA melalui wrapper scripts/audit/run-production-guidance-readonly.ps1 (§9.1a); project Playwright bersyarat E2E_PRODUCTION, jadi CI tidak pernah mengutipnya. Semak semula: 2026-09-30 (F8)',
    ];

    $config = (string) file_get_contents(base_path('playwright.config.js'));
    $specs = collect(glob(base_path('e2e/*.spec.js')))->map(fn (string $p) => basename($p));
    expect($specs)->not->toBeEmpty();

    foreach ($specs as $spec) {
        $inProject = str_contains($config, "e2e/{$spec}");
        $inAllowlist = array_key_exists($spec, $allowlist);
        expect($inProject || $inAllowlist)->toBeTrue(
            "Spec e2e/{$spec} tiada dalam mana-mana project playwright.config.js DAN tiada dalam allowlist bersebab — spec baharu tidak boleh wujud tanpa pernah dijalankan CI",
        );
        if ($inAllowlist) {
            expect($allowlist[$spec])->toContain('Semak semula:');
        }
    }
});

test('role_routes: kiraan halaman per identiti dikira daripada array + A==B direkod', function () {
    $rr = planManifest()['role_routes'];
    expect($rr['identities'])->toHaveCount(10)
        ->and($rr['mismatches'])->toBeEmpty('manifest mengandungi mismatch expected↔declared — jana semula & siasat');

    foreach ($rr['entries'] as $entry) {
        expect($entry['expected_access'])->toBe($entry['declared_access'],
            "A≠B: {$entry['identity']} {$entry['route_template']}");
    }

    foreach ($rr['expected_page_counts'] as $identity => $count) {
        $computed = collect($rr['entries'])
            ->filter(fn (array $e): bool => $e['identity'] === $identity && $e['panel'] === 'app'
                && $e['expected_access'] === 'allow' && $e['in_navigation'])
            ->count();
        expect($computed)->toBe($count, "expected_page_counts[{$identity}] tidak dikira daripada array");
    }
});

test('role_routes lapisan C: probe HTTP sebenar sepadan expected_status', function (string $identity) {
    $this->seed(DemoSeeder::class);
    $rr = planManifest()['role_routes'];

    $user = match ($identity) {
        'public' => null,
        'superadmin' => User::query()->where('email', 'superadmin@diwan.test')->firstOrFail(),
        default => User::query()->where('email', "{$identity}@demo.test")->firstOrFail(),
    };

    $entries = collect($rr['entries'])->filter(fn (array $e): bool => $e['identity'] === $identity);
    expect($entries)->not->toBeEmpty();

    foreach ($entries as $entry) {
        if ($user) {
            $this->actingAs($user);
        }
        $response = $this->get($entry['url']);
        expect($response->getStatusCode())->toBe(
            $entry['expected_status'],
            "C≠A: {$identity} GET {$entry['url']} → {$response->getStatusCode()} (jangka {$entry['expected_status']}; peraturan: {$entry['expected_rule']})",
        );
        // Kebersihan antara request DALAM SATU proses ujian (produksi: setiap request segar):
        // (a) throttle route awam (cth /daftar 3/jam) — bersihkan kaunter limiter cache array;
        // (b) tenant Filament statik — request /app meninggalkan tenant; tanpa reset, render
        //     hook berskop Dashboard::class (kelas dikongsi dua panel) pada /admin cuba menjana
        //     route persediaan panel admin yang tidak wujud → 500 palsu;
        // (c) state komponen Livewire — flush rasmi (corak Octane).
        $this->app['cache']->clear();
        auth('web')->logout();
        Filament::setTenant(null);
        Livewire::flushState();
    }

    // §0.6 S1 — probe silang-tenant untuk role tenant sahaja (superadmin sah akses semua tenant).
    if (! in_array($identity, ['public', 'superadmin'], true)) {
        $this->actingAs($user);
        $cross = $this->get('/app/'.$rr['cross_tenant'].'/records');
        expect($cross->getStatusCode())->toBe(404, "S1 gagal: {$identity} silang-tenant → {$cross->getStatusCode()}");
    }
})->with(['public', 'superadmin', 'admin_masjid', 'pengerusi', 'setiausaha', 'bendahari', 'nazir', 'ketua_imam', 'ajk', 'audit']);

/**
 * F8 — penjaga yang lahir daripada kesilapan SEBENAR: satu commit tempatan (belum ditolak)
 * memasukkan `latihan-9.1/fixture-<uuid>.json`, iaitu fail kredensial yang ditulis oleh
 * `diwan:audit-fixture prepare` dan mengandungi kata laluan lapan akaun fixture. Ia dikeluarkan
 * daripada HEAD dan `bukti/.gitignore` dikemas — tetapi corak "artifak larian ditulis ke dalam
 * folder bukti, kemudian `git add -A`" akan berulang pada setiap latihan berikutnya.
 *
 * Ujian ini mengimbas apa yang BENAR-BENAR dijejak git (bukan cakera), supaya fail yang
 * diabaikan tidak memberi positif palsu dan fail baharu yang dikomit tidak boleh lolos.
 */
test('tiada kredensial dalam artifak bukti yang dijejak git', function () {
    exec('git ls-files "Audit Review Round Robin/bukti/*.json" "Audit Review Round Robin/bukti/**/*.json"', $fail, $kod);
    expect($kod)->toBe(0, 'git ls-files gagal — penjaga ini tidak boleh mengesahkan apa-apa');

    // Anti-vakum: jika glob rosak, senarai kosong akan LULUS secara senyap.
    expect(count($fail))->toBeGreaterThan(50, 'terlalu sedikit JSON bukti dijumpai — glob mungkin rosak');

    $pelanggaran = [];
    foreach ($fail as $laluan) {
        $isi = @file_get_contents(base_path($laluan));
        if ($isi !== false && preg_match('/"(password|secret|token)"\s*:\s*"/i', $isi)) {
            $pelanggaran[] = $laluan;
        }
    }

    // F8 — fail kredensial produksi tidak boleh dijejak di MANA-MANA, bukan hanya dalam bukti/.
    exec('git ls-files', $semua, $kodSemua);
    expect($kodSemua)->toBe(0, 'git ls-files gagal');
    $terlarang = array_values(array_filter($semua, fn (string $f) => str_contains($f, '.e2e-prod-credentials')));
    expect($terlarang)->toBeEmpty(
        'fail kredensial produksi DIJEJAK git: '.implode(', ', $terlarang)
        .' — keluarkan serta-merta (git rm --cached) dan tukar kata laluan itu',
    );

    expect($pelanggaran)->toBeEmpty(
        'artifak bukti mengandungi kunci kredensial: '.implode(', ', $pelanggaran)
        .' — keluarkan daripada git dan tambah corak ke Audit Review Round Robin/bukti/.gitignore',
    );
})->group('plan-manifest');

/**
 * F8 — penjaga untuk kecacatan yang akan menggagalkan larian PRODUKSI pada SETIAP role tenant.
 *
 * `production-guidance-readonly.spec.js` melawat `/app/mamad/records` dengan SENGAJA untuk
 * membuktikan isolasi tenant, dan mengassert 404. 404 itu menjana ralat console; ujian yang
 * SAMA kemudian mengassert SIFAR ralat console. Assertion itu tidak boleh dipenuhi oleh
 * pembinaannya sendiri — pada mana-mana mesin, termasuk produksi.
 *
 * CI tidak pernah menjalankan spec itu (project bersyarat `E2E_PRODUCTION`), jadi tiada penjaga
 * TINGKAH LAKU yang mungkin. Penjaga STRUKTUR ini ialah yang terbaik yang boleh dijalankan pada
 * setiap larian suite: ia menuntut ralat dipotong pada sempadan probe, dan bahawa apa-apa
 * selepas probe tetap DIREKAM (bukan dibuang senyap).
 */
test('spec produksi tidak boleh mengassert sifar ralat console selepas probe 404 yang disengajakan', function () {
    $spec = (string) file_get_contents(base_path('e2e/production-guidance-readonly.spec.js'));

    // Anti-vakum: jika fail dinamakan semula/dipindahkan, penjaga mesti gagal, bukan lulus kosong.
    // ⚠️ `toContain()` Pest ialah VARIADIK — hujah kedua ialah JARUM LAIN, bukan mesej. Saya
    // melanggar pelajaran ini buat kali KETIGA semasa menulis penjaga ini, dan ia memerahkan
    // ujian atas sebab yang salah. Gunakan `str_contains` + `toBeTrue` supaya mesej kekal mesej.
    expect(str_contains($spec, '/app/mamad/records'))->toBeTrue(
        'probe silang-tenant hilang daripada spec — penjaga ini tidak lagi menguji apa-apa',
    );

    expect(str_contains($spec, 'const ralatSebelumProbe = errors.length;'))->toBeTrue(
        'snapshot ralat SEBELUM probe silang-tenant hilang — tanpanya 404 yang disengajakan '
        .'dikira sebagai ralat console dan assertion menjadi mustahil dipenuhi',
    );

    expect(str_contains($spec, 'errors.slice(0, ralatSebelumProbe)'))->toBeTrue(
        'assertion ralat console tidak dipotong pada sempadan probe — setiap role tenant akan gagal',
    );

    // Dipotong, BUKAN dibuang: yang selepas probe mesti masih direkod untuk diperiksa.
    expect(str_contains($spec, 'ralat_selepas_probe'))->toBeTrue(
        'ralat selepas probe dibuang, bukan direkod — kehilangan bukti secara senyap',
    );
})->group('plan-manifest');

/**
 * F8 — penjaga untuk kecacatan yang MEMUTASI PRODUKSI lalu gagal sebelum satu ujian pun berjalan.
 *
 * Larian 11 Ogos 2026 23:13 mencipta tenant `smoke-<uuid>` + 8 akaun pada produksi, kemudian mati
 * pada `Get-Content` fail rahsianya sendiri (ACL `"$($env:USERNAME):(R)"` menghasilkan ACE yang
 * bukan milik pengguna ini; diukur: BACA ditolak). Cleanup gagal pula, jadi produksi ditinggalkan
 * kotor, kata laluan 8 akaun kekal dalam `%TEMP%` DAN dalam `/tmp` kontena produksi, dan punca
 * sebenar tidak kelihatan kerana ralat `finally` menggantikan pengecualian asal.
 *
 * Kecacatan kedua yang terpendam pada larian sama: `$psi.FileName = 'npx'` dengan
 * `UseShellExecute = $false` tidak boleh dilancarkan pada Windows (`npx` ialah `npx.cmd`;
 * CreateProcess tidak menyelesaikan PATHEXT).
 *
 * Tiada penjaga TINGKAH LAKU mungkin — menjalankan wrapper bermakna menyentuh produksi. Penjaga
 * STRUKTUR ini menuntut susunan yang menjadikan urutan itu mustahil: runner dibuktikan boleh
 * dilancarkan SEBELUM `prepare` memutasi apa-apa.
 */
test('wrapper produksi membuktikan runner boleh berjalan SEBELUM ia memutasi produksi', function () {
    $laluan = base_path('scripts/audit/run-production-guidance-readonly.ps1');
    $ps1 = (string) file_get_contents($laluan);

    // Anti-vakum: fail dinamakan semula/dikosongkan mesti MEMERAHKAN penjaga, bukan meluluskannya.
    expect(strlen($ps1))->toBeGreaterThan(4000, 'wrapper produksi hilang atau terpangkas');

    // ⚠️ `toContain()` Pest ialah VARIADIK (hujah kedua = jarum lain, bukan mesej) — guna
    // `str_contains` + `toBeTrue($mesej)` supaya mesej diagnostik kekal mesej.
    expect(str_contains($ps1, 'Get-Command npx'))->toBeTrue(
        'wrapper tidak lagi menyelesaikan npx melalui Get-Command — `npx` telanjang tidak boleh '
        .'dilancarkan dengan UseShellExecute=$false pada Windows (npx ialah npx.cmd)',
    );

    // Diperiksa pada baris KOD sahaja: komen dalam wrapper mendokumenkan bentuk lama secara
    // verbatim (itulah gunanya), dan padanan teks buta ke atas seluruh fail memerahkan penjaga
    // ini kerana DOKUMENTASI — bukan kerana regresi. Diukur: versi pertama penjaga ini gagal
    // tepat begitu.
    $baris = preg_split('/\R/', $ps1) ?: [];
    $npxTelanjang = array_values(array_filter($baris, function (string $b): bool {
        $bersih = ltrim($b);

        return ! str_starts_with($bersih, '#')
            && preg_match('/\$psi\.FileName\s*=\s*[\'"]npx[\'"]/', $bersih) === 1;
    }));
    expect($npxTelanjang)->toBeEmpty(
        'wrapper kembali kepada `FileName = \'npx\'` telanjang dalam KOD — larian akan mati '
        .'SELEPAS fixture dicipta pada produksi: '.implode(' | ', $npxTelanjang),
    );

    // ⭐ Susunan ialah keseluruhan penjaga: pra-terbang mesti mendahului mutasi PERTAMA.
    $praTerbang = strpos($ps1, '--project=production-readonly --list');
    $prepare = strpos($ps1, 'diwan:audit-fixture prepare');
    expect($praTerbang)->not->toBeFalse('pra-terbang `--list` hilang daripada wrapper');
    expect($prepare)->not->toBeFalse('langkah `prepare` hilang daripada wrapper');
    expect($praTerbang)->toBeLessThan(
        $prepare,
        'pra-terbang runner berada SELEPAS `audit-fixture prepare` — fixture akan dicipta pada '
        .'produksi sebelum runner terbukti boleh dilancarkan (kegagalan 11 Ogos 2026)',
    );

    // Pra-terbang yang mengutip SIFAR ujian tidak membuktikan apa-apa; kiraan mesti diassert.
    expect(str_contains($ps1, '-lt 22'))->toBeTrue(
        'pra-terbang tidak lagi mengassert bilangan ujian dikutip (>= 22 = 2 kontrak + 20 konteks) '
        .'— `--list` yang mengutip sifar ujian boleh keluar 0 dan lulus secara vakum',
    );

    // ACL: prinsipal berkelayakan + (F). Bentuk lama menafikan BACA kepada skrip itu sendiri.
    expect(str_contains($ps1, '$($env:USERDOMAIN)\$($env:USERNAME):(F)'))->toBeTrue(
        'ACL fail rahsia tidak lagi menggunakan prinsipal berkelayakan dengan (F) — bentuk '
        .'`$($env:USERNAME):(R)` menghasilkan ACE yang menafikan BACA dan PADAM kepada wrapper',
    );

    // Punca mesti direkod sebelum `finally` memusnahkannya.
    expect(str_contains($ps1, 'RALAT ASAL:'))->toBeTrue(
        'wrapper tidak lagi merekod pengecualian asal sebelum `finally` — ralat cleanup akan '
        .'menggantikan punca sebenar (itu yang menyembunyikan kegagalan 11 Ogos)',
    );
})->group('plan-manifest');

/**
 * F8 — artifak bukti DIJEJAK oleh git, dan `diwan:audit-fixture inventory` memaparkan
 * `superadmin.emails`: alamat e-mel PERIBADI pemilik. Imbasan sebelum commit pertama larian
 * produksi menemuinya dalam LAPAN fail. Penjaga kredensial sedia ada hanya mencari KUNCI
 * seperti `password`/`secret`, jadi ia tidak menangkapnya.
 *
 * Akaun fixture (`@smoke.test`) dibenarkan dengan sengaja: ia bukan rahsia, ia dipadam oleh
 * cleanup, dan kehadirannya ialah bukti bahawa larian itu benar-benar berlaku.
 */
test('artifak bukti tidak mengandungi alamat e-mel sebenar', function () {
    $dijejak = [];
    exec('git ls-files "Audit Review Round Robin/bukti"', $dijejak);
    $dijejak = array_values(array_filter($dijejak));

    // Anti-vakum: glob/`git ls-files` yang rosak memberi senarai kosong, dan senarai kosong
    // meluluskan penjaga ini tanpa memeriksa apa-apa.
    expect(count($dijejak))->toBeGreaterThan(50, 'senarai fail bukti terlalu kecil — penjaga tidak menguji apa-apa');

    // TLD `.test` dikhaskan untuk ujian oleh RFC 6761 — ia tidak boleh menjadi alamat sebenar.
    $dibenarkan = '/@([A-Za-z0-9.-]+\.test|(example|invalid)\.[A-Za-z]{2,})$/i';

    // ⚠️ Tiga fail SUDAH mengandungi alamat sebenar sebelum penjaga ini ditulis (e-mel pengarang
    // commit dalam dump JSON larian CI, dan akaun penghantar SMTP dalam bukti Deploy 3). Ia
    // sudah berada dalam sejarah git, jadi memadamnya daripada pokok kerja TIDAK mengeluarkannya
    // daripada repo — perlindungan palsu. Dikecualikan mengikut FAIL dan bukan mengikut alamat,
    // supaya penjaga ini tidak menambah SALINAN BAHARU alamat itu ke dalam repo (versi pertama
    // saya berbuat demikian, iaitu tepat perkara yang ia sepatutnya halang).
    // 📌 Keputusan pemilik: sama ada hendak menulis semula sejarah, atau menerimanya.
    // Fail BAHARU tetap ditolak sepenuhnya — itulah gunanya penjaga ini.
    $failDikecualikan = [
        'Audit Review Round Robin/bukti/deploy-3/BUKTI-DEPLOY-3.md',
        'Audit Review Round Robin/bukti/plan-f8/bukti-larian/ci-a11y.json',
        'Audit Review Round Robin/bukti/plan-f8/bukti-larian/ci-domain.json',
    ];
    $pelanggaran = [];

    foreach ($dijejak as $laluan) {
        $penuh = base_path($laluan);
        if (! is_file($penuh) || in_array($laluan, $failDikecualikan, true)) {
            continue;
        }
        $isi = (string) file_get_contents($penuh);
        preg_match_all('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', $isi, $padanan);
        foreach (array_unique($padanan[0]) as $emel) {
            if (preg_match($dibenarkan, $emel) === 1) {
                continue;
            }
            $pelanggaran[] = "$laluan → $emel";
        }
    }

    expect($pelanggaran)->toBeEmpty(
        'artifak bukti mengandungi alamat e-mel sebenar (redaksi pada titik penulisan — lihat '
        ."fungsi `Redaksi` dalam run-production-guidance-readonly.ps1):\n  ".implode("\n  ", array_slice($pelanggaran, 0, 20)),
    );
})->group('plan-manifest');
