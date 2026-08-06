// F6-W5 langkah 5 — tulis 46 entri allowlist justifikasi.
//
// Jalankan dari root repo:
//   node "Audit Review Round Robin/bukti/plan-f6-w5/skrip/justifikasi-w5.mjs"
import { readFileSync, writeFileSync } from 'node:fs';
import { JUSTIFIKASI, PETA } from './peta-w5.mjs';

const PATH = 'resources/help/step-justifications.json';
const SINCE = '2026-08-07';

// `not-applicable` = tindakan/konsep DI LUAR skrin ini (tiada elemen yang boleh mewakilinya).
// `generic-justified` = elemen WUJUD dalam produk tetapi tidak boleh dicapai oleh guide ini
// (modal/wizard/halaman butiran) — kekal calon untuk gelombang atau fasa kemudian.
const TIDAK_BERKENAAN = new Set([
    'tenant.sensitive-access-logs#4',
    'tenant.log-aktiviti#5',
    'tenant.penggunaan#5',
    'tenant.records#5',
    'tenant.minit-saya#5',
    'tenant.minit-saya#6',
    'tenant.kelulusan#5',
    'tenant.laporan#4',
    'tenant.classification-nodes#5',
    'tenant.tetapan-masjid#6',
    'tenant.delegasi#5',
    'admin.dashboard#3',
    'admin.mosques#3',
    'admin.users#3',
    'admin.storage-orders#3',
    'admin.status-sambungan#3',
    'admin.whatsapp-platform#3',
    'admin.tetapan-platform#3',
    'admin.profil-saya#3',
]);

/** Cadangan susulan bagi langkah yang elemennya WUJUD tetapi tidak boleh dicapai W5. */
const SUSULAN = {
    'tenant.kelulusan#1': 'F9 — `tenant.kelulusan` menyenaraikan KESEMUA lapan peranan, tetapi hanya pengerusi dan nazir memegang `approvals.decide`. Guide ini sepatutnya berperanan pelulus sahaja; sehingga itu ia sentiasa kosong untuk peranan lain. Penemuan kandungan, bukan pepijat kod.',
    'tenant.persediaan#2': 'F7/W6 — wizard persediaan ialah modal berlangkah. Memberi guide halaman ini langkah TINDAKAN akan mengubah kiraan `action_steps` yang dibekukan F0; keputusan itu milik fasa yang boleh mengemas denominator.',
};

const doc = JSON.parse(readFileSync(PATH, 'utf8'));
const sedia = new Map(doc.justifications.map((j) => [j.key, j]));

// Kunci W5 dikira daripada PETA supaya senarai ini TIDAK boleh menyimpang daripada katalog.
const kunciW5 = Object.entries(PETA).flatMap(([guideId, peta]) => Object.entries(peta)
    .filter(([, keputusan]) => keputusan === null)
    .map(([n]) => `${guideId}#${n}`));

const hilang = kunciW5.filter((k) => !JUSTIFIKASI[k]);
if (hilang.length) throw new Error('sebab justifikasi tiada: ' + hilang.join(', '));

let tambah = 0;
for (const key of kunciW5) {
    if (sedia.has(key)) {
        console.log(`LANGKAU (sudah ada): ${key}`);
        continue;
    }
    const entri = {
        key,
        status: TIDAK_BERKENAAN.has(key) ? 'not-applicable' : 'generic-justified',
        wave: 'W5',
        reason: JUSTIFIKASI[key],
        since: SINCE,
    };
    if (SUSULAN[key]) entri.followup = SUSULAN[key];
    doc.justifications.push(entri);
    tambah += 1;
}

const pendek = doc.justifications.filter((j) => j.reason.length < 40);
if (pendek.length) throw new Error('sebab < 40 aksara: ' + pendek.map((j) => j.key).join(', '));

writeFileSync(PATH, JSON.stringify(doc, null, 2) + '\n');
console.log(`\n+${tambah} entri W5 · jumlah ${doc.justifications.length}`);
