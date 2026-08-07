/**
 * F7 §8.4 (RR-08-05) — keadaan kawalan viewer dokumen sebagai fungsi TULEN.
 *
 * Corak yang sama seperti `help/step-advance-plan.js`: keputusan diasingkan daripada DOM
 * supaya ia boleh diuji tanpa pelayar, dan supaya hanya ADA SATU tempat yang menentukan
 * bila setiap butang disabled. Sebelum ini keputusan itu tersebar (atau tiada langsung) dan
 * butang kekal boleh ditekan pada had, semasa memuat, dan selepas ralat.
 *
 * Nilai float: 0.25, 0.5, 1.25 dan 3 kesemuanya pecahan binari TEPAT, jadi `<=` / `>=`
 * selamat tanpa epsilon. Pemanggil bertanggungjawab menjepit `scale` (Math.min/max) supaya
 * ia tidak pernah melepasi had.
 */

export const ZOOM_MIN = 0.5;
export const ZOOM_MAX = 3;

/**
 * @param {object} keadaan
 * @param {string}  keadaan.type        mime dokumen (`application/pdf` atau `image/*`)
 * @param {boolean} keadaan.pdfLoaded   dokumen PDF sudah termuat
 * @param {boolean} keadaan.failed      muatan gagal (kekal terkunci)
 * @param {number}  keadaan.pageNumber  halaman semasa (1-asas)
 * @param {number}  keadaan.numPages    jumlah halaman (0 jika belum diketahui)
 * @param {number}  keadaan.scale       faktor zum semasa
 * @returns {{prev: boolean, next: boolean, pageInput: boolean, find: boolean,
 *            zoomOut: boolean, zoomIn: boolean}} true = DISABLED
 */
export function viewerControlState({
    type = '',
    pdfLoaded = false,
    failed = false,
    pageNumber = 1,
    numPages = 0,
    scale = 1.25,
} = {}) {
    const pdfDijangka = type === 'application/pdf';
    const pdfSedia = pdfDijangka && pdfLoaded && !failed;

    // Zum berfungsi untuk IMEJ juga (transform CSS), jadi ia hanya dikunci semasa PDF sedang
    // dimuat atau selepas ralat — bukan untuk dokumen bukan-PDF.
    const zumDikunci = pdfDijangka && !pdfSedia;

    return {
        prev: !pdfSedia || pageNumber <= 1,
        next: !pdfSedia || pageNumber >= numPages,
        pageInput: !pdfSedia,
        find: !pdfSedia,
        zoomOut: zumDikunci || scale <= ZOOM_MIN,
        zoomIn: zumDikunci || scale >= ZOOM_MAX,
    };
}
