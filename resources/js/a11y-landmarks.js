/**
 * F7 §8.2 (RR-04-01, axe `landmark-unique` moderate) — namakan dua landmark `nav` Filament.
 *
 * Setiap halaman panel merender DUA `<nav>` tanpa nama: `.fi-topbar` dan sidebar. axe
 * menuntut landmark jenis sama mempunyai nama yang membezakannya.
 *
 * ⚠️ Entri BERASINGAN, bukan sebahagian `help.js` — dan itu bukan kosmetik (§8.2 C18):
 *   (a) `DIWAN_GUIDANCE_ENABLED=false` ialah suis sedia ada yang boleh diperluas kelak untuk
 *       menggugurkan aset panduan sepenuhnya;
 *   (b) sebarang `throw` awal dalam `help.js` (cth. import `driver.js` gagal) akan
 *       MENDIAMKAN label landmark bersama-sama;
 *   (c) kebolehcapaian bukan ciri pilihan.
 * Ujian `A11yLandmarksTest` menjalankan halaman dengan `DIWAN_GUIDANCE_ENABLED=false` untuk
 * membuktikan pemisahan itu benar-benar berlaku, bukan sekadar diisytiharkan di sini.
 *
 * Tiada import: modul ini mesti kekal boleh dimuat walaupun setiap dependensi lain gagal.
 *
 * Had yang diakui (kekal, §8.2): ini pembaikan sisi-pelanggan. axe dijalankan atas DOM hidup
 * jadi ia lulus; pembaikan "betul" (PR upstream Filament yang merender `aria-label` dari
 * pelayan) berada di luar skop.
 */

const LABEL = [
    ['.fi-topbar nav, nav.fi-topbar', 'Navigasi atas'],
    ['.fi-sidebar nav, nav.fi-sidebar', 'Navigasi utama'],
];

function namakanLandmark() {
    for (const [pemilih, label] of LABEL) {
        for (const el of document.querySelectorAll(pemilih)) {
            // Idempoten: jangan tulis semula jika sudah bernama. Menulis atribut pada setiap
            // panggilan menghasilkan ribut MutationObserver — punca sebenar kegagalan F5c.
            if (!el.hasAttribute('aria-label')) el.setAttribute('aria-label', label);
        }
    }
}

document.addEventListener('DOMContentLoaded', namakanLandmark);
document.addEventListener('livewire:navigated', namakanLandmark);
namakanLandmark();
