import * as pdfjsLib from 'pdfjs-dist';
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

    const setStatus = (message, error = false) => {
        status.textContent = message;
        status.dataset.error = error ? 'true' : 'false';
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
        }
    };

    const updateZoom = async (change) => {
        scale = Math.min(Math.max(scale + change, 0.5), 3);
        zoomLabel.textContent = `${Math.round(scale * 100)}%`;
        if (pdf) await renderPage(pageNumber);
        if (image) image.style.transform = `scale(${scale / 1.25})`;
    };

    root.querySelector('[data-prev]')?.addEventListener('click', () => renderPage(pageNumber - 1));
    root.querySelector('[data-next]')?.addEventListener('click', () => renderPage(pageNumber + 1));
    root.querySelector('[data-zoom-out]')?.addEventListener('click', () => updateZoom(-0.25));
    root.querySelector('[data-zoom-in]')?.addEventListener('click', () => updateZoom(0.25));
    pageInput?.addEventListener('change', () => renderPage(pageInput.value));
    root.querySelector('[data-print]')?.addEventListener('click', () => window.print());

    root.querySelector('[data-find]')?.addEventListener('click', async () => {
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
        if (event.key === 'Enter') root.querySelector('[data-find]')?.click();
    });

    if (type === 'application/pdf') {
        setStatus('Memuatkan dokumen...');
        pdfjsLib.getDocument({ url, withCredentials: true }).promise
            .then((document) => {
                pdf = document;
                pageCount.textContent = String(pdf.numPages);
                return renderPage(1);
            })
            .catch(() => setStatus('Dokumen gagal dimuatkan atau pautan telah tamat tempoh.', true));
    } else {
        setStatus('Imej dipaparkan.');
    }
}
