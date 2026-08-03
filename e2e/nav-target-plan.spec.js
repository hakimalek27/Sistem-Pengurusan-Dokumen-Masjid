// F5c (PELAN-PEMBAIKAN §6.5 #7) — peraturan pemilihan sasaran navigasi diuji sebagai FUNGSI
// TULEN. Modul diimport terus sebagai Node ESM (package.json "type":"module"), jadi tiada
// bundler baharu dan TIADA hook ujian dalam bundle produksi (kontrak C11).
//
// Kriteria §6.5 #7 yang dikunci di sini: `nav-primary` menyelesai kepada `nav-sidebar` bila
// sidebar kelihatan, kepada `nav-menu-toggle` bila tidak, dan **TIADA** keadaan yang
// memulangkan `null` atau sasaran generik (`page-content`/`page-primary`/`main`).
import { expect, test } from '@playwright/test';
import { NAV_CANDIDATES, NAV_PRIMARY, navPrimaryTarget } from '../resources/js/help/nav-target-plan.js';

/** Deps: hanya senarai `nampak` yang kelihatan. */
const nampak = (...visible) => (candidate) => visible.includes(candidate);

test('desktop: sidebar kelihatan → nav-sidebar', () => {
    expect(navPrimaryTarget(nampak('nav-sidebar', 'nav-bar'))).toBe('nav-sidebar');
});

test('mobile: sidebar tersembunyi di sebalik menu → nav-menu-toggle', () => {
    // Inilah keadaan yang dahulu memulangkan null → target_missing → "Tindakan belum
    // tersedia" (C13): `sidebar` bukan ahli GENERIC_TARGETS, jadi tiada fallback berjalan.
    expect(navPrimaryTarget(nampak('nav-menu-toggle', 'nav-bar'))).toBe('nav-menu-toggle');
});

test('keutamaan: bila KEDUA-DUA kelihatan, sidebar menang', () => {
    expect(navPrimaryTarget(nampak('nav-sidebar', 'nav-menu-toggle', 'nav-bar')))
        .toBe('nav-sidebar');
});

test('bar navigasi sahaja kelihatan → nav-bar', () => {
    expect(navPrimaryTarget(nampak('nav-bar'))).toBe('nav-bar');
});

test('TIADA calon kelihatan → tetap memulangkan sasaran navigasi, bukan null', () => {
    const hasil = navPrimaryTarget(nampak());

    expect(hasil).toBe('nav-bar');
    expect(hasil).not.toBeNull();
});

test('setiap kombinasi keterlihatan memulangkan calon navigasi sah — 0 null, 0 generik', () => {
    const GENERIK = ['page-content', 'page-primary', 'main', 'MAIN', NAV_PRIMARY];

    // 2^3 = 8 kombinasi keterlihatan bagi tiga calon: liputan menyeluruh, bukan sampel.
    for (let mask = 0; mask < 1 << NAV_CANDIDATES.length; mask += 1) {
        const visible = NAV_CANDIDATES.filter((_, i) => mask & (1 << i));
        const hasil = navPrimaryTarget(nampak(...visible));

        expect(hasil, `mask=${mask} visible=[${visible}]`).not.toBeNull();
        expect(NAV_CANDIDATES, `mask=${mask}`).toContain(hasil);
        expect(GENERIK, `mask=${mask} tidak boleh generik`).not.toContain(hasil);
    }
});

test('calon berada dalam urutan keutamaan desktop → mobile → penambat', () => {
    expect(NAV_CANDIDATES).toEqual(['nav-sidebar', 'nav-menu-toggle', 'nav-bar']);
    expect(NAV_PRIMARY).toBe('nav-primary');
});
