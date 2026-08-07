// Separuh ukur bagi A/B `centerCovered`. Set langkah DIPAKU (24 langkah yang wujud dalam
// KEDUA-DUA katalog) supaya kedua-dua belah mengukur perkara yang sama.

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);
import { writeFileSync } from 'node:fs';

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
writeFileSync(`/tmp/ab-${LABEL}.json`, JSON.stringify({ label: LABEL, cc, ada, hasil }, null, 2));
await pelayar.close();
