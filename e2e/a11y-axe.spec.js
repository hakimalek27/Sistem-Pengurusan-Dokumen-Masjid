// F7 §8.5 — larian axe SEBENAR pada DOM hidup.
//
// Laluan automatik dibenarkan oleh ADDENDUM v2.7 (pengecualian polisi D5a): `axe-core` dalam
// `devDependencies` sahaja, disuntik daripada `node_modules` — TIADA CDN, tiada muat turun
// runtime, jadi suite kekal boleh dijalankan tanpa rangkaian luar (peraturan #5 CLAUDE.md).
//
// Sasaran §8.5: `link-name` 0 · `landmark-unique` 0 · `empty-table-header` 0-atau-didokumen.
// Ketiga-tiganya diperiksa pada 5 halaman × 2 viewport.
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { expect, test } from '@playwright/test';

const require = createRequire(import.meta.url);
const SUMBER_AXE = readFileSync(require.resolve('axe-core'), 'utf8');

const PERATURAN = ['link-name', 'landmark-unique', 'empty-table-header'];
const tenantSlug = process.env.E2E_PROD_TENANT ?? 'mam';
const defaultPassword = process.env.E2E_PROD_PASSWORD ?? process.env.MANUAL_DEMO_PASSWORD ?? 'password';

const VIEWPORT = [
    ['desktop 1280×800', { width: 1280, height: 800 }],
    ['mobile 390×664', { width: 390, height: 664 }],
];

/** Lima halaman: satu awam + empat panel tenant yang membawa jadual (tempat isu axe hidup). */
const HALAMAN = [
    { nama: 'awam /bantuan', laluan: '/bantuan', perluLogMasuk: false },
    { nama: 'papan pemuka', laluan: `/app/${tenantSlug}`, perluLogMasuk: true },
    { nama: 'peti masuk', laluan: `/app/${tenantSlug}/peti-masuk`, perluLogMasuk: true },
    { nama: 'rekod', laluan: `/app/${tenantSlug}/records`, perluLogMasuk: true },
    { nama: 'fail registri', laluan: `/app/${tenantSlug}/registry-files`, perluLogMasuk: true },
];

async function logMasuk(page) {
    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
    await page.locator('input[type="password"]').fill(defaultPassword);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL((url) => url.pathname.replace(/\/$/, '') === `/app/${tenantSlug}`, { timeout: 60_000 });
}

/**
 * Jalankan axe pada halaman semasa dan pulangkan pelanggaran bagi peraturan yang disasarkan.
 *
 * ⚠️ `axe.run` diberi senarai peraturan EKSPLISIT. Menjalankan axe penuh akan memasukkan
 * puluhan peraturan yang berada di luar skop F7 dan menjadikan gate ini gagal atas sebab
 * yang fasa ini tidak berjanji membaikinya — itu gate yang melatih orang mengabaikan merah.
 */
async function jalankanAxe(page) {
    await page.addScriptTag({ content: SUMBER_AXE });

    return page.evaluate(async (peraturan) => {
        const hasil = await window.axe.run(document, {
            runOnly: { type: 'rule', values: peraturan },
            resultTypes: ['violations'],
        });

        return hasil.violations.map((v) => ({
            id: v.id,
            impact: v.impact,
            bilangan: v.nodes.length,
            // `failureSummary` disertakan dengan SENGAJA: "landmark-unique pada <header>"
            // tidak memberitahu landmark MANA yang berlanggar dengannya, dan gate yang tidak
            // menerangkan dirinya memakan masa siasatan setiap kali ia merah.
            nod: v.nodes.slice(0, 3).map((n) => ({
                html: n.html.slice(0, 160),
                sebab: (n.failureSummary || '').replace(/\s+/g, ' ').slice(0, 300),
            })),
        }));
    }, PERATURAN);
}

for (const [namaViewport, viewport] of VIEWPORT) {
    for (const halaman of HALAMAN) {
        test(`axe ${halaman.nama} — ${namaViewport}`, async ({ browser, baseURL }) => {
            test.setTimeout(120_000);
            const context = await browser.newContext({ baseURL, viewport });
            const page = await context.newPage();

            try {
                if (halaman.perluLogMasuk) await logMasuk(page);
                await page.goto(halaman.laluan);

                // Jadual Filament dirender selepas muatan awal; menjalankan axe terlalu awal
                // memberi HIJAU PALSU kerana elemen yang dinilai belum wujud (pelajaran
                // "skrin tanpa data menjadikan gate hijau palsu", F6-W1).
                await page.locator('main').waitFor({ state: 'visible', timeout: 30_000 });
                await page.waitForTimeout(2_000);

                const pelanggaran = await jalankanAxe(page);

                expect(pelanggaran, `pelanggaran axe pada ${halaman.laluan} (${namaViewport}): `
                    + JSON.stringify(pelanggaran, null, 2)).toEqual([]);
            } finally {
                await context.close();
            }
        });
    }
}

/**
 * ⚠️ PENJAGA ANTI-HIJAU-PALSU.
 *
 * Semua ujian di atas menuntut senarai KOSONG. Senarai kosong juga yang dihasilkan apabila
 * axe gagal disuntik, apabila peraturan salah nama, atau apabila halaman tidak dimuat — dan
 * ketiga-tiganya kelihatan IDENTIK dengan kejayaan. Ujian ini menyuntik pelanggaran yang
 * DIKETAHUI dan menuntut axe melihatnya; tanpa ia, keseluruhan fail ini boleh hijau tanpa
 * pernah menjalankan apa-apa.
 */
test('axe benar-benar berjalan — pelanggaran yang disuntik DIKESAN', async ({ page }) => {
    await page.goto('/bantuan');
    await page.locator('main').waitFor({ state: 'visible', timeout: 30_000 });

    // Pautan tanpa teks = `link-name` yang pasti.
    await page.evaluate(() => {
        const a = document.createElement('a');
        a.href = '/tiada';
        a.id = 'umpan-axe';
        document.querySelector('main').appendChild(a);
    });

    const pelanggaran = await jalankanAxe(page);

    expect(pelanggaran.map((v) => v.id)).toContain('link-name');
});
