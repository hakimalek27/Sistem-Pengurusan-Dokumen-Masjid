import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const guideIds = JSON.parse(readFileSync('resources/help/guides.json', 'utf8')).guides.map((guide) => guide.id);

async function login(browser, baseURL, email) {
    const context = await browser.newContext({ baseURL });
    await context.addInitScript((ids) => {
        for (const id of ids) localStorage.setItem(`diwan-help-seen:${id}`, '1');
    }, guideIds);
    const page = await context.newPage();
    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill(email);
    await page.locator('input[type="password"]').fill('password');
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(/\/app\/mam\/?$/, { timeout: 60_000 });
    return { context, page };
}

async function submitVisibleAction(page) {
    const submit = page.getByRole('dialog').getByRole('button', { name: /^(Hantar|Sahkan|Klasifikasikan)$/ }).last();
    await expect(submit).toBeEnabled();
    await submit.click();
}

async function selectFilamentOption(page, index, label, exact = true) {
    const dialog = page.getByRole('dialog');
    await dialog.getByRole('button', { name: 'Pilih satu pilihan' }).nth(index).click();
    await page.getByRole('option', { name: label, exact }).filter({ visible: true }).click();
}

test('klasifikasi Peti Masuk terus edarkan minit melalui modal', async ({ browser, baseURL }) => {
    const marker = Date.now();
    const documentTitle = `Dokumen aliran pejabat E2E ${marker}`;
    const instruction = `Minit dari klasifikasi E2E ${marker}`;
    const adminKeraniSession = await login(browser, baseURL, 'admin_masjid@demo.test');
    const kerani = adminKeraniSession.page;

    await kerani.goto('/app/mam/peti-masuk');
    await kerani.getByRole('button', { name: /Muat Naik Dokumen/i }).click();
    const uploadDialog = kerani.getByRole('dialog');
    await uploadDialog.locator('input[type="file"]').setInputFiles({
        name: `${documentTitle}.txt`,
        mimeType: 'text/plain',
        buffer: Buffer.from(`Dokumen ujian aliran pejabat ${marker}.`),
    });
    await expect(uploadDialog.getByText('Upload complete', { exact: true })).toBeVisible({ timeout: 60_000 });
    const upload = uploadDialog.getByRole('button', { name: 'Hantar', exact: true });
    await expect(upload).toBeEnabled({ timeout: 60_000 });
    await upload.click();
    await expect(kerani.getByText('1 dokumen dimuat naik ke Peti Masuk.')).toBeVisible({ timeout: 60_000 });

    const inboxRow = kerani.locator('tr').filter({ hasText: documentTitle }).first();
    await expect(inboxRow).toBeVisible();
    await inboxRow.getByRole('button', { name: 'Klasifikasikan' }).click();

    const dialog = kerani.getByRole('dialog');
    const next = () => dialog.getByRole('button', { name: 'Seterus', exact: true });
    await expect(dialog).toContainText('Asal dokumen');
    await next().click();
    await kerani.locator('#mountedActionSchema0\\.record_type').selectOption('surat_menyurat');
    await kerani.waitForTimeout(500);
    await kerani.locator('#mountedActionSchema0\\.direction').selectOption('masuk');
    await next().click();
    await selectFilamentOption(kerani, 0, /MAM\.100-4\/1.*Surat-Menyurat Am 2026/, false);
    await kerani.locator('#mountedActionSchema0\\.sensitivity').selectOption('dalaman');
    await next().click();
    await selectFilamentOption(kerani, 0, 'Pengerusi (MAM)');
    await kerani.locator('#mountedActionSchema0\\.minit_body').fill(instruction);
    await kerani.locator('#mountedActionSchema0\\.minit_priority').selectOption('biasa');
    await next().click();
    await expect(dialog).toContainText('Sensitiviti efektif: Dalaman');
    await expect(dialog).toContainText('Pengerusi (MAM)');
    await submitVisibleAction(kerani);

    await expect(kerani.getByText('Rekod difailkan dan minit tindakan telah dihantar.')).toBeVisible({ timeout: 60_000 });
    await adminKeraniSession.context.close();

    const pengerusiSession = await login(browser, baseURL, 'pengerusi@demo.test');
    const pengerusi = pengerusiSession.page;
    await pengerusi.goto('/app/mam/minit-saya');
    await expect(pengerusi.locator('tr').filter({ hasText: instruction })).toBeVisible();
    await pengerusiSession.context.close();
});

test('minit, maklum balas, susulan dan kelulusan pejabat melalui UI', async ({ browser, baseURL }) => {
    const marker = Date.now();
    const instruction = `Arahan E2E ${marker}: semak dokumen dan beri maklum balas.`;
    const reply = `Maklum balas E2E ${marker}: semakan selesai, edarkan kepada setiausaha.`;
    const approvalNote = `Mohon kelulusan E2E ${marker}.`;

    const adminKeraniSession = await login(browser, baseURL, 'admin_masjid@demo.test');
    const kerani = adminKeraniSession.page;
    await kerani.goto('/app/mam/records');
    const recordUrl = await kerani.locator('a[href*="/app/mam/records/"]').first().getAttribute('href');
    expect(recordUrl).toBeTruthy();
    await kerani.goto(recordUrl);
    await kerani.getByRole('button', { name: 'Edarkan Minit' }).click();
    await selectFilamentOption(kerani, 0, 'Pengerusi (MAM)');
    await kerani.getByRole('dialog').getByLabel('Catatan / Arahan').fill(instruction);
    await kerani.getByRole('dialog').getByLabel('Keutamaan*', { exact: true }).selectOption('segera');
    await submitVisibleAction(kerani);
    await expect(kerani.getByText('Minit diedarkan.')).toBeVisible({ timeout: 60_000 });
    await kerani.getByRole('tab', { name: 'Minit' }).click();
    await expect(kerani.getByText(instruction)).toBeVisible();
    await adminKeraniSession.context.close();

    const pengerusiSession = await login(browser, baseURL, 'pengerusi@demo.test');
    const pengerusi = pengerusiSession.page;
    await pengerusi.goto('/app/mam/minit-saya');
    const minitRow = pengerusi.locator('tr').filter({ hasText: instruction });
    await expect(minitRow).toBeVisible();
    await minitRow.getByRole('button', { name: 'Balas & Edarkan' }).click();
    await selectFilamentOption(pengerusi, 0, 'Setiausaha (MAM)');
    await pengerusi.getByRole('dialog').getByLabel('Catatan').fill(reply);
    await submitVisibleAction(pengerusi);
    await expect(pengerusi.getByText('Balasan minit diedarkan.')).toBeVisible({ timeout: 60_000 });
    await minitRow.getByRole('button', { name: 'Tanda Selesai' }).click();
    await submitVisibleAction(pengerusi);
    await expect(pengerusi.getByText('Tindakan minit ditanda selesai.')).toBeVisible({ timeout: 60_000 });
    await pengerusiSession.context.close();

    const secretarySession = await login(browser, baseURL, 'setiausaha@demo.test');
    const secretary = secretarySession.page;
    await secretary.goto('/app/mam/minit-saya');
    const replyRow = secretary.locator('tr').filter({ hasText: reply });
    await expect(replyRow).toBeVisible();
    await replyRow.getByRole('button', { name: 'Tanda Selesai' }).click();
    await submitVisibleAction(secretary);
    await expect(secretary.getByText('Tindakan minit ditanda selesai.')).toBeVisible({ timeout: 60_000 });

    await secretary.goto(recordUrl);
    await secretary.getByRole('button', { name: 'Mohon Kelulusan' }).click();
    await secretary.getByRole('dialog').getByLabel('Kepada*', { exact: true }).selectOption({ label: 'Pengerusi (MAM)' });
    await secretary.getByRole('dialog').getByLabel('Nota').fill(approvalNote);
    await submitVisibleAction(secretary);
    await expect(secretary.getByText('Permohonan kelulusan dihantar.')).toBeVisible({ timeout: 60_000 });
    await secretarySession.context.close();

    const approverSession = await login(browser, baseURL, 'pengerusi@demo.test');
    const approver = approverSession.page;
    await approver.goto('/app/mam/kelulusan');
    const approvalRow = approver.locator('tr').filter({ hasText: approvalNote });
    await expect(approvalRow).toBeVisible();
    await approvalRow.getByRole('button', { name: 'Lulus' }).click();
    await approver.getByRole('dialog').getByLabel('Sahkan Kata Laluan').fill('password');
    await approver.getByRole('dialog').getByLabel('Nota').fill('Diluluskan melalui ujian E2E.');
    await submitVisibleAction(approver);
    await expect(approver.getByText('Keputusan kelulusan direkodkan.')).toBeVisible({ timeout: 60_000 });
    await approverSession.context.close();
});
