import { chromium } from '@playwright/test';

const baseURL = process.env.ACTIVITY_LOG_BASE_URL ?? process.env.MANUAL_BASE_URL ?? 'http://127.0.0.1:8094';
const defaultPassword = process.env.MANUAL_DEMO_PASSWORD;
const tenant = process.env.ACTIVITY_LOG_TENANT ?? 'mam';
const crossTenant = process.env.ACTIVITY_LOG_CROSS_TENANT ?? 'man';
const loginDelayMs = Number(process.env.ACTIVITY_LOG_LOGIN_DELAY_MS ?? 15_000);
const configuredAccounts = process.env.ACTIVITY_LOG_ROLE_ACCOUNTS
    ? JSON.parse(process.env.ACTIVITY_LOG_ROLE_ACCOUNTS)
    : null;

if (!configuredAccounts && !defaultPassword) {
    throw new Error('MANUAL_DEMO_PASSWORD atau ACTIVITY_LOG_ROLE_ACCOUNTS diperlukan.');
}

const allowed = configuredAccounts?.allowed ?? [
    { role: 'admin_masjid', email: 'admin_masjid@demo.test', password: defaultPassword },
    { role: 'pengerusi', email: 'pengerusi@demo.test', password: defaultPassword },
    { role: 'setiausaha', email: 'setiausaha@demo.test', password: defaultPassword },
    { role: 'bendahari', email: 'bendahari@demo.test', password: defaultPassword },
];
const deniedAccount = configuredAccounts?.denied ?? {
    role: 'ajk',
    email: 'ajk@demo.test',
    password: defaultPassword,
};
const searches = configuredAccounts?.searches ?? ['60123456789', 'test123@example.test'];

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const results = [];
let lastLoginAt = 0;

async function login(page, account) {
    const waitMs = Math.max(0, loginDelayMs - (Date.now() - lastLoginAt));
    if (waitMs) await page.waitForTimeout(waitMs);
    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill(account.email);
    await page.locator('input[type="password"]').fill(account.password);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(new RegExp(`/app/${tenant}/?$`));
    lastLoginAt = Date.now();
}

try {
    for (const account of allowed) {
        const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
        const page = await context.newPage();
        const browserErrors = [];
        page.on('pageerror', (error) => browserErrors.push(error.message));
        page.on('console', (message) => { if (message.type() === 'error') browserErrors.push(message.text()); });

        await login(page, account);
        const response = await page.goto(`/app/${tenant}/log-aktiviti`);
        if (response?.status() !== 200) throw new Error(`${account.role}: activity page HTTP ${response?.status()}`);
        await page.getByRole('heading', { name: 'Log Aktiviti Masjid' }).waitFor();
        await page.getByRole('button', { name: 'Butiran' }).first().click();
        const modal = page.locator('.fi-modal-window:visible');
        await modal.getByText('Butiran Log Aktiviti').waitFor();
        await modal.getByText('Tarikh dan masa').waitFor();
        await modal.getByRole('button', { name: 'Tutup', exact: true }).last().click();

        const crossTenantResponse = await page.goto(`/app/${crossTenant}/log-aktiviti`);
        if (crossTenantResponse?.status() !== 404) throw new Error(`${account.role}: cross-tenant HTTP ${crossTenantResponse?.status()}`);
        const meaningfulErrors = [...new Set(browserErrors)]
            .filter((message) => !message.includes('Failed to load resource: the server responded with a status of 404'));
        if (meaningfulErrors.length) throw new Error(`${account.role}: ${meaningfulErrors.join(' | ')}`);

        results.push({ role: account.role, page: 200, modal: true, crossTenant: 404 });
        await context.close();
    }

    const searchContext = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    const searchPage = await searchContext.newPage();
    await login(searchPage, allowed[0]);
    await searchPage.goto(`/app/${tenant}/log-aktiviti`);
    const search = searchPage.locator('main input[type="search"]').first();
    for (const term of searches) {
        await search.fill(term);
        await searchPage.getByText(term, { exact: false }).first().waitFor();
    }
    results.push({ searches });
    await searchContext.close();

    const deniedContext = await browser.newContext({ baseURL });
    const deniedPage = await deniedContext.newPage();
    await login(deniedPage, deniedAccount);
    const denied = await deniedPage.goto(`/app/${tenant}/log-aktiviti`);
    if (denied?.status() !== 403) throw new Error(`${deniedAccount.role}: expected 403, got ${denied?.status()}`);
    results.push({ role: deniedAccount.role, page: 403 });
    await deniedContext.close();
} finally {
    await browser.close();
}

console.log(JSON.stringify(results, null, 2));
