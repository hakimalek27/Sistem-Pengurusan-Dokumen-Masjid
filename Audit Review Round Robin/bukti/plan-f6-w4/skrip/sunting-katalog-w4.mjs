// F6-W4 langkah 3 — sunting 82 sasaran langkah generik shard `workflow`.
// Round-trip WAJIB `JSON.stringify(d, null, 2) + '\n'` (json_encode PHP memecahkan fail).
import { readFileSync, writeFileSync } from 'node:fs';

const LALUAN = 'resources/help/guides.json';
const VERSI_BAHARU = '2026.08.06.1';

// Kunci = <guide_id tanpa awalan 'workflow.'> ; nilai = { indeks: sasaran }.
// `JUSTIFY` bermakna langkah itu KEKAL generik dan menerima justifikasi bertarikh.
const JUSTIFY = Symbol('justify');
const PETA = {
    'admin_masjid.muat-naik-semak-dan-klasifikasikan-dokumen-serta-hantar-minit': {
        1: 'nav-primary',       // "Sahkan tenant MAM/data masjid sendiri." (papan pemuka)
        2: 'nav-primary',       // "Pada menu kiri, tekan Peti Masuk."
        3: 'inbox-record',      // "Semak sumber, pengirim, tarikh dan masa diterima."
        4: 'inbox-scan-status', // "Semak Antivirus, OCR dan amaran Duplikat."
        15: 'minit-record',     // "Buka Minit Saya." (tour sudah mendarat di sini)
        16: 'minit-filters',    // "Pilih kategori Saya Hantar."
        17: 'minit-record',     // "Sahkan rekod, penerima, arahan, keutamaan…"
        18: 'log-detail',       // "Buka Log Aktiviti Masjid."
        19: 'log-search',       // "Cari tajuk rekod."
        20: 'log-detail',       // "Sahkan urutan record_uploaded, record_classified…"
    },
    'admin_masjid.betulkan-rekod-salah-tawan-tanpa-memadam-sejarah': {
        1: 'records-search',
        2: 'records-view',
        8: 'correction-diff',
        9: 'correction-decision',
        10: 'correction-status',
        11: 'log-search',
        12: 'log-detail',
        13: 'log-detail',       // "Pastikan tiada perubahan senyap tanpa log."
    },
    'admin_masjid.urus-fail-fizikal-atau-hibrid-dan-jejak-penjagaan': {
        1: 'regfiles-search',
        2: 'regfiles-medium',
        3: 'regfiles-view',
        12: 'log-filters',      // "Tapis jenis aktiviti fail fizikal."
        13: 'log-detail',
    },
    'admin_masjid.sediakan-dan-laksanakan-pelupusan-terkawal': {
        1: 'retention-schedule',
        2: 'retention-hold',
        3: 'retention-export',  // admin_masjid DIUKUR ada export.create
        4: 'disposal-candidates',
        5: 'disposal-batches',  // "Jangan cipta batch pendua." (lihat batch sedia ada)
        9: 'disposal-status',   // "Tunggu status Lulus."
        10: 'disposal-actions', // "Tekan Laksana sekali."
        11: 'disposal-actions', // "Muat turun sijil apabila status Selesai."
        12: 'log-detail',
        13: 'log-detail',
    },
    'pengerusi.terima-baca-balas-dan-selesaikan-minit': {
        1: 'minit-filters',
        2: 'minit-record',
        3: 'minit-record',
        11: 'log-search',
        12: 'log-detail',
    },
    'pengerusi.buat-keputusan-kelulusan-atau-pelupusan': {
        1: 'approval-record',
        2: 'approval-record',
        6: 'disposal-batches',
        7: 'disposal-actions',
        8: 'log-filters',
        9: 'log-detail',
    },
    'setiausaha.klasifikasikan-surat-masuk-dan-edarkan-minit': {
        1: 'nav-primary',
        2: 'nav-primary',
        3: 'inbox-record',
        10: 'minit-filters',
        11: 'minit-status',
        12: 'log-search',
        13: 'log-detail',
    },
    'setiausaha.mohon-kelulusan-dan-pembetulan-rekod': {
        1: 'records-search',
        2: 'records-view',
        7: 'approval-status',
        8: 'approval-status',
        9: 'log-search',
        10: 'log-detail',
    },
    'bendahari.urus-rekod-kewangan-dan-minit': {
        1: 'records-search',
        2: 'records-view',
        8: 'log-search',
        9: 'log-detail',
        10: JUSTIFY,            // "Rekod pentadbiran sulit di luar akses tidak akan dipulangkan."
    },
    'bendahari.mohon-storan-tambahan': {
        1: 'storage-usage',
        2: 'storage-orders',
        7: 'log-filters',
        8: 'log-detail',
        9: 'log-detail',
    },
    'nazir.proses-minit-dan-keputusan-kelulusan': {
        1: 'minit-filters',
        2: 'minit-record',
        5: 'approval-record',
        6: 'approval-record',
    },
    'ketua_imam.laksanakan-arahan-minit': {
        1: 'minit-filters',
        2: 'minit-record',
    },
    'ajk.baca-rekod-dan-selesaikan-tugasan-minit': {
        1: 'minit-filters',
        2: 'minit-record',
    },
    'audit.laksanakan-semakan-audit-baca-sahaja': {
        1: 'search-filters',
        2: 'search-favourite',
        3: 'search-result-open',
        8: 'sensitive-log-record',
        9: JUSTIFY,             // "Bandingkan dengan skop audit." (kerja di luar sistem)
        10: 'report-summary',
        11: JUSTIFY,            // audit DIUKUR tiada export.create -> butang tidak dirender
    },
};

const GENERIK = new Set(['page-content', 'page-primary']);
const d = JSON.parse(readFileSync(LALUAN, 'utf8'));
const ikutId = new Map(d.guides.map((g) => [g.id, g]));

let ditukar = 0;
let dijustifikasi = 0;
const ralat = [];
const justifikasiDiperlukan = [];

for (const [suffix, peta] of Object.entries(PETA)) {
    const id = 'workflow.' + suffix;
    const g = ikutId.get(id);
    if (!g) { ralat.push('guide tiada: ' + id); continue; }

    // Setiap langkah generik guide ini MESTI ada dalam peta — kalau tidak, wave tidak akan tutup.
    const generikSebenar = g.steps
        .map((s, i) => (GENERIK.has(s.target) ? i + 1 : null))
        .filter(Boolean);
    const dipeta = Object.keys(peta).map(Number).sort((a, b) => a - b);
    const hilang = generikSebenar.filter((n) => !dipeta.includes(n));
    const lebihan = dipeta.filter((n) => !generikSebenar.includes(n));
    if (hilang.length) ralat.push(`${id}: langkah generik TIDAK dipeta: ${hilang.join(',')}`);
    if (lebihan.length) ralat.push(`${id}: peta merujuk langkah BUKAN generik: ${lebihan.join(',')}`);

    for (const [n, sasaran] of Object.entries(peta)) {
        const step = g.steps[Number(n) - 1];
        if (!step) { ralat.push(`${id}#${n}: langkah tiada`); continue; }
        if (sasaran === JUSTIFY) {
            justifikasiDiperlukan.push(`${id}#${n}`);
            dijustifikasi += 1;
            continue;
        }
        step.target = sasaran;
        ditukar += 1;
    }
}

if (ralat.length) {
    console.error('GAGAL — tiada fail ditulis:');
    for (const r of ralat) console.error('  ' + r);
    process.exit(1);
}

d.catalog_version = VERSI_BAHARU;
writeFileSync(LALUAN, JSON.stringify(d, null, 2) + '\n');

console.log('sasaran ditukar      :', ditukar);
console.log('kekal generik (justify):', dijustifikasi, '->', justifikasiDiperlukan.join(' '));
console.log('catalog_version      :', VERSI_BAHARU);
