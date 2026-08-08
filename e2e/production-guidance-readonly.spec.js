// D11 #9 (PELAN-PEMBAIKAN.md §9.1/§9.1a) — matriks PRODUKSI 20 BrowserContext, READ-ONLY MUTLAK.
// HANYA dijalankan melalui wrapper scripts/audit/run-production-guidance-readonly.ps1 —
// SENGAJA tiada dalam mana-mana project playwright.config.js (allowlist PlanManifestTest).
//
// Menutup lapan jurang §9.1: (1) satu tour per role×viewport; (2) carian 3 pertanyaan;
// (3) artifak inventori berstruktur (E2E_PROD_REPORT); (4) TIADA mutasi — ensureInboxFixture
// dan semua laluan tulis TIDAK wujud dalam fail ini; (5) dijalankan via wrapper + run_uuid;
// (6) page-by-page desktop DAN mobile daripada manifest role_routes (bukan sidebar);
// (7) set role diassert TEPAT lapan; (8) TEPAT 20 konteks diassert.
//
// Env WAJIB (disemak — gagal jelas, bukan skip senyap): E2E_PRODUCTION=1, E2E_PROD_TENANT
// (smoke-<uuid>), E2E_PROD_ROLE_ACCOUNTS (JSON 8 akaun fixture), E2E_PROD_SUPERADMIN_EMAIL/
// _PASSWORD (dibekal pemilik — TIADA lalai demo; lalai diam guidance.spec dilarang di sini),
// E2E_PROD_REPORT (laluan JSON output). Jarak log masuk: 15s (had produksi 5/min).

import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';
import { expect, test } from '@playwright/test';

const required = ['E2E_PRODUCTION', 'E2E_PROD_TENANT', 'E2E_PROD_ROLE_ACCOUNTS',
    'E2E_PROD_SUPERADMIN_EMAIL', 'E2E_PROD_SUPERADMIN_PASSWORD', 'E2E_PROD_REPORT'];
for (const name of required) {
    if (!process.env[name] || process.env[name].trim() === '') {
        // Nilai TIDAK dicetak — nama pemboleh ubah sahaja (kredensial produksi ≠ log).
        throw new Error(`Spec produksi memerlukan env ${name} (dibekal wrapper §9.1a) — tiada lalai diam.`);
    }
}

const manifest = JSON.parse(readFileSync('Audit Review Round Robin/bukti/plan-baseline/manifest.json', 'utf8'));
const rr = manifest.role_routes;
const tenantSlug = process.env.E2E_PROD_TENANT;
if (!/^smoke-[0-9a-f-]{36}$/i.test(tenantSlug)) {
    throw new Error('E2E_PROD_TENANT mesti `smoke-<run_uuid>` — slug `smoke` ialah tenant gate deploy dan DILARANG.');
}
const roleAccounts = JSON.parse(process.env.E2E_PROD_ROLE_ACCOUNTS);
const EXPECTED_ROLES = ['admin_masjid', 'pengerusi', 'setiausaha', 'bendahari', 'nazir', 'ketua_imam', 'ajk', 'audit'];
const loginDelayMs = Number(process.env.E2E_PROD_ROLE_LOGIN_DELAY_MS ?? 15_000);
let lastLoginAt = 0;

const catalogGuideIds = manifest.catalogue.map((g) => g.guide_id);
const tourForRole = (role) => (role === 'superadmin'
    ? { guide: 'admin.dashboard', route: '/admin' }
    : { guide: 'tenant.dashboard', route: `/app/${tenantSlug}` });

function routesFor(identity) {
    return rr.entries
        .filter((e) => e.identity === identity && e.expected_access === 'allow' && e.category === 'read-only')
        .map((e) => ({ url: e.url.replaceAll('/app/mam', `/app/${tenantSlug}`), template: e.route_template }));
}

async function waitForLoginSlot(page) {
    const remaining = loginDelayMs - (Date.now() - lastLoginAt);
    if (remaining > 0) await page.waitForTimeout(remaining);
}

async function login(page, email, password, loginPath, homePattern) {
    await waitForLoginSlot(page);
    await page.goto(loginPath);
    await page.locator('input[id="form.login"]').fill(email);
    await page.locator('input[type="password"]').fill(password);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(homePattern, { timeout: 90_000 });
    lastLoginAt = Date.now();
}

// F8 (Codex P2 #14) — semakan per-halaman yang SEBELUM INI hanya dipakai pada role tenant.
// Blok `public` hanya mengassert status 200, dan `superadmin` melangkau overflow — jadi dua
// daripada sepuluh identiti tidak pernah diperiksa untuk landmark atau overflow mendatar.
async function assertHalamanSihat(page, expect, label) {
    await expect(page.locator('main')).toBeVisible();
    const overflow = await page.evaluate(() => Math.max(
        document.documentElement.scrollWidth, document.body?.scrollWidth ?? 0,
    ) - window.innerWidth);
    expect(overflow, `${label} overflow mendatar`).toBeLessThanOrEqual(2);
}

// Tiga pertanyaan §9.1 jurang (2). ⚠️ Mengassert `.diwan-help-search-status` KELIHATAN sahaja
// tidak bermakna — elemen itu sentiasa ada. Yang diassert di sini ialah TEKSNYA berubah dan
// membezakan "ada hasil" daripada "0 hasil".
async function assertCarianBantuan(page, expect, label) {
    const status = page.locator('.diwan-help-search-status');
    const hasil = [];
    for (const query of ['Peti Masuk', 'klasfikasi surat', 'zzqqxx-tiada-langsung']) {
        await page.locator('#help-query').fill(query);
        await page.getByRole('button', { name: 'Cari', exact: true }).click();
        await expect(status).toBeVisible();
        await expect(status).not.toBeEmpty();
        hasil.push(((await status.textContent()) ?? '').trim());
    }
    // Pertanyaan karut MESTI memberi keadaan "0 hasil"; pertanyaan tepat MESTI tidak.
    expect(hasil[2], `${label}: query karut sepatutnya 0 hasil — dapat "${hasil[2]}"`).toContain('0 hasil');
    expect(hasil[0], `${label}: query tepat memberi 0 hasil — carian mungkin rosak`).not.toContain('0 hasil');
}

function monitorBrowserErrors(page) {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error') errors.push(message.text());
    });

    return errors;
}

test('matriks produksi read-only: 10 identiti × 2 viewport = 20 konteks', async ({ browser, baseURL }) => {
    test.setTimeout(3_600_000);
    // (7) Set role diassert TEPAT — bukan sekadar panjang array.
    const roles = roleAccounts.map((a) => a.role).sort();
    expect(roles).toEqual([...EXPECTED_ROLES].sort());
    expect(new Set(roles).size).toBe(8);

    const contexts = new Set();
    const inventory = [];

    for (const viewport of [
        { name: 'desktop', width: 1440, height: 1000 },
        { name: 'mobile', width: 390, height: 844 },
    ]) {
        const size = { width: viewport.width, height: viewport.height };

        // public (tanpa akaun — identiti ke-10).
        {
            const context = await browser.newContext({ baseURL, viewport: size });
            contexts.add(context);
            const page = await context.newPage();
            const errors = monitorBrowserErrors(page);
            const visited = [];
            for (const item of routesFor('public').filter((r) => r.template.startsWith('/'))) {
                const response = await page.goto(item.url);
                expect(response?.status(), `public ${viewport.name}: ${item.url}`).toBe(200);
                await assertHalamanSihat(page, expect, `public ${viewport.name} ${item.url}`);
                visited.push({ url: item.url, status: response?.status() });
            }

            // (1)+(2) untuk AWAM juga — sebelum ini hanya role tenant yang diuji.
            await page.goto('/bantuan?panduan=public.help&langkah=0');
            await expect(page.locator('.driver-popover')).toBeVisible();
            await page.locator('.driver-popover-close-btn').click();
            await assertCarianBantuan(page, expect, `public ${viewport.name}`);

            expect([...new Set(errors)]).toEqual([]);
            inventory.push({ viewport: viewport.name, identity: 'public', pages: visited });
            await context.close();
        }

        // superadmin (kredensial DIBEKAL LUARAN — tiada lalai).
        {
            const context = await browser.newContext({ baseURL, viewport: size });
            contexts.add(context);
            const page = await context.newPage();
            const errors = monitorBrowserErrors(page);
            await login(page, process.env.E2E_PROD_SUPERADMIN_EMAIL, process.env.E2E_PROD_SUPERADMIN_PASSWORD,
                '/admin/login', /\/admin\/?$/);
            const visited = [];
            // ⚠️ Sebelum ini hanya panel `admin` dilawati, jadi 25 halaman panel `app` yang
            // superadmin BOLEH capai tidak pernah diperiksa (Codex P2 #14).
            const laluanSuperadmin = rr.entries.filter((e) => e.identity === 'superadmin'
                && e.expected_access === 'allow'
                && (e.panel === 'admin' || e.panel === 'app'));
            for (const item of laluanSuperadmin) {
                const url = item.url.replaceAll('/app/mam', `/app/${tenantSlug}`);
                const response = await page.goto(url);
                expect(response?.status(), `superadmin ${viewport.name}: ${url}`).toBe(200);
                await assertHalamanSihat(page, expect, `superadmin ${viewport.name} ${url}`);
                visited.push({ url, status: response?.status() });
            }
            // (1) satu tour read-only (telemetri diisytihar dalam laporan larian).
            const tour = tourForRole('superadmin');
            if (catalogGuideIds.includes(tour.guide)) {
                await page.goto(`${tour.route}?panduan=${tour.guide}&langkah=0`);
                await expect(page.locator('.driver-popover')).toBeVisible();
                await page.locator('.driver-popover-close-btn').click();
            }
            expect([...new Set(errors)]).toEqual([]);
            inventory.push({ viewport: viewport.name, identity: 'superadmin', pages: visited });
            await context.close();
        }

        for (const account of roleAccounts) {
            await test.step(`${viewport.name}: ${account.role}`, async () => {
                const context = await browser.newContext({ baseURL, viewport: size });
                contexts.add(context);
                const page = await context.newPage();
                const errors = monitorBrowserErrors(page);
                await login(page, account.email, account.password, '/app/login',
                    (url) => url.pathname.replace(/\/$/, '') === `/app/${tenantSlug}`);

                // (6) Page-by-page daripada MANIFEST role_routes — desktop DAN mobile.
                const visited = [];
                for (const item of routesFor(account.role)) {
                    const response = await page.goto(item.url);
                    expect(response?.status(), `${account.role} ${viewport.name}: ${item.url}`).toBe(200);
                    await expect(page.locator('main')).toBeVisible();
                    const overflow = await page.evaluate(() => Math.max(
                        document.documentElement.scrollWidth, document.body?.scrollWidth ?? 0,
                    ) - window.innerWidth);
                    expect(overflow, `${account.role} ${viewport.name} overflow: ${item.url}`).toBeLessThanOrEqual(2);
                    visited.push({ url: item.url, status: response?.status() });
                }

                // (1) satu tour per role×viewport.
                const tour = tourForRole(account.role);
                await page.goto(`${tour.route}?panduan=${tour.guide}&langkah=0`);
                await expect(page.locator('.driver-popover')).toBeVisible();
                await page.locator('.driver-popover-close-btn').click();

                // (2) carian bantuan 3 pertanyaan (tepat / salah ejaan / istilah DDMS).
                await page.goto(`/app/${tenantSlug}/bantuan`);
                await assertCarianBantuan(page, expect, `${account.role} ${viewport.name}`);

                // Probe silang-tenant 404 (S1) — tenant sebenar TIDAK dilog masuk, hanya URL.
                const cross = await page.goto('/app/mamad/records');
                expect(cross?.status(), `${account.role} silang-tenant`).toBe(404);

                expect([...new Set(errors)], `${account.role} ${viewport.name}`).toEqual([]);
                inventory.push({ viewport: viewport.name, identity: account.role, pages: visited, crossTenant: cross?.status() });
                await context.close();
            });
        }
    }

    // (8) TEPAT 20 konteks — diwarisi daripada guidance.spec.js:214, bukan toBe(accounts.length).
    expect(contexts.size).toBe(20);

    // (3) Artifak inventori berstruktur — BUKAN console.log.
    const report = {
        schema_version: 1,
        run_tenant: tenantSlug,
        base_url: baseURL,
        contexts: contexts.size,
        expected_page_counts: rr.expected_page_counts,
        inventory,
    };
    mkdirSync(dirname(process.env.E2E_PROD_REPORT), { recursive: true });
    writeFileSync(process.env.E2E_PROD_REPORT, JSON.stringify(report, null, 2) + '\n');
});
