import { chromium } from '@playwright/test';

const baseURL = process.env.MANUAL_BASE_URL ?? 'http://127.0.0.1:8094';
const password = process.env.MANUAL_DEMO_PASSWORD;

if (!password) throw new Error('MANUAL_DEMO_PASSWORD is required.');

const allowed = [
    ['admin_masjid', 'admin_masjid@demo.test'],
    ['pengerusi', 'pengerusi@demo.test'],
    ['setiausaha', 'setiausaha@demo.test'],
    ['bendahari', 'bendahari@demo.test'],
];

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const results = [];
let lastLoginAt = 0;

async function login(page, email) {
    const waitMs = Math.max(0, 15_000 - (Date.now() - lastLoginAt));
    if (waitMs) await page.waitForTimeout(waitMs);
    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill(email);
    await page.locator('input[type="password"]').fill(password);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(/\/app\/mam\/?$/);
    lastLoginAt = Date.now();
}

try {
    for (const [role, email] of allowed) {
        const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
        const page = await context.newPage();
        const browserErrors = [];
        page.on('pageerror', (error) => browserErrors.push(error.message));
        page.on('console', (message) => { if (message.type() === 'error') browserErrors.push(message.text()); });

        await login(page, email);
        const response = await page.goto('/app/mam/log-aktiviti');
        if (response?.status() !== 200) throw new Error(`${role}: activity page HTTP ${response?.status()}`);
        await page.getByRole('heading', { name: 'Log Aktiviti Masjid' }).waitFor();
        await page.getByRole('button', { name: 'Butiran' }).first().click();
        const modal = page.locator('.fi-modal-window:visible');
        await modal.getByText('Butiran Log Aktiviti').waitFor();
        await modal.getByText('Tarikh dan masa').waitFor();
        await modal.getByRole('button', { name: 'Tutup', exact: true }).last().click();

        const crossTenant = await page.goto('/app/man/log-aktiviti');
        if (crossTenant?.status() !== 404) throw new Error(`${role}: cross-tenant HTTP ${crossTenant?.status()}`);
        const meaningfulErrors = [...new Set(browserErrors)]
            .filter((message) => !message.includes('Failed to load resource: the server responded with a status of 404'));
        if (meaningfulErrors.length) throw new Error(`${role}: ${meaningfulErrors.join(' | ')}`);

        results.push({ role, page: 200, modal: true, crossTenant: 404 });
        await context.close();
    }

    const searchContext = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    const searchPage = await searchContext.newPage();
    await login(searchPage, 'admin_masjid@demo.test');
    await searchPage.goto('/app/mam/log-aktiviti');
    const search = searchPage.locator('main input[type="search"]').first();
    await search.fill('60123456789');
    await searchPage.getByText('60123456789 memuat naik dokumen').waitFor();
    await search.fill('test123@example.test');
    await searchPage.getByText('test123@example.test memuat naik dokumen').waitFor();
    results.push({ searches: ['60123456789', 'test123@example.test'] });
    await searchContext.close();

    const deniedContext = await browser.newContext({ baseURL });
    const deniedPage = await deniedContext.newPage();
    await login(deniedPage, 'ajk@demo.test');
    const denied = await deniedPage.goto('/app/mam/log-aktiviti');
    if (denied?.status() !== 403) throw new Error(`ajk: expected 403, got ${denied?.status()}`);
    results.push({ role: 'ajk', page: 403 });
    await deniedContext.close();
} finally {
    await browser.close();
}

console.log(JSON.stringify(results, null, 2));
