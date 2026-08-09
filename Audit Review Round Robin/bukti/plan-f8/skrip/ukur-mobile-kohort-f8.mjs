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

// ── Provenance (Codex P2 #1/#17) ──────────────────────────────────────────────────────────
// Artifak tanpa provenance tidak boleh diaudit: dua larian versi berbeza boleh dicampur dan
// tetap kelihatan lengkap. Setiap fail ukuran kini membawa commit, versi+hash katalog, tenant,
// base URL, viewport dan masa.
async function provenance(extra = {}) {
    const { execSync } = await import('node:child_process');
    const { createHash } = await import('node:crypto');
    let commit = 'tidak-diketahui';
    try { commit = execSync('git rev-parse --short HEAD', { encoding: 'utf8' }).trim(); } catch { /* noop */ }
    // ⚠️ JANGAN telan kegagalan ini. Versi katalog ialah medan provenance yang PALING penting
    // (bagi A/B ia satu-satunya yang membuktikan fail `ab-lama` benar-benar sisi katalog lama).
    // Versi pertama membalutnya dalam try/catch dan menghasilkan `katalog_version: null` secara
    // senyap kerana `readFileSync` tidak diimport — provenance yang gagal senyap lebih buruk
    // daripada tiada provenance.
    const mentah = readFileSync('resources/help/guides.json', 'utf8');
    const katalogVersi = JSON.parse(mentah).catalog_version ?? null;
    const katalogHash = createHash('sha256').update(mentah).digest('hex').slice(0, 16);
    if (!katalogVersi) throw new Error('provenance: catalog_version tidak dapat dibaca');

    return { commit, katalog_version: katalogVersi, katalog_sha256_16: katalogHash,
        masa: new Date().toISOString(), ...extra };
}

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

// ⚠️ `provenance()` diisytihar dalam fail ini sejak Codex P2 #1 tetapi TIDAK PERNAH dipanggil,
// jadi artifak kohort ini keluar TANPA provenance — tepat kelemahan yang fungsi itu wujud untuk
// hapuskan. Dipanggil sekarang, sekali, dan dilampirkan pada tulisan berperingkat DAN akhir.
const prov = await provenance({ base_url: BASE, tenant: TENANT, viewport: '390x664' });

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
            // F8 — metrik PENGGANTI yang dicadangkan: adakah popover menutup SASARANNYA
            // SENDIRI? Itu perkara yang pengguna sebenarnya perlu lihat. `centerCovered`
            // hanya bertanya sama ada popover menyentuh titik tengah viewport, yang pada
            // skrin 664px ditepati oleh mana-mana popover yang diletak DI ATAS sasarannya —
            // iaitu susun atur yang BETUL (bukti visual §3B). Kedua-duanya diukur di sini
            // supaya perbandingan dibuat pada larian yang SAMA, bukan merentas larian.
            const el = document.querySelector('.driver-active-element');
            const t = el?.getBoundingClientRect();
            const bertindih = t
                ? !(b.right <= t.left || b.left >= t.right || b.bottom <= t.top || b.top >= t.bottom)
                : null;
            const luasT = t ? Math.max(0, t.width) * Math.max(0, t.height) : 0;
            const luasTindih = t && bertindih
                ? Math.max(0, Math.min(b.right, t.right) - Math.max(b.left, t.left))
                  * Math.max(0, Math.min(b.bottom, t.bottom) - Math.max(b.top, t.top))
                : 0;
            return {
                adaPopover: true,
                centerCovered: b.left <= cx && b.right >= cx && b.top <= cy && b.bottom >= cy,
                popSize: { w: Math.round(b.width), h: Math.round(b.height), left: Math.round(b.left), top: Math.round(b.top) },
                target: el?.getAttribute('data-help-target') ?? null,
                targetRect: t ? { w: Math.round(t.width), h: Math.round(t.height), left: Math.round(t.left), top: Math.round(t.top) } : null,
                menutupSasaran: bertindih,
                peratusSasaranTertutup: luasT > 0 ? Math.round((luasTindih / luasT) * 100) : null,
                sasaranDalamViewport: t ? (t.top < innerHeight && t.bottom > 0 && t.left < innerWidth && t.right > 0) : null,
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
            provenance: prov,
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

// ── METRIK PENGGANTI yang dicadangkan, diukur pada larian yang SAMA ─────────────────────────
// Keputusan pemilik #1 sebelum ini bersandar pada satu tangkapan skrin. Di sini ia diukur
// merentas kohort penuh supaya "gantikan centerCovered dengan penjaga sasaran-sendiri" menjadi
// cadangan BERANGKA dan bukan cadangan berdasarkan mekanisme.
const adaSasaran = hasil.filter((h) => h.adaPopover && h.targetRect);
const menutup = adaSasaran.filter((h) => h.menutupSasaran);
const teruk = menutup.filter((h) => (h.peratusSasaranTertutup ?? 0) >= 50);
const luarViewport = adaSasaran.filter((h) => h.sasaranDalamViewport === false);
console.log(`\n── METRIK PENGGANTI: popover menutup SASARANNYA SENDIRI ──`);
console.log(`  langkah dgn rect sasaran : ${adaSasaran.length}/${hasil.length}`);
console.log(`  menutup sasaran (apa2)   : ${menutup.length}`);
console.log(`  menutup >=50% sasaran    : ${teruk.length}`);
for (const h of teruk) console.log(`     ${h.key.padEnd(26)} ${h.peratusSasaranTertutup}% target=${h.target}`);
console.log(`  sasaran di LUAR viewport : ${luarViewport.length}  ${luarViewport.map((h) => h.key).slice(0, 6).join(', ')}`);
// Silang-jadual: berapa banyak yang centerCovered TETAPI sasarannya kelihatan penuh?
const palsu = hasil.filter((h) => h.centerCovered && h.targetRect && !h.menutupSasaran);
console.log(`\n  ⭐ centerCovered=TRUE tetapi sasaran TIDAK terlindung : ${palsu.length}/${kini.length}`);
console.log(`     (setiap satu = susun atur baik yang metrik lama tandakan MERAH)`);

writeFileSync('Audit Review Round Robin/bukti/plan-f8/mobile-kohort-f8.json', JSON.stringify({
    provenance: prov,
    viewport: { width: 390, height: 664 },
    nota: 'audit = produksi tenant smoke; kini = tempatan tenant mam, benih demo',
    audit_centercovered: dahuluCc.length, kini_centercovered: kini.length,
    pulih: pulih.map((h) => h.key), baharu: baharu.map((h) => h.key),
    tiada_popover: tiadaPopover.map((h) => h.key),
    metrik_pengganti: {
        definisi: 'popover bertindih dengan rect .driver-active-element (sasarannya sendiri)',
        langkah_dgn_rect_sasaran: adaSasaran.length,
        menutup_sasaran: menutup.length,
        menutup_50pc_atau_lebih: teruk.map((h) => ({ key: h.key, peratus: h.peratusSasaranTertutup })),
        sasaran_luar_viewport: luarViewport.map((h) => h.key),
        centercovered_tetapi_sasaran_selamat: palsu.map((h) => h.key),
    },
    hasil,
}, null, 2) + '\n');
await pelayar.close();
