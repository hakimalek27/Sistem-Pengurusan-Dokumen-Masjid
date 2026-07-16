import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const logPath = 'storage/logs/laravel.log';

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
    const initialLogSize = readFileSync(logPath, 'utf8').length;

    const publicContext = await browser.newContext({ baseURL });
    const registration = await publicContext.newPage();
    await registration.goto('/daftar');
    await registration.locator('input[wire\\:model\\.blur="name"]').fill(name);
    await registration.locator('select[wire\\:model="state"]').selectOption('Selangor');
    await registration.locator('input[wire\\:model="district"]').fill('Gombak');
    await registration.locator('input[wire\\:model="code"]').fill(code);
    await registration.locator('input[wire\\:model="slug"]').fill(slug);
    await registration.locator('input[wire\\:model="admin_name"]').fill('Pentadbir E2E');
    await registration.locator('input[wire\\:model="email"]').fill(email);
    await registration.locator('input[wire\\:model="phone_wa"]').fill(phone);
    await registration.locator('input[type="checkbox"]').nth(0).check();
    await registration.locator('input[type="checkbox"]').nth(1).check();
    await registration.getByRole('button', { name: 'Hantar Permohonan' }).click();
    await expect(registration.getByText('Permohonan diterima!')).toBeVisible({ timeout: 60_000 });
    await publicContext.close();

    const adminContext = await browser.newContext({ baseURL });
    const admin = await adminContext.newPage();
    await admin.goto('/admin/login');
    await admin.locator('input[type="email"]').fill('superadmin@diwan.test');
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
    const user = await userContext.newPage();
    await user.goto(`/masuk/${token}`);
    await user.waitForURL(new RegExp(`/app/${slug}/?$`), { timeout: 60_000 });
    await expect(user.locator('main')).toBeVisible();
    await expect(user.getByText(name).first()).toBeVisible();
    await user.goto(`/app/${slug}/classification-nodes`);
    await expect(user.locator('main')).toBeVisible();
    await expect(user.getByText('Klasifikasi Fail').first()).toBeVisible();
    await userContext.close();
});
