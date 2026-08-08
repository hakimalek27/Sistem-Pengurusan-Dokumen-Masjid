// F8 — prob terfokus: `/app/<tenant>/peti-masuk` sebagai admin_masjid.
//
// Mengapa: latihan §9.1 tersekat pada URL ini berulang kali untuk tenant `smoke-<uuid>`,
// sedangkan prob yang SAMA pada tenant `mam` memberi 200 tiga kali dalam ~720 ms. Satu-satunya
// perbezaan yang belum diuji ialah TENANT: `mam` mempunyai data demo, `smoke-<uuid>` KOSONG.
// Prob ini mengasingkan pemboleh ubah itu dan tidak lebih.
//
// Guna (fixture disediakan oleh pemanggil; kredensial melalui env, tidak pernah ditulis):
//   PROB_BASE=http://127.0.0.1:8095 PROB_EMAIL=… PROB_PASSWORD=… PROB_TENANT=smoke-…
//   node "…/skrip/prob-peti-masuk-smoke.mjs"

import { chromium } from 'playwright';

const base = process.env.PROB_BASE ?? 'http://127.0.0.1:8095';
const { PROB_EMAIL: emel, PROB_PASSWORD: kataLaluan, PROB_TENANT: tenant } = process.env;
for (const [nama, nilai] of Object.entries({ PROB_EMAIL: emel, PROB_PASSWORD: kataLaluan, PROB_TENANT: tenant })) {
    if (!nilai) { console.error(`env ${nama} wajib`); process.exit(2); }
}

const pelayar = await chromium.launch({ channel: 'chrome', headless: true });
const konteks = await pelayar.newContext({ baseURL: base, viewport: { width: 1440, height: 1000 } });
const halaman = await konteks.newPage();

const gagal = [];
halaman.on('requestfailed', (r) => gagal.push(`${r.method()} ${r.url().replace(base, '')} :: ${r.failure()?.errorText}`));

await halaman.goto('/app/login');
await halaman.locator('input[id="form.login"]').fill(emel);
await halaman.locator('input[type="password"]').fill(kataLaluan);
await halaman.getByRole('button', { name: /Log masuk/i }).click();
await halaman.waitForURL((u) => u.pathname.replace(/\/$/, '') === `/app/${tenant}`, { timeout: 90_000 });
console.log('LOG MASUK OK');

for (let i = 1; i <= 3; i++) {
    const mula = Date.now();
    try {
        const balas = await halaman.goto(`/app/${tenant}/peti-masuk`, { timeout: 60_000 });
        console.log(`cubaan ${i}: status=${balas?.status()} ms=${Date.now() - mula}`);
    } catch (e) {
        console.log(`cubaan ${i}: LEMPAR ms=${Date.now() - mula} :: ${String(e.message).split('\n')[0]}`);
    }
}

// Kawalan dalam prob yang SAMA: satu halaman tenant lain. Jika ia juga perlahan, masalahnya
// bukan Peti Masuk — ia tenant kosong itu sendiri.
const mula = Date.now();
const kawalan = await halaman.goto(`/app/${tenant}/records`, { timeout: 60_000 }).catch(() => null);
console.log(`kawalan /records: status=${kawalan?.status() ?? 'LEMPAR'} ms=${Date.now() - mula}`);

console.log('permintaan GAGAL:', gagal.length ? gagal.join(' | ') : '(tiada)');
await konteks.close();
await pelayar.close();
