// D11 #5 (PELAN-PEMBAIKAN.md §1 F0(iv) — P14-01/P16-01): canary sesi HTTP.
// Log masuk ialah komponen LIVEWIRE (medan `data.login`, submit /livewire/update) — POST
// borang mentah mustahil; canary ini menggunakan laluan sebenar yang sama dengan suite.
// Kegagalan canary hampir pasti KONFIGURASI: SESSION_DRIVER (array = sesi hilang setiap
// permintaan pada `artisan serve`) atau APP_URL/E2E_BASE_URL (port salah) — bukan pepijat UI.
import { expect, test } from '@playwright/test';

const diagnose = 'Semak env proses SERVER: SESSION_DRIVER=file (BUKAN array — ArraySessionHandler '
    + 'menyimpan sesi per-instance, artisan serve share-nothing → log masuk hilang selepas redirect) '
    + `dan APP_URL sepadan E2E_BASE_URL (${process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8092'}).`;

test('@session-canary sesi HTTP kekal selepas log masuk dan reload', async ({ page }) => {
    // Akaun DemoSeeder ({role}@demo.test / password, tenant mam) — wujud selepas migrate:fresh --seed.
    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
    await page.locator('input[type="password"]').fill('password');
    await page.getByRole('button', { name: /Log masuk/i }).click();

    // (1) Log masuk berjaya — sampai ke laluan panel tenant, bukan kekal di borang.
    await page.waitForURL((url) => url.pathname.replace(/\/$/, '') === '/app/mam', { timeout: 60_000 })
        .catch(() => { throw new Error(`Canary (1) gagal: tidak sampai /app/mam selepas log masuk. ${diagnose}`); });

    // (2) Respons akhir bukan borang log masuk — penanda panel berautentikasi hadir.
    await expect(page.locator('[data-help-target="help-launcher"]'), `Canary (2): penanda panel tiada. ${diagnose}`)
        .toBeVisible();
    await expect(page.locator('input[id="form.login"]'), `Canary (2): borang log masuk masih kelihatan. ${diagnose}`)
        .toHaveCount(0);

    // (3) Reload — sesi mesti DISIMPAN merentas permintaan (inilah yang SESSION_DRIVER=array
    //     tidak mampu buat pada artisan serve).
    await page.reload();
    expect(page.url().replace(/\/$/, ''), `Canary (3): reload melontar keluar dari panel. ${diagnose}`)
        .toContain('/app/mam');
    await expect(page.locator('[data-help-target="help-launcher"]'), `Canary (3): penanda panel hilang selepas reload. ${diagnose}`)
        .toBeVisible();

    // (4) Permintaan BAHARU ke route panel kedua — masih berautentikasi, tiada redirect login.
    const second = await page.goto('/app/mam/peti-masuk');
    expect(second?.status(), `Canary (4): /app/mam/peti-masuk status ${second?.status()}. ${diagnose}`).toBe(200);
    expect(page.url(), `Canary (4): diubah hala ke log masuk. ${diagnose}`).not.toContain('/login');
});
