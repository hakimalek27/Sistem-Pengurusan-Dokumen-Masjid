import { expect, test } from '@playwright/test';

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
