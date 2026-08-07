// Hutang F7 — PENGESAHAN VISUAL enam langkah yang ditukar, SEBELUM gate 40 minit.
//
// Gate hanya membuktikan sasaran DISOROT. Yang perlu dibuktikan di sini ialah sorotan itu
// BERMAKNA: saiznya munasabah (bukan seluruh halaman, bukan jalur nipis) dan ia benar-benar
// elemen yang ayat langkah itu namakan. Dua kecacatan W4 lulus setiap ujian automatik dan
// hanya terbongkar di sini.

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8092';
const KATA = process.env.MANUAL_DEMO_PASSWORD || 'password';

const KES = [
    ['tenant', '/app/mam/bantuan', 'tenant.bantuan', 0, 'help-search-form'],
    ['tenant', '/app/mam/bantuan', 'tenant.bantuan', 1, 'help-scope'],
    ['admin', '/admin/bantuan', 'admin.bantuan', 0, 'help-search-form'],
    ['admin', '/admin/bantuan', 'admin.bantuan', 1, 'help-scope'],
    ['admin', '/admin/mosques', 'admin.mosques', 1, 'platform-mosques-actions'],
    ['admin', '/admin/users', 'admin.users', 1, 'platform-users-actions'],
];

const pelayar = await chromium.launch();

async function sesi(peranan) {
    const k = await pelayar.newContext({ viewport: { width: 1440, height: 900 } });
    const p = await k.newPage();
    const [emel, login, tunggu] = peranan === 'admin'
        ? ['superadmin@diwan.test', '/admin/login', (u) => u.pathname.startsWith('/admin')]
        : ['admin_masjid@demo.test', '/app/login', (u) => u.pathname.replace(/\/$/, '') === '/app/mam'];
    await p.goto(`${BASE}${login}`);
    await p.locator('input[id="form.login"]').fill(emel);
    await p.locator('input[type="password"]').fill(KATA);
    await p.getByRole('button', { name: /Log masuk/i }).click();
    await p.waitForURL(tunggu, { timeout: 90_000 });
    return { k, p };
}

const sesiCache = {};
let gagal = 0;

for (const [peranan, laluan, panduan, langkah, dijangka] of KES) {
    sesiCache[peranan] ??= await sesi(peranan);
    const { p } = sesiCache[peranan];
    const url = `${BASE}${laluan}?panduan=${panduan}&langkah=${langkah}`;

    for (let c = 1; ; c += 1) {
        try { await p.goto(url, { waitUntil: 'domcontentloaded' }); break; }
        catch (e) { if (c >= 4) throw e; await p.waitForTimeout(2000); }
    }
    await p.waitForTimeout(5000);

    const r = await p.evaluate(() => {
        const a = document.querySelector('.driver-active-element');
        const utama = document.querySelector('main')?.getBoundingClientRect();
        const b = a?.getBoundingClientRect();
        return {
            aktif: a?.getAttribute('data-help-target') ?? null,
            tag: a?.tagName.toLowerCase() ?? null,
            saiz: b ? `${Math.round(b.width)}x${Math.round(b.height)}` : null,
            pct: b && utama ? Math.round((b.height / utama.height) * 100) : null,
            teks: (a?.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 70),
            popover: document.querySelector('.driver-popover-title')?.textContent?.trim() ?? null,
            kaunter: document.querySelector('.driver-popover-progress-text')?.textContent?.trim() ?? null,
        };
    });

    const ok = r.aktif === dijangka;
    if (!ok) gagal += 1;
    console.log(`${ok ? '✅' : '🔴'} ${panduan}#${langkah + 1}  dijangka=${dijangka}`);
    console.log(`     aktif=${r.aktif} <${r.tag}> ${r.saiz} = ${r.pct}% <main>  | ${r.kaunter}`);
    console.log(`     popover="${r.popover}"`);
    console.log(`     teks disorot: "${r.teks}"`);
}

console.log(gagal ? `\n🔴 ${gagal}/${KES.length} TIDAK sepadan` : `\n✅ ${KES.length}/${KES.length} sepadan`);
await pelayar.close();
