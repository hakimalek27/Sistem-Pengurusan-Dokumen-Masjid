import { parse } from 'node:path';
import { expect, test } from '@playwright/test';
import { uploadComplete } from './helpers/upload.js';

test('kerani muat naik imej, OCR siap dan teks boleh dicari', async ({ page }) => {
    const files = [process.env.SPDM_OCR_FIXTURE_1, process.env.SPDM_OCR_FIXTURE_2].filter(Boolean);
    const terms = [process.env.SPDM_OCR_TERM_1, process.env.SPDM_OCR_TERM_2].filter(Boolean);
    test.skip(files.length !== 2 || terms.length !== 2, 'Tetapkan dua fail dan dua istilah OCR.');
    // Tajuk rekod = nama fail fixture (F0: penapis lama /WhatsApp Image/ diganti — nama fixture
    // beku D11 #16a/b ialah sample-scan-*.png; ujian kini deterministik terhadap fail sebenar).
    const titlePattern = new RegExp(files.map((f) => parse(f).name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|'), 'i');

    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
    await page.locator('input[type="password"]').fill('password');
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(/\/app\/mam\/?$/, { timeout: 60_000 });

    await page.goto('/app/mam/peti-masuk');
    await page.getByRole('button', { name: /Muat Naik Dokumen/i }).click();
    // Tunggu FilePond siap sebelum memasukkan fail — rujuk e2e/helpers/upload.js
    // (setInputFiles sebelum JS lazy dimuat = tiada permintaan upload langsung).
    await expect(page.locator('.filepond--root').first()).toBeVisible({ timeout: 60_000 });
    const fileInput = page.locator('input[type="file"]').last();
    await fileInput.setInputFiles(files);
    await expect(uploadComplete(page)).toHaveCount(2, { timeout: 60_000 });
    await page.waitForTimeout(3_000);

    const submit = page.getByRole('button', { name: 'Hantar' }).last();
    await expect(submit).toBeEnabled({ timeout: 60_000 });
    await submit.click();
    await expect(page.getByText(/2 dokumen dimuat naik ke Peti Masuk/i)).toBeVisible({ timeout: 120_000 });

    for (const term of terms) {
        await page.goto('/app/mam/carian');
        // `wire:model="query"` — BUKAN placeholder: teks sebenar ialah "Tajuk, rujukan atau
        // kandungan OCR". Selector lama (`placeholder*="Cari tajuk"`) tidak pernah wujud;
        // ia tidak tertangkap kerana ujian ini SENTIASA di-skip sebelum fixture OCR dikomit
        // pada F0, jadi gate ini baru berjalan buat kali pertama (CI run 30770018483).
        await page.locator('input[wire\\:model="query"]').fill(term);
        await page.getByRole('button', { name: /^Cari$/ }).click();
        await expect(page.getByText(/Tiada hasil ditemui/i)).not.toBeVisible({ timeout: 60_000 });
        await expect(page.locator('main a[href*="/r/"]').filter({ hasText: titlePattern }).first()).toBeVisible();
    }
});
