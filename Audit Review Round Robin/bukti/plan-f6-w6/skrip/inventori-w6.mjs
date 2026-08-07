// F6-W6 — JANA inventori gelombang `public` daripada katalog + registri.
//
// ⚠️ PERATURAN YANG W5 BAYAR MAHAL: jadual inventori mesti DIJANA, jangan ditaip tangan.
// Inventori W5 versi pertama tersilap kira 16 langkah kerana ia ditaip. Skrip ini mencetak
// Markdown siap tampal, jadi tiada nombor yang pernah disentuh manusia.
//
// Guna: node "Audit Review Round Robin/bukti/plan-f6-w6/skrip/inventori-w6.mjs"

import { readFileSync } from 'node:fs';

const katalog = JSON.parse(readFileSync('resources/help/guides.json', 'utf8'));
const registri = JSON.parse(readFileSync('resources/help/targets.json', 'utf8'));
const manifes = JSON.parse(readFileSync(
    'Audit Review Round Robin/bukti/plan-baseline/manifest.json', 'utf8'));

const GENERIK = new Set(['page-content', 'page-primary']);

// Wave datang daripada MANIFEST (sumber kebenaran partition, §7.2 P16-05) — bukan daripada
// tekaan berdasarkan awalan id guide.
//
// ⚠️ `manifes.catalogue` ialah ARRAY entri guide (setiap satu membawa `steps`), bukan objek
// `{guides, steps}`. Skema disemak sebelum digunakan, bukan diandaikan.
const langkahManifes = new Map(
    manifes.catalogue.flatMap((g) => g.steps).map((s) => [s.key, s]));
const guideWave = new Map(
    manifes.catalogue.map((g) => [g.guide_id, g.wave]));

const w6 = katalog.guides.filter((g) => guideWave.get(g.id) === 'W6');

const daftar = new Map(registri.targets.map((t) => [t.id, t]));

let jumlahLangkah = 0;
let jumlahGenerik = 0;
const baris = [];

for (const g of w6) {
    g.steps.forEach((step, i) => {
        const idx = i + 1;
        const kunci = `${g.id}#${idx}`;
        const m = langkahManifes.get(kunci);
        jumlahLangkah += 1;
        const generik = GENERIK.has(step.target);
        if (generik) jumlahGenerik += 1;
        const entri = daftar.get(step.target);
        baris.push({
            kunci,
            guide: g.id,
            idx,
            route: step.route ?? g.route,
            target: step.target,
            generik,
            wait: Boolean(step.wait_for_user),
            status: m?.status ?? '(tiada dalam manifest)',
            shard: m?.shard ?? '?',
            registri: entri ? `${entri.status} · ${entri.state}` : '—',
            tajuk: step.title,
            arahan: step.instruction,
        });
    });
}

const esc = (s) => String(s ?? '').replace(/\|/g, '\\|');

console.log(`# INVENTORI F6-W6 (DIJANA — jangan sunting tangan)\n`);
console.log(`Dijana daripada \`resources/help/guides.json\`, \`resources/help/targets.json\``);
console.log(`dan \`plan-baseline/manifest.json\`. Katalog \`${katalog.catalog_version}\`.\n`);
console.log(`**Guide W6: ${w6.length} · langkah: ${jumlahLangkah} · bersasar generik: ${jumlahGenerik}**\n`);

for (const g of w6) {
    const rows = baris.filter((b) => b.guide === g.id);
    const gen = rows.filter((b) => b.generik).length;
    console.log(`## \`${g.id}\` — ${g.title}`);
    console.log(`route \`${g.route}\` · panel \`${g.panel}\` · roles \`${g.roles.join(',')}\``);
    console.log(`· ${rows.length} langkah · **${gen} generik**\n`);
    console.log('| # | sasaran | generik | wait | status | shard | registri | tajuk | arahan |');
    console.log('|---:|---|:---:|:---:|---|---|---|---|---|');
    for (const b of rows) {
        console.log(`| ${b.idx} | \`${b.target}\` | ${b.generik ? '**YA**' : '—'} | ${b.wait ? 'ya' : '—'} `
            + `| ${b.status} | ${b.shard} | ${esc(b.registri)} | ${esc(b.tajuk)} | ${esc(b.arahan)} |`);
    }
    console.log('');
}

console.log(`## Ringkasan mesin\n`);
console.log('```json');
console.log(JSON.stringify({
    guides: w6.length,
    steps: jumlahLangkah,
    generic: jumlahGenerik,
    generic_keys: baris.filter((b) => b.generik).map((b) => b.kunci),
    shards: [...new Set(baris.map((b) => b.shard))],
    routes: [...new Set(baris.map((b) => b.route))],
}, null, 2));
console.log('```');
