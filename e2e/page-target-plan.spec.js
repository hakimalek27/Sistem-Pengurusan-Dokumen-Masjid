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
    // F6-W5: `/app/{tenant}` dan `/admin` KINI dipetakan (widget statistik papan pemuka),
    // jadi ia dikeluarkan daripada senarai ini dan diassert secara positif di bawah.
    for (const laluan of ['/app/mam/peti-masuk', '/app/mam/carian', '/log-masuk', '/']) {
        expect(pageTargetsFor(laluan), `${laluan} tidak sepatutnya dipetakan`).toEqual([]);
    }
});

test('papan pemuka kedua-dua panel dipetakan kepada widget statistik yang SAMA', () => {
    // Sasaran dikongsi dengan sengaja: `.fi-wi-stats-overview` ialah pembalut yang sama pada
    // kedua-dua panel, dan registri mengisytiharkannya sebagai satu entri dwi-laluan.
    expect(sasaranBagi('/app/mam')).toEqual(['dashboard-stats']);
    expect(sasaranBagi('/admin')).toEqual(['dashboard-stats']);
    expect(sasaranBagi('/admin/')).toEqual(['dashboard-stats']);
    // Sub-laluan panel admin TIDAK boleh terperangkap oleh regex `/admin`.
    expect(sasaranBagi('/admin/mosques')).toEqual(['platform-mosques']);
});

test('sasaran dikongsi hanya bila registri mengisytiharkannya dwi-laluan', () => {
    // Peraturan asal ("satu sasaran = satu laluan") wujud kerana satu ELEMEN hanya boleh
    // memegang satu `data-help-target`. Itu kekal benar. Tetapi dua HALAMAN berbeza boleh
    // berkongsi id sasaran apabila ia elemen yang setara pada kedua-duanya — papan pemuka dua
    // panel, dan jadual tiket yang dikongsi `App\Filament\Support\SupportRequestsTable`.
    //
    // Penjaga DIKETATKAN, bukan dilonggarkan: perkongsian hanya sah jika registri
    // mengisytiharkan TEPAT set laluan itu sebagai `a|b`. Sasaran yang bocor tanpa
    // pengisytiharan tetap gagal.
    const registri = JSON.parse(readFileSync('resources/help/targets.json', 'utf8')).targets;
    const ikutId = new Map(registri.map((t) => [t.id, t]));
    const laluanPemetaan = new Map();

    for (const entri of PAGE_TARGETS) {
        for (const [, target] of entri.peta) {
            if (!laluanPemetaan.has(target)) laluanPemetaan.set(target, []);
            laluanPemetaan.get(target).push(entri.route);
        }
    }

    for (const [target, laluan] of laluanPemetaan) {
        const diisytihar = String(ikutId.get(target)?.route ?? '').split('|').sort();
        expect(laluan.slice().sort(), `${target}: laluan pemetaan tidak sepadan registri`)
            .toEqual(diisytihar);
    }
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
            // Entri dwi-laluan (`a|b`) sah selagi laluan pemetaan ini salah satu daripadanya;
            // kesepadanan SET penuh dikuatkuasakan oleh ujian perkongsian di atas.
            expect(String(rekod.route).split('|'), `${target}: route registri tidak sepadan pemetaan`)
                .toContain(entri.route);
            expect(rekod.owner_source, `${target}: owner_source mesti menunjuk decorateTargets`)
                .toContain('js:decorateTargets');
        }
    }
});
