// F6-W4 langkah 3b — aktifkan sasaran yang katalog kini rujuk + tulis allowlist justifikasi.
import { readFileSync, writeFileSync } from 'node:fs';

const SINCE = '2026-08-06';

// ── 1. Registri: reserved -> active bagi sasaran yang katalog W4 rujuk ────────────────────
const REG = 'resources/help/targets.json';
const reg = JSON.parse(readFileSync(REG, 'utf8'));
const katalog = JSON.parse(readFileSync('resources/help/guides.json', 'utf8'));
const dirujuk = new Set(katalog.guides.flatMap((g) => g.steps.map((s) => s.target)));

let diaktifkan = [];
for (const t of reg.targets) {
    if (t.status !== 'reserved') continue;
    if (!dirujuk.has(t.id)) continue;
    t.status = 'active';
    t.since = SINCE;
    diaktifkan.push(t.id);
}
writeFileSync(REG, JSON.stringify(reg, null, 2) + '\n');
console.log('diaktifkan (' + diaktifkan.length + '):', diaktifkan.sort().join(' '));

const masihRizab = reg.targets.filter((t) => t.status === 'reserved' && dirujuk.has(t.id));
if (masihRizab.length) { console.error('RALAT: masih rizab tetapi dirujuk:', masihRizab.map((t) => t.id)); process.exit(1); }
const aktifYatim = reg.targets.filter((t) => t.status === 'active' && !dirujuk.has(t.id));
console.log('aktif tetapi TIDAK dirujuk katalog (mesti 0):', aktifYatim.length, aktifYatim.map((t) => t.id).join(' '));

// ── 2. Allowlist justifikasi per-langkah ────────────────────────────────────────────────
const JUS = 'resources/help/step-justifications.json';
const jus = JSON.parse(readFileSync(JUS, 'utf8'));
const baharu = [
    {
        key: 'workflow.bendahari.urus-rekod-kewangan-dan-minit#10',
        status: 'not-applicable',
        wave: 'W4',
        reason: 'Langkah menyatakan KESAN penapisan kebenaran terhadap hasil log: rekod pentadbiran sulit di luar akses bendahari tidak dipulangkan sama sekali, jadi tiada elemen DOM yang boleh disorot. Amaran konsep, bukan kawalan skrin.',
        since: SINCE,
    },
    {
        key: 'workflow.audit.laksanakan-semakan-audit-baca-sahaja#9',
        status: 'not-applicable',
        wave: 'W4',
        reason: 'Perbandingan dibuat terhadap dokumen skop audit di LUAR Diwan; tiada kawalan dalam sistem yang mewakili "skop audit", jadi tiada sasaran yang jujur. Sama seperti langkah kerja-luar-sistem yang W3 kelaskan.',
        since: SINCE,
    },
    {
        key: 'workflow.audit.laksanakan-semakan-audit-baca-sahaja#11',
        status: 'generic-justified',
        wave: 'W4',
        reason: 'Butang eksport /laporan (`report-export`) diauthorize oleh `export.create`, dan peranan `audit` DIUKUR tidak memilikinya (probe canIn 6 Ogos 2026), jadi butang itu tidak pernah dirender untuk khalayak guide ini. Arahan itu sendiri bersyarat: "Eksport hanya jika dibenarkan".',
        since: SINCE,
        followup: 'Jika peranan audit diberi export.create pada masa hadapan, tukar sasaran langkah ini kepada `report-export`.',
    },
];

const sedia = new Set(jus.justifications.map((j) => j.key));
for (const j of baharu) {
    if (sedia.has(j.key)) { console.log('  LANGKAU (sudah ada):', j.key); continue; }
    jus.justifications.push(j);
}
writeFileSync(JUS, JSON.stringify(jus, null, 2) + '\n');
console.log('justifikasi kini:', jus.justifications.length);
