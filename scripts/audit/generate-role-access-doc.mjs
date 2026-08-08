// F8 §9 gate #6 (P14-03) — jana `docs/AKSES-PAGE-MENGIKUT-ROLE.md` daripada manifest
// `role_routes`, supaya kiraan halaman per role mempunyai SATU sumber kebenaran.
//
// Keadaan sebelum ini, DIUKUR (bukan diandaikan):
//   `e2e/guidance.spec.js:16-18` sudah membaca `expected_page_counts` daripada manifest — sisi
//   itu ditutup di F0. Yang tinggal ialah `AKSES-PAGE-MENGIKUT-ROLE-PRODUCTION-2026-07-21.md`,
//   yang membawa kiraan BERTULIS daripada crawl produksi 21 Julai.
//
// ⚠️ Beza antara dokumen itu dan manifest BUKAN percanggahan, dan itu dibuktikan:
//   dokumen ada 21 halaman admin, manifest 25 · tambahan 4 · HILANG 0.
//   Keempat-empat tambahan (`/bantuan`, `/analitik-bantuan`, `/log-aktiviti`, `/tiket-sokongan`)
//   ditambah pada 2026-07-22 (`f9e4e09`, `b9a5c30`) — SEHARI selepas crawl itu.
//   Jadi dokumen 21 Julai ialah rekod SEJARAH yang betul dan TIDAK diubah oleh skrip ini.
//
// Skrip ini menulis dokumen BAHARU yang dijana, dan `tests/Feature/RoleAccessDocTest.php`
// menjadikannya gagal-tertutup terhadap drift masa depan.
//
// Guna: node scripts/audit/generate-role-access-doc.mjs

import { readFileSync, writeFileSync } from 'node:fs';

const MANIFEST = 'Audit Review Round Robin/bukti/plan-baseline/manifest.json';
const KELUAR = 'docs/AKSES-PAGE-MENGIKUT-ROLE.md';

const m = JSON.parse(readFileSync(MANIFEST, 'utf8'));
const rr = m.role_routes;

const LABEL = {
    superadmin: 'Superadmin (Pentadbir Platform)',
    admin_masjid: 'Admin / Kerani',
    pengerusi: 'Pengerusi',
    setiausaha: 'Setiausaha',
    bendahari: 'Bendahari',
    nazir: 'Nazir',
    ketua_imam: 'Ketua Imam',
    ajk: 'AJK',
    audit: 'Juruaudit',
    public: 'Orang Awam (tidak log masuk)',
};

// ⚠️ Kiraan beku `expected_page_counts` ialah PANEL-SKOP, dan itu diukur bukan diandaikan:
// superadmin memberi 25 pada panel `app` + 12 pada panel `admin` = 37, sedangkan nilai beku
// ialah 25. `guidance.spec.js` mengira melalui `visibleNavigation()` yang membaca
// `.fi-sidebar a[href]` panel TENANT, jadi 25 = panel `app`. Menjumlahkan dua panel akan
// menghasilkan dokumen yang bercanggah dengan manifestnya sendiri.
/** Halaman navigasi yang dibenarkan bagi satu identiti, ditapis ikut panel. */
function halamanNav(identity, panel = null) {
    return rr.entries
        .filter((e) => e.identity === identity && e.expected_access === 'allow' && e.in_navigation
            && (panel === null || e.panel === panel))
        .map((e) => e.route_template)
        .sort((a, b) => a.localeCompare(b));
}

const urutan = ['superadmin', 'admin_masjid', 'pengerusi', 'setiausaha', 'bendahari', 'nazir',
    'ketua_imam', 'ajk', 'audit', 'public'];

const baris = [];
baris.push('# Senarai Page Mengikut Role — DIJANA');
baris.push('');
baris.push('> ⚠️ **Fail ini DIJANA.** Jangan sunting dengan tangan.');
baris.push('> Sumber: `Audit Review Round Robin/bukti/plan-baseline/manifest.json` → `role_routes`.');
baris.push('> Jana semula: `node scripts/audit/generate-role-access-doc.mjs`');
baris.push('> Penjaga: `tests/Feature/RoleAccessDocTest.php` (gagal jika fail ini menyimpang).');
baris.push('');
baris.push(`**Sistem:** Diwan / SPDM · **catalog_version manifest:** \`${m.catalog_version}\``);
baris.push(`**Identiti:** ${rr.identities?.length ?? urutan.length} · **Entri route:** ${rr.entries.length}`);
baris.push('');
baris.push('Definisi "page terlihat" = route yang `expected_access = allow` **dan** muncul dalam');
baris.push('navigasi (`in_navigation`). Butang, modal dan tindakan DALAM sesuatu page masih');
baris.push('tertakluk kepada permission, policy, status rekod, sensitiviti dokumen dan keahlian');
baris.push('tenant — dokumen ini tidak membuat dakwaan tentangnya.');
baris.push('');
baris.push('## Ringkasan');
baris.push('');
baris.push('⚠️ Kiraan beku manifest ialah **panel `app`** — `guidance.spec.js` mengiranya melalui');
baris.push('sidebar panel tenant. Superadmin turut mempunyai halaman panel `admin`; ia dilajurkan');
baris.push('berasingan supaya tiada nombor dalam dokumen ini bercanggah dengan manifestnya.');
baris.push('');
baris.push('| Role | Panel `app` | Panel `admin` | Kiraan beku (`app`) | Sepadan |');
baris.push('|---|---:|---:|---:|---|');

let jumlah = 0;
for (const id of urutan) {
    const nApp = halamanNav(id, 'app').length;
    const nAdmin = halamanNav(id, 'admin').length;
    const beku = rr.expected_page_counts?.[id];
    if (id !== 'public') jumlah += nApp;
    const sepadan = beku === undefined ? '—' : (nApp === beku ? '✔' : '✘');
    baris.push(`| ${LABEL[id] ?? id} | ${nApp} | ${nAdmin} | ${beku ?? '—'} | ${sepadan} |`);
}
baris.push(`| **Jumlah panel \`app\` (tanpa awam)** | **${jumlah}** | | | |`);
baris.push('');
baris.push('## Perbandingan dengan crawl produksi 21 Julai 2026');
baris.push('');
baris.push('`AKSES-PAGE-MENGIKUT-ROLE-PRODUCTION-2026-07-21.md` ialah rekod **bertarikh** bagi');
baris.push('crawl produksi dan **tidak diubah**. Kiraannya lebih rendah kerana empat halaman');
baris.push('ditambah pada **2026-07-22** — sehari selepas crawl itu:');
baris.push('');
baris.push('```');
baris.push('/app/{tenant}/bantuan            f9e4e09  2026-07-22');
baris.push('/app/{tenant}/analitik-bantuan   f9e4e09  2026-07-22');
baris.push('/app/{tenant}/tiket-sokongan     f9e4e09  2026-07-22');
baris.push('/app/{tenant}/log-aktiviti       b9a5c30  2026-07-22');
baris.push('```');
baris.push('');
baris.push('Diukur: halaman dalam dokumen 21 Julai yang **tiada** dalam manifest = **0**.');
baris.push('Jadi kedua-duanya konsisten; bezanya masa, bukan percanggahan.');
baris.push('');

for (const id of urutan) {
    const app = halamanNav(id, 'app');
    const admin = halamanNav(id, 'admin');
    baris.push(`## ${LABEL[id] ?? id} — ${app.length} page (\`app\`)${admin.length ? ` + ${admin.length} (\`admin\`)` : ''}`);
    baris.push('');
    if (!app.length && !admin.length) {
        baris.push('_Tiada halaman navigasi._');
    } else {
        if (app.length) {
            baris.push('Panel `app`:');
            app.forEach((r, i) => baris.push(`${i + 1}. \`${r}\``));
        }
        if (admin.length) {
            baris.push('');
            baris.push('Panel `admin`:');
            admin.forEach((r, i) => baris.push(`${i + 1}. \`${r}\``));
        }
    }
    baris.push('');
}

writeFileSync(KELUAR, baris.join('\n'));
console.log(`OK: ${KELUAR} dijana — ${urutan.length} identiti, jumlah panel app (tanpa awam) ${jumlah}`);
let gagal = 0;
for (const id of urutan) {
    const nApp = halamanNav(id, 'app').length;
    const nAdmin = halamanNav(id, 'admin').length;
    const beku = rr.expected_page_counts?.[id];
    const tanda = beku === undefined ? '?' : (nApp === beku ? 'OK' : `BEZA (beku ${beku})`);
    if (beku !== undefined && nApp !== beku) gagal += 1;
    console.log(`  ${id.padEnd(14)} app=${String(nApp).padStart(3)} admin=${String(nAdmin).padStart(3)}  ${tanda}`);
}
if (gagal) { console.error(`\nGAGAL: ${gagal} identiti tidak sepadan kiraan beku manifest.`); process.exit(1); }
console.log('\nSemua identiti sepadan kiraan beku manifest (panel app).');
