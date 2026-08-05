// F6-W4 (PELAN-PEMBAIKAN §7.2 langkah 2) — peraturan pemetaan sasaran PER HALAMAN diuji
// sebagai FUNGSI TULEN. Modul diimport terus sebagai Node ESM, jadi tiada bundler baharu dan
// TIADA hook ujian dalam bundle produksi (kontrak C11, sama seperti nav-target-plan).
//
// Yang dikunci di sini ialah invarian yang menyebabkan kerosakan sebenar bila dilanggar:
//   1. satu selector tidak boleh dipetakan kepada DUA sasaran (satu elemen = satu atribut);
//   2. pemetaan tidak boleh bocor ke halaman lain (`records-search` pada `/log-aktiviti`);
//   3. laluan BUTIRAN tidak dipadankan (medan carian tidak wujud di sana);
//   4. setiap sasaran yang diisytihar mesti ada dalam registri `targets.json`.
import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { PAGE_TARGETS, pageTargetsFor } from '../resources/js/help/page-target-plan.js';

const sasaranBagi = (pathname) => pageTargetsFor(pathname).map(([, target]) => target).sort();

test('setiap halaman yang dipetakan memulangkan sasaran yang betul', () => {
    expect(sasaranBagi('/app/mam/records')).toEqual(['records-search']);
    expect(sasaranBagi('/app/mam/log-aktiviti')).toEqual(['log-filters', 'log-search']);
    expect(sasaranBagi('/app/mam/minit-saya')).toEqual(['minit-filters']);
    expect(sasaranBagi('/app/mam/registry-files')).toEqual(['regfiles-search']);
});

test('garis condong hujung tidak mengubah keputusan', () => {
    expect(sasaranBagi('/app/mam/records/')).toEqual(['records-search']);
    expect(sasaranBagi('/app/smoke/log-aktiviti/')).toEqual(['log-filters', 'log-search']);
});

test('slug tenant lain dipadankan sama', () => {
    expect(sasaranBagi('/app/masjid-lain-123/records')).toEqual(['records-search']);
});

test('halaman BUTIRAN tidak dipadankan — medan carian tidak wujud di sana', () => {
    expect(pageTargetsFor('/app/mam/records/12')).toEqual([]);
    expect(pageTargetsFor('/app/mam/registry-files/3')).toEqual([]);
});

test('halaman tanpa pemetaan memulangkan kosong', () => {
    for (const laluan of ['/app/mam', '/app/mam/peti-masuk', '/app/mam/carian', '/admin', '/log-masuk', '/']) {
        expect(pageTargetsFor(laluan), `${laluan} tidak sepatutnya dipetakan`).toEqual([]);
    }
});

test('TIADA sasaran bocor antara halaman — setiap sasaran milik satu laluan sahaja', () => {
    const pemilik = new Map();
    for (const entri of PAGE_TARGETS) {
        for (const [, target] of entri.peta) {
            expect(pemilik.has(target), `${target} diisytihar pada lebih daripada satu laluan`).toBe(false);
            pemilik.set(target, entri.route);
        }
    }
    expect(pemilik.size).toBe(5);
});

test('dalam SATU halaman, satu selector tidak boleh memegang dua sasaran', () => {
    // Satu elemen hanya boleh memegang satu `data-help-target`. Pelanggaran di sini ialah
    // punca sebenar ribut mutasi F5c, bukan kesilapan gaya.
    for (const entri of PAGE_TARGETS) {
        const selectors = entri.peta.map(([selector]) => selector);
        expect(new Set(selectors).size, `${entri.route} memetakan selector yang sama dua kali`)
            .toBe(selectors.length);
    }
});

test('setiap sasaran yang dipetakan wujud dalam registri targets.json', () => {
    const registri = JSON.parse(readFileSync('resources/help/targets.json', 'utf8')).targets;
    const ikutId = new Map(registri.map((t) => [t.id, t]));

    for (const entri of PAGE_TARGETS) {
        for (const [, target] of entri.peta) {
            const rekod = ikutId.get(target);
            expect(rekod, `${target} tiada dalam registri`).toBeTruthy();
            expect(rekod.route, `${target}: route registri tidak sepadan pemetaan`).toBe(entri.route);
            expect(rekod.owner_source, `${target}: owner_source mesti menunjuk decorateTargets`)
                .toContain('js:decorateTargets');
        }
    }
});
