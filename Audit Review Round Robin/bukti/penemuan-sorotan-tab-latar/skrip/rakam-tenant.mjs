// #73 — Senario W5d SEBENAR: `/app/{tenant}/bantuan`, di mana sasaran berada DI DALAM
// komponen Livewire yang memorph. Halaman awam hanya mendekatinya.
//
// Ini menutup jurang yang terbuka sejak W5: pengesahan tenant tidak pernah dibuat kerana
// kredensial produksi tidak pernah dicipta. Tempatan membawa kod bundel yang IDENTIK
// (help-EPOANIj9.js — hash sama dengan produksi), jadi ia menguji logik yang sama.
//
// Tab kekal DI HADAPAN sepanjang larian: pemasa yang dibekukan ialah punca pembacaan palsu
// saya sebelum ini, jadi larian ini mesti bebas daripadanya.

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8092';
const TENANT = process.env.E2E_TENANT || 'mam';
const KATALALUAN = process.env.MANUAL_DEMO_PASSWORD || 'password';
const TEMPOH = Number(process.argv[2] || 30_000);

const perekam = () => {
    const t0 = Date.now();
    const log = [];
    window.__rakam = log;
    const cap = (jenis, nod = '-') => log.push({ ms: Date.now() - t0, jenis, nod, vis: document.visibilityState });
    const pasang = () => {
        new MutationObserver((rekod) => {
            for (const r of rekod) {
                if (r.attributeName !== 'class') continue;
                const ada = r.target.classList?.contains('driver-active-element');
                const dulu = (r.oldValue || '').split(/\s+/).includes('driver-active-element');
                if (ada !== dulu) cap(ada ? '+sorotan' : '-sorotan', r.target.getAttribute?.('data-help-target') || r.target.tagName);
            }
        }).observe(document.documentElement, {
            attributes: true, attributeFilter: ['class'], attributeOldValue: true, subtree: true,
        });
    };
    if (document.documentElement) pasang(); else document.addEventListener('DOMContentLoaded', pasang, { once: true });
    document.addEventListener('livewire:init', () => {
        window.Livewire.hook('commit', ({ component, succeed }) => {
            const n = component?.name || '?';
            cap('commit-hantar', n);
            succeed(() => cap('commit-respons', n));
        });
        window.Livewire.hook('morphed', ({ component }) => cap('morphed', component?.name || '?'));
    });
};

const pelayar = await chromium.launch({ headless: false });
const konteks = await pelayar.newContext({ viewport: { width: 1440, height: 900 } });
const halaman = await konteks.newPage();
await halaman.bringToFront();

console.log('log masuk sebagai admin_masjid@demo.test …');
await halaman.goto(`${BASE}/app/login`);
await halaman.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
await halaman.locator('input[type="password"]').fill(KATALALUAN);
await halaman.getByRole('button', { name: /Log masuk/i }).click();
await halaman.waitForURL((u) => u.pathname.replace(/\/$/, '') === `/app/${TENANT}`, { timeout: 90_000 });
console.log('log masuk OK\n');

// Perekam dipasang SELEPAS log masuk supaya ia hanya merakam halaman tour.
await konteks.addInitScript(perekam);
await halaman.goto(`${BASE}/app/${TENANT}/bantuan?panduan=tenant.bantuan&langkah=0`, { waitUntil: 'domcontentloaded' });
await halaman.waitForTimeout(TEMPOH);

const log = await halaman.evaluate(() => window.__rakam || []);
for (const e of log) console.log(`${String(e.ms).padStart(6)}ms  ${String(e.vis).padEnd(7)} ${e.jenis.padEnd(16)} ${e.nod}`);

const akhir = await halaman.evaluate(() => {
    const a = document.querySelector('.driver-active-element');
    const k = a?.getBoundingClientRect();
    const utama = document.querySelector('main')?.getBoundingClientRect();
    return {
        aktif: a?.getAttribute('data-help-target') ?? null,
        saiz: k ? `${Math.round(k.width)}x${Math.round(k.height)}` : null,
        peratusTinggiMain: k && utama ? Math.round((k.height / utama.height) * 100) + '%' : null,
        popover: document.querySelector('.driver-popover-title')?.textContent?.trim() ?? null,
        kaunter: document.querySelector('.driver-popover-progress-text')?.textContent?.trim() ?? null,
        visibility: document.visibilityState,
    };
});
console.log('\nAKHIR:', JSON.stringify(akhir, null, 2));
console.log(akhir.aktif ? '\n✅ sorotan tenant BERTAHAN' : '\n🔴 sorotan tenant HILANG');
await pelayar.close();
