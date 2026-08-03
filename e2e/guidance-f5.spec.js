// F5 (PELAN-PEMBAIKAN §6.5 "e2e") — tour halaman awam + tour muat naik, dalam pelayar sebenar.
//
// Dua perkara yang HANYA boleh dibuktikan di sini, bukan oleh Pest:
//   (a) `/log-masuk` tidak lagi memaparkan "Tindakan belum tersedia" (RR-01-01) dan langkah 1
//       menyorot INPUT, bukan `<main>` — pada desktop DAN mobile;
//   (b) sasaran `inbox-upload-dropzone` / `inbox-upload-submit` benar-benar wujud dalam DOM
//       modal Filament, yang dirender PELANGGAN-SISI (jadi HTML pelayan tidak pernah
//       mengandunginya — pelajaran F3).
import { readFileSync } from 'node:fs';
import { devices, expect, test } from '@playwright/test';
import { attachFile } from './helpers/upload.js';

const catalog = JSON.parse(readFileSync('resources/help/guides.json', 'utf8'));
const guideIds = catalog.guides.map((guide) => guide.id);
const tenantSlug = process.env.E2E_PROD_TENANT ?? 'mam';
const defaultPassword = process.env.E2E_PROD_PASSWORD ?? process.env.MANUAL_DEMO_PASSWORD ?? 'password';
const adminEmail = process.env.E2E_F5_ADMIN ?? 'admin_masjid@demo.test';
const RALAT_PALSU = 'Tindakan belum tersedia';

/** Halang auto-tour halaman awam supaya deep-link `?panduan=` yang diuji, bukan resume rawak. */
async function disableAutomaticGuides(context) {
    await context.addInitScript((ids) => {
        for (const id of ids) localStorage.setItem(`diwan-help-seen:${id}`, '1');
    }, guideIds);
}

async function fillStable(locator, value) {
    await expect(async () => {
        await locator.fill(value);
        await expect(locator).toHaveValue(value, { timeout: 2_000 });
    }).toPass({ timeout: 30_000 });
}

/** Elemen yang Driver.js sedang sorot (kelas vendor `driver-active-element`). */
function highlighted(page) {
    return page.locator('.driver-active-element');
}

// ── (a) Tour /log-masuk ─────────────────────────────────────────────────────────────────

for (const [nama, viewport] of [
    ['desktop 1280×800', { width: 1280, height: 800 }],
    ['mobile 390×664', devices['iPhone 13'].viewport],
]) {
    test(`F5a tour /log-masuk menyorot medan sebenar — ${nama}`, async ({ browser, baseURL }) => {
        const context = await browser.newContext({ viewport, baseURL });
        await disableAutomaticGuides(context);
        const page = await context.newPage();

        await page.goto('/log-masuk?panduan=public.login&langkah=0');

        const popover = page.locator('.driver-popover');
        await expect(popover).toBeVisible({ timeout: 30_000 });

        // Punca RR-01-01: kedua-dua langkah tiada sasaran → ralat palsu setiap kali.
        await expect(popover).not.toContainText(RALAT_PALSU);
        await expect(popover.locator('.driver-popover-title')).toContainText('Masukkan identiti');

        // Sorotan mesti INPUT, bukan <main>/<body> (sorotan-terlalu-besar).
        await expect(highlighted(page)).toHaveAttribute('data-help-target', 'login-identity');
        expect(await highlighted(page).evaluate((el) => el.tagName)).toBe('INPUT');

        // Langkah 2 → butang hantar.
        await popover.locator('.driver-popover-next-btn').click();
        await expect(popover.locator('.driver-popover-title')).toContainText('Minta pautan');
        await expect(popover).not.toContainText(RALAT_PALSU);
        await expect(highlighted(page)).toHaveAttribute('data-help-target', 'login-submit');
        expect(await highlighted(page).evaluate((el) => el.tagName)).toBe('BUTTON');

        // Langkah 2 ialah tindakan sebenar → CTA "Buat pada skrin", bukan "Selesai".
        await expect(popover.locator('.driver-popover-next-btn')).toHaveText(/Buat pada skrin/i);

        await context.close();
    });
}

test('F5a layout tetamu: satu <main>, jenama+nav di luar, tiada ralat JS', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (e) => errors.push(String(e)));
    page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));

    for (const path of ['/', '/log-masuk', '/daftar', '/bantuan']) {
        await page.goto(path);

        const bentuk = await page.evaluate(() => {
            const mains = document.querySelectorAll('main');
            const main = mains[0];
            return {
                mains: mains.length,
                // `.wrap > header` — banner LAYOUT sahaja. Mengira SEMUA `<header>` dalam
                // dokumen adalah terlalu luas: tour Driver.js menyuntik chrome sendiri pada
                // halaman yang auto-mula (cth /bantuan), dan itu bukan sebahagian kontrak
                // layout §6.5 #6. Ujian Pest mengira HTML PELAYAN, di mana 1 tetap betul.
                headers: document.querySelectorAll('.wrap > header').length,
                target: main?.dataset.helpTarget ?? null,
                h1DalamMain: main ? main.querySelectorAll('h1').length : -1,
                navDalamMain: main ? main.querySelectorAll('nav').length : -1,
                // decorateTargets() TIDAK boleh menimpa sasaran eksplisit pelayan.
                pageContentCount: document.querySelectorAll('[data-help-target="page-content"]').length,
            };
        });

        expect(bentuk, `halaman ${path}`).toEqual({
            mains: 1, headers: 1, target: 'page-content',
            h1DalamMain: 0, navDalamMain: 0, pageContentCount: 1,
        });

        // Landmark: <main> tidak bersarang dalam <header>/<nav>.
        expect(await page.evaluate(() => !!document.querySelector('header main, nav main')),
            `halaman ${path}: <main> bersarang`).toBe(false);
    }

    expect(errors).toEqual([]);
});

// ── (b) Tour muat naik + matriks keadaan ────────────────────────────────────────────────

async function loginAdmin(page) {
    await page.goto('/app/login');
    await fillStable(page.locator('input[id="form.login"]'), adminEmail);
    await fillStable(page.locator('input[type="password"]'), defaultPassword);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL((url) => url.pathname.replace(/\/$/, '') === `/app/${tenantSlug}`, { timeout: 60_000 });
}

/** Buka modal muat naik dan pulangkan locator tetingkapnya. */
async function openUploadModal(page) {
    await page.goto(`/app/${tenantSlug}/peti-masuk`);
    const trigger = page.locator('[data-help-target="inbox-upload"]');
    await expect(trigger).toBeVisible({ timeout: 30_000 });
    await expect(trigger).toBeEnabled();
    // dispatchEvent, bukan click({force:true}): overlay tour menyerap klik koordinat dan
    // `force` hanya melangkau semakan actionability (pelajaran F0/F3).
    await trigger.dispatchEvent('click');

    const modal = page.locator('[data-help-target="inbox-upload-modal"]');
    await expect(modal).toBeVisible({ timeout: 30_000 });

    return modal;
}

// ── (c) Sasaran navigasi responsif — §6.3 #5 "ujian dua breakpoint WAJIB" ───────────────

for (const [nama, viewport, jangka] of [
    ['desktop 1280×800', { width: 1280, height: 800 }, 'nav-sidebar'],
    ['mobile 390×664', devices['iPhone 13'].viewport, 'nav-menu-toggle'],
]) {
    test(`F5c nav-primary menyelesai kepada ${jangka} — ${nama}`, async ({ browser, baseURL }) => {
        const context = await browser.newContext({ viewport, baseURL });
        await disableAutomaticGuides(context);
        const page = await context.newPage();
        await loginAdmin(page);

        await page.goto(`/app/${tenantSlug}?panduan=tenant.dashboard&langkah=0`);

        const popover = page.locator('.driver-popover');
        await expect(popover).toBeVisible({ timeout: 30_000 });

        // C13: dahulu `sidebar` bukan ahli GENERIC_TARGETS, jadi bila `.fi-sidebar`
        // tersembunyi (mobile) resolver memulangkan null → ralat palsu ini.
        await expect(popover).not.toContainText(RALAT_PALSU);

        const dipilih = highlighted(page);
        await expect(dipilih).toHaveAttribute('data-help-nav', jangka);

        // Mesti elemen navigasi SEBENAR — bukan <main>/<body> (sorotan-terlalu-besar).
        const tag = await dipilih.evaluate((el) => el.tagName);
        expect(['ASIDE', 'NAV', 'BUTTON', 'DIV']).toContain(tag);
        expect(tag).not.toBe('MAIN');
        expect(tag).not.toBe('BODY');

        const keadaan = await page.evaluate(() => {
            const nampak = (t) => {
                const el = document.querySelector(`[data-help-nav="${t}"]`);
                if (!el) return null;
                const s = getComputedStyle(el);
                const r = el.getBoundingClientRect();
                return s.display !== 'none' && s.visibility !== 'hidden'
                    && el.getClientRects().length > 0
                    && r.right > 0 && r.left < window.innerWidth;
            };
            return {
                'nav-sidebar': nampak('nav-sidebar'),
                'nav-menu-toggle': nampak('nav-menu-toggle'),
                // Ruang nama BERASINGAN: calon nav tidak boleh mencemari `data-help-target`,
                // dan `nav-primary` ialah sasaran LOGIK yang TIDAK wujud dalam DOM.
                targetTercemar: document.querySelectorAll(
                    '[data-help-target="nav-sidebar"],[data-help-target="nav-menu-toggle"],'
                    + '[data-help-target="nav-bar"],[data-help-target="nav-primary"]').length,
                // `sidebar` (sasaran lama) mesti KEKAL — ia tidak boleh ditimpa oleh nav.
                sidebarKekal: document.querySelectorAll('[data-help-target="sidebar"]').length,
            };
        });

        expect(keadaan.targetTercemar).toBe(0);
        expect(keadaan.sidebarKekal).toBe(1);
        expect(keadaan[jangka]).toBe(true);

        // ⚠️ REGRESI YANG DITEMUI SEMASA F5: `decorateTargets()` dipanggil pada SETIAP
        // `resolveStepElement()`. Jika ia menulis atribut setiap kali (dua kunci berebut
        // satu elemen), pemerhati mutasi tour menerima ribut `attributes` tanpa henti dan
        // koreografi tour klasifikasi tersangkut sepenuhnya (3 ujian tamat masa 180s).
        // Penjaga: hitung mutasi atribut nav sepanjang 1 saat aktiviti tour — mesti 0.
        const mutasi = await page.evaluate(async () => {
            let n = 0;
            const obs = new MutationObserver((recs) => {
                for (const r of recs) {
                    if (r.type === 'attributes'
                        && (r.attributeName === 'data-help-nav' || r.attributeName === 'data-help-target')) n += 1;
                }
            });
            obs.observe(document.documentElement, { attributes: true, subtree: true });
            await new Promise((r) => setTimeout(r, 1000));
            obs.disconnect();
            return n;
        });

        expect(mutasi, 'decorateTargets() menulis semula atribut berulang kali').toBe(0);

        // `target_missing` tidak pernah dipancarkan untuk langkah ini.
        const jejak = await page.evaluate(() =>
            JSON.parse(sessionStorage.getItem('diwan-help:tenant.dashboard') ?? '{}'));
        expect(jejak.event ?? '').not.toBe('target_missing');

        await context.close();
    });
}

test('F5b sasaran dropzone dan Hantar wujud dalam DOM modal (bukan grep sumber)', async ({ page, context }) => {
    await disableAutomaticGuides(context);
    await loginAdmin(page);
    const modal = await openUploadModal(page);

    // Modal Filament 4 dirender pelanggan-sisi — inilah satu-satunya tempat kewujudan
    // sasaran ini boleh dibuktikan.
    const dropzone = modal.locator('[data-help-target="inbox-upload-dropzone"]');
    const submit = modal.locator('[data-help-target="inbox-upload-submit"]');

    await expect(dropzone).toBeVisible();
    await expect(submit).toBeVisible();

    // C12: ketiga-tiga sasaran mesti elemen BERBEZA dan tiada satu pun sama besar dengan
    // seluruh tetingkap modal — kecacatan asal ialah langkah 2 DAN 3 menyorot modal penuh.
    //
    // Tunggu FilePond siap sebelum mengukur: `.fi-fo-file-upload` runtuh (~20px) sebelum
    // skrip komponen dimuat secara lazy, jadi ukuran awal tidak bermakna.
    await expect(modal.locator('.filepond--root').first()).toBeVisible({ timeout: 60_000 });

    const bentuk = await page.evaluate(() => {
        const el = (t) => document.querySelector(`[data-help-target="${t}"]`);
        const [m, d, s] = ['inbox-upload-modal', 'inbox-upload-dropzone', 'inbox-upload-submit'].map(el);
        const area = (e) => { const b = e.getBoundingClientRect(); return Math.round(b.width * b.height); };

        return {
            tigaElemenBerbeza: new Set([m, d, s]).size === 3,
            dropzoneDalamModal: m.contains(d),
            submitDalamModal: m.contains(s),
            submitBukanDalamDropzone: !d.contains(s),
            areaModal: area(m), areaDropzone: area(d), areaSubmit: area(s),
        };
    });

    expect(bentuk.tigaElemenBerbeza).toBe(true);
    expect(bentuk.dropzoneDalamModal).toBe(true);
    expect(bentuk.submitDalamModal).toBe(true);
    // Butang Hantar berada di footer modal, BUKAN di dalam dropzone — jadi langkah 2 dan 3
    // menyorot dua kawasan yang tidak bertindan.
    expect(bentuk.submitBukanDalamDropzone).toBe(true);
    expect(bentuk.areaDropzone).toBeLessThan(bentuk.areaModal);
    expect(bentuk.areaSubmit).toBeLessThan(bentuk.areaModal);
});

test('F5b matriks: fail sah → toast bilangan dokumen', async ({ page, context }) => {
    await disableAutomaticGuides(context);
    await loginAdmin(page);
    const modal = await openUploadModal(page);

    await attachFile(modal, 'tests/fixtures/ocr/sample-scan-1.png');
    await modal.locator('[data-help-target="inbox-upload-submit"]').dispatchEvent('click');

    await expect(page.getByText(/\d+ dokumen dimuat naik ke Peti Masuk/)).toBeVisible({ timeout: 60_000 });
});

test('F5b matriks: format salah ditolak, tour tidak tersangkut', async ({ page, context }) => {
    await disableAutomaticGuides(context);
    await loginAdmin(page);
    const modal = await openUploadModal(page);

    // `acceptedFileTypes` menolak .zip pada FilePond (sebelum sampai pelayan).
    await expect(modal.locator('.filepond--root').first()).toBeVisible({ timeout: 60_000 });
    await modal.locator('input[type="file"]').first().setInputFiles({
        name: 'tidak-sah.zip', mimeType: 'application/zip', buffer: Buffer.from('PKpalsu'),
    });

    // Mesej penolakan FilePond ATAU ralat validasi Filament — kedua-duanya sah; yang
    // penting: modal kekal terbuka dan sasaran tour masih ada (tour tidak tersangkut).
    await expect(modal.locator('[data-help-target="inbox-upload-dropzone"]')).toBeVisible();
    await expect(modal.locator('[data-help-target="inbox-upload-submit"]')).toBeVisible();
    await expect(page.getByText(/\d+ dokumen dimuat naik ke Peti Masuk/)).toHaveCount(0);

    // Had saiz mesti dinyatakan pada dropzone (langkah 2 tour merujuknya).
    await expect(modal.getByText(/Format sah:/)).toBeVisible();
});
