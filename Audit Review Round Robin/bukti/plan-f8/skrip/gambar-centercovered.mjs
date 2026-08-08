// F8 — bukti VISUAL bagi keputusan `centerCovered`, pada 390×664 TEPAT.
//
// Mengapa bukan MCP Chrome: tab kumpulan MCP hidup dalam tetingkap latar yang melaporkan
// `outer: 0x0`, tidak menghormati `resize_window` (viewport kekal 1920×889), dan
// `visibilityState` kekal `hidden`. Itu had struktur yang SAMA yang menyebabkan penggera palsu
// #73. Diukur, bukan diandaikan — jadi kaedah terkawal digunakan sebaliknya.
//
// Gambar melukis titik pusat viewport dan kotak sasaran supaya hubungan yang metrik
// `centerCovered` ukur boleh DILIHAT, bukan hanya dibaca sebagai nombor.

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);
import { mkdirSync } from 'node:fs';

const BASE = process.env.E2E_BASE_URL || 'https://bakwim.my';
const KELUAR = 'Audit Review Round Robin/bukti/plan-f8/gambar';
mkdirSync(KELUAR, { recursive: true });

const pelayar = await chromium.launch();
const k = await pelayar.newContext({ viewport: { width: 390, height: 664 } });
const p = await k.newPage();
await p.goto(`${BASE}/bantuan?panduan=public.help&langkah=0`, { waitUntil: 'domcontentloaded' });
await p.waitForSelector('.driver-popover', { timeout: 30_000 });
await p.waitForTimeout(1500);

const ukuran = await p.evaluate(() => {
    const pop = document.querySelector('.driver-popover');
    const t = document.querySelector('.driver-active-element');
    const cx = innerWidth / 2;
    const cy = innerHeight / 2;
    const b = pop.getBoundingClientRect();
    const tb = t?.getBoundingClientRect();

    // Penanda: titik pusat (merah) + sempadan sasaran (biru). Ditambah HANYA untuk gambar.
    const tanda = document.createElement('div');
    tanda.style.cssText = `position:fixed;left:${cx - 9}px;top:${cy - 9}px;width:18px;height:18px;`
        + 'border-radius:50%;background:#e11d48;border:2px solid #fff;z-index:2147483647;'
        + 'box-shadow:0 0 0 2px #e11d48';
    document.body.appendChild(tanda);
    const label = document.createElement('div');
    label.textContent = `pusat viewport ${Math.round(cx)},${Math.round(cy)}`;
    label.style.cssText = `position:fixed;left:6px;top:${cy + 14}px;font:11px/1.3 system-ui;`
        + 'color:#e11d48;background:#fff;padding:2px 5px;border:1px solid #e11d48;z-index:2147483647';
    document.body.appendChild(label);
    if (tb) {
        const kotak = document.createElement('div');
        kotak.style.cssText = `position:fixed;left:${tb.left}px;top:${tb.top}px;width:${tb.width}px;`
            + `height:${tb.height}px;border:2px dashed #2563eb;z-index:2147483646;pointer-events:none`;
        document.body.appendChild(kotak);
    }

    return {
        viewport: `${innerWidth}x${innerHeight}`,
        popover: { x: Math.round(b.left), y: Math.round(b.top), w: Math.round(b.width), h: Math.round(b.height) },
        sasaran: tb ? { nama: t.getAttribute('data-help-target'), x: Math.round(tb.left), y: Math.round(tb.top), w: Math.round(tb.width), h: Math.round(tb.height) } : null,
        centerCovered: b.left <= cx && b.right >= cx && b.top <= cy && b.bottom >= cy,
        popoverMenutupSasaran: tb ? (b.left < tb.right && b.right > tb.left && b.top < tb.bottom && b.bottom > tb.top) : null,
        sasaranKelihatanPenuh: tb ? (tb.top >= 0 && tb.bottom <= innerHeight) : null,
    };
});

const nama = `${KELUAR}/centercovered-mobile-390x664.png`;
await p.screenshot({ path: nama });
console.log(JSON.stringify(ukuran, null, 2));
console.log(`\ngambar: ${nama}`);
await pelayar.close();
