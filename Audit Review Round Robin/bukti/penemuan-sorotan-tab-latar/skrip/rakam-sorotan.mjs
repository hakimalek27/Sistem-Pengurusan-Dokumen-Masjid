// #73 — RAKAM (bukan teka) kitaran hayat `.driver-active-element` pada halaman awam /bantuan.
//
// Perekam dipasang SEBELUM sebarang skrip halaman (`addInitScript`) supaya peristiwa
// PERTAMA tertangkap — kegagalan pengukuran saya pada produksi ialah perekam dipasang
// selepas sorotan sudah hilang, jadi masa kehilangan tidak diketahui.
//
// Ia merakam TIGA aliran pada garis masa yang sama:
//   (a) setiap penambahan/pembuangan kelas `driver-active-element` + identiti nod
//   (b) setiap commit Livewire dan setiap morph, dengan nama komponen
//   (c) indeks langkah tour + teks kaunter popover
//
// Guna: node rakam-sorotan.mjs [tempoh_ms]

// Skrip hidup dalam scratchpad, jadi resolusi modul relatif kepadanya — bukan kepada repo.
// Import mutlak daripada node_modules repo supaya ia boleh dijalankan dari mana-mana.
const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);
import { writeFileSync } from 'node:fs';

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8092';
const URL_UJIAN = `${BASE}/bantuan?panduan=public.help&langkah=0`;
const TEMPOH = Number(process.argv[2] || 40_000);

const perekam = () => {
    const t0 = Date.now();
    const log = [];
    window.__rakam = log;
    const cap = (jenis, butiran) => log.push({ ms: Date.now() - t0, jenis, ...butiran });

    const nama = (el) => {
        if (!(el instanceof Element)) return String(el);
        const t = el.getAttribute?.('data-help-target');
        return t ? `[${t}]` : `${el.tagName.toLowerCase()}${el.id ? '#' + el.id : ''}`;
    };

    const pasang = () => {
        // (a) transisi kelas — attributeOldValue supaya tambah/buang dapat dibezakan.
        new MutationObserver((rekod) => {
            for (const r of rekod) {
                if (r.attributeName !== 'class') continue;
                const ada = r.target.classList?.contains('driver-active-element');
                const adaDulu = (r.oldValue || '').split(/\s+/).includes('driver-active-element');
                if (ada === adaDulu) continue;
                cap(ada ? '+sorotan' : '-sorotan', { nod: nama(r.target) });
            }
        }).observe(document.documentElement, {
            attributes: true, attributeFilter: ['class'], attributeOldValue: true, subtree: true,
        });
        cap('perekam-sedia', { nod: '-' });
    };

    if (document.documentElement) pasang();
    else document.addEventListener('DOMContentLoaded', pasang, { once: true });

    // (b) Livewire — commit dihantar, respons tiba, dan morph sebenar.
    document.addEventListener('livewire:init', () => {
        cap('livewire-init', { nod: '-' });
        window.Livewire.hook('commit', ({ component, succeed, fail }) => {
            const n = component?.name || '?';
            cap('commit-hantar', { nod: n });
            succeed(() => cap('commit-respons', { nod: n }));
            fail(() => cap('commit-gagal', { nod: n }));
        });
        window.Livewire.hook('morph', ({ component }) => cap('morph', { nod: component?.name || '?' }));
        window.Livewire.hook('morphed', ({ component }) => cap('morphed', { nod: component?.name || '?' }));
    });

    // (c) keadaan tour — undi ringan; ia hanya membaca, tidak menyentuh sasaran.
    setInterval(() => {
        const pop = document.querySelector('.driver-popover');
        if (!pop) return;
        const kaunter = pop.querySelector('.driver-popover-progress-text')?.textContent?.trim()
            || pop.querySelector('[class*=progress]')?.textContent?.trim() || '';
        const aktif = document.querySelector('.driver-active-element');
        const kunci = `${kaunter}|${aktif ? nama(aktif) : 'NULL'}`;
        if (kunci === window.__kunciAkhir) return;
        window.__kunciAkhir = kunci;
        cap('keadaan-tour', { nod: aktif ? nama(aktif) : 'NULL', kaunter });
    }, 200);
};

const pelayar = await chromium.launch();
const konteks = await pelayar.newContext({ viewport: { width: 1440, height: 900 } });
await konteks.addInitScript(perekam);
const halaman = await konteks.newPage();

// Perlumbaan morph-lawan-sorotan menentukan sama ada laluan pemulihan disentuh langsung.
// Klien perlahan + latensi tinggi memaksa respons commit mendarat SELEPAS sorotan — arah
// yang produksi pantas tidak pernah ambil, dan tepat keadaan pengguna pada mesin sibuk.
const CPU = Number(process.env.THROTTLE_CPU || 0);
const LATENSI = Number(process.env.LATENCY_MS || 0);
if (CPU || LATENSI) {
    const cdp = await konteks.newCDPSession(halaman);
    if (CPU) await cdp.send('Emulation.setCPUThrottlingRate', { rate: CPU });
    if (LATENSI) {
        await cdp.send('Network.enable');
        await cdp.send('Network.emulateNetworkConditions', {
            offline: false, latency: LATENSI,
            downloadThroughput: 1_000_000, uploadThroughput: 1_000_000,
        });
    }
    console.log(`throttle: cpu=${CPU || 1}x latensi=${LATENSI}ms`);
}

const ralatKonsol = [];
halaman.on('console', (m) => { if (m.type() === 'error') ralatKonsol.push(m.text()); });
halaman.on('pageerror', (e) => ralatKonsol.push(`pageerror: ${e.message}`));

console.log(`buka  ${URL_UJIAN}`);
await halaman.goto(URL_UJIAN, { waitUntil: 'domcontentloaded' });
await halaman.waitForTimeout(TEMPOH);

const log = await halaman.evaluate(() => window.__rakam || []);
const akhir = await halaman.evaluate(() => {
    const aktif = document.querySelector('.driver-active-element');
    const sasaran = document.querySelector('[data-help-target="help-search-form"]');
    return {
        aktif: aktif?.getAttribute('data-help-target') ?? null,
        sasaranDalamDom: !!sasaran,
        sasaranKelihatan: !!sasaran?.getClientRects().length,
        popover: document.querySelector('.driver-popover-title')?.textContent?.trim() ?? null,
    };
});

for (const e of log) {
    console.log(`${String(e.ms).padStart(6)}ms  ${e.jenis.padEnd(16)} ${e.nod}${e.kaunter ? '  ' + e.kaunter : ''}`);
}
console.log('\nKEADAAN AKHIR:', JSON.stringify(akhir));
console.log('RALAT KONSOL :', ralatKonsol.length ? ralatKonsol.join(' | ') : '(tiada)');

writeFileSync(new URL('./rakam-sorotan.json', import.meta.url), JSON.stringify({ log, akhir, ralatKonsol }, null, 2));
await pelayar.close();
