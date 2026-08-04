// BUG-A (laporan pemilik, 5 Ogos 2026) — pengesahan PERINGKAT PELAYAR untuk pendaratan
// selepas log masuk. Ujian Pest membuktikannya pada aras Livewire/HTTP; ujian ini
// membuktikan aliran SEBENAR yang pemilik lalui: borang log masuk panel masjid dalam
// Chromium, kemudian halaman awam mesti mengenal sesi itu.
//
// Punca yang dijaga di sini: pautan "Log masuk dengan kata laluan" pada /log-masuk menuju
// /app/login, dan LoginResponse lalai Filament mendarat pada panel SEMASA — jadi superadmin
// mendarat dalam masjid tenant lalai, bukan /admin.
import { expect, test } from '@playwright/test';

const superadmin = { email: 'superadmin@diwan.test', password: 'password' };
const adminMasjid = { email: 'admin_masjid@demo.test', password: 'password' };

async function logMasukPanelMasjid(page, akaun) {
    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill(akaun.email);
    await page.locator('input[type="password"]').fill(akaun.password);
    await page.getByRole('button', { name: /Log masuk/i }).click();
}

/** Pautan "Teruskan ke Panel" pada laman utama + laluan yang ditujunya. */
async function ctaLamanUtama(page) {
    await page.goto('/');
    const cta = page.getByRole('link', { name: 'Teruskan ke Panel' });
    await expect(cta).toBeVisible();

    return new URL(await cta.getAttribute('href'), page.url()).pathname;
}

test('BUG-A: superadmin log masuk di /app/login mendarat di /admin', async ({ page }) => {
    await logMasukPanelMasjid(page, superadmin);

    // Sebelum pembaikan: mendarat pada /app/{masjid-pertama-platform}.
    await page.waitForURL(/\/admin\/?$/, { timeout: 60_000 });
    await expect(page.locator('.fi-topbar').first()).toBeVisible();

    expect(await ctaLamanUtama(page)).toBe('/admin');
});

test('BUG-A: admin masjid kekal mendarat dalam masjidnya (tiada regresi)', async ({ page }) => {
    await logMasukPanelMasjid(page, adminMasjid);

    // Data demo tidak menetapkan onboarding_done — pendaratan kata laluan mesti KEKAL
    // papan pemuka, bukan wizard persediaan (jurang `withOnboarding` yang disengajakan).
    await page.waitForURL(/\/app\/mam\/?$/, { timeout: 60_000 });

    expect(await ctaLamanUtama(page)).toBe('/app/mam');
});

test('BUG-A: tetamu tidak nampak tawaran panel', async ({ page }) => {
    await page.goto('/');

    await expect(page.getByRole('link', { name: 'Teruskan ke Panel' })).toHaveCount(0);
    await expect(page.getByRole('link', { name: 'Log Masuk', exact: true }).first()).toBeVisible();
});
