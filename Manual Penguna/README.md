# Manual Pengguna Diwan

Pakej manual ini mempunyai **9 folder persona**: lapan role tenant dan satu aliran orang awam. Superadmin ialah operator platform global, bukan role tenant, maka tidak termasuk dalam sembilan folder yang diminta.

## Ringkasan verifikasi

- Tarikh: 22 Julai 2026.
- Browser: Google Chrome melalui Playwright, konteks berasingan bagi setiap role.
- Pangkalan data: SQLite latihan terasing; tiada mutasi data production.
- Halaman sidebar: 125/125 mendapat HTTP 200.
- Ujian URL tenant lain: 8/8 mendapat HTTP 404.
- Tangkapan: 252 PNG beranotasi, termasuk modal, viewer PDF dan pendaftaran penuh.
- Viewer: setiap role menunggu “Halaman 1 dipaparkan” dan canvas PDF berisi sebelum gambar.

| # | Persona | Halaman | Gambar tindakan + login | Silang tenant | Manual |
|---:|---|---:|---:|---:|---|
| 1 | Admin / Kerani | 25 | 30 | 404 | [Buka manual](<01-Admin-Kerani/MANUAL-PENGGUNA.md>) |
| 2 | Pengerusi | 17 | 13 | 404 | [Buka manual](<02-Pengerusi/MANUAL-PENGGUNA.md>) |
| 3 | Setiausaha | 15 | 20 | 404 | [Buka manual](<03-Setiausaha/MANUAL-PENGGUNA.md>) |
| 4 | Bendahari | 15 | 14 | 404 | [Buka manual](<04-Bendahari/MANUAL-PENGGUNA.md>) |
| 5 | Nazir | 13 | 12 | 404 | [Buka manual](<05-Nazir/MANUAL-PENGGUNA.md>) |
| 6 | Ketua Imam | 13 | 11 | 404 | [Buka manual](<06-Ketua-Imam/MANUAL-PENGGUNA.md>) |
| 7 | AJK | 13 | 9 | 404 | [Buka manual](<07-AJK/MANUAL-PENGGUNA.md>) |
| 8 | Juruaudit | 14 | 9 | 404 | [Buka manual](<08-Juruaudit/MANUAL-PENGGUNA.md>) |
| 9 | Orang Awam / Pendaftaran | 9 keadaan | 9 | Tidak berkenaan | [Buka manual](<09-Orang-Awam-Pendaftaran/MANUAL-PENGGUNA.md>) |

## Cara membaca gambar

Garis merah menunjukkan kawalan penting. Bulatan merah bernombor dipadankan dengan langkah “Nombor pada gambar”. Gambar menggunakan data latihan MAM; jangan anggap nama contoh sebagai data sebenar.

## Rujukan pengurusan rekod

Manual diselaraskan dengan prinsip dalam dokumen rujukan pengguna:

- Tatacara Pengurusan Rekod Elektronik dalam DDMS di Pejabat Awam (2020): pewujudan, penawanan, klasifikasi, minit, carian, pembetulan, fail/jilid, sistem hibrid, retensi dan pelupusan.
- Panduan Pengguna DDMS 2.0: log masuk, dashboard, menawan rekod, lampiran, paparan, cetak/muat turun, minit dan carian.

Diwan bukan salinan DDMS 2.0; nama butang dan permission dalam manual mesti mengikut UI Diwan yang ditangkap.

## Istilah ringkas

- **Tenant:** ruang data satu masjid.
- **Peti Masuk:** dokumen belum diklasifikasi/difailkan.
- **Rekod:** dokumen rasmi selepas difailkan.
- **Fail:** bekas klasifikasi untuk rekod/kandungan.
- **Minit:** arahan atau edaran tindakan.
- **s.k.:** salinan kepada, untuk makluman.
- **u.p.:** untuk perhatian, metadata orang/unit khusus.
- **Principal/Delegate:** pemilik kuasa dan wakil sementara.
- **Legal Hold:** tahan pelupusan atas sebab undang-undang/audit/siasatan.
- **Hibrid:** kandungan elektronik dengan salinan/fail fizikal yang dijejak.
