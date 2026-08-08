// F8 §9 — ukur SEMULA tiga metrik KANDUNGAN kohort pada RUNTIME, apple-to-apple.
//
// 🔴 Mengapa skrip ini wujud: `metrik-f8.mjs` mengira `title == instruction` daripada KATALOG
// dan memberi 0 pada kedua-dua belah — jadi ia tidak boleh menunjukkan pergerakan. Ditentukur
// dan gagal: pada commit audit `4e07a70`, katalog memberi 0 sedangkan asas audit ialah 77.
//
// Puncanya: asas audit ialah ukuran RUNTIME. Pada `4e07a70`, **118/124** tajuk langkah kohort
// ialah placeholder `"Langkah N"`, jadi tour MENERBITKAN tajuk daripada arahan — dan pada
// popover, `title == description` untuk 77 langkah. Katalog tidak boleh menunjukkannya.
//
// Sisi asas DITENTUKUR TEPAT daripada data audit sendiri
// (`pusingan-11-codex/production-desktop-all-tour-steps.json`):
//     title == description 77 · tajuk terpotong 20 · CTA "Buat pada skrin" 20
// Ketiga-tiganya dihasilkan semula dengan definisi di bawah. Maka definisi itu betul, dan
// sisi SEMASA mesti diukur dengan definisi yang SAMA pada popover sebenar.
//
// Guna: node "Audit Review Round Robin/bukti/plan-f8/skrip/ukur-runtime-kohort-f8.mjs"
//   (perlu pelayan tempatan pada :8092 + benih demo)

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
const KELUAR = 'Audit Review Round Robin/bukti/plan-f8/runtime-kohort-f8.json';

const manifest = JSON.parse(readFileSync('Audit Review Round Robin/bukti/plan-baseline/manifest.json', 'utf8'));
const kohort = manifest.catalogue
    .filter((g) => g.family === 'tenant')
    .flatMap((g) => g.steps.map((s) => ({ key: s.key, guide: g.guide_id, index: s.index, route: s.route || g.route })));

// Definisi audit, disahkan menghasilkan semula 77/20/20 pada datanya sendiri.
const bersih = (t) => String(t ?? '').trim().replace(/[.!?]+$/, '').toLowerCase();
const terpotong = (t) => /(…|\.\.\.)$/.test(String(t ?? '').trim());

const pelayar = await chromium.launch();
// Audit mengukur DESKTOP untuk metrik kandungan ini.
const k = await pelayar.newContext({ viewport: { width: 1440, height: 1000 } });
await k.addInitScript(() => { try { localStorage.setItem('diwan-help-mode', 'dimatikan'); } catch { /* noop */ } });
const p = await k.newPage();

await p.goto(`${BASE}/app/login`);
await p.locator('input[id="form.login"]').fill('admin_masjid@demo.test');
await p.locator('input[type="password"]').fill(KATA);
await p.getByRole('button', { name: /Log masuk/i }).click();
await p.waitForURL((u) => u.pathname.replace(/\/$/, '') === `/app/${TENANT}`, { timeout: 90_000 });
console.log(`log masuk OK · ${kohort.length} langkah kohort · desktop 1440x1000\n`);

// BOLEH SAMBUNG: had hayat tugas latar membunuh larian ini dua kali pada 20/124 dan 40/124.
// Hasil sebelumnya dimuat semula dan langkah yang sudah diukur dilangkau, jadi larian boleh
// dipecahkan kepada beberapa bahagian pendek tanpa kehilangan kerja.
let hasil = [];
try {
    const lama = JSON.parse(readFileSync(KELUAR, 'utf8'));
    hasil = (lama.hasil ?? []).filter((h) => h.adaPopover);   // ulang yang gagal
    console.log(`sambung: ${hasil.length} langkah sudah diukur, dilangkau\n`);
} catch { /* larian pertama */ }
const sudah = new Set(hasil.map((h) => h.key));
const HAD = Number(process.env.AB_HAD || 0);   // 0 = semua
let dibuat = 0;

for (const s of kohort) {
    if (sudah.has(s.key)) continue;
    if (HAD && dibuat >= HAD) {
        console.log(`had ${HAD} dicapai — jalankan semula untuk menyambung`);
        break;
    }
    dibuat += 1;
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
            const btn = [...pop.querySelectorAll('button')]
                .map((b) => (b.textContent || '').trim())
                .filter((t) => t && !/^[×✕✖]$/.test(t));
            return {
                adaPopover: true,
                title: pop.querySelector('.driver-popover-title')?.textContent?.trim() ?? '',
                // 🔴 Codex P2 #3: `.driver-popover-description` mengandungi arahan PLUS
                // boilerplate — hint ("Baca penerangan ini, kemudian tekan Seterusnya"), baris
                // status, dan pautan "Buka panduan penuh". Membandingkan tajuk dengannya
                // menjadikan `title == description` hampir MUSTAHIL, jadi `0` sebahagiannya
                // dijamin oleh STRUKTUR, bukan oleh kualiti kandungan.
                // Arahan teras hidup dalam `p.diwan-tour-instruction` — itulah yang audit
                // bandingkan (rekod auditnya membawa arahan sahaja, tanpa boilerplate).
                description: pop.querySelector('.diwan-tour-instruction')?.textContent?.trim()
                    ?? pop.querySelector('.driver-popover-description')?.textContent?.trim() ?? '',
                description_penuh: pop.querySelector('.driver-popover-description')?.textContent?.trim() ?? '',
                button: btn.join(' | '),
            };
        });
    } catch (e) {
        r = { adaPopover: false, ralat: String(e.message).split('\n')[0].slice(0, 70) };
    }
    hasil.push({ ...s, ...r });
    // Kemajuan dikira daripada `hasil.length`, bukan `i` — dengan sambungan, `i` melompat.
    if (hasil.length % 10 === 0 || hasil.length === kohort.length) {
        console.log(`  … ${hasil.length}/${kohort.length}`);
        writeFileSync(KELUAR, JSON.stringify({
            lengkap: hasil.length === kohort.length, diukur: hasil.length, hasil,
        }, null, 2) + '\n');
    }
}
writeFileSync(KELUAR, JSON.stringify({
    lengkap: hasil.length === kohort.length, diukur: hasil.length, hasil,
}, null, 2) + '\n');

const ada = hasil.filter((h) => h.adaPopover);
const sama = ada.filter((h) => bersih(h.title) === bersih(h.description));
const potong = ada.filter((h) => terpotong(h.title));
const cta = ada.filter((h) => (h.button || '').includes('Buat pada skrin'));
const placeholder = ada.filter((h) => /^Langkah \d+$/.test(h.title));

console.log('\n── RUNTIME kohort, definisi audit yang SAMA ──');
console.log(`  popover dirender      : ${ada.length}/${hasil.length}`);
console.log(`  title == description  : ${sama.length}   (asas audit 77)`);
console.log(`  tajuk terpotong       : ${potong.length}   (asas audit 20)`);
console.log(`  CTA "Buat pada skrin" : ${cta.length}   (asas audit 20)`);
console.log(`  placeholder "Langkah N": ${placeholder.length}   (asas audit 118 dlm katalog)`);
if (sama.length) console.log('  contoh title==description:', sama.slice(0, 5).map((h) => h.key).join(', '));

writeFileSync(KELUAR, JSON.stringify({
    provenance: await provenance({ tenant: TENANT, base_url: BASE, viewport: '1440x1000' }),
    lengkap: hasil.length === kohort.length,
    definisi: 'title==description selepas buang noktah akhir; terpotong = tajuk berakhir elipsis',
    asas_audit: { title_equals_description: 77, truncated: 20, cta_buat_pada_skrin: 20 },
    kini: {
        popover: ada.length, title_equals_description: sama.length,
        truncated: potong.length, cta_buat_pada_skrin: cta.length, placeholder: placeholder.length,
    },
    hasil,
}, null, 2) + '\n');
await pelayar.close();
