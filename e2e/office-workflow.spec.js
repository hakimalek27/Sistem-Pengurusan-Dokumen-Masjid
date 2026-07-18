import { expect, test } from '@playwright/test';

async function login(browser, baseURL, email) {
    const context = await browser.newContext({ baseURL });
    const page = await context.newPage();
    await page.goto('/app/login');
    await page.locator('input[id="form.login"]').fill(email);
    await page.locator('input[type="password"]').fill('password');
    await page.getByRole('button', { name: /Log masuk/i }).click();
    await page.waitForURL(/\/app\/mam\/?$/, { timeout: 60_000 });
    return { context, page };
}

async function submitVisibleAction(page) {
    const submit = page.getByRole('dialog').getByRole('button', { name: /^(Hantar|Sahkan)$/ }).last();
    await expect(submit).toBeEnabled();
    await submit.click();
}

async function selectFilamentOption(page, index, label) {
    const dialog = page.getByRole('dialog');
    await dialog.getByRole('button', { name: 'Pilih satu pilihan' }).nth(index).click();
    await page.getByRole('option', { name: label, exact: true }).click();
}

test('minit, maklum balas, susulan dan kelulusan pejabat melalui UI', async ({ browser, baseURL }) => {
    const marker = Date.now();
    const instruction = `Arahan E2E ${marker}: semak dokumen dan beri maklum balas.`;
    const reply = `Maklum balas E2E ${marker}: semakan selesai, edarkan kepada setiausaha.`;
    const approvalNote = `Mohon kelulusan E2E ${marker}.`;

    const keraniSession = await login(browser, baseURL, 'kerani@demo.test');
    const kerani = keraniSession.page;
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
    await keraniSession.context.close();

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
