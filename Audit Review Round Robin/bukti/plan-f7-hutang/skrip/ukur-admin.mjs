// Hutang F7 (b) — apakah yang SEBENARNYA ada pada tiga halaman admin, dan berapa baris?
// W5 §10 merekod bahawa `platform-*` menyorot kotak carian sedangkan ayat langkah menerangkan
// tindakan BARIS. Sebelum memutuskan sasaran baharu, ukur: sasaran sedia ada + saiznya +
// bilangan baris sebenar dalam benih (skrin kosong = gate hijau PALSU, pelajaran W1).

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8092';
const KATALALUAN = process.env.MANUAL_DEMO_PASSWORD || 'password';
const HALAMAN = ['/admin/mosques', '/admin/users', '/admin/storage-orders'];

const pelayar = await chromium.launch();
const konteks = await pelayar.newContext({ viewport: { width: 1440, height: 900 } });
const p = await konteks.newPage();

await p.goto(`${BASE}/admin/login`);
await p.locator('input[id="form.login"]').fill('superadmin@diwan.test');
await p.locator('input[type="password"]').fill(KATALALUAN);
await p.getByRole('button', { name: /Log masuk/i }).click();
await p.waitForURL((u) => u.pathname.startsWith('/admin'), { timeout: 90_000 });
console.log('log masuk superadmin OK');

for (const laluan of HALAMAN) {
    for (let cubaan = 1; ; cubaan += 1) {
        try { await p.goto(`${BASE}${laluan}`, { waitUntil: 'domcontentloaded' }); break; }
        catch (e) { if (cubaan >= 4) throw e; await p.waitForTimeout(2000); }
    }
    await p.waitForTimeout(3000);

    const hasil = await p.evaluate(() => {
        const utama = document.querySelector('main')?.getBoundingClientRect();
        const sasaran = [...document.querySelectorAll('[data-help-target]')].map((el) => {
            const b = el.getBoundingClientRect();
            return {
                nama: el.getAttribute('data-help-target'),
                tag: el.tagName.toLowerCase(),
                saiz: `${Math.round(b.width)}x${Math.round(b.height)}`,
                pct: utama ? Math.round((b.height / utama.height) * 100) : null,
            };
        });
        const baris = document.querySelectorAll('table tbody tr').length;
        const selTindakan = document.querySelectorAll('table tbody tr td:last-child').length;
        return { sasaran, baris, selTindakan };
    });

    console.log(`\n=== ${laluan}  (baris jadual: ${hasil.baris}) ===`);
    for (const s of hasil.sasaran) {
        console.log(`  ${s.nama.padEnd(26)} <${s.tag}> ${s.saiz.padEnd(12)} ${s.pct}%`);
    }
}

await pelayar.close();
