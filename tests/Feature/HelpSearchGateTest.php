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

use App\Console\Commands\SyncHelpIndex;
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

    // ⚠️ "tidak kosong" SAHAJA terlalu longgar (Codex #9): fallback yang memulangkan guide
    // rawak masih lulus. Hasil mesti RELEVAN kepada pertanyaan.
    $ids = $hasil->pluck('id');
    expect($ids->contains(fn (string $id) => str_contains($id, 'klasifikasi')
        || str_contains($id, 'klasifikasikan')
        || str_contains($id, 'peti-masuk')))
        ->toBeTrue('fallback memulangkan hasil TIDAK BERKAITAN dengan "klasifikasi surat": '.$ids->implode(', '));

    // Dan ia mesti direkod sebagai enjin `php`, bukan `meilisearch` — jika tidak, telemetri
    // akan mendakwa Meili sihat sedangkan ia mati (keluarga "sihat != tiada ralat").
    // ⚠️ `engine` bukan kolum: ia hidup dalam `metadata` (`HelpSearchService::recordSearch`).
    // Versi pertama ujian ini membaca `$peristiwa->engine` dan mendapat `null` — yang akan
    // LULUS jika saya menulis `not->toBe('meilisearch')` sebaliknya. Baca tempat sebenar.
    $peristiwa = HelpEvent::query()->latest('id')->first();
    expect(data_get($peristiwa->metadata, 'engine'))->toBe('php');
});

it('(a2) Meilisearch TERGANTUNG (timeout, bukan refused) — fallback menyelamatkan, tetapi LAMBAT', function () {
    // Codex pusingan 2 (#15) betul: ujian (a) ialah `connection refused` pada port 1, bukan
    // TIMEOUT. Cabang itu berbeza — refused kembali serta-merta; hang menunggu tempoh klien.
    //
    // 192.0.2.1 = RFC 5737 TEST-NET-1: tidak boleh dirutkan, jadi `connect` TERGANTUNG lalu
    // timeout. Tiada rangkaian LUAR diperlukan (peraturan #5 CLAUDE.md) — paket tidak pergi
    // ke mana-mana.
    //
    // 🔴 DIUKUR: 24,232 ms. Fallback MEMANG menyelamatkan hasil (10 guide, `engine=php`) —
    // tetapi pengguna menunggu ~24 saat dahulu. Itu lebih buruk daripada "carian jadi cetek":
    // halaman tersekat. Klien Meilisearch tiada tempoh eksplisit; ia mewarisi lalai.
    // Cadangan (BUKAN dibuat di F8 — perubahan produk): beri klien tempoh eksplisit ~2s.
    //
    // Ujian ini OPT-IN kerana ia menambah ~24s kepada suite. `DIWAN_SLOW_TESTS=1` menghidupkannya.
    config()->set('scout.meilisearch.host', 'http://192.0.2.1:7700');
    config()->set('scout.meilisearch.key', 'kunci-tidak-sah');

    $mula = microtime(true);
    $hasil = app(HelpSearchService::class)->search('klasifikasi surat', 'app', $this->adminMam, $this->mam);
    $ms = (int) round((microtime(true) - $mula) * 1000);

    expect($hasil)->not->toBeEmpty('fallback TIDAK menyelamatkan carian apabila Meili tergantung');
    expect(data_get(HelpEvent::query()->latest('id')->first()->metadata, 'engine'))->toBe('php');

    // Sempadan atas yang generous — ia mendokumenkan tingkah laku, bukan mengunci prestasi.
    // Jika ia melebihi 60s, sesuatu berubah menjadi lebih buruk dan patut disiasat.
    expect($ms)->toBeLessThan(60_000, "laluan timeout mengambil {$ms} ms — semak tempoh klien");
})->skip(! env('DIWAN_SLOW_TESTS'), 'ujian LAMBAT (~24s) — hidupkan dengan DIWAN_SLOW_TESTS=1');

it('(b) korpus yang diindeks tidak mengandungi data tenant atau pengguna', function () {
    // Medan yang benar-benar diindeks (disahkan pada indeks PRODUKSI, 8 Ogos 2026):
    // document_id, guide_id, panel, roles, title, summary, keywords, steps_text,
    // troubleshooting_text. Tiada mosque_id, tiada user_id, tiada e-mel.
    $guides = app(HelpCatalog::class)->raw()['guides'];

    // ⚠️ Versi pertama membina proyeksinya SENDIRI daripada `guides.json`, jadi jika
    // `SyncHelpIndex` ditukar untuk memasukkan `mosque_id`/`user_id`, ujian kekal hijau
    // (Codex #10). Kini dokumen dibina melalui `SyncHelpIndex::documentFor()` — laluan yang
    // SAMA seperti runtime.
    $dokumen = collect($guides)->map(fn (array $g): array => SyncHelpIndex::documentFor($g));

    // Set medan DIKUNCI pada SETIAP dokumen, bukan hanya yang pertama.
    // ⚠️ Codex pusingan 2 (#5) menjalankan counterexample: menambah `mosque_id`/`user_id` pada
    // dokumen KEDUA melepasi versi terdahulu yang hanya memeriksa `->first()`. Satu dokumen
    // bukan sampel yang mencukupi bagi invarian struktur.
    $medanDijangka = [
        'document_id', 'guide_id', 'panel', 'roles', 'title', 'summary', 'keywords',
        'steps_text', 'troubleshooting_text',
    ];
    $medanSalah = [];
    foreach ($dokumen as $d) {
        if (array_keys($d) !== $medanDijangka) {
            $medanSalah[] = ($d['guide_id'] ?? '?').': '.implode(',', array_diff(array_keys($d), $medanDijangka));
        }
    }
    expect($medanSalah)->toBe([],
        'set medan dokumen indeks berubah pada '.count($medanSalah).' dokumen — sahkan tiada '
        .'data tenant/pengguna masuk: '.implode(' · ', array_slice($medanSalah, 0, 5)));

    $korpus = $dokumen->map(fn (array $d): string => json_encode($d, JSON_UNESCAPED_UNICODE))->implode("\n");

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

    // ⚠️ Versi pertama LULUS SECARA VAKUM: jika keempat-empat query memulangkan koleksi kosong,
    // `$bukanAwam` kosong dan ujian hijau walaupun carian awam rosak sepenuhnya (Codex #11).
    // Kini sekurang-kurangnya satu query MESTI memberi hasil awam sebelum sempadan bermakna.
    $jumlahHasil = 0;
    foreach (['klasifikasi surat', 'pelupusan', 'kelulusan', 'daftar masjid'] as $q) {
        $hasil = app(HelpSearchService::class)->search($q, 'public');
        $jumlahHasil += $hasil->count();
        $bukanAwam = $hasil->pluck('id')->reject(fn (string $id) => str_starts_with($id, 'public.'))->all();
        expect($bukanAwam)->toBe([], "carian awam \"{$q}\" membocorkan guide bukan-awam: ".implode(', ', $bukanAwam));
    }
    expect($jumlahHasil)->toBeGreaterThan(0,
        'TIADA satu pun query awam memberi hasil — sempadan "tiada kebocoran" tidak bermakna '
        .'apabila carian memulangkan kosong untuk semuanya');
});

it('(d) dua tenant: SET guide sama, laluan dikontekskan, tiada silang slug (isolasi)', function () {
    config()->set('scout.meilisearch.host', null);
    $q = 'klasifikasi surat';

    // ⚠️ Versi pertama `pluck('id')` SEBELUM membanding, jadi kebocoran dalam route/title/
    // summary/metadata hasil tidak pernah diperiksa (Codex #12). Kini objek PENUH dibanding.
    $a = app(HelpSearchService::class)->search($q, 'app', $this->adminMam, $this->mam)->values()->all();
    $b = app(HelpSearchService::class)->search($q, 'app', $this->adminMan, $this->man)->values()->all();

    // 🔴 Membanding objek PENUH mendedahkan bahawa premis "IDENTIK" itu SENDIRI salah:
    // `route` dikontekskan kepada tenant semasa (`/app/mam/peti-masuk` lawan
    // `/app/man/peti-masuk`). Itu tingkah laku yang BETUL — setiap tenant mendapat laluannya
    // sendiri. Jadi sifat isolasi yang sebenar ada DUA, dan kedua-duanya diassert:
    //   (i)  SET guide sama       → katalog tidak bergantung kepada tenant;
    //   (ii) tiada silang slug     → tenant A tidak pernah melihat laluan tenant B.
    // Versi `pluck('id')` yang lama lulus atas sebab yang betul tetapi menguji kurang; versi
    // objek-penuh MERAH atas sebab yang sah. Kedua-dua sifat kini dikunci.
    expect($a)->not->toBeEmpty();

    expect(collect($b)->pluck('id')->all())->toBe(collect($a)->pluck('id')->all(),
        'dua tenant dengan role yang sama memberi SET guide berbeza — katalog tidak sepatutnya '
        .'bergantung kepada tenant');

    // ⚠️ `JSON_UNESCAPED_SLASHES` WAJIB. Tanpanya `json_encode` menghasilkan `\/app\/mam\/`,
    // jadi setiap `toContain('/app/…/')` di bawah TIDAK MUNGKIN padan dan kedua-dua semakan
    // silang-tenant akan lulus secara VAKUM. Semakan positif pada baris terakhir blok ini
    // yang mendedahkannya — itulah sebabnya ia ada.
    $jsonA = json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $jsonB = json_encode($b, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // 🔴 JANGAN guna `toContain($needle, $mesej)` — **`toContain` VARIADIK**: argumen kedua
    // menjadi NEEDLE KEDUA, bukan mesej. Kesannya dua arah dan kedua-duanya buruk:
    //   `expect($x)->toContain($n, 'ayat')`      → gagal, kerana 'ayat' tiada
    //   `expect($x)->not->toContain($n, 'ayat')` → LULUS VAKUM ("tidak mengandungi kedua-duanya")
    // Ini pelajaran yang sudah direkod dalam memori projek dan saya langgar semula di sini.
    // `str_contains` + `toBeFalse/toBeTrue` menerima mesej dengan betul.
    expect(str_contains($jsonA, "/app/{$this->man->slug}/"))->toBeFalse(
        'hasil tenant MAM mengandungi laluan tenant MAN — kebocoran silang-tenant');
    expect(str_contains($jsonB, "/app/{$this->mam->slug}/"))->toBeFalse(
        'hasil tenant MAN mengandungi laluan tenant MAM — kebocoran silang-tenant');

    // Kontekstualisasi mesti BENAR-BENAR berlaku, jika tidak dua semakan di atas lulus vakum.
    expect(str_contains($jsonA, "/app/{$this->mam->slug}/"))->toBeTrue(
        'hasil tidak dikontekskan kepada tenant semasa — semakan silang di atas tidak bermakna');

    foreach ([$jsonA, $jsonB] as $json) {
        expect(preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $json))->toBe(0,
            'hasil carian mengandungi alamat e-mel');
    }
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
    // ⚠️ Versi pertama hanya `substr_count` case-SENSITIVE dan tidak pernah MEMANGGIL carian
    // (Codex #13): `ddms` huruf kecil boleh ditambah tanpa dikesan, dan tingkah laku carian
    // sebenar tidak diuji. Kini kedua-duanya diuji.
    foreach (['DDMS', 'SPDM'] as $akronim) {
        expect(mb_stripos($mentah, $akronim))->toBeFalse(
            "{$akronim} kini ADA dalam katalog (apa-apa huruf) — kemas kini jadual §9 dan PENEMUAN-CARIAN.md");
        expect(app(HelpSearchService::class)->search($akronim, 'app', $this->adminMam, $this->mam))
            ->toBeEmpty("{$akronim} kini memberi hasil — penemuan §9.2 berubah, kemas dokumen");
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

    // ⚠️ Versi pertama MENGUNCI 17 dan 38 tepat. Codex #15 betul bahawa itu rapuh: set token
    // exact tidak memodelkan ASCII-folding, substring dan Levenshtein yang `HelpCatalog::search`
    // lakukan, jadi suntingan copy biasa akan memerahkan suite tanpa membuktikan regresi produk.
    // Yang dikunci sekarang ialah ARAH (jurang WUJUD) + contoh yang DISAHKAN pada laluan sebenar.
    expect(count($hanyaTajuk))->toBeGreaterThan(0,
        'J1 tertutup — tajuk langkah kini boleh dicari? Kemas PENEMUAN-CARIAN.md §4');
    expect(count($hanyaInstruksi))->toBeGreaterThan(0,
        'J2 tertutup — fallback kini seluas Meili? Kemas PENEMUAN-CARIAN.md §3');

    // Contoh yang DISAHKAN pada laluan fallback SEBENAR (bukan inferens set perkataan):
    //   `taip`    hanya dalam instruction  -> Meili 1 hit (produksi), fallback 0
    //   `penapis` hanya dalam tajuk langkah -> Meili 0, fallback 0
    config()->set('scout.meilisearch.host', null);
    foreach (['taip', 'penapis'] as $q) {
        expect(app(HelpSearchService::class)->search($q, 'app', $this->adminMam, $this->mam))
            ->toBeEmpty("`{$q}` kini dijumpai oleh fallback — jurang mungkin ditutup, kemas dokumen");
    }

    // Kawalan: perkataan dalam title/summary/keywords MESTI dijumpai oleh fallback yang sama.
    // Tanpa ini, "kosong" di atas boleh bermakna carian rosak sepenuhnya.
    expect(app(HelpSearchService::class)->search('klasifikasi', 'app', $this->adminMam, $this->mam))
        ->not->toBeEmpty('kawalan gagal: fallback tidak menjumpai perkataan yang ADA dalam badan');
});
