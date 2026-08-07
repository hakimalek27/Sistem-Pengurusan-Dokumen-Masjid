// F8 §9 — ukur SEMULA `centerCovered` bagi KESELURUHAN kohort 124 langkah pada mobile.
//
// Mengapa penuh dan bukan hanya enam yang cacat: kawalan menunjukkan `tenant.records#1` kini
// `centerCovered=true` walaupun ia TIADA dalam senarai enam asal. Melaporkan "6 → n" sahaja
// akan menyembunyikan komposisi yang berubah. Audit mengukur 124; F8 mesti mengukur 124.
//
// Metrik ditentukur dahulu (pelajaran F5): audit mendapati 6/124 — jadi ia MEMBEZAKAN, bukan
// benar untuk mana-mana popover. Definisi ditiru tepat:
//   viewport 390x664 · pusat (195,332) · centerCovered = rect popover mengandungi pusat.
//
// ⚠️ Perbezaan yang mesti dinyatakan: audit berjalan pada PRODUKSI tenant `smoke`; ukuran ini
// TEMPATAN pada tenant `mam` dengan benih demo. Struktur halaman sama, isi baris berbeza.
//
// Guna: node "Audit Review Round Robin/bukti/plan-f8/skrip/ukur-mobile-kohort-f8.mjs"

const { chromium } = await import(
    'file:///C:/Projek%20Coding/Sistem%20Pengurusan%20Dokumen%20Masjid/node_modules/playwright/index.mjs'
);
import { readFileSync, writeFileSync } from 'node:fs';

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8092';
const TENANT = process.env.E2E_TENANT || 'mam';
const KATA = process.env.MANUAL_DEMO_PASSWORD || 'password';

const manifest = JSON.parse(readFileSync('Audit Review Round Robin/bukti/plan-baseline/manifest.json', 'utf8'));
const audit = (() => {
    const d = JSON.parse(readFileSync('Audit Review Round Robin/bukti/pusingan-11-codex/production-mobile-all-tour-steps.json', 'utf8'));
    const arr = Array.isArray(d) ? d : (d.steps || Object.values(d)[0]);
    return new Map(arr.map((x) => [`${x.guide}#${x.index}`, x]));
})();

const kohort = manifest.catalogue
    .filter((g) => g.family === 'tenant')
    .flatMap((g) => g.steps.map((s) => ({ key: s.key, guide: g.guide_id, index: s.index, route: s.route || g.route })));

console.log(`kohort: ${kohort.length} langkah · audit centerCovered = ${[...audit.values()].filter((x) => x.centerCovered).length}\n`);

const pelayar = await chromium.launch();
const k = await pelayar.newContext({ viewport: { width: 390, height: 664 } });
await k.addInitScript(() => { try { localStorage.setItem('diwan-help-mode', 'dimatikan'); } catch { /* noop */ } });
const p = await k.newPage();

await p.goto(`${BASE}/app/login`);
await p.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
await p.locator('input[type="password"]').fill(KATA);
await p.getByRole('button', { name: /Log masuk/i }).click();
await p.waitForURL((u) => u.pathname.replace(/\/$/, '') === `/app/${TENANT}`, { timeout: 90_000 });

// Kegemaran perlu sekurang-kurangnya satu item, jika tidak lima langkahnya diukur pada skrin
// kosong dan angkanya tidak bermakna (pelajaran W1/W4).
await p.goto(`${BASE}/app/${TENANT}/kegemaran`);
await p.waitForTimeout(1200);
if (!(await p.locator('[data-help-target="favourite-item"]').count())) {
    await p.goto(`${BASE}/app/${TENANT}/records`);
    const rek = p.locator('main a[href*="/records/"]').first();
    if (await rek.count()) {
        await rek.dispatchEvent('click');
        await p.waitForURL(/\/records\/\d+/, { timeout: 60_000 });
        const b = p.getByRole('button', { name: /Kegemaran/i }).first();
        if (await b.isVisible().catch(() => false)) { await b.dispatchEvent('click'); await p.waitForTimeout(1200); }
    }
}

const hasil = [];
for (const [i, s] of kohort.entries()) {
    const laluan = String(s.route || '').replace('{tenant}', TENANT) || `/app/${TENANT}`;
    const url = `${BASE}${laluan}?panduan=${s.guide}&langkah=${s.index - 1}`;
    let r = { adaPopover: false };
    try {
        for (let c = 1; ; c += 1) {
            try { await p.goto(url, { waitUntil: 'domcontentloaded' }); break; }
            catch (e) { if (c >= 3) throw e; await p.waitForTimeout(1200); }
        }
        await p.waitForTimeout(2200);
        r = await p.evaluate(() => {
            const pop = document.querySelector('.driver-popover');
            if (!pop) return { adaPopover: false };
            const b = pop.getBoundingClientRect();
            const cx = innerWidth / 2; const cy = innerHeight / 2;
            return {
                adaPopover: true,
                centerCovered: b.left <= cx && b.right >= cx && b.top <= cy && b.bottom >= cy,
                popSize: { w: Math.round(b.width), h: Math.round(b.height), left: Math.round(b.left), top: Math.round(b.top) },
                target: document.querySelector('.driver-active-element')?.getAttribute('data-help-target') ?? null,
            };
        });
    } catch (e) {
        r = { adaPopover: false, ralat: String(e.message).split('\n')[0].slice(0, 80) };
    }
    const dahulu = audit.get(s.key)?.centerCovered ?? null;
    hasil.push({ key: s.key, dahulu, ...r });
    // Tulis berperingkat: larian pertama dihentikan pada 100/124 dan KEHILANGAN semuanya
    // kerana JSON hanya ditulis di hujung. Hasil separa yang boleh dibaca lebih berguna
    // daripada tiada — asalkan ia berlabel separa.
    if ((i + 1) % 20 === 0 || i + 1 === kohort.length) {
        console.log(`  … ${i + 1}/${kohort.length}`);
        writeFileSync('Audit Review Round Robin/bukti/plan-f8/mobile-kohort-f8.json', JSON.stringify({
            lengkap: i + 1 === kohort.length, diukur: i + 1, daripada: kohort.length, hasil,
        }, null, 2) + '\n');
    }
}

const kini = hasil.filter((h) => h.centerCovered);
const dahuluCc = hasil.filter((h) => h.dahulu === true);
const baharu = kini.filter((h) => h.dahulu !== true);
const pulih = dahuluCc.filter((h) => !h.centerCovered);
const tiadaPopover = hasil.filter((h) => !h.adaPopover);

console.log(`\n── centerCovered mobile 390x664 ──`);
console.log(`  audit (produksi, smoke) : ${dahuluCc.length}/124`);
console.log(`  kini  (tempatan, mam)   : ${kini.length}/${hasil.length}`);
console.log(`  masih tertutup          : ${dahuluCc.filter((h) => h.centerCovered).length}`);
console.log(`  PULIH                   : ${pulih.length}  ${pulih.map((h) => h.key).join(', ') || '-'}`);
console.log(`  BAHARU tertutup         : ${baharu.length}`);
for (const h of baharu) console.log(`     ${h.key.padEnd(26)} target=${h.target} pop=${h.popSize?.h}px@${h.popSize?.top}`);
console.log(`  tiada popover (diukur)  : ${tiadaPopover.length}  ${tiadaPopover.map((h) => h.key).slice(0, 8).join(', ')}`);

writeFileSync('Audit Review Round Robin/bukti/plan-f8/mobile-kohort-f8.json', JSON.stringify({
    viewport: { width: 390, height: 664 },
    nota: 'audit = produksi tenant smoke; kini = tempatan tenant mam, benih demo',
    audit_centercovered: dahuluCc.length, kini_centercovered: kini.length,
    pulih: pulih.map((h) => h.key), baharu: baharu.map((h) => h.key),
    tiada_popover: tiadaPopover.map((h) => h.key), hasil,
}, null, 2) + '\n');
await pelayar.close();
