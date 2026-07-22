import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const catalog = JSON.parse(readFileSync('resources/help/guides.json', 'utf8'));
const guideIds = catalog.guides.map((guide) => guide.id);
const defaultPassword = process.env.E2E_PROD_PASSWORD ?? process.env.MANUAL_DEMO_PASSWORD ?? 'password';
const tenantSlug = process.env.E2E_PROD_TENANT ?? 'mam';
const crossTenantSlug = process.env.E2E_PROD_CROSS_TENANT ?? 'man';
const filePrefix = process.env.E2E_PROD_FILE_PREFIX ?? 'MAM';
const loginDelayMs = Number(process.env.E2E_PROD_ROLE_LOGIN_DELAY_MS ?? process.env.E2E_ROLE_LOGIN_DELAY_MS ?? 15_000);
let lastLoginAt = 0;

const localTenantRoles = [
    { role: 'admin_masjid', email: 'admin_masjid@demo.test', pages: 25 },
    { role: 'pengerusi', email: 'pengerusi@demo.test', pages: 17 },
    { role: 'setiausaha', email: 'setiausaha@demo.test', pages: 15 },
    { role: 'bendahari', email: 'bendahari@demo.test', pages: 15 },
    { role: 'nazir', email: 'nazir@demo.test', pages: 13 },
    { role: 'ketua_imam', email: 'ketua_imam@demo.test', pages: 13 },
    { role: 'ajk', email: 'ajk@demo.test', pages: 13 },
    { role: 'audit', email: 'audit@demo.test', pages: 14 },
];
const tenantRoles = process.env.E2E_PROD_ROLE_ACCOUNTS
    ? JSON.parse(process.env.E2E_PROD_ROLE_ACCOUNTS)
    : localTenantRoles;
const superadminAccount = {
    email: process.env.E2E_PROD_SUPERADMIN_EMAIL ?? 'superadmin@diwan.test',
    password: process.env.E2E_PROD_SUPERADMIN_PASSWORD ?? defaultPassword,
};

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
    await page.locator('input[id="form.login"]').fill(account.email);
    await page.locator('input[type="password"]').fill(account.password ?? defaultPassword);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL((url) => url.pathname.replace(/\/$/, '') === `/app/${tenantSlug}`, { timeout: 60_000 });
    lastLoginAt = Date.now();
}

async function loginSuperadmin(page) {
    await waitForLoginSlot(page);
    await page.goto('/admin/login');
    await page.locator('input[id="form.login"]').fill(superadminAccount.email);
    await page.locator('input[type="password"]').fill(superadminAccount.password);
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

async function closeGuideIfOpen(page) {
    const close = page.locator('.driver-popover-close-btn');
    if (await close.isVisible().catch(() => false)) await close.click();
}

async function ensureInboxFixture(page) {
    if (await page.getByRole('button', { name: 'Klasifikasikan', exact: true }).first().isVisible().catch(() => false)) return;

    const marker = Date.now();
    await page.getByRole('button', { name: /Muat Naik Dokumen/i }).click();
    const dialog = page.getByRole('dialog');
    await dialog.locator('input[type="file"]').setInputFiles({
        name: `Dokumen panduan E2E ${marker}.txt`,
        mimeType: 'text/plain',
        buffer: Buffer.from(`Dokumen ujian panduan ${marker}.`),
    });
    await expect(dialog.getByText('Upload complete', { exact: true })).toBeVisible({ timeout: 60_000 });
    const submit = dialog.getByRole('button', { name: 'Hantar', exact: true });
    await expect(submit).toBeEnabled({ timeout: 60_000 });
    await submit.click();
    await expect(page.getByText('1 dokumen dimuat naik ke Peti Masuk.')).toBeVisible({ timeout: 60_000 });
}

async function assertFloatingHelpLauncher(page, viewportHeight) {
    const launcher = page.locator('[data-help-target="help-launcher"]');
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
    await popover.getByRole('button', { name: 'Buat pada skrin' }).click();
    await expect(page.locator('[data-diwan-tour-waiting]')).toContainText('Panduan menunggu');
    await expect(page.locator('[data-diwan-tour-waiting]')).toHaveAttribute('role', 'status');
    await expect(popover).toBeHidden();
    expect(await page.evaluate(() => Boolean(document.activeElement?.closest('[data-help-target="registration-organisation"]')))).toBe(true);

    const organisation = page.locator('[data-help-target="registration-organisation"]');
    await organisation.locator('input').nth(0).fill(`Masjid Tour ${Date.now()}`);
    await organisation.locator('select').selectOption({ label: 'Selangor' });
    await organisation.locator('input').nth(1).fill('Petaling');
    await organisation.locator('input').nth(2).fill('TURAA');
    await organisation.locator('input').nth(3).fill(`tour-${Date.now()}`);
    await page.locator('[data-help-target="registration-next"]').click();
    await expect(page.locator('[data-help-target="registration-admin"]')).toBeVisible();
    await expect(popover).toContainText('2 daripada 4');
    await popover.getByRole('button', { name: 'Buat pada skrin' }).click();

    const admin = page.locator('[data-help-target="registration-admin"]');
    await admin.locator('input').nth(0).fill('Pentadbir Tour');
    await admin.locator('input').nth(1).fill(`tour-${Date.now()}@example.test`);
    await admin.locator('input').nth(2).fill('60123456789');
    await page.locator('[data-help-target="registration-next"]').click();
    await expect(page.locator('[data-help-target="registration-consent"]')).toBeVisible();
    await expect(popover).toContainText('3 daripada 4');
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
    await popover.getByRole('button', { name: 'Buat pada skrin' }).click();
    await classify.click();

    const modal = page.locator('.fi-modal-window:visible').last();
    await expect(modal).toBeVisible();
    await expect(page.locator('[data-help-target="classification-source"]:visible')).toBeVisible();
    await expect(popover).toContainText('2 daripada 11');
    await popover.getByRole('button', { name: 'Buat pada skrin' }).click();
    await modal.getByRole('button', { name: 'Seterus', exact: true }).click();

    await expect(page.locator('[data-help-target="classification-metadata"]:visible')).toBeVisible();
    await expect(popover).toContainText('3 daripada 11');
    const recordType = page.locator('#mountedActionSchema0\\.record_type');
    if (!await recordType.inputValue()) await recordType.selectOption('surat_menyurat');
    await page.waitForTimeout(400);
    await page.locator('#mountedActionSchema0\\.direction').selectOption('masuk');
    await popover.getByRole('button', { name: 'Saya sudah buat' }).click();
    await expect(popover).toContainText('4 daripada 11');
    await popover.getByRole('button', { name: 'Saya sudah buat' }).click();
    await expect(popover).toContainText('5 daripada 11');
    await popover.getByRole('button', { name: 'Buat pada skrin' }).click();
    await modal.getByRole('button', { name: 'Seterus', exact: true }).click();

    await expect(page.locator('[data-help-target="classification-file"]:visible')).toBeVisible();
    await expect(popover).toContainText('6 daripada 11');
    const fileStep = modal.locator('form.fi-active');
    await fileStep.locator('.fi-select-input-btn').first().click();
    await page.getByRole('option', { name: new RegExp(`${filePrefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\.`) }).first().click();
    await page.locator('#mountedActionSchema0\\.sensitivity').selectOption('dalaman');
    await popover.getByRole('button', { name: 'Saya sudah buat' }).click();
    await expect(popover).toContainText('7 daripada 11');
    await popover.getByRole('button', { name: 'Buat pada skrin' }).click();
    await modal.getByRole('button', { name: 'Seterus', exact: true }).click();

    await expect(page.locator('[data-help-target="classification-minit"]:visible')).toBeVisible();
    await expect(popover).toContainText('8 daripada 11');
    await popover.getByRole('button', { name: 'Saya sudah buat' }).click();
    await expect(popover).toContainText('9 daripada 11');
    await popover.getByRole('button', { name: 'Buat pada skrin' }).click();
    await modal.getByRole('button', { name: 'Seterus', exact: true }).click();

    await expect(page.locator('[data-help-target="classification-review"]:visible')).toBeVisible();
    await expect(popover).toContainText('10 daripada 11');
    await popover.getByRole('button', { name: 'Saya sudah buat' }).click();
    await expect(popover).toContainText('11 daripada 11');
    await expect(page.locator('[data-help-target="classification-submit"]:visible')).toBeVisible();
    await popover.getByRole('button', { name: 'Tutup panduan' }).click();
    await modal.getByRole('button', { name: 'Tutup' }).click();
    await expect(modal).toBeHidden();
    expect([...new Set(browserErrors)]).toEqual([]);
    await context.close();
});

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

    const next = () => modal.getByRole('button', { name: 'Seterus', exact: true });
    await expect(modal.locator('form.fi-active')).toContainText('Asal dokumen');
    await next().click();
    await expect(modal.locator('form.fi-active')).toContainText('Ruj. Kami ialah rujukan masjid');
    const recordType = page.locator('#mountedActionSchema0\\.record_type');
    if (!await recordType.inputValue()) await recordType.selectOption('surat_menyurat');
    await page.waitForTimeout(500);
    await page.locator('#mountedActionSchema0\\.direction').selectOption('masuk');
    await next().click();

    await expect(modal.locator('form.fi-active')).toContainText('Tahap Akses Rekod');
    const fileStep = modal.locator('form.fi-active');
    await fileStep.locator('.fi-select-input-btn').first().click();
    await page.getByRole('option', { name: new RegExp(`${filePrefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\.`) }).first().click();
    await page.locator('#mountedActionSchema0\\.sensitivity').selectOption('dalaman');
    await assertModalFits();
    await next().click();

    await expect(modal.locator('form.fi-active')).toContainText('Untuk Tindakan (Minit)');
    await expect(modal.locator('form.fi-active')).toContainText('Untuk Makluman (s.k.)');
    await page.locator('#mountedActionSchema0\\.minit_priority').selectOption('biasa');
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
