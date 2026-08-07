// Hutang F7 — satu batch suntingan katalog + registri.
//
// ⚠️ INDENTASI BERBEZA SETIAP FAIL (perangkap W6 yang menghasilkan diff 5,410 baris):
//    guides.json = 2 ruang · targets.json = 4 ruang.
//
// Semua sasaran dipilih daripada UKURAN DOM, bukan bacaan Blade:
//   help-search           tenant 1056x3211 (70% <main>) · admin 1056x1421 (53%)  -> terlalu luas
//   help-search-form      1056x66 (1-2%)                                          -> dipilih
//   help-scope            1056x20 (0-1%)  baris yang MENYATAKAN skop                -> dipilih
//   platform-*            211x36 kotak carian, sedangkan ayat menerangkan tindakan BARIS
//   td tindakan baris 1   mosques 629x105 · users 222x57                           -> dipilih
//   .fi-ta-actions        593x20 / 186x20 = jalur nipis -> DITOLAK (defect W4 disposal-actions)

import { readFileSync, writeFileSync } from 'node:fs';

const VERSI_BAHARU = '2026.08.08.2';
const INDEN = { 'resources/help/guides.json': 2, 'resources/help/targets.json': 4 };

const baca = (f) => JSON.parse(readFileSync(f, 'utf8'));
const tulis = (f, data) => writeFileSync(f, JSON.stringify(data, null, INDEN[f]) + '\n');

// ── katalog ──────────────────────────────────────────────────────────────────────────────
const G = 'resources/help/guides.json';
const g = baca(G);
const senarai = Array.isArray(g.guides) ? g.guides : Object.values(g.guides ?? g);
const cari = (id) => senarai.find((x) => x.id === id);

const TUKAR = [
    // (a) hutang yang direkod: sasaran terlalu luas, hampir tidak dapat dibezakan drp page-content
    ['tenant.bantuan', 1, 'help-search', 'help-search-form'],
    ['admin.bantuan', 1, 'help-search', 'help-search-form'],
    // (a+) langkah 2 diselaraskan dengan public.help yang W6 sudah reka: ayatnya berbunyi
    //      "Pastikan panel, tenant dan role semasa adalah betul" dan `help-scope` ialah baris
    //      yang MENYATAKAN skop itu; `nav-primary` hanya menyiratkannya.
    ['tenant.bantuan', 2, 'nav-primary', 'help-scope'],
    ['admin.bantuan', 2, 'nav-primary', 'help-scope'],
    // (b) tiga ketidakpadanan makna W5 §10 — sasaran mesti menjawab AYAT langkah.
    ['admin.mosques', 2, 'platform-mosques', 'platform-mosques-actions'],
    ['admin.users', 2, 'platform-users', 'platform-users-actions'],
    // admin.storage-orders#2 SENGAJA KEKAL: benih 0 baris (diukur), jadi sasaran baris tidak
    // akan wujud dan gate hijau bermakna "tiada yang diuji" — pelajaran W4 (butang Laksana).
];

for (const [id, langkah, dari, ke] of TUKAR) {
    const s = cari(id)?.steps?.[langkah - 1];
    if (!s) throw new Error(`${id}#${langkah} tidak dijumpai`);
    if (s.target !== dari) throw new Error(`${id}#${langkah} sasaran = ${s.target}, dijangka ${dari}`);
    s.target = ke;
    console.log(`katalog  ${id}#${langkah}  ${dari} -> ${ke}`);
}

const versiLama = g.catalog_version;
g.catalog_version = VERSI_BAHARU;
console.log(`katalog  catalog_version ${versiLama} -> ${VERSI_BAHARU}`);
tulis(G, g);

// ── registri ─────────────────────────────────────────────────────────────────────────────
const T = 'resources/help/targets.json';
const t = baca(T);
const entri = Array.isArray(t) ? t : (t.targets ?? Object.values(t));

const cariEntri = (id) => entri.find((x) => x.id === id);

// Yatim selepas pertukaran: DOM masih ada, katalog tidak lagi merujuk => `reserved`,
// bukan dibuang. Gate (d) menolak entri `active` yang yatim.
for (const id of ['help-search', 'platform-mosques', 'platform-users']) {
    const e = cariEntri(id);
    if (!e) throw new Error(`registri: ${id} tiada`);
    if (e.status !== 'active') throw new Error(`registri: ${id} status ${e.status}, dijangka active`);
    e.status = 'reserved';
    console.log(`registri ${id.padEnd(24)} active -> reserved (yatim selepas pertukaran)`);
}

const BAHARU = [
    {
        id: 'platform-mosques-actions',
        family: 'admin',
        route: '/admin/mosques',
        owner_source: 'resources/js/help/page-target-plan.js (sel tindakan baris pertama)',
        selector_hint: '[data-help-target="platform-mosques-actions"]',
        viewport: 'both',
        state: 'perlu sekurang-kurangnya satu baris tenant',
        permission: 'superadmin',
        status: 'active',
        since: '2026-08-08',
    },
    {
        id: 'platform-users-actions',
        family: 'admin',
        route: '/admin/users',
        owner_source: 'resources/js/help/page-target-plan.js (sel tindakan baris pertama)',
        selector_hint: '[data-help-target="platform-users-actions"]',
        viewport: 'both',
        state: 'perlu sekurang-kurangnya satu baris pengguna',
        permission: 'superadmin',
        status: 'active',
        since: '2026-08-08',
    },
];

for (const b of BAHARU) {
    if (cariEntri(b.id)) throw new Error(`registri: ${b.id} sudah wujud`);
    entri.push(b);
    console.log(`registri ${b.id.padEnd(24)} DITAMBAH (active)`);
}

entri.sort((a, b) => a.id.localeCompare(b.id));
tulis(T, t);

console.log('\nsiap — jalankan penjanaan manifest + HELP-TARGETS.md selepas ini');
