import { expect, test } from '@playwright/test';

test('kerani muat naik imej, OCR siap dan teks boleh dicari', async ({ page }) => {
    const files = [process.env.SPDM_OCR_FIXTURE_1, process.env.SPDM_OCR_FIXTURE_2].filter(Boolean);
    const terms = [process.env.SPDM_OCR_TERM_1, process.env.SPDM_OCR_TERM_2].filter(Boolean);
    test.skip(files.length !== 2 || terms.length !== 2, 'Tetapkan dua fail dan dua istilah OCR.');

    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
    await page.locator('input[type="password"]').fill('password');
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(/\/app\/mam\/?$/, { timeout: 60_000 });

    await page.goto('/app/mam/peti-masuk');
    await page.getByRole('button', { name: /Muat Naik Dokumen/i }).click();
    const fileInput = page.locator('input[type="file"]').last();
    await fileInput.setInputFiles(files);
    await expect(page.getByText('Upload complete')).toHaveCount(2, { timeout: 60_000 });
    await page.waitForTimeout(3_000);

    const submit = page.getByRole('button', { name: 'Hantar' }).last();
    await expect(submit).toBeEnabled({ timeout: 60_000 });
    await submit.click();
    await expect(page.getByText(/2 dokumen dimuat naik ke Peti Masuk/i)).toBeVisible({ timeout: 120_000 });

    for (const term of terms) {
        await page.goto('/app/mam/carian');
        await page.locator('input[placeholder*="Cari tajuk"]').fill(term);
        await page.getByRole('button', { name: /^Cari$/ }).click();
        await expect(page.getByText(/Tiada hasil ditemui/i)).not.toBeVisible({ timeout: 60_000 });
        await expect(page.locator('main a[href*="/r/"]').filter({ hasText: /WhatsApp Image/i }).first()).toBeVisible();
    }
});
