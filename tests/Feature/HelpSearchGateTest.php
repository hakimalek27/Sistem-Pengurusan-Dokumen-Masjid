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

    // ⚠️ Dua pusingan menegaskan ini. P1 #9: "tidak kosong" terlalu longgar. P2 #7: menerima
    // mana-mana ID yang mengandungi tiga substring MASIH proksi — guide rawak dengan ID yang
    // "kebetulan sesuai" lulus. Kini KEDUDUKAN diassert: hasil TERATAS mesti guide yang betul.
    // DIUKUR pada fallback: "klasifikasi surat" -> tenant.peti-masuk, tenant.classification-nodes,
    // workflow.admin_masjid.muat-naik-…-klasifikasikan-…
    $ids = $hasil->pluck('id');
    expect($ids->first())->toBeIn(['tenant.peti-masuk', 'tenant.classification-nodes'],
        'hasil TERATAS untuk "klasifikasi surat" bukan guide yang berkaitan: '.$ids->implode(', '));

    // Dan sekurang-kurangnya satu guide klasifikasi hadir dalam keseluruhan set.
    expect($ids->contains('tenant.classification-nodes'))->toBeTrue(
        'guide klasifikasi tiada dalam hasil: '.$ids->implode(', '));

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
    // 🔴 DIUKUR SEBELUM PEMBAIKAN: 24,232 ms. Fallback MEMANG menyelamatkan hasil, tetapi
    // pengguna menunggu ~24 saat dahulu — halaman bantuan TERSEKAT, bukan sekadar cetek.
    // Puncanya: klien Meilisearch tiada tempoh eksplisit dan mewarisi lalai yang panjang.
    //
    // ✅ DIBAIKI: `diwan.guidance.meilisearch_timeout` (lalai 2.0s) dihantar kepada klien HTTP.
    // DIUKUR SELEPAS: **2,085 ms**, hasil dan `engine=php` tidak berubah. 12x lebih pantas.
    //
    // Kerana ia kini ~2s, ujian ini BUKAN lagi opt-in — ia berjalan setiap larian dan menjaga
    // tempoh itu daripada hilang secara senyap.
    config()->set('scout.meilisearch.host', 'http://192.0.2.1:7700');
    config()->set('scout.meilisearch.key', 'kunci-tidak-sah');

    $mula = microtime(true);
    $hasil = app(HelpSearchService::class)->search('klasifikasi surat', 'app', $this->adminMam, $this->mam);
    $ms = (int) round((microtime(true) - $mula) * 1000);

    expect($hasil)->not->toBeEmpty('fallback TIDAK menyelamatkan carian apabila Meili tergantung');
    expect(data_get(HelpEvent::query()->latest('id')->first()->metadata, 'engine'))->toBe('php');

    // Sempadan: tempoh dikonfigurasi 2.0s, jadi 8s memberi ruang luas untuk mesin perlahan
    // sambil tetap MERAH jika tempoh itu dibuang (yang akan mengembalikan ~24s).
    $had = (int) (config('diwan.guidance.meilisearch_timeout') * 4000);
    expect($ms)->toBeLessThan($had,
        "laluan timeout mengambil {$ms} ms (had {$had} ms) — tempoh klien Meilisearch mungkin hilang");
});

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

    // ⚠️ Codex P2 #6: komen mendakwa "objek penuh dibanding" tetapi kod hanya membanding ID,
    // jadi perbezaan dalam `title`/`summary`/metadata antara dua tenant kekal hijau. Kini
    // objek PENUH dibanding selepas `route` DINORMALKAN kembali kepada `{tenant}` — kerana
    // kontekstualisasi route itu SAH (dan diassert berasingan di bawah), manakala apa-apa
    // perbezaan LAIN antara dua tenant tidak sepatutnya wujud.
    // ⚠️ Normalisasi mesti MENYELURUH: versi pertama guna `array_map` peringkat atas sahaja,
    // jadi `steps[].route` yang bersarang kekal membawa slug dan ujian gagal atas sebab yang
    // salah. Round-trip JSON menyentuh setiap rentetan pada setiap kedalaman.
    $normal = fn (array $senarai, string $slug): array => json_decode(
        str_replace("/app/{$slug}", '/app/{tenant}', json_encode($senarai, JSON_UNESCAPED_SLASHES)),
        true,
    );
    $aNorm = $normal($a, $this->mam->slug);
    $bNorm = $normal($b, $this->man->slug);

    expect($bNorm)->toBe($aNorm,
        'dua tenant dengan role yang sama memberi hasil BERBEZA selepas route dinormalkan — '
        .'perbezaan itu bermakna data tenant memasuki medan selain route');

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

it('(e) akronim: yang ADA dalam korpus memberi hasil, termasuk akronim PRODUK', function () {
    // SEJARAH: gate §9.2 menuntut `DDMS` memulangkan hasil, tetapi diukur 9 Ogos 2026 istilah itu
    // muncul **0 kali** dalam katalog — jadi 0 hasil ialah perbendaharaan kandungan, bukan
    // kegagalan enjin. Pemilik memilih (9 Ogos, pilihan (a)) untuk MENAMBAH akronim produk kepada
    // `keywords`, kerana sistem ini memang sebuah DDMS dan pengguna mungkin menaipnya.
    // Ditambah pada 4 guide merentas KETIGA-TIGA panel supaya ia berfungsi untuk pengguna awam,
    // tenant dan platform: public.help · public.registration · tenant.dashboard · admin.dashboard
    // ⚠️ Kandungan indeks berubah -> deploy MESTI `diwan:sync-help-index --delete`.
    $mentah = json_encode(app(HelpCatalog::class)->raw(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Akronim PRODUK: kini WAJIB ada dalam katalog DAN boleh dicari.
    foreach (['DDMS', 'SPDM'] as $akronim) {
        expect(mb_stripos($mentah, $akronim))->not->toBeFalse(
            "{$akronim} hilang daripada katalog — keputusan pemilik (9 Ogos) ialah ia MESTI ada",
        );
        expect(app(HelpSearchService::class)->search($akronim, 'public', null, null))
            ->not->toBeEmpty("{$akronim} tidak dijumpai oleh carian AWAM walaupun ada dalam katalog");
        expect(app(HelpSearchService::class)->search($akronim, 'app', $this->adminMam, $this->mam))
            ->not->toBeEmpty("{$akronim} tidak dijumpai oleh carian TENANT walaupun ada dalam katalog");
    }

    // Akronim yang MEMANG dalam korpus sejak awal — kawalan bahawa enjin tidak berubah.
    foreach (['OCR', 'ocr'] as $q) {
        expect(app(HelpSearchService::class)->search($q, 'app', $this->adminMam, $this->mam))
            ->not->toBeEmpty("akronim `{$q}` sepatutnya memberi hasil (case-insensitive)");
    }

    // Kawalan NEGATIF: istilah karut mesti kekal kosong, jika tidak "semua akronim dijumpai"
    // di atas boleh bermakna carian memulangkan segala-galanya.
    expect(mb_stripos($mentah, 'XYZQ'))->toBeFalse('XYZQ tidak sepatutnya ada dalam katalog');
    expect(app(HelpSearchService::class)->search('XYZQ', 'app', $this->adminMam, $this->mam))
        ->toBeEmpty('carian memberi hasil untuk istilah karut — kawalan negatif gagal');

    // Skop role dihormati: guide berskop `ajk` tidak boleh muncul untuk admin_masjid.
    expect(app(HelpSearchService::class)->search('AJK', 'app', $this->adminMam, $this->mam))
        ->toBeEmpty('guide berskop role `ajk` bocor kepada admin_masjid');
});

it('(f) korpus fallback SEPADAN dengan Meilisearch — jurang J1 dan J2 DITUTUP', function () {
    // SEJARAH (jangan buang — ia menerangkan mengapa ujian ini wujud):
    // Diukur 9 Ogos 2026, fallback PHP mencari title+summary+keywords SAHAJA sedangkan
    // Meilisearch turut mencari steps_text + troubleshooting_text. Dua jurang:
    //   J1  perkataan hanya dalam TAJUK langkah -> 0 hasil pada KEDUA-DUA enjin
    //       (tajuk yang F6 tulis, placeholder 258 -> 0, tidak boleh dicari langsung)
    //   J2  perkataan hanya dalam instruction   -> Meili jumpa, fallback TIDAK
    //       (melanggar §9.2 "hasil setara" secara literal)
    // Kedua-duanya kini DITUTUP: `steps_text` merangkumi tajuk langkah, dan badan fallback
    // merangkumi tajuk+arahan langkah serta teks penyelesaian masalah.
    // ⚠️ Perubahan kandungan indeks memerlukan `diwan:sync-help-index --delete` semasa deploy.
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
    $hanyaInstruksi = array_values(array_diff($kata($instruksi), $B));
    $hanyaTajuk = array_values(array_diff($kata($tajukLangkah), array_merge($B, $kata($instruksi))));

    // Anti-vakum: jika katalog kehilangan teks langkah, set ini jadi kosong dan gelung di bawah
    // akan LULUS tanpa menguji apa-apa.
    expect(count($hanyaInstruksi))->toBeGreaterThan(5, 'terlalu sedikit perkataan hanya-instruction — set ujian rosak');
    expect(count($hanyaTajuk))->toBeGreaterThan(5, 'terlalu sedikit perkataan hanya-tajuk-langkah — set ujian rosak');

    // Penjaga STRUKTUR sisi Meilisearch (tiada Meili tempatan): senarai atribut mesti kekal,
    // DAN dokumen mesti benar-benar membawa tajuk langkah dalam `steps_text`.
    expect(SyncHelpIndex::SEARCHABLE_ATTRIBUTES)->toBe(
        ['title', 'summary', 'keywords', 'steps_text', 'troubleshooting_text'],
        'atribut boleh-cari Meilisearch berubah — pariti J1/J2 dikira dengan andaian senarai ini',
    );
    expect(SyncHelpIndex::FILTERABLE_ATTRIBUTES)->toBe(['panel', 'roles']);

    $adaTajuk = collect($guides)->first(fn (array $g) => filled(collect($g['steps'] ?? [])->pluck('title')->filter()));
    $dok = SyncHelpIndex::documentFor($adaTajuk);
    $tajukPertama = (string) collect($adaTajuk['steps'])->pluck('title')->filter()->first();
    expect(str_contains($dok['steps_text'], $tajukPertama))->toBeTrue(
        "`steps_text` tidak mengandungi tajuk langkah \"{$tajukPertama}\" — jurang J1 dibuka semula",
    );

    // PARITI pada laluan fallback SEBENAR (bukan inferens set perkataan). Meili dimatikan.
    config()->set('scout.meilisearch.host', null);
    $cari = fn (string $q) => app(HelpSearchService::class)->search($q, 'app', $this->adminMam, $this->mam);

    foreach (array_slice($hanyaInstruksi, 0, 5) as $q) {
        expect($cari($q))->not->toBeEmpty("J2 dibuka semula: `{$q}` hanya dalam instruction dan fallback tidak menjumpainya");
    }
    foreach (array_slice($hanyaTajuk, 0, 5) as $q) {
        expect($cari($q))->not->toBeEmpty("J1 dibuka semula: `{$q}` hanya dalam tajuk langkah dan fallback tidak menjumpainya");
    }

    // Kawalan DUA HALA — tanpa ini "semuanya dijumpai" boleh bermakna carian memulangkan
    // segala-galanya, dan gelung di atas akan lulus secara vakum.
    expect($cari('klasifikasi'))->not->toBeEmpty('kawalan positif gagal: perkataan badan tidak dijumpai');
    expect($cari('zzqqxx-tiada-langsung'))->toBeEmpty('kawalan negatif gagal: carian memulangkan hasil untuk perkataan karut');
});
