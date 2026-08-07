// F6-W6 — sunting katalog + registri untuk dua langkah generik terakhir.
//
// Round-trip JSON mesti guna Node `JSON.stringify(d, null, 2) + "\n"`: `json_encode` PHP
// memecahkan seluruh fail (366KB lawan 300KB) — pelajaran W2.
// Skrip ini IDEMPOTEN: jalankan semula tidak mengubah apa-apa selepas kali pertama.

import { readFileSync, writeFileSync } from 'node:fs';

const HARI_INI = '2026-08-08';
const KATALOG = 'resources/help/guides.json';
const REGISTRI = 'resources/help/targets.json';

// ⚠️ Kedua-dua fail JSON tidak berkongsi indentasi: `guides.json` = 2 ruang,
// `targets.json` = 4. Menulis kedua-duanya dengan indentasi yang sama menghasilkan diff
// SELURUH FAIL (5,410 baris untuk penambahan 2 entri) yang menyembunyikan perubahan sebenar
// daripada semakan. Ukur, jangan andaikan — keluarga sama seperti perangkap CRLF W2.
const INDEN = { [KATALOG]: 2, [REGISTRI]: 4 };
const tulisJson = (laluan, data) =>
    writeFileSync(laluan, `${JSON.stringify(data, null, INDEN[laluan])}
`);

const PETA = {
    'public.help': { 1: 'help-search-form', 2: 'help-scope' },
};

const SASARAN_BAHARU = [
    {
        id: 'help-search-form',
        family: 'public|tenant|admin',
        route: '/bantuan|/app/{tenant}/bantuan|/admin/bantuan',
        owner_source: 'resources/views/livewire/help-center.blade.php:45',
        selector_hint: '[data-help-target="help-search-form"]',
        viewport: 'both',
        state: '-',
        permission: '-',
        status: 'active',
        since: HARI_INI,
    },
    {
        id: 'help-scope',
        family: 'public|tenant|admin',
        route: '/bantuan|/app/{tenant}/bantuan|/admin/bantuan',
        owner_source: 'resources/views/livewire/help-center.blade.php:36',
        selector_hint: '[data-help-target="help-scope"]',
        viewport: 'both',
        state: '-',
        permission: '-',
        status: 'active',
        since: HARI_INI,
    },
];

// ── registri ───────────────────────────────────────────────────────────────────────────
const registri = JSON.parse(readFileSync(REGISTRI, 'utf8'));
const sediaAda = new Set(registri.targets.map((t) => t.id));
let ditambah = 0;
for (const t of SASARAN_BAHARU) {
    if (sediaAda.has(t.id)) continue;
    registri.targets.push(t);
    ditambah += 1;
}
registri.targets.sort((a, b) => a.id.localeCompare(b.id)); // fail memang tersusun ikut id
writeFileSync(REGISTRI, `${JSON.stringify(registri, null, 2)}\n`);

// ── katalog ────────────────────────────────────────────────────────────────────────────
const katalog = JSON.parse(readFileSync(KATALOG, 'utf8'));
let diubah = 0;
for (const [guideId, langkah] of Object.entries(PETA)) {
    const g = katalog.guides.find((x) => x.id === guideId);
    if (!g) throw new Error(`guide ${guideId} tiada dalam katalog`);
    for (const [idxStr, target] of Object.entries(langkah)) {
        const step = g.steps[Number(idxStr) - 1];
        if (!step) throw new Error(`${guideId}#${idxStr} tiada`);
        if (step.target === target) continue;
        step.target = target;
        diubah += 1;
    }
}

if (diubah > 0) {
    // `catalog_version` dibump SEKALI (bukan versi per-guide) — §4 F3; deploy mesti
    // menjalankan `diwan:sync-help-index --delete` selepasnya.
    katalog.catalog_version = '2026.08.08.1';
    writeFileSync(KATALOG, `${JSON.stringify(katalog, null, 2)}\n`);
}

// ── laporan ────────────────────────────────────────────────────────────────────────────
const GENERIK = new Set(['page-content', 'page-primary']);
const semak = JSON.parse(readFileSync(KATALOG, 'utf8'));
let generik = 0;
let langkahJumlah = 0;
for (const g of semak.guides) {
    for (const s of g.steps) {
        langkahJumlah += 1;
        if (GENERIK.has(s.target)) generik += 1;
    }
}
const reg = JSON.parse(readFileSync(REGISTRI, 'utf8'));
console.log(JSON.stringify({
    registri_ditambah: ditambah,
    langkah_diubah: diubah,
    catalog_version: semak.catalog_version,
    guide: semak.guides.length,
    langkah: langkahJumlah,
    generik_baki: generik,
    registri_jumlah: reg.targets.length,
    registri_aktif: reg.targets.filter((t) => t.status === 'active').length,
}, null, 2));
