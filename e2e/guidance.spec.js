import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { attachFile } from './helpers/upload.js';

const catalog = JSON.parse(readFileSync('resources/help/guides.json', 'utf8'));
const guideIds = catalog.guides.map((guide) => guide.id);
const defaultPassword = process.env.E2E_PROD_PASSWORD ?? process.env.MANUAL_DEMO_PASSWORD ?? 'password';
const tenantSlug = process.env.E2E_PROD_TENANT ?? 'mam';
const crossTenantSlug = process.env.E2E_PROD_CROSS_TENANT ?? 'man';
const filePrefix = process.env.E2E_PROD_FILE_PREFIX ?? 'MAM';
const loginDelayMs = Number(process.env.E2E_PROD_ROLE_LOGIN_DELAY_MS ?? process.env.E2E_ROLE_LOGIN_DELAY_MS ?? 15_000);
let lastLoginAt = 0;

// F0(ii-b) gate #6 (P14-03): kiraan halaman per role dibaca daripada manifest role_routes —
// BUKAN nombor literal — supaya hanya SATU sumber kebenaran (drift 8/8 role tamat di sini).
const expectedPages = JSON.parse(readFileSync(
    'Audit Review Round Robin/bukti/plan-baseline/manifest.json', 'utf8',
)).role_routes.expected_page_counts;

const localTenantRoles = [
    'admin_masjid', 'pengerusi', 'setiausaha', 'bendahari', 'nazir', 'ketua_imam', 'ajk', 'audit',
].map((role) => ({ role, email: `${role}@demo.test`, pages: expectedPages[role] }));
const tenantRoles = process.env.E2E_PROD_ROLE_ACCOUNTS
    ? JSON.parse(process.env.E2E_PROD_ROLE_ACCOUNTS)
    : localTenantRoles;
const superadminAccount = {
    email: process.env.E2E_PROD_SUPERADMIN_EMAIL ?? 'superadmin@diwan.test',
    password: process.env.E2E_PROD_SUPERADMIN_PASSWORD ?? defaultPassword,
};

/**
 * Isi/pilih medan borang Livewire dengan selamat terhadap morph.
 * `fill()` = clear + insertText; jika morph Livewire mendarat antara kedua-duanya, nilai
 * lama dipulihkan lalu insertText MENAMBAH di hujung (slug berganda menumbangkan CI run
 * c90264c; pada medan kod ia melanggar had 6 aksara dan menolak wizard secara senyap).
 * `toPass` mengulang tindakan sehingga nilai benar-benar melekat.
 */
async function fillStable(locator, value) {
    await expect(async () => {
        await locator.fill(value);
        await expect(locator).toHaveValue(value, { timeout: 2_000 });
    }).toPass({ timeout: 30_000 });
}

async function selectStable(locator, value) {
    await expect(async () => {
        await locator.selectOption(value);
        await expect(locator).toHaveValue(typeof value === 'string' ? value : /.+/, { timeout: 2_000 });
    }).toPass({ timeout: 30_000 });
}

async function disableAutomaticGuides(context) {
    await context.addInitScript((ids) => {
        for (const id of ids) localStorage.setItem(`diwan-help-seen:${id}`, '1');
    }, guideIds);
}

async function waitForLoginSlot(page) {
    const remaining = loginDelayMs - (Date.now() - lastLoginAt);
    if (remaining > 0) await page.waitForTimeout(remaining);
}

async function loginTenant(page, account) {
    await waitForLoginSlot(page);
    await page.goto('/app/login');
    await fillStable(page.locator('input[id="form.login"]'), account.email);
    await fillStable(page.locator('input[type="password"]'), account.password ?? defaultPassword);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL((url) => url.pathname.replace(/\/$/, '') === `/app/${tenantSlug}`, { timeout: 60_000 });
    lastLoginAt = Date.now();
}

async function loginSuperadmin(page) {
    await waitForLoginSlot(page);
    await page.goto('/admin/login');
    await fillStable(page.locator('input[id="form.login"]'), superadminAccount.email);
    await fillStable(page.locator('input[type="password"]'), superadminAccount.password);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(/\/admin\/?$/, { timeout: 60_000 });
    lastLoginAt = Date.now();
}

async function visibleNavigation(page) {
    return page.locator('.fi-sidebar a[href]').evaluateAll((nodes) => nodes
        .filter((node) => node.offsetParent !== null)
        .map((node) => ({
            label: (node.textContent ?? '').replace(/\s+/g, ' ').trim(),
            href: node.href,
        }))
        .filter((item, index, items) => item.label && items.findIndex((other) => other.href === item.href) === index));
}

function monitorBrowserErrors(page) {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error') errors.push(message.text());
    });

    return errors;
}

async function assertNoHorizontalPageOverflow(page) {
    const overflow = await page.evaluate(() => Math.max(
        document.documentElement.scrollWidth,
        document.body?.scrollWidth ?? 0,
    ) - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(2);
}

/**
 * Klik elemen halaman semasa tour aktif: overlay tour kekal semasa minimize
 * (minimiseForAction tidak destroy driver) dan lubang sorotan ikut geometri fon —
 * pada runner Linux butang boleh jatuh di luar lubang. Klik koordinat (biasa ATAU
 * force) diserap overlay: force cuma melangkau semakan, klik tetap mendarat pada
 * elemen teratas di koordinat itu. dispatchEvent menghantar event terus pada ELEMEN,
 * jadi handler Livewire/Alpine menerima tanpa kira lapisan. toBeEnabled dahulu —
 * klik semasa wire:loading disabled hilang tanpa kesan. Ujian ini menguji
 * sinkronisasi langkah tour, bukan hit-test overlay (UX overlay = skop F2/F6).
 */
async function forceClickWhenEnabled(locator) {
    await expect(locator).toBeEnabled();
    await locator.dispatchEvent('click');
}

/**
 * Tunggu popover tiba pada langkah `text` selepas tindakan halaman. Auto-advance
 * (watchForNextStep → moveNext berjadual 120ms) berlumba dgn re-highlight Driver.js
 * selepas morph Livewire: re-highlight memanggil watchForNextStep semula →
 * clearTransitionWatch membunuh jadual moveNext → guard "sasaran seterusnya sudah
 * wujud" (help.js:363) menghalang poller baharu → tour terkandas. Popover pula masih
 * `display:none` daripada minimiseForAction, jadi laluan keluar pengguna sebenar ialah
 * DUA butang: "Tunjuk arahan" pada banner menunggu, kemudian CTA maju popover.
 * Race ini bug produk skop F2 §3 — help.js TIDAK disentuh pada F0 (§0.3).
 */
async function recoverStalledTour(popover) {
    // "Tunjuk arahan" pada banner: dispatchEvent, BUKAN klik tetikus — vendor Driver.js
    // menetapkan `.driver-active * { pointer-events: none }` (kecuali sasaran + popover),
    // jadi banner menolak klik tetikus. Laluan papan kekunci pengguna masih hidup
    // (help.js:242 memberi fokus pada butang itu) dan menghasilkan event click yang sama.
    // Kebolehklikan tetikus banner = pembaikan produk F2 (§3), bukan skop F0.
    const show = popover.page().locator('[data-diwan-tour-waiting] button');
    if (await show.isVisible().catch(() => false)) await show.dispatchEvent('click').catch(() => {});
    const nudge = popover.locator('.driver-popover-next-btn');
    if (await nudge.isVisible().catch(() => false)) await nudge.click().catch(() => {});
}

async function expectStepAdvance(popover, text) {
    try {
        await expect(popover).toContainText(text, { timeout: 5_000 });

        return;
    } catch {
        // auto-advance kalah race — pulihkan melalui UI seperti pengguna
    }
    // Semakan kedua: elak nudge jika advance mendarat tepat selepas timeout (nudge
    // ketika itu akan MELOMPAT satu langkah lagi).
    if (await popover.textContent().then((t) => (t ?? '').includes(text)).catch(() => false)) return;
    await recoverStalledTour(popover);
    await expect(popover).toContainText(text);
}

async function closeGuideIfOpen(page) {
    const close = page.locator('.driver-popover-close-btn');
    if (await close.isVisible().catch(() => false)) {
        await close.click();
        await expect(page.locator('.driver-popover')).toBeHidden();
    }
}

async function ensureInboxFixture(page) {
    if (await page.getByRole('button', { name: 'Klasifikasikan', exact: true }).first().isVisible().catch(() => false)) return;

    const marker = Date.now();
    await page.getByRole('button', { name: /Muat Naik Dokumen/i }).click();
    const dialog = page.getByRole('dialog');
    await attachFile(dialog, {
        name: `Dokumen panduan E2E ${marker}.txt`,
        mimeType: 'text/plain',
        buffer: Buffer.from(`Dokumen ujian panduan ${marker}.`),
    });
    const submit = dialog.getByRole('button', { name: 'Hantar', exact: true });
    await expect(submit).toBeEnabled({ timeout: 60_000 });
    await submit.click();
    await expect(page.getByText('1 dokumen dimuat naik ke Peti Masuk.')).toBeVisible({ timeout: 60_000 });
}

async function assertFloatingHelpLauncher(page, viewportHeight) {
    const launcher = page.locator('[data-help-target="help-launcher"]');
    // Auto-start/resume tour dijadualkan 450ms SELEPAS boot (help.js:585) dan
    // `body.driver-active` menyembunyikan launcher (help.css:76). Melepasi tetingkap itu
    // dahulu, jika tidak gelung boleh lulus sebelum tour sempat bermula lalu assert
    // berikutnya gagal (CI run 30772402289). Kemudian tutup apa-apa tour dan pastikan
    // launcher kekal kelihatan secara STABIL, bukan seketika.
    await page.waitForTimeout(800);
    await expect.poll(async () => {
        const close = page.locator('.driver-popover-close-btn');
        if (await close.isVisible().catch(() => false)) {
            await close.click().catch(() => {});
            await page.waitForTimeout(300);
        }
        if (! await launcher.isVisible().catch(() => false)) return false;
        await page.waitForTimeout(600);   // tour lain sempat bermula dalam tempoh ini?

        return launcher.isVisible().catch(() => false);
    }, { timeout: 30_000, message: 'launcher bantuan tidak kekal kelihatan (tour aktif?)' }).toBe(true);
    await expect(launcher).toBeVisible();
    await expect(launcher).toHaveAttribute('aria-label', 'Buka Pembantu Diwan');
    expect(await launcher.evaluate((element) => getComputedStyle(element).position)).toBe('fixed');
    const box = await launcher.boundingBox();
    expect(box).not.toBeNull();
    expect(box.y).toBeGreaterThan(viewportHeight - 100);
    expect(box.width).toBeLessThanOrEqual(60);
}

test('Chrome berasingan untuk superadmin, lapan role dan public pada desktop serta mobile', async ({ browser, baseURL }) => {
    test.setTimeout(900_000);
    const contextKeys = new Set();
    const inventory = [];

    for (const viewport of [
        { name: 'desktop', width: 1440, height: 1000 },
        { name: 'mobile', width: 390, height: 844 },
    ]) {
        const viewportSize = { width: viewport.width, height: viewport.height };
        const publicContext = await browser.newContext({ baseURL, viewport: viewportSize });
        contextKeys.add(publicContext);
        await disableAutomaticGuides(publicContext);
        const publicPage = await publicContext.newPage();
        const publicErrors = monitorBrowserErrors(publicPage);
        for (const path of ['/', '/daftar', '/bantuan']) {
            const response = await publicPage.goto(path);
            expect(response?.status(), `public ${viewport.name}: ${path}`).toBe(200);
            await expect(publicPage.locator('body')).toBeVisible();
            await assertNoHorizontalPageOverflow(publicPage);
        }
        await expect(publicPage.locator('[data-help-target="help-center"]')).toBeVisible();
        expect([...new Set(publicErrors)]).toEqual([]);
        inventory.push({ viewport: viewport.name, role: 'public', pages: 3 });
        await publicContext.close();

        const superadminContext = await browser.newContext({ baseURL, viewport: viewportSize });
        contextKeys.add(superadminContext);
        await disableAutomaticGuides(superadminContext);
        const superadmin = await superadminContext.newPage();
        const superadminErrors = monitorBrowserErrors(superadmin);
        await loginSuperadmin(superadmin);
        const adminNavigation = viewport.name === 'desktop' ? await visibleNavigation(superadmin) : [];
        if (viewport.name === 'desktop') {
            for (const item of adminNavigation) {
                const response = await superadmin.goto(item.href);
                expect(response?.status(), `superadmin: ${item.href}`).toBe(200);
                await expect(superadmin.locator('main')).toBeVisible();
            }
        }
        const adminHelp = await superadmin.goto('/admin/bantuan');
        expect(adminHelp?.status()).toBe(200);
        await expect(superadmin.locator('[data-help-target="help-center"]')).toBeVisible();
        // DB perawan boleh auto-sambung tour (autoStart/resume ikut DB, bukan localStorage —
        // help.js hanya semak diwan-help-seen utk panel public); launcher sengaja disorok
        // semasa tour aktif (help.css .driver-active), jadi tutup dahulu.
        await closeGuideIfOpen(superadmin);
        await assertFloatingHelpLauncher(superadmin, viewport.height);
        await assertNoHorizontalPageOverflow(superadmin);
        expect([...new Set(superadminErrors)]).toEqual([]);
        inventory.push({ viewport: viewport.name, role: 'superadmin', pages: adminNavigation.length || 1 });
        await superadminContext.close();

        for (const account of tenantRoles) {
            await test.step(`${viewport.name}: ${account.role}`, async () => {
                const context = await browser.newContext({ baseURL, viewport: viewportSize });
                contextKeys.add(context);
                await disableAutomaticGuides(context);
                const page = await context.newPage();
                const browserErrors = monitorBrowserErrors(page);
                await loginTenant(page, account);

                let navigation = [];
                if (viewport.name === 'desktop') {
                    navigation = await visibleNavigation(page);
                    expect(navigation.length, account.role).toBe(account.pages);
                    for (const item of navigation) {
                        const response = await page.goto(item.href);
                        expect(response?.status(), `${account.role}: ${item.href}`).toBe(200);
                        await expect(page.locator('main')).toBeVisible();
                    }
                }

                const help = await page.goto(`/app/${tenantSlug}/bantuan`);
                expect(help?.status()).toBe(200);
                await expect(page.locator('[data-help-target="help-center"]')).toBeVisible();
                await closeGuideIfOpen(page);
                await assertFloatingHelpLauncher(page, viewport.height);
                await expect(page.locator('.diwan-help-result').first()).toBeVisible();
                await assertNoHorizontalPageOverflow(page);
                expect([...new Set(browserErrors)], `${account.role} ${viewport.name}`).toEqual([]);

                const crossTenant = await page.goto(`/app/${crossTenantSlug}/records`);
                expect(crossTenant?.status(), `${account.role} silang tenant`).toBe(404);
                inventory.push({
                    viewport: viewport.name,
                    role: account.role,
                    pages: navigation.length || 1,
                    crossTenant: crossTenant?.status(),
                });
                await context.close();
            });
        }
    }

    expect(contextKeys.size).toBe(20);
    console.log(JSON.stringify({ contextCount: contextKeys.size, inventory }, null, 2));
});

test('tour boleh dimula, ditutup, disambung, diselesaikan dan diulang', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    const browserErrors = monitorBrowserErrors(page);
    await loginTenant(page, tenantRoles[0]);

    await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=tenant.peti-masuk&langkah=0`);
    const popover = page.locator('.driver-popover');
    await expect(popover).toBeVisible();
    await expect(popover).toContainText('1 daripada 6');
    await popover.getByRole('button', { name: 'Tutup panduan' }).click();
    await expect(popover).toBeHidden();
    await expect.poll(() => page.evaluate(() => JSON.parse(sessionStorage.getItem('diwan-help:tenant.peti-masuk') ?? '{}').event)).toBe('dismissed');

    await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=tenant.peti-masuk&langkah=0`);
    await expect(popover).toBeVisible();
    await popover.getByRole('button', { name: 'Seterusnya' }).click();
    await expect(popover).toContainText('2 daripada 6');
    await popover.getByRole('button', { name: 'Tutup panduan' }).click();
    await page.waitForTimeout(700);

    const inboxPath = `/app/${tenantSlug}/peti-masuk`;
    await page.goto(`/app/${tenantSlug}/bantuan?asal=${encodeURIComponent(inboxPath)}`);
    await page.locator('#help-query').fill('Peti Masuk');
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    const result = page.locator('.diwan-help-result').filter({ has: page.getByRole('heading', { name: 'Peti Masuk', exact: true }) }).first();
    await expect(result).toBeVisible();
    await result.getByRole('button', { name: 'Mulakan panduan' }).click();
    await expect(popover).toBeVisible();
    await expect(popover).toContainText('2 daripada 6');

    for (let index = 0; index < 5; index += 1) {
        await popover.locator('.driver-popover-next-btn').click();
        if (index < 4) await expect(popover).toBeVisible();
    }
    await expect(popover).toBeHidden();
    await expect(page).not.toHaveURL(/panduan=/);

    await page.goto(`/app/${tenantSlug}/bantuan?asal=${encodeURIComponent(inboxPath)}`);
    await page.locator('#help-query').fill('Peti Masuk');
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    const repeatResult = page.locator('.diwan-help-result').filter({ has: page.getByRole('heading', { name: 'Peti Masuk', exact: true }) }).first();
    await repeatResult.getByRole('button', { name: 'Mulakan panduan' }).click();
    await expect(popover).toContainText('1 daripada 6');
    await popover.getByRole('button', { name: 'Tutup panduan' }).click();

    expect([...new Set(browserErrors)]).toEqual([]);
    await context.close();
});

test('panduan pendaftaran awam bermula automatik sekali dan ikon bantuan kekal tersedia', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 390, height: 844 } });
    const page = await context.newPage();
    const browserErrors = monitorBrowserErrors(page);
    await page.goto('/daftar');

    const popover = page.locator('.driver-popover');
    await expect(popover).toBeVisible();
    await expect(popover).toContainText('1 daripada 4');
    await expect(popover).toContainText('Tindakan anda');
    await expect(page.locator('[data-help-target="registration-organisation"]')).toBeVisible();
    await popover.getByRole('button', { name: 'Tutup panduan' }).click();
    await expect(popover).toBeHidden();

    await page.reload();
    await page.waitForTimeout(800);
    await expect(popover).toBeHidden();
    const launcher = page.locator('[data-help-target="help-launcher"]');
    await expect(launcher).toBeVisible();
    await expect(launcher).toContainText('Pembantu Diwan');
    await launcher.click();
    await expect(page).toHaveURL(/\/bantuan\?asal=/);
    await expect(page.locator('[data-help-target="help-center"]')).toBeVisible();
    await assertNoHorizontalPageOverflow(page);
    expect([...new Set(browserErrors)]).toEqual([]);
    await context.close();
});

test('carian bantuan memberi hasil, status dan sempadan role yang jelas', async ({ browser, baseURL }) => {
    const publicContext = await browser.newContext({ baseURL, viewport: { width: 390, height: 844 } });
    await disableAutomaticGuides(publicContext);
    const publicPage = await publicContext.newPage();
    const publicErrors = monitorBrowserErrors(publicPage);
    await publicPage.goto('/bantuan');
    await expect(publicPage.getByText('Skop panduan:')).toContainText('Orang Awam');
    await publicPage.locator('#help-query').fill('klasifikasi surat');
    await publicPage.getByRole('button', { name: 'Cari', exact: true }).click();
    await expect(publicPage.locator('.diwan-help-search-status')).toContainText('0 hasil');
    await expect(publicPage.locator('.diwan-help-empty')).toContainText('log masuk ke akaun masjid');
    await publicPage.getByRole('button', { name: 'Daftar masjid', exact: true }).click();
    await expect(publicPage.locator('.diwan-help-search-status')).toContainText('hasil dalam skop Orang Awam');
    await expect(publicPage.getByRole('heading', { name: 'Daftar Masjid', exact: true })).toBeVisible();
    expect([...new Set(publicErrors)]).toEqual([]);
    await publicContext.close();

    const appContext = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await disableAutomaticGuides(appContext);
    const appPage = await appContext.newPage();
    const appErrors = monitorBrowserErrors(appPage);
    await loginTenant(appPage, tenantRoles[0]);
    await appPage.goto(`/app/${tenantSlug}/bantuan`);
    await closeGuideIfOpen(appPage);
    await expect(appPage.getByText('Skop panduan:')).toContainText('Admin / Kerani');
    await appPage.locator('#help-query').fill('nak klasfikasi surat wasap');
    await appPage.getByRole('button', { name: 'Cari', exact: true }).click();
    await expect(appPage.locator('.diwan-help-search-status')).not.toContainText('0 hasil');
    await expect(appPage.locator('.diwan-help-result').filter({ hasText: /Klasifikasi|Peti Masuk/i }).first()).toBeVisible();
    expect([...new Set(appErrors)]).toEqual([]);
    await appContext.close();
});

test('imej bantuan yang gagal tidak meninggalkan ruang kosong atau ralat halaman', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 390, height: 844 } });
    await disableAutomaticGuides(context);
    await context.route('**/bantuan/imej/tenant.dashboard**', (route) => route.fulfill({
        status: 404,
        contentType: 'text/plain',
        body: 'not found',
    }));
    const page = await context.newPage();
    const browserErrors = monitorBrowserErrors(page);
    await loginTenant(page, tenantRoles[0]);
    await page.goto(`/app/${tenantSlug}/bantuan`);
    const media = page.locator('[data-help-image-wrap]').first();
    await expect(media).toHaveClass(/is-missing/);
    await expect(media.locator('.diwan-help-image-fallback')).toBeVisible();
    expect([...new Set(browserErrors.filter((error) => !error.includes('404 (Not Found)')))]).toEqual([]);
    await context.close();
});

test('tour pendaftaran tidak tergantung dan mengikuti langkah Livewire sebenar', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 390, height: 844 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    const browserErrors = monitorBrowserErrors(page);
    await page.goto('/daftar?panduan=public.registration&langkah=0');

    const popover = page.locator('.driver-popover');
    await expect(popover).toContainText('1 daripada 4');
    await popover.locator('.driver-popover-next-btn').click();
    await expect(page.locator('[data-diwan-tour-waiting]')).toContainText('Panduan menunggu');
    await expect(page.locator('[data-diwan-tour-waiting]')).toHaveAttribute('role', 'status');
    await expect(popover).toBeHidden();
    expect(await page.evaluate(() => Boolean(document.activeElement?.closest('[data-help-target="registration-organisation"]')))).toBe(true);

    const organisation = page.locator('[data-help-target="registration-organisation"]');
    await fillStable(organisation.locator('input').nth(0), `Masjid Tour ${Date.now()}`);
    // Blur EKSPLISIT (wire:model.blur) → auto-slug; tunggu ia mendarat sebelum medan lain,
    // jika tidak morph berlumba dgn fill dan menghasilkan nilai berganda (rujuk fillStable).
    await organisation.locator('input').nth(0).blur();
    await expect(organisation.locator('input').nth(3)).not.toHaveValue('');
    await selectStable(organisation.locator('select'), { label: 'Selangor' });
    await fillStable(organisation.locator('input').nth(1), 'Petaling');
    await fillStable(organisation.locator('input').nth(2), 'TURAA');
    await fillStable(organisation.locator('input').nth(3), `tour-${Date.now()}`);
    await forceClickWhenEnabled(page.locator('[data-help-target="registration-next"]'));
    await expect(page.locator('[data-help-target="registration-admin"]')).toBeVisible();
    await expectStepAdvance(popover, '2 daripada 4');
    await popover.locator('.driver-popover-next-btn').click();

    const admin = page.locator('[data-help-target="registration-admin"]');
    await fillStable(admin.locator('input').nth(0), 'Pentadbir Tour');
    await fillStable(admin.locator('input').nth(1), `tour-${Date.now()}@example.test`);
    await fillStable(admin.locator('input').nth(2), `6012${String(Date.now()).slice(-8)}`);
    await forceClickWhenEnabled(page.locator('[data-help-target="registration-next"]'));
    await expect(page.locator('[data-help-target="registration-consent"]')).toBeVisible();
    await expectStepAdvance(popover, '3 daripada 4');
    await popover.getByRole('button', { name: 'Tutup panduan' }).click();
    expect([...new Set(browserErrors)]).toEqual([]);
    await context.close();
});

test('tour klasifikasi mengikuti modal lima langkah tanpa menghantar rekod', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    const browserErrors = monitorBrowserErrors(page);
    await loginTenant(page, tenantRoles[0]);
    await page.goto(`/app/${tenantSlug}/peti-masuk`);
    await ensureInboxFixture(page);
    await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=screen.klasifikasi-peti-masuk&langkah=0`);

    const classify = page.getByRole('button', { name: 'Klasifikasikan', exact: true }).first();
    await expect(classify, 'Fixture Peti Masuk diperlukan untuk audit tour klasifikasi').toBeVisible();
    const popover = page.locator('.driver-popover');
    await expect(popover).toContainText('1 daripada 11');
    await popover.locator('.driver-popover-next-btn').click();
    await classify.click();

    const modal = page.locator('.fi-modal-window:visible').last();
    await expect(modal).toBeVisible();
    await expect(page.locator('[data-help-target="classification-source"]:visible')).toBeVisible();
    await expectStepAdvance(popover, '2 daripada 11');
    await popover.locator('.driver-popover-next-btn').click();
    await forceClickWhenEnabled(modal.getByRole('button', { name: 'Seterusnya', exact: true }));

    await expect(page.locator('[data-help-target="classification-metadata"]:visible')).toBeVisible();
    await expectStepAdvance(popover, '3 daripada 11');
    const recordType = page.locator('#mountedActionSchema0\\.record_type');
    if (!await recordType.inputValue()) await selectStable(recordType, 'surat_menyurat');
    await selectStable(page.locator('#mountedActionSchema0\\.direction'), 'masuk');
    await popover.locator('.driver-popover-next-btn').click();
    await expectStepAdvance(popover, '4 daripada 11');
    await popover.locator('.driver-popover-next-btn').click();
    await expectStepAdvance(popover, '5 daripada 11');
    await popover.locator('.driver-popover-next-btn').click();
    await forceClickWhenEnabled(modal.getByRole('button', { name: 'Seterusnya', exact: true }));

    await expect(page.locator('[data-help-target="classification-file"]:visible')).toBeVisible();
    await expectStepAdvance(popover, '6 daripada 11');
    const fileStep = modal.locator('form.fi-active');
    await fileStep.locator('.fi-select-input-btn').first().click();
    await page.getByRole('option', { name: new RegExp(`${filePrefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\.`) }).first().click();
    await selectStable(page.locator('#mountedActionSchema0\\.sensitivity'), 'dalaman');
    await popover.locator('.driver-popover-next-btn').click();
    await expect(popover).toContainText('7 daripada 11');
    await popover.locator('.driver-popover-next-btn').click();
    await forceClickWhenEnabled(modal.getByRole('button', { name: 'Seterusnya', exact: true }));

    await expect(page.locator('[data-help-target="classification-minit"]:visible')).toBeVisible();
    await expectStepAdvance(popover, '8 daripada 11');
    await popover.locator('.driver-popover-next-btn').click();
    await expectStepAdvance(popover, '9 daripada 11');
    await popover.locator('.driver-popover-next-btn').click();
    await forceClickWhenEnabled(modal.getByRole('button', { name: 'Seterusnya', exact: true }));

    await expect(page.locator('[data-help-target="classification-review"]:visible')).toBeVisible();
    await expectStepAdvance(popover, '10 daripada 11');
    await popover.locator('.driver-popover-next-btn').click();
    await expectStepAdvance(popover, '11 daripada 11');
    await expect(page.locator('[data-help-target="classification-submit"]:visible')).toBeVisible();
    await popover.getByRole('button', { name: 'Tutup panduan' }).click();
    await modal.getByRole('button', { name: 'Tutup' }).click();
    await expect(modal).toBeHidden();
    expect([...new Set(browserErrors)]).toEqual([]);
    await context.close();
});

// F6-W5 — nota penjaga: invarian "guide AUTOMATIK berundur apabila modal dibuka" dijaga oleh
// `wizard klasifikasi lima langkah…` di bawah, yang TERBUKTI dua arah: ia MERAH sebelum
// pembaikan `guardAutomaticGuideFromDialogs` (popover memintas butang modal / memerangkap
// fokus) dan HIJAU selepasnya.
//
// Penjaga yang lebih sempit pernah ditulis lalu DIBUANG: ia mengklik "Klasifikasikan" melalui
// koordinat semasa tour aktif, dan overlay Driver.js menyerap klik koordinat
// (`overlayClickBehavior: 'close'` — pelajaran F0/W1). Penjaga yang tidak setia kepada laluan
// pengguna sebenar lebih buruk daripada tiada penjaga; ujian penuh di bawah memandu laluan
// yang SAMA seperti pengguna dan sudah meliputi invarian ini.

async function verifyClassificationWizard(browser, baseURL, account, viewport) {
    const context = await browser.newContext({ baseURL, viewport });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    const browserErrors = monitorBrowserErrors(page);
    await loginTenant(page, account);
    await page.goto(`/app/${tenantSlug}/peti-masuk`);
    const classify = page.getByRole('button', { name: 'Klasifikasikan', exact: true }).first();
    await expect(classify).toHaveAttribute('data-help-target', 'inbox-classify');
    await classify.click();

    const modal = page.locator('.fi-modal-window:visible').last();
    await expect(modal).toBeVisible();
    await expect(modal).toHaveAttribute('data-help-target', 'inbox-classification-modal');
    await page.keyboard.press('Tab');
    expect(await page.evaluate(() => Boolean(document.activeElement?.closest('.fi-modal-window')))).toBe(true);

    const assertModalFits = async () => {
        const box = await modal.boundingBox();
        expect(box).not.toBeNull();
        expect(box.x).toBeGreaterThanOrEqual(-1);
        expect(box.width).toBeLessThanOrEqual(viewport.width + 1);
        expect(box.x + box.width).toBeLessThanOrEqual(viewport.width + 1);
    };
    await assertModalFits();

    const next = () => modal.getByRole('button', { name: 'Seterusnya', exact: true });
    await expect(modal.locator('form.fi-active')).toContainText('Asal dokumen');
    await next().click();
    await expect(modal.locator('form.fi-active')).toContainText('Ruj. Kami ialah rujukan masjid');
    const recordType = page.locator('#mountedActionSchema0\\.record_type');
    if (!await recordType.inputValue()) await selectStable(recordType, 'surat_menyurat');
    await selectStable(page.locator('#mountedActionSchema0\\.direction'), 'masuk');
    await next().click();

    await expect(modal.locator('form.fi-active')).toContainText('Tahap Akses Rekod');
    const fileStep = modal.locator('form.fi-active');
    await fileStep.locator('.fi-select-input-btn').first().click();
    await page.getByRole('option', { name: new RegExp(`${filePrefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\.`) }).first().click();
    await selectStable(page.locator('#mountedActionSchema0\\.sensitivity'), 'dalaman');
    await assertModalFits();
    await next().click();

    await expect(modal.locator('form.fi-active')).toContainText('Untuk Tindakan (Minit)');
    await expect(modal.locator('form.fi-active')).toContainText('Untuk Makluman (s.k.)');
    await selectStable(page.locator('#mountedActionSchema0\\.minit_priority'), 'biasa');
    await next().click();

    await expect(modal.locator('form.fi-active')).toContainText('Kesan hantar');
    await expect(modal.locator('form.fi-active')).toContainText('Sensitiviti efektif: Dalaman');
    await expect(modal.locator('form.fi-active')).toContainText('satu transaksi');
    await assertModalFits();
    await modal.getByRole('button', { name: 'Tutup' }).click();
    await expect(modal).toBeHidden();
    expect([...new Set(browserErrors)], `${account.role} ${viewport.width}px`).toEqual([]);
    await context.close();
}

test('wizard klasifikasi lima langkah berfungsi pada desktop dan mobile tanpa menghantar data', async ({ browser, baseURL }) => {
    await verifyClassificationWizard(browser, baseURL, tenantRoles[0], { width: 1440, height: 1000 });
    await verifyClassificationWizard(browser, baseURL, tenantRoles[2], { width: 390, height: 844 });
});

// F1 (PELAN-PEMBAIKAN §2.4) — konteks Pembantu Diwan mesti kekal merentas kitaran Livewire
// dan berubah dengan betul selepas navigasi penuh. Sebelum F1, setiap interaksi Livewire
// (termasuk telemetri tour itu sendiri) memusnahkan konteks pada 19/25 halaman produksi.
async function guideIdOf(page) {
    return page.locator('[data-diwan-help-runtime]').first().getAttribute('data-guide-id');
}

test('konteks Pembantu Diwan kekal selepas interaksi Livewire dan betul selepas navigasi', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    const browserErrors = monitorBrowserErrors(page);
    await loginTenant(page, tenantRoles[0]);

    await page.goto(`/app/${tenantSlug}/peti-masuk`);
    await expect.poll(() => guideIdOf(page)).toBe('tenant.peti-masuk');
    const runtime = page.locator('[data-diwan-help-runtime]').first();
    const helpUrlBefore = await runtime.getAttribute('data-help-url');
    expect(helpUrlBefore).toContain(encodeURIComponent(`/app/${tenantSlug}/peti-masuk`));

    // Interaksi Livewire sebenar pada halaman — kitaran update penuh.
    await page.getByRole('button', { name: /Muat Naik Dokumen/i }).click();
    await page.keyboard.press('Escape');
    await page.waitForTimeout(1_000);
    await expect.poll(() => guideIdOf(page)).toBe('tenant.peti-masuk');
    expect(await runtime.getAttribute('data-help-url')).toBe(helpUrlBefore);

    // Navigasi penuh melalui sidebar → mount semula → konteks halaman BAHARU.
    // dispatchEvent: anchor sidebar Filament ada handler Alpine; klik koordinat boleh
    // dipintas oleh tooltip/overlay pada viewport tertentu.
    await Promise.all([
        page.waitForURL(new RegExp(`/app/${tenantSlug}/records`), { timeout: 60_000 }),
        page.getByRole('link', { name: 'Rekod', exact: true }).first().dispatchEvent('click'),
    ]);
    await expect.poll(() => guideIdOf(page)).toBe('tenant.records');
    expect([...new Set(browserErrors)]).toEqual([]);
    await context.close();

    // Panel admin: 11 halaman superadmin paling teruk terjejas sebelum F1.
    const adminContext = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await disableAutomaticGuides(adminContext);
    const admin = await adminContext.newPage();
    const adminErrors = monitorBrowserErrors(admin);
    await loginSuperadmin(admin);
    await admin.goto('/admin/mosques');
    await expect.poll(() => guideIdOf(admin)).toBe('admin.mosques');
    await admin.getByRole('button', { name: /Cipta|Penapis/i }).first().dispatchEvent('click').catch(() => {});
    await admin.waitForTimeout(1_500);
    await expect.poll(() => guideIdOf(admin)).toBe('admin.mosques');
    expect([...new Set(adminErrors)]).toEqual([]);
    await adminContext.close();
});

test('tour yang ditutup tidak bermula semula semasa halaman terus digunakan (one-shot)', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    await loginTenant(page, tenantRoles[0]);

    await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=tenant.peti-masuk&langkah=0`);
    const popover = page.locator('.driver-popover');
    await expect(popover).toBeVisible();
    await popover.getByRole('button', { name: 'Tutup panduan' }).click();
    await expect(popover).toBeHidden();

    // Kontrak yang penting bagi pengguna: tour TIDAK muncul semula walaupun halaman
    // terus digunakan (interaksi Livewire lain selepas tour ditutup).
    await page.getByRole('button', { name: /Muat Naik Dokumen/i }).click();
    await page.keyboard.press('Escape');
    await page.waitForTimeout(1_500);
    await expect(popover).toBeHidden();

    // NOTA (disahkan e2e): `data-auto-start` dalam DOM kekal "1" sehingga muat penuh
    // berikutnya — HelpLauncher ialah komponen Livewire BERASINGAN, jadi interaksi pada
    // komponen lain tidak me-render semula ia, dan kitaran telemetrinya sendiri memanggil
    // skipRender (kontrak PELAN §2.2 nota 3). Nilai SERVER sudah padam — dibuktikan
    // HelpLauncherContextTest #5a/#5b/#5c. Selamat selagi SPA mati (penjaga #11):
    // bootRuntime hanya berjalan pada DOMContentLoaded = muat penuh yang mount() semula.
    // Jika SPA dihidupkan kelak, `livewire:navigated` akan membaca DOM lama — laksanakan
    // spesifikasi beku PELAN §2.2 nota 4 dahulu.

    // Muat penuh baharu dengan URL sama → tour bermula semula (kontrak (c) PELAN §2.2).
    await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=tenant.peti-masuk&langkah=0`);
    await expect(popover).toBeVisible();
    await popover.getByRole('button', { name: 'Tutup panduan' }).click();
    await context.close();
});

// ── F2 (PELAN-PEMBAIKAN §3.6) ────────────────────────────────────────────────────────
// Label mesti 1:1 dengan kelakuan; label BM pada fallback; fokus; auto-minimize modal.

test('F2a label=kelakuan: guide generik penuh tidak pernah memaparkan CTA tindakan', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    const browserErrors = monitorBrowserErrors(page);
    await loginTenant(page, tenantRoles[0]);

    // tenant.dashboard: 4 langkah, SEMUA sasaran generik (page-content/page-primary).
    // Sebelum F2, label dikira tanpa fallback generik → "Buat pada skrin" palsu (RR-10-06).
    await page.goto(`/app/${tenantSlug}?panduan=tenant.dashboard&langkah=0`);
    const popover = page.locator('.driver-popover');
    const cta = popover.locator('.driver-popover-next-btn');
    await expect(popover).toBeVisible();

    for (let langkah = 1; langkah <= 4; langkah += 1) {
        await expect(popover).toContainText(`${langkah} daripada 4`);
        const label = (await cta.textContent())?.trim();
        expect(label, `langkah ${langkah}`).toBe(langkah === 4 ? 'Selesai' : 'Seterusnya');
        expect(await popover.textContent()).not.toContain('Buat pada skrin');
        await cta.click();
    }
    await expect(popover).toBeHidden();
    expect([...new Set(browserErrors)]).toEqual([]);
    await context.close();
});

test('F2b/F2d fallback: label BM penuh + aria-modal, popover utama TIADA aria-modal', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    await loginTenant(page, tenantRoles[0]);

    // Popover UTAMA: tiada aria-modal (halaman masih boleh diguna melalui minimize).
    await page.goto(`/app/${tenantSlug}?panduan=tenant.dashboard&langkah=0`);
    const popover = page.locator('.driver-popover');
    await expect(popover).toBeVisible();
    expect(await popover.getAttribute('aria-modal')).toBeNull();
    await popover.getByRole('button', { name: 'Tutup panduan' }).click();

    // Fallback: guide public.login pada halaman ini tiada sasarannya → popover fallback.
    await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=screen.klasifikasi-peti-masuk&langkah=10`);
    const fallback = page.locator('.driver-popover');
    await expect(fallback).toBeVisible();
    if (await fallback.getByText('Tindakan belum tersedia').isVisible().catch(() => false)) {
        expect(await fallback.getAttribute('aria-modal')).toBe('true');
        const teks = (await fallback.textContent()) ?? '';
        expect(teks).not.toMatch(/Previous|\bNext\b|\d+ of \d+/);
    }
    await context.close();
});

test('F2d fokus: fokus awal masuk popover, kekal dalam kitaran vendor, pulang selepas ESC', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    await loginTenant(page, tenantRoles[0]);
    await page.goto(`/app/${tenantSlug}?panduan=tenant.dashboard&langkah=0`);
    await expect(page.locator('.driver-popover')).toBeVisible();

    const fokusDalamTour = () => page.evaluate(() => Boolean(
        document.activeElement?.closest('.driver-popover, .driver-active-element'),
    ));
    await expect.poll(fokusDalamTour, { message: 'fokus awal mesti masuk popover' }).toBe(true);

    // Kitaran Tab milik vendor Driver.js — fokus tidak boleh terlepas keluar.
    for (let i = 0; i < 6; i += 1) await page.keyboard.press('Tab');
    expect(await fokusDalamTour()).toBe(true);
    for (let i = 0; i < 6; i += 1) await page.keyboard.press('Shift+Tab');
    expect(await fokusDalamTour()).toBe(true);

    // ESC menutup tour DAN fokus pulang ke pencetus (tidak tersesat ke <body>).
    await page.keyboard.press('Escape');
    await expect(page.locator('.driver-popover')).toBeHidden();
    await expect.poll(() => page.evaluate(() => document.activeElement?.getAttribute?.('data-help-target')
        ?? document.activeElement?.tagName), { timeout: 10_000 }).not.toBe('BODY');
    await context.close();
});

/**
 * 🔴 F8 (12 Ogos 2026) — penjaga DETERMINISTIK untuk kecacatan yang memerahkan CI ~1 drp 3
 * di bawah beban, dan yang ujian "F2d fokus" di atas hanya menangkap secara kebetulan.
 *
 * Mekanisme, diukur dengan kawalan dua hala pada pelayar sebenar:
 *   `help.css:76` → `body.driver-active .diwan-help-launcher-button { visibility: hidden }`
 *   `focus()` semasa kelas ADA    → activeElement kekal **BODY** (no-op SENYAP)
 *   `focus()` selepas kelas TIADA → activeElement = **help-launcher**
 * Versi lama `clearFocusManagement()` mencuba SEKALI pada 50 ms. Jika Driver.js belum
 * membuang kelasnya menjelang saat itu, fokus hilang SELAMANYA — tiada percubaan kedua.
 *
 * Ujian ini tidak menunggu perlumbaan menembak: ia MEMAKSA keadaan lambat itu dengan
 * mengekalkan `body.driver-active` selepas ESC, kemudian menuntut fokus tetap pulang.
 * Terhadap kod lama ia MERAH setiap kali; terhadap kod baharu ia HIJAU setiap kali.
 */
test('F2d fokus: teardown LAMBAT tidak boleh menghilangkan fokus (penjaga deterministik)', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    await loginTenant(page, tenantRoles[0]);
    await page.goto(`/app/${tenantSlug}?panduan=tenant.dashboard&langkah=0`);
    await expect(page.locator('.driver-popover')).toBeVisible();

    // Kekalkan `body.driver-active` selama 400 ms SELEPAS Driver.js membuangnya — mensimulasi
    // mesin sibuk. 400 ms dipilih kerana ia jauh melepasi percubaan tunggal 50 ms yang lama.
    await page.evaluate(() => {
        const body = document.body;
        const pemerhati = new MutationObserver(() => {
            if (!body.classList.contains('driver-active')) {
                body.classList.add('driver-active');
                pemerhati.disconnect();
                window.setTimeout(() => body.classList.remove('driver-active'), 400);
            }
        });
        pemerhati.observe(body, { attributes: true, attributeFilter: ['class'] });
    });

    await page.keyboard.press('Escape');
    await expect(page.locator('.driver-popover')).toBeHidden();

    // Kawalan anti-vakum: keadaan lambat itu mesti BENAR-BENAR berlaku, jika tidak ujian ini
    // lulus tanpa menguji apa-apa.
    expect(await page.evaluate(() => document.body.classList.contains('driver-active')),
        'simulasi teardown lambat tidak berlaku — penjaga ini tidak menguji apa-apa').toBe(true);

    await expect.poll(() => page.evaluate(() => document.activeElement?.getAttribute?.('data-help-target')
        ?? document.activeElement?.tagName), { timeout: 10_000 }).toBe('help-launcher');
    await context.close();
});

test('F2c mobile: popover auto-minimize bila bertindih modal, tour kekal aktif', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 390, height: 664 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    await loginTenant(page, tenantRoles[0]);
    await page.goto(`/app/${tenantSlug}/peti-masuk`);
    await ensureInboxFixture(page);
    await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=screen.klasifikasi-peti-masuk&langkah=0`);

    const popover = page.locator('.driver-popover');
    await expect(popover).toBeVisible();
    await popover.locator('.driver-popover-next-btn').click();
    await page.getByRole('button', { name: 'Klasifikasikan', exact: true }).first().click();
    const modal = page.locator('.fi-modal-window:visible').last();
    await expect(modal).toBeVisible();

    // Tunggu KEADAAN (bukan masa): pil menunggu muncul = auto-minimize berlaku.
    await expect(page.locator('[data-diwan-tour-waiting]')).toBeVisible({ timeout: 30_000 });
    await expect(popover).toBeHidden();

    // guardAutomaticGuideFromDialogs TIDAK boleh menutup guide yang langkahnya menyasar modal.
    const masihAktif = await page.evaluate(() => document.body.classList.contains('driver-active'));
    expect(masihAktif, 'tour ditutup oleh guard modal — regresi').toBe(true);
    await context.close();
});

test('F2c timer bersih: ESC semasa tempoh baca tidak meninggalkan minimize tertunda', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 390, height: 664 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    const browserErrors = monitorBrowserErrors(page);
    await loginTenant(page, tenantRoles[0]);
    await page.goto(`/app/${tenantSlug}/peti-masuk`);
    await ensureInboxFixture(page);
    await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=screen.klasifikasi-peti-masuk&langkah=0`);

    const popover = page.locator('.driver-popover');
    await expect(popover).toBeVisible();
    await page.keyboard.press('Escape');           // tutup SEMASA tempoh baca
    await expect(popover).toBeHidden();
    await page.waitForTimeout(2_500);              // lepasi tempoh baca 1.8s
    await expect(page.locator('[data-diwan-tour-waiting]')).toHaveCount(0);
    await expect(popover).toBeHidden();

    // Dua guide berturutan tanpa muat semula → tiada keadaan bocor.
    await page.goto(`/app/${tenantSlug}?panduan=tenant.dashboard&langkah=0`);
    await expect(popover).toBeVisible();
    await expect(popover).toContainText('1 daripada 4');
    await popover.getByRole('button', { name: 'Tutup panduan' }).click();
    expect([...new Set(browserErrors)]).toEqual([]);
    await context.close();
});

test('F2 banner menunggu boleh diklik dengan TETIKUS semasa tour aktif', async ({ browser, baseURL }) => {
    // Regresi bug yang gate F0 temui dan disahkan HIDUP DI PRODUKSI (VERIFIKASI-F0 §17/§20):
    // vendor `.driver-active * { pointer-events: none }` mematikan banner, jadi pengguna yang
    // menekan "Buat pada skrin" lalu perlu arahan semula TERKANDAS — popover tersembunyi dan
    // butang penyelamat menolak klik. Ujian ini menggunakan klik tetikus SEBENAR (bukan
    // dispatchEvent) supaya ia gagal semula jika peraturan CSS itu dibuang.
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    await loginTenant(page, tenantRoles[0]);
    await page.goto(`/app/${tenantSlug}/peti-masuk`);
    await ensureInboxFixture(page);
    await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=screen.klasifikasi-peti-masuk&langkah=0`);

    const popover = page.locator('.driver-popover');
    await expect(popover).toBeVisible();
    await popover.locator('.driver-popover-next-btn').click();     // "Buat pada skrin"
    const banner = page.locator('[data-diwan-tour-waiting]');
    await expect(banner).toBeVisible();
    await expect(popover).toBeHidden();

    const show = banner.getByRole('button', { name: 'Tunjuk arahan' });
    expect(await show.evaluate((el) => getComputedStyle(el).pointerEvents)).not.toBe('none');
    await show.click({ timeout: 10_000 });                          // klik tetikus SEBENAR
    await expect(popover).toBeVisible();
    await expect(banner).toHaveCount(0);
    await context.close();
});

// ── F6-W0 (PELAN-PEMBAIKAN §7.2) — hotfix 6 defect mobile `centerCovered` ────────────────
// Dua guide (tenant.pelupusan 5 langkah + tenant.kegemaran 5) dahulu menyorot MAIN pada
// setiap langkah; pada 390×664 popover menutup ruang tengah dan pengguna tidak nampak apa
// yang dirujuk. Gate W0: keenam-enam langkah diuji desktop DAN mobile.
const W0_GUIDES = [
    { id: 'tenant.pelupusan', path: 'pelupusan', langkah: 5 },
    { id: 'tenant.kegemaran', path: 'kegemaran', langkah: 5 },
];

for (const viewport of [
    { nama: 'desktop', width: 1440, height: 1000 },
    { nama: 'mobile', width: 390, height: 664 },
]) {
    for (const guide of W0_GUIDES) {
        test(`F6-W0 ${viewport.nama}: ${guide.id} — sasaran spesifik, popover tidak menutup ruang tengah`, async ({ browser, baseURL }) => {
            const context = await browser.newContext({
                baseURL, viewport: { width: viewport.width, height: viewport.height },
            });
            await disableAutomaticGuides(context);
            const page = await context.newPage();
            const browserErrors = monitorBrowserErrors(page);
            await loginTenant(page, tenantRoles[0]);

            // Guide kegemaran merujuk item sebenar (klik untuk buka, bintang untuk buang),
            // jadi keadaan pengguna yang bermakna ialah "ada sekurang-kurangnya satu
            // kegemaran". Seeder demo tiada — jadi ujian menciptanya melalui UI sebenar.
            if (guide.id === 'tenant.kegemaran') {
                await page.goto(`/app/${tenantSlug}/records`);
                const rekod = page.locator('main a[href*="/records/"]').first();
                if (await rekod.count()) {
                    await rekod.dispatchEvent('click');
                    await page.waitForURL(/\/records\/\d+/, { timeout: 60_000 });
                    const bintang = page.getByRole('button', { name: /Kegemaran/i }).first();
                    if (await bintang.isVisible().catch(() => false)) {
                        await bintang.dispatchEvent('click');
                        await page.waitForTimeout(1_500);
                    }
                }
            }

            const popover = page.locator('.driver-popover');
            for (let i = 0; i < guide.langkah; i += 1) {
                await page.goto(`/app/${tenantSlug}/${guide.path}?panduan=${guide.id}&langkah=${i}`);
                await expect(page.locator('[data-diwan-help-runtime]'))
                    .toHaveAttribute('data-guide-id', guide.id);
                await expect(popover, `${guide.id}#${i + 1}`).toBeVisible();
                await expect(popover).toContainText(`${i + 1} daripada ${guide.langkah}`);

                // Tajuk bermakna — bukan placeholder "Langkah N" (10/10 W0).
                const tajuk = (await popover.locator('.driver-popover-title').textContent())?.trim();
                expect(tajuk, `${guide.id}#${i + 1} tajuk`).not.toMatch(/^Langkah \d+$/);

                // Sasaran yang disorot mesti elemen spesifik, BUKAN <main>/page-content.
                const aktif = page.locator('.driver-active-element');
                if (await aktif.count()) {
                    const t = await aktif.first().getAttribute('data-help-target');
                    expect(['page-content', 'page-primary', null], `${guide.id}#${i + 1} sorot generik`)
                        .not.toContain(t);
                }

                // Defect asal `centerCovered` berpunca daripada sasaran generik: popover
                // menyorot seluruh MAIN lalu duduk di tengah dan menutup segalanya. Dengan
                // sasaran spesifik, ukuran yang bermakna ialah: popover TIDAK menutup elemen
                // yang sedang dirujuknya — itulah yang pengguna perlu lihat.
                if (await aktif.count()) {
                    const bertindih = await popover.evaluate((el, sel) => {
                        const t = document.querySelector(sel);
                        if (!t) return false;
                        const a = el.getBoundingClientRect();
                        const b = t.getBoundingClientRect();

                        return a.left < b.right && a.right > b.left && a.top < b.bottom && a.bottom > b.top;
                    }, '.driver-active-element');
                    expect(bertindih, `${guide.id}#${i + 1} popover menutup sasarannya sendiri`).toBe(false);
                }
            }
            expect([...new Set(browserErrors)]).toEqual([]);
            await context.close();
        });
    }
}

// ── F8: `centerCovered` DIBERSARAKAN sebagai gate; penggantinya diluaskan ke kohort PENUH ────
//
// Keputusan pemilik (9 Ogos 2026, pilihan (a)): metrik `centerCovered` bersara kepada
// pemerhatian, dan penjaga "popover tidak menutup sasarannya sendiri" — yang sebelum ini hanya
// berjalan pada DUA guide W0 — diluaskan kepada kohort tenant penuh.
//
// Asas ukuran (📄 bukti/plan-f8/PENEMUAN-CENTERCOVERED.md §3C, kohort 124 langkah, 390×664):
//   centerCovered ditanda MERAH                      : 45/124
//   daripada 45 itu, sasaran TIDAK terlindung         : 45/45   -> positif-palsu 100%
//   popover menutup >=50% sasarannya                  : 0/124
//   sasaran di LUAR viewport                          : 0/124
//
// ⚠️ Mengapa ambang 50% dan BUKAN "sifar pertindihan" seperti penjaga W0: diukur, 47/124 langkah
// mempunyai pertindihan TEPI (1 pada 0%, 3 pada 1–9%, 17 pada 10–24%, 26 pada 25–49%; maksimum
// 49%). Meluaskan assertion "sifar pertindihan" secara verbatim akan menghasilkan 47 kegagalan
// yang BUKAN kecacatan. Ambang ini ialah kriteria yang saya UKUR sebagai 0/124.
// ⚠️ Yang saya TIDAK buktikan: bahawa pertindihan 25–49% tidak mengganggu pengguna. 26 langkah
// duduk dalam jalur itu dan ia DIDEDAHKAN dalam laporan, bukan dikubur. Jika pemilik mahu
// ambang lebih ketat, angka untuk memilihnya ada dalam artifak kohort.
const KOHORT_MOBILE = JSON.parse(readFileSync(
    'Audit Review Round Robin/bukti/plan-baseline/manifest.json', 'utf8',
)).catalogue.filter((g) => g.family === 'tenant');

test('F8 mobile: popover tidak MENGABURKAN sasarannya sendiri — kohort tenant PENUH', async ({ browser, baseURL }) => {
    test.setTimeout(1_800_000);
    const context = await browser.newContext({ baseURL, viewport: { width: 390, height: 664 } });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    await loginTenant(page, tenantRoles[0]);

    const cacat = [];
    let diukur = 0;
    for (const guide of KOHORT_MOBILE) {
        for (const step of guide.steps) {
            const laluan = String(step.route || guide.route || '').replace('{tenant}', tenantSlug) || `/app/${tenantSlug}`;
            await page.goto(`${baseURL}${laluan}?panduan=${guide.guide_id}&langkah=${step.index - 1}`,
                { waitUntil: 'domcontentloaded' });
            await page.waitForTimeout(2_200);
            const ukur = await page.evaluate(() => {
                const pop = document.querySelector('.driver-popover');
                const el = document.querySelector('.driver-active-element');
                if (!pop || !el) return null;
                const a = pop.getBoundingClientRect();
                const b = el.getBoundingClientRect();
                const luas = Math.max(0, b.width) * Math.max(0, b.height);
                const x = Math.max(0, Math.min(a.right, b.right) - Math.max(a.left, b.left))
                    * Math.max(0, Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top));

                return {
                    peratus: luas > 0 ? Math.round((x / luas) * 100) : 0,
                    dalamViewport: b.top < innerHeight && b.bottom > 0 && b.left < innerWidth && b.right > 0,
                };
            });
            if (!ukur) continue;                       // tiada popover/sasaran: diliputi gate lain
            diukur += 1;
            if (ukur.peratus >= 50 || !ukur.dalamViewport) {
                cacat.push(`${guide.guide_id}#${step.index} (${ukur.peratus}%${ukur.dalamViewport ? '' : ', LUAR viewport'})`);
            }
        }
    }

    // Anti-vakum: jika deep-link berhenti berfungsi, `diukur` jatuh dan senarai kosong akan
    // LULUS tanpa menguji apa-apa. Kohort ialah 124 langkah; terima sedikit variasi benih.
    expect(diukur).toBeGreaterThan(110, `hanya ${diukur} langkah diukur — deep-link mungkin rosak`);
    expect(cacat, `popover mengaburkan sasarannya: ${cacat.join(' · ')}`).toEqual([]);
    await context.close();
});
