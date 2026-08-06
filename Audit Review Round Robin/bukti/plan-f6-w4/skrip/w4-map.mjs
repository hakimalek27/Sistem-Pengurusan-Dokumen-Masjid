// Inventori W4: petakan setiap langkah generik -> route ISYTIHAR -> sasaran yang ADA pada route itu.
// Sumber: guides.json (katalog) + targets.json (registri) + manifest.json (wave).
import { readFileSync } from 'node:fs';

const cat = JSON.parse(readFileSync('resources/help/guides.json', 'utf8'));
const reg = JSON.parse(readFileSync('resources/help/targets.json', 'utf8')).targets;
const man = JSON.parse(readFileSync('Audit Review Round Robin/bukti/plan-baseline/manifest.json', 'utf8'));

const w4 = new Set(man.catalogue.filter((g) => g.wave === 'W4').map((g) => g.guide_id));
const aktifPerRoute = new Map();
for (const t of reg) {
    if (t.status !== 'active') continue;
    if (!aktifPerRoute.has(t.route)) aktifPerRoute.set(t.route, []);
    aktifPerRoute.get(t.route).push(t);
}

const GENERIK = new Set(['page-content', 'page-primary']);
const rows = [];
for (const g of cat.guides.filter((x) => w4.has(x.id))) {
    g.steps.forEach((s, i) => {
        if (!GENERIK.has(s.target)) return;
        const route = s.route ?? g.route;
        rows.push({
            guide: g.id,
            n: i + 1,
            role: (g.roles ?? []).join(','),
            route,
            title: s.title,
            arahan: String(s.instruction).replace(/\s+/g, ' '),
            adaSasaran: (aktifPerRoute.get(route) ?? []).map((t) => t.id),
        });
    });
}

// Ringkasan per route
const perRoute = new Map();
for (const r of rows) {
    if (!perRoute.has(r.route)) perRoute.set(r.route, []);
    perRoute.get(r.route).push(r);
}

console.log('JUMLAH langkah generik W4 =', rows.length);
console.log();
console.log('=== BEBAN KERJA PER ROUTE (tempat tour SEBENARNYA berada) ===');
console.log('route'.padEnd(38) + 'lgkh  sasaran-aktif-sedia-ada');
const urut = [...perRoute.entries()].sort((a, b) => b[1].length - a[1].length);
for (const [route, list] of urut) {
    const ada = aktifPerRoute.get(route) ?? [];
    console.log('  ' + route.padEnd(36) + String(list.length).padStart(4) + '  ' + (ada.length ? ada.length + ': ' + ada.map((t) => t.id).join(' ') : '⚠️ TIADA — perlu sasaran BAHARU'));
}
console.log();
console.log('=== langkah tanpa sebarang sasaran pada routenya ===');
let kosong = 0;
for (const [route, list] of urut) {
    if ((aktifPerRoute.get(route) ?? []).length) continue;
    kosong += list.length;
    console.log('  ' + route + '  (' + list.length + ' langkah)');
    for (const r of list) console.log('      ' + r.guide.replace('workflow.', '').slice(0, 44).padEnd(46) + '#' + String(r.n).padStart(2) + '  ' + r.arahan.slice(0, 56));
}
console.log('  JUMLAH langkah pada route tanpa sasaran =', kosong);
console.log();
console.log('=== langkah pada route yang SUDAH ada sasaran (calon dinaikkan) =', rows.length - kosong, '===');
for (const [route, list] of urut) {
    const ada = aktifPerRoute.get(route) ?? [];
    if (!ada.length) continue;
    console.log('\n  --- ' + route + ' — sasaran: ' + ada.map((t) => t.id).join(', '));
    for (const r of list) console.log('      ' + r.guide.replace('workflow.', '').slice(0, 44).padEnd(46) + '#' + String(r.n).padStart(2) + '  ' + r.arahan.slice(0, 62));
}
