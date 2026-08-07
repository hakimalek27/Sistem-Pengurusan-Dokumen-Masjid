// F6-W4 (PELAN-PEMBAIKAN §7.2 langkah 2, cabang "Input carian jadual / elemen vendor tanpa
// cangkuk PHP") — peraturan pemetaan sasaran bantuan PER HALAMAN.
//
// Punca: Filament 4 merender medan carian jadual (`.fi-ta-search-field`) dan pencetus tapisan
// (`.fi-ta-filters-trigger-action-ctn`) sendiri, tanpa `extraAttributes()`. PHP tidak boleh
// menandakannya, jadi pelan membenarkan pemetaan JS — dengan syarat ia kecil dan bersebab.
//
// Mengapa PER HALAMAN dan bukan satu entri global: satu elemen hanya boleh memegang SATU
// `data-help-target`. Entri global `['.fi-ta-search-field', 'records-search']` akan merampas
// medan carian pada SETIAP halaman berjadual, menjadikan `log-search` mustahil dan
// menghidupkan semula bentrokan yang F5c bayar mahal (`sidebar` lawan `nav-sidebar`).
//
// Modul ini TULEN: tiada DOM, tiada CSS, tiada import — supaya ujian boleh mengimportnya
// sebagai Node ESM tanpa bundler dan TANPA meninggalkan cangkuk ujian dalam bundle produksi
// (kontrak C11, sama seperti `step-advance-plan.js` dan `nav-target-plan.js`).
//
// Kewujudan carian/tapisan setiap halaman DIUKUR daripada kelas jadualnya, bukan diandaikan:
//   RecordsTable              searchable ✔  filters ✔
//   MosqueActivityLogsTable   searchable ✔  filters ✔
//   MinitsTable               searchable ✘  filters ✔   (kategori = SelectFilter:48)
//   RegistryFilesTable        searchable ✔  filters ✘
// Sebab itu `minit-saya` tiada entri carian dan `registry-files` tiada entri tapisan —
// entri yang tidak akan pernah dipadankan ialah dokumentasi palsu.
//
// ⚠️ SELEKTOR MESTI DIUKUR PADA HTML SEBENAR, bukan dibaca daripada blade vendor.
// Versi pertama modul ini menyasar `.fi-ta-filters-trigger-action-ctn` kerana kelas itu
// kelihatan dalam `vendor/filament/tables/.../index.blade.php` — tetapi ia dirender HANYA
// `@if ($hasCollapsibleFilters)`, bukan pada susun atur tapisan LALAI. Akibatnya `log-filters`
// dan `minit-filters` tidak pernah wujud dan lima guide `workflow` gagal pada gate W4
// pusingan 1 dengan `sasaran dijangka: -:tiada`. Susun atur lalai merender
// `<x-filament::dropdown class="fi-ta-filters-dropdown">`. Kelas yang dipakai di bawah kini
// dikunci oleh `tests/Feature/Help/PageTargetSelectorTest.php`, yang mengassert setiap sauh
// vendor benar-benar hadir dalam HTML halamannya — dan yang akan merah SEBELUM shard e2e
// yang panjang itu merah jika Filament menukar nama kelasnya.

/**
 * Pemetaan `[selector, target]` mengikut laluan halaman panel tenant.
 *
 * Regex menerima laluan dengan atau tanpa garis condong hujung, dan `[^/]+` ialah slug tenant.
 * Ia sengaja DIPAKU pada hujung (`$`) supaya halaman butiran (`/records/12`) tidak dipadankan:
 * medan carian tidak wujud di sana, dan sasaran senarai tidak sepatutnya bocor ke butiran.
 */
export const PAGE_TARGETS = [
    {
        route: '/app/{tenant}/records',
        padanan: /^\/app\/[^/]+\/records\/?$/,
        peta: [['.fi-ta-search-field', 'records-search']],
    },
    {
        route: '/app/{tenant}/log-aktiviti',
        padanan: /^\/app\/[^/]+\/log-aktiviti\/?$/,
        peta: [
            ['.fi-ta-search-field', 'log-search'],
            ['.fi-ta-filters-dropdown', 'log-filters'],
        ],
    },
    {
        route: '/app/{tenant}/minit-saya',
        padanan: /^\/app\/[^/]+\/minit-saya\/?$/,
        peta: [['.fi-ta-filters-dropdown', 'minit-filters']],
    },
    {
        route: '/app/{tenant}/registry-files',
        padanan: /^\/app\/[^/]+\/registry-files\/?$/,
        peta: [['.fi-ta-search-field', 'regfiles-search']],
    },
    // ── F6-W5 ────────────────────────────────────────────────────────────────────────────
    {
        route: '/app/{tenant}/classification-nodes',
        padanan: /^\/app\/[^/]+\/classification-nodes\/?$/,
        peta: [['.fi-ta-search-field', 'classnode-search']],
    },
    {
        route: '/app/{tenant}/sensitive-access-logs',
        padanan: /^\/app\/[^/]+\/sensitive-access-logs\/?$/,
        peta: [['.fi-ta-search-field', 'sensitive-log-search']],
    },
    // Tiket sokongan wujud pada KEDUA-DUA panel dan berkongsi `App\Filament\Support\
    // SupportRequestsTable`, jadi satu sasaran melayan dua guide. Medan carian DIUKUR hadir
    // walaupun jadual KOSONG (benih demo: 0 tiket) — itulah sebab ia dipilih dan bukan
    // sasaran baris.
    {
        route: '/app/{tenant}/tiket-sokongan',
        padanan: /^\/app\/[^/]+\/tiket-sokongan\/?$/,
        peta: [['.fi-ta-search-field', 'tickets-search']],
    },
    {
        route: '/admin/tiket-sokongan',
        padanan: /^\/admin\/tiket-sokongan\/?$/,
        peta: [['.fi-ta-search-field', 'tickets-search']],
    },
    // Papan pemuka: `StatsOverviewWidget` Filament tidak menyalurkan `extraAttributes()` ke
    // pembalutnya (`Stat extends Schemas\Components\Component`; blade `stat.blade.php` tidak
    // pernah merender bag atribut). Kelas pembalut `.fi-wi-stats-overview` datang daripada
    // `stats-overview-widget.blade.php:18` dan DIUKUR hadir pada kedua-dua panel.
    {
        route: '/app/{tenant}',
        padanan: /^\/app\/[^/]+\/?$/,
        peta: [['.fi-wi-stats-overview', 'dashboard-stats']],
    },
    {
        route: '/admin',
        padanan: /^\/admin\/?$/,
        peta: [['.fi-wi-stats-overview', 'dashboard-stats']],
    },
    // ── Hutang F7 (W5 §10) ───────────────────────────────────────────────────────────────
    // `admin.mosques#2` berbunyi "Semak, lulus, gantung atau pulihkan tenant" dan
    // `admin.users#2` "Urus akaun global, status aktif dan akses superadmin" — kedua-duanya
    // menerangkan tindakan BARIS, tetapi menyorot kotak carian (211x36). Sasaran kini sel
    // tindakan baris pertama, yang benar-benar mengandungi butang yang ayat itu namakan.
    //
    // ⚠️ Sel `<td>`, BUKAN `.fi-ta-actions` di dalamnya. Diukur: `.fi-ta-actions` ialah
    // 593x20 (mosques) / 186x20 (users) — jalur nipis yang tidak melitupi butang yang
    // membalut ke baris kedua. Itu tepat kecacatan `disposal-actions` yang W4 bayar
    // (sorotan sah tetapi tidak bermakna). `<td>` = 629x105 / 222x57 dan melitupi semuanya.
    //
    // Sasaran hanya wujud bila ada baris. Diukur pada benih demo: mosques 2, users 10.
    // (`/admin/storage-orders` ada 0 baris, sebab itu ia KEKAL pada sasaran carian.)
    {
        route: '/admin/mosques',
        padanan: /^\/admin\/mosques\/?$/,
        peta: [
            ['.fi-ta-search-field', 'platform-mosques'],
            ['tbody tr:first-child td:last-child', 'platform-mosques-actions'],
        ],
    },
    {
        route: '/admin/users',
        padanan: /^\/admin\/users\/?$/,
        peta: [
            ['.fi-ta-search-field', 'platform-users'],
            ['tbody tr:first-child td:last-child', 'platform-users-actions'],
        ],
    },
    {
        route: '/admin/storage-orders',
        padanan: /^\/admin\/storage-orders\/?$/,
        peta: [['.fi-ta-search-field', 'platform-storage-orders']],
    },
    {
        route: '/admin/help-announcements',
        padanan: /^\/admin\/help-announcements\/?$/,
        peta: [['.fi-ta-search-field', 'platform-announcements']],
    },
];

/**
 * Pasangan `[selector, target]` yang terpakai bagi satu laluan.
 *
 * @param {string} pathname `window.location.pathname`
 * @returns {Array<[string, string]>} kosong jika laluan itu tiada pemetaan
 */
export function pageTargetsFor(pathname) {
    return PAGE_TARGETS
        .filter((entri) => entri.padanan.test(pathname))
        .flatMap((entri) => entri.peta);
}
