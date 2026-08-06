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

/**
 * F6-W4 — indeks langkah yang mesin-keadaan koreografi benar-benar pandu, per guide.
 *
 * Dahulu sempadan julat disimpulkan daripada `status === 'specific'`. Proksi itu sah hanya
 * selagi langkah AWAL dan PENGHUJUNG guide masih generik. W4 memberi kesemua langkah sasaran
 * spesifik, jadi `lastSpecific` melompat 14 → 20: mesin-keadaan — yang hanya memahami SATU
 * halaman dan modalnya — cuba memandu merentas peti-masuk → minit-saya → log-aktiviti dan
 * tersekat pada peralihan silang-halaman ("langkah 14 tidak maju", 90s).
 *
 * Nilai di bawah ialah kunci peta `actions` guide berkenaan; ia diassert sepadan pada masa
 * larian supaya kedua-duanya tidak boleh hanyut secara senyap.
 */
const AKSI_KOREOGRAFI = {
    'workflow.admin_masjid.muat-naik-semak-dan-klasifikasikan-dokumen-serta-hantar-minit': [5, 9, 10, 11, 12, 13],
    'workflow.setiausaha.klasifikasikan-surat-masuk-dan-edarkan-minit': [4, 5, 6, 7, 8],
};

function accountFor(guide) {
    if (guide.panel === 'public') return null;
    if (guide.panel === 'admin') return { email: 'superadmin@diwan.test', login: '/admin/login', home: /\/admin\/?$/ };
    const role = guide.roles.includes('admin_masjid') ? 'admin_masjid' : guide.roles[0];

    return { email: `${role}@demo.test`, login: '/app/login', home: null, role };
}

async function newContextPage(browser, baseURL, sasaranGuide = []) {
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await context.addInitScript((ids) => {
        for (const id of ids) localStorage.setItem(`diwan-help-seen:${id}`, '1');
    }, guideIds);
    // PEREKAM keadaan tour (G3). Mengundi keadaan SEKETIKA tidak boleh dipercayai: mekanisme
    // sync F2 memang memajukan tour sebaik sasaran langkah berikut muncul, jadi tour boleh
    // melintasi satu langkah dalam beberapa milisaat dan pengundi terlepas pandang — kemudian
    // menunggu 90s untuk nombor langkah yang TIDAK akan kembali (diukur: harness menunggu
    // `n: 4` sedangkan tour sudah `n: 5`). Merakam setiap peralihan menjadikan assertion
    // kalis-perlumbaan DAN lebih kuat: ia membuktikan langkah itu benar-benar berlaku dengan
    // sasaran yang betul, bukan hanya bahawa ia boleh dicerap pada satu ketika tertentu.
    // Setiap entri turut merakam KEADAAN SETIAP SASARAN guide ini. Tanpa itu, "sasaran tiada"
    // dan "resolusi gagal walaupun sasaran ADA" menghasilkan jejak yang kelihatan SAMA
    // (`4:page-content`) — dan kekaburan itu memakan pusingan CI 25 minit setiap kali. Perekam
    // juga merakam banner (tour diminimize = CTA benar-benar diklik) dan bilangan modal
    // (tindakan benar-benar berkuat kuasa). Ini pengamatan TULEN: tiada interaksi diubah.
    await context.addInitScript((sasaran) => {
        window.__diwanTourLog = [];
        // F6-W2: kaunter dokumen. `__diwanTourLog` diset semula pada SETIAP muatan, jadi
        // "log kosong" tidak dapat membezakan halaman yang tidak pernah memulakan tour
        // daripada halaman yang dimuat semula berulang kali (gelung pengalihan). Kaunter ini
        // hidup dalam sessionStorage, jadi ia SELAMAT daripada set semula itu.
        try {
            const n = Number(sessionStorage.getItem('__diwanNav') || 0) + 1;
            sessionStorage.setItem('__diwanNav', String(n));
            sessionStorage.setItem('__diwanNavAkhir', location.pathname + location.search);
        } catch { /* konteks tanpa storage */ }
        const nampak = (el) => {
            const b = el.getBoundingClientRect();
            const s = getComputedStyle(el);

            return s.display !== 'none' && s.visibility !== 'hidden' && b.width > 0 && b.height > 0;
        };
        const keadaanSasaran = () => {
            const out = {};
            for (const t of sasaran) {
                const els = [...document.querySelectorAll(`[data-help-target="${t}"]`)];
                if (!els.length) { out[t] = 'tiada'; continue; }
                const n = els.filter(nampak).length;
                out[t] = n ? (n === 1 ? 'ada' : `ada×${n}`) : `sembunyi×${els.length}`;
            }

            return out;
        };
        const rakam = () => {
            const pop = document.querySelector('.driver-popover');
            if (!pop) return;
            const teks = pop.innerText || '';
            const padan = teks.match(/(\d+)\s+daripada\s+\d+/);
            const entri = {
                n: padan ? Number(padan[1]) : null,
                aktif: [...document.querySelectorAll('.driver-active-element')]
                    .map((el) => el.getAttribute('data-help-target')),
                ralatPalsu: teks.includes('Tindakan belum tersedia'),
                banner: Boolean(document.querySelector('[data-diwan-tour-waiting]')),
                modal: document.querySelectorAll('.fi-modal-window').length,
                sasaran: keadaanSasaran(),
                // Langkah WIZARD Filament yang sedang aktif. Tanpa ini, "sasaran hilang" tidak
                // dapat dibezakan daripada "wizard sudah melangkaui langkah itu" — dan itulah
                // kekaburan yang tinggal pada `persediaan-berpandu` (`3:ada → 3:sembunyi`).
                wizard: (() => {
                    const btn = [...document.querySelectorAll('.fi-sc-wizard-header-step-btn')];
                    if (!btn.length) return null;
                    const aktif = btn.findIndex((b) => b.getAttribute('aria-current') === 'step'
                        || b.dataset.active === 'true' || b.className.includes('fi-active'));

                    return aktif >= 0 ? aktif + 1 : `?${btn.length}`;
                })(),
            };
            entri.kunci = `${entri.n}|${entri.aktif.join(',')}|${entri.ralatPalsu}|${entri.banner}`
                + `|${entri.modal}|${entri.wizard}`
                + `|${Object.entries(entri.sasaran).map(([k, v]) => k + '=' + v).join(',')}`;
            const akhir = window.__diwanTourLog[window.__diwanTourLog.length - 1];
            if (akhir && akhir.kunci === entri.kunci) return;
            window.__diwanTourLog.push(entri);
        };
        // ⚠️ Skrip init berjalan SEBELUM mana-mana skrip halaman — `document.documentElement`
        // boleh MASIH null pada ketika itu, dan `observe(null, …)` melempar lalu memusnahkan
        // pemasangan secara SENYAP (log tetap wujud tetapi kekal kosong; itu memerahkan
        // langkah 1 dengan mesej yang mengelirukan). Interval selamat dipasang segera kerana
        // `rakam` hanya menyentuh DOM apabila ia dipanggil.
        window.setInterval(rakam, 100);
        window.__diwanTourLogSedia = false;
        const pasang = () => {
            if (!document.documentElement || window.__diwanTourLogSedia) return;
            new MutationObserver(rakam).observe(document.documentElement, {
                childList: true, subtree: true, attributes: true,
            });
            window.__diwanTourLogSedia = true;
        };
        pasang();
        document.addEventListener('DOMContentLoaded', pasang, { once: true });
        document.addEventListener('readystatechange', pasang);
    }, sasaranGuide);

    const page = await context.newPage();
    // F6-W2: jejak permintaan yang BELUM selesai. `readyState=loading` yang berpanjangan
    // hanya boleh dijelaskan oleh subsumber yang tergantung, dan tanpa senarai ini "halaman
    // lambat" tidak dapat dibezakan daripada "pelayan tersekat". Pengamatan tulen.
    page.__belumSelesai = new Map();
    page.on('request', (r) => page.__belumSelesai.set(r, r.url()));
    page.on('requestfinished', (r) => page.__belumSelesai.delete(r));
    page.on('requestfailed', (r) => page.__belumSelesai.delete(r));

    return { context, page };
}

/** Ringkasan permintaan yang masih tergantung (maksimum 6, laluan sahaja). */
function permintaanTergantung(page) {
    const senarai = [...(page.__belumSelesai?.values() ?? [])]
        .map((u) => { try { return new URL(u).pathname; } catch { return u; } });

    return senarai.length ? `${senarai.length} tergantung: ${senarai.slice(0, 6).join(', ')}` : 'tiada';
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

/**
 * G2 untuk guide yang dipandu KOREOGRAFI (F6-W3).
 *
 * `assertStepPopover()` menguatkuasakan "elemen aktif = sasaran langkah", tetapi ia hanya
 * dipanggil oleh `driveGenericSteps()` dan `driveFlowGuide()`. `driveChoreographedRange()`
 * hanya mengundi NOMBOR langkah — jadi guide berkoreografi boleh mendakwa status `specific`
 * tanpa satu pun bukti bahawa sasarannya benar-benar disorot. Menaikkan sesuatu langkah
 * kepada `specific` tanpa menutup jurang ini bermakna menambah ujian yang tidak boleh gagal.
 *
 * Perekam dalam halaman sudah merakam `aktif: [data-help-target…]` pada setiap peralihan,
 * jadi buktinya sudah dikumpul; yang tiada hanyalah assertion ke atasnya. Assertion bertanya
 * "adakah langkah *i* PERNAH berlaku dengan sasaran betul" — kalis-perlumbaan, sama seperti
 * pendekatan yang F6-W1 buktikan perlu.
 *
 * ── Satu pengecualian yang DIUKUR, bukan diandaikan ──────────────────────────────────────
 * Sasaran wizard klasifikasi DILUASKAN kepada modalnya oleh runtime — `help.js:231`:
 *
 *     if (step.target.startsWith('classification-') && step.target !== 'classification-submit')
 *         return exact.closest('.fi-modal-window') || exact;
 *
 * Sebabnya kekal sah: menyorot SATU medan di dalam modal boleh-skrol menolak popover (dan
 * lubang overlaynya) ke luar viewport — kecacatan yang sudah diukur dan dibawa ke F7. Jadi
 * `inbox-classification-modal` ialah jawapan yang BETUL untuk langkah-langkah itu, bukan
 * kegagalan. `classification-submit` ialah pengecualian dalam kod, dan kelakuannya sepadan
 * (diukur: `13:classification-submit` disorot terus).
 *
 * Corak sama seperti sasaran LOGIK `nav-primary` yang `assertStepPopover` sudah kendalikan.
 */
const CLASSIFICATION_MODAL = 'inbox-classification-modal';
const diluaskanKeModal = (t) => t.startsWith('classification-') && t !== 'classification-submit';

async function assertTrailTargets(page, guide, sehinggaIndex = Infinity, daripadaIndex = 0) {
    const jejak = await page.evaluate(() => (window.__diwanTourLog ?? [])
        .filter((e) => e.n !== null)
        .map((e) => ({ n: e.n, aktif: e.aktif, sasaran: e.sasaran })));
    if (!jejak.length) throw new Error(`${guide.guide_id}: perekam tour KOSONG — G2 tidak boleh disahkan`);

    const ringkas = jejak.map((e) => `${e.n}:${(e.aktif ?? []).filter(Boolean).join('+') || '-'}`).join(' → ');
    for (const step of guide.steps) {
        // F6-W4: perekam DIRESET oleh navigasi, jadi jejak halaman koreografi hanya
        // mengandungi langkah yang dipandu DI SANA. Sebelum W4 had bawah tidak diperlukan
        // kerana langkah sebelum julat semuanya generik dan dilangkau oleh semakan status;
        // kini ia spesifik dan dipandu `driveGenericSteps` pada halaman LAIN, jadi mencarinya
        // dalam jejak ini akan gagal atas sebab yang salah (diukur di CI: "langkah tidak
        // pernah dirakam perekam (jejak: 5:inbox-upload)").
        if (step.status !== 'specific' || step.index > sehinggaIndex || step.index < daripadaIndex) continue;
        const dilihat = jejak.filter((e) => e.n === step.index);
        // Langkah yang tidak pernah dicerap = kelemahan perekaman, bukan kegagalan sasaran;
        // laporkan berasingan supaya dua keadaan itu tidak boleh dikelirukan.
        if (!dilihat.length) {
            throw new Error(`${step.key}: langkah tidak pernah dirakam perekam (jejak: ${ringkas})`);
        }
        // Peluasan modal TIDAK melonggarkan gate: ia menuntut DUA fakta serentak — modal yang
        // betul disorot, DAN medan yang diisytihar benar-benar hadir lagi kelihatan pada saat
        // itu. Menerima "modal sahaja" akan membenarkan wizard berada pada langkah yang salah.
        const kena = dilihat.some((e) => {
            const aktif = e.aktif ?? [];
            if (aktif.includes(step.target)) return true;
            if (!diluaskanKeModal(step.target)) return false;

            return aktif.includes(CLASSIFICATION_MODAL)
                && String(e.sasaran?.[step.target] ?? '').startsWith('ada');
        });
        if (!kena) {
            const keadaan = dilihat.map((e) => e.sasaran?.[step.target] ?? '?').join('/');
            throw new Error(`${step.key}: sasaran "${step.target}" tidak pernah disorot`
                + ` (keadaan sasaran pada langkah itu: ${keadaan}; jejak: ${ringkas})`);
        }
    }
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

// ── F6-W1: pemandu ALIRAN untuk guide `screen` bersasar spesifik ───────────────────────
//
// `driveGenericSteps` memasuki SETIAP langkah melalui deep-link berasingan, jadi ia hanya
// sah apabila setiap sasaran wujud dalam keadaan LALAI halaman. Selepas W1 itu tidak lagi
// benar: sasaran hidup di dalam modal, pada halaman BUTIRAN sesuatu baris, atau pada
// langkah wizard yang belum dibuka. Registri (`resources/help/targets.json`, medan `state`
// — §7.2 langkah 4) ialah SUMBER KEBENARAN untuk prasyarat itu, jadi pemilihan pemandu
// dibuat daripada data, bukan senarai yang ditaip semula dalam spec ini.
const registryRaw = readFileSync('resources/help/targets.json', 'utf8');
const targetState = new Map(JSON.parse(registryRaw).targets.map((t) => [t.id, String(t.state ?? '-')]));
const stateOf = (target) => targetState.get(target) ?? '-';
const needsFlow = (guide) => guide.steps.some((s) => /^(detail:|modal:|wizard )/.test(stateOf(s.target)))
    || guide.steps.some((s) => stateOf(s.target).includes('modal:') || stateOf(s.target).includes('jadual tidak kosong'));
// Guide mempunyai langkah pada halaman BUTIRAN di mana-mana kedudukan.
const hasDetailStep = (guide) => guide.steps.some((s) => stateOf(s.target).startsWith('detail:'));
// Guide BERMULA pada halaman butiran. Ini soalan yang BERBEZA, dan membezakannya penting:
//
// F6-W1 hanya mempunyai guide `screen` yang keseluruhannya hidup pada satu halaman butiran,
// jadi "ada langkah butiran" dan "bermula pada butiran" sentiasa sama. F6-W2 memecahkan
// andaian itu: guide `workflow` bermula pada SENARAI ("Cari rekod", "Buka Lihat") dan hanya
// kemudian masuk ke butiran. Melancarkannya pada URL butiran menyebabkan runtime melihat
// `route` langkah 1 (senarai) ≠ laluan semasa lalu memanggil `location.assign` — halaman yang
// dialih itu DIUKUR tersangkut pada `readyState=loading` selama 90s tanpa runtime bantuan
// (dokumen ke-6, popover=false). Kawalan: `screen.mohon-pembetulan-rekod` yang menggunakan
// laluan kod SAMA lulus 20.1s, jadi ia bukan kekangan pelayan tempatan.
const startsOnDetail = (guide) => stateOf(guide.steps[0].target).startsWith('detail:');

/** Baca kedudukan langkah DAN elemen yang disorot dalam SATU penilaian (elak perlumbaan). */
function tourState(page) {
    return page.evaluate(() => {
        const popover = document.querySelector('.driver-popover');
        const aktif = [...document.querySelectorAll('.driver-active-element')];
        const teks = popover ? popover.innerText : '';

        return {
            n: Number((teks.match(/(\d+)\s+daripada\s+\d+/) || [null, 0])[1]) || null,
            // Driver.js boleh MENINGGALKAN kelas pada elemen langkah sebelumnya; kelas
            // yang paling akhir dalam susunan DOM bukan semestinya yang terbaharu, jadi
            // kumpulkan SEMUA dan biar pemanggil memeriksa keahlian.
            aktif: aktif.map((el) => el.getAttribute('data-help-target')),
            ralatPalsu: teks.includes('Tindakan belum tersedia'),
        };
    // Sama seperti `rekod()`: penilaian yang mendarat semasa navigasi melempar. Pemanggil
    // hanya perlu tahu "belum diketahui", bukan menerima pengecualian.
    }).catch(() => ({ n: null, aktif: [], ralatPalsu: false }));
}

/**
 * Cari halaman butiran yang benar-benar memaparkan sasaran langkah 1 guide ini.
 *
 * Baris pertama TIDAK semestinya betul: `keluarkan-fail-fizikal` hanya wujud pada fail
 * bermedium fizikal/hibrid. Mengimbas baris sehingga sasaran muncul menjadikan gate
 * deterministik tanpa mengunci ID baris benih.
 */
async function resolveDetailPath(page, guide) {
    const listPath = hydrate(guide.steps[0].route);
    await page.goto(listPath);
    const hrefs = await page.locator('.fi-ta-row a[href]').evaluateAll(
        (anchors) => [...new Set(anchors.map((a) => a.getAttribute('href')).filter(Boolean))],
    );
    // Sahkan SEMUA sasaran peringkat-halaman guide ini, bukan hanya langkah 1: sasaran
    // langkah 1 selalunya wujud pada setiap baris (cth. butang "Beri Akses" ada pada semua
    // fail), jadi memeriksanya sahaja memilih baris pertama yang salah — fail tanpa geran
    // akses, tanpa medium fizikal, dsb.
    const wajib = guide.steps
        .filter((s) => stateOf(s.target).startsWith('detail:') && !stateOf(s.target).includes('modal:'))
        .map((s) => s.target);
    for (const href of hrefs) {
        await page.goto(href);
        let semua = true;
        for (const t of wajib.length ? wajib : [guide.steps[0].target]) {
            // `isVisible()` ialah SNAPSHOT tanpa menunggu — relation manager & infolist
            // Filament dirender selepas muatan awal, jadi snapshot memberi negatif palsu.
            const ada = await page.locator(`[data-help-target="${t}"]`).first()
                .waitFor({ state: 'visible', timeout: 10_000 }).then(() => true).catch(() => false);
            if (!ada) { semua = false; break; }
        }
        if (semua) return new URL(href, 'http://x').pathname;
    }

    throw new Error(`${guide.guide_id}: tiada baris yang memaparkan sasaran ${guide.steps[0].target}`);
}

/**
 * Pilih pilihan PERTAMA dalam medan Select berbilang.
 *
 * ⚠️ DIUKUR, bukan diandaikan: Filament 4 TIDAK menggunakan Choices.js. Ia merender komponen
 * selectnya sendiri — `.fi-select-input-btn` (pencetus) + `<li class="fi-select-input-option">`
 * (pilihan). Percubaan pertama saya menyasarkan `.choices` dan gagal dengan "tidak dirender";
 * diagnostik yang mendedahkan struktur sebenar ditambah dalam commit yang sama supaya kesilapan
 * andaian yang sama tidak memakan pusingan lain.
 *
 * Klik KOORDINAT tidak boleh dipercayai di sini (overlay tour kekal semasa minimize), jadi
 * `dispatchEvent` digunakan — ia memadai untuk pencetus Alpine; hanya PENGHANTARAN BORANG yang
 * memerlukan event dipercayai (pelajaran F5).
 */
async function pilihPilihanPertama(page, targetId) {
    const pembalut = page.locator(`[data-help-target="${targetId}"]`).first();
    const butang = pembalut.locator('.fi-select-input-btn').first();
    const ada = await butang.waitFor({ state: 'visible', timeout: 15_000 }).then(() => true).catch(() => false);
    if (!ada) {
        const struktur = await pembalut.evaluate((el) => [...el.querySelectorAll('*')]
            .filter((e) => ['SELECT', 'INPUT', 'BUTTON', 'LI'].includes(e.tagName))
            .map((e) => `${e.tagName}.${String(e.className).split(' ').slice(0, 3).join('.')}`)
            .slice(0, 12).join('  ||  ')).catch(() => '(pembalut tidak dijumpai)');
        throw new Error(`${targetId}: pencetus select tidak dirender.
  DOM sebenar: ${struktur}`);
    }

    await butang.dispatchEvent('click');
    const pilihan = pembalut.locator('.fi-select-input-option').first();
    await expect(pilihan, `${targetId}: senarai pilihan kosong`).toBeVisible({ timeout: 10_000 });
    await pilihan.dispatchEvent('click');
    // Tutup dropdown supaya ia tidak menutupi medan seterusnya.
    await butang.dispatchEvent('click').catch(() => {});
}

/**
 * Isi medan wajib lalu hantar modal SEHINGGA ia benar-benar tertutup.
 *
 * Kenapa penghantaran SEBENAR diperlukan (dan bukan sekadar menekan CTA): apabila langkah
 * BERIKUT mengisytiharkan `route` lain, `stepAdvancePlan` memulangkan `action-then-navigate`
 * dan `watchForActionCompletion()` (help.js) hanya memanggil `gotoNextRoute()` selepas
 * elemen sasaran HILANG. Borang yang gagal pengesahan mengekalkan modal → tour tidak pernah
 * berpindah halaman → langkah seterusnya tidak pernah direkod. Jadi gate mesti melengkapkan
 * borang itu seperti pengguna sebenar.
 *
 * Klik diULANG selagi modal masih terbuka — pelajaran `submitUploadUntilToast`: morph
 * Livewire boleh menggantikan nod footer dan menelan satu klik tanpa sebarang kesan.
 */
async function isiDanHantarModal(page, submitTarget, isiBorang = null, kesan = null) {
    // Diskop kepada modal yang KELIHATAN: Filament mengekalkan nod modal terdahulu dalam DOM,
    // dan locator seluruh halaman boleh memadan butang Hantar BASI yang tidak lagi terikat
    // kepada komponen hidup (pelajaran `submitUploadUntilToast`).
    const modal = page.locator('.fi-modal-window:visible').last();
    const submit = modal.locator(`[data-help-target="${submitTarget}"]`).last();
    await expect(submit, `${submitTarget}: butang hantar tidak dirender`).toBeVisible({ timeout: 15_000 });
    if (isiBorang) await isiBorang();

    // Kaunter permintaan Livewire. "Klik hilang" dan "borang ditolak" menghasilkan gejala
    // yang SAMA (modal kekal terbuka) tetapi puncanya bertentangan — hanya bilangan
    // permintaan membezakannya. Inilah bukti yang meleraikan flake muat naik F5.
    let permintaan = 0;
    const kira = (req) => { if (req.url().includes('/livewire/')) permintaan += 1; };
    page.on('request', kira);

    const toast = kesan ? page.getByText(kesan).first() : null;
    const berkesan = () => (toast ? toast.isVisible().catch(() => false) : Promise.resolve(false));

    // ⚠️ DUA percubaan sahaja, dan setiap satu MENUNGGU KESAN. Versi pertama saya mencuba
    // EMPAT kali sambil hanya menunggu butang hilang: pada pelayan dev satu-benang itu
    // membanjiri baris gilir dengan POST `/livewire/update` yang belum dijawab dan
    // MENGHABISKAN keenam-enam sambungan Chrome — dokumen seterusnya kemudian tersangkut pada
    // `readyState=loading` dengan 17 permintaan tergantung (5 daripadanya `/livewire/update`).
    // Klik ulangan itu MENCIPTA kegagalan yang saya sangka ia sedang pulihkan.
    for (let cuba = 0; cuba < 2; cuba += 1) {
        if (await berkesan()) { page.off('request', kira); return; }
        if (!(await submit.isVisible().catch(() => false))) { page.off('request', kira); return; }
        await expect(submit).toBeEnabled({ timeout: 15_000 });
        await submit.scrollIntoViewIfNeeded().catch(() => {});
        // ⚠️ KLIK SEBENAR dahulu — hanya event DIPERCAYAI menjalankan penghantaran borang.
        // `dispatchEvent` mencetuskan pengendali Alpine tetapi BUKAN penghantaran; itulah
        // punca muktamad flake muat naik F5 (0 permintaan selama 121s). Lubang overlay tour
        // berada tepat di atas elemen yang DISOROT, dan pada langkah ini elemen itu ialah
        // butang Hantar — jadi klik koordinat memang mendarat padanya.
        try {
            await submit.click({ timeout: 10_000 });
        } catch {
            await submit.dispatchEvent('click').catch(() => {});
        }
        // Tunggu KESAN yang boleh diperhatikan (toast kejayaan) apabila pemanggil menyatakannya
        // — "butang hilang" boleh bermakna modal ditutup TANPA menghantar (pelajaran F5).
        const berjaya = toast
            ? await toast.waitFor({ state: 'visible', timeout: 30_000 }).then(() => true).catch(() => false)
            : await submit.waitFor({ state: 'hidden', timeout: 30_000 }).then(() => true).catch(() => false);
        if (berjaya) { page.off('request', kira); return; }
    }
    page.off('request', kira);

    // Jangan hanya lapor "masih terbuka": tanpa mesej pengesahan SEBENAR, punca "medan wajib
    // mana?" hanya boleh diteka. Filament merender ralat medan dalam `.fi-fo-field-wrp-error-message`.
    const ralat = await page.locator('.fi-fo-field-wrp-error-message, .fi-in-error, [data-validation-error]')
        .allInnerTexts().catch(() => []);
    const medan = await page.locator('.fi-modal-window label').allInnerTexts().catch(() => []);
    const nilai = await modal.locator('textarea, input:not([type="hidden"]), select')
        .evaluateAll((els) => els.map((el) => `${el.name || el.id || el.tagName}="${String(el.value).slice(0, 20)}"`))
        .catch(() => []);
    throw new Error(`${submitTarget}: modal masih terbuka selepas 4 percubaan hantar.`
        + `\n  permintaan livewire: ${permintaan}  (0 = klik HILANG · >0 = borang DITOLAK)`
        + `\n  nilai borang     : ${nilai.join(' · ') || '(tiada)'}`
        + `\n  ralat pengesahan : ${ralat.length ? ralat.join(' | ') : '(tiada mesej ralat dirender)'}`
        + `\n  medan dalam modal: ${medan.join(' · ') || '(tiada)'}`);
}

/**
 * Isi borang Mohon Pembetulan: sebab + SATU perubahan medan sebenar.
 *
 * ⚠️ Sebab sahaja TIDAK memadai. `RecordCorrectionService::request()` menolak permohonan yang
 * tidak mengubah satu pun medan (`ValidationException` berkunci `changes`) — betul dari segi
 * domain, dan ia juga tepat apa yang langkah 6 guide arahkan: "Ubah hanya medan yang salah".
 * Gate mesti melakukan perkara yang guide suruh, bukan menyingkat.
 */
async function isiPembetulan(page, sebab) {
    await fillStable(page.locator('[data-help-target="record-correction-reason"] textarea').first(), sebab);
    const tajuk = page.locator('[data-help-target="record-correction-title"] input').first();
    await fillStable(tajuk, `${(await tajuk.inputValue()).slice(0, 40)} (dibetulkan)`);
}

/**
 * Tindakan khusus per-langkah.
 *
 * Kosong selepas F6-W1; F6-W2 mengisinya kerana enam guide `workflow` menyeberang halaman
 * TEPAT selepas satu penghantaran borang (lihat `isiDanHantarModal`). Setiap entri di sini
 * melengkapkan borang yang langkah itu suruh pengguna lengkapkan — tiada pintasan, tiada
 * pembatalan modal untuk "menghilangkan" sasaran.
 */
const AKSI_LANGKAH = {
    'workflow.admin_masjid.betulkan-rekod-salah-tawan-tanpa-memadam-sejarah': {
        7: (page) => isiDanHantarModal(page, 'record-correction-submit', () => isiPembetulan(page,
            'Salah tawan tarikh rekod semasa klasifikasi awal.'), /Permohonan pembetulan dihantar/),
    },
    'workflow.admin_masjid.urus-fail-fizikal-atau-hibrid-dan-jejak-penjagaan': {
        // Langkah 8 = "Simpan sebelum fail diserahkan". Ia MESTI benar-benar disimpan: langkah
        // 9 menekan "Pindah Lokasi" pada bar tindakan halaman, dan Filament tidak akan melekap
        // aksi kedua semasa modal pertama masih terbuka. Diukur pada jejak gate: modal kekal
        // 1 dan `file-relocate-submit` tidak pernah muncul sepanjang 90s
        // (`9:file-relocate+file-identity` berulang). `notes` ialah medan WAJIB modal ini.
        8: (page) => isiDanHantarModal(page, 'file-checkout-submit', async () => {
            // Langkah 6 guide: "Pilih pemegang ahli atau isi nama luar" — dan perkhidmatan
            // MEMANG menolak tanpa pemegang. Gate melakukan apa yang guide suruh.
            await pilihPilihanPertama(page, 'file-checkout-holder');
            await fillStable(page.locator('[data-help-target="file-checkout-notes"] textarea').first(),
                'Diserahkan untuk semakan mesyuarat AJK.');
        }, /Pergerakan keluar direkodkan/),
    },
    'workflow.setiausaha.mohon-kelulusan-dan-pembetulan-rekod': {
        4: (page) => isiDanHantarModal(page, 'record-approval-submit', async () => {
            // `approver_id` = Select TUNGGAL bukan-searchable → `<select>` natif.
            const select = page.locator('[data-help-target="record-approval-approver"] select').first();
            const nilai = await select.locator('option').nth(1).getAttribute('value');
            await selectStable(select, nilai);
        }, /Permohonan kelulusan dihantar/),
        6: (page) => isiDanHantarModal(page, 'record-correction-submit', () => isiPembetulan(page,
            'Nama pengirim tersalah taip pada tawanan asal.'), /Permohonan pembetulan dihantar/),
    },
    'workflow.pengerusi.buat-keputusan-kelulusan-atau-pelupusan': {
        5: (page) => isiDanHantarModal(page, 'approval-submit', () => fillStable(
            page.locator('[data-help-target="approval-password"] input').first(), defaultPassword,
        ), /Keputusan kelulusan direkodkan/),
    },
    'workflow.bendahari.urus-rekod-kewangan-dan-minit': {
        7: (page) => isiDanHantarModal(page, 'record-minit-submit', async () => {
            await pilihPilihanPertama(page, 'record-minit-action');
            await fillStable(page.locator('[data-help-target="record-minit-body"] textarea').first(),
                'Sila semak dan sahkan butiran kewangan rekod ini.');
        }, /Minit diedarkan/),
    },
    'workflow.bendahari.mohon-storan-tambahan': {
        // `blocks` mempunyai `->default(1)` — tiada medan kosong untuk diisi.
        6: (page) => isiDanHantarModal(page, 'storage-submit', null, /Pesanan dijana/),
    },
    'workflow.nazir.proses-minit-dan-keputusan-kelulusan': {
        4: (page) => isiDanHantarModal(page, 'minit-reply-submit', async () => {
            await pilihPilihanPertama(page, 'minit-reply-action');
            await fillStable(page.locator('[data-help-target="minit-reply-body"] textarea').first(),
                'Balasan Nazir: tindakan susulan diambil.');
        }, /Balasan minit diedarkan/),
    },
};

/**
 * Wizard Filament memaparkan satu langkah sekali; majukan bila sasaran berikut tersembunyi.
 *
 * ⚠️ `filter({ hasText: <RegExp> })` menguji regex terhadap teks MENTAH elemen — whitespace
 * TIDAK dinormalisasi (berbeza daripada `hasText: <string>` dan daripada nama boleh-akses).
 * Butang Filament dirender Blade dengan baris baharu + indentasi di sekeliling labelnya, jadi
 * `/^Seterusnya$/` memberi **count=0** sedangkan butangnya jelas ada di skrin. Diukur pada DOM
 * sebenar halaman ini DAN pada DOM sintetik minimum:
 *   /^Seterusnya$/ → 0 · /Seterusnya/ → 1 · /^\s*Seterusnya\s*$/ → 1 · 'Seterusnya' → 1 ·
 *   getByRole(exact) → 1.
 * Nama boleh-akses dinormalisasi, jadi `getByRole(..., { exact: true })` memberi ketepatan
 * yang sauh regex itu SANGKA ia beri. Skop `.fi-modal-window` kekal penting: CTA popover tour
 * juga berlabel "Seterusnya" (dan popover itu `role="dialog"` — jangan sasarkannya dgn peranan).
 */
async function advanceWizard(page) {
    const next = page.locator('.fi-modal-window')
        .getByRole('button', { name: 'Seterusnya', exact: true }).first();
    if (!(await next.isVisible().catch(() => false))) return false;

    // ⚠️ Perlumbaan check-then-act: butang boleh TERTANGGAL antara semakan dan tindakan
    // (wizard maju sendiri, atau modal dirender semula oleh morph). `dispatchEvent` tiada
    // tempoh sendiri → mewarisi actionTimeout 30s → MELEMPAR pada laluan yang sebenarnya
    // berjaya. Corak yang sama pernah menjatuhkan pemulihan banner; ia menyala di sini
    // sebaik pembaikan produk memajukan tour lebih awal. Pulangkan sama ada klik BENAR-BENAR
    // dihantar supaya pemanggil tidak tersilap menganggap wizard sudah dimajukan.
    return next.dispatchEvent('click', { timeout: 3_000 }).then(() => true).catch(() => false);
}

/**
 * Cetak jejak perekam bagi larian yang LULUS (env `DIWAN_DUMP_TRAIL=1`).
 *
 * Tanpa ini, jejak hanya kelihatan apabila ujian GAGAL — jadi mustahil membandingkan larian
 * CI yang merah dengan larian tempatan yang hijau, dan perbandingan itulah yang menunjukkan
 * di mana kedua-duanya bercapah. Diagnostik mesti tersedia pada kedua-dua belah.
 */
async function dumpTrail(page, guideId) {
    if (!process.env.DIWAN_DUMP_TRAIL) return;
    const log = await page.evaluate(() => window.__diwanTourLog ?? []);
    console.log(`\n[JEJAK] ${guideId} (${log.length} entri)`);
    for (const e of log) {
        const sasaran = Object.entries(e.sasaran ?? {}).map(([k, v]) => `${k}=${v}`).join(' ');
        console.log(`  n=${e.n ?? '-'} aktif=[${(e.aktif ?? []).filter(Boolean).join('+') || '-'}]`
            + ` banner=${e.banner ? 'Y' : 'n'} modal=${e.modal} | ${sasaran}`);
    }
}

/**
 * Pandu guide sebagai ALIRAN sebenar: deep-link langkah 1, kemudian ikut tour seperti
 * pengguna — tekan CTA, dan apabila CTA menjanjikan tindakan, lakukan tindakan itu pada
 * elemen YANG DISOROT. Elemen bukan-butang (pembalut medan) tidak diklik: pengguna MENAIP
 * di situ, dan tour maju sendiri sebaik sasaran berikut kelihatan.
 */
async function driveFlowGuide(page, guide, basePath, detailPath = null) {
    const total = guide.steps.length;
    const popover = page.locator('.driver-popover');
    await page.goto(`${basePath}?panduan=${guide.guide_id}&langkah=0`);

    const runtime = page.locator('[data-diwan-help-runtime]');
    await expect(runtime, `${guide.guide_id}: deep-link mesti memilih guide yang diminta`)
        .toHaveAttribute('data-guide-id', guide.guide_id);

    for (let i = 1; i <= total; i += 1) {
        const step = guide.steps[i - 1];
        // G3 diassert terhadap URUTAN YANG DIREKOD, bukan keadaan seketika. Driver.js mengemas
        // popover sebelum memindahkan `.driver-active-element` (animasi), dan sync F2 boleh
        // memajukan tour melintasi satu langkah dalam beberapa milisaat — pengundi seketika
        // terlepas pandang, lalu menunggu 90s untuk nombor yang tidak akan kembali.
        // F6-W5 — langkah GENERIK tidak boleh dituntut menyorot sasarannya.
        //
        // `page-content` / `page-primary` ialah sasaran generik: `page-primary` tiada elemen
        // langsung (hanya `main` → `page-content` ditandakan), jadi runtime dengan BETUL
        // menyorot fallback. `assertStepPopover` sudah lama menghadkan G2 kepada langkah
        // `specific` atas sebab yang sama; `driveFlowGuide` tidak, kerana sehingga W5 tiada
        // guide berlangkah-generik pernah mengambil laluan ALIRAN — pemandu dipilih daripada
        // `state` registri, dan hanya guide bersasar penuh yang layak.
        //
        // W5 memecahkan andaian itu: `tenant.pembetulan-rekod` mendapat dua sasaran BARIS
        // (langkah 4-5), jadi `needsFlow()` kini benar, sedangkan langkah 1-3 kekal generik
        // dengan justifikasi bertulis. Diukur di gate: `jejak 1:page-content → 2:page-content`
        // sedangkan langkah 2 mengisytihar `page-primary` — produk betul, pengamat terlalu
        // ketat. Kedua-dua pemandu kini menguatkuasakan peraturan yang SAMA.
        const perluSasaran = step.status === 'specific';
        const rekod = () => page.evaluate(([idx, sasaran, wajib]) => {
            const log = window.__diwanTourLog ?? [];

            return {
                jumpa: log.some((e) => e.n === idx && (!wajib || e.aktif.includes(sasaran)) && !e.ralatPalsu),
                ralatPalsu: log.some((e) => e.ralatPalsu),
                // Perekam yang tidak terpasang mesti kelihatan dalam mesej kegagalan —
                // "log kosong" dan "langkah tidak berlaku" tidak boleh kelihatan sama.
                sedia: window.__diwanTourLogSedia === true,
                bilangan: log.length,
                // F6-W2: "perekam tiada" dan "tour di halaman lain" menghasilkan jejak yang
                // kelihatan SAMA (kedua-duanya kosong). URL + readyState membezakannya
                // dalam satu baris, tanpa perlu membuka trace.
                url: location.pathname + location.search,
                dokumen: sessionStorage.getItem('__diwanNav') ?? '?',
                sedia_dom: document.readyState,
                popover: Boolean(document.querySelector('.driver-popover')),
                guideAktif: document.querySelector('[data-diwan-help-runtime]')?.dataset.guideId ?? '(tiada runtime)',
                jejak: log.filter((e) => e.n !== null)
                    .map((e) => `${e.n}:${e.aktif.filter(Boolean).join('+') || '-'}`).slice(-14).join(' → '),
                // Diagnostik penentu: keadaan sasaran yang DIJANGKA sepanjang jejak. Kalau ia
                // `tiada` di setiap entri, produk/data tidak pernah merendernya; kalau `ada`
                // sedangkan sorotan lain, resolusi tour itu yang gagal. Kedua-duanya kelihatan
                // sama tanpa baris ini.
                sasaranDijangka: log.slice(-14).map((e) => `${e.n ?? '-'}:${e.sasaran?.[sasaran] ?? '?'}`).join(' → '),
                // Banner = CTA benar-benar diklik (tour diminimize). Modal = tindakan berkuat
                // kuasa. W = langkah wizard Filament aktif (membezakan "sasaran hilang"
                // daripada "wizard sudah melangkaui langkah itu").
                bannerModal: log.slice(-14)
                    .map((e) => `${e.banner ? 'B' : '-'}${e.modal ?? '?'}W${e.wizard ?? '-'}`).join(' '),
            };
        }, [i, step.target, perluSasaran])
            // ⚠️ `expect.poll` TIDAK mencuba semula apabila callbacknya MELEMPAR — ia gagal
            // serta-merta. `page.evaluate` melempar "Execution context was destroyed" apabila
            // ia dinilai TEPAT semasa navigasi, dan F6-W2 memperkenalkan navigasi yang
            // dimulakan RUNTIME di tengah guide (langkah silang-route selepas penghantaran
            // borang). Hasilnya: kegagalan **8 saat** yang menyamar sebagai tamat masa 90s.
            // Diukur di CI (run 30972196342): 13/14 guide lulus, satu gagal dalam 8s dengan
            // `readyState=loading, rangkaian: tiada` — potret halaman yang sedang menavigasi,
            // bukan halaman yang tersangkut. Kembalikan bentuk "belum sedia" supaya pengundi
            // MENCUBA SEMULA merentas navigasi, seperti yang sepatutnya.
            .catch(() => ({
                jumpa: false, ralatPalsu: false, sedia: false, bilangan: 0,
                url: '(sedang menavigasi)', dokumen: '?', sedia_dom: 'navigating',
                popover: false, guideAktif: '(sedang menavigasi)',
                jejak: '', sasaranDijangka: '', bannerModal: '',
            }));

        // Pemulihan dokumen TERGANTUNG (sekali per langkah).
        //
        // Navigasi yang dimulakan runtime (`location.assign` bagi langkah silang-route)
        // kadangkala meninggalkan dokumen pada `readyState=loading` dengan belasan aset yang
        // tidak pernah selesai. DIUKUR bahawa ini BUKAN kecacatan produk dan bukan pelayan
        // tersekat: sepanjang 140s gantung, `curl /up` dijawab 200 dalam ~0.6s setiap 10s.
        // Ia kehabisan sambungan pelanggan pada pelayan dev PHP yang tidak boleh fork
        // (`forking is not supported on this platform` — Windows; CI Linux guna
        // PHP_CLI_SERVER_WORKERS=4). Muat semula membuka sambungan baharu pada URL yang SAMA
        // (`?panduan=…&langkah=…` kekal), jadi tour disambung pada langkah yang sama dan
        // assertion di bawah tetap penuh — pengamatan dilaras, interaksi TIDAK.
        let dipulihkan = false;
        await expect
            .poll(async () => {
                const k = await rekod();
                if (k.jumpa) return true;
                if (!dipulihkan && !k.sedia && k.sedia_dom === 'loading') {
                    dipulihkan = true;
                    await page.reload({ waitUntil: 'domcontentloaded' }).catch(() => {});
                }

                return false;
            }, {
                timeout: 90_000,
                message: `${step.key}: tour tidak pernah merekod langkah ${i}`
                    + (perluSasaran ? ` dengan sasaran ${step.target}` : ' (langkah generik — kedudukan sahaja)'),
            })
            .toBe(true)
            .catch(async () => {
                const k = await rekod();
                throw new Error(`${step.key}: tour tidak pernah merekod langkah ${i}`
                    + (perluSasaran ? ` dengan sasaran ${step.target}` : ' (langkah generik — kedudukan sahaja)')
                    + ` — perekam sedia=${k.sedia}, entri=${k.bilangan}`
                    + `\n  halaman         : ${k.url} (dokumen ke-${k.dokumen}, readyState=${k.sedia_dom}, popover=${k.popover}, guide=${k.guideAktif})`
                    + `\n  rangkaian       : ${permintaanTergantung(page)}`
                    + `\n  jejak sorotan   : ${k.jejak || '(kosong)'}`
                    + `\n  sasaran dijangka: ${k.sasaranDijangka || '(kosong)'}`
                    + `\n  banner/modal    : ${k.bannerModal || '(kosong)'}`);
            });
        const keadaan = await rekod();
        expect(keadaan.ralatPalsu, `${step.key}: popover memaparkan ralat palsu "Tindakan belum tersedia" (jejak: ${keadaan.jejak})`)
            .toBe(false);

        // Jika tour SUDAH melintasi langkah ini, jangan tekan CTAnya — CTA itu kini milik
        // langkah lain dan menekannya akan memaju tour dua kali.
        const semasa = (await tourState(page)).n;
        if (semasa !== null && semasa > i) continue;

        const cta = popover.locator('.driver-popover-next-btn');
        await expect(cta, `${step.key}: CTA tiada`).toBeVisible();
        const label = (await cta.innerText()).trim();
        // Sasaran di bawah lipatan dalam modal boleh menolak popover (position: fixed) —
        // dan CTAnya — ke luar viewport. Itu kecacatan produk yang DIREKOD dalam help.js
        // (dibawa ke F7); di sini gate menggulung supaya ia menguji TOUR, bukan tersekat
        // pada kecacatan itu. Klik CTA guna dispatchEvent sebagai sandaran kerana butang
        // di luar viewport ditolak oleh klik sebenar Playwright.
        await page.locator(`[data-help-target="${step.target}"]`).first()
            .evaluate((el) => el.scrollIntoView({ block: 'center', inline: 'nearest' })).catch(() => {});
        // Sandaran `dispatchEvent` mesti BERTEMPOH: tanpa `timeout` ia mewarisi actionTimeout
        // 30s dan MELEMPAR apabila CTA lenyap kerana tour maju sendiri (kejayaan, bukan ralat).
        await cta.click({ timeout: 10_000 })
            .catch(() => cta.dispatchEvent('click', { timeout: 3_000 }))
            .catch(() => {});

        if (i === total) {
            await expect(popover, `${step.key}: langkah akhir tidak menutup/meminimize popover`).toBeHidden();
            break;
        }

        // Peralihan SENARAI → BUTIRAN. Runtime tidak boleh melakukannya sendiri: URL butiran
        // bersifat dinamik (`/records/{id}`), jadi katalog tidak boleh mengisytiharkannya
        // sebagai `route` langkah dan `gotoNextRoute()` tiada apa-apa untuk dituju. Pengguna
        // sebenar menekan baris; gate melakukan perkara setara secara deterministik melalui
        // deep-link ke langkah yang sama. Ini menguji SASARAN langkah butiran, dan tidak
        // berpura-pura menguji kesinambungan tour merentas navigasi pengguna — jurang produk
        // itu direkod dalam laporan fasa, bukan disembunyikan di sini.
        const seterusnyaAwal = guide.steps[i];
        if (detailPath && seterusnyaAwal && stateOf(seterusnyaAwal.target).startsWith('detail:')
            && new URL(page.url()).pathname !== detailPath) {
            await page.goto(`${detailPath}?panduan=${guide.guide_id}&langkah=${seterusnyaAwal.index - 1}`);
            continue;
        }

        const khusus = AKSI_LANGKAH[guide.guide_id]?.[i];
        // Tindakan langkah ini disimpan supaya ia boleh DIULANG. Bukti CI (run 30906909355,
        // `serve-ci.log`): selepas `/app/mam/tetapan-masjid` dimuat dan dua `/livewire/update`
        // selesai, klik tindakan menghasilkan **SIFAR permintaan selama 94 saat** — modal tidak
        // pernah terbuka dan tour kekal di langkah 1. Tandatangan yang sama seperti flake muat
        // naik F5. Runner CI lebih perlahan daripada mesin dev, jadi klik boleh mendahului
        // pemasangan pendengar (Filament memuat JS komponen secara lazy).
        let ulangTindakan = null;
        let majuAwal = false;
        if (khusus) {
            await khusus(page);
        } else if (label === 'Buat pada skrin') {
            const sasaran = page.locator(`[data-help-target="${step.target}"]`).first();
            const tag = await sasaran.evaluate((el) => el.tagName).catch(() => null);
            if (tag === 'BUTTON' || tag === 'A') {
                // `dispatchEvent`, BUKAN klik sebenar. Diuji dan DITOLAK: menukar ini kepada
                // `sasaran.click()` memerahkan TIGA guide yang sebelumnya hijau
                // (`jemput-ahli`, `sedia-senarai-pelupusan`, `tetapkan-kata-laluan`), setiap
                // satu tamat masa pada ~1.7m. Sebabnya `help.js:663-664` menetapkan
                // `overlayClickBehavior: 'close'`, jadi klik berasaskan KOORDINAT yang
                // mendarat pada overlay tour MENUTUP tour dan bukan menekan butang — pelajaran
                // F0 yang sudah direkod ("overlay menyerap klik koordinat"). Pembaikan yang
                // BETUL untuk klik yang hilang di CI ialah MENGULANG event ini sehingga ada
                // kesan, bukan menukar jenis event.
                ulangTindakan = () => sasaran.dispatchEvent('click', { timeout: 5_000 }).catch(() => {});
                await ulangTindakan();
            } else {
                // Sasaran bukan butang (cth. pembalut medan Radio) → tindakan langkah ini
                // ialah memajukan wizard. Ini DIKIRA sebagai kemajuan wizard bagi peralihan
                // ini; jika tidak, gelung di bawah akan memajukannya SEKALI LAGI.
                majuAwal = await advanceWizard(page);
            }
        }

        // Sasaran berikut boleh berada pada langkah WIZARD seterusnya. Modal mengambil masa
        // untuk terbuka, jadi ini mesti GELUNG yang menunggu — semakan sekali sahaja
        // berlaku sebelum modal wujud dan tidak pernah menemui butang wizard.
        const seterusnya = guide.steps[i];
        const sasaranSeterusnya = page.locator(`[data-help-target="${seterusnya.target}"]`).first();
        // Pulih-sendiri hanya bila registri MENGISYTIHARKAN modal dijangka terbuka. Mengikat
        // ulangan kepada `state` yang diisytihar (bukan tekaan) menghalang ulangan tindakan
        // yang bersifat TOGGLE daripada membatalkan kesannya sendiri.
        const jangkaModal = stateOf(seterusnya.target).includes('modal:');
        const modalTerbuka = () => page.locator('.fi-modal-window').first().isVisible().catch(() => false);
        // ⚠️ SATU langkah guide = paling banyak SATU kemajuan wizard. Mengklik "Seterusnya"
        // dua kali untuk satu peralihan menjadikan wizard MELANGKAUI langkah yang guide ini
        // sasarkan; sasaran itu hilang selama-lamanya dan Driver.js menyorot fallback
        // `page-content` — diukur di CI (`n` betul 4, `sasaranAktif` false) DAN dalam trace
        // tempatan (dua `dispatchEvent` pada "Seterusnya" dalam tetingkap 100ms yang sama).
        // Selepas satu kemajuan, gelung hanya MENUNGGU; ia tidak memaju lagi. Itu menjadikan
        // gelung ini selamat pada runner perlahan tanpa mengubah masa yang terbukti hijau.
        // ⚠️ Bermula `true` jika cabang tindakan SUDAH memajukan wizard. Inilah punca tepat
        // kegagalan CI `persediaan-berpandu`: pada mesin pantas gelung nampak sasaran sudah
        // muncul lalu berhenti, tetapi pada runner CI yang perlahan ia memeriksa SEBELUM wizard
        // siap dirender dan memaju SEKALI LAGI — wizard melangkaui langkah yang guide sasarkan
        // dan sasaran itu hilang selama-lamanya (jejak: `3:ada → 3:sembunyi … 4:sembunyi`).
        let sudahMaju = majuAwal;
        for (let cuba = 0; cuba < 12; cuba += 1) {
            if (await sasaranSeterusnya.isVisible().catch(() => false)) break;
            if (!sudahMaju && await advanceWizard(page)) {
                sudahMaju = true;
                await page.waitForTimeout(1500);
                continue;
            }
            // Pulih-sendiri untuk klik yang HILANG (CI: sifar permintaan selama 94 saat).
            // ⚠️ Mesti SABAR: mengulang pencetus Filament terlalu awal akan mount aksi itu
            // dua kali atau menutup modal yang sedang dibuka — diukur, ia memerahkan
            // `edit-tetapan-masjid` yang sebelumnya hijau. Beri modal ~4 saat untuk muncul
            // dahulu, kemudian cuba semula paling banyak dua kali dalam bajet 12 lelaran.
            if (ulangTindakan && jangkaModal && cuba >= 4 && cuba % 4 === 0 && !(await modalTerbuka())) {
                await ulangTindakan();
            }
            await page.waitForTimeout(1000);
        }

        // Laluan pengguna sebenar: bila panduan diminimize, tekan "Tunjuk arahan" supaya
        // popover pulih. Tanpa ini keadaan tour dibaca daripada popover yang tersembunyi.
        //
        // ⚠️ Banner yang LENYAP di tengah-tengah ialah KEJAYAAN, bukan kegagalan: ia bermakna
        // sasaran langkah berikut muncul dan tour maju sendiri (mekanisme sync F2), yang
        // memanggil `clearWaitingBanner()`. Ini perlumbaan check-then-act — `isVisible()`
        // hanyalah snapshot — jadi setiap percubaan mesti boleh gagal dengan senyap:
        //   1. `click()` tamat masa apabila elemen tertanggal semasa menunggu;
        //   2. `dispatchEvent` LALAI menunggu 30s untuk elemen yang tidak akan kembali, lalu
        //      MELEMPAR — itulah yang menjatuhkan guide yang laluan penggunanya betul-betul
        //      berjaya (diukur: klik gagal pada 5.1s, dispatchEvent melempar 30s kemudian).
        const tunjuk = page.locator('[data-diwan-tour-waiting] button');
        if (await tunjuk.isVisible().catch(() => false)) {
            await tunjuk.click({ timeout: 3_000 })
                .catch(() => tunjuk.dispatchEvent('click', { timeout: 2_000 }))
                .catch(() => {});
            await page.waitForTimeout(500);
        }
    }
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
async function submitUploadUntilToast(page, modal, reattach) {
    // ⚠️ MESTI diskop kepada modal yang DIBERI, bukan `page.locator(...)` seluruh halaman.
    // Guide `workflow.*` membuka DUA modal berturutan (muat naik, kemudian klasifikasi) dan
    // Filament mengekalkan nod modal terdahulu dalam DOM. Locator seluruh halaman boleh
    // memadan butang Hantar BASI daripada render terdahulu: ia wujud dan "enabled", tetapi
    // tiada lagi terikat kepada komponen hidup — `dispatchEvent` padanya menghasilkan SIFAR
    // permintaan, selama-lamanya. Dibuktikan: shard `workflow` LULUS dua larian CI dengan
    // locator berskop-dialog yang asal, lalu GAGAL sebaik saya menjadikannya seluruh halaman
    // (serve-ci.log: 0 permintaan sepanjang 120s retry).
    const submit = modal.locator('[data-help-target="inbox-upload-submit"]').last();
    const trigger = page.locator('[data-help-target="inbox-upload"]');
    const toast = page.getByText(/\d+ dokumen dimuat naik ke Peti Masuk/);

    await expect(async () => {
        if (await toast.isVisible().catch(() => false)) return;

        // Modal HILANG tanpa toast = klik menutupnya tanpa menghantar borang. Dibuktikan
        // daripada trace CI: `dispatchEvent` berjalan SEKALI, modal lenyap, dan serve-ci.log
        // menunjukkan 0 permintaan selama 121s. Event TAK-DIPERCAYAI mencetuskan pengendali
        // tutup Alpine tetapi TIDAK mencetuskan penghantaran borang. Pulih: buka semula,
        // lampir semula, cuba lagi — persis apa yang pengguna sebenar akan buat.
        if (!(await modal.isVisible().catch(() => false))) {
            await expect(trigger).toBeEnabled({ timeout: 15_000 });
            // ⚠️ Overlay tour MEMINTAS klik koordinat di sini. Lubang overlay berada di atas
            // elemen yang DISOROT, jadi sebaik tour maju ke langkah lain, butang ini berada di
            // bawah bahagian pepejal overlay. Dinamakan secara literal dalam log CI:
            //   `<svg class="driver-overlay …"> subtree intercepts pointer events`
            // (klik gagal selepas 15s, guide muat-naik `workflow.*`). Membuka modal ialah
            // tindakan `wire:click`, BUKAN penghantaran borang, jadi `dispatchEvent` memadai
            // sebagai sandaran — corak yang sama seperti penghantaran di bawah, dan sebab
            // mengapa repo ini menggunakan `dispatchEvent` untuk pencetus yang disekat overlay.
            try {
                await trigger.click({ timeout: 5_000 });
            } catch {
                await trigger.dispatchEvent('click', { timeout: 3_000 }).catch(() => {});
            }
            await expect(modal).toBeVisible({ timeout: 15_000 });
            await reattach();
        }

        await expect(submit).toBeEnabled({ timeout: 15_000 });
        // KLIK SEBENAR dahulu: hanya event DIPERCAYAI yang menjalankan tingkah laku lalai
        // penghantaran borang. `dispatchEvent` dikekalkan sebagai sandaran untuk kes overlay
        // memintas — dan jika ia yang berjalan, gelung ini akan mencuba semula, jadi
        // kehilangan senyap tidak lagi menjadi kegagalan 120 saat tanpa maklumat.
        try {
            await submit.click({ timeout: 10_000 });
        } catch {
            await submit.dispatchEvent('click');
        }

        await expect(toast).toBeVisible({ timeout: 15_000 });
    }).toPass({ timeout: 150_000 });
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
async function cycleGuide(page, guide, basePath = null) {
    const first = guide.steps[0];
    const url = `${basePath ?? hydrate(first.route)}?panduan=${guide.guide_id}&langkah=0`;
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
        const { context, page } = await newContextPage(browser, baseURL, [
            ...new Set(guide.steps.map((s) => s.target)),
        ]);
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
                const lampirFail = () => attachFile(modalMuatNaik, {
                    name: `Dokumen skrin ${Date.now()}.txt`,
                    mimeType: 'text/plain',
                    buffer: Buffer.from(`Dokumen gate skrin ${Date.now()}.`),
                });
                const hantar = async (cta) => {
                    await cta();
                    await submitUploadUntilToast(page, modalMuatNaik, lampirFail);
                };

                await driveChoreographedRange(
                    popover,
                    { 1: bukaModal, 2: pilihFail, 3: hantar },
                    guide.steps.length,
                    guide.guide_id,
                );
                // F6-W3: langkah 4 ("Sahkan toast dan baris baharu") kini menyasar
                // `inbox-record` — sel tajuk baris pertama, iaitu dokumen yang baru dimuat
                // naik. Tanpa assertion ini, `specific` bagi guide berkoreografi tidak
                // pernah diuji (rujuk assertTrailTargets).
                await assertTrailTargets(page, guide);
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
                // F6-W4: sempadan julat DIKIRA daripada halaman koreografi, bukan daripada
                // `status === 'specific'` (lihat AKSI_KOREOGRAFI). Julat = blok langkah pada
                // `/peti-masuk` yang bermula pada aksi pertama. Formula ini menghasilkan tepat
                // julat yang dahulunya hijau (muat-naik 5–14, setiausaha 4–9), jadi ia
                // MEMULIHKAN koreografi dan bukan mengubahnya.
                const laluanKoreografi = `/app/${tenantSlug}/peti-masuk`;
                // ⚠️ Dalam MANIFEST, langkah tanpa `route` sendiri diisi dengan route GUIDE —
                // ia tidak pernah `null` di sini, tidak seperti dalam `guides.json` mentah.
                // Versi pertama menyemak `r === null` dan gelung berhenti pada langkah pertama
                // (tamat=5), lalu menghantar langkah 6 ke `driveGenericSteps` → "kedudukan
                // langkah salah" di CI. Kerana gelung bermula pada `mulaKoreografi`, memadankan
                // route GUIDE adalah selamat: langkah papan pemuka (1–2) yang berkongsi route
                // itu berada SEBELUM julat dan sudah dikecualikan.
                const laluanGuide = hydrate(guide.route);
                const padaHalamanKoreografi = (s) => {
                    const r = s.route ? hydrate(s.route) : laluanGuide;

                    return r === laluanKoreografi || r === laluanGuide;
                };
                const mulaKoreografi = Math.min(...AKSI_KOREOGRAFI[guide.guide_id]);
                let tamatKoreografi = mulaKoreografi;
                // `guide.steps` 0-asas manakala `.index` 1-asas → steps[n] ialah langkah n+1.
                while (guide.steps[tamatKoreografi] && padaHalamanKoreografi(guide.steps[tamatKoreografi])) {
                    tamatKoreografi += 1;
                }
                await driveGenericSteps(page, guide, guide.steps.filter((s) => s.index < mulaKoreografi));

                await page.goto(`/app/${tenantSlug}/peti-masuk`);
                await ensureInboxFixture(page);
                await page.goto(`/app/${tenantSlug}/peti-masuk?panduan=${guide.guide_id}&langkah=${mulaKoreografi - 1}`);
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
                    await submitUploadUntilToast(page, dialog, () => attachFile(dialog, {
                        name: `Dokumen workflow ${Date.now()}.txt`,
                        mimeType: 'text/plain',
                        buffer: Buffer.from(`Dokumen workflow gate ${Date.now()}.`),
                    }));
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

                // Penjaga hanyut: AKSI_KOREOGRAFI mesti sepadan peta `actions` sebenar,
                // kalau tidak sempadan julat dikira daripada senarai yang lapuk.
                expect(Object.keys(actions).map(Number).sort((a, b) => a - b),
                    `${guide.guide_id}: AKSI_KOREOGRAFI tidak sepadan kunci peta actions`)
                    .toEqual(AKSI_KOREOGRAFI[guide.guide_id]);

                await driveChoreographedRange(popover, actions, tamatKoreografi, guide.guide_id);
                // F6-W3: G2 untuk laluan berkoreografi (rujuk assertTrailTargets). Hanya
                // sehingga hujung julat koreografi — langkah selepasnya dipandu
                // `driveGenericSteps`, yang sudah memanggil `assertStepPopover` sendiri.
                await assertTrailTargets(page, guide, tamatKoreografi, mulaKoreografi);
                await popover.getByRole('button', { name: 'Tutup panduan' }).click();
                await modal.getByRole('button', { name: 'Tutup' }).click().catch(() => {});
                await expect(popover).toBeHidden();

                await driveGenericSteps(page, guide, guide.steps.filter((s) => s.index > tamatKoreografi));
            } else if (needsFlow(guide)) {
                // F6-W1: sasaran hidup dalam modal / halaman butiran / langkah wizard —
                // deep-link per langkah tidak lagi sah (rujuk nota driveFlowGuide).
                const detailPath = hasDetailStep(guide) ? await resolveDetailPath(page, guide) : null;
                const basePath = (detailPath && startsOnDetail(guide))
                    ? detailPath
                    : hydrate(guide.steps[0].route);
                await driveFlowGuide(page, guide, basePath, detailPath);
                await dumpTrail(page, guide.guide_id);
                await cycleGuide(page, guide, basePath);
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
