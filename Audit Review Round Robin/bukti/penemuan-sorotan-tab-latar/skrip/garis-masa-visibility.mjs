// #73 — Garis masa GABUNGAN: keterlihatan tab + kitaran hayat sorotan + Livewire, satu jam.
//
// Larian sebelum ini menunjukkan sorotan tiada pada 30s dan kembali 1.5s selepas tab dibawa
// ke hadapan, tetapi bacaan `visibilityState` bercanggah — jadi saya tidak boleh mendakwa
// puncanya keterlihatan. Skrip ini merakam peralihan `visibilitychange` SENDIRI, supaya
// urutan sebenar boleh dibaca dan bukan disimpulkan.

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);

const BASE = process.env.E2E_BASE_URL || 'https://bakwim.my';
const SEMBUNYI_MS = Number(process.env.SEMBUNYI_MS || 25_000);

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
        cap('perekam-sedia');
    };
    if (document.documentElement) pasang();
    else document.addEventListener('DOMContentLoaded', pasang, { once: true });

    document.addEventListener('visibilitychange', () => cap(`visibility=${document.visibilityState}`));

    document.addEventListener('livewire:init', () => {
        window.Livewire.hook('commit', ({ component, succeed }) => {
            const n = component?.name || '?';
            cap('commit-hantar', n);
            succeed(() => cap('commit-respons', n));
        });
        window.Livewire.hook('morphed', ({ component }) => cap('morphed', component?.name || '?'));
    });

    // Detak untuk membuktikan pendikitan pemasa: sepatutnya setiap 250ms bila kelihatan.
    let n = 0;
    setInterval(() => { n += 1; if (n % 4 === 0) cap('detak-1s'); }, 250);
};

const pelayar = await chromium.launch({ headless: false });
const konteks = await pelayar.newContext({ viewport: null });
await konteks.addInitScript(perekam);

const tour = await konteks.newPage();
const lain = await konteks.newPage();

await tour.bringToFront();
await tour.goto(`${BASE}/bantuan?panduan=public.help&langkah=0`, { waitUntil: 'domcontentloaded' });
await lain.bringToFront();                       // tour kini di latar
await tour.waitForTimeout(SEMBUNYI_MS);
await tour.bringToFront();                       // pengguna beralih kembali
await tour.waitForTimeout(10_000);

const log = await tour.evaluate(() => window.__rakam);
for (const e of log) {
    console.log(`${String(e.ms).padStart(6)}ms  ${String(e.vis).padEnd(7)} ${e.jenis.padEnd(18)} ${e.nod}`);
}
const akhir = await tour.evaluate(() => {
    const a = document.querySelector('.driver-active-element');
    return { aktif: a?.getAttribute('data-help-target') ?? null, vis: document.visibilityState };
});
console.log('\nAKHIR:', JSON.stringify(akhir));
await pelayar.close();
