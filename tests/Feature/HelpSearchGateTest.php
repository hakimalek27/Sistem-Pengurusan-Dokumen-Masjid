<?php

/**
 * F8 §9.2 — gate carian bantuan: Meilisearch DAN fallback PHP (C20).
 *
 * Yang SUDAH diuji sebelum ini (`HelpCatalogTest`): tapisan role/panel/permission, istilah
 * biasa + singkatan + salah ejaan melalui fallback, pertanyaan mentah tidak disimpan, dan
 * primary key Meilisearch sah. Fail ini menutup baki §9.2 yang tiada penjaga:
 *
 *   (a) "Meili mati/timeout → carian masih berfungsi" — DIUKUR dengan host yang tidak boleh
 *       dihubungi, bukan dengan host yang KOSONG. Perbezaannya penting:
 *       `HelpSearchService.php:24` hanya menyemak `filled(host)`, jadi host kosong TIDAK
 *       pernah memasuki laluan Meili sama sekali — ujian sedia ada menguji "Meili tidak
 *       dikonfigurasi", bukan "Meili mati". Cabang `catch (Throwable)` (:37) tidak pernah
 *       dilalui oleh ujian sebelum ini.
 *   (b) tiada data tenant/pengguna dalam korpus yang diindeks;
 *   (c) awam tidak nampak guide tenant/admin;
 *   (d) dua tenant memberi hasil IDENTIK (katalog agnostik-tenant) — regresi isolasi RR-02-04;
 *   (e) akronim: yang ADA dalam korpus memberi hasil, yang TIADA memberi kosong.
 *
 * ⚠️ Penemuan yang (e) kunci, diukur pada Meilisearch PRODUKSI (8 Ogos 2026):
 *   OCR 10 · AJK 1 · QR 1 · ZIP 1 · SLA 1 · PDF 1  hits
 *   DDMS 0 · SPDM 0 · XYZQ 0 (kawalan)
 * §9.2 menuntut "query akronim (`DDMS`) memulangkan hasil". Ia TIDAK BOLEH lulus seperti
 * tertulis kerana `DDMS` muncul **0 kali** dalam katalog — itu soal perbendaharaan korpus,
 * bukan keupayaan enjin. Ujian (e) merekod kedua-dua fakta supaya tiada satu pun boleh
 * berubah secara senyap.
 */

use App\Models\HelpEvent;
use App\Models\Mosque;
use App\Services\HelpCatalog;
use App\Services\HelpSearchService;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    $this->adminMam = makeMember($this->mam, 'admin_masjid', 'admin-gate@mam.test');
    $this->adminMan = makeMember($this->man, 'admin_masjid', 'admin-gate@man.test');
});

it('(a) Meilisearch MATI (host tidak boleh dihubungi) — carian masih memulangkan hasil', function () {
    // Port 1 pada localhost: sentiasa ditolak, tiada rangkaian luar diperlukan (peraturan #5).
    config()->set('scout.meilisearch.host', 'http://127.0.0.1:1');
    config()->set('scout.meilisearch.key', 'kunci-tidak-sah');

    $hasil = app(HelpSearchService::class)->search('klasifikasi surat', 'app', $this->adminMam, $this->mam);

    expect($hasil)->not->toBeEmpty('fallback PHP tidak menyelamatkan carian apabila Meili mati');

    // Dan ia mesti direkod sebagai enjin `php`, bukan `meilisearch` — jika tidak, telemetri
    // akan mendakwa Meili sihat sedangkan ia mati (keluarga "sihat != tiada ralat").
    // ⚠️ `engine` bukan kolum: ia hidup dalam `metadata` (`HelpSearchService::recordSearch`).
    // Versi pertama ujian ini membaca `$peristiwa->engine` dan mendapat `null` — yang akan
    // LULUS jika saya menulis `not->toBe('meilisearch')` sebaliknya. Baca tempat sebenar.
    $peristiwa = HelpEvent::query()->latest('id')->first();
    expect(data_get($peristiwa->metadata, 'engine'))->toBe('php');
});

it('(b) korpus yang diindeks tidak mengandungi data tenant atau pengguna', function () {
    // Medan yang benar-benar diindeks (disahkan pada indeks PRODUKSI, 8 Ogos 2026):
    // document_id, guide_id, panel, roles, title, summary, keywords, steps_text,
    // troubleshooting_text. Tiada mosque_id, tiada user_id, tiada e-mel.
    $guides = app(HelpCatalog::class)->raw()['guides'];
    $korpus = collect($guides)->map(fn (array $g): string => json_encode([
        $g['title'] ?? '', $g['summary'] ?? '', $g['keywords'] ?? [],
        collect($g['steps'] ?? [])->map(fn ($s) => [$s['title'] ?? '', $s['instruction'] ?? ''])->all(),
        $g['troubleshooting'] ?? [],
    ], JSON_UNESCAPED_UNICODE))->implode("\n");

    // ⚠️ Versi pertama ujian ini memadan slug `mam`/`man` sebagai SUBSTRING dan gagal — bukan
    // kerana korpus bocor, tetapi kerana tiga aksara itu muncul dalam perkataan Melayu biasa
    // (`mampu`, `mana`, `manual`). Itu fixture lemah, bukan penemuan. Vektor kebocoran yang
    // SEBENAR diuji di bawah, dan setiap satu tidak boleh berlaku secara kebetulan.

    // (i) tiada alamat e-mel sama sekali.
    expect(preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $korpus))->toBe(0,
        'korpus bantuan mengandungi alamat e-mel');

    // (ii) tiada host/URL mutlak — panduan tidak sepatutnya memaku domain penyewa.
    expect(preg_match('#https?://#i', $korpus))->toBe(0, 'korpus bantuan mengandungi URL mutlak');

    // (iii) route guide mesti guna placeholder `{tenant}`, BUKAN slug sebenar. Ini vektor
    // kebocoran yang paling mungkin: satu route yang disalin daripada pelayar akan membawa
    // slug tenant sebenar ke dalam katalog awam.
    $slugSebenar = Mosque::query()->pluck('slug')->all();
    $routeBocor = [];
    foreach ($guides as $g) {
        foreach ([$g['route'] ?? null, ...collect($g['steps'] ?? [])->pluck('route')->all()] as $route) {
            if (! $route || ! str_starts_with((string) $route, '/app/')) {
                continue;
            }
            if (! str_contains((string) $route, '{tenant}')) {
                $routeBocor[] = $g['id'].' → '.$route;
            }
            foreach ($slugSebenar as $slug) {
                if (str_contains((string) $route, "/app/{$slug}")) {
                    $routeBocor[] = $g['id'].' → '.$route.' (slug sebenar)';
                }
            }
        }
    }
    expect(array_unique($routeBocor))->toBe([],
        'route katalog memaku slug tenant sebenar: '.implode(' · ', array_unique($routeBocor)));
});

it('(c) panel awam tidak pernah memulangkan guide tenant atau admin', function () {
    config()->set('scout.meilisearch.host', null);

    foreach (['klasifikasi surat', 'pelupusan', 'kelulusan', 'daftar masjid'] as $q) {
        $hasil = app(HelpSearchService::class)->search($q, 'public');
        $bukanAwam = $hasil->pluck('id')->reject(fn (string $id) => str_starts_with($id, 'public.'))->all();
        expect($bukanAwam)->toBe([], "carian awam \"{$q}\" membocorkan guide bukan-awam: ".implode(', ', $bukanAwam));
    }
});

it('(d) dua tenant memberi hasil IDENTIK — katalog agnostik-tenant (regresi isolasi)', function () {
    config()->set('scout.meilisearch.host', null);
    $q = 'klasifikasi surat';

    $a = app(HelpSearchService::class)->search($q, 'app', $this->adminMam, $this->mam)->pluck('id')->all();
    $b = app(HelpSearchService::class)->search($q, 'app', $this->adminMan, $this->man)->pluck('id')->all();

    expect($a)->not->toBeEmpty()->and($b)->toBe($a,
        'dua tenant dengan role yang sama memberi hasil BERBEZA — katalog tidak sepatutnya '
        .'bergantung kepada tenant, jadi ini bermakna data tenant memasuki laluan carian');

    // Dan tiada slug tenant dalam mana-mana hasil.
    $json = json_encode($a).json_encode($b);
    expect($json)->not->toContain($this->mam->slug)->and($json)->not->toContain($this->man->slug);
});

it('(e) akronim: yang ADA dalam korpus memberi hasil, yang TIADA memberi kosong', function () {
    config()->set('scout.meilisearch.host', null);
    $katalog = app(HelpCatalog::class);
    $mentah = json_encode($katalog->raw(), JSON_UNESCAPED_UNICODE);

    // ⚠️ Persona PENTING. Versi pertama ujian ini mencari `AJK` sebagai `admin_masjid` dan
    // mendapat kosong — saya hampir melaporkannya sebagai jurang fallback. Ukuran menunjukkan
    // satu-satunya guide dengan `ajk` dalam badan carian ialah `workflow.ajk.*`, berskop role
    // `ajk`. Jadi kosong itu ialah tapisan role yang BERFUNGSI, bukan carian yang rosak.
    // Ujian kini membuktikan kedua-duanya: akronim boleh dicari, DAN skop role dihormati.
    $ajk = makeMember($this->mam, 'ajk', 'ajk-gate@mam.test');

    foreach ([['OCR', $this->adminMam], ['AJK', $ajk]] as [$akronim, $persona]) {
        expect(substr_count($mentah, $akronim))->toBeGreaterThan(0, "prasyarat: {$akronim} sepatutnya ada dalam katalog");
        $hasil = app(HelpSearchService::class)->search($akronim, 'app', $persona, $this->mam);
        expect($hasil)->not->toBeEmpty("akronim {$akronim} ADA dalam korpus tetapi carian memberi kosong");
    }

    // Sisi negatif skop: `admin_masjid` TIDAK boleh melihat guide role `ajk` melalui akronim itu.
    $bukanMilikku = app(HelpSearchService::class)->search('AJK', 'app', $this->adminMam, $this->mam)
        ->pluck('id')->filter(fn (string $id) => str_starts_with($id, 'workflow.ajk.'))->all();
    expect($bukanMilikku)->toBe([], 'admin_masjid melihat guide berskop role ajk melalui carian akronim');

    // TIADA dalam korpus — ini merekod penemuan §9.2, bukan menyembunyikannya.
    // Jika `DDMS` kemudian DITAMBAH kepada keywords, ujian ini merah dan memaksa jadual §9
    // dikemas — itu tingkah laku yang dikehendaki, bukan gangguan.
    foreach (['DDMS', 'SPDM'] as $akronim) {
        expect(substr_count($mentah, $akronim))->toBe(0,
            "{$akronim} kini ADA dalam katalog — kemas kini jadual §9 dan PENEMUAN-CARIAN.md");
    }
});

it('(f) korpus yang boleh dicari DIUKUR — dua jurang direkod, bukan disembunyikan', function () {
    // Diukur 9 Ogos 2026 dan dikunci di sini supaya ia tidak boleh bergerak secara senyap:
    //
    //   fallback PHP  (HelpCatalog::search:69) : title + summary + keywords SAHAJA
    //   Meilisearch   (SyncHelpIndex:70-71)    : + steps_text (INSTRUCTION sahaja) + troubleshooting
    //
    // Dua jurang, kedua-duanya disahkan pada Meilisearch PRODUKSI:
    //   J1  perkataan hanya dalam TAJUK langkah -> 0 hasil pada KEDUA-DUA enjin
    //       (`penapis`, `lajur`: Meili 0, fallback 0)
    //   J2  perkataan hanya dalam instruction   -> Meili jumpa, fallback TIDAK
    //       (`taip`: Meili 1, fallback 0) — melanggar §9.2 "hasil setara" secara literal
    //
    // ⚠️ J1 bermakna tajuk langkah yang F6 tulis (placeholder 258 -> 0) TIDAK boleh dicari.
    $guides = app(HelpCatalog::class)->raw()['guides'];
    $kata = fn (string $t): array => array_values(array_unique(array_filter(
        preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($t)) ?: [],
        fn (string $w) => mb_strlen($w) >= 3,
    )));

    $badan = $instruksi = $tajukLangkah = '';
    foreach ($guides as $g) {
        $badan .= ' '.($g['title'] ?? '').' '.($g['summary'] ?? '').' '.implode(' ', $g['keywords'] ?? []);
        foreach ($g['steps'] ?? [] as $s) {
            $tajukLangkah .= ' '.($s['title'] ?? '');
            $instruksi .= ' '.($s['instruction'] ?? '');
        }
    }

    $B = $kata($badan);
    $I = $kata($instruksi);
    $T = $kata($tajukLangkah);

    $hanyaInstruksi = array_values(array_diff($I, $B));                  // Meili ada, fallback tiada
    $hanyaTajuk = array_values(array_diff($T, array_merge($B, $I)));     // tiada mana-mana enjin

    expect(count($hanyaTajuk))->toBe(17,
        'jurang J1 berubah ('.count($hanyaTajuk).') — kemas kini PENEMUAN-CARIAN.md dan jadual §9');
    expect(count($hanyaInstruksi))->toBe(38,
        'jurang J2 berubah ('.count($hanyaInstruksi).') — kemas kini PENEMUAN-CARIAN.md dan jadual §9');

    // Dan buktikan J2 pada laluan fallback SEBENAR, bukan hanya pada set perkataan.
    config()->set('scout.meilisearch.host', null);
    expect(app(HelpSearchService::class)->search('taip', 'app', $this->adminMam, $this->mam))
        ->toBeEmpty('`taip` kini dijumpai oleh fallback — J2 mungkin sudah ditutup, kemas dokumen');
});
