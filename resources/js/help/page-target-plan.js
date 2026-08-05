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
            ['.fi-ta-filters-trigger-action-ctn', 'log-filters'],
        ],
    },
    {
        route: '/app/{tenant}/minit-saya',
        padanan: /^\/app\/[^/]+\/minit-saya\/?$/,
        peta: [['.fi-ta-filters-trigger-action-ctn', 'minit-filters']],
    },
    {
        route: '/app/{tenant}/registry-files',
        padanan: /^\/app\/[^/]+\/registry-files\/?$/,
        peta: [['.fi-ta-search-field', 'regfiles-search']],
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
