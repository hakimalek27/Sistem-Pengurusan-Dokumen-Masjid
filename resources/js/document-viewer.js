import * as pdfjsLib from 'pdfjs-dist';
import { viewerControlState } from './viewer-control-plan.js';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

const root = document.querySelector('[data-document-viewer]');

if (root) {
    const type = root.dataset.mime || '';
    const url = root.dataset.url;
    const canvas = root.querySelector('canvas');
    const image = root.querySelector('[data-viewer-image]');
    const pageInput = root.querySelector('[data-page-input]');
    const pageCount = root.querySelector('[data-page-count]');
    const zoomLabel = root.querySelector('[data-zoom-label]');
    const findInput = root.querySelector('[data-find-input]');
    const status = root.querySelector('[data-status]');
    let pdf = null;
    let pageNumber = 1;
    let scale = 1.25;
    let renderTask = null;
    let searchCursor = 0;

    // F7 §8.4 (RR-08-05) — rujukan kawalan diangkat supaya keadaan disabled boleh diselaraskan
    // dari satu tempat. Sebelum ini butang kekal "boleh ditekan" pada had (halaman 1, halaman
    // akhir, zum minimum/maksimum), semasa memuat, dan selepas ralat.
    const prevBtn = root.querySelector('[data-prev]');
    const nextBtn = root.querySelector('[data-next]');
    const zoomOutBtn = root.querySelector('[data-zoom-out]');
    const zoomInBtn = root.querySelector('[data-zoom-in]');
    const findBtn = root.querySelector('[data-find]');
    let gagalMuat = false;

    const setStatus = (message, error = false) => {
        status.textContent = message;
        status.dataset.error = error ? 'true' : 'false';
    };

    /**
     * `aria-disabled` sentiasa SEIRING sifat `disabled` native — jangan salah satu sahaja.
     * Sifat native menghalang interaksi; atribut ARIA menyatakannya kepada pembaca skrin yang
     * mungkin mengumumkan butang sebelum ia dilawati.
     */
    const setDisabled = (el, disabled) => {
        if (!el) return;
        el.disabled = disabled;
        el.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    };

    /**
     * Satu sumber kebenaran untuk keadaan kawalan. Dipanggil selepas SETIAP peralihan:
     * muat, render halaman, tukar zum, dan ralat.
     *
     * Keputusannya hidup dalam modul TULEN `viewer-control-plan.js` supaya ia boleh diuji
     * tanpa pelayar (corak `step-advance-plan.js`); fungsi ini hanya memetakan keputusan itu
     * kepada DOM.
     */
    const syncControls = () => {
        const keadaan = viewerControlState({
            type,
            pdfLoaded: Boolean(pdf),
            failed: gagalMuat,
            pageNumber,
            numPages: pdf?.numPages ?? 0,
            scale,
        });

        setDisabled(prevBtn, keadaan.prev);
        setDisabled(nextBtn, keadaan.next);
        setDisabled(findBtn, keadaan.find);
        setDisabled(zoomOutBtn, keadaan.zoomOut);
        setDisabled(zoomInBtn, keadaan.zoomIn);
        if (pageInput) pageInput.disabled = keadaan.pageInput;
    };

    const renderPage = async (number) => {
        if (!pdf) return;
        pageNumber = Math.min(Math.max(Number(number) || 1, 1), pdf.numPages);
        pageInput.value = pageNumber;
        if (renderTask) renderTask.cancel();
        const page = await pdf.getPage(pageNumber);
        const viewport = page.getViewport({ scale });
        const context = canvas.getContext('2d', { alpha: false });
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);
        renderTask = page.render({ canvasContext: context, viewport });
        try {
            await renderTask.promise;
            setStatus(`Halaman ${pageNumber} dipaparkan.`);
        } catch (error) {
            if (error?.name !== 'RenderingCancelledException') setStatus('Halaman gagal dipaparkan.', true);
        } finally {
            // `finally`, bukan selepas `try`: render yang DIBATALKAN (klik pantas) mesti tetap
            // menyelaraskan kawalan, jika tidak butang tersekat pada keadaan halaman lama.
            syncControls();
        }
    };

    const updateZoom = async (change) => {
        scale = Math.min(Math.max(scale + change, 0.5), 3);
        zoomLabel.textContent = `${Math.round(scale * 100)}%`;
        if (pdf) await renderPage(pageNumber);
        if (image) image.style.transform = `scale(${scale / 1.25})`;
        syncControls();
    };

    prevBtn?.addEventListener('click', () => renderPage(pageNumber - 1));
    nextBtn?.addEventListener('click', () => renderPage(pageNumber + 1));
    zoomOutBtn?.addEventListener('click', () => updateZoom(-0.25));
    zoomInBtn?.addEventListener('click', () => updateZoom(0.25));
    pageInput?.addEventListener('change', () => renderPage(pageInput.value));
    root.querySelector('[data-print]')?.addEventListener('click', () => window.print());

    findBtn?.addEventListener('click', async () => {
        const needle = findInput.value.trim().toLocaleLowerCase();
        if (!pdf || !needle) return setStatus('Masukkan teks untuk dicari.');

        for (let offset = 0; offset < pdf.numPages; offset += 1) {
            const candidate = ((searchCursor + offset) % pdf.numPages) + 1;
            const page = await pdf.getPage(candidate);
            const content = await page.getTextContent();
            const text = content.items.map((item) => item.str).join(' ').toLocaleLowerCase();
            if (text.includes(needle)) {
                searchCursor = candidate;
                await renderPage(candidate);
                return setStatus(`Padanan ditemui pada halaman ${candidate}.`);
            }
        }
        setStatus('Teks tidak ditemui dalam dokumen.', true);
    });
    findInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') findBtn?.click();
    });

    if (type === 'application/pdf') {
        setStatus('Memuatkan dokumen...');
        syncControls();                       // keadaan MEMUAT: semua kawalan dikunci
        pdfjsLib.getDocument({ url, withCredentials: true }).promise
            .then((document) => {
                pdf = document;
                pageCount.textContent = String(pdf.numPages);
                // `max` tiada dalam markup asal — tanpanya pelayar membenarkan nombor di luar
                // julat dan hanya clamp JS yang menahannya (RR-08-05).
                if (pageInput) pageInput.max = String(pdf.numPages);
                return renderPage(1);
            })
            .catch(() => {
                gagalMuat = true;             // keadaan RALAT: kawalan KEKAL dikunci
                setStatus('Dokumen gagal dimuatkan atau pautan telah tamat tempoh.', true);
                syncControls();
            });
    } else {
        setStatus('Imej dipaparkan.');
        syncControls();                       // imej: zum aktif, kawalan halaman tiada
    }
}
