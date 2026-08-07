// F7 §8.5 (RR-08-05) — viewer dokumen dalam PELAYAR SEBENAR.
//
// Logik keputusan kawalan sudah diuji sepenuhnya sebagai fungsi tulen
// (`e2e/viewer-control-plan.spec.js`, projek `unit`). Fail ini menguji perkara yang fungsi
// tulen TIDAK boleh buktikan: bahawa keputusan itu benar-benar sampai ke DOM, bahawa pdf.js
// memuat dokumen, dan bahawa clamp/cari/cetak berkelakuan seperti dituntut §8.5.
//
// Fixture dijana tanpa pakej baharu (`bukti/plan-f7/skrip/gen-pdf-fixtures.mjs`) dan disahkan
// boleh dibaca pdf.js sebelum dikomit.
import { expect, test } from '@playwright/test';
import { attachFile } from './helpers/upload.js';

const tenantSlug = process.env.E2E_PROD_TENANT ?? 'mam';
const defaultPassword = process.env.E2E_PROD_PASSWORD ?? process.env.MANUAL_DEMO_PASSWORD ?? 'password';

async function logMasuk(page) {
    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
    await page.locator('input[type="password"]').fill(defaultPassword);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL((url) => url.pathname.replace(/\/$/, '') === `/app/${tenantSlug}`, { timeout: 60_000 });
}

/**
 * Muat naik fixture ke Peti Masuk dan pulangkan URL viewer bagi medianya.
 *
 * URL viewer ialah `temporarySignedRoute`, jadi ia MESTI diambil daripada UI — membinanya
 * sendiri dalam ujian bermakna menguji URL yang pengguna tidak pernah dapat.
 */
async function muatNaikDanDapatkanUrlViewer(page, fixture) {
    await page.goto(`/app/${tenantSlug}/peti-masuk`);

    await page.locator('[data-help-target="inbox-upload"]').click();
    const modal = page.locator('[data-help-target="inbox-upload-modal"]');
    await expect(modal).toBeVisible({ timeout: 30_000 });

    await attachFile(modal, fixture);
    await modal.locator('[data-help-target="inbox-upload-submit"]').click();
    await expect(page.getByText(/\d+ dokumen dimuat naik ke Peti Masuk/)).toBeVisible({ timeout: 60_000 });

    // Baris pertama = dokumen yang baru dimuat naik (jadual disusun `created_at desc`).
    await page.locator('[data-help-target="inbox-view"]').click();

    // ⚠️ Infolist rekod BERTAB (`RecordInfolist`): pautan media hidup dalam tab
    // "Lampiran & Versi" (:56), bukan tab lalai "Maklumat" (:24). Mencari pautan tanpa
    // membuka tab itu memberi "element(s) not found" yang kelihatan seperti muat naik gagal
    // — diukur, bukan diteka.
    await page.getByRole('tab', { name: 'Lampiran & Versi' }).click();

    const pautan = page.getByRole('link', { name: 'Buka Viewer' }).first();
    await expect(pautan).toBeVisible({ timeout: 30_000 });

    return pautan.getAttribute('href');
}

/** Baca keadaan kawalan viewer terus daripada DOM. */
async function keadaanKawalan(page) {
    return page.evaluate(() => {
        const el = (s) => document.querySelector(s);
        const baca = (s) => {
            const n = el(s);
            if (!n) return null;

            return { disabled: n.disabled === true, aria: n.getAttribute('aria-disabled') };
        };

        return {
            prev: baca('[data-prev]'),
            next: baca('[data-next]'),
            zoomOut: baca('[data-zoom-out]'),
            zoomIn: baca('[data-zoom-in]'),
            halaman: el('[data-page-input]')?.value ?? null,
            maxHalaman: el('[data-page-input]')?.getAttribute('max') ?? null,
            jumlah: el('[data-page-count]')?.textContent?.trim() ?? null,
            zum: el('[data-zoom-label]')?.textContent?.trim() ?? null,
            status: el('[data-status]')?.textContent?.trim() ?? null,
        };
    });
}

const sudahDipapar = (page, n) =>
    expect(page.locator('[data-status]')).toHaveText(`Halaman ${n} dipaparkan.`, { timeout: 30_000 });

test.describe('F7 §8.5 viewer dokumen', () => {
    test.slow();

    test('PDF 3 halaman: had halaman, had zum, max input, clamp, cari, cetak', async ({ page }) => {
        await logMasuk(page);
        const url = await muatNaikDanDapatkanUrlViewer(page, 'tests/fixtures/viewer/tiga-halaman.pdf');
        await page.goto(url);
        await sudahDipapar(page, 1);

        // ── had halaman ────────────────────────────────────────────────────────────────
        let k = await keadaanKawalan(page);
        expect(k.jumlah).toBe('3');
        expect(k.maxHalaman).toBe('3');            // §8.4 kerja BAHARU: markup asal tiada `max`
        expect(k.prev).toEqual({ disabled: true, aria: 'true' });   // aria SEIRING native
        expect(k.next).toEqual({ disabled: false, aria: 'false' });

        await page.locator('[data-next]').click();
        await sudahDipapar(page, 2);
        k = await keadaanKawalan(page);
        expect(k.prev.disabled).toBe(false);
        expect(k.next.disabled).toBe(false);

        await page.locator('[data-next]').click();
        await sudahDipapar(page, 3);
        k = await keadaanKawalan(page);
        expect(k.next).toEqual({ disabled: true, aria: 'true' });   // halaman terakhir
        expect(k.prev.disabled).toBe(false);

        // ── clamp input halaman (kes tepi §8.5) ────────────────────────────────────────
        //
        // ⚠️ `fill('abc')` MUSTAHIL pada `input[type=number]` — Playwright menolaknya
        // ("Cannot type text into input[type=number]"), dan pelayar sebenar juga tidak
        // membenarkan pengguna menaipnya. Nilai bukan-nombor hanya boleh sampai kepada
        // pengendali melalui laluan PROGRAMATIK (autofill, tampal, skrip), jadi ia ditetapkan
        // begitu di sini. Menguji melalui `fill` bermakna menguji laluan yang tidak wujud.
        const tetapkanHalaman = async (nilai) => {
            await page.evaluate((v) => {
                const el = document.querySelector('[data-page-input]');
                el.value = v;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }, nilai);
        };

        for (const [input, jangka] of [['', '1'], ['0', '1'], ['-3', '1'], ['abc', '1'], ['999', '3']]) {
            await tetapkanHalaman(input);
            await expect(page.locator('[data-page-input]')).toHaveValue(jangka, { timeout: 30_000 });
        }

        // ── had zum ────────────────────────────────────────────────────────────────────
        // ⚠️ Gelung mesti berhenti apabila butang jadi disabled. Versi pertama saya mengklik
        // 8 kali tanpa syarat dan TAMAT MASA pada klik terakhir — iaitu ujian menghukum
        // produk kerana berkelakuan BETUL (butang memang dikunci pada had).
        for (let i = 0; i < 10; i += 1) {
            if ((await keadaanKawalan(page)).zoomIn.disabled) break;
            await page.locator('[data-zoom-in]').click();
        }
        k = await keadaanKawalan(page);
        expect(k.zum).toBe('300%');
        expect(k.zoomIn).toEqual({ disabled: true, aria: 'true' });

        for (let i = 0; i < 12; i += 1) {
            if ((await keadaanKawalan(page)).zoomOut.disabled) break;
            await page.locator('[data-zoom-out]').click();
        }
        k = await keadaanKawalan(page);
        expect(k.zum).toBe('50%');
        expect(k.zoomOut).toEqual({ disabled: true, aria: 'true' });

        // ── cari: jumpa / tidak jumpa / kosong / Enter ─────────────────────────────────
        await page.locator('[data-find-input]').fill('UNIKKEYWORD');   // hanya pada halaman 2
        await page.locator('[data-find]').click();
        await expect(page.locator('[data-status]')).toContainText('Padanan ditemui pada halaman 2', { timeout: 30_000 });

        await page.locator('[data-find-input]').fill('tiadaperkataaninisamasekali');
        await page.locator('[data-find-input]').press('Enter');        // laluan Enter
        await expect(page.locator('[data-status]')).toContainText('Teks tidak ditemui', { timeout: 30_000 });
        await expect(page.locator('[data-status]')).toHaveAttribute('data-error', 'true');

        await page.locator('[data-find-input]').fill('');
        await page.locator('[data-find]').click();
        await expect(page.locator('[data-status]')).toContainText('Masukkan teks untuk dicari');

        // ── cetak: metadata SAHAJA, kanvas tidak dicetak ───────────────────────────────
        await page.emulateMedia({ media: 'print' });
        const cetak = await page.evaluate(() => {
            const nampak = (s) => {
                const el = document.querySelector(s);
                if (!el) return null;

                return getComputedStyle(el).display !== 'none';
            };

            return {
                meta: nampak('.print-meta'),
                pentas: nampak('.viewer-stage'),
                bar: nampak('.viewer-toolbar'),
                metaAdaTajuk: (document.querySelector('.print-meta')?.innerText || '').trim().length > 0,
            };
        });
        await page.emulateMedia({ media: 'screen' });

        expect(cetak.meta).toBe(true);
        expect(cetak.metaAdaTajuk).toBe(true);
        expect(cetak.pentas).toBe(false);   // kanvas dokumen TIDAK dicetak
        expect(cetak.bar).toBe(false);
    });

    test('PDF 1 halaman: prev DAN next kedua-duanya disabled', async ({ page }) => {
        await logMasuk(page);
        const url = await muatNaikDanDapatkanUrlViewer(page, 'tests/fixtures/viewer/satu-halaman.pdf');
        await page.goto(url);
        await sudahDipapar(page, 1);

        const k = await keadaanKawalan(page);
        expect(k.jumlah).toBe('1');
        expect(k.maxHalaman).toBe('1');
        expect(k.prev).toEqual({ disabled: true, aria: 'true' });
        expect(k.next).toEqual({ disabled: true, aria: 'true' });
        // Zum kekal HIDUP walaupun dokumen satu halaman.
        expect(k.zoomIn.disabled).toBe(false);
    });

    test('PDF tanpa lapisan teks: cari melaporkan tidak ditemui, viewer tidak tersekat', async ({ page }) => {
        await logMasuk(page);
        const url = await muatNaikDanDapatkanUrlViewer(page, 'tests/fixtures/viewer/tanpa-teks.pdf');
        await page.goto(url);
        await sudahDipapar(page, 1);

        await page.locator('[data-find-input]').fill('apa-apa');
        await page.locator('[data-find]').click();
        await expect(page.locator('[data-status]')).toContainText('Teks tidak ditemui', { timeout: 30_000 });

        // Viewer mesti kekal boleh diguna selepas carian gagal.
        const k = await keadaanKawalan(page);
        expect(k.zoomIn.disabled).toBe(false);
        expect(k.halaman).toBe('1');
    });

    /**
     * ⚠️ PENJAGA ANTI-HIJAU-PALSU: keadaan RALAT.
     *
     * URL viewer ditandatangani dan LUPUT. Membuka URL yang rosak mesti meninggalkan kawalan
     * DIKUNCI — bukan kelihatan boleh diguna sedangkan tiada dokumen. Tanpa ujian ini,
     * cabang `gagalMuat` dalam `document-viewer.js` tidak pernah dijalankan dalam pelayar.
     */
    test('dokumen gagal dimuat: kawalan KEKAL dikunci', async ({ page }) => {
        await logMasuk(page);
        const url = await muatNaikDanDapatkanUrlViewer(page, 'tests/fixtures/viewer/satu-halaman.pdf');
        await page.goto(url);
        await sudahDipapar(page, 1);

        // Sabotaj muatan: batalkan URL media SEBENAR yang viewer gunakan. Corak tekaan
        // (`**/media/**`) tidak memadan apa-apa dan meninggalkan status kekal
        // "Memuatkan dokumen..." — ujian yang menunggu ralat yang tidak pernah datang.
        const urlMedia = await page.getAttribute('[data-document-viewer]', 'data-url');
        expect(urlMedia).toBeTruthy();
        await page.route(urlMedia, (laluan) => laluan.abort());
        await page.reload();

        // Sifat KESELAMATAN yang §8.4 tuntut: kawalan mesti KEKAL dikunci apabila tiada
        // dokumen. Itu yang diassert di sini, dan ia benar sama ada muatan MENOLAK (ralat)
        // atau TERGANTUNG (permintaan dibatalkan).
        await expect(page.locator('[data-status]')).toBeVisible();
        await expect(async () => {
            const kk = await keadaanKawalan(page);
            expect(kk.prev.disabled).toBe(true);
            expect(kk.next.disabled).toBe(true);
            expect(kk.zoomIn.disabled).toBe(true);
            expect(kk.zoomOut.disabled).toBe(true);
        }).toPass({ timeout: 30_000 });

        // ⚠️ PENEMUAN DIUKUR, bukan diandaikan: apabila permintaan media DIBATALKAN (bukan
        // ditolak dengan status ralat), `pdfjsLib.getDocument().promise` TIDAK menolak, jadi
        // cabang `.catch()` tidak pernah berjalan dan status kekal "Memuatkan dokumen..."
        // dengan `data-error="false"` selama-lamanya. Kawalan kekal dikunci — jadi pengguna
        // TIDAK boleh berinteraksi dengan viewer kosong — tetapi dia juga tidak pernah
        // diberitahu mengapa. Ujian ini merekod keadaan SEBENAR supaya ia tidak hilang;
        // menambah tempoh-tamat muatan ialah keputusan reka bentuk (berapa lama? kesan pada
        // sambungan perlahan?) yang diserahkan kepada pemilik — lihat LAPORAN-F7.md §(i).
        const status = await page.locator('[data-status]').textContent();
        expect(status).toMatch(/Memuatkan dokumen|gagal dimuatkan/);
    });
});
