<?php

/**
 * F7 §8.1 (RR-04-01, axe `link-name` serious) — kolum "Duplikat" Peti Masuk.
 *
 * Puncanya BUKAN teks kosong tetapi kewujudan `<a>`: Filament membungkus setiap sel dengan
 * `<a href=recordUrl>`, jadi state `''` menghasilkan pautan tanpa nama boleh-akses.
 * `disabledClick()` menjadikan sel `<div>`, jadi `link-name` mustahil gagal pada kolum itu
 * tanpa bergantung pada teks pengganti.
 *
 * Pelan §8.1 menuntut LIMA fakta; (iv) larian axe sebenar berada dalam e2e a11y kerana axe
 * memerlukan DOM hidup. Empat yang lain dikunci di sini, pada KEDUA-DUA keadaan data —
 * dengan baris duplikat dan tanpa — kerana P2/P6 merekod bahawa data benih berubah selepas
 * ujian menulis, jadi kedua-dua keadaan mesti disediakan secara eksplisit.
 */

use App\Enums\SourceChannel;
use App\Filament\App\Resources\Inbox\Pages\ListInbox;
use App\Services\InboxIngestService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->svc = app(InboxIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');

    // Halaman senarai berpenyewa memulangkan 403 tanpa panel + tenant semasa ditetapkan.
    // Urutan penting: panel -> tenant -> actingAs (gotcha F5 yang sudah direkod).
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->mam, isQuiet: true);
    $this->actingAs($this->admin);
});

afterEach(function () {
    Filament::setTenant(null, isQuiet: true);
});

/** Peti Masuk dengan SATU dokumen sahaja — tiada duplikat. */
function petiMasukTanpaDuplikat(object $t): void
{
    $t->svc->ingest($t->mam, 'kandungan-unik', 'tunggal.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
}

/** Peti Masuk dengan sepasang sha256 yang sama — baris kedua ditanda duplikat. */
function petiMasukDenganDuplikat(object $t): void
{
    $t->svc->ingest($t->mam, 'kandungan-sama', 'satu.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $t->svc->ingest($t->mam, 'kandungan-sama', 'dua.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
}

dataset('keadaan-peti-masuk', [
    'tanpa duplikat' => ['petiMasukTanpaDuplikat', 'Tiada duplikat'],
    'dengan duplikat' => ['petiMasukDenganDuplikat', 'Duplikat dikesan'],
]);

/**
 * Pulangkan elemen PEMBUNGKUS bagi sel kolum `duplikat` — anak elemen pertama `<td>`nya.
 *
 * ⚠️ Sauh STRUKTUR, bukan jarak bait. Versi pertama ujian ini memeriksa 400 aksara sebelum
 * teks state; ia LULUS walaupun selepas `disabledClick()` dibuang, kerana atribut tooltip
 * yang panjang menolak tag `<a>` pembungkus KELUAR daripada tetingkap itu. Assertion yang
 * tidak boleh gagal ialah assertion yang tiada. (Pelajaran F6-W3 #4, dilanggar semula di sini
 * dan direkod.)
 *
 * Struktur sebenar yang diukur pada Filament 4.11.8:
 *     <td wire:key="...column.duplikat"> <a href="...rekod"> <div class="fi-ta-text-item">
 * `disabledClick()` menukar `<a>` itu kepada `<div>` (index.blade.php:2233-2237).
 */
function pembungkusSelDuplikat(string $html): DOMElement
{
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();

    $xp = new DOMXPath($dom);
    $td = $xp->query('//td[contains(@*[name()="wire:key"], "column.duplikat")]')->item(0);
    expect($td)->not->toBeNull('sel kolum `duplikat` tidak dijumpai dalam HTML');

    foreach ($td->childNodes as $anak) {
        if ($anak instanceof DOMElement) {
            return $anak;
        }
    }

    throw new RuntimeException('sel `duplikat` tiada elemen pembungkus');
}

/**
 * (i) + (ii) — sel BUKAN `<a>`, dan setiap keadaan membawa teks yang boleh dibaca.
 */
test('sel Duplikat dirender sebagai div dengan teks bermakna, bukan pautan', function (string $benih, string $teks) {
    $benih($this);

    $html = $this->actingAs($this->admin)->get('/app/mam/peti-masuk')->assertOk()->getContent();

    $pembungkus = pembungkusSelDuplikat($html);

    // (i) pembungkus mesti `div`, dan secara eksplisit BUKAN `a`.
    expect(strtolower($pembungkus->tagName))->toBe('div',
        'sel Duplikat dibungkus <'.$pembungkus->tagName.'> — `disabledClick()` hilang atau tidak berkuat kuasa');

    // (ii) teks state boleh dibaca pembaca skrin dalam KEADAAN INI.
    // ⚠️ `toContain()` Pest bersifat VARIADIC — argumen kedua ialah NEEDLE tambahan, bukan
    // mesej kegagalan.
    expect($pembungkus->textContent)->toContain($teks);
})->with('keadaan-peti-masuk');

/**
 * ⚠️ PENJAGA ANTI-FIXTURE-LEMAH (pelajaran F5: 2 daripada 9 regresi sengaja saya LULUS).
 *
 * Ujian di atas hanya bermakna jika `pembungkusSelDuplikat()` benar-benar memulangkan
 * pembungkus sel dan bukan sesuatu yang lain. Ujian ini membuktikannya dengan menuntut
 * DUA fakta bebas: pembungkus itu mengandungi teks state, DAN kolum jiran (yang MASIH
 * sepatutnya `<a>`) dipulangkan sebagai `<a>` oleh helper yang sama bentuknya. Jika
 * helper sentiasa memulangkan `div`, fakta kedua akan gagal.
 */
test('helper mengesan pembungkus sebenar — kolum jiran masih dikesan sebagai pautan', function () {
    petiMasukDenganDuplikat($this);

    $html = $this->actingAs($this->admin)->get('/app/mam/peti-masuk')->assertOk()->getContent();

    expect(pembungkusSelDuplikat($html)->textContent)->toContain('Duplikat dikesan');

    // Kolum `ocr_status` tidak memanggil `disabledClick()`, jadi Filament masih
    // membungkusnya dengan `<a>`. Ini membuktikan ujian di atas mengukur perbezaan SEBENAR
    // dan bukan sekadar melaporkan `div` untuk setiap sel.
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    $xp = new DOMXPath($dom);
    $td = $xp->query('//td[contains(@*[name()="wire:key"], "column.ocr_status")]')->item(0);
    expect($td)->not->toBeNull('sel kolum `ocr_status` tidak dijumpai');

    $jiran = null;
    foreach ($td->childNodes as $anak) {
        if ($anak instanceof DOMElement) {
            $jiran = $anak;
            break;
        }
    }

    expect(strtolower($jiran->tagName))->toBe('a',
        'kolum jiran sepatutnya masih <a> — jika tidak, ujian "bukan <a>" tidak membuktikan apa-apa');
});

/** (iii) — susun dan tapis jadual tidak rosak selepas `disabledClick()`. */
test('susun dan tapis jadual kekal berfungsi selepas disabledClick', function () {
    petiMasukDenganDuplikat($this);

    Livewire::test(ListInbox::class)
        ->assertOk()
        ->sortTable('created_at')
        ->assertOk()
        ->sortTable('created_at', 'desc')
        ->assertOk()
        ->searchTable('satu.pdf')
        ->assertOk()
        ->assertSee('satu.pdf');
});

/** (v) — baris masih boleh dibuka: `ViewAction` kekal dirender. */
test('baris masih boleh dibuka melalui tindakan Lihat Dokumen selepas kolum jadi div', function () {
    petiMasukDenganDuplikat($this);

    $html = $this->actingAs($this->admin)->get('/app/mam/peti-masuk')->assertOk()->getContent();

    expect($html)->toContain('Lihat Dokumen / OCR');
});
