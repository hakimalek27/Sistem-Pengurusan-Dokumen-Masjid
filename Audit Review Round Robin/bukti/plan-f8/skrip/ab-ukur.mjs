// Separuh ukur bagi A/B `centerCovered`. Set langkah DIPAKU (24 langkah yang wujud dalam
// KEDUA-DUA katalog) supaya kedua-dua belah mengukur perkara yang sama.
//
// 🔴 Versi pertama menulis ke `/tmp` dan saya menyalinnya ke folder bukti dengan `cp … 2>/dev/null`
// yang MENELAN kegagalannya. Fail tidak pernah tiba, `/tmp` kemudian dibersihkan, dan
// SUSULAN-PEMBAIKAN.md menamakan dua artifak yang TIDAK WUJUD. Codex pusingan 1 menangkapnya
// (#22). Kini ia menulis terus ke folder bukti — tiada langkah salinan untuk gagal secara senyap.
//
// PEMILIHAN SAMPEL (Codex #22 menuntutnya dinyatakan): 8 guide tenant × 3 langkah pertama.
// Kriteria: guide yang mempunyai >=3 langkah dalam KEDUA-DUA katalog (lama `9619509` dan
// semasa), merentas jenis halaman berbeza (papan pemuka, senarai, borang, laporan). Ia sampel
// BERTUJUAN untuk mengasingkan satu pemboleh ubah, bukan sampel wakil untuk mendakwa kadar.

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);
import { readFileSync, writeFileSync } from 'node:fs';

// ── Provenance (Codex P2 #1/#17) ──────────────────────────────────────────────────────────
// Artifak tanpa provenance tidak boleh diaudit: dua larian versi berbeza boleh dicampur dan
// tetap kelihatan lengkap. Setiap fail ukuran kini membawa commit, versi+hash katalog, tenant,
// base URL, viewport dan masa.
async function provenance(extra = {}) {
    const { execSync } = await import('node:child_process');
    const { createHash } = await import('node:crypto');
    let commit = 'tidak-diketahui';
    try { commit = execSync('git rev-parse --short HEAD', { encoding: 'utf8' }).trim(); } catch { /* noop */ }
    // ⚠️ JANGAN telan kegagalan ini. Versi katalog ialah medan provenance yang PALING penting
    // (bagi A/B ia satu-satunya yang membuktikan fail `ab-lama` benar-benar sisi katalog lama).
    // Versi pertama membalutnya dalam try/catch dan menghasilkan `katalog_version: null` secara
    // senyap kerana `readFileSync` tidak diimport — provenance yang gagal senyap lebih buruk
    // daripada tiada provenance.
    const mentah = readFileSync('resources/help/guides.json', 'utf8');
    const katalogVersi = JSON.parse(mentah).catalog_version ?? null;
    const katalogHash = createHash('sha256').update(mentah).digest('hex').slice(0, 16);
    if (!katalogVersi) throw new Error('provenance: catalog_version tidak dapat dibaca');

    return { commit, katalog_version: katalogVersi, katalog_sha256_16: katalogHash,
        masa: new Date().toISOString(), ...extra };
}

const BASE = 'http://127.0.0.1:8092';
const T = 'mam';
const LABEL = process.env.AB_LABEL || 'x';

// 24 langkah merentas 8 guide tenant — dipilih kerana indeksnya wujud dalam katalog LAMA
// (semua guide ini sekurang-kurangnya 3 langkah dalam kedua-dua versi).
const SET = [
    ['tenant.dashboard', '', [1, 2, 3]],
    ['tenant.records', 'records', [1, 2, 3]],
    ['tenant.peti-masuk', 'peti-masuk', [1, 2, 3]],
    ['tenant.carian', 'carian', [1, 2, 3]],
    ['tenant.retensi', 'retensi', [1, 2, 3]],
    ['tenant.profil', 'profil', [1, 2, 3]],
    ['tenant.penggunaan', 'penggunaan', [1, 2, 3]],
    ['tenant.laporan', 'laporan', [1, 2, 3]],
];

const pelayar = await chromium.launch();
const k = await pelayar.newContext({ viewport: { width: 390, height: 664 } });
await k.addInitScript(() => { try { localStorage.setItem('diwan-help-mode', 'dimatikan'); } catch { /* noop */ } });
const p = await k.newPage();
await p.goto(`${BASE}/app/login`);
await p.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
await p.locator('input[type="password"]').fill('password');
await p.getByRole('button', { name: /Log masuk/i }).click();
await p.waitForURL((u) => u.pathname.replace(/\/$/, '') === `/app/${T}`, { timeout: 90_000 });

const hasil = [];
for (const [guide, path, idx] of SET) {
    for (const i of idx) {
        const url = `${BASE}/app/${T}${path ? '/' + path : ''}?panduan=${guide}&langkah=${i - 1}`;
        let r = { adaPopover: false };
        try {
            for (let c = 1; ; c += 1) {
                try { await p.goto(url, { waitUntil: 'domcontentloaded' }); break; }
                catch (e) { if (c >= 3) throw e; await p.waitForTimeout(1200); }
            }
            await p.waitForTimeout(2200);
            r = await p.evaluate(() => {
                const pop = document.querySelector('.driver-popover');
                if (!pop) return { adaPopover: false };
                const b = pop.getBoundingClientRect();
                const cx = innerWidth / 2; const cy = innerHeight / 2;
                return {
                    adaPopover: true,
                    cc: b.left <= cx && b.right >= cx && b.top <= cy && b.bottom >= cy,
                    top: Math.round(b.top), h: Math.round(b.height),
                    target: document.querySelector('.driver-active-element')?.getAttribute('data-help-target') ?? null,
                };
            });
        } catch { /* biar sebagai tiada popover */ }
        hasil.push({ key: `${guide}#${i}`, ...r });
    }
}

const cc = hasil.filter((h) => h.cc).length;
const ada = hasil.filter((h) => h.adaPopover).length;
console.log(`  [${LABEL}] centerCovered ${cc}/${ada} (daripada ${hasil.length} diukur)`);
for (const h of hasil) {
    console.log(`     ${h.cc ? '🔴' : (h.adaPopover ? '✅' : '· ')} ${h.key.padEnd(24)} target=${h.target ?? '-'} top=${h.top ?? '-'} h=${h.h ?? '-'}`);
}
writeFileSync(
    `Audit Review Round Robin/bukti/plan-f8/ab-${LABEL}.json`,
    JSON.stringify({
        provenance: await provenance({ tenant: T, base_url: BASE, viewport: '390x664' }),
        label: LABEL,
        pemilihan: '8 guide tenant × 3 langkah pertama; guide dengan >=3 langkah dalam KEDUA-DUA katalog',
        definisi: 'centerCovered = rect popover mengandungi (innerWidth/2, innerHeight/2) pada 390x664',
        cc, ada, jumlah: hasil.length, hasil,
    }, null, 2) + '\n',
);
await pelayar.close();
