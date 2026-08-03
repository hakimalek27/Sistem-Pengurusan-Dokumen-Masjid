<?php

/**
 * Penjaga kualiti kandungan katalog panduan.
 *
 * Dicipta pada F3 (§4.7 #6) untuk satu semakan sahaja — ejaan butang wizard —
 * dan SENGAJA dikongsi dengan F5 (§6), yang akan menambah semakan kualiti
 * katalog yang lain ke dalam fail yang sama.
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
