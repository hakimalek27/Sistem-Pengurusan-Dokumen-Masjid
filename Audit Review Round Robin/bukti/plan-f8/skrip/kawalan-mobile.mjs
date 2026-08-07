// TENTUKUR metrik sebelum melaporkan kemajuan (pelajaran F5: "jika alat anda tidak
// menghasilkan semula angka asas, alat itu salah — bukan datanya").
//
// `centerCovered` masih 6/6 selepas W0. Soalannya: adakah metrik itu MEMBEZAKAN kecacatan,
// atau adakah ia benar untuk mana-mana popover pada skrin 390x664? Kawalan = langkah yang
// TIDAK PERNAH tersenarai sebagai cacat dalam audit.

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);
import { readFileSync } from 'node:fs';

const BASE = 'http://127.0.0.1:8092';
const TENANT = 'mam';

// Rekod audit: berapa banyak langkah mobile yang diukur, dan berapa yang centerCovered?
const audit = JSON.parse(readFileSync(
    'Audit Review Round Robin/bukti/pusingan-11-codex/production-mobile-all-tour-steps.json', 'utf8'));
const arr = Array.isArray(audit) ? audit : (audit.steps || Object.values(audit)[0]);
const adaPop = arr.filter((x) => x.popSize);
console.log(`AUDIT: ${arr.length} langkah diukur · ${adaPop.length} ada popover · `
    + `${arr.filter((x) => x.centerCovered).length} centerCovered`);
console.log('  contoh yang TIDAK centerCovered:');
for (const x of arr.filter((x) => x.popSize && !x.centerCovered).slice(0, 4)) {
    console.log(`    ${x.guide}#${x.index} pop=${x.popSize.w}x${x.popSize.h}@${x.popSize.left},${x.popSize.top}`);
}

const pelayar = await chromium.launch();
const k = await pelayar.newContext({ viewport: { width: 390, height: 664 } });
await k.addInitScript(() => { try { localStorage.setItem('diwan-help-mode', 'dimatikan'); } catch { /* noop */ } });
const p = await k.newPage();
await p.goto(`${BASE}/app/login`);
await p.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
await p.locator('input[type="password"]').fill('password');
await p.getByRole('button', { name: /Log masuk/i }).click();
await p.waitForURL((u) => u.pathname.replace(/\/$/, '') === `/app/${TENANT}`, { timeout: 90_000 });

const KAWALAN = [
    ['tenant.dashboard', '', 0], ['tenant.dashboard', '', 1],
    ['tenant.records', 'records', 0], ['tenant.peti-masuk', 'peti-masuk', 0],
    ['tenant.carian', 'carian', 0], ['tenant.bantuan', 'bantuan', 0],
];

console.log('\nKAWALAN (langkah yang TIDAK pernah tersenarai cacat):');
let tertutup = 0;
for (const [guide, path, i] of KAWALAN) {
    const url = `${BASE}/app/${TENANT}${path ? '/' + path : ''}?panduan=${guide}&langkah=${i}`;
    for (let c = 1; ; c += 1) {
        try { await p.goto(url, { waitUntil: 'domcontentloaded' }); break; }
        catch (e) { if (c >= 4) throw e; await p.waitForTimeout(1500); }
    }
    await p.waitForTimeout(3000);
    const r = await p.evaluate(() => {
        const pop = document.querySelector('.driver-popover');
        if (!pop) return { ada: false };
        const b = pop.getBoundingClientRect();
        const cx = innerWidth / 2; const cy = innerHeight / 2;
        return {
            ada: true, cc: b.left <= cx && b.right >= cx && b.top <= cy && b.bottom >= cy,
            pop: `${Math.round(b.width)}x${Math.round(b.height)}@${Math.round(b.left)},${Math.round(b.top)}`,
            t: document.querySelector('.driver-active-element')?.getAttribute('data-help-target') ?? null,
        };
    });
    if (r.cc) tertutup += 1;
    console.log(`  ${r.ada ? (r.cc ? '🔴' : '✅') : '·'} ${`${guide}#${i + 1}`.padEnd(24)} `
        + `cc=${r.cc} target=${r.t} pop=${r.pop ?? '-'}`);
}
console.log(`\nkawalan centerCovered: ${tertutup}/${KAWALAN.length}`);
await pelayar.close();
