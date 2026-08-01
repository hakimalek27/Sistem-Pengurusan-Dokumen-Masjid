// AUDIT PRODUKSI bakwim.my — 25 halaman tenant × desktop + mobile, dengan data tour penuh.
// Baca-sahaja pada tenant UJIAN `smoke`. Tiada mutasi.
import { chromium, devices } from 'playwright';
import fs from 'node:fs';

const BASE = 'https://bakwim.my';
const OUT = process.env.AUDIT_OUT;
fs.mkdirSync(OUT, { recursive: true });
const MAGIC = JSON.parse(fs.readFileSync(process.env.MAGIC_FILE, 'utf8'));
const log = []; const say = (s) => { console.log(s); log.push(s); };
const hasil = [];

const HALAMAN = [
  ['Papan pemuka', '/app/smoke'],
  ['Log Akses Sulit', '/app/smoke/sensitive-access-logs'],
  ['Analitik Bantuan', '/app/smoke/analitik-bantuan'],
  ['Tiket Sokongan', '/app/smoke/tiket-sokongan'],
  ['Pusat Bantuan', '/app/smoke/bantuan'],
  ['Persediaan Berpandu', '/app/smoke/persediaan'],
  ['Ahli & Peranan', '/app/smoke/ahli-peranan'],
  ['Klasifikasi Fail', '/app/smoke/classification-nodes'],
  ['Pelupusan', '/app/smoke/pelupusan'],
  ['Peraturan Retensi', '/app/smoke/retensi-peraturan'],
  ['Tetapan Masjid', '/app/smoke/tetapan-masjid'],
  ['Penggunaan & Storan', '/app/smoke/penggunaan'],
  ['Retensi & Pegangan', '/app/smoke/retensi'],
  ['Delegasi', '/app/smoke/delegasi'],
  ['Profil Saya', '/app/smoke/profil'],
  ['Peti Masuk', '/app/smoke/peti-masuk'],
  ['Rekod', '/app/smoke/records'],
  ['Fail', '/app/smoke/registry-files'],
  ['Minit Saya', '/app/smoke/minit-saya'],
  ['Kelulusan', '/app/smoke/kelulusan'],
  ['Carian', '/app/smoke/carian'],
  ['Kegemaran', '/app/smoke/kegemaran'],
  ['Laporan', '/app/smoke/laporan'],
  ['Pembetulan Rekod', '/app/smoke/pembetulan-rekod'],
  ['Log Aktiviti Masjid', '/app/smoke/log-aktiviti'],
];

const periksa = (page) => page.evaluate(() => {
  const pop = document.querySelector('.driver-popover');
  const h = document.querySelector('.driver-active-element');
  const r = h?.getBoundingClientRect();
  const rt = document.querySelector('[data-diwan-help-runtime]');
  const de = document.documentElement;
  const tajuk = pop?.querySelector('.driver-popover-title')?.textContent?.trim() || null;
  const huraian = pop?.querySelector('.diwan-tour-instruction')?.textContent?.trim() || null;
  return {
    h1: document.querySelector('h1,.fi-header-heading')?.textContent?.trim()?.slice(0, 60) || null,
    baris: document.querySelectorAll('table tbody tr').length,
    lajur: [...document.querySelectorAll('table thead th')].map((e) => e.innerText.trim()).filter(Boolean).slice(0, 9),
    aksi: [...new Set([...document.querySelectorAll('.fi-header button,.fi-header a,.fi-ac button,.fi-ac a')].map((e) => e.innerText.trim()).filter(Boolean))].slice(0, 8),
    overflowX: de.scrollWidth > window.innerWidth + 1,
    guideId: rt?.dataset.guideId || null,
    autoStart: rt?.dataset.autoStart,
    helpUrl: rt?.dataset.helpUrl,
    tour: pop ? {
      langkah: pop.querySelector('.driver-popover-progress-text')?.textContent?.trim(),
      tajuk, huraian,
      tajukSamaHuraian: !!(tajuk && huraian && huraian.replace(/\.$/, '') === tajuk.replace(/\.\.\.$/, '').replace(/\.$/, '')),
      tajukTerpotong: !!(tajuk && tajuk.endsWith('...')),
      btn: pop.querySelector('.driver-popover-next-btn')?.textContent?.trim(),
      sorot: h?.getAttribute('data-help-target') || h?.tagName,
      saiz: r ? `${Math.round(r.width)}x${Math.round(r.height)}` : null,
      generik: (h?.getAttribute('data-help-target') === 'page-content') || h?.tagName === 'MAIN',
      keluarSkrin: (() => { const pr = pop.getBoundingClientRect(); return pr.right > window.innerWidth + 2 || pr.bottom > window.innerHeight + 2 || pr.left < -2 || pr.top < -2; })(),
    } : null,
  };
});

const browser = await chromium.launch({ channel: 'chrome', headless: true });

for (const [vp, opts, token] of [
  ['desktop', { viewport: { width: 1440, height: 900 } }, MAGIC.desktop],
  ['mobile', { ...devices['iPhone 13'] }, MAGIC.mobile],
]) {
  const ctx = await browser.newContext({ ...opts });
  const page = await ctx.newPage();
  await page.goto(token, { waitUntil: 'networkidle', timeout: 60000 });
  await page.waitForTimeout(3000);
  if (page.url().includes('/masuk') || page.url().includes('/log-masuk')) { say(`[${vp}] ❌ LOG MASUK GAGAL: ${page.url()}`); await ctx.close(); continue; }
  say(`\n===== PRODUKSI [${vp}] log masuk OK → ${new URL(page.url()).pathname} =====`);

  for (const [nama, path] of HALAMAN) {
    const errs = []; const bad = [];
    const oe = (e) => errs.push('pageerror: ' + e.message.slice(0, 110));
    const oc = (m) => { if (m.type() === 'error') errs.push('console: ' + m.text().slice(0, 110)); };
    const orr = (r) => { if (r.status() >= 400) bad.push(r.status() + ' ' + r.url().replace(BASE, '').slice(0, 70)); };
    page.on('pageerror', oe); page.on('console', oc); page.on('response', orr);

    let st = null;
    try { const r = await page.goto(BASE + path, { waitUntil: 'networkidle', timeout: 45000 }); st = r?.status(); await page.waitForTimeout(3200); }
    catch (e) { errs.push('goto: ' + e.message.slice(0, 90)); }

    const info = await periksa(page).catch((e) => ({ ralatEval: e.message }));
    await page.screenshot({ path: `${OUT}/${vp}-${path.replace(/\W+/g, '_')}.png`, fullPage: true }).catch(() => {});
    hasil.push({ vp, nama, path, st, ...info, errs, bad });

    const f = [];
    if (st !== 200) f.push('STATUS=' + st);
    if (info.overflowX) f.push('OVERFLOW-X');
    if (errs.length) f.push('JS:' + errs.length);
    if (bad.length) f.push('HTTP:' + bad.join('|'));
    if (!info.guideId) f.push('guideId-HILANG');
    if (info.helpUrl?.includes('livewire')) f.push('helpUrl-ROSAK');
    if (info.tour) {
      f.push(`TOUR[${info.tour.langkah}] btn="${info.tour.btn}" sorot=${info.tour.sorot}(${info.tour.saiz})${info.tour.generik ? ' GENERIK' : ''}${info.tour.tajukSamaHuraian ? ' TAJUK=HURAIAN' : ''}${info.tour.tajukTerpotong ? ' TAJUK-TERPOTONG' : ''}${info.tour.keluarSkrin ? ' POPOVER-KELUAR-SKRIN' : ''}`);
    }
    say(`  ${String(st).padEnd(4)} ${nama.padEnd(22)} baris=${String(info.baris ?? '-').padStart(3)} ${f.join(' ; ')}`);
    page.off('pageerror', oe); page.off('console', oc); page.off('response', orr);
  }

  // probe silang tenant ke tenant SEBENAR mamad (baca sahaja)
  for (const p of ['/app/mamad', '/app/mamad/records', '/app/mamad/peti-masuk']) {
    const r = await page.goto(BASE + p, { waitUntil: 'domcontentloaded' }).catch(() => null);
    say(`  [silang-tenant] ${p} → ${r?.status() ?? 'ERR'}`);
    hasil.push({ vp, nama: 'SILANG-TENANT', path: p, st: r?.status() ?? null });
  }
  await ctx.close();
}

await browser.close();
fs.writeFileSync(`${OUT}/produksi-audit.json`, JSON.stringify(hasil, null, 2));
fs.writeFileSync(`${OUT}/produksi-audit.txt`, log.join('\n'));
console.log('\nDisimpan: ' + OUT);
