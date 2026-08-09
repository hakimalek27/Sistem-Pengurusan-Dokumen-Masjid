// D11 #9 (PELAN-PEMBAIKAN.md §9.1/§9.1a) — matriks PRODUKSI 20 BrowserContext, READ-ONLY MUTLAK.
// HANYA dijalankan melalui wrapper scripts/audit/run-production-guidance-readonly.ps1 —
// project `production-readonly` dalam playwright.config.js WUJUD hanya apabila E2E_PRODUCTION
// diset (wrapper menetapkannya), jadi CI tidak pernah mengutip mahupun menjalankan fail ini.
//
// Menutup lapan jurang §9.1: (1) satu tour per role×viewport; (2) carian 3 pertanyaan;
// (3) artifak inventori berstruktur (E2E_PROD_REPORT); (4) TIADA mutasi — ensureInboxFixture
// dan semua laluan tulis TIDAK wujud dalam fail ini; (5) dijalankan via wrapper + run_uuid;
// (6) page-by-page desktop DAN mobile daripada manifest role_routes (bukan sidebar);
// (7) set role diassert TEPAT lapan; (8) TEPAT 20 konteks diassert.
//
// ⚠️ STRUKTUR: 20 `test()` BERASINGAN (satu per identiti×viewport), bukan satu monolit.
// Sebabnya DIUKUR pada latihan tempatan 9 Ogos 2026 (LATIHAN-9.1-TEMPATAN.md): satu
// `POST /livewire/update` menggantung, dan kerana keseluruhan matriks ialah SATU test yang
// menulis artifaknya hanya di hujung, larian itu menghasilkan **sifar** bukti selepas ~41
// muatan halaman. Pada produksi kesannya lebih teruk: ia membazirkan satu-satunya tetingkap
// kredensial pemilik dan tidak meninggalkan apa-apa untuk dianalisis.
// Kini setiap konteks menulis inventorinya ke cakera SEBAIK ia tamat, jadi gantung memberi
// "19/20 selesai, `mobile · bendahari` tergantung" — hasil yang berguna. Penulisan ke cakera
// (bukan memori) sengaja: apabila satu ujian tamat masa, Playwright boleh memulakan semula
// worker, dan keadaan dalam-memori akan hilang bersamanya.
//
// Env WAJIB (disemak — gagal jelas, bukan skip senyap): E2E_PRODUCTION=1, E2E_PROD_TENANT
// (smoke-<uuid>), E2E_PROD_ROLE_ACCOUNTS (JSON 8 akaun fixture), E2E_PROD_SUPERADMIN_EMAIL/
// _PASSWORD (dibekal pemilik — TIADA lalai demo; lalai diam guidance.spec dilarang di sini),
// E2E_PROD_REPORT (laluan JSON output). Jarak log masuk: 15s (had produksi 5/min).

import { existsSync, readFileSync, writeFileSync, mkdirSync, renameSync } from 'node:fs';
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
const REPORT_PATH = process.env.E2E_PROD_REPORT;
const VIEWPORTS = [
    { name: 'desktop', width: 1440, height: 1000 },
    { name: 'mobile', width: 390, height: 844 },
];

const catalogGuideIds = manifest.catalogue.map((g) => g.guide_id);
const tourForRole = (role) => (role === 'superadmin'
    ? { guide: 'admin.dashboard', route: '/admin' }
    : { guide: 'tenant.dashboard', route: `/app/${tenantSlug}` });

function routesFor(identity) {
    return rr.entries
        .filter((e) => e.identity === identity && e.expected_access === 'allow' && e.category === 'read-only')
        .map((e) => ({ url: e.url.replaceAll('/app/mam', `/app/${tenantSlug}`), template: e.route_template }));
}

// ── Keadaan berterusan pada CAKERA ──────────────────────────────────────────────────────────
// Dikunci pada `run_tenant`: slug ialah `smoke-<uuid>` unik per larian, jadi fail daripada
// larian TERDAHULU dibuang secara automatik, sementara worker yang dimulakan semula dalam
// larian yang SAMA menyambung inventori yang sedia ada. Tiada pemadaman membuta.
let rosakDikesan = null;

function bacaKeadaan(baseURL) {
    if (existsSync(REPORT_PATH)) {
        try {
            const sedia = JSON.parse(readFileSync(REPORT_PATH, 'utf8'));
            if (sedia.run_tenant === tenantSlug) return sedia;
        } catch (e) {
            // ⚠️ JANGAN mula semula secara SENYAP. Versi pertama berbuat demikian dan ia
            // memusnahkan bukti sebenar: laporan berundur daripada 2/20 kepada 0/20 kerana satu
            // penulisan terpotong menyebabkan setiap invokasi berikutnya membuang inventori
            // terkumpul. Penulisan kini ATOMIK (tulis .tmp → rename), jadi keadaan ini
            // sepatutnya mustahil — dan jika ia tetap berlaku, ia MESTI kelihatan.
            rosakDikesan = String(e.message).slice(0, 120);
        }
    }
    return {
        schema_version: 2,
        run_tenant: tenantSlug,
        base_url: baseURL ?? null,
        expected_page_counts: rr.expected_page_counts,
        last_login_at: 0,
        inventory: [],
    };
}

// Tulisan ATOMIK. `jejak()` menulis sebelum SETIAP navigasi (~30 kali per konteks), jadi
// tetingkap "fail separuh ditulis" dilalui berpuluh kali setiap konteks. Larian yang ditamatkan
// dalam tetingkap itu meninggalkan JSON terpotong — DIPERHATIKAN: laporan berundur 2/20 → 0/20.
// Tulis ke `.tmp` kemudian `rename` bermakna pembaca hanya nampak fail LENGKAP: sama ada yang
// lama atau yang baharu, tidak pernah separuh.
function tulisKeadaan(keadaan) {
    mkdirSync(dirname(REPORT_PATH), { recursive: true });
    if (rosakDikesan) keadaan.amaran_rosak = rosakDikesan;
    const tmp = `${REPORT_PATH}.tmp`;
    writeFileSync(tmp, JSON.stringify(keadaan, null, 2) + '\n');
    renameSync(tmp, REPORT_PATH);
}

// Jejak DALAM konteks: `cuba` ditulis SEBELUM setiap navigasi, jadi apabila larian terkunci
// artifak menamakan **laluan** yang menyekat dan bukan hanya identitinya.
// Sebabnya diukur: pada latihan tempatan satu konteks terkunci 40 minit tanpa had per-ujiannya
// menembak, dan inventori hanya boleh melaporkan "desktop|admin_masjid" — cukup untuk
// mengulanginya, tidak cukup untuk mendiagnosnya.
async function jejak(baseURL, viewport, identity, url, kerja) {
    rekod(baseURL, viewport, identity, { cuba: url });
    return kerja();
}

// Satu baca-ubah-tulis per identiti. `kunci` = viewport|identity, jadi percubaan semula
// menggantikan entri lama dan bukan menggandakannya.
function rekod(baseURL, viewport, identity, ubah) {
    const keadaan = bacaKeadaan(baseURL);
    const kunci = `${viewport}|${identity}`;
    const idx = keadaan.inventory.findIndex((e) => `${e.viewport}|${e.identity}` === kunci);
    const asal = idx >= 0 ? keadaan.inventory[idx] : { viewport, identity };
    const baharu = { ...asal, ...ubah };
    if (idx >= 0) keadaan.inventory[idx] = baharu; else keadaan.inventory.push(baharu);
    tulisKeadaan(keadaan);
    return keadaan;
}

// Jarak log masuk dikekalkan pada CAKERA juga: jika worker dimulakan semula selepas tamat
// masa, cap masa dalam-memori hilang dan larian berikutnya akan melanggar had kadar 5/min.
async function waitForLoginSlot(page, baseURL) {
    const { last_login_at: lalu = 0 } = bacaKeadaan(baseURL);
    const remaining = loginDelayMs - (Date.now() - lalu);
    if (remaining > 0) await page.waitForTimeout(remaining);
}

function catatLogMasuk(baseURL) {
    const keadaan = bacaKeadaan(baseURL);
    keadaan.last_login_at = Date.now();
    tulisKeadaan(keadaan);
}

async function login(page, baseURL, email, password, loginPath, homePattern) {
    await waitForLoginSlot(page, baseURL);
    await page.goto(loginPath);
    await page.locator('input[id="form.login"]').fill(email);
    await page.locator('input[type="password"]').fill(password);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(homePattern, { timeout: 90_000 });
    catatLogMasuk(baseURL);
}

// F8 (Codex P2 #14) — semakan per-halaman yang SEBELUM INI hanya dipakai pada role tenant.
// Blok `public` hanya mengassert status 200, dan `superadmin` melangkau overflow — jadi dua
// daripada sepuluh identiti tidak pernah diperiksa untuk landmark atau overflow mendatar.
async function assertHalamanSihat(page, label) {
    await expect(page.locator('main')).toBeVisible();
    const overflow = await page.evaluate(() => Math.max(
        document.documentElement.scrollWidth, document.body?.scrollWidth ?? 0,
    ) - window.innerWidth);
    expect(overflow, `${label} overflow mendatar`).toBeLessThanOrEqual(2);
}

// Tiga pertanyaan §9.1 jurang (2). ⚠️ Mengassert `.diwan-help-search-status` KELIHATAN sahaja
// tidak bermakna — elemen itu sentiasa ada. Yang diassert di sini ialah TEKSNYA berubah dan
// membezakan "ada hasil" daripada "0 hasil".
async function assertCarianBantuan(page, label) {
    const status = page.locator('.diwan-help-search-status');
    const hasil = [];
    for (const query of ['Peti Masuk', 'klasfikasi surat', 'zzqqxx-tiada-langsung']) {
        await page.locator('#help-query').fill(query);
        await page.getByRole('button', { name: 'Cari', exact: true }).click();
        await expect(status).toBeVisible();
        await expect(status).not.toBeEmpty();
        // ⚠️ MENUNGGU pertanyaan itu MUNCUL dalam status. Tanpa ini keputusan tersasar SATU:
        // DIUKUR pada latihan tempatan, `textContent` dibaca sebelum Livewire menggantikan
        // status, jadi pertanyaan #1 merakam teks AWAL ("3 panduan disyorkan") dan pertanyaan
        // #3 merakam hasil pertanyaan #2. Kedua-dua assertion di bawah kemudian LULUS atas
        // sebab yang salah — gate hijau yang tidak menguji apa-apa yang dinamakannya.
        // Status memaparkan pertanyaan itu sendiri (`… untuk "<query>"`), jadi ia penanda
        // yang tepat untuk "kitaran INI sudah selesai".
        await expect(status, `${label}: status tidak pernah mencerminkan pertanyaan "${query}"`)
            .toContainText(query, { timeout: 30_000 });
        hasil.push(((await status.textContent()) ?? '').trim());
    }
    // Pertanyaan karut MESTI memberi keadaan "0 hasil"; pertanyaan tepat MESTI tidak.
    // ⚠️ Pertanyaan #2 (salah ejaan) DIREKAM tetapi TIDAK diassert, dan sebabnya dinyatakan:
    // toleransi typo datang daripada Meilisearch sahaja. Fallback PHP memadan substring, jadi
    // "klasfikasi surat" memberi 0 hasil yang SAH pada laluan fallback (diukur — PENEMUAN-CARIAN
    // §3). Mengassertnya akan menjadikan latihan tempatan mustahil dijalankan, dan latihan itu
    // satu-satunya cara membuktikan runner sebelum tetingkap kredensial produksi dibuka.
    // Nilainya ada dalam artifak `carian[1]` untuk dibaca pada laporan larian produksi.
    expect(hasil[2], `${label}: query karut sepatutnya 0 hasil — dapat "${hasil[2]}"`).toContain('0 hasil');
    expect(hasil[0], `${label}: query tepat memberi 0 hasil — carian mungkin rosak`).not.toContain('0 hasil');
    return hasil;
}

function monitorBrowserErrors(page) {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error') errors.push(message.text());
    });

    return errors;
}

// ── Kontrak (7): set role fixture diassert TEPAT, sebelum apa-apa pelayar dibuka ────────────
test('kontrak: akaun fixture ialah TEPAT lapan role yang dijangka', async () => {
    const roles = roleAccounts.map((a) => a.role).sort();
    expect(roles).toEqual([...EXPECTED_ROLES].sort());
    expect(new Set(roles).size).toBe(8);
});

// ── 20 konteks: satu `test()` setiap satu ───────────────────────────────────────────────────
for (const viewport of VIEWPORTS) {
    const size = { width: viewport.width, height: viewport.height };

    // public (tanpa akaun — identiti ke-10).
    test(`${viewport.name} · public`, async ({ browser, baseURL }) => {
        test.setTimeout(600_000);
        rekod(baseURL, viewport.name, 'public', { status: 'mula' });
        const context = await browser.newContext({ baseURL, viewport: size });
        try {
            const page = await context.newPage();
            const errors = monitorBrowserErrors(page);
            const visited = [];
            for (const item of routesFor('public').filter((r) => r.template.startsWith('/'))) {
                const response = await jejak(baseURL, viewport.name, 'public', item.url,
                    () => page.goto(item.url));
                expect(response?.status(), `public ${viewport.name}: ${item.url}`).toBe(200);
                await assertHalamanSihat(page, `public ${viewport.name} ${item.url}`);
                visited.push({ url: item.url, status: response?.status() });
            }

            // (1)+(2) untuk AWAM juga — sebelum ini hanya role tenant yang diuji.
            await jejak(baseURL, viewport.name, 'public', '/bantuan?panduan=public.help',
                () => page.goto('/bantuan?panduan=public.help&langkah=0'));
            await expect(page.locator('.driver-popover')).toBeVisible();
            await page.locator('.driver-popover-close-btn').click();
            const carian = await assertCarianBantuan(page, `public ${viewport.name}`);

            expect([...new Set(errors)]).toEqual([]);
            rekod(baseURL, viewport.name, 'public', { status: 'selesai', pages: visited, carian });
        } finally {
            await context.close();
        }
    });

    // superadmin (kredensial DIBEKAL LUARAN — tiada lalai).
    test(`${viewport.name} · superadmin`, async ({ browser, baseURL }) => {
        test.setTimeout(600_000);
        rekod(baseURL, viewport.name, 'superadmin', { status: 'mula' });
        const context = await browser.newContext({ baseURL, viewport: size });
        try {
            const page = await context.newPage();
            const errors = monitorBrowserErrors(page);
            await login(page, baseURL, process.env.E2E_PROD_SUPERADMIN_EMAIL,
                process.env.E2E_PROD_SUPERADMIN_PASSWORD, '/admin/login', /\/admin\/?$/);
            const visited = [];
            // ⚠️ Sebelum ini hanya panel `admin` dilawati, jadi 25 halaman panel `app` yang
            // superadmin BOLEH capai tidak pernah diperiksa (Codex P2 #14).
            const laluanSuperadmin = rr.entries.filter((e) => e.identity === 'superadmin'
                && e.expected_access === 'allow'
                && (e.panel === 'admin' || e.panel === 'app'));
            for (const item of laluanSuperadmin) {
                const url = item.url.replaceAll('/app/mam', `/app/${tenantSlug}`);
                const response = await jejak(baseURL, viewport.name, 'superadmin', url,
                    () => page.goto(url));
                expect(response?.status(), `superadmin ${viewport.name}: ${url}`).toBe(200);
                await assertHalamanSihat(page, `superadmin ${viewport.name} ${url}`);
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
            rekod(baseURL, viewport.name, 'superadmin', { status: 'selesai', pages: visited });
        } finally {
            await context.close();
        }
    });

    for (const account of roleAccounts) {
        test(`${viewport.name} · ${account.role}`, async ({ browser, baseURL }) => {
            test.setTimeout(600_000);
            rekod(baseURL, viewport.name, account.role, { status: 'mula' });
            const context = await browser.newContext({ baseURL, viewport: size });
            try {
                const page = await context.newPage();
                const errors = monitorBrowserErrors(page);
                await login(page, baseURL, account.email, account.password, '/app/login',
                    (url) => url.pathname.replace(/\/$/, '') === `/app/${tenantSlug}`);

                // (6) Page-by-page daripada MANIFEST role_routes — desktop DAN mobile.
                const visited = [];
                for (const item of routesFor(account.role)) {
                    const response = await jejak(baseURL, viewport.name, account.role, item.url,
                        () => page.goto(item.url));
                    expect(response?.status(), `${account.role} ${viewport.name}: ${item.url}`).toBe(200);
                    await assertHalamanSihat(page, `${account.role} ${viewport.name} ${item.url}`);
                    visited.push({ url: item.url, status: response?.status() });
                }

                // (1) satu tour per role×viewport.
                const tour = tourForRole(account.role);
                await jejak(baseURL, viewport.name, account.role, `${tour.route}?panduan=${tour.guide}`,
                    () => page.goto(`${tour.route}?panduan=${tour.guide}&langkah=0`));
                await expect(page.locator('.driver-popover')).toBeVisible();
                await page.locator('.driver-popover-close-btn').click();

                // (2) carian bantuan 3 pertanyaan (tepat / salah ejaan / istilah karut).
                await jejak(baseURL, viewport.name, account.role, `/app/${tenantSlug}/bantuan`,
                    () => page.goto(`/app/${tenantSlug}/bantuan`));
                const carian = await assertCarianBantuan(page, `${account.role} ${viewport.name}`);

                // Probe silang-tenant 404 (S1) — tenant sebenar TIDAK dilog masuk, hanya URL.
                const cross = await jejak(baseURL, viewport.name, account.role, '/app/mamad/records',
                    () => page.goto('/app/mamad/records'));
                expect(cross?.status(), `${account.role} silang-tenant`).toBe(404);

                expect([...new Set(errors)], `${account.role} ${viewport.name}`).toEqual([]);
                rekod(baseURL, viewport.name, account.role, {
                    status: 'selesai', pages: visited, carian, crossTenant: cross?.status(),
                });
            } finally {
                await context.close();
            }
        });
    }
}

// ── Kontrak (8): TEPAT 20 konteks SELESAI, dan set identiti×viewport tepat ──────────────────
// Diassert daripada CAKERA, bukan kaunter dalam-memori: kaunter tidak akan selamat daripada
// worker yang dimulakan semula, dan lebih penting — ia tidak boleh MENAMAKAN konteks yang
// hilang. Ujian ini diisytihar terakhir, jadi ia berjalan terakhir (workers: 1, urutan fail).
test('kontrak: TEPAT 20 konteks selesai — dan yang hilang DINAMAKAN', async ({ baseURL }) => {
    const keadaan = bacaKeadaan(baseURL);
    const dijangka = VIEWPORTS.flatMap((v) => ['public', 'superadmin', ...roleAccounts.map((a) => a.role)]
        .map((identity) => `${v.name}|${identity}`));
    expect(dijangka).toHaveLength(20);

    const selesai = keadaan.inventory.filter((e) => e.status === 'selesai')
        .map((e) => `${e.viewport}|${e.identity}`);
    const hilang = dijangka.filter((k) => !selesai.includes(k));

    keadaan.contexts = selesai.length;
    keadaan.missing_contexts = hilang;
    tulisKeadaan(keadaan);

    // Kehilangan data tidak boleh lulus sebagai kejayaan: jika inventori pernah dibaca dalam
    // keadaan rosak, kiraan 20 itu sendiri tidak boleh dipercayai.
    expect(keadaan.amaran_rosak ?? null,
        `inventori pernah rosak semasa larian (${keadaan.amaran_rosak}) — kiraan tidak boleh dipercayai`).toBeNull();

    expect(hilang, `konteks TIDAK selesai: ${hilang.join(', ') || '(tiada)'}`).toEqual([]);
    expect(selesai.sort()).toEqual([...dijangka].sort());
    expect(selesai).toHaveLength(20);
});
