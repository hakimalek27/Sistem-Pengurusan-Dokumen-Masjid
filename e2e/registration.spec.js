import { existsSync, readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const logPath = 'storage/logs/laravel.log';
const guideIds = JSON.parse(readFileSync('resources/help/guides.json', 'utf8')).guides.map((guide) => guide.id);

async function disableAutomaticGuides(context) {
    await context.addInitScript((ids) => {
        for (const id of ids) localStorage.setItem(`diwan-help-seen:${id}`, '1');
    }, guideIds);
}

/** Isi medan Livewire kalis morph — rujuk nota penuh dlm guidance.spec.js (fillStable). */
async function fillStable(locator, value) {
    await expect(async () => {
        await locator.fill(value);
        await expect(locator).toHaveValue(value, { timeout: 2_000 });
    }).toPass({ timeout: 30_000 });
}

function letterCode(seed) {
    let value = seed;
    let code = '';
    for (let index = 0; index < 4; index += 1) {
        code += String.fromCharCode(65 + (value % 26));
        value = Math.floor(value / 26);
    }
    return code;
}

test('pengguna baharu daftar, diluluskan superadmin dan masuk melalui magic link', async ({ browser, baseURL }) => {
    const seed = Date.now();
    const suffix = String(seed).slice(-8);
    const name = `Masjid E2E ${suffix}`;
    const slug = `masjid-e2e-${suffix}`;
    const code = letterCode(seed);
    const email = `admin-${suffix}@e2e.test`;
    const phone = `6011${suffix}`;
    // Persekitaran perawan (CI selepas migrate:fresh) belum ada laravel.log sehingga
    // log pertama ditulis — saiz awal 0, bukan ralat.
    const initialLogSize = existsSync(logPath) ? readFileSync(logPath, 'utf8').length : 0;

    const publicContext = await browser.newContext({ baseURL });
    await disableAutomaticGuides(publicContext);
    const registration = await publicContext.newPage();
    await registration.goto('/daftar');
    const nameInput = registration.locator('input[wire\\:model\\.blur="name"]');
    await fillStable(nameInput, name);
    // Blur nama mencetuskan RegisterMosque::updatedName() yang mengisi slug automatik.
    // Cetuskan blur EKSPLISIT (selectOption/fill medan lain tidak dijamin memfokus, jadi
    // menunggu slug tanpa blur = menunggu selamanya), kemudian tunggu morph itu MENDARAT
    // sebelum menyentuh medan lain: fill() yang berlumba dgn morph mengosongkan input,
    // morph memulihkan nilai, insertText menambah di hujung → nilai BERGANDA (slug
    // berganda menumbangkan CI run c90264c; pada `code` ia melanggar had 6 aksara).
    await nameInput.blur();
    const slugInput = registration.locator('input[wire\\:model="slug"]');
    await expect(slugInput).toHaveValue(slug, { timeout: 30_000 });
    await registration.locator('select[wire\\:model="state"]').selectOption('Selangor');
    await fillStable(registration.locator('input[wire\\:model="district"]'), 'Gombak');
    await fillStable(registration.locator('input[wire\\:model="code"]'), code);
    await registration.getByRole('button', { name: 'Seterusnya' }).click();
    await expect(registration.locator('[data-help-target="registration-admin"]')).toBeVisible();
    await fillStable(registration.locator('input[wire\\:model="admin_name"]'), 'Pentadbir E2E');
    await fillStable(registration.locator('input[wire\\:model="email"]'), email);
    await fillStable(registration.locator('input[wire\\:model="phone_wa"]'), phone);
    await registration.getByRole('button', { name: 'Seterusnya' }).click();
    const review = registration.locator('.registration-review');
    await expect(review).toContainText(name);
    await expect(review).toContainText('Pentadbir E2E');
    await registration.locator('input[type="checkbox"]').nth(0).check();
    await registration.locator('input[type="checkbox"]').nth(1).check();
    await registration.getByRole('button', { name: 'Hantar Permohonan' }).click();
    await expect(registration.getByText('Permohonan diterima!')).toBeVisible({ timeout: 60_000 });
    await publicContext.close();

    const adminContext = await browser.newContext({ baseURL });
    await disableAutomaticGuides(adminContext);
    const admin = await adminContext.newPage();
    await admin.goto('/admin/login');
    await admin.locator('input[id="form.login"]').fill('superadmin@diwan.test');
    await admin.locator('input[type="password"]').fill('password');
    await admin.getByRole('button', { name: /Log masuk/i }).click();
    await admin.waitForURL(/\/admin\/?$/, { timeout: 60_000 });
    await admin.goto('/admin/mosques');

    const row = admin.locator('tr').filter({ hasText: name });
    await expect(row).toBeVisible();
    await row.getByRole('button', { name: 'Lulus' }).click();
    await admin.getByRole('dialog').getByRole('button', { name: 'Sahkan' }).click();
    await expect(admin.getByText(/diluluskan.*disediakan/i)).toBeVisible({ timeout: 60_000 });
    await adminContext.close();

    await expect.poll(() => readFileSync(logPath, 'utf8').length, { timeout: 30_000 }).toBeGreaterThan(initialLogSize);
    const newLog = readFileSync(logPath, 'utf8').slice(initialLogSize);
    const token = newLog.match(/\/masuk\/([A-Za-z0-9]{64})/)?.[1];
    expect(token, 'Magic link tidak ditemui dalam mail log').toBeTruthy();

    const userContext = await browser.newContext({ baseURL });
    await disableAutomaticGuides(userContext);
    const user = await userContext.newPage();
    await user.goto(`/masuk/${token}`);
    // Fasa B: akaun baharu tiada kata laluan → gate paksa tetapkan dahulu.
    await user.waitForURL(/tetapkan-kata-laluan/, { timeout: 60_000 });
    await user.locator('input[wire\\:model="password"]').fill('RahsiaBaru123!');
    await user.locator('input[wire\\:model="password_confirmation"]').fill('RahsiaBaru123!');
    await user.getByRole('button', { name: /Simpan.*Teruskan/i }).click();
    // Fasa 4: admin baharu (onboarding belum selesai) diarah ke wizard persediaan.
    await user.waitForURL(new RegExp(`/app/${slug}/persediaan`), { timeout: 60_000 });
    await expect(user.locator('main')).toBeVisible();
    await expect(user.getByText(name).first()).toBeVisible();
    await user.goto(`/app/${slug}/classification-nodes`);
    await expect(user.locator('main')).toBeVisible();
    await expect(user.getByText('Klasifikasi Fail').first()).toBeVisible();
    await userContext.close();
});
