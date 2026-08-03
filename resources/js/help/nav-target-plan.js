// F5c (PELAN-PEMBAIKAN §6.3) — peraturan pemilihan sasaran NAVIGASI yang responsif.
//
// Punca (C13): katalog tidak boleh menyasar `sidebar` secara terus. `GENERIC_TARGETS` dalam
// help.js ialah tepat `new Set(['page-content', 'page-primary'])` — `sidebar` TIADA di dalamnya,
// jadi apabila `.fi-sidebar` tidak kelihatan (mobile <64rem, tersembunyi di sebalik ☰)
// `resolveStepElement()` memulangkan `null` → langkah menjadi `target_missing` → mesej palsu
// "Tindakan belum tersedia".
//
// Penyelesaian: katalog menyasar `nav-primary` (sasaran LOGIK). Modul ini memutuskan sasaran
// KONKRIT mana yang digunakan mengikut apa yang kelihatan sekarang. Ia peraturan PEMILIHAN,
// bukan fallback generik: setiap calon ialah elemen navigasi sebenar, jadi tour tidak pernah
// jatuh ke `main`/`page-content` (masalah sorotan-terlalu-besar yang F6 cuba selesaikan).
//
// Modul ini TULEN: tiada DOM, tiada CSS, tiada import. Itu membolehkan ujian mengimportnya
// terus sebagai Node ESM tanpa bundler dan TANPA meninggalkan hook ujian dalam bundle
// produksi (kontrak C11, sama seperti step-advance-plan.js).

/** Sasaran logik yang katalog guna. Bukan `data-help-target` sebenar. */
export const NAV_PRIMARY = 'nav-primary';

/**
 * Calon konkrit mengikut keutamaan. `decorateTargets()` menandakan ketiga-tiganya:
 *   nav-sidebar     → `.fi-sidebar`                    (desktop >=64rem)
 *   nav-menu-toggle → `.fi-topbar-open-sidebar-btn`    (mobile; Filament sembunyikan >=64rem)
 *   nav-bar         → `.fi-topbar`                     (sentiasa dirender — penambat terakhir)
 */
export const NAV_CANDIDATES = ['nav-sidebar', 'nav-menu-toggle', 'nav-bar'];

/**
 * Pilih sasaran navigasi konkrit yang pertama kelihatan.
 *
 * Penambat terakhir `nav-bar` dipulangkan walaupun tiada calon kelihatan supaya fungsi ini
 * TIDAK PERNAH memulangkan `null` (§6.5 #7). `.fi-topbar` ialah `<nav>` yang dirender tanpa
 * syarat oleh Filament, jadi pemulangan itu tetap elemen navigasi sebenar.
 *
 * @param {(target: string) => boolean} isTargetVisible calon itu kelihatan SEKARANG?
 * @returns {string} satu id daripada NAV_CANDIDATES
 */
export function navPrimaryTarget(isTargetVisible) {
    for (const candidate of NAV_CANDIDATES) {
        if (isTargetVisible(candidate)) return candidate;
    }

    return NAV_CANDIDATES[NAV_CANDIDATES.length - 1];
}
