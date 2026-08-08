// F8 §9.1 — proksi round-robin TEMPATAN untuk latihan matriks.
//
// Mengapa ia wujud: `php artisan serve` ialah `php -S`, yang SATU-BENANG. Latihan §9.1
// melompat dari halaman ke halaman tanpa henti, jadi pelayar membuka sambungan selari yang
// tidak dapat dilayan; hasilnya `net::ERR_ABORTED` pada dokumen utama secara rawak, dan larian
// gagal atas sebab yang tiada kaitan dengan produk (dibuktikan: /peti-masuk memberi 200 tiga
// kali apabila diprob bersendirian).
//
// Ia menggunakan modul `http` terbina Node SAHAJA — tiada pakej npm baharu (pelan §0.3).
//
// Guna:
//   php artisan serve --port=8101 &   (ulang untuk 8102, 8103, 8104)
//   node "Audit Review Round Robin/bukti/plan-f8/skrip/pelayan-berbilang.mjs" 8095 8101 8102 8103 8104
//   E2E_BASE_URL=http://127.0.0.1:8095 bash .../latihan-9.1-tempatan.sh

import http from 'node:http';

const [, , portArg, ...backendArgs] = process.argv;
const port = Number(portArg);
const backends = backendArgs.map(Number);
if (!port || backends.length === 0 || [port, ...backends].some((p) => !Number.isInteger(p) || p < 1 || p > 65535)) {
    console.error('Guna: node pelayan-berbilang.mjs <port-hadapan> <port-backend...>');
    process.exit(2);
}

let seterusnya = 0;
let jumlah = 0;
const ralat = new Map();

const pelayan = http.createServer((req, res) => {
    const backend = backends[seterusnya++ % backends.length];
    jumlah++;

    const hantar = http.request(
        { host: '127.0.0.1', port: backend, path: req.url, method: req.method, headers: req.headers },
        (balas) => {
            res.writeHead(balas.statusCode ?? 502, balas.headers);
            balas.pipe(res);
        },
    );

    // Ralat backend dikira dan DIPAPARKAN pada penutupan — proksi yang menelan ralat secara
    // senyap akan menjadikan latihan kelihatan sihat sedangkan ia tidak.
    hantar.on('error', (e) => {
        ralat.set(e.code ?? 'UNKNOWN', (ralat.get(e.code ?? 'UNKNOWN') ?? 0) + 1);
        if (!res.headersSent) res.writeHead(502);
        res.end('proksi: backend gagal');
    });

    req.pipe(hantar);
});

pelayan.on('listening', () => {
    console.log(`proksi 127.0.0.1:${port} -> ${backends.join(', ')}`);
});

for (const isyarat of ['SIGINT', 'SIGTERM']) {
    process.on(isyarat, () => {
        const senarai = [...ralat].map(([k, v]) => `${k}=${v}`).join(' ') || '(tiada)';
        console.log(`\nproksi tutup · permintaan=${jumlah} · ralat backend: ${senarai}`);
        pelayan.close(() => process.exit(0));
    });
}

pelayan.listen(port, '127.0.0.1');
