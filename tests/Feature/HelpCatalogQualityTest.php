<?php

use App\Services\HelpCatalog;

/**
 * Penjaga kualiti kandungan katalog panduan.
 *
 * Dicipta pada F3 (§4.7 #6) untuk satu semakan sahaja — ejaan butang wizard —
 * dan diperluas pada F5 (§6.5) dengan semakan kualiti tajuk, sasaran login,
 * sasaran muat naik, layout tetamu dan integriti registri sasaran.
 */
function helpCatalog(): array
{
    static $catalog = null;

    return $catalog ??= json_decode(
        (string) file_get_contents(resource_path('help/guides.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** Registri `data-help-target` (sumber kebenaran §7.2 — ujian membaca registri, bukan grep). */
function helpTargetRegistry(): array
{
    static $registry = null;

    return $registry ??= json_decode(
        (string) file_get_contents(resource_path('help/targets.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** Kohort audit produksi P11 = family `tenant` (25 guide / 124 langkah). */
function helpCohortGuideIds(): array
{
    return collect(helpCatalog()['guides'])
        ->filter(fn (array $g): bool => str_starts_with((string) $g['id'], 'tenant.'))
        ->pluck('id')
        ->all();
}

/** Padan `HelpCatalog::normalise()` — Str::lower(Str::ascii()) + runtuhkan ruang. */
function helpNormalise(string $value): string
{
    return trim((string) preg_replace('/\s+/', ' ', Str::lower(Str::ascii($value))));
}

/** Satu guide mengikut id. */
function helpGuide(string $id): array
{
    $guide = collect(helpCatalog()['guides'])->firstWhere('id', $id);

    expect($guide)->not->toBeNull("guide {$id} tiada dalam katalog");

    return $guide;
}

test('arahan katalog guna "Seterusnya", bukan ejaan pendek "Seterus"', function () {
    $padanan = [];

    foreach (helpCatalog()['guides'] as $guide) {
        foreach ($guide['steps'] as $i => $step) {
            foreach (['title', 'instruction', 'hint'] as $medan) {
                $teks = (string) ($step[$medan] ?? '');
                // \bSeterus\b(?!nya) — "Seterus" berdiri sendiri, tetapi bukan
                // awalan "Seterusnya" (yang betul).
                if (preg_match('/\bSeterus\b(?!nya)/u', $teks)) {
                    $padanan[] = $guide['id'].'#'.($i + 1)." ({$medan})";
                }
            }
        }
    }

    expect($padanan)->toBe([],
        'Katalog masih menyebut butang "Seterus" — label sebenar kini "Seterusnya" (F3 §4.4), '
        .'jadi arahan ini merujuk butang yang tidak wujud: '.implode(', ', $padanan));
});

test('arahan katalog tidak menyebut butang "Sebelum" (label kini "Sebelumnya")', function () {
    $padanan = [];

    foreach (helpCatalog()['guides'] as $guide) {
        foreach ($guide['steps'] as $i => $step) {
            foreach (['title', 'instruction', 'hint'] as $medan) {
                $teks = (string) ($step[$medan] ?? '');
                // Hanya bentuk arahan menekan butang — "sebelum" sebagai kata
                // hubung biasa ("sebelum menghantar") adalah sah dan kerap.
                if (preg_match('/\b[Tt]ekan\s+Sebelum\b(?!nya)/u', $teks)) {
                    $padanan[] = $guide['id'].'#'.($i + 1)." ({$medan})";
                }
            }
        }
    }

    expect($padanan)->toBe([], 'Katalog merujuk butang "Sebelum": '.implode(', ', $padanan));
});

// ─────────────────────────────────────────────────────────────────────────────────────────
// F5 (§6.5) — kualiti tajuk, sasaran spesifik, layout tetamu, integriti registri.
// ─────────────────────────────────────────────────────────────────────────────────────────

test('§6.5 #2 tajuk tidak menduplikasi arahan (77/124 duplikasi verbatim → 0)', function () {
    $pelanggar = [];

    foreach (helpCatalog()['guides'] as $guide) {
        foreach ($guide['steps'] as $i => $step) {
            $key = $guide['id'].'#'.($i + 1);
            $title = helpNormalise((string) ($step['title'] ?? ''));
            $instruction = helpNormalise((string) ($step['instruction'] ?? ''));

            if ($title === '' || $instruction === '') {
                continue;
            }
            if ($title === $instruction) {
                $pelanggar[] = "{$key} (sama)";

                continue;
            }
            // Toleransi: tajuk boleh menjadi prefix arahan selagi ia ≤60% panjangnya —
            // prefix panjang bermakna popover memaparkan ayat sama dua kali.
            if (str_starts_with($instruction, $title) && mb_strlen($title) > 0.6 * mb_strlen($instruction)) {
                $pelanggar[] = "{$key} (prefix ".round(100 * mb_strlen($title) / mb_strlen($instruction)).'%)';
            }
        }
    }

    expect($pelanggar)->toBe([], 'Tajuk menduplikasi arahan: '.implode(', ', $pelanggar));
});

test('§6.5 #3 kohort 25 guide/124 langkah: tajuk eksplisit, tiada elipsis', function () {
    $cohort = helpCohortGuideIds();
    expect($cohort)->toHaveCount(25);

    $placeholder = [];
    $elipsis = [];
    $langkah = 0;

    foreach (helpCatalog()['guides'] as $guide) {
        if (! in_array($guide['id'], $cohort, true)) {
            continue;
        }
        foreach ($guide['steps'] as $i => $step) {
            $langkah++;
            $key = $guide['id'].'#'.($i + 1);
            $title = (string) ($step['title'] ?? '');

            // Placeholder `Langkah N` → dihidratkan runtime daripada klausa pertama arahan,
            // iaitu punca 77/124 tajuk duplikasi dan 20/124 tajuk terpotong (RR-01-10).
            if (preg_match('/^Langkah\s+\d+$/i', $title)) {
                $placeholder[] = $key;
            }
            if (str_contains($title, '...') || str_contains($title, '…')) {
                $elipsis[] = $key;
            }
        }
    }

    expect($langkah)->toBe(124)
        ->and($placeholder)->toBe([], 'Kohort masih placeholder: '.implode(', ', $placeholder))
        ->and($elipsis)->toBe([], 'Tajuk kohort mengandungi elipsis: '.implode(', ', $elipsis));
});

test('§6.5 #3 fallback meaningfulStepTitle memotong pada sempadan perkataan', function (string $panjang) {
    // Fungsi protected — diuji terus supaya kontrak potongan yang diassert, bukan kesan
    // sampingan. `preserveWords: true` menutup RR-10-04 (20/124 tajuk terpotong tengah).
    //
    // ⚠️ Fixture MESTI melintasi had 72 aksara DI DALAM satu perkataan. Percubaan pertama
    // saya jatuh tepat pada ruang pada aksara 73, jadi potongan naif dan `preserveWords`
    // memberi hasil IDENTIK dan ujian lulus walaupun `preserveWords` dibuang (regresi R7).
    // Assert `!== naif` di bawah menjadikan kelemahan itu mustahil berulang.
    $title = (new ReflectionClass(HelpCatalog::class))->getMethod('meaningfulStepTitle');
    $title->setAccessible(true);
    $hasil = $title->invoke(app(HelpCatalog::class), $panjang);

    expect(mb_strlen($hasil))->toBeLessThanOrEqual(73) // 72 + 1 aksara elipsis
        ->and($hasil)->toEndWith('…');

    // Sempadan perkataan: buang elipsis; potongan mesti prefix teks asal, dan aksara
    // seterusnya dalam teks asal mesti ruang (bukan pertengahan perkataan).
    $tanpaElipsis = rtrim(mb_substr($hasil, 0, mb_strlen($hasil) - 1));
    expect(str_starts_with($panjang, $tanpaElipsis))->toBeTrue("potongan bukan prefix: {$tanpaElipsis}");
    expect(mb_substr($panjang, mb_strlen($tanpaElipsis), 1))->toBe(' ',
        "elipsis memotong pertengahan perkataan: …{$tanpaElipsis}|");

    // Penjaga anti-fixture-lemah: potongan naif MESTI berbeza, jika tidak fixture ini tidak
    // menguji apa-apa. `rtrim` meniru cabang !preserveWords dalam Str::limit.
    $naif = rtrim(mb_strimwidth($panjang, 0, 72, '', 'UTF-8')).'…';
    expect($hasil)->not->toBe($naif,
        'fixture tidak melintasi had di pertengahan perkataan — ujian ini tidak boleh gagal');
})->with([
    // Aksara 62–76 = "penyelenggaraan": had 72 mendarat di dalamnya.
    'perkataan panjang melintasi had' => ['Semak semula tanggungjawab pegawai bertanggungjawab terhadap penyelenggaraan sistem pemfailan masjid'],
    // Diakritik + perkataan panjang, memastikan pengiraan mb_* bukan bait.
    'berdiakritik' => ['Pastikan pegawai bertanggungjawab menyemak keseluruhan dokumen pengesahan kebenaran sebelum menghantar'],
]);

test('§6.5 #4 public.login menyasar medan dan butang sebenar', function () {
    $guide = helpGuide('public.login');
    $targets = collect($guide['steps'])->pluck('target')->all();

    expect($targets)->toBe(['login-identity', 'login-submit'])
        ->and($guide['version'])->toBeGreaterThanOrEqual(2)
        // Langkah 2 ialah tindakan sebenar (hantar pautan) — `wait_for_user` mesti true
        // supaya `stepAdvancePlan` memberi kind `final-action` dan tour tamat apabila
        // borang benar-benar dihantar, bukan apabila pengguna menekan "Selesai".
        ->and($guide['steps'][1]['wait_for_user'])->toBeTrue();
});

test('§6.5 #4 muat naik: 3 sasaran berbeza, tiada dua langkah berturut sama', function () {
    $guide = helpGuide('screen.muat-naik-dokumen');
    $targets = collect($guide['steps'])->pluck('target')->all();

    expect(array_slice($targets, 0, 3))
        ->toBe(['inbox-upload', 'inbox-upload-dropzone', 'inbox-upload-submit'])
        ->and($guide['version'])->toBeGreaterThanOrEqual(2);

    // C12: dua langkah berturut-turut yang berkongsi sasaran menyorot objek yang sama dan
    // sama besar sambil arahan bertukar — kecacatan yang sama seperti sorotan generik.
    foreach ($targets as $i => $target) {
        if ($i === 0) {
            continue;
        }
        expect($target)->not->toBe($targets[$i - 1],
            'langkah '.($i + 1)." berkongsi sasaran '{$target}' dengan langkah {$i}");
    }
});

test('§6.5 #7 tenant.dashboard menyasar navigasi, bukan page-content', function () {
    $steps = helpGuide('tenant.dashboard')['steps'];

    // Langkah 1 ("sahkan tenant") dan 4 ("buka tugasan dari menu") kedua-duanya merujuk
    // menu navigasi. `nav-primary` ialah sasaran LOGIK — diselesaikan runtime kepada
    // `nav-sidebar` (desktop) atau `nav-menu-toggle` (mobile) oleh nav-target-plan.js.
    expect($steps[0]['target'])->toBe('nav-primary')
        ->and($steps[3]['target'])->toBe('nav-primary');

    // Arahan mesti neutral-peranti: menyebut sidebar/"menu kiri" sahaja mengelirukan
    // pengguna telefon, yang perlu menekan ☰ dahulu.
    foreach ([$steps[0], $steps[3]] as $step) {
        expect($step['instruction'])->toContain('menu navigasi')
            ->and(helpNormalise($step['instruction']))->not->toContain('menu kiri');
    }

    // Langkah 4 ialah langkah AKHIR: `wait_for_user: true` di sini akan memberi kind
    // `final-action`, dan `watchForActionCompletion` hanya tamat apabila sasaran HILANG —
    // membuka sidebar tidak menghilangkannya, jadi tour akan tergantung selama-lamanya.
    expect($steps[3]['wait_for_user'] ?? false)->toBeFalse();
});

test('§6.5 #8 setiap sasaran bukan-generik dalam katalog terdaftar (yatim = 0)', function () {
    // Sasaran yang ditandakan oleh help.js `decorateTargets()` bukan entri registri —
    // ia tiada pemilik dalam HTML pelayan. `nav-primary` ialah sasaran LOGIK.
    $ditandakanJs = ['page-content', 'page-primary', 'sidebar', 'nav-primary'];
    $registri = collect(helpTargetRegistry()['targets'])->pluck('id')->all();

    $yatimKatalog = [];
    $dirujuk = [];

    foreach (helpCatalog()['guides'] as $guide) {
        foreach ($guide['steps'] as $i => $step) {
            $target = (string) ($step['target'] ?? '');
            if ($target === '' || in_array($target, $ditandakanJs, true)) {
                continue;
            }
            $dirujuk[$target] = true;
            if (! in_array($target, $registri, true)) {
                $yatimKatalog[] = $guide['id'].'#'.($i + 1)." → {$target}";
            }
        }
    }

    // Arah kedua: entri registri `active` mesti dirujuk sekurang-kurangnya satu guide.
    $yatimRegistri = [];
    foreach (helpTargetRegistry()['targets'] as $entry) {
        if (($entry['status'] ?? '') === 'active' && ! isset($dirujuk[$entry['id']])) {
            $yatimRegistri[] = $entry['id'];
        }
    }

    expect($yatimKatalog)->toBe([], 'Sasaran katalog tiada dlm registri: '.implode(', ', $yatimKatalog))
        ->and($yatimRegistri)->toBe([],
            'Entri registri `active` tidak dirujuk katalog (tandakan `reserved`): '.implode(', ', $yatimRegistri));
});

test('§6.5 #8 sasaran login benar-benar wujud dalam HTML /log-masuk yang dirender', function () {
    // Bukan grep sumber: halaman sebenar dirender dan atributnya diperiksa. Ini menutup
    // jurang "registri menyenaraikan sasaran yang tiada dalam DOM".
    $html = $this->get('/log-masuk')->assertOk()->getContent();

    foreach (['login-identity', 'login-submit'] as $target) {
        expect(substr_count($html, 'data-help-target="'.$target.'"'))->toBe(1,
            "sasaran {$target} tidak wujud (atau tidak unik) dalam /log-masuk");
    }
});

test('§6.5 #8 sasaran muat naik didaftarkan pada objek aksi Filament sebenar', function () {
    // Modal Filament 4 dirender PELANGGAN-SISI — HTML pelayan tidak pernah mengandungi
    // kandungan modal (pelajaran F3 §4.7). Jadi assert pada objek yang MENJANA atribut:
    // FileUpload dan modalSubmitAction, dibaca daripada skema aksi yang sebenar.
    $sumber = file_get_contents(app_path('Filament/App/Resources/Inbox/Pages/ListInbox.php'));

    expect($sumber)
        ->toContain("'data-help-target' => 'inbox-upload-dropzone'")
        ->toContain("'data-help-target' => 'inbox-upload-submit'")
        // Corak modalSubmitAction mesti membungkus aksi induk — menggantinya akan
        // memusnahkan penghantaran borang (pelajaran F4 §5.2).
        ->toContain('->modalSubmitAction(fn (Action $action): Action => $action');

    // Kontrak DOM: kedua-dua sasaran mesti diselesaikan oleh e2e muat naik
    // (e2e/guidance-upload.spec.js) — di sana modal benar-benar dibuka.
});

test('§6.5 #6 layout tetamu: tepat satu <main>, jenama & nav di LUAR <main>', function () {
    // Enam halaman menggunakan components.guest-layout: 3 view statik + 3 komponen Livewire
    // penuh-halaman (`->layout('components.guest-layout')`).
    $laluan = ['/', '/log-masuk', '/daftar', '/bantuan'];

    foreach ($laluan as $path) {
        $html = $this->get($path)->assertOk()->getContent();
        $label = "halaman {$path}";

        // NOTA: `toContain()` mengambil needle VARIADIK, bukan mesej kegagalan (pelajaran F3) —
        // jadi semakan kandungan di sini guna str_contains + toBeTrue supaya mesej kekal mesej.
        expect(substr_count($html, '<main'))->toBe(1, "{$label}: bilangan <main> bukan 1")
            ->and(substr_count($html, '<header'))->toBe(1, "{$label}: bilangan <header> bukan 1")
            ->and(str_contains($html, '<main data-help-target="page-content">'))
            ->toBeTrue("{$label}: <main> tiada sasaran page-content");

        // <h1> jenama dan nav mesti berada DI LUAR <main> — jika tidak, `page-content`
        // akan menyorot seluruh halaman termasuk navigasi (masalah sorotan-terlalu-besar).
        //
        // Semakan mesti memeriksa KANDUNGAN <main>, bukan kedudukan kemunculan PERTAMA:
        // `strpos('<h1>') < strpos('<main')` sentiasa benar kerana <h1> jenama datang dahulu,
        // jadi <h1> KEDUA di dalam <main> terlepas. (Dibuktikan: regresi R2 lulus penjaga
        // versi pertama.) Jadi kandungan <main> diekstrak dan diperiksa terus.
        $mula = strpos($html, '<main');
        $tamat = strpos($html, '</main>');
        expect($mula)->not->toBeFalse("{$label}: tiada <main>")
            ->and($tamat)->not->toBeFalse("{$label}: tiada </main>")
            ->and($tamat)->toBeGreaterThan($mula, "{$label}: </main> sebelum <main>");
        $dalamMain = substr($html, $mula, $tamat - $mula);

        expect(str_contains($dalamMain, '<h1'))->toBeFalse("{$label}: <h1> jenama berada DALAM <main>");
        expect(str_contains($dalamMain, 'brand-actions'))->toBeFalse("{$label}: nav jenama berada DALAM <main>");
        expect(str_contains($dalamMain, 'diwan-help-launcher'))->toBeFalse("{$label}: pelancar bantuan DALAM <main>");
    }
});

test('§6.5 #5 catalog_version dibumbung bila kandungan katalog berubah', function () {
    // Indeks carian bantuan disegerakkan mengikut versi; kandungan berubah tanpa bump
    // bermakna `diwan:sync-help-index` boleh melangkau perubahan.
    expect(helpCatalog()['catalog_version'])->toBeGreaterThanOrEqual('2026.08.04.1');
});
