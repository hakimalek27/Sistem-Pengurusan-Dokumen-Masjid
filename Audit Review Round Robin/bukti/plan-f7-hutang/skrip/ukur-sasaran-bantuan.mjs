// Hutang F7 — UKUR saiz sasaran calon pada halaman bantuan TENANT dan ADMIN sebelum menukar
// katalog. W5 §17 merekod bahawa sorotan yang pulih ≠ sorotan yang berguna; keputusan mesti
// dibuat daripada piksel sebenar, bukan daripada bacaan Blade.

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8092';
const KATALALUAN = process.env.MANUAL_DEMO_PASSWORD || 'password';
const CALON = ['help-search', 'help-search-form', 'help-scope'];

const pelayar = await chromium.launch();

async function ukur(label, emel, laluanLogin, urlBantuan, tunggu) {
    const konteks = await pelayar.newContext({ viewport: { width: 1440, height: 900 } });
    const p = await konteks.newPage();
    await p.goto(`${BASE}${laluanLogin}`);
    await p.locator('input[id="form.login"]').fill(emel);
    await p.locator('input[type="password"]').fill(KATALALUAN);
    await p.getByRole('button', { name: /Log masuk/i }).click();
    await p.waitForURL(tunggu, { timeout: 90_000 });
    // `php -S` satu-benang Windows memberi net::ERR_ABORTED sekali-sekala (direkod HANDOVER;
    // hijau di CI). Cuba semula, jangan tafsir sebagai halaman rosak.
    for (let cubaan = 1; ; cubaan += 1) {
        try {
            await p.goto(`${BASE}${urlBantuan}`, { waitUntil: 'domcontentloaded' });
            break;
        } catch (e) {
            if (cubaan >= 4) throw e;
            console.log(`  (cubaan ${cubaan} gagal: ${String(e.message).split('\n')[0]})`);
            await p.waitForTimeout(2000);
        }
    }
    await p.waitForTimeout(3500);

    const hasil = await p.evaluate((calon) => {
        const utama = document.querySelector('main')?.getBoundingClientRect();
        return calon.map((nama) => {
            const el = document.querySelector(`[data-help-target="${nama}"]`);
            if (!el) return { nama, ada: false };
            const b = el.getBoundingClientRect();
            return {
                nama, ada: true, tag: el.tagName.toLowerCase(),
                saiz: `${Math.round(b.width)}x${Math.round(b.height)}`,
                peratusMain: utama ? Math.round((b.height / utama.height) * 100) : null,
            };
        });
    }, CALON);

    console.log(`\n=== ${label} (${urlBantuan}) ===`);
    for (const r of hasil) {
        console.log(r.ada
            ? `  ${r.nama.padEnd(18)} <${r.tag}> ${r.saiz.padEnd(12)} ${r.peratusMain}% tinggi <main>`
            : `  ${r.nama.padEnd(18)} TIADA PADA HALAMAN INI`);
    }
    await konteks.close();
    return hasil;
}

await ukur('TENANT', 'admin_masjid@demo.test', '/app/login',
    '/app/mam/bantuan', (u) => u.pathname.replace(/\/$/, '') === '/app/mam');

await ukur('ADMIN (superadmin)', 'superadmin@diwan.test', '/admin/login',
    '/admin/bantuan', (u) => u.pathname.startsWith('/admin'));

await pelayar.close();
