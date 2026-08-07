import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const guideIds = JSON.parse(readFileSync('resources/help/guides.json', 'utf8')).guides.map((guide) => guide.id);

/**
 * Tutup tour AUTOMATIK seperti pengguna sebenar.
 *
 * ⚠️ `localStorage['diwan-help-seen:*']` (di bawah) TIDAK mematikan tour panel tenant:
 * `help.js` hanya menyemak kunci itu apabila `runtime.dataset.panel === 'public'`. Ia
 * kelihatan berfungsi selama ini kerana guide halaman bersasar `page-content` (iaitu
 * `<main>`), jadi vendor Driver.js memberi seluruh kandungan `pointer-events: auto` dan
 * popover tidak pernah menghalang apa-apa. F6-W5 menjadikan sasaran KECIL, jadi popover kini
 * duduk di atas kandungan — pada viewport 1280×720 ia menutupi butang "Cari" secara FIZIKAL,
 * yang tiada peraturan CSS boleh selesaikan.
 *
 * Ujian ini menguji aliran DOMAIN, bukan tour. Menutup tour dahulu ialah tepat apa yang
 * pengguna buat, jadi ia laluan yang setia — bukan pengecualian.
 */
async function tutupTourJikaAda(page) {
    // ⚠️ Tour auto-mula selepas `setTimeout(..., 450)` dalam help.js, jadi semakan
    // `isVisible()` SERTA-MERTA selepas `goto` sentiasa memberi false dan helper ini menjadi
    // no-op — itu punca percubaan pertama masih gagal. Tunggu sebentar dahulu; ketiadaan
    // popover selepas tempoh itu memang bermakna tiada tour, jadi `catch` selamat.
    const popover = page.locator('.driver-popover');
    await popover.waitFor({ state: 'visible', timeout: 2_500 }).catch(() => {});
    if (!await popover.isVisible().catch(() => false)) return;

    await popover.locator('.driver-popover-close-btn').click().catch(() => {});
    await expect(popover).toBeHidden();
}

async function login(page) {
    // Dikekalkan untuk panel AWAM (satu-satunya tempat help.js menghormatinya).
    await page.context().addInitScript((ids) => {
        for (const id of ids) localStorage.setItem(`diwan-help-seen:${id}`, '1');
    }, guideIds);
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
    await tutupTourJikaAda(page);
    await page.locator('input[wire\\:model="savedSearchName"]').fill(`Carian E2E ${Date.now()}`);
    await page.locator('input[wire\\:model="sender"]').fill('Masjid');
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(page.getByText('Carian disimpan.')).toBeVisible();
    // Tour boleh mula (atau mula semula selepas kitaran Livewire) selepas langkah di atas.
    await tutupTourJikaAda(page);
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    await expect(page.getByText(/hasil ditemui/)).toBeVisible();

    await page.goto('/app/mam/registry-files');
    await tutupTourJikaAda(page);
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
