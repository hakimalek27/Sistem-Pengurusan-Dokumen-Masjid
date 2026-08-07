// F7 §8.4/§8.5 (RR-08-05) — kontrak keadaan kawalan viewer, fungsi TULEN (tiada pelayar).
//
// Projek `unit`: ~1s, tiada pelayan, tiada DB. Ia mengunci SETIAP keadaan yang §8.5 senaraikan:
// dokumen 1 halaman, 3 halaman, had zum, keadaan memuat, keadaan ralat, dan imej bukan-PDF.
import { expect, test } from '@playwright/test';
import { viewerControlState, ZOOM_MAX, ZOOM_MIN } from '../resources/js/viewer-control-plan.js';

const PDF = 'application/pdf';

test('memuat: SEMUA kawalan dikunci sebelum dokumen tiba', () => {
    const k = viewerControlState({ type: PDF, pdfLoaded: false, numPages: 0 });

    expect(k).toEqual({
        prev: true, next: true, pageInput: true, find: true, zoomOut: true, zoomIn: true,
    });
});

test('ralat muat: kawalan KEKAL dikunci walaupun objek pdf wujud', () => {
    const k = viewerControlState({ type: PDF, pdfLoaded: true, failed: true, numPages: 3, pageNumber: 2 });

    expect(k.prev).toBe(true);
    expect(k.next).toBe(true);
    expect(k.find).toBe(true);
    expect(k.zoomIn).toBe(true);
    expect(k.zoomOut).toBe(true);
});

test('dokumen 1 halaman: prev DAN next kedua-duanya disabled', () => {
    const k = viewerControlState({ type: PDF, pdfLoaded: true, numPages: 1, pageNumber: 1 });

    expect(k.prev).toBe(true);
    expect(k.next).toBe(true);
    // Tetapi cari dan zum mesti HIDUP — dokumen satu halaman masih boleh dicari dan dizum.
    expect(k.find).toBe(false);
    expect(k.pageInput).toBe(false);
});

test('dokumen 3 halaman: had pada halaman pertama dan terakhir sahaja', () => {
    const pada = (pageNumber) => viewerControlState({ type: PDF, pdfLoaded: true, numPages: 3, pageNumber });

    expect(pada(1).prev).toBe(true);
    expect(pada(1).next).toBe(false);

    expect(pada(2).prev).toBe(false);
    expect(pada(2).next).toBe(false);

    expect(pada(3).prev).toBe(false);
    expect(pada(3).next).toBe(true);
});

test('had zum: minimum dan maksimum mengunci butang yang berkenaan sahaja', () => {
    const pada = (scale) => viewerControlState({ type: PDF, pdfLoaded: true, numPages: 3, pageNumber: 2, scale });

    expect(pada(ZOOM_MIN).zoomOut).toBe(true);
    expect(pada(ZOOM_MIN).zoomIn).toBe(false);

    expect(pada(ZOOM_MAX).zoomIn).toBe(true);
    expect(pada(ZOOM_MAX).zoomOut).toBe(false);

    expect(pada(1.25).zoomIn).toBe(false);
    expect(pada(1.25).zoomOut).toBe(false);
});

test('imej bukan-PDF: zum HIDUP, kawalan halaman dikunci', () => {
    const k = viewerControlState({ type: 'image/png', pdfLoaded: false, numPages: 0 });

    // Zum imej berfungsi melalui transform CSS — mengunci ia adalah regresi.
    expect(k.zoomIn).toBe(false);
    expect(k.zoomOut).toBe(false);

    expect(k.prev).toBe(true);
    expect(k.next).toBe(true);
    expect(k.find).toBe(true);
    expect(k.pageInput).toBe(true);
});

/**
 * ⚠️ Penjaga terhadap perbandingan float yang longgar. Langkah zum ialah 0.25 bermula dari
 * 1.25, jadi had 0.5 dan 3 dicapai TEPAT. Jika sesiapa menukar perbandingan kepada `<`/`>`
 * atau memperkenalkan epsilon yang salah tanda, butang tidak akan pernah terkunci pada had.
 */
test('turutan zum sebenar mencapai kedua-dua had dengan TEPAT', () => {
    const langkah = [];
    let scale = 1.25;
    for (let i = 0; i < 3; i += 1) {
        scale = Math.min(Math.max(scale - 0.25, ZOOM_MIN), ZOOM_MAX);
        langkah.push(scale);
    }
    expect(langkah.at(-1)).toBe(ZOOM_MIN);
    expect(viewerControlState({ type: PDF, pdfLoaded: true, numPages: 1, scale: langkah.at(-1) }).zoomOut).toBe(true);

    scale = 1.25;
    for (let i = 0; i < 7; i += 1) {
        scale = Math.min(Math.max(scale + 0.25, ZOOM_MIN), ZOOM_MAX);
    }
    expect(scale).toBe(ZOOM_MAX);
    expect(viewerControlState({ type: PDF, pdfLoaded: true, numPages: 1, scale }).zoomIn).toBe(true);
});
