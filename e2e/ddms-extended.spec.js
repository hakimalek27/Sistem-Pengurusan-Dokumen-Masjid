import { expect, test } from '@playwright/test';

async function login(page) {
    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
    await page.locator('input[type="password"]').fill('password');
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(/\/app\/mam\/?$/);
}

test('carian lanjutan, carian tersimpan dan kegemaran boleh digunakan', async ({ page }) => {
    await login(page);
    await page.goto('/app/mam/carian');
    await expect(page.getByRole('heading', { name: 'Carian Rekod' })).toBeVisible();
    await page.locator('input[wire\\:model="savedSearchName"]').fill(`Carian E2E ${Date.now()}`);
    await page.locator('input[wire\\:model="sender"]').fill('Masjid');
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(page.getByText('Carian disimpan.')).toBeVisible();
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    await expect(page.getByText(/hasil ditemui/)).toBeVisible();

    await page.goto('/app/mam/registry-files');
    const favourite = page.getByRole('button', { name: 'Kegemaran', exact: true }).first();
    await expect(favourite).toBeVisible();
    await favourite.click();
    await expect(page.getByText(/Fail ditambah ke kegemaran|Fail dibuang daripada kegemaran/)).toBeVisible();
    await page.goto('/app/mam/kegemaran');
    await expect(page.getByRole('heading', { name: 'Rekod & Fail Kegemaran' })).toBeVisible();
});

test('rekod memaparkan tindakan pembetulan, provenance dan viewer section', async ({ page }) => {
    await login(page);
    await page.goto('/app/mam/records');
    const recordLink = page.locator('a[href*="/app/mam/records/"]').first();
    await expect(recordLink).toBeVisible();
    await recordLink.click();
    await expect(page.getByRole('button', { name: 'Mohon Pembetulan' })).toBeVisible();
    await expect(page.getByText('Tarikh & Masa Upload')).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Lampiran & Versi' })).toBeVisible();
    await page.getByRole('button', { name: 'Mohon Pembetulan' }).click();
    await expect(page.getByLabel('Sebab Rekod Salah Tawan*')).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Tajuk' })).toBeVisible();
});
