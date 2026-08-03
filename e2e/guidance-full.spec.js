// D11 #6 (PELAN-PEMBAIKAN.md §1 F0(iv)(a)/(b) + §7.3) — gate liputan PENUH G1–G5, di-shard.
// Sumber senarai kerja = manifest catalogue F0(ii) ditapis GUIDANCE_SHARD (BUKAN pembahagian
// automatik Playwright). Kunci langkah = `<guide_id>#<index1>` (step.id tidak unik global).
//
// Liputan per langkah pada BASELINE (pra-F2/F6 — semantik CTA lama masih ada, RR-01-07):
//   G1  status per-langkah datang dari manifest (specific/generic-justified/risk-accepted…)
//   G2  langkah `specific`: elemen .driver-active-element mesti elemen sasaran sebenar
//   G3  langkah tindakan: tour black-box — popover dirender pada kedudukan langkah TEPAT
//       (navigasi deterministik ?panduan=<id>&langkah=<i>) + CTA hadir; guide berkoreografi
//       (klasifikasi/muat-naik/registrasi) menjalankan TINDAKAN SEBENAR hujung-ke-hujung.
//       Klik-maju generik diassert hanya pada transisi route-sama — semantik penuh
//       label↔kelakuan menjadi tegas selepas F2 (predikat bersatu).
//   G4  kitaran guide: mula → (maju) → tutup → ulang; `risk-accepted` (public.login) menguji
//       LALUAN FALLBACK sebenar (popover "Tindakan belum tersedia" + pautan artikel /bantuan).
//   G5  pengecualian direkod per-langkah dalam shard JSON (status_counts + senarai).
//
// Output: storage/app/plan-f6/shard-<GUIDANCE_SHARD>.json (skema beku F0(iv)(b)).
// Mobile: liputan mobile registrasi/awam dibawa oleh project ci-guidance (390×844);
// guidance-full berjalan desktop 1440×1000 — dicatat dalam medan `notes` shard JSON.

import { createHash } from 'node:crypto';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname } from 'node:path';
import { expect, test } from '@playwright/test';
import { attachFile } from './helpers/upload.js';
import { NAV_CANDIDATES, NAV_PRIMARY } from '../resources/js/help/nav-target-plan.js';

const MANIFEST_PATH = 'Audit Review Round Robin/bukti/plan-baseline/manifest.json';
const manifestRaw = readFileSync(MANIFEST_PATH, 'utf8');
const manifest = JSON.parse(manifestRaw);
const manifestSha = createHash('sha256').update(manifestRaw).digest('hex');

const SHARD = process.env.GUIDANCE_SHARD;
const VALID_SHARDS = ['screen', 'workflow', 'tenant-admin-public'];
if (!VALID_SHARDS.includes(SHARD)) {
    // GAGAL KERAS, bukan test.skip — skip senyap ialah gate palsu (F0(iv)(e) assertion 7).
    throw new Error(`GUIDANCE_SHARD wajib salah satu ${VALID_SHARDS.join('|')} — diberi: ${SHARD ?? '(tiada)'}`);
}

const tenantSlug = 'mam';
const defaultPassword = 'password';
const loginDelayMs = Number(process.env.E2E_ROLE_LOGIN_DELAY_MS ?? 0);
const guides = manifest.catalogue.filter((g) => g.shard === SHARD);
const expected = manifest.invariants.shards[SHARD];
const guideIds = manifest.catalogue.map((g) => g.guide_id);
const results = [];
const failures = [];
let lastLoginAt = 0;

const hydrate = (route) => route.replaceAll('{tenant}', tenantSlug);
const CHOREOGRAPHED = new Set([
    'screen.klasifikasi-peti-masuk',
    'workflow.admin_masjid.muat-naik-semak-dan-klasifikasikan-dokumen-serta-hantar-minit',
    'workflow.setiausaha.klasifikasikan-surat-masuk-dan-edarkan-minit',
    'public.registration',
]);

function accountFor(guide) {
    if (guide.panel === 'public') return null;
    if (guide.panel === 'admin') return { email: 'superadmin@diwan.test', login: '/admin/login', home: /\/admin\/?$/ };
    const role = guide.roles.includes('admin_masjid') ? 'admin_masjid' : guide.roles[0];

    return { email: `${role}@demo.test`, login: '/app/login', home: null, role };
}

async function newContextPage(browser, baseURL) {
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await context.addInitScript((ids) => {
        for (const id of ids) localStorage.setItem(`diwan-help-seen:${id}`, '1');
    }, guideIds);

    return { context, page: await context.newPage() };
}

async function login(page, account) {
    const wait = loginDelayMs - (Date.now() - lastLoginAt);
    if (wait > 0) await page.waitForTimeout(wait);
    await page.goto(account.login);
    await page.locator('input[id="form.login"]').fill(account.email);
    await page.locator('input[type="password"]').fill(defaultPassword);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    if (account.home) await page.waitForURL(account.home, { timeout: 60_000 });
    else await page.waitForURL((url) => url.pathname.replace(/\/$/, '') === `/app/${tenantSlug}`, { timeout: 60_000 });
    lastLoginAt = Date.now();
}

async function assertStepPopover(page, guide, step, total) {
    const runtime = page.locator('[data-diwan-help-runtime]');
    await expect(runtime, `${step.key}: deep-link mesti memilih guide yang diminta (17 route dikongsi)`)
        .toHaveAttribute('data-guide-id', guide.guide_id);
    const popover = page.locator('.driver-popover');
    await expect(popover, `${step.key}: popover tidak dirender`).toBeVisible();
    await expect(popover, `${step.key}: kedudukan langkah salah`).toContainText(`${step.index} daripada ${total}`);
    if (step.status === 'specific') {
        // G2 — sasaran sebenar disorot, bukan MAIN generik.
        const active = page.locator('.driver-active-element');
        await expect(active, `${step.key}: tiada elemen aktif`).toBeVisible();

        if (step.target === NAV_PRIMARY) {
            // F5c: `nav-primary` ialah sasaran LOGIK — ia tidak wujud sebagai
            // `data-help-target` dalam DOM. Runtime menyelesaikannya kepada calon konkrit
            // dalam ruang nama BERASINGAN `data-help-nav` (ruang nama berasingan itu wajib:
            // dua nama pada satu `data-help-target` menjadikan `decorateTargets()` menulis
            // semula atribut pada setiap panggilan → ribut MutationObserver).
            // Gate kekal ketat: elemen yang disorot mesti salah satu calon NAVIGASI SEBENAR.
            const nav = await active.getAttribute('data-help-nav');
            expect(NAV_CANDIDATES, `${step.key}: sorotan nav-primary = ${nav} (bukan calon nav)`)
                .toContain(nav);
            const tag = await active.evaluate((el) => el.tagName);
            expect(['MAIN', 'BODY'], `${step.key}: nav-primary menyorot ${tag} (sorotan terlalu besar)`)
                .not.toContain(tag);
        } else {
            const target = await active.getAttribute('data-help-target');
            expect(target, `${step.key}: sasaran aktif ${target} ≠ ${step.target}`).toBe(step.target);
        }
    }

    return popover;
}

/** Baca nombor langkah semasa daripada popover ("X daripada Y"). */
async function currentStepNumber(popover) {
    const text = await popover.locator('.driver-popover-progress-text, .driver-popover').first().innerText();
    const match = text.match(/(\d+)\s+daripada\s+(\d+)/);

    return match ? Number(match[1]) : null;
}

/** Pemandu per-langkah deterministik untuk subset langkah generik (navigasi &langkah=). */
async function driveGenericSteps(page, guide, steps) {
    const total = guide.steps.length;
    for (const step of steps) {
        const url = `${hydrate(step.route)}?panduan=${guide.guide_id}&langkah=${step.index - 1}`;
        await page.goto(url);
        const popover = await assertStepPopover(page, guide, step, total);
        const next = guide.steps[step.index] ?? null; // index 1-asas → steps[index] = langkah berikut
        const sameRoute = next && hydrate(next.route) === hydrate(step.route);
        const nextBtn = popover.locator('.driver-popover-next-btn');
        await expect(nextBtn, `${step.key}: CTA maju tiada`).toBeVisible();
        if (!next) {
            await nextBtn.click(); // langkah akhir → selesai
            await expect(popover, `${step.key}: guide tidak selesai pada langkah akhir`).toBeHidden();
        } else if (sameRoute) {
            await nextBtn.click(); // G3: maju TEPAT SEKALI
            await expect(popover, `${step.key}: klik maju tidak menambah tepat satu langkah`)
                .toContainText(`${step.index + 1} daripada ${total}`);
        }
        // Transisi silang-route: lelaran berikutnya menavigasi dgn &langkah= (deterministik).
    }
}

async function driveGenericGuide(page, guide) {
    await driveGenericSteps(page, guide, guide.steps);
}

/**
 * Mesin-keadaan koreografi wizard klasifikasi/muat-naik untuk 2 guide `workflow.*` —
 * TOLERAN terhadap auto-advance sync (watchForActionCompletion melompat serta-merta apabila
 * sasaran langkah berikut sudah kelihatan): baca nombor langkah SEMASA, lakukan tindakan
 * untuk langkah itu, kemudian poll sehingga nombor bertambah. Popover boleh tersembunyi
 * seketika (mod minimize "Panduan menunggu") — pembacaan nombor selamat-null.
 */
async function driveChoreographedRange(popover, actions, lastStep, guideId) {
    const readNumber = async () => {
        try {
            if (! await popover.isVisible()) return null;

            return await currentStepNumber(popover);
        } catch {
            return null;
        }
    };
    const clickCtaIfVisible = async () => {
        const btn = popover.locator('.driver-popover-next-btn');
        if (await btn.isVisible().catch(() => false)) await btn.click();
    };

    await expect.poll(readNumber, { timeout: 60_000, message: `${guideId}: popover awal tidak muncul` }).not.toBeNull();
    let n = await readNumber();
    const deadline = Date.now() + 240_000;
    while (n !== null && n < lastStep) {
        if (Date.now() > deadline) throw new Error(`${guideId}: koreografi melebihi masa pada langkah ${n}`);
        const action = actions[n];
        if (action) await action(clickCtaIfVisible);
        else await clickCtaIfVisible();
        const previous = n;
        // Fasa 1 pendek: beri peluang auto-advance; jika kalah race re-highlight
        // (rujuk expectStepAdvance) popover terkandas dgn CTA maju —
        // tekan sekali (laluan pengguna sebenar) sebelum poll penuh.
        try {
            await expect.poll(readNumber, { timeout: 10_000 }).toBeGreaterThan(previous);
        } catch {
            await recoverStalledTour(popover);
        }
        await expect
            .poll(readNumber, { timeout: 90_000, message: `${guideId}: langkah ${previous} tidak maju` })
            .toBeGreaterThan(previous);
        n = await readNumber();
    }
    await expect(popover, `${guideId}: popover langkah akhir koreografi (${lastStep}) tidak kelihatan`).toBeVisible();
}

/**
 * Klik elemen halaman semasa tour aktif: overlay kekal semasa minimize dan lubang
 * sorotan ikut geometri fon — pada runner Linux butang boleh jatuh di luar lubang.
 * Klik koordinat (biasa ATAU force) diserap overlay — force cuma melangkau semakan,
 * klik tetap mendarat pada elemen teratas di koordinat. dispatchEvent menghantar event
 * terus pada ELEMEN, jadi handler Livewire/Alpine menerima tanpa kira lapisan.
 * toBeEnabled dahulu — klik semasa wire:loading disabled hilang tanpa kesan.
 * (Gate menguji sorotan+kemajuan langkah, bukan hit-test overlay; UX = skop F2/F6.)
 */
async function forceClickWhenEnabled(locator) {
    await expect(locator).toBeEnabled();
    await locator.dispatchEvent('click');
}

/**
 * Hantar borang muat naik dan tunggu toast — dengan cuba semula sehingga ada KESAN.
 *
 * 🔎 PUNCA kegagalan berselang shard `workflow` (F,P,F,P,F,P sejak F3) akhirnya dibuktikan
 * daripada `serve-ci.log` run 30842419416 (artifak diagnostik yang ditambah pada `08d3643`):
 *
 *     18:56:52  /livewire/upload-file        <- fail SAMPAI ke pelayan
 *     18:56:54  /livewire/update   500ms     <- muat naik selesai
 *     …62 saat SIFAR permintaan…
 *     18:57:56  /app/login                   <- ujian tamat masa
 *
 * Klik "Hantar" tidak menghasilkan SATU PUN permintaan. Jadi ia bukan overlay (event tidak
 * pernah perlu melalui koordinat — `dispatchEvent` menghantar terus pada elemen), bukan
 * antivirus, bukan masa: **klik itu hilang senyap**. Penjelasan yang konsisten dengan bukti:
 * morph Livewire menggantikan nod footer modal selepas muat naik selesai, dan Alpine memasang
 * semula pendengarnya secara TAK SEGERAK — klik yang mendarat dalam tetingkap itu mengenai
 * nod tanpa pendengar. Tiada ralat, tiada permintaan, tiada kesan.
 *
 * ⚠️ Ini juga bermakna PENGGUNA sebenar yang menekan Hantar tepat dalam tetingkap itu tidak
 * akan nampak apa-apa berlaku. Kelemahan produk yang tulen (severiti rendah — tekan sekali
 * lagi memulihkannya) dan DIREKOD untuk F6/F7; F5 tidak mendakwa membaikinya.
 *
 * Cuba semula hanya SELAGI modal masih terbuka: modal yang tertutup bermakna penghantaran
 * sudah diterima, jadi kita hanya menunggu toast — tiada risiko menghantar dua kali.
 */
async function submitUploadUntilToast(page, modal) {
    // ⚠️ MESTI diskop kepada modal yang DIBERI, bukan `page.locator(...)` seluruh halaman.
    // Guide `workflow.*` membuka DUA modal berturutan (muat naik, kemudian klasifikasi) dan
    // Filament mengekalkan nod modal terdahulu dalam DOM. Locator seluruh halaman boleh
    // memadan butang Hantar BASI daripada render terdahulu: ia wujud dan "enabled", tetapi
    // tiada lagi terikat kepada komponen hidup — `dispatchEvent` padanya menghasilkan SIFAR
    // permintaan, selama-lamanya. Dibuktikan: shard `workflow` LULUS dua larian CI dengan
    // locator berskop-dialog yang asal, lalu GAGAL sebaik saya menjadikannya seluruh halaman
    // (serve-ci.log: 0 permintaan sepanjang 120s retry).
    const submit = modal.locator('[data-help-target="inbox-upload-submit"]').last();
    const toast = page.getByText(/\d+ dokumen dimuat naik ke Peti Masuk/);

    await expect(async () => {
        if (await modal.isVisible().catch(() => false)) {
            await expect(submit).toBeEnabled({ timeout: 15_000 });
            await submit.dispatchEvent('click');
        }
        await expect(toast).toBeVisible({ timeout: 10_000 });
    }).toPass({ timeout: 120_000 });
}

/**
 * Isi/pilih medan borang Livewire dengan selamat terhadap morph — `fill()` = clear +
 * insertText; morph yang mendarat di antaranya memulihkan nilai lama lalu insertText
 * MENAMBAH di hujung (nilai berganda). Rujuk nota penuh dlm guidance.spec.js.
 */
async function fillStable(locator, value) {
    await expect(async () => {
        await locator.fill(value);
        await expect(locator).toHaveValue(value, { timeout: 2_000 });
    }).toPass({ timeout: 30_000 });
}

async function selectStable(locator, value) {
    await expect(async () => {
        await locator.selectOption(value);
        await expect(locator).toHaveValue(typeof value === 'string' ? value : /.+/, { timeout: 2_000 });
    }).toPass({ timeout: 30_000 });
}

/**
 * Pulihkan tour yang terkandas kerana auto-advance kalah race re-highlight (rujuk nota
 * penuh dlm guidance.spec.js — bug produk skop F2 §3). Laluan pengguna sebenar = DUA
 * butang: "Tunjuk arahan" pada banner menunggu (popover masih display:none daripada
 * minimiseForAction), kemudian CTA maju popover.
 */
async function recoverStalledTour(popover) {
    // dispatchEvent, bukan klik tetikus: `.driver-active * { pointer-events: none }`
    // (vendor) menolak klik pada banner — rujuk nota penuh dlm guidance.spec.js.
    const show = popover.page().locator('[data-diwan-tour-waiting] button');
    if (await show.isVisible().catch(() => false)) await show.dispatchEvent('click').catch(() => {});
    const nudge = popover.locator('.driver-popover-next-btn');
    if (await nudge.isVisible().catch(() => false)) await nudge.click().catch(() => {});
}

async function expectStepAdvance(popover, text) {
    try {
        await expect(popover).toContainText(text, { timeout: 5_000 });

        return;
    } catch {
        // auto-advance kalah race — pulihkan melalui UI seperti pengguna
    }
    // Elak nudge jika advance mendarat tepat selepas timeout (akan melompat satu langkah).
    if (await popover.textContent().then((t) => (t ?? '').includes(text)).catch(() => false)) return;
    await recoverStalledTour(popover);
    await expect(popover).toContainText(text);
}

/** Isi metadata wizard klasifikasi (jenis + arah) — guna semula corak guidance.spec.js. */
async function fillClassificationMetadata(page) {
    const recordType = page.locator('#mountedActionSchema0\\.record_type');
    if (! await recordType.inputValue()) await selectStable(recordType, 'surat_menyurat');
    await selectStable(page.locator('#mountedActionSchema0\\.direction'), 'masuk');
}

async function fillClassificationFile(page, modal) {
    const fileStep = modal.locator('form.fi-active');
    await fileStep.locator('.fi-select-input-btn').first().click();
    await page.getByRole('option', { name: /MAM\./ }).first().click();
    await selectStable(page.locator('#mountedActionSchema0\\.sensitivity'), 'dalaman');
}

/** G4 — kitaran ringkas: mula → tutup → ulang (resume penuh diliputi ci-guidance). */
async function cycleGuide(page, guide) {
    const first = guide.steps[0];
    const url = `${hydrate(first.route)}?panduan=${guide.guide_id}&langkah=0`;
    const popover = page.locator('.driver-popover');
    await page.goto(url);
    await expect(popover, `${guide.guide_id}: kitaran mula gagal`).toBeVisible();
    await popover.locator('.driver-popover-close-btn').click();
    await expect(popover).toBeHidden();
    await page.goto(url); // ulang selepas tutup
    await expect(popover, `${guide.guide_id}: kitaran ulang gagal`).toBeVisible();
    await popover.locator('.driver-popover-close-btn').click();
    await expect(popover).toBeHidden();
}

/** risk-accepted (public.login): buktikan LALUAN FALLBACK pengguna sebenar (§7.3 peraturan 2). */
async function assertFallbackPath(page, guide) {
    await page.goto(`${hydrate(guide.route)}?panduan=${guide.guide_id}&langkah=0`);
    const popover = page.locator('.driver-popover');
    await expect(popover).toBeVisible();
    await expect(popover, `${guide.guide_id}: fallback tidak dipaparkan`).toContainText('Tindakan belum tersedia');
    const article = popover.locator('a.diwan-tour-article');
    await expect(article, `${guide.guide_id}: pautan artikel fallback tiada`).toBeVisible();
    const href = await article.getAttribute('href');
    expect(href, `${guide.guide_id}: href artikel kosong`).toContain('/bantuan');
    await page.goto(href);
    await expect(page.locator('[data-help-target="help-center"]'), `${guide.guide_id}: artikel /bantuan tidak terbuka`)
        .toBeVisible();
}

async function ensureInboxFixture(page) {
    if (await page.getByRole('button', { name: 'Klasifikasikan', exact: true }).first().isVisible().catch(() => false)) return;
    const marker = Date.now();
    await page.getByRole('button', { name: /Muat Naik Dokumen/i }).click();
    const dialog = page.getByRole('dialog');
    await attachFile(dialog, {
        name: `Dokumen gate G3 ${marker}.txt`,
        mimeType: 'text/plain',
        buffer: Buffer.from(`Dokumen ujian gate penuh ${marker}.`),
    });
    const submit = dialog.getByRole('button', { name: 'Hantar', exact: true });
    await expect(submit).toBeEnabled({ timeout: 60_000 });
    await submit.click();
    await expect(page.getByText('1 dokumen dimuat naik ke Peti Masuk.')).toBeVisible({ timeout: 60_000 });
}

/** Ikut wizard klasifikasi dari langkah popover semasa hingga langkah akhir guide (tanpa hantar). */
async function followClassificationModal(page, popover, modal, plan) {
    for (const act of plan) {
        await expectStepAdvance(popover, `${act.num} daripada ${act.total}`);
        await popover.locator('.driver-popover-next-btn').click();
        if (act.do === 'open-modal') {
            await page.getByRole('button', { name: 'Klasifikasikan', exact: true }).first().click();
            await expect(modal).toBeVisible();
        } else if (act.do === 'next') {
            await forceClickWhenEnabled(modal.getByRole('button', { name: 'Seterusnya', exact: true }));
        } else if (act.do === 'metadata') {
            await fillClassificationMetadata(page);
        } else if (act.do === 'file') {
            await fillClassificationFile(page, modal);
        }
        // act.do === 'none' → klik popover sahaja (langkah pengesahan/minit/review)
    }
}

for (const guide of guides) {
    test(`gate ${SHARD}: ${guide.guide_id} (${guide.steps.length} langkah)`, async ({ browser, baseURL }) => {
        test.setTimeout(300_000);
        const account = accountFor(guide);
        const { context, page } = await newContextPage(browser, baseURL);
        try {
            if (account) await login(page, account);

            if (guide.steps[0]?.status === 'risk-accepted') {
                await assertFallbackPath(page, guide);
            } else if (guide.guide_id === 'screen.muat-naik-dokumen') {
                // F5b: guide ini dahulu 5× `page-primary` generik, jadi `driveGenericSteps`
                // memadai — klik "Seterusnya" sentiasa maju. Kini langkah 1–3 menyasar
                // butang → dropzone → Hantar, dan sasaran langkah berikut hanya WUJUD
                // selepas tindakan sebenar. `stepAdvancePlan` betul memberi kind
                // `wait-for-action` (CTA "Buat pada skrin" yang MEMINIMIZE, bukan maju),
                // jadi pemandu generik tidak boleh lagi digunakan — sama seperti guide
                // `workflow.*` muat naik. Ini akibat langsung perubahan produk (peraturan #9).
                await page.goto(`/app/${tenantSlug}/peti-masuk`);
                await ensureInboxFixture(page);
                await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=${guide.guide_id}&langkah=0`);
                const popover = page.locator('.driver-popover');

                // `getByRole('dialog')` TIDAK boleh digunakan di sini: popover tour juga
                // `role="dialog"` (ARIA yang betul), jadi ia melanggar mod ketat Playwright.
                // Sasar tetingkap modal muat naik secara tepat melalui sasaran bantuannya.
                const modalMuatNaik = page.locator('[data-help-target="inbox-upload-modal"]');

                const bukaModal = async (cta) => {
                    await cta();
                    await forceClickWhenEnabled(page.locator('[data-help-target="inbox-upload"]'));
                    await expect(modalMuatNaik).toBeVisible({ timeout: 30_000 });
                };
                const pilihFail = async (cta) => {
                    await cta();
                    await attachFile(modalMuatNaik, {
                        name: `Dokumen skrin ${Date.now()}.txt`,
                        mimeType: 'text/plain',
                        buffer: Buffer.from(`Dokumen gate skrin ${Date.now()}.`),
                    });
                };
                const hantar = async (cta) => {
                    await cta();
                    await submitUploadUntilToast(page, modalMuatNaik);
                };

                await driveChoreographedRange(
                    popover,
                    { 1: bukaModal, 2: pilihFail, 3: hantar },
                    guide.steps.length,
                    guide.guide_id,
                );
                await popover.getByRole('button', { name: 'Tutup panduan' }).click();
                await expect(popover).toBeHidden();
            } else if (guide.guide_id === 'public.registration') {
                await page.goto(`/daftar?panduan=public.registration&langkah=0`);
                const popover = page.locator('.driver-popover');
                await expect(popover).toContainText('1 daripada 4');
                await popover.locator('.driver-popover-next-btn').click();
                const organisation = page.locator('[data-help-target="registration-organisation"]');
                await fillStable(organisation.locator('input').nth(0), `Masjid Gate ${Date.now()}`);
                // Blur eksplisit (wire:model.blur) → auto-slug; tunggu ia mendarat sebelum
                // medan lain (rujuk nota fillStable).
                await organisation.locator('input').nth(0).blur();
                await expect(organisation.locator('input').nth(3)).not.toHaveValue('');
                await selectStable(organisation.locator('select'), { label: 'Selangor' });
                await fillStable(organisation.locator('input').nth(1), 'Petaling');
                // Kod akronim: 3–6 HURUF sahaja (validasi /daftar) + unik setiap larian.
                await fillStable(
                    organisation.locator('input').nth(2),
                    'G'.concat(Date.now().toString().slice(-5).replace(/\d/g, (d) => 'ABCDEFGHIJ'[Number(d)])),
                );
                await fillStable(organisation.locator('input').nth(3), `gate-${Date.now()}`);
                await forceClickWhenEnabled(page.locator('[data-help-target="registration-next"]'));
                await expectStepAdvance(popover, '2 daripada 4');
                await popover.locator('.driver-popover-next-btn').click();
                const admin = page.locator('[data-help-target="registration-admin"]');
                await admin.locator('input').nth(0).fill('Pentadbir Gate');
                await admin.locator('input').nth(1).fill(`gate-${Date.now()}@example.test`);
                // Telefon UNIK setiap larian — pendaftaran menolak nombor WA pendua (fix Julai),
                // dan penolakan itu senyap dari langkah 3 (ralat melekat pada medan langkah 2).
                await admin.locator('input').nth(2).fill(`6012${Date.now().toString().slice(-8)}`);
                await forceClickWhenEnabled(page.locator('[data-help-target="registration-next"]'));
                await expect(page.locator('[data-help-target="registration-consent"]')).toBeVisible();
                await expectStepAdvance(popover, '3 daripada 4');
                await popover.locator('.driver-popover-next-btn').click();
                const registration = page.locator('[data-help-target="registration-consent"]').locator('..');
                // el.click() terus (bukan check() koordinat) — rujuk nota overlay pada forceClickWhenEnabled.
                await page.locator('input[type="checkbox"]').nth(0).evaluate((el) => { if (!el.checked) el.click(); });
                await page.locator('input[type="checkbox"]').nth(1).evaluate((el) => { if (!el.checked) el.click(); });
                await forceClickWhenEnabled(page.getByRole('button', { name: 'Hantar Permohonan' }));
                await expect(page.getByText('Permohonan diterima!')).toBeVisible({ timeout: 60_000 });
                await expect(page.locator('[data-help-target="registration-complete"]')).toBeVisible();
                await expectStepAdvance(popover, '4 daripada 4');
                await popover.locator('.driver-popover-next-btn').click();
                await expect(popover).toBeHidden();
                void registration;
            } else if (guide.guide_id === 'screen.klasifikasi-peti-masuk') {
                await page.goto(`/app/${tenantSlug}/peti-masuk`);
                await ensureInboxFixture(page);
                await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=${guide.guide_id}&langkah=0`);
                const popover = page.locator('.driver-popover');
                const modal = page.locator('.fi-modal-window:visible').last();
                await followClassificationModal(page, popover, modal, [
                    { num: 1, total: 11, do: 'open-modal' },
                    { num: 2, total: 11, do: 'next' },
                    { num: 3, total: 11, do: 'metadata' },
                    { num: 4, total: 11, do: 'none' },
                    { num: 5, total: 11, do: 'next' },
                    { num: 6, total: 11, do: 'file' },
                    { num: 7, total: 11, do: 'next' },
                    { num: 8, total: 11, do: 'none' },
                    { num: 9, total: 11, do: 'next' },
                    { num: 10, total: 11, do: 'none' },
                ]);
                await expect(popover).toContainText('11 daripada 11');
                await expect(page.locator('[data-help-target="classification-submit"]:visible')).toBeVisible();
                await popover.getByRole('button', { name: 'Tutup panduan' }).click();
                await modal.getByRole('button', { name: 'Tutup' }).click();
                await expect(modal).toBeHidden();
            } else if (CHOREOGRAPHED.has(guide.guide_id)) {
                // Dua guide workflow klasifikasi (13/20 langkah): langkah generik AWAL dipandu
                // per-langkah; julat modal (spesifik) diikuti mesin-keadaan toleran; langkah
                // generik PENGHUJUNG (minit-saya/log-aktiviti) dipandu per-langkah semula.
                const specificSteps = guide.steps.filter((s) => s.status === 'specific');
                const firstSpecific = specificSteps[0];
                const lastSpecific = specificSteps[specificSteps.length - 1];
                await driveGenericSteps(page, guide, guide.steps.filter((s) => s.index < firstSpecific.index));

                await page.goto(`/app/${tenantSlug}/peti-masuk`);
                await ensureInboxFixture(page);
                await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=${guide.guide_id}&langkah=${firstSpecific.index - 1}`);
                const popover = page.locator('.driver-popover');
                const modal = page.locator('.fi-modal-window:visible').last();
                const upload = async (cta) => {
                    // Popover boleh muncul semula DI ATAS modal pada setiap kemajuan langkah
                    // (6→7) dan memintas klik (RR-08-03) — minimize (CTA) sebelum SETIAP
                    // interaksi modal.
                    await cta();
                    await page.getByRole('button', { name: /Muat Naik Dokumen/i }).click();
                    // `getByRole('dialog')` melanggar mod ketat sebaik popover tour muncul —
                    // popover JUGA `role="dialog"` (ARIA yang betul). Sasarkan tetingkap modal
                    // muat naik secara tepat; kalau tidak, semakan `isVisible()` melempar dan
                    // mana-mana `.catch(() => false)` akan MENELANNYA secara senyap.
                    const dialog = page.locator('[data-help-target="inbox-upload-modal"]');
                    await cta();
                    await attachFile(dialog, {
                        name: `Dokumen workflow ${Date.now()}.txt`,
                        mimeType: 'text/plain',
                        buffer: Buffer.from(`Dokumen workflow gate ${Date.now()}.`),
                    });
                    await cta();
                    // ⛳ Tapak kegagalan berselang shard `workflow` sejak F3 (F,P,F,P,F,P).
                    // Diagnosis LAMA saya ("`force:true` diserap overlay") sudah terbukti
                    // SALAH: `dispatchEvent` memintas koordinat sepenuhnya namun kegagalan
                    // berulang. Punca SEBENAR akhirnya dibuktikan daripada `serve-ci.log`
                    // (run 30842419416): selepas muat naik selesai, klik Hantar menghasilkan
                    // SIFAR permintaan selama 62 saat. Lihat `submitUploadUntilToast()`.
                    await submitUploadUntilToast(page, dialog);
                };
                const openClassify = async (cta) => {
                    await cta();
                    await page.getByRole('button', { name: 'Klasifikasikan', exact: true }).first().click();
                    await expect(modal).toBeVisible();
                };
                const wizardNext = async (cta) => {
                    await cta();
                    await forceClickWhenEnabled(modal.getByRole('button', { name: 'Seterusnya', exact: true }));
                };
                const metadataThenNext = async (cta) => {
                    await cta();
                    await fillClassificationMetadata(page);
                    await forceClickWhenEnabled(modal.getByRole('button', { name: 'Seterusnya', exact: true }));
                };
                const fileThenNext = async (cta) => {
                    await cta();
                    await fillClassificationFile(page, modal);
                    await forceClickWhenEnabled(modal.getByRole('button', { name: 'Seterusnya', exact: true }));
                };

                const actions = guide.guide_id.startsWith('workflow.setiausaha.')
                    ? { 4: openClassify, 5: wizardNext, 6: metadataThenNext, 7: fileThenNext, 8: wizardNext }
                    : { 5: upload, 9: openClassify, 10: wizardNext, 11: metadataThenNext, 12: fileThenNext, 13: wizardNext };

                await driveChoreographedRange(popover, actions, lastSpecific.index, guide.guide_id);
                await popover.getByRole('button', { name: 'Tutup panduan' }).click();
                await modal.getByRole('button', { name: 'Tutup' }).click().catch(() => {});
                await expect(popover).toBeHidden();

                await driveGenericSteps(page, guide, guide.steps.filter((s) => s.index > lastSpecific.index));
            } else {
                await driveGenericGuide(page, guide);
                await cycleGuide(page, guide);
            }

            results.push({ guide: guide.guide_id, steps: guide.steps.map((s) => s.key), ok: true });
        } catch (error) {
            failures.push({ guide: guide.guide_id, message: String(error?.message ?? error).slice(0, 500) });
            throw error;
        } finally {
            await context.close();
        }
    });
}

test(`tulis shard JSON (${SHARD})`, async () => {
    const shardGuides = guides;
    const stepEntries = shardGuides.flatMap((g) => g.steps);
    const statusCounts = { 'specific': 0, 'generic-justified': 0, 'not-applicable': 0, 'risk-accepted': 0, 'blocked': 0 };
    for (const s of stepEntries) statusCounts[s.status] += 1;
    const doneGuides = new Set(results.map((r) => r.guide));
    const payload = {
        schema_version: 1,
        shard: SHARD,
        catalog_version: manifest.catalog_version,
        manifest_sha256: manifestSha,
        expected: { guides: expected.guides, steps: expected.steps, action_steps: expected.action_steps },
        guide_ids: shardGuides.map((g) => g.guide_id),
        step_ids: stepEntries.map((s) => s.key),
        action_step_ids: stepEntries.filter((s) => s.wait_for_user).map((s) => s.key),
        status_counts: statusCounts,
        blocked: stepEntries.filter((s) => s.status === 'blocked').map((s) => ({ step: s.key, reason: s.reason ?? '' })),
        failures: failures.map((f) => ({ step: f.guide, gate: 'G3/G4', message: f.message })),
        complete: failures.length === 0 && doneGuides.size === shardGuides.length,
        notes: 'Baseline pra-F2/F6: G3 klik-maju penuh diassert pada transisi route-sama; liputan mobile registrasi/awam dibawa project ci-guidance (390x844).',
    };
    const out = `storage/app/plan-f6/shard-${SHARD}.json`;
    mkdirSync(dirname(out), { recursive: true });
    writeFileSync(out, JSON.stringify(payload, null, 2) + '\n');
    expect(payload.complete, `shard ${SHARD} tidak lengkap: ${JSON.stringify(payload.failures)}`).toBe(true);
    expect(payload.guide_ids.length).toBe(expected.guides);
    expect(payload.step_ids.length).toBe(expected.steps);
    expect(payload.action_step_ids.length).toBe(expected.action_steps);
});
