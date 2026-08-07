// Hutang F7 (b) — butang MANA yang benar-benar dirender pada baris pertama?
// Beberapa tindakan bersyarat status (`lulus`/`tolak` hanya untuk Menunggu; `gantung` hanya
// untuk Aktif/Digantung). Sasaran yang dipilih mesti yang SENTIASA ada, jika tidak gate
// menjadi hijau palsu pada benih yang berbeza (pelajaran W4: butang Laksana yang tidak wujud).

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8092';
const KATALALUAN = process.env.MANUAL_DEMO_PASSWORD || 'password';

const pelayar = await chromium.launch();
const konteks = await pelayar.newContext({ viewport: { width: 1440, height: 900 } });
const p = await konteks.newPage();

await p.goto(`${BASE}/admin/login`);
await p.locator('input[id="form.login"]').fill('superadmin@diwan.test');
await p.locator('input[type="password"]').fill(KATALALUAN);
await p.getByRole('button', { name: /Log masuk/i }).click();
await p.waitForURL((u) => u.pathname.startsWith('/admin'), { timeout: 90_000 });

for (const laluan of ['/admin/mosques', '/admin/users']) {
    for (let c = 1; ; c += 1) {
        try { await p.goto(`${BASE}${laluan}`, { waitUntil: 'domcontentloaded' }); break; }
        catch (e) { if (c >= 4) throw e; await p.waitForTimeout(2000); }
    }
    // Jadual Filament dimuat secara async — tunggu baris SEBENAR, jangan tidur dan harap.
    try {
        await p.waitForSelector('table tbody tr', { timeout: 20_000 });
    } catch {
        const diag = await p.evaluate(() => ({
            tajuk: document.title,
            adaTable: !!document.querySelector('table'),
            teksMain: (document.querySelector('main')?.innerText || '').slice(0, 200),
        }));
        console.log(`\n=== ${laluan} — TIADA BARIS ===`, JSON.stringify(diag));
        continue;
    }

    const hasil = await p.evaluate(() => {
        const baris = [...document.querySelectorAll('table tbody tr')];
        return baris.slice(0, 3).map((tr, i) => {
            const sel = tr.querySelector('td:last-child');
            const b = sel?.getBoundingClientRect();
            return {
                baris: i + 1,
                teksBaris: (tr.querySelector('td')?.textContent || '').trim().slice(0, 28),
                selSaiz: b ? `${Math.round(b.width)}x${Math.round(b.height)}` : null,
                butang: [...(sel?.querySelectorAll('button, a') || [])]
                    .map((x) => (x.textContent || '').trim() || x.getAttribute('aria-label') || '?')
                    .filter(Boolean),
            };
        });
    });

    console.log(`\n=== ${laluan} ===`);
    for (const r of hasil) {
        console.log(`  baris ${r.baris} "${r.teksBaris}"  sel=${r.selSaiz}`);
        console.log(`     butang: ${r.butang.join(' | ') || '(tiada)'}`);
    }
}

await pelayar.close();
