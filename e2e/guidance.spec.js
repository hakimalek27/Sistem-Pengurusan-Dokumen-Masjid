import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const catalog = JSON.parse(readFileSync('resources/help/guides.json', 'utf8'));
const guideIds = catalog.guides.map((guide) => guide.id);
const password = process.env.MANUAL_DEMO_PASSWORD ?? 'password';
const loginDelayMs = Number(process.env.E2E_ROLE_LOGIN_DELAY_MS ?? 15_000);
let lastLoginAt = 0;

const tenantRoles = [
    { role: 'admin_masjid', email: 'admin_masjid@demo.test', pages: 25 },
    { role: 'pengerusi', email: 'pengerusi@demo.test', pages: 17 },
    { role: 'setiausaha', email: 'setiausaha@demo.test', pages: 15 },
    { role: 'bendahari', email: 'bendahari@demo.test', pages: 15 },
    { role: 'nazir', email: 'nazir@demo.test', pages: 13 },
    { role: 'ketua_imam', email: 'ketua_imam@demo.test', pages: 13 },
    { role: 'ajk', email: 'ajk@demo.test', pages: 13 },
    { role: 'audit', email: 'audit@demo.test', pages: 14 },
];

async function disableAutomaticGuides(context) {
    await context.addInitScript((ids) => {
        for (const id of ids) localStorage.setItem(`diwan-help-seen:${id}`, '1');
    }, guideIds);
}

async function waitForLoginSlot(page) {
    const remaining = loginDelayMs - (Date.now() - lastLoginAt);
    if (remaining > 0) await page.waitForTimeout(remaining);
}

async function loginTenant(page, email) {
    await waitForLoginSlot(page);
    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill(email);
    await page.locator('input[type="password"]').fill(password);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(/\/app\/mam\/?$/, { timeout: 60_000 });
    lastLoginAt = Date.now();
}

async function loginSuperadmin(page) {
    await waitForLoginSlot(page);
    await page.goto('/admin/login');
    await page.locator('input[id="form.login"]').fill('superadmin@diwan.test');
    await page.locator('input[type="password"]').fill(password);
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
        await expect(superadmin.locator('[data-help-target="help-launcher"]')).toBeVisible();
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
                await loginTenant(page, account.email);

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

                const help = await page.goto('/app/mam/bantuan');
                expect(help?.status()).toBe(200);
                await expect(page.locator('[data-help-target="help-center"]')).toBeVisible();
                await expect(page.locator('[data-help-target="help-launcher"]')).toBeVisible();
                await expect(page.locator('.diwan-help-result').first()).toBeVisible();
                await assertNoHorizontalPageOverflow(page);
                expect([...new Set(browserErrors)], `${account.role} ${viewport.name}`).toEqual([]);

                const crossTenant = await page.goto('/app/man/records');
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
    await loginTenant(page, 'admin_masjid@demo.test');

    await page.goto('/app/mam/peti-masuk?panduan=tenant.peti-masuk&langkah=0');
    const popover = page.locator('.driver-popover');
    await expect(popover).toBeVisible();
    await expect(popover).toContainText('1 daripada 6');
    await popover.getByRole('button', { name: 'Close' }).click();
    await expect(popover).toBeHidden();
    await expect.poll(() => page.evaluate(() => JSON.parse(sessionStorage.getItem('diwan-help:tenant.peti-masuk') ?? '{}').event)).toBe('dismissed');

    await page.goto('/app/mam/peti-masuk?panduan=tenant.peti-masuk&langkah=0');
    await expect(popover).toBeVisible();
    await popover.getByRole('button', { name: 'Seterusnya' }).click();
    await expect(popover).toContainText('2 daripada 6');
    await popover.getByRole('button', { name: 'Close' }).click();
    await page.waitForTimeout(700);

    await page.goto('/app/mam/bantuan?asal=%2Fapp%2Fmam%2Fpeti-masuk');
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

    await page.goto('/app/mam/bantuan?asal=%2Fapp%2Fmam%2Fpeti-masuk');
    await page.locator('#help-query').fill('Peti Masuk');
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    const repeatResult = page.locator('.diwan-help-result').filter({ has: page.getByRole('heading', { name: 'Peti Masuk', exact: true }) }).first();
    await repeatResult.getByRole('button', { name: 'Mulakan panduan' }).click();
    await expect(popover).toContainText('1 daripada 6');
    await popover.getByRole('button', { name: 'Close' }).click();

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
    await expect(popover).toContainText('1 daripada 3');
    await expect(page.locator('[data-help-target="registration-organisation"]')).toBeVisible();
    await popover.getByRole('button', { name: 'Close' }).click();
    await expect(popover).toBeHidden();

    await page.reload();
    await page.waitForTimeout(800);
    await expect(popover).toBeHidden();
    const launcher = page.locator('[data-help-target="help-launcher"]');
    await expect(launcher).toBeVisible();
    await launcher.click();
    await expect(page).toHaveURL(/\/bantuan\?asal=/);
    await expect(page.locator('[data-help-target="help-center"]')).toBeVisible();
    await assertNoHorizontalPageOverflow(page);
    expect([...new Set(browserErrors)]).toEqual([]);
    await context.close();
});

async function verifyClassificationWizard(browser, baseURL, account, viewport) {
    const context = await browser.newContext({ baseURL, viewport });
    await disableAutomaticGuides(context);
    const page = await context.newPage();
    const browserErrors = monitorBrowserErrors(page);
    await loginTenant(page, account.email);
    await page.goto('/app/mam/peti-masuk');
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
    await page.getByRole('option', { name: /MAM\./ }).first().click();
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
