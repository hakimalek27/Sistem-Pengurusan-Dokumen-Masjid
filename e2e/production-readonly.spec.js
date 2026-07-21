import { expect, test } from '@playwright/test';

function productionRoleAccounts() {
    const raw = process.env.E2E_PROD_ROLE_ACCOUNTS;

    if (!raw) {
        return [];
    }

    return JSON.parse(raw);
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

test('production read-only smoke untuk halaman DDMS lanjutan', async ({ page }) => {
    test.skip(process.env.E2E_PRODUCTION !== '1', 'Ujian ini hanya dijalankan secara eksplisit terhadap production.');

    const email = process.env.E2E_PROD_EMAIL;
    const password = process.env.E2E_PROD_PASSWORD;
    const tenant = process.env.E2E_PROD_TENANT ?? 'smoke';
    expect(email).toBeTruthy();
    expect(password).toBeTruthy();

    const browserErrors = [];
    page.on('pageerror', (error) => browserErrors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error') {
            browserErrors.push(message.text());
        }
    });

    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill(email);
    await page.locator('input[type="password"]').fill(password);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(new RegExp(`/app/${tenant}/?$`), { timeout: 60_000 });

    for (const path of [
        '',
        '/carian',
        '/kegemaran',
        '/delegasi',
        '/pembetulan-rekod',
        '/peti-masuk',
        '/records',
        '/registry-files',
    ]) {
        const response = await page.goto(`/app/${tenant}${path}`);
        expect(response?.status(), path || '/').toBe(200);
        await expect(page.locator('main')).toBeVisible();
    }

    expect(browserErrors).toEqual([]);
    const crossTenant = await page.goto('/app/mamad/records');
    expect(crossTenant?.status()).toBe(404);
});

test('production role matrix: satu BrowserContext berasingan setiap role', async ({ browser, baseURL }) => {
    test.skip(process.env.E2E_PRODUCTION !== '1', 'Ujian ini hanya dijalankan secara eksplisit terhadap production.');
    test.setTimeout(600_000);

    const accounts = productionRoleAccounts();
    const loginDelayMs = Number(process.env.E2E_PROD_ROLE_LOGIN_DELAY_MS ?? 0);
    test.skip(accounts.length === 0, 'Set E2E_PROD_ROLE_ACCOUNTS dengan akaun production yang diluluskan.');

    const contexts = new Set();
    const inventory = [];

    for (const [index, account] of accounts.entries()) {
        const context = await browser.newContext({ baseURL });
        contexts.add(context);
        const page = await context.newPage();
        const browserErrors = [];

        page.on('pageerror', (error) => browserErrors.push(error.message));
        page.on('console', (message) => {
            if (message.type() === 'error') {
                browserErrors.push(message.text());
            }
        });

        try {
            if (index > 0 && loginDelayMs > 0) {
                await page.waitForTimeout(loginDelayMs);
            }
            await page.goto('/app/login');
            await page.locator('input[id="form.login"]').fill(account.email);
            await page.locator('input[type="password"]').fill(account.password);
            await page.getByRole('button', { name: /Log masuk/i }).click();
            await page.waitForURL(new RegExp(`/app/${account.tenant}/?$`), { timeout: 60_000 });
            await expect(page.locator('main')).toBeVisible();

            const navigation = await visibleNavigation(page);
            const pages = [];

            for (const item of navigation) {
                const response = await page.goto(item.href);
                expect(response?.status(), `${account.role}: ${item.href} mesti 200`).toBe(200);
                await expect(page.locator('main')).toBeVisible();
                pages.push({
                    label: item.label,
                    path: new URL(item.href).pathname,
                    status: response?.status() ?? null,
                    buttons: await page.getByRole('button').count(),
                    links: await page.locator('a[href]').count(),
                });
            }

            const browserErrorsBeforeCrossTenant = [...new Set(browserErrors)];
            expect(browserErrorsBeforeCrossTenant, `${account.role}: tiada pageerror/console error`).toEqual([]);
            const crossTenant = await page.goto(account.crossTenantPath ?? '/app/mamad/records');
            expect(crossTenant?.status(), `${account.role}: tenant lain mesti 404`).toBe(404);

            inventory.push({
                context: index + 1,
                role: account.role,
                tenant: account.tenant,
                pages: pages.length,
                pagePaths: pages.map((item) => item.path),
                crossTenant: crossTenant?.status() ?? null,
                browserErrors: browserErrorsBeforeCrossTenant,
            });
        } finally {
            await context.close();
        }
    }

    expect(contexts.size, 'setiap role mesti mendapat BrowserContext berasingan').toBe(accounts.length);
    console.log(JSON.stringify({ contextCount: contexts.size, roles: inventory }, null, 2));
});
