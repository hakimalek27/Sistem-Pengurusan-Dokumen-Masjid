// F8 §9 — ukur SEMULA metrik `centerCovered` mobile dengan kaedah audit yang SAMA.
//
// Manifest membekukan `mobile_defect` sebagai input audit (6 langkah), jadi membacanya semula
// hanya memulangkan 6 selama-lamanya — ia bukan ukuran keadaan semasa. §9 menuntut
// perbandingan apple-to-apple, jadi definisi audit ditiru TEPAT:
//
//   viewport 390x664 · pusat = (195, 332)
//   centerCovered = segi empat popover MENGANDUNGI titik pusat itu
//
// Disahkan terhadap rekod audit sebenar (`pusingan-11-codex/production-mobile-all-tour-steps.json`):
//   tenant.pelupusan#1 → popSize {w:366,h:327,left:12,top:327} → span y 327..654 → pusat DALAM.
//
// ⚠️ Penjaga W0 dalam `guidance.spec.js` mengukur perkara BERBEZA (popover menutup SASARANNYA
// sendiri) dan ia lebih ketat — tetapi ia bukan angka yang audit laporkan, jadi ia tidak boleh
// menutup baris jadual ini tanpa ukuran ini.
//
// Guna: node "Audit Review Round Robin/bukti/plan-f8/skrip/ukur-mobile-f8.mjs"

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);
import { writeFileSync } from 'node:fs';

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8092';
const TENANT = process.env.E2E_TENANT || 'mam';
const KATA = process.env.MANUAL_DEMO_PASSWORD || 'password';
const VIEWPORT = { width: 390, height: 664 };

const KES = [
    { guide: 'tenant.pelupusan', path: 'pelupusan', langkah: [1], jumlah: 5 },
    { guide: 'tenant.kegemaran', path: 'kegemaran', langkah: [1, 2, 3, 4, 5], jumlah: 5 },
];

const pelayar = await chromium.launch();
const konteks = await pelayar.newContext({ viewport: VIEWPORT });
// Tour automatik dimatikan supaya deep-link yang diukur ialah satu-satunya tour aktif.
await konteks.addInitScript(() => {
    try { window.localStorage.setItem('diwan-help-mode', 'dimatikan'); } catch { /* noop */ }
});
const p = await konteks.newPage();

await p.goto(`${BASE}/app/login`);
await p.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
await p.locator('input[type="password"]').fill(KATA);
await p.getByRole('button', { name: /Log masuk/i }).click();
await p.waitForURL((u) => u.pathname.replace(/\/$/, '') === `/app/${TENANT}`, { timeout: 90_000 });
console.log('log masuk OK · viewport 390x664 · pusat (195, 332)\n');

// `tenant.kegemaran` merujuk item sebenar; tanpa sekurang-kurangnya satu kegemaran, halaman
// kosong dan ukuran menjadi hijau PALSU (pelajaran W1/W4).
await p.goto(`${BASE}/app/${TENANT}/kegemaran`);
await p.waitForTimeout(1500);
const adaKegemaran = await p.locator('[data-help-target="favourite-item"]').count();
if (!adaKegemaran) {
    await p.goto(`${BASE}/app/${TENANT}/records`);
    const rekod = p.locator('main a[href*="/records/"]').first();
    if (await rekod.count()) {
        await rekod.dispatchEvent('click');
        await p.waitForURL(/\/records\/\d+/, { timeout: 60_000 });
        const bintang = p.getByRole('button', { name: /Kegemaran/i }).first();
        if (await bintang.isVisible().catch(() => false)) {
            await bintang.dispatchEvent('click');
            await p.waitForTimeout(1500);
        }
    }
}
await p.goto(`${BASE}/app/${TENANT}/kegemaran`);
await p.waitForTimeout(1200);
console.log(`kegemaran dalam senarai: ${await p.locator('[data-help-target="favourite-item"]').count()}\n`);

const hasil = [];
for (const kes of KES) {
    for (const n of kes.langkah) {
        const url = `${BASE}/app/${TENANT}/${kes.path}?panduan=${kes.guide}&langkah=${n - 1}`;
        for (let c = 1; ; c += 1) {
            try { await p.goto(url, { waitUntil: 'domcontentloaded' }); break; }
            catch (e) { if (c >= 4) throw e; await p.waitForTimeout(2000); }
        }
        await p.waitForTimeout(3500);

        const r = await p.evaluate(() => {
            const pop = document.querySelector('.driver-popover');
            const aktif = document.querySelector('.driver-active-element');
            const cx = window.innerWidth / 2;
            const cy = window.innerHeight / 2;
            if (!pop) return { adaPopover: false };
            const b = pop.getBoundingClientRect();
            return {
                adaPopover: true,
                popSize: { w: Math.round(b.width), h: Math.round(b.height), left: Math.round(b.left), top: Math.round(b.top) },
                pusat: { x: Math.round(cx), y: Math.round(cy) },
                centerCovered: b.left <= cx && b.right >= cx && b.top <= cy && b.bottom >= cy,
                target: aktif?.getAttribute('data-help-target') ?? null,
                statusText: document.querySelector('.driver-popover-progress-text')?.textContent?.trim() ?? null,
                title: document.querySelector('.driver-popover-title')?.textContent?.trim() ?? null,
            };
        });

        hasil.push({ key: `${kes.guide}#${n}`, ...r });
        console.log(`${r.centerCovered ? '🔴' : '✅'} ${`${kes.guide}#${n}`.padEnd(24)} `
            + `centerCovered=${r.centerCovered}  target=${r.target}  `
            + `pop=${r.popSize ? `${r.popSize.w}x${r.popSize.h}@${r.popSize.left},${r.popSize.top}` : '-'}  ${r.statusText ?? ''}`);
    }
}

const tertutup = hasil.filter((h) => h.centerCovered).length;
console.log(`\ncenterCovered: ${tertutup}/${hasil.length}  (asas audit: 6/6)`);
writeFileSync('Audit Review Round Robin/bukti/plan-f8/mobile-centercovered-f8.json',
    JSON.stringify({ viewport: VIEWPORT, asas_audit: 6, kini: tertutup, hasil }, null, 2) + '\n');
await pelayar.close();
