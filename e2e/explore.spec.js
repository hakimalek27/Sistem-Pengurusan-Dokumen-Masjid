import { expect, test } from '@playwright/test';

const tenantRoles = [
    'admin_masjid',
    'pengerusi',
    'setiausaha',
    'bendahari',
    'nazir',
    'ketua_imam',
    'ajk',
    'audit',
];

async function summary(page) {
    return {
        url: page.url(),
        title: await page.title(),
        headings: (await page.locator('h1,h2,h3').allTextContents()).map((text) => text.replace(/\s+/g, ' ').trim()).filter(Boolean),
        buttons: (await page.getByRole('button').allTextContents()).map((text) => text.replace(/\s+/g, ' ').trim()).filter(Boolean),
        tabs: (await page.getByRole('tab').allTextContents()).map((text) => text.replace(/\s+/g, ' ').trim()).filter(Boolean),
        links: await page.locator('a[href]').evaluateAll((nodes) => nodes.map((node) => ({
            text: (node.textContent ?? '').replace(/\s+/g, ' ').trim(),
            href: node.getAttribute('href'),
        })).filter((item) => item.text)),
    };
}

test('inventori panel superadmin', async ({ page }) => {
    await page.goto('/admin/login');
    await expect(page).toHaveTitle(/Diwan/i);
    await page.locator('input[id="form.login"]').fill('superadmin@diwan.test');
    await page.locator('input[type="password"]').fill('password');
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(/\/admin\/?$/, { timeout: 60_000 });

    const pages = [];
    for (const path of [
        '/admin',
        '/admin/mosques',
        '/admin/mosques/1',
        '/admin/mosques/1/edit',
        '/admin/storage-orders',
        '/admin/users',
        '/admin/tetapan-platform',
    ]) {
        const response = await page.goto(path);
        expect(response?.status(), `${path} mesti 200`).toBe(200);
        await expect(page.locator('main')).toBeVisible();
        if (path === '/admin/storage-orders') {
            await expect(page.getByRole('link', { name: 'Cipta', exact: true })).toHaveCount(0);
            await expect(page.getByRole('button', { name: 'Cipta', exact: true })).toHaveCount(0);
        }
        pages.push(await summary(page));
    }

    console.log(JSON.stringify(pages, null, 2));
});

test('inventori dan smoke semua peranan tenant', async ({ browser, baseURL }) => {
    const inventory = [];

    for (const role of tenantRoles) {
        const context = await browser.newContext({ baseURL });
        const page = await context.newPage();
        const browserErrors = [];

        page.on('pageerror', (error) => browserErrors.push(error.message));
        page.on('console', (message) => {
            if (message.type() === 'error') {
                browserErrors.push(message.text());
            }
        });

        await page.goto('/app/login');
        await page.locator('input[id="form.login"]').fill(`${role}@demo.test`);
        await page.locator('input[type="password"]').fill('password');
        await page.getByRole('button', { name: /Log masuk/i }).click();
        await page.waitForURL(/\/app\/mam\/?$/, { timeout: 60_000 });
        await expect(page.locator('main')).toBeVisible();

        const navigation = await page.locator('.fi-sidebar a[href]').evaluateAll((nodes) => nodes
            .filter((node) => node.offsetParent !== null)
            .map((node) => ({
                label: (node.textContent ?? '').replace(/\s+/g, ' ').trim(),
                href: node.href,
            }))
            .filter((item, index, items) => item.label && items.findIndex((other) => other.href === item.href) === index));

        const pages = [];
        for (const item of navigation) {
            const response = await page.goto(item.href);
            expect(response?.status(), `${role}: ${item.href} mesti 200`).toBe(200);
            await expect(page.locator('main')).toBeVisible();
            pages.push({
                ...item,
                status: response?.status() ?? null,
                heading: (await page.locator('h1').first().textContent().catch(() => null))?.replace(/\s+/g, ' ').trim() ?? null,
                buttons: (await page.getByRole('button').allTextContents()).map((text) => text.replace(/\s+/g, ' ').trim()).filter(Boolean),
                tabs: (await page.getByRole('tab').allTextContents()).map((text) => text.replace(/\s+/g, ' ').trim()).filter(Boolean),
            });
        }

        expect(browserErrors, `${role}: ralat browser sebelum ujian silang tenant`).toEqual([]);
        const crossTenant = await page.goto('/app/man/records');
        expect(crossTenant?.status(), `${role}: tenant lain mesti disembunyikan`).toBe(404);
        inventory.push({
            role,
            navigation,
            pages,
            crossTenant: {
                status: crossTenant?.status() ?? null,
                finalUrl: page.url(),
            },
            browserErrors: [...new Set(browserErrors)],
        });

        await context.close();
    }

    console.log(JSON.stringify(inventory, null, 2));
});
