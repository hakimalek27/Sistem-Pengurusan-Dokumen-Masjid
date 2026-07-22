import { chromium } from '@playwright/test';
import { randomBytes } from 'node:crypto';
import { mkdir, readFile, stat, writeFile } from 'node:fs/promises';
import path from 'node:path';

const baseURL = process.env.MANUAL_BASE_URL ?? 'http://127.0.0.1:8094';
const outputRoot = path.resolve(process.cwd(), 'Manual Penguna');
const password = process.env.MANUAL_DEMO_PASSWORD;

if (!password) {
    throw new Error('MANUAL_DEMO_PASSWORD is required for the isolated manual-capture accounts.');
}

const roles = [
    { key: 'admin_masjid', label: 'Admin / Kerani', folder: '01-Admin-Kerani', email: 'admin_masjid@demo.test', pages: 22 },
    { key: 'pengerusi', label: 'Pengerusi', folder: '02-Pengerusi', email: 'pengerusi@demo.test', pages: 16 },
    { key: 'setiausaha', label: 'Setiausaha', folder: '03-Setiausaha', email: 'setiausaha@demo.test', pages: 14 },
    { key: 'bendahari', label: 'Bendahari', folder: '04-Bendahari', email: 'bendahari@demo.test', pages: 14 },
    { key: 'nazir', label: 'Nazir', folder: '05-Nazir', email: 'nazir@demo.test', pages: 12 },
    { key: 'ketua_imam', label: 'Ketua Imam', folder: '06-Ketua-Imam', email: 'ketua_imam@demo.test', pages: 12 },
    { key: 'ajk', label: 'AJK', folder: '07-AJK', email: 'ajk@demo.test', pages: 12 },
    { key: 'audit', label: 'Juruaudit', folder: '08-Juruaudit', email: 'audit@demo.test', pages: 13 },
];

const manifestPath = path.join(outputRoot, 'manifest-tangkapan.json');
const requestedRoleKey = process.env.MANUAL_ROLE;
const selectedRoles = requestedRoleKey
    ? roles.filter((role) => role.key === requestedRoleKey)
    : roles;

if (requestedRoleKey && selectedRoles.length === 0) {
    throw new Error(`Unknown MANUAL_ROLE: ${requestedRoleKey}`);
}

let manifest = { generatedAt: new Date().toISOString(), baseURL, roles: {}, public: {}, errors: [] };
if (process.env.MANUAL_RESUME === '1') {
    const previous = await readFile(manifestPath, 'utf8').then(JSON.parse).catch(() => null);
    if (previous) {
        manifest = {
            ...previous,
            generatedAt: new Date().toISOString(),
            baseURL,
            errors: (previous.errors ?? []).filter((message) => !selectedRoles.some((role) => message.startsWith(`${role.label}:`))),
        };
    }
}

function fileSlug(value) {
    return value
        .replace(/^https?:\/\/[^/]+/i, '')
        .replace(/^\/app\/mam\/?/, '')
        .replace(/^\/+|\/+$/g, '')
        .replace(/[^a-z0-9]+/gi, '-')
        .replace(/^-|-$/g, '') || 'dashboard';
}

function letterCode(seed) {
    let value = seed;
    let code = '';
    for (let index = 0; index < 4; index += 1) {
        code += String.fromCharCode(65 + (value % 26));
        value = Math.floor(value / 26);
    }
    return code;
}

async function ensureFolders() {
    await mkdir(outputRoot, { recursive: true });
    for (const role of roles) {
        await mkdir(path.join(outputRoot, role.folder, 'imej'), { recursive: true });
    }
    await mkdir(path.join(outputRoot, '09-Orang-Awam-Pendaftaran', 'imej'), { recursive: true });
}

async function addOverlay(page, title, requested = []) {
    const existingOverlay = page.locator('#manual-capture-overlay');
    if (await existingOverlay.count()) {
        await existingOverlay.evaluate((node) => node.remove());
    }
    const resolved = [];

    for (const item of requested) {
        const locator = item.locator(page).filter({ visible: true }).first();
        if (await locator.count() === 0) continue;
        const box = await locator.boundingBox({ timeout: 750 }).catch(() => null);
        if (!box) continue;
        resolved.push({ ...item, x: box.x, y: box.y, width: box.width, height: box.height });
    }

    const serializable = resolved.map(({ note, x, y, width, height }) => ({ note, x, y, width, height }));

    await page.evaluate(({ titleText, items }) => {
        const root = document.createElement('div');
        root.id = 'manual-capture-overlay';
        root.style.cssText = 'position:absolute;inset:0;z-index:2147483647;pointer-events:none;font-family:Arial,sans-serif;';

        const banner = document.createElement('div');
        banner.textContent = `PANDUAN BERGAMBAR: ${titleText}`;
        banner.style.cssText = 'position:fixed;left:50%;top:10px;transform:translateX(-50%);padding:9px 16px;border:2px solid #fff;border-radius:6px;background:#12372a;color:#fff;font-size:15px;font-weight:700;box-shadow:0 3px 12px #0005;';
        root.appendChild(banner);

        items.forEach((item, index) => {
            const number = document.createElement('div');
            number.textContent = String(index + 1);
            number.setAttribute('aria-label', item.note);
            number.style.cssText = `position:absolute;left:${Math.max(4, item.x - 12 + window.scrollX)}px;top:${Math.max(54, item.y - 12 + window.scrollY)}px;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#c62828;color:#fff;border:3px solid #fff;font-size:14px;font-weight:800;box-shadow:0 2px 8px #0008;`;
            root.appendChild(number);

            const outline = document.createElement('div');
            outline.style.cssText = `position:absolute;left:${item.x - 3 + window.scrollX}px;top:${item.y - 3 + window.scrollY}px;width:${item.width + 6}px;height:${item.height + 6}px;border:3px solid #c62828;border-radius:5px;box-sizing:border-box;`;
            root.appendChild(outline);
        });

        document.body.appendChild(root);
    }, { titleText: title, items: serializable });

    return resolved.map((item, index) => ({ number: index + 1, note: item.note }));
}

async function shot(page, target, title, callouts = [], fullPage = false) {
    await page.waitForLoadState('domcontentloaded');
    await page.locator('main, form, body').first().waitFor({ state: 'visible', timeout: 30_000 });
    await page.evaluate(() => window.scrollTo(0, 0));
    const notes = await addOverlay(page, title, callouts);
    const cdp = await page.context().newCDPSession(page);
    try {
        const capture = await cdp.send('Page.captureScreenshot', {
            format: 'png',
            fromSurface: true,
            captureBeyondViewport: fullPage,
        });
        await writeFile(target, Buffer.from(capture.data, 'base64'));
    } finally {
        await cdp.detach();
    }
    await page.locator('#manual-capture-overlay').evaluate((node) => node.remove()).catch(() => {});
    return notes;
}

function commonPageCallouts(label) {
    return [
        { locator: (page) => page.locator('h1'), note: `Tajuk halaman ${label}.` },
        { locator: (page) => page.locator('.fi-sidebar a[aria-current="page"], .fi-sidebar a.fi-active'), note: 'Menu aktif menunjukkan lokasi semasa.' },
        { locator: (page) => page.locator('main input[type="search"], main input[placeholder*="Cari" i]'), note: 'Carian atau tapisan halaman.' },
        { locator: (page) => page.locator('main button, main a').filter({ hasText: /Muat Naik|Cipta|Tambah|Simpan|Eksport|Edit|Klasifikasikan|Lihat/i }), note: 'Tindakan utama yang dibenarkan untuk peranan ini.' },
    ];
}

async function login(page, role) {
    await page.goto('/app/login');
    const image = path.join(outputRoot, role.folder, 'imej', '00-log-masuk.png');
    const notes = await shot(page, image, `Log masuk ${role.label}`, [
        { locator: (p) => p.locator('input[id="form.login"]'), note: 'Masukkan e-mel atau nombor telefon yang didaftarkan.' },
        { locator: (p) => p.locator('input[type="password"]'), note: 'Masukkan kata laluan sendiri. Jangan kongsi dengan orang lain.' },
        { locator: (p) => p.getByRole('button', { name: /Log masuk/i }), note: 'Tekan Log masuk selepas kedua-dua medan lengkap.' },
        { locator: (p) => p.getByRole('link', { name: /pautan log masuk|tanpa kata laluan/i }), note: 'Gunakan pautan selamat jika terlupa atau belum menetapkan kata laluan.' },
    ]);

    await page.locator('input[id="form.login"]').fill(role.email);
    await page.locator('input[type="password"]').fill(password);
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(/\/app\/mam\/?$/, { timeout: 60_000 });
    return { image: 'imej/00-log-masuk.png', notes };
}

async function captureModal(page, role, imageName, buttonName, title, calloutNotes = []) {
    const button = page.getByRole('button', { name: buttonName, exact: true }).filter({ visible: true }).first();
    if (await button.count() === 0) return null;
    await button.click();
    const dialog = page.locator('.fi-modal-window:visible').last();
    const openedDialog = await dialog.waitFor({ state: 'visible', timeout: 3_000 }).then(() => true).catch(() => false);
    if (!openedDialog) {
        const target = path.join(outputRoot, role.folder, 'imej', imageName);
        return {
            image: `imej/${imageName}`,
            title,
            labels: (await page.locator('main label').allTextContents()).map((value) => value.replace(/\s+/g, ' ').trim()).filter(Boolean),
            notes: await shot(page, target, title, [
                { locator: (p) => p.locator('main form, main').first(), note: calloutNotes[0] ?? 'Lengkapkan langkah atau medan yang dipaparkan.' },
                { locator: (p) => p.getByRole('button', { name: /Seterusnya|Simpan|Selesai|Hantar|Sahkan/i }).filter({ visible: true }).last(), note: calloutNotes[1] ?? 'Teruskan hanya selepas maklumat disemak.' },
            ], true),
        };
    }
    const labels = await dialog.locator('label').allTextContents();
    const callouts = [
        { locator: (p) => p.locator('.fi-modal-window:visible').last(), note: calloutNotes[0] ?? 'Semak semua medan dalam dialog sebelum menghantar.' },
        { locator: (p) => p.locator('.fi-modal-window:visible').last().getByRole('button', { name: /Hantar|Simpan|Sahkan|Klasifikasikan|Cipta|Luluskan|Lulus|Tolak/i }).filter({ visible: true }).last(), note: calloutNotes[1] ?? 'Tekan hanya selepas maklumat disemak.' },
    ];
    const target = path.join(outputRoot, role.folder, 'imej', imageName);
    await dialog.evaluate((windowElement) => {
        const container = windowElement.closest('.fi-modal-window-ctn');
        if (!container || container.scrollHeight <= container.clientHeight) return;

        const height = Math.ceil(Math.max(container.scrollHeight, windowElement.getBoundingClientRect().bottom + 16));
        container.style.position = 'absolute';
        container.style.height = `${height}px`;
        container.style.overflow = 'visible';

        const closeOverlay = container.querySelector('.fi-modal-close-overlay');
        if (closeOverlay) {
            closeOverlay.style.position = 'absolute';
            closeOverlay.style.height = '100%';
        }

        document.documentElement.style.height = 'auto';
        document.body.style.height = `${height}px`;
        document.body.style.minHeight = `${height}px`;
        document.body.style.overflow = 'visible';
    });
    const notes = await shot(page, target, title, callouts, true);
    await page.goto(page.url().split('?')[0]);
    return { image: `imej/${imageName}`, title, labels: labels.map((v) => v.replace(/\s+/g, ' ').trim()).filter(Boolean), notes };
}

async function captureModalAtUrl(page, url, role, imageName, buttonName, title, calloutNotes = []) {
    const freshPage = await page.context().newPage();
    try {
        await freshPage.goto(url);
        return await captureModal(freshPage, role, imageName, buttonName, title, calloutNotes);
    } finally {
        await freshPage.close();
    }
}

async function captureRecordDetails(page, role, extras) {
    await page.goto('/app/mam/records');
    const preferredLink = page.locator('tr').filter({ hasText: 'Surat jemputan Mesyuarat AJK' })
        .locator('a[href*="/app/mam/records/"]').filter({ visible: true }).first();
    const link = (await preferredLink.count())
        ? preferredLink
        : page.locator('a[href*="/app/mam/records/"]').filter({ visible: true }).first();
    if (await link.count() === 0) return;
    await link.click();
    await page.waitForURL(/\/records\/\d+/);
    const recordUrl = page.url().split('?')[0];
    const detailPath = path.join(outputRoot, role.folder, 'imej', 'rekod-butiran.png');
    extras.push({
        image: 'imej/rekod-butiran.png',
        title: 'Butiran rekod dan tindakan mengikut kebenaran',
        notes: await shot(page, detailPath, `Butiran rekod - ${role.label}`, [
            { locator: (p) => p.getByRole('tab', { name: 'Maklumat' }), note: 'Metadata utama, sumber, tarikh upload dan status antivirus.' },
            { locator: (p) => p.getByRole('tab', { name: 'Lampiran & Versi' }), note: 'Buka viewer atau muat turun fail yang dibenarkan.' },
            { locator: (p) => p.getByRole('tab', { name: 'Minit' }), note: 'Jejak arahan, penerima, status dan bebenang minit.' },
            { locator: (p) => p.getByRole('tab', { name: 'Kelulusan' }), note: 'Jejak permohonan dan keputusan kelulusan.' },
            { locator: (p) => p.getByRole('button', { name: 'Mohon Pembetulan', exact: true }), note: 'Mohon pembetulan metadata tanpa mengubah rekod secara senyap.' },
        ], true),
    });

    const correction = await captureModalAtUrl(page, recordUrl, role, 'rekod-mohon-pembetulan.png', 'Mohon Pembetulan', 'Mohon pembetulan rekod', [
        'Nyatakan sebab salah tawan dan hanya ubah medan yang benar-benar salah.',
        'Hantar untuk semakan; rekod asal kekal sehingga diluluskan.',
    ]);
    if (correction) extras.push(correction);

    for (const modal of [
        ['rekod-edarkan-minit.png', 'Edarkan Minit', 'Edarkan minit', ['Tetapkan penerima tindakan, penerima makluman, arahan dan keutamaan.', 'Hantar minit selepas penerima disemak.']],
        ['rekod-mohon-kelulusan.png', 'Mohon Kelulusan', 'Mohon kelulusan', ['Pilih pelulus yang dibenarkan dan beri nota yang jelas.', 'Hantar permohonan untuk direkod dan dinotifikasikan.']],
        ['rekod-ganti-versi.png', 'Ganti Versi', 'Ganti versi rekod', ['Pilih fail versi baharu; versi lama kekal dalam jejak audit.', 'Sahkan hanya jika fail benar-benar versi pengganti.']],
        ['rekod-pindah-fail.png', 'Pindah Fail', 'Pindah rekod ke fail lain', ['Pilih fail baharu dalam tenant yang sama dan nyatakan sebab.', 'Sahkan perpindahan selepas nombor fail diperiksa.']],
    ]) {
        const captured = await captureModalAtUrl(page, recordUrl, role, ...modal);
        if (captured) extras.push(captured);
    }

    const attachmentTab = page.getByRole('tab', { name: 'Lampiran & Versi' });
    if (await attachmentTab.count()) {
        await attachmentTab.click();
        const viewerLink = page.getByRole('link', { name: 'Buka Viewer' }).first();
        if (await viewerLink.count()) {
            const viewer = await page.context().newPage();
            await viewer.goto(await viewerLink.getAttribute('href'));
            const viewerState = await viewer.waitForFunction(() => {
                const status = document.querySelector('[data-status]');
                const canvas = document.querySelector('canvas');
                if (!status) return null;
                if (status.dataset.error === 'true') return { error: true, message: status.textContent };
                if (canvas && canvas.width > 0 && canvas.height > 0 && /Halaman \d+ dipaparkan/.test(status.textContent ?? '')) {
                    return { error: false, message: status.textContent };
                }
                return null;
            }, null, { timeout: 45_000 }).then((handle) => handle.jsonValue()).catch(() => ({ error: true, message: 'Viewer tidak selesai dalam 45 saat.' }));
            if (viewerState.error) {
                manifest.errors.push(`${role.label}: ${viewerState.message}`);
            }
            const target = path.join(outputRoot, role.folder, 'imej', 'rekod-viewer.png');
            extras.push({
                image: 'imej/rekod-viewer.png',
                title: 'Viewer dokumen',
                notes: await shot(viewer, target, `Viewer dokumen - ${role.label}`, [
                    { locator: (p) => p.getByRole('button', { name: /Sebelum|Previous/i }), note: 'Ke halaman sebelumnya.' },
                    { locator: (p) => p.getByRole('button', { name: /Seterus|Next/i }), note: 'Ke halaman seterusnya.' },
                    { locator: (p) => p.locator('input[type="search"], input[placeholder*="Cari" i]'), note: 'Cari teks dalam PDF yang mempunyai lapisan teks.' },
                    { locator: (p) => p.getByRole('button', { name: /Cetak|Print/i }), note: 'Cetak dokumen bersama metadata yang dibenarkan.' },
                ]),
            });
            await viewer.close();
        }
    }
}

async function captureFileDetails(page, role, extras) {
    await page.goto('/app/mam/registry-files');
    const hrefs = await page.locator('a[href*="/app/mam/registry-files/"]').filter({ visible: true }).evaluateAll((nodes) => nodes.map((node) => node.href));
    const href = hrefs.find((value) => /\/registry-files\/\d+$/.test(value));
    if (!href) return;
    await page.goto(href);
    await page.waitForURL(/\/registry-files\/\d+/);
    const fileUrl = page.url().split('?')[0];
    const target = path.join(outputRoot, role.folder, 'imej', 'fail-butiran.png');
    extras.push({
        image: 'imej/fail-butiran.png',
        title: 'Butiran fail elektronik, fizikal atau hibrid',
        notes: await shot(page, target, `Butiran fail - ${role.label}`, [
            { locator: (p) => p.getByText('No. Fail', { exact: true }), note: 'Pastikan nombor fail sepadan dengan klasifikasi.' },
            { locator: (p) => p.getByText('Medium', { exact: true }), note: 'Semak sama ada elektronik, fizikal atau hibrid.' },
            { locator: (p) => p.getByText('Lokasi', { exact: true }), note: 'Lokasi sebenar salinan fizikal.' },
            { locator: (p) => p.getByText('Sejarah Pergerakan', { exact: true }), note: 'Jejak keluar, pulang dan pindah lokasi fail.' },
        ], true),
    });

    for (const modal of [
        ['fail-keluarkan-fizikal.png', 'Keluarkan Fail', 'Keluarkan fail fizikal', ['Rekod pemegang, lokasi tujuan, tarikh pulang dan catatan.', 'Simpan supaya penjagaan fail boleh dijejak.']],
        ['fail-pindah-lokasi.png', 'Pindah Lokasi', 'Pindah lokasi fizikal', ['Masukkan lokasi baharu dan catatan.', 'Simpan selepas label rak atau kotak disahkan.']],
        ['fail-beri-akses.png', 'Beri Akses', 'Beri akses khas fail sulit', ['Pilih ahli tenant yang benar-benar memerlukan akses.', 'Beri akses minimum dan tarik balik selepas urusan selesai.']],
    ]) {
        const captured = await captureModalAtUrl(page, fileUrl, role, ...modal);
        if (captured) extras.push(captured);
    }
}

async function captureRole(browser, role) {
    const context = await browser.newContext({
        baseURL,
        viewport: { width: 1440, height: 1000 },
        locale: 'ms-MY',
        colorScheme: 'light',
    });
    const page = await context.newPage();
    const browserErrors = [];
    page.on('pageerror', (error) => browserErrors.push(error.message));
    page.on('console', (message) => { if (message.type() === 'error') browserErrors.push(message.text()); });

    const loginCapture = await login(page, role);
    const navigation = await page.locator('.fi-sidebar a[href]').evaluateAll((nodes) => nodes
        .filter((node) => node.offsetParent !== null)
        .map((node) => ({ label: (node.textContent ?? '').replace(/\s+/g, ' ').trim(), href: node.href }))
        .filter((item, index, items) => item.label && items.findIndex((other) => other.href === item.href) === index));

    if (navigation.length !== role.pages) {
        manifest.errors.push(`${role.label}: expected ${role.pages} pages, found ${navigation.length}`);
    }

    const pages = [];
    let index = 1;
    for (const item of navigation) {
        const response = await page.goto(item.href);
        const status = response?.status() ?? null;
        const slug = fileSlug(item.href);
        const name = `${String(index).padStart(2, '0')}-${slug}.png`;
        const target = path.join(outputRoot, role.folder, 'imej', name);
        const notes = await shot(page, target, `${role.label}: ${item.label}`, commonPageCallouts(item.label));
        const buttons = (await page.getByRole('button').allTextContents()).map((value) => value.replace(/\s+/g, ' ').trim()).filter(Boolean);
        const tabs = (await page.getByRole('tab').allTextContents()).map((value) => value.replace(/\s+/g, ' ').trim()).filter(Boolean);
        pages.push({ order: index, label: item.label, path: new URL(item.href).pathname, status, image: `imej/${name}`, notes, buttons, tabs });
        if (status !== 200) manifest.errors.push(`${role.label}: ${item.href} returned ${status}`);
        index += 1;
    }
    const visiblePaths = new Set(navigation.map((item) => new URL(item.href).pathname));

    const extras = [];
    const extraModals = [
        ['/app/mam/persediaan', 'Mula Persediaan Berpandu', 'persediaan-modal.png', 'Persediaan berpandu'],
        ['/app/mam/ahli-peranan', 'Jemput Ahli', 'ahli-jemput.png', 'Jemput ahli'],
        ['/app/mam/pelupusan', 'Sedia Senarai Semakan', 'pelupusan-sedia.png', 'Sedia senarai pelupusan'],
        ['/app/mam/tetapan-masjid', 'Edit Tetapan', 'tetapan-edit.png', 'Edit tetapan masjid'],
        ['/app/mam/penggunaan', 'Tambah Storan', 'storan-tambah.png', 'Permohonan storan tambahan'],
        ['/app/mam/peti-masuk', '+ Muat Naik Dokumen', 'inbox-muat-naik.png', 'Muat naik dokumen'],
        ['/app/mam/profil', 'Tetapan Notifikasi', 'profil-notifikasi.png', 'Tetapan notifikasi'],
        ['/app/mam/profil', 'Tetapkan Kata Laluan', 'profil-kata-laluan.png', 'Tetapkan kata laluan'],
        ['/app/mam/log-aktiviti', 'Butiran', 'log-aktiviti-butiran.png', 'Butiran log aktiviti'],
    ];
    for (const [url, button, imageName, title] of extraModals) {
        if (!visiblePaths.has(url)) continue;
        await page.goto(url);
        if (page.url().includes('/login') || (await page.locator('main').count()) === 0) continue;
        const captured = await captureModal(page, role, imageName, button, title);
        if (captured) extras.push(captured);
    }

    if (['admin_masjid', 'setiausaha'].includes(role.key)) {
        await page.goto('/app/mam/peti-masuk');
        const captured = await captureModal(page, role, 'inbox-klasifikasi.png', 'Klasifikasikan', 'Klasifikasi peti masuk', [
            'Lengkapkan metadata, pilih atau buka fail, tetapkan sensitiviti dan penerima minit.',
            'Klasifikasikan hanya selepas dokumen, rujukan dan penerima disahkan.',
        ]);
        if (captured) extras.push(captured);
    }

    if (role.key === 'admin_masjid') {
        for (const [url, name, title] of [
            ['/app/mam/classification-nodes/create', 'klasifikasi-cipta.png', 'Cipta nod klasifikasi'],
            ['/app/mam/registry-files/create', 'fail-cipta.png', 'Buka fail baharu'],
            ['/app/mam/retensi-peraturan/create', 'retensi-peraturan-cipta.png', 'Cipta peraturan retensi'],
            ['/app/mam/delegasi/create', 'delegasi-cipta.png', 'Cipta delegasi'],
        ]) {
            const response = await page.goto(url);
            if (response?.status() !== 200) continue;
            const target = path.join(outputRoot, role.folder, 'imej', name);
            extras.push({ image: `imej/${name}`, title, notes: await shot(page, target, title, [
                { locator: (p) => p.locator('main form'), note: 'Isi semua medan wajib bertanda asterisk.' },
                { locator: (p) => p.getByRole('button', { name: /Cipta|Simpan/i }).filter({ visible: true }).last(), note: 'Semak semula sebelum menyimpan.' },
            ], true) });
        }
    }

    await captureRecordDetails(page, role, extras);
    await captureFileDetails(page, role, extras);

    await page.goto('/app/mam/minit-saya');
    for (const [name, imageName, title] of [
        ['Balas & Edarkan', 'minit-balas.png', 'Balas dan edarkan minit'],
        ['Tanda Selesai', 'minit-selesai.png', 'Tanda tindakan minit selesai'],
    ]) {
        const captured = await captureModalAtUrl(page, '/app/mam/minit-saya', role, imageName, name, title);
        if (captured) extras.push(captured);
    }

    if (['pengerusi', 'nazir'].includes(role.key)) {
        await page.goto('/app/mam/kelulusan');
        const captured = await captureModalAtUrl(page, '/app/mam/kelulusan', role, 'kelulusan-lulus.png', 'Lulus', 'Buat keputusan kelulusan', [
            'Sahkan kata laluan dan masukkan nota keputusan jika perlu.',
            'Lulus hanya selepas dokumen serta metadata disemak.',
        ]);
        if (captured) extras.push(captured);
    }

    await page.goto('/app/mam/carian');
    const query = page.locator('input[wire\\:model="query"]');
    if (await query.count()) {
        await query.fill('surat');
        await page.getByRole('button', { name: 'Cari', exact: true }).click();
        await page.getByText(/hasil ditemui/).waitFor({ timeout: 15_000 });
        const target = path.join(outputRoot, role.folder, 'imej', 'carian-hasil.png');
        extras.push({ image: 'imej/carian-hasil.png', title: 'Hasil carian lanjutan', notes: await shot(page, target, `Hasil carian - ${role.label}`, [
            { locator: (p) => p.getByText(/hasil ditemui/), note: 'Jumlah hasil yang pengguna ini dibenarkan lihat.' },
            { locator: (p) => p.locator('article').first(), note: 'Buka rekod atau tekan bintang untuk kegemaran.' },
        ], true) });
    }

    const crossTenant = await page.goto('/app/man/records');
    const crossTenantStatus = crossTenant?.status() ?? null;
    if (crossTenantStatus !== 404) manifest.errors.push(`${role.label}: cross-tenant route returned ${crossTenantStatus}`);

    manifest.roles[role.key] = {
        label: role.label,
        folder: role.folder,
        expectedPages: role.pages,
        actualPages: navigation.length,
        login: loginCapture,
        pages,
        extras,
        crossTenantStatus,
        browserErrors: [...new Set(browserErrors)].filter((message) => !message.includes('Failed to load resource: the server responded with a status of 404')),
    };
    if (manifest.roles[role.key].browserErrors.length) {
        manifest.errors.push(`${role.label}: browser errors: ${manifest.roles[role.key].browserErrors.join(' | ')}`);
    }
    await context.close();
}

async function capturePublic(browser) {
    const folder = path.join(outputRoot, '09-Orang-Awam-Pendaftaran', 'imej');
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 }, locale: 'ms-MY' });
    const page = await context.newPage();
    const captures = [];

    await page.goto('/');
    captures.push({ image: 'imej/01-laman-utama.png', title: 'Laman utama', notes: await shot(page, path.join(folder, '01-laman-utama.png'), 'Laman utama Diwan', [
        { locator: (p) => p.getByRole('link', { name: /Daftar/i }).first(), note: 'Pilih Daftar untuk permohonan masjid baharu.' },
        { locator: (p) => p.getByRole('link', { name: /Log Masuk/i }).first(), note: 'Pengguna sedia ada memilih Log Masuk.' },
    ]) });

    await page.goto('/daftar');
    captures.push({ image: 'imej/02-borang-daftar.png', title: 'Borang pendaftaran', notes: await shot(page, path.join(folder, '02-borang-daftar.png'), 'Daftar masjid', [
        { locator: (p) => p.locator('input[wire\\:model\\.blur="name"]'), note: 'Nama rasmi masjid.' },
        { locator: (p) => p.locator('select[wire\\:model="state"]'), note: 'Negeri dan daerah masjid.' },
        { locator: (p) => p.locator('input[wire\\:model="code"]'), note: 'Kod akronim unik 3 hingga 6 huruf.' },
        { locator: (p) => p.locator('input[wire\\:model="slug"]'), note: 'Slug URL ringkas, huruf kecil dan nombor sahaja.' },
        { locator: (p) => p.locator('input[wire\\:model="admin_name"]'), note: 'Nama orang yang akan menjadi Admin / Kerani pertama.' },
        { locator: (p) => p.locator('input[wire\\:model="email"]'), note: 'E-mel aktif untuk menerima pautan kelulusan.' },
        { locator: (p) => p.locator('input[wire\\:model="phone_wa"]'), note: 'Nombor WhatsApp format negara, contoh 60123456789.' },
        { locator: (p) => p.locator('input[type="checkbox"]').first(), note: 'Baca dan setuju Terma serta DPA.' },
        { locator: (p) => p.locator('input[type="checkbox"]').nth(1), note: 'Fahami dan setuju dasar retensi.' },
        { locator: (p) => p.getByRole('button', { name: 'Hantar Permohonan' }), note: 'Hantar selepas semua maklumat disemak.' },
    ], true) });

    const suffix = String(Date.now()).slice(-7);
    const mosqueName = `Masjid Manual Latihan ${suffix}`;
    const slug = `manual-latihan-${suffix}`;
    const email = `pemohon-${suffix}@example.test`;
    const initialLogSize = await stat(path.resolve('storage/logs/laravel.log')).then((value) => value.size).catch(() => 0);
    await page.locator('input[wire\\:model\\.blur="name"]').fill(mosqueName);
    await page.locator('select[wire\\:model="state"]').selectOption('Selangor');
    await page.locator('input[wire\\:model="district"]').fill('Gombak');
    await page.locator('input[wire\\:model="code"]').fill(letterCode(Date.now()));
    await page.locator('input[wire\\:model="slug"]').fill(slug);
    await page.locator('input[wire\\:model="admin_name"]').fill('Pentadbir Manual Latihan');
    await page.locator('input[wire\\:model="email"]').fill(email);
    await page.locator('input[wire\\:model="phone_wa"]').fill(`6011${suffix}`);
    await page.locator('input[type="checkbox"]').nth(0).check();
    await page.locator('input[type="checkbox"]').nth(1).check();
    await page.getByRole('button', { name: 'Hantar Permohonan' }).click();
    await page.getByText('Permohonan diterima!').waitFor({ timeout: 60_000 });
    captures.push({ image: 'imej/03-permohonan-diterima.png', title: 'Permohonan diterima', notes: await shot(page, path.join(folder, '03-permohonan-diterima.png'), 'Permohonan diterima', [
        { locator: (p) => p.getByText('Permohonan diterima!'), note: 'Simpan bukti ini dan tunggu kelulusan platform.' },
        { locator: (p) => p.getByRole('link', { name: 'Kembali ke laman utama' }), note: 'Tiada tindakan lain sehingga pautan kelulusan diterima.' },
    ]) });

    const adminContext = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    const admin = await adminContext.newPage();
    await admin.goto('/admin/login');
    await admin.locator('input[id="form.login"]').fill('superadmin@diwan.test');
    await admin.locator('input[type="password"]').fill(password);
    await admin.getByRole('button', { name: /Log masuk/i }).click();
    await admin.waitForURL(/\/admin\/?$/);
    await admin.goto('/admin/mosques');
    const row = admin.locator('tr').filter({ hasText: mosqueName });
    await row.getByRole('button', { name: 'Lulus' }).click();
    await admin.getByRole('dialog').getByRole('button', { name: 'Sahkan' }).click();
    await admin.getByText(/diluluskan.*disediakan/i).waitFor({ timeout: 60_000 });
    await adminContext.close();

    const log = await readFile(path.resolve('storage/logs/laravel.log'));
    const newLog = log.subarray(Math.min(initialLogSize, log.length)).toString('utf8');
    const token = newLog.match(/\/masuk\/([A-Za-z0-9]{64})/)?.[1];
    if (!token) throw new Error('Magic-link token not found in local mail log.');

    await page.goto(`/masuk/${token}`);
    await page.waitForURL(/tetapkan-kata-laluan/, { timeout: 60_000 });
    captures.push({ image: 'imej/04-tetapkan-kata-laluan.png', title: 'Tetapkan kata laluan kali pertama', notes: await shot(page, path.join(folder, '04-tetapkan-kata-laluan.png'), 'Kata laluan kali pertama', [
        { locator: (p) => p.locator('input[wire\\:model="password"]'), note: 'Cipta kata laluan panjang dan unik.' },
        { locator: (p) => p.locator('input[wire\\:model="password_confirmation"]'), note: 'Taip semula kata laluan yang sama.' },
        { locator: (p) => p.getByRole('button', { name: /Simpan.*Teruskan/i }), note: 'Simpan dan teruskan ke persediaan masjid.' },
    ]) });
    const firstPassword = `${randomBytes(18).toString('base64url')}Aa1!`;
    await page.locator('input[wire\\:model="password"]').fill(firstPassword);
    await page.locator('input[wire\\:model="password_confirmation"]').fill(firstPassword);
    await page.getByRole('button', { name: /Simpan.*Teruskan/i }).click();
    await page.waitForURL(/\/app\/[^/]+\/persediaan(?:\?.*)?$/, { timeout: 60_000 });
    captures.push({ image: 'imej/05-persediaan-pertama.png', title: 'Persediaan masjid kali pertama', notes: await shot(page, path.join(folder, '05-persediaan-pertama.png'), 'Persediaan kali pertama', [
        { locator: (p) => p.getByRole('button', { name: 'Mula Persediaan Berpandu' }), note: 'Mulakan wizard bagi profil, WhatsApp dan ahli.' },
        { locator: (p) => p.getByRole('button', { name: 'Langkau Buat Sementara' }), note: 'Langkau hanya jika maklumat belum tersedia; lengkapkan kemudian.' },
    ], true) });

    await page.goto('/log-masuk');
    captures.push({ image: 'imej/06-log-masuk-pautan.png', title: 'Log masuk tanpa kata laluan', notes: await shot(page, path.join(folder, '06-log-masuk-pautan.png'), 'Pautan log masuk selamat', [
        { locator: (p) => p.locator('input[wire\\:model="login"]'), note: 'Masukkan e-mel atau nombor telefon yang berdaftar.' },
        { locator: (p) => p.getByRole('button', { name: 'Hantar Pautan Log Masuk' }), note: 'Pautan sah 15 minit dan hanya sekali guna.' },
        { locator: (p) => p.getByRole('link', { name: /kata laluan/i }), note: 'Gunakan log masuk kata laluan jika sudah ditetapkan.' },
    ]) });

    manifest.public = { folder: '09-Orang-Awam-Pendaftaran', captures, completedRegistration: true };
    await context.close();
}

await ensureFolders();
const browser = await chromium.launch({ channel: 'chrome', headless: true });
try {
    if (process.env.MANUAL_SKIP_PUBLIC === '1' && !manifest.public.completedRegistration) {
        manifest.public = {
            folder: '09-Orang-Awam-Pendaftaran',
            completedRegistration: true,
            captures: [
                ['01-laman-utama.png', 'Laman utama'],
                ['02-borang-daftar.png', 'Borang pendaftaran'],
                ['03-permohonan-diterima.png', 'Permohonan diterima'],
                ['04-tetapkan-kata-laluan.png', 'Tetapkan kata laluan kali pertama'],
                ['05-persediaan-pertama.png', 'Persediaan masjid kali pertama'],
                ['06-log-masuk-pautan.png', 'Log masuk tanpa kata laluan'],
            ].map(([image, title]) => ({ image: `imej/${image}`, title, notes: [] })),
        };
    } else if (process.env.MANUAL_SKIP_PUBLIC !== '1') {
        await capturePublic(browser);
    }
    for (const role of selectedRoles) {
        try {
            await captureRole(browser, role);
        } catch (error) {
            manifest.errors.push(`${role.label}: capture failed: ${error instanceof Error ? error.message : String(error)}`);
            throw error;
        }
    }
} finally {
    await browser.close();
    await writeFile(manifestPath, `${JSON.stringify(manifest, null, 2)}\n`, 'utf8');
}

if (manifest.errors.length) {
    console.error(JSON.stringify(manifest.errors, null, 2));
    process.exitCode = 1;
} else {
    console.log(JSON.stringify({ roles: Object.keys(manifest.roles).length, public: manifest.public.completedRegistration, images: Object.values(manifest.roles).reduce((sum, role) => sum + role.pages.length + role.extras.length + 1, manifest.public.captures.length) }, null, 2));
}
