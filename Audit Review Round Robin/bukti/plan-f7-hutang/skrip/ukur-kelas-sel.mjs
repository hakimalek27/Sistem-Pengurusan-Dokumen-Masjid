// Kelas SEBENAR sel tindakan Filament — diukur pada HTML hidup.
// page-target-plan.js merekod perangkapnya sendiri: kelas yang dibaca daripada blade vendor
// (`.fi-ta-filters-trigger-action-ctn`) hanya dirender secara bersyarat dan lima guide gagal.

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8092';
const pelayar = await chromium.launch();
const konteks = await pelayar.newContext({ viewport: { width: 1440, height: 900 } });
const p = await konteks.newPage();

await p.goto(`${BASE}/admin/login`);
await p.locator('input[id="form.login"]').fill('superadmin@diwan.test');
await p.locator('input[type="password"]').fill(process.env.MANUAL_DEMO_PASSWORD || 'password');
await p.getByRole('button', { name: /Log masuk/i }).click();
await p.waitForURL((u) => u.pathname.startsWith('/admin'), { timeout: 90_000 });

for (const laluan of ['/admin/mosques', '/admin/users']) {
    for (let c = 1; ; c += 1) {
        try { await p.goto(`${BASE}${laluan}`, { waitUntil: 'domcontentloaded' }); break; }
        catch (e) { if (c >= 4) throw e; await p.waitForTimeout(2000); }
    }
    await p.waitForSelector('table tbody tr', { timeout: 20_000 });

    const r = await p.evaluate(() => {
        const tr = document.querySelector('table tbody tr');
        const td = tr?.querySelector('td:last-child');
        const dalam = td?.firstElementChild;
        const calon = [
            'tbody tr:first-child .fi-ta-actions',
            'tbody tr:first-child td:last-child',
            '.fi-ta-actions',
        ];
        const ukur = (sel) => {
            const el = document.querySelector(sel);
            if (!el) return `${sel} -> TIADA`;
            const b = el.getBoundingClientRect();
            return `${sel} -> <${el.tagName.toLowerCase()}> ${Math.round(b.width)}x${Math.round(b.height)}`;
        };
        return {
            kelasTd: td?.className || null,
            kelasDalam: dalam?.className || null,
            bilanganFiTaActions: document.querySelectorAll('.fi-ta-actions').length,
            calon: calon.map(ukur),
        };
    });

    console.log(`\n=== ${laluan} ===`);
    console.log('  kelas <td> terakhir :', r.kelasTd);
    console.log('  kelas anak pertama  :', r.kelasDalam);
    console.log('  bilangan .fi-ta-actions pada halaman :', r.bilanganFiTaActions);
    for (const c of r.calon) console.log('   ', c);
}

await pelayar.close();
