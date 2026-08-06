// F6-W5 langkah 4 — sunting katalog SATU BATCH mengikut `peta-w5.mjs`.
//
// Jalankan dari root repo:
//   node "Audit Review Round Robin/bukti/plan-f6-w5/skrip/sunting-katalog-w5.mjs"
//
// ⚠️ Wave DIKIRA daripada katalog, bukan disimpan. Menyunting satu guide sahaja akan
// memindahkannya keluar dari W5 dan memecahkan penjaga denominator beku — sebab itu
// KESELURUHAN wave mesti disunting dalam satu larian (pelajaran W2).
import { readFileSync, writeFileSync } from 'node:fs';
import { JUSTIFIKASI, PETA } from './peta-w5.mjs';

const KATALOG = 'resources/help/guides.json';
const VERSI_BAHARU = '2026.08.07.1';
const GENERIK = new Set(['page-content', 'page-primary']);

const mentah = readFileSync(KATALOG, 'utf8');
const doc = JSON.parse(mentah);

// Bukti round-trip SEBELUM menyentuh apa-apa: jika penulis semula tidak identik, diff akan
// meliputi seluruh fail dan menyembunyikan perubahan sebenar (pelajaran W2: json_encode PHP).
const rt = JSON.stringify(doc, null, 2) + '\n';
if (rt !== mentah) {
    throw new Error('Round-trip JSON TIDAK identik — jangan tulis; siasat format fail dahulu.');
}

const guides = new Map(doc.guides.map((g) => [g.id, g]));
let dinaikkan = 0;
let dijustifikasikan = 0;
const masalah = [];

for (const [guideId, peta] of Object.entries(PETA)) {
    const guide = guides.get(guideId);
    if (!guide) {
        masalah.push(`guide tiada dalam katalog: ${guideId}`);
        continue;
    }

    for (const [nStr, keputusan] of Object.entries(peta)) {
        const n = Number(nStr);
        const step = guide.steps[n - 1];
        if (!step) {
            masalah.push(`${guideId}#${n}: langkah tiada`);
            continue;
        }
        if (!GENERIK.has(step.target)) {
            masalah.push(`${guideId}#${n}: bukan generik (${step.target}) — peta lapuk`);
            continue;
        }

        if (keputusan === null) {
            const kunci = `${guideId}#${n}`;
            if (!JUSTIFIKASI[kunci]) masalah.push(`${kunci}: justifikasi tiada`);
            dijustifikasikan += 1;
            continue;
        }

        const [target, route] = keputusan;
        step.target = target;
        if (route) step.route = route;
        dinaikkan += 1;
    }
}

// Setiap langkah generik W5 mesti mempunyai keputusan — tiada yang terlepas senyap.
const manifestWave = JSON.parse(readFileSync('Audit Review Round Robin/bukti/plan-baseline/manifest.json', 'utf8'));
const w5 = new Set(manifestWave.catalogue.filter((g) => g.wave === 'W5').map((g) => g.guide_id));
for (const id of w5) {
    if (!PETA[id]) masalah.push(`guide W5 tiada dalam peta: ${id}`);
}

if (masalah.length) {
    console.error('GAGAL — tiada fail ditulis:\n  ' + masalah.join('\n  '));
    process.exit(1);
}

doc.catalog_version = VERSI_BAHARU;
writeFileSync(KATALOG, JSON.stringify(doc, null, 2) + '\n');

const bakiGenerik = doc.guides.flatMap((g) => g.steps).filter((s) => GENERIK.has(s.target)).length;
console.log(`↑ dinaikkan ${dinaikkan} · ≡ dijustifikasikan ${dijustifikasikan}`);
console.log(`catalog_version → ${VERSI_BAHARU}`);
console.log(`langkah generik BAKI seluruh katalog: ${bakiGenerik}`);
