<?php

use App\Services\SearchService;
use Illuminate\Support\Facades\Storage;

/*
 * §13′ — petikan (snippet) + highlight untuk hasil carian, dengan escape selamat (tiada XSS).
 */

beforeEach(function () {
    config()->set('scout.driver', 'collection');
    Storage::fake(config('diwan.storage_disk'));
    $this->svc = app(SearchService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->rec = makeRecord(
        $this->mam,
        makeFile($this->mam, makeNode($this->mam, '100-4', 'dalaman'), 'dalaman'),
        'dalaman',
        'surat_menyurat',
        [
            'title' => 'Permohonan Dewan',
            'ocr_text' => str_repeat('Perenggan pembuka yang panjang untuk menolak padanan ke tengah teks. ', 5)
                .'Saya memohon untuk menggunakan DEWAN SERBAGUNA bagi majlis perkahwinan. '
                .str_repeat('Ayat penutup tambahan yang panjang selepas padanan supaya ada ekor. ', 5),
        ],
    );
});

it('snippetFor memulangkan petikan konteks sekitar padanan dalam ocr_text', function () {
    $snippet = $this->svc->snippetFor($this->rec, 'DEWAN SERBAGUNA');

    expect($snippet)->toContain('DEWAN SERBAGUNA')
        ->and($snippet)->toContain('…'); // dipotong pada kedua-dua hujung
});

it('snippetFor jatuh ke tajuk bila tiada dalam ocr_text', function () {
    $snippet = $this->svc->snippetFor($this->rec, 'Permohonan');

    expect($snippet)->toContain('Permohonan');
});

it('snippetFor null bila tiada padanan langsung', function () {
    expect($this->svc->snippetFor($this->rec, 'takwujudlangsung-xyz'))->toBeNull();
});

it('highlight membalut padanan dengan <mark> dan meng-escape HTML (tiada XSS)', function () {
    $html = SearchService::highlight('Nilai <script>alert(1)</script> DEWAN di sini', 'DEWAN');

    expect($html)->toContain('<mark')
        ->and($html)->toContain('DEWAN</mark>')
        ->and($html)->toContain('&lt;script&gt;')
        ->and($html)->not->toContain('<script>');
});

it('highlight null bila input null; escape sahaja bila query kosong', function () {
    expect(SearchService::highlight(null, 'x'))->toBeNull()
        ->and(SearchService::highlight('<b>hai</b>', ''))->toBe('&lt;b&gt;hai&lt;/b&gt;');
});
