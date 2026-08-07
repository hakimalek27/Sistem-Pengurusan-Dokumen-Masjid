// F7 §8.5 — jana fixture PDF untuk ujian viewer, TANPA pakej baharu.
//
// PDF ialah format teks dengan jadual rujukan-silang (xref) yang mengandungi OFFSET BAIT
// mutlak bagi setiap objek. Offset itu mesti dikira, bukan ditulis tangan — sebab itu ini
// skrip dan bukan fail yang dikomit sekali sahaja tanpa cara menjananya semula.
//
// Tiga fixture:
//   satu-halaman.pdf    — 1 halaman berteks  (prev DAN next mesti disabled)
//   tiga-halaman.pdf    — 3 halaman berteks  (had pada halaman 1 dan 3)
//   tanpa-teks.pdf      — 1 halaman tanpa operator teks (kes "PDF tanpa lapisan teks")
//
// Guna: node "Audit Review Round Robin/bukti/plan-f7/skrip/gen-pdf-fixtures.mjs"

import { mkdirSync, writeFileSync } from 'node:fs';

const OUT = 'tests/fixtures/viewer';

/** Bina PDF dengan `teks.length` halaman; entri kosong = halaman tanpa lapisan teks. */
function binaPdf(teks) {
    const objek = [];
    const bilangan = teks.length;

    // 1 = Catalog, 2 = Pages, kemudian setiap halaman: Page + Contents + Font dikongsi.
    const idFont = 3;
    const idPertamaHalaman = 4;
    const idHalaman = teks.map((_, i) => idPertamaHalaman + i * 2);
    const idKandungan = teks.map((_, i) => idPertamaHalaman + i * 2 + 1);

    objek[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    objek[2] = `<< /Type /Pages /Kids [${idHalaman.map((id) => `${id} 0 R`).join(' ')}] /Count ${bilangan} >>`;
    objek[idFont] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

    teks.forEach((baris, i) => {
        objek[idHalaman[i]] = `<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 200] `
            + `/Resources << /Font << /F1 ${idFont} 0 R >> >> /Contents ${idKandungan[i]} 0 R >>`;

        // Halaman tanpa teks: aliran kandungan yang hanya melukis segi empat, tiada BT/ET.
        const aliran = baris === ''
            ? '0.9 0.9 0.9 rg 20 20 260 160 re f'
            : `BT /F1 18 Tf 24 120 Td (${baris}) Tj ET`;
        objek[idKandungan[i]] = `<< /Length ${aliran.length} >>\nstream\n${aliran}\nendstream`;
    });

    let pdf = '%PDF-1.4\n';
    const offset = [];
    for (let id = 1; id < objek.length; id += 1) {
        if (!objek[id]) continue;
        offset[id] = pdf.length;
        pdf += `${id} 0 obj\n${objek[id]}\nendobj\n`;
    }

    const xrefAt = pdf.length;
    const jumlahObjek = objek.length; // indeks 0 dikira sebagai entri percuma
    pdf += `xref\n0 ${jumlahObjek}\n0000000000 65535 f \n`;
    for (let id = 1; id < jumlahObjek; id += 1) {
        pdf += `${String(offset[id] ?? 0).padStart(10, '0')} 00000 n \n`;
    }
    pdf += `trailer\n<< /Size ${jumlahObjek} /Root 1 0 R >>\nstartxref\n${xrefAt}\n%%EOF\n`;

    return Buffer.from(pdf, 'latin1');
}

mkdirSync(OUT, { recursive: true });

const fixture = {
    'satu-halaman.pdf': binaPdf(['Dokumen ujian satu halaman']),
    'tiga-halaman.pdf': binaPdf([
        'Halaman pertama ujian',
        'Halaman kedua mengandungi kata kunci UNIKKEYWORD',
        'Halaman ketiga ujian',
    ]),
    'tanpa-teks.pdf': binaPdf(['']),
};

for (const [nama, buf] of Object.entries(fixture)) {
    writeFileSync(`${OUT}/${nama}`, buf);
    console.log(`${nama.padEnd(20)} ${String(buf.length).padStart(6)} bait`);
}
