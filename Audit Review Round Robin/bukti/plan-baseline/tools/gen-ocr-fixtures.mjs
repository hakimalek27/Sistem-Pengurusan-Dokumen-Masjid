// Jana 2 imej "imbasan" sintetik utk ci-ocr (D11 #16a/b) — teks bercetak jelas, TIADA data
// peribadi (PDPA). Istilah unik BAKTIMURNI / CAHAYAIKHLAS mudah di-OCR & unik dlm carian.
import { chromium } from '@playwright/test';
import { mkdirSync } from 'node:fs';

const docs = [
  { file: 'tests/fixtures/ocr/sample-scan-1.png', term: 'BAKTIMURNI', tajuk: 'NOTIS MESYUARAT JAWATANKUASA', no: 'BIL (1) DLM. SPDM 100-1/2' },
  { file: 'tests/fixtures/ocr/sample-scan-2.png', term: 'CAHAYAIKHLAS', tajuk: 'MAKLUMAN PROGRAM GOTONG-ROYONG', no: 'BIL (2) DLM. SPDM 500-3/1' },
];
mkdirSync('tests/fixtures/ocr', { recursive: true });
const browser = await chromium.launch({ channel: 'chrome' });
const page = await browser.newPage({ viewport: { width: 1240, height: 1600 }, deviceScaleFactor: 2 });
for (const d of docs) {
  await page.setContent(`<!doctype html><html><body style="margin:0;background:#fff;color:#000;font-family:Georgia,'Times New Roman',serif">
    <div style="padding:70px 90px;font-size:26px;line-height:1.8">
      <div style="text-align:center;border-bottom:3px double #000;padding-bottom:18px;margin-bottom:30px">
        <div style="font-size:34px;font-weight:bold;letter-spacing:1px">SISTEM PENGURUSAN DOKUMEN MASJID</div>
        <div style="font-size:24px">Fixture Ujian OCR — Dokumen Sintetik (Bukan Rekod Sebenar)</div>
      </div>
      <p><b>Rujukan Kami:</b> ${d.no}<br><b>Tarikh:</b> 2 Ogos 2026</p>
      <h2 style="font-size:30px;text-align:center;margin:34px 0">${d.tajuk} ${d.term}</h2>
      <p>Dengan hormatnya perkara di atas dirujuk. Dokumen ini ialah fixture ujian automatik
      bagi saluran pengecaman aksara optik. Kata kunci carian yang dijangka ialah
      <b>${d.term}</b> dan ia mesti boleh ditemui melalui halaman carian selepas proses
      pengecaman selesai.</p>
      <p>Kandungan tambahan untuk kepadatan teks: mesyuarat akan membincangkan hal ehwal
      pentadbiran rekod, jadual penyimpanan, serta tatacara pelupusan yang teratur mengikut
      garis panduan yang berkuat kuasa. Sila bawa dokumen sokongan yang berkaitan.</p>
      <p style="margin-top:44px">Sekian, terima kasih.</p>
      <p style="margin-top:40px"><b>Urus Setia Ujian ${d.term}</b><br>Sistem Diwan (SPDM)</p>
    </div></body></html>`);
  await page.screenshot({ path: d.file, fullPage: true });
  console.log('OK:', d.file, '(term:', d.term + ')');
}
await browser.close();
