import { readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const root = path.resolve(process.cwd(), 'Manual Penguna');
const manifest = JSON.parse(await readFile(path.join(root, 'manifest-tangkapan.json'), 'utf8'));
const generatedDate = '22 Julai 2026';

const permissionSummary = {
    admin_masjid: {
        scope: 'Mengurus operasi registri dan pentadbiran masjid. Peranan ini ialah gabungan rasmi Admin dan Kerani.',
        allowed: 'Peti masuk, klasifikasi, rekod, fail, minit, permohonan kelulusan, klasifikasi fail, retensi, pelupusan persediaan/pelaksanaan, ahli, tetapan masjid, storan dan log audit.',
        boundary: 'Tidak membuat keputusan kelulusan dokumen dan tidak meluluskan batch pelupusan sendiri. Kelulusan itu kekal tugas Pengerusi/Nazir atau Pengerusi bagi pelupusan.',
    },
    pengerusi: {
        scope: 'Menyemak rekod, memberi arahan/minit, membuat keputusan kelulusan, meluluskan pelupusan dan memantau penggunaan serta audit.',
        allowed: 'Rekod/fail, akses khas fail, minit, keputusan kelulusan, kelulusan pelupusan, penggunaan storan dan log akses sensitif.',
        boundary: 'Tidak mengklasifikasikan peti masuk, tidak mengubah tetapan masjid, tidak melaksanakan pelupusan dan tidak mengurus ahli.',
    },
    setiausaha: {
        scope: 'Mengurus surat masuk, metadata, pemfailan, minit dan permohonan kelulusan bagi urusan pentadbiran.',
        allowed: 'Peti masuk, klasifikasi, cipta/kemas kini/ganti versi rekod, fail, minit dan permohonan kelulusan.',
        boundary: 'Tidak membuat keputusan kelulusan, tidak mengurus retensi/pelupusan, ahli, tetapan masjid atau pesanan storan.',
    },
    bendahari: {
        scope: 'Mengurus rekod kewangan yang dibenarkan, minit, permohonan kelulusan serta storan.',
        allowed: 'Rekod dan fail yang boleh dilihat, cipta/kemas kini rekod kewangan, minit, permohonan kelulusan, penggunaan dan pesanan storan.',
        boundary: 'Cipta/kemas kini rekod terhad kepada klasifikasi kewangan kod 200/300. Tiada klasifikasi peti masuk, keputusan kelulusan atau tetapan masjid.',
    },
    nazir: {
        scope: 'Menyemak rekod/fail, memberi atau menjawab minit dan membuat keputusan kelulusan yang ditujukan kepadanya.',
        allowed: 'Rekod, fail, minit dan keputusan kelulusan.',
        boundary: 'Tidak mengklasifikasikan peti masuk, mengubah rekod, mengurus fail, storan, ahli atau tetapan.',
    },
    ketua_imam: {
        scope: 'Membaca rekod/fail yang dibenarkan serta menerima, membalas dan menutup tindakan minit.',
        allowed: 'Rekod, fail dan minit.',
        boundary: 'Tidak mengubah metadata, membuat keputusan kelulusan, mengurus storan, ahli atau tetapan.',
    },
    ajk: {
        scope: 'Membaca rekod/fail yang dibenarkan serta melaksanakan arahan minit.',
        allowed: 'Rekod, fail dan minit.',
        boundary: 'Fail/rekod sulit tidak kelihatan kecuali akses khas diberi. Tiada kuasa ubah, kelulusan atau pentadbiran.',
    },
    audit: {
        scope: 'Akses semakan baca sahaja bagi rekod, fail, laporan dan log akses sensitif.',
        allowed: 'Rekod/fail yang dibenarkan, carian, kegemaran, laporan dan log audit.',
        boundary: 'Tidak mencipta, mengubah, mengklasifikasi, memberi minit, membuat keputusan atau mentadbir tenant.',
    },
};

const pageGuides = {
    dashboard: {
        purpose: 'Ringkasan kerja dan keadaan semasa tenant: jumlah rekod, peti masuk, minit, storan dan carta trend yang dibenarkan.',
        steps: [
            'Semak nama masjid pada panel untuk memastikan tenant yang betul.',
            'Baca kad statistik; nombor hanya merangkumi data yang role ini dibenarkan lihat.',
            'Semak senarai semak persediaan jika masih dipaparkan.',
            'Gunakan menu kiri untuk membuka tugasan; jangan gunakan URL tenant lain secara manual.',
        ],
        expected: 'Dashboard terbuka tanpa ralat dan tiada metadata masjid lain dipaparkan.',
    },
    'sensitive-access-logs': {
        purpose: 'Jejak tidak boleh ubah bagi akses lihat atau muat turun rekod sensitif.',
        steps: [
            'Gunakan carian jadual untuk nama pengguna, rekod atau tindakan.',
            'Semak pengguna, tindakan, alamat IP, user-agent dan masa akses.',
            'Siasat akses luar biasa melalui rekod asal; jangan padam atau ubah log.',
            'Laporkan akses tidak dikenali kepada Admin/Kerani dan wakil perlindungan data.',
        ],
        expected: 'Log hanya memaparkan tenant semasa dan kekal baca sahaja.',
    },
    persediaan: {
        purpose: 'Wizard persediaan pertama bagi profil admin, telefon masjid, saluran WhatsApp dan ahli awal.',
        steps: [
            'Tekan Mula Persediaan Berpandu.',
            'Isi jawatan anda dan nombor telefon rasmi masjid.',
            'Pilih sama ada nombor sendiri digunakan sementara atau nombor khas masjid.',
            'Tambah ahli awal dengan nama, peranan, telefon dan e-mel jika ada.',
            'Semak setiap peranan sebelum simpan; elakkan memberi Admin/Kerani tanpa keperluan.',
            'Jika dilangkau, kembali melalui menu Persediaan Berpandu untuk melengkapkan kemudian.',
        ],
        expected: 'Tetapan dan ahli disimpan, kemudian penanda persediaan selesai dikemas kini.',
    },
    'ahli-peranan': {
        purpose: 'Mengurus ahli tenant, peranan, nombor WhatsApp, pilihan notifikasi dan pautan log masuk.',
        steps: [
            'Tekan Jemput Ahli dan isi nama serta nombor WhatsApp format negara.',
            'Masukkan e-mel jika tersedia dan pilih hanya satu peranan yang tepat.',
            'Selepas jemputan, pastikan ahli muncul dalam senarai tenant ini.',
            'Gunakan Hantar Semula Pautan jika ahli belum menerima pautan; jangan cipta akaun pendua.',
            'Tetapkan semula kata laluan sementara hanya apabila identiti ahli disahkan.',
            'Untuk ubah peranan atau keluarkan ahli, semak kesan terhadap tugasan/minit dahulu.',
        ],
        expected: 'Ahli menerima pautan melalui saluran tersedia dan hanya menjadi ahli masjid semasa.',
    },
    'classification-nodes': {
        purpose: 'Katalog klasifikasi berhierarki Fungsi, Aktiviti dan Sub-Aktiviti untuk nombor serta tajuk fail.',
        steps: [
            'Cari kod/tajuk sedia ada sebelum mencipta nod baharu.',
            'Pilih nod induk yang betul dan peringkat yang sepadan.',
            'Gunakan pola kod seperti 500, 500-1 atau 500-1/2.',
            'Tetapkan tajuk rasmi, sensitiviti lalai, status Aktif dan susunan.',
            'Nod yang sudah digunakan tidak boleh diubah sesuka hati; nyahaktifkan nod lama jika perlu.',
        ],
        expected: 'Nod baharu muncul hanya dalam tenant semasa dan tersedia pada pembukaan fail.',
    },
    pelupusan: {
        purpose: 'Mengurus pelupusan terkawal dengan pengasingan tugas antara penyedia, pelulus dan pelaksana.',
        steps: [
            'Admin/Kerani memilih rekod cukup tempoh satu per satu dan menyediakan batch.',
            'Pengerusi menyemak senarai, pegangan undang-undang dan bukti sandaran sebelum meluluskan.',
            'Admin/Kerani hanya melaksanakan batch selepas status diluluskan.',
            'Jika gagal, jangan cipta batch pendua; baiki storan dan gunakan Cuba Semula pada batch sama.',
            'Simpan sijil pelupusan; metadata audit kekal walaupun blob rekod dipadam.',
        ],
        expected: 'Tiada individu menyediakan, meluluskan dan melaksanakan keseluruhan pelupusan seorang diri.',
    },
    'retensi-peraturan': {
        purpose: 'Menetapkan tempoh simpan dan tindakan akhir mengikut jenis rekod atau prefix klasifikasi.',
        steps: [
            'Semak peraturan lalai platform sebelum membuat override masjid.',
            'Pilih jenis rekod atau isi prefix klasifikasi yang khusus.',
            'Isi tahun simpanan; kosongkan hanya apabila tindakan kekal memang dikehendaki.',
            'Pilih tindakan akhir yang sah dan tulis catatan dasar/kelulusan.',
            'Uji kesan pada halaman Retensi sebelum mengaktifkan pelupusan.',
        ],
        expected: 'Tarikh retensi rekod dikira daripada peraturan paling khusus yang sah.',
    },
    'tetapan-masjid': {
        purpose: 'Maklumat masjid, wakil perlindungan data dan konfigurasi intake e-mel/WhatsApp.',
        steps: [
            'Semak telefon rasmi dan wakil perlindungan data.',
            'Aktifkan intake WhatsApp/e-mel hanya selepas saluran dikawal oleh masjid.',
            'Tetapkan kata kunci intake yang mudah tetapi khusus.',
            'Bagi e-mel dipercayai, masukkan alamat PENGHANTAR seperti pengimbas; jangan masukkan alamat intake sistem.',
            'Pasangkan WhatsApp melalui QR/kod telefon dan semak status tersambung.',
            'Matikan notifikasi jika peranti hilang atau sesi tidak lagi dikawal.',
        ],
        expected: 'Dokumen masuk ke peti masuk tenant ini dan sumber asal direkodkan.',
    },
    penggunaan: {
        purpose: 'Memantau kuota, pesanan storan dan add-on aktif.',
        steps: [
            'Bandingkan penggunaan GB, kuota efektif dan peratus penggunaan.',
            'Semak pesanan sedia ada sebelum memohon sekali lagi.',
            'Jika dibenarkan, tekan Tambah Storan dan pilih bilangan blok 10 GB.',
            'Catat nombor invois dan tunggu pengesahan pembayaran platform.',
            'Jangan anggap pesanan menunggu sebagai kuota aktif sehingga status disahkan.',
        ],
        expected: 'Pesanan idempotent muncul sekali dan kuota bertambah hanya selepas disahkan.',
    },
    retensi: {
        purpose: 'Senarai rekod akan luput, sumber peraturan, legal hold dan eksport sebelum luput.',
        steps: [
            'Semak rekod yang akan luput dalam 365/90 hari.',
            'Bandingkan sumber peraturan lalai dengan override masjid.',
            'Aktifkan Legal Hold jika ada audit, siasatan, litigasi atau arahan simpan.',
            'Tarik hold hanya dengan kebenaran dan bukti urusan selesai.',
            'Eksport ZIP sebelum pelupusan jika perlu; pautan eksport mempunyai tempoh tamat.',
        ],
        expected: 'Rekod ber-hold tidak memasuki pelupusan walaupun tarikh retensi tiba.',
    },
    delegasi: {
        purpose: 'Paparan principal/delegate untuk mewakilkan minit atau keputusan kelulusan dalam tempoh tertentu.',
        steps: [
            'Pilih Principal, iaitu pemilik tugas asal.',
            'Pilih Delegate, iaitu orang yang akan bertindak bagi pihak principal.',
            'Hadkan capability kepada Minit, Kelulusan atau kedua-duanya.',
            'Tetapkan mula/tamat dan sebab yang boleh diaudit.',
            'Semak nama “bagi pihak” pada tindakan yang dibuat delegate.',
            'Batal delegasi sebaik keperluan tamat.',
        ],
        expected: 'Delegate hanya boleh bertindak dalam tenant, capability dan julat masa yang diluluskan.',
    },
    profil: {
        purpose: 'Maklumat akaun, saluran notifikasi, Telegram dan kata laluan sendiri.',
        steps: [
            'Semak nama, e-mel dan nombor WhatsApp.',
            'Buka Tetapan Notifikasi dan hidupkan hanya saluran yang boleh dicapai.',
            'Sambung Telegram melalui pautan rasmi dan tekan Start sebelum tamat tempoh.',
            'Gunakan Hantar Notifikasi Ujian selepas perubahan.',
            'Tetapkan kata laluan panjang, unik dan tidak digunakan di sistem lain.',
            'Putuskan Telegram atau tukar kata laluan segera jika peranti hilang.',
        ],
        expected: 'Notifikasi ujian sampai melalui saluran aktif; kata laluan lama tidak lagi boleh digunakan selepas ditukar.',
    },
    'peti-masuk': {
        purpose: 'Pintu masuk dokumen UI, e-mel, WhatsApp atau imbasan sebelum menjadi rekod rasmi.',
        steps: [
            'Semak Sumber, Tajuk/Fail, Tarikh Terima, Penghantar/Sumber, Diterima, Antivirus, OCR dan Duplikat.',
            'Buka Lihat Dokumen/OCR dan sahkan fail boleh dibaca serta benar untuk masjid ini.',
            'Muat naik hanya format dibenarkan; fail melalui semakan antivirus dan kuota.',
            'Tekan Klasifikasikan dan lengkapkan metadata serta fail destinasi.',
            'Jika spam/tidak berkaitan, gunakan Padam (Spam) dan nyatakan sebab; jangan klasifikasikan sebagai rekod.',
            'Pastikan sumber menunjukkan e-mel pengirim, nombor WhatsApp atau pengguna UI serta masa upload.',
        ],
        expected: 'Item hanya keluar daripada peti masuk selepas berjaya difailkan atau dipadam dengan audit.',
    },
    records: {
        purpose: 'Senarai rekod rasmi yang telah difailkan, dengan metadata, media, OCR, minit, kelulusan dan audit.',
        steps: [
            'Cari melalui rujukan/tajuk dan tapis Jenis, Sensitiviti atau Status.',
            'Buka Lihat untuk memeriksa metadata dan asal dokumen.',
            'Semak tab Teks OCR, Lampiran & Versi, Minit, Kelulusan dan Audit.',
            'Gunakan Kegemaran untuk rujukan kerap.',
            'Gunakan hanya tindakan yang dipaparkan oleh role; jangan cuba mengubah URL untuk mendapatkan tindakan lain.',
        ],
        expected: 'Senarai tidak merangkumi peti masuk dan tidak mendedahkan rekod tenant/sensitiviti yang tidak dibenarkan.',
    },
    'registry-files': {
        purpose: 'Senarai fail mengikut nombor klasifikasi, jilid, sensitiviti, medium dan penjagaan fizikal.',
        steps: [
            'Cari nombor/tajuk fail sedia ada sebelum membuka fail baharu.',
            'Semak status terbuka/tutup dan bilangan kandungan.',
            'Bagi hibrid/fizikal, semak rujukan, lokasi, pemegang dan tarikh pulang.',
            'Gunakan Keluarkan Fail, Terima Pulangan atau Pindah Lokasi untuk setiap pergerakan.',
            'Tutup fail dengan sebab; buka jilid baharu apabila had kandungan dicapai.',
            'Akses khas fail sulit hendaklah minimum, individu dan ditarik selepas selesai.',
        ],
        expected: 'Nombor fail elektronik dan fizikal sepadan; sejarah pergerakan tidak terputus.',
    },
    'minit-saya': {
        purpose: 'Arahan, makluman, balasan dan status tindakan minit yang berkaitan dengan pengguna.',
        steps: [
            'Tapis Kategori: Perlu Tindakan, Makluman, Saya Hantar atau Selesai.',
            'Baca rekod, pengirim, arahan, penerima, keutamaan dan tarikh akhir.',
            'Jika tindakan, buat kerja sebenar dahulu sebelum Tanda Selesai.',
            'Gunakan Balas & Edarkan untuk catatan susulan dan penerima seterusnya.',
            'Penerima s.k. ialah makluman; penerima tindakan mempunyai tanggungjawab dan SLA.',
            'Tindakan delegate direkod sebagai “oleh X bagi pihak Y”.',
        ],
        expected: 'Apabila semua penerima tindakan selesai, minit ditutup dan pengirim dimaklumkan.',
    },
    kelulusan: {
        purpose: 'Permohonan dan keputusan kelulusan dengan pengesahan semula kata laluan, masa, IP dan pihak yang bertindak.',
        steps: [
            'Semak tajuk rekod, pemohon, nota dan status.',
            'Buka rekod asal dan media sebelum membuat keputusan.',
            'Pelulus yang ditetapkan memilih Lulus atau Tolak.',
            'Masukkan kata laluan sendiri; nota wajib bagi penolakan dan digalakkan bagi kelulusan.',
            'Jangan berkongsi kata laluan untuk membolehkan orang lain meluluskan.',
            'Semak status akhir dan rekod “bagi pihak” jika delegasi digunakan.',
        ],
        expected: 'Keputusan direkod sekali, tidak boleh ditindih, dan pemohon menerima notifikasi.',
    },
    carian: {
        purpose: 'Carian penuh dan metadata dengan saved search, julat tarikh serta hasil yang ditapis mengikut akses.',
        steps: [
            'Masukkan teks tajuk, rujukan atau kandungan OCR jika perlu.',
            'Gabungkan Jenis, Fail, Arah, Sensitiviti, Status dan Saluran.',
            'Isi pengirim, rujukan, penerima serta julat tarikh rekod/terima.',
            'Tekan Cari dan semak jumlah hasil.',
            'Isi Nama carian, tandakan Lalai jika sesuai, kemudian Simpan.',
            'Pilih carian tersimpan untuk guna semula atau Padam carian apabila tidak diperlukan.',
            'Tekan bintang untuk menambah hasil ke Kegemaran.',
        ],
        expected: 'Hasil hanya mengandungi rekod yang policy role dan tenant benarkan.',
    },
    kegemaran: {
        purpose: 'Pintasan peribadi kepada rekod dan fail yang kerap dirujuk.',
        steps: [
            'Tambah bintang dari senarai fail, butiran rekod atau hasil carian.',
            'Buka Kegemaran untuk melihat rekod/fail tersimpan.',
            'Klik item untuk membuka sumber asal.',
            'Tekan bintang penuh untuk membuang kegemaran.',
            'Kegemaran tidak mengatasi permission; item hilang jika akses ditarik.',
        ],
        expected: 'Senarai kegemaran adalah per pengguna dan per tenant.',
    },
    laporan: {
        purpose: 'Ringkasan jumlah rekod, retensi, minit lewat, sumber dan akses sensitif mengikut kebenaran.',
        steps: [
            'Semak Jumlah Rekod, Akan Luput, Minit Lewat dan Akses Sulit.',
            'Bandingkan pecahan Jenis, Status dan Sumber.',
            'Jika butang Eksport CSV tersedia, muat turun untuk analisis terkawal.',
            'Buka fail CSV sebagai data; berhati-hati dengan perkongsian luar tenant.',
        ],
        expected: 'Angka dan eksport menggunakan skop rekod yang pengguna dibenarkan lihat.',
    },
    'pembetulan-rekod': {
        purpose: 'Workflow pembetulan salah tawan tanpa memadam jejak nilai asal.',
        steps: [
            'Dari butiran rekod, tekan Mohon Pembetulan.',
            'Nyatakan sebab sekurang-kurangnya 10 aksara dan ubah hanya medan yang salah.',
            'Hantar; rekod asal kekal sehingga reviewer yang berkuasa memutuskan.',
            'Reviewer membandingkan nilai asal/cadangan, kemudian Luluskan atau Tolak dengan catatan.',
            'Pemohon menerima notifikasi keputusan dan audit menyimpan sebelum/selepas.',
        ],
        expected: 'Tiada perubahan senyap; setiap pembetulan mempunyai pemohon, reviewer, masa dan keputusan.',
    },
};

const extraGuides = {
    'Persediaan berpandu': ['Isi jawatan pengguna.', 'Isi telefon rasmi masjid.', 'Pilih sumber nombor WhatsApp notifikasi.', 'Tambah ahli awal dan semak peranannya.', 'Simpan hanya selepas maklumat lengkap.'],
    'Jemput ahli': ['Isi nama penuh.', 'Isi nombor WhatsApp format 60...', 'Masukkan e-mel jika tersedia.', 'Pilih peranan minimum yang diperlukan.', 'Hantar dan sahkan ahli menerima pautan log masuk.'],
    'Sedia senarai pelupusan': ['Semak setiap rekod sudah cukup tempoh.', 'Pastikan rekod tidak mempunyai Legal Hold.', 'Pilih satu per satu.', 'Sahkan amaran pemadaman kekal.', 'Hantar batch untuk kelulusan Pengerusi.'],
    'Edit tetapan masjid': ['Semak telefon dan wakil perlindungan data.', 'Tetapkan kata kunci intake.', 'Aktif/matikan WhatsApp atau e-mel.', 'Masukkan e-mel pengirim dipercayai, bukan alamat intake.', 'Simpan dan uji satu dokumen masuk.'],
    'Permohonan storan tambahan': ['Semak baki kuota dan pesanan sedia ada.', 'Masukkan bilangan blok 10 GB.', 'Sahkan jumlah/invois.', 'Tunggu pengesahan bayaran sebelum menganggap kuota aktif.'],
    'Muat naik dokumen': ['Pilih atau seret satu/lebih fail yang dibenarkan.', 'Pastikan saiz setiap fail dalam had.', 'Tunggu upload selesai.', 'Sahkan toast bilangan dokumen.', 'Buka Peti Masuk dan semak antivirus, OCR serta sumber UI.'],
    'Tetapan notifikasi': ['Hidup/matikan E-mel, WhatsApp dan Telegram mengikut saluran sebenar.', 'Simpan perubahan.', 'Gunakan Hantar Notifikasi Ujian.', 'Betulkan nombor/e-mel jika ujian tidak sampai.'],
    'Tetapkan kata laluan': ['Cipta kata laluan unik yang panjang.', 'Masukkan semula nilai sama.', 'Simpan.', 'Uji pada sesi baharu; jangan kongsi kata laluan.'],
    'Klasifikasi peti masuk': [
        'Sahkan dokumen, sumber dan status antivirus/OCR dahulu.',
        'Pilih Jenis Rekod; medan metadata khusus akan berubah mengikut jenis.',
        'Isi Tajuk, Arah, Ruj. Kami/Ruj. Tuan dan kedua-dua tarikh.',
        'Isi nama/organisasi pengirim, penerima, jumlah lampiran dan Untuk Perhatian (u.p.).',
        'Pilih Failkan Ke. Jika tiada fail sesuai, buka fail baharu pada nod klasifikasi yang betul.',
        'Tetapkan Tahap Akses. Nilai efektif tidak boleh lebih rendah daripada sensitiviti fail.',
        'Pilih Untuk Tindakan bagi orang yang wajib bertindak; pilih s.k. bagi makluman sahaja.',
        'Jika ada penerima tindakan, isi arahan yang jelas dan keutamaan.',
        'Tekan Klasifikasikan dan sahkan nombor fail/kandungan pada notifikasi kejayaan.',
    ],
    'Cipta nod klasifikasi': ['Pilih Nod Induk jika Aktiviti/Sub-Aktiviti.', 'Pilih Peringkat.', 'Masukkan Kod mengikut hierarki.', 'Isi Tajuk rasmi.', 'Tetapkan Sensitiviti Lalai, Aktif dan Susunan.', 'Cipta dan semak nod pada senarai.'],
    'Buka fail baharu': ['Pilih Nod Klasifikasi Aktiviti/Sub-Aktiviti.', 'Isi Tajuk Fail khusus.', 'Pilih Medium Elektronik/Hibrid/Fizikal.', 'Bagi hibrid/fizikal, isi Rujukan Salinan Fizikal dan Lokasi.', 'Cipta dan semak nombor fail automatik.'],
    'Cipta peraturan retensi': ['Pilih Jenis Rekod atau Prefix Klasifikasi.', 'Isi Tahun Simpanan.', 'Pilih Tindakan.', 'Tambah Catatan dasar.', 'Cipta dan semak kesan pada halaman Retensi.'],
    'Cipta delegasi': ['Pilih Principal.', 'Pilih Delegate.', 'Pilih tugas Minit/Kelulusan.', 'Tetapkan mula dan tamat.', 'Nyatakan sebab.', 'Cipta dan semak status Aktif.'],
    'Butiran rekod dan tindakan mengikut kebenaran': ['Semak tab Maklumat dan asal dokumen.', 'Semak Teks OCR.', 'Buka Lampiran & Versi.', 'Semak Minit dan Kelulusan.', 'Semak Audit.', 'Gunakan hanya butang yang role anda paparkan.'],
    'Mohon pembetulan rekod': ['Nyatakan sebab salah tawan.', 'Semak semua nilai sedia ada.', 'Ubah sekurang-kurangnya satu medan sebenar.', 'Hantar untuk semakan.', 'Pantau keputusan di Pembetulan Rekod.'],
    'Edarkan minit': ['Pilih sekurang-kurangnya seorang Penerima Tindakan.', 'Tambah s.k. jika hanya perlu makluman.', 'Tulis arahan yang boleh dilaksanakan.', 'Pilih Biasa/Segera/Kritikal.', 'Hantar dan semak penerima muncul pada tab Minit.'],
    'Mohon kelulusan': ['Pilih pelulus yang dipaparkan.', 'Tulis nota konteks.', 'Hantar.', 'Pantau status pada tab Kelulusan.', 'Jangan hantar permohonan pendua.'],
    'Ganti versi rekod': ['Pilih fail versi baharu yang sah.', 'Semak nama dan format.', 'Simpan.', 'Sahkan rekod baharu menjadi versi aktif dan versi lama kekal dalam jejak.'],
    'Pindah rekod ke fail lain': ['Pilih Fail Baharu dalam tenant sama.', 'Semak sensitiviti fail sasaran.', 'Nyatakan sebab.', 'Sahkan pindah.', 'Semak nombor kandungan dan audit.'],
    'Viewer dokumen': ['Gunakan anak panah untuk halaman sebelum/seterusnya.', 'Ubah nombor halaman secara terus.', 'Zum keluar/masuk tanpa mengubah fail.', 'Cari teks jika PDF mempunyai lapisan teks.', 'Cetak Metadata untuk bukti konteks.', 'Muat Turun hanya jika dibenarkan dan simpan secara terkawal.'],
    'Butiran fail elektronik, fizikal atau hibrid': ['Semak nombor, tajuk dan klasifikasi.', 'Semak medium serta rujukan fizikal.', 'Semak lokasi, status penjagaan dan pemegang.', 'Semak sejarah pergerakan.', 'Semak Akses Khas bagi fail sulit jika role dibenarkan.'],
    'Keluarkan fail fizikal': ['Pilih Pemegang Ahli atau isi pemegang luar.', 'Isi lokasi tujuan.', 'Tetapkan tarikh perlu pulang.', 'Tulis catatan tujuan.', 'Simpan dan serahkan fail hanya selepas rekod pergerakan berjaya.'],
    'Pindah lokasi fizikal': ['Masukkan lokasi baharu yang tepat hingga rak/kotak.', 'Tulis sebab/catatan.', 'Simpan.', 'Pastikan label fizikal turut dikemas kini.'],
    'Beri akses khas fail sulit': ['Pilih ahli masjid yang perlu akses.', 'Beri akses.', 'Sahkan ahli boleh melihat fail tetapi tenant lain tidak.', 'Tarik balik apabila tugasan selesai.'],
    'Balas dan edarkan minit': ['Baca arahan asal dan rekod.', 'Pilih penerima tindakan susulan.', 'Tambah penerima s.k. jika perlu.', 'Tulis catatan jawapan.', 'Pilih keutamaan dan hantar.', 'Tanda minit asal selesai hanya selepas tindakan sendiri selesai.'],
    'Tanda tindakan minit selesai': ['Pastikan kerja sebenar selesai.', 'Tekan Tanda Selesai.', 'Baca pengesahan.', 'Sahkan status penerima/ minit berubah.', 'Pengirim dimaklumkan apabila semua penerima tindakan selesai.'],
    'Buat keputusan kelulusan': ['Buka dan semak rekod asal.', 'Pilih Lulus atau Tolak.', 'Masukkan kata laluan sendiri.', 'Isi nota; nota wajib untuk Tolak.', 'Sahkan sekali sahaja.', 'Semak masa, IP dan pihak yang bertindak.'],
    'Hasil carian lanjutan': ['Semak jumlah hasil.', 'Pastikan metadata hasil sepadan dengan kriteria.', 'Buka rekod untuk pengesahan.', 'Tambah bintang jika kerap dirujuk.', 'Ubah kriteria jika hasil terlalu luas.'],
};

function mdImage(image, title) {
    return `![${title}](<${image}>)`;
}

function bullets(values) {
    return values.map((value) => `- ${value}`).join('\n');
}

function numbered(values) {
    return values.map((value, index) => `${index + 1}. ${value}`).join('\n');
}

function pageKey(page) {
    const segment = page.path.split('/').filter(Boolean).at(-1);
    return page.path === '/app/mam' ? 'dashboard' : segment;
}

function renderCallouts(notes) {
    if (!notes?.length) return '';
    return `\n**Nombor pada gambar**\n${numbered(notes.map((note) => note.note))}\n`;
}

function visibleControls(page) {
    const ignored = /^(Buka menu|Tutup menu|Pemberitahuan|Akaun|Tema|Sebelumnya|Seterusnya|\d+)$/i;
    return [...new Set([...(page.buttons ?? []), ...(page.tabs ?? [])])]
        .map((value) => value.replace(/\s+/g, ' ').trim())
        .filter((value) => value && !ignored.test(value))
        .slice(0, 30);
}

function renderPage(page, index) {
    const guide = pageGuides[pageKey(page)] ?? {
        purpose: 'Halaman fungsi bagi role ini.',
        steps: ['Buka melalui menu kiri.', 'Semak data dan gunakan hanya tindakan yang dipaparkan.', 'Pastikan hasil kekal dalam tenant semasa.'],
        expected: 'Halaman berfungsi tanpa ralat.',
    };
    const controls = visibleControls(page);
    return `### ${index + 1}. ${page.label}\n\n` +
        `**URL:** \`${page.path.replace('/app/mam', '/app/{tenant}') || '/app/{tenant}'}\`\n\n` +
        `**Tujuan:** ${guide.purpose}\n\n` +
        `${mdImage(page.image, `${page.label} - paparan ${page.status}`)}\n` +
        `${renderCallouts(page.notes)}` +
        `\n**Cara menggunakan**\n${numbered(guide.steps)}\n\n` +
        (controls.length ? `**Kawalan/tindakan yang terlihat semasa verifikasi:** ${controls.map((value) => `\`${value}\``).join(', ')}.\n\n` : '') +
        `**Hasil dijangka:** ${guide.expected}\n`;
}

function renderExtra(extra, index) {
    const steps = extraGuides[extra.title] ?? ['Baca tajuk dan semua medan.', 'Isi medan wajib bertanda *.', 'Semak semula sebelum menghantar.', 'Pastikan notifikasi kejayaan dipaparkan.'];
    return `### ${index + 1}. ${extra.title}\n\n` +
        `${mdImage(extra.image, extra.title)}\n` +
        `${renderCallouts(extra.notes)}` +
        (extra.labels?.length ? `\n**Medan/kawalan yang disahkan:** ${[...new Set(extra.labels)].map((label) => `\`${label}\``).join(', ')}.\n` : '') +
        `\n**Langkah terperinci**\n${numbered(steps)}\n\n` +
        `**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.\n`;
}

function roleWorkflow(roleKey) {
    const shared = [
        'Log masuk dan sahkan nama tenant.',
        'Cari atau buka rekod/fail yang dibenarkan.',
        'Semak metadata, sumber, tarikh upload, antivirus, OCR dan lampiran.',
        'Laksanakan tindakan yang dipaparkan mengikut role.',
        'Semak toast, status, tab Audit/Minit/Kelulusan dan notifikasi penerima.',
        'Log keluar atau kunci peranti selepas selesai.',
    ];
    const specific = {
        admin_masjid: [
            'Intake UI/e-mel/WhatsApp masuk ke Peti Masuk tenant.',
            'Admin/Kerani semak fail, antivirus, OCR, duplikat dan provenance.',
            'Lengkapkan metadata dan pilih fail sedia ada atau buka fail baharu.',
            'Tetapkan penerima tindakan, s.k., arahan dan keutamaan.',
            'Klasifikasikan; sistem memberi nombor fail(kandungan) dan menghantar notifikasi.',
            'Pantau balasan minit, permohonan kelulusan, pembetulan dan retensi.',
        ],
        pengerusi: ['Terima notifikasi minit/kelulusan.', 'Buka rekod dan semak media.', 'Balas/edarkan arahan jika perlu.', 'Untuk kelulusan, sahkan kata laluan dan rekod keputusan.', 'Bagi pelupusan, lulus hanya selepas semakan bebas.'],
        setiausaha: ['Semak peti masuk dan provenance.', 'Klasifikasikan ke fail yang betul.', 'Edarkan minit.', 'Mohon kelulusan kepada Pengerusi/Nazir.', 'Pantau keputusan dan pembetulan.'],
        bendahari: ['Cari fail klasifikasi kewangan 200/300.', 'Semak/cipta rekod kewangan yang dibenarkan.', 'Edarkan minit atau mohon kelulusan.', 'Pantau kuota dan buat satu pesanan storan jika perlu.'],
        nazir: ['Terima minit atau permohonan kelulusan.', 'Semak rekod/media.', 'Balas minit atau buat keputusan kelulusan.', 'Sahkan tindakan direkod atas nama sendiri/delegasi.'],
        ketua_imam: ['Terima arahan minit.', 'Semak rekod dan lampiran.', 'Balas serta edarkan jika ada susulan.', 'Tanda selesai selepas tindakan sebenar lengkap.'],
        ajk: ['Terima arahan minit.', 'Semak rekod yang dibenarkan.', 'Balas/edarkan kepada ahli berkaitan.', 'Tanda selesai selepas tugasan lengkap.'],
        audit: ['Gunakan carian/laporan untuk sampel.', 'Buka rekod/fail secara baca sahaja.', 'Semak metadata, versi, minit, kelulusan, pergerakan dan log sensitif.', 'Catat dapatan di luar sistem mengikut prosedur audit tanpa mengubah bukti.'],
    };
    return [...(specific[roleKey] ?? []), ...shared];
}

function internalManual(roleKey, role) {
    const permission = permissionSummary[roleKey];
    const pageRows = role.pages.map((page, index) => `| ${index + 1} | ${page.label} | \`${page.path.replace('/app/mam', '/app/{tenant}') || '/app/{tenant}'}\` | ${page.status} |`).join('\n');
    const loginNotes = role.login.notes?.length ? numbered(role.login.notes.map((note) => note.note)) : numbered([
        'Masukkan e-mel atau nombor telefon berdaftar.',
        'Masukkan kata laluan sendiri.',
        'Tekan Log masuk.',
        'Gunakan pautan log masuk selamat jika perlu.',
    ]);
    return `# Manual Pengguna Diwan - ${role.label}\n\n` +
        `**Versi UI disahkan:** ${generatedDate}\n\n` +
        `**Tenant contoh:** MAM (data latihan, bukan production)\n\n` +
        `**Liputan Chrome:** ${role.actualPages}/${role.expectedPages} halaman, silang tenant HTTP ${role.crossTenantStatus}, ${role.extras.length} skrin tindakan tambahan.\n\n` +
        `Manual ini khusus untuk role **${role.label}**. Gambar menggunakan data latihan. Nama, e-mel, nombor telefon dan dokumen sebenar organisasi tidak patut dimasukkan ke manual.\n\n` +
        `## 1. Skop dan had role\n\n` +
        `**Tanggungjawab:** ${permission.scope}\n\n` +
        `**Dibenarkan:** ${permission.allowed}\n\n` +
        `**Had penting:** ${permission.boundary}\n\n` +
        `Jika butang tidak kelihatan, itu lazimnya sekatan role, status, sensitiviti atau tenant. Jangan cuba memintas melalui URL.\n\n` +
        `## 2. Log masuk\n\n` +
        `${mdImage(role.login.image, `Log masuk ${role.label}`)}\n\n` +
        `**Nombor pada gambar**\n${loginNotes}\n\n` +
        `### Log masuk dengan kata laluan\n\n` +
        `1. Buka \`https://bakwim.my/app/login\`.\n` +
        `2. Masukkan e-mel **atau** nombor telefon yang didaftarkan untuk akaun sendiri.\n` +
        `3. Masukkan kata laluan; pastikan Caps Lock dan susun atur papan kekunci betul.\n` +
        `4. Tekan **Log masuk** sekali dan tunggu dashboard tenant.\n` +
        `5. Sahkan nama masjid. Jika masjid salah atau anda tidak mengenalinya, log keluar dan lapor kepada Admin/Kerani.\n\n` +
        `### Log masuk melalui pautan selamat\n\n` +
        `1. Buka \`https://bakwim.my/log-masuk\`.\n` +
        `2. Masukkan e-mel atau nombor telefon berdaftar dan tekan **Hantar Pautan Log Masuk**.\n` +
        `3. Semak e-mel/WhatsApp. Respons sistem sengaja tidak mendedahkan sama ada akaun wujud.\n` +
        `4. Buka pautan dalam masa 15 minit. Pautan hanya sekali guna.\n` +
        `5. Jika tamat tempoh, minta pautan baharu; jangan kongsi atau forward pautan.\n\n` +
        `### Jika gagal\n\n` +
        `- Jangan cuba berulang kali kerana perlindungan brute-force/rate-limit boleh mengunci percubaan sementara.\n` +
        `- Gunakan pautan selamat atau minta Admin/Kerani hantar semula pautan.\n` +
        `- HTTP 403 bermaksud tindakan tidak dibenarkan; HTTP 404 juga digunakan untuk menyembunyikan tenant/rekod yang bukan milik anda.\n` +
        `- Jangan hantar screenshot kata laluan, token atau pautan sekali guna kepada sesiapa.\n\n` +
        `## 3. Senarai halaman role\n\n` +
        `| # | Halaman | Laluan | Status Chrome |\n|---:|---|---|---:|\n${pageRows}\n\n` +
        `## 4. Panduan setiap halaman\n\n` +
        `${role.pages.map(renderPage).join('\n\n')}\n\n` +
        `## 5. Panduan tindakan dan modal\n\n` +
        `Bahagian ini hanya menyenaraikan tindakan yang benar-benar kelihatan bagi role ini semasa verifikasi. Medan bertanda \`*\` wajib.\n\n` +
        `${role.extras.map(renderExtra).join('\n\n')}\n\n` +
        `## 6. Workflow hujung ke hujung untuk role ini\n\n${numbered(roleWorkflow(roleKey))}\n\n` +
        `## 7. Peraturan klasifikasi, minit dan notifikasi\n\n` +
        `- **Untuk Tindakan (Minit):** penerima wajib mengambil tindakan, boleh membalas/mengedarkan dan perlu menanda selesai.\n` +
        `- **Untuk Makluman (s.k.):** penerima dimaklumkan tetapi bukan pemilik tindakan asal.\n` +
        `- **Untuk Perhatian (u.p.):** nama/unit khusus yang patut membaca surat; ia metadata surat dan tidak menggantikan penerima minit.\n` +
        `- **Ruj. Kami:** rujukan yang dikeluarkan masjid/organisasi sendiri. **Ruj. Tuan:** rujukan pihak penghantar.\n` +
        `- **Arah Masuk:** diterima daripada luar. **Keluar:** dihantar keluar. **Dalaman:** diwujud/diedar dalam organisasi.\n` +
        `- Notifikasi dihantar hanya melalui saluran yang aktif dan tersedia: pangkalan data, e-mel, WhatsApp atau Telegram. Semak Profil dan tetapan tenant jika notifikasi tidak tiba.\n` +
        `- Penerima dipilih daripada ahli aktif tenant yang dibenarkan melihat sensitiviti rekod. Nama tenant lain tidak patut muncul.\n\n` +
        `## 8. Keselamatan dan pengasingan data\n\n` +
        `1. Gunakan akaun sendiri; jangan guna akaun kongsi.\n` +
        `2. Semak tenant sebelum upload, klasifikasi, minit, kelulusan atau eksport.\n` +
        `3. Jangan ubah slug/ID pada URL. Ujian silang tenant manual ini mengembalikan HTTP ${role.crossTenantStatus}.\n` +
        `4. Simpan muat turun sensitif hanya pada peranti/storan organisasi yang dibenarkan.\n` +
        `5. Jika data masjid lain kelihatan, berhenti serta-merta, jangan muat turun/sebar, catat masa/URL dan lapor insiden.\n` +
        `6. Semak sumber dokumen (UI/e-mel/WhatsApp), masa upload, antivirus dan OCR sebelum pemfailan.\n` +
        `7. Jangan luluskan permintaan, pembetulan atau pelupusan tanpa membuka bukti asal.\n` +
        `8. Log keluar pada peranti awam dan jangan simpan kata laluan dalam browser yang dikongsi.\n\n` +
        `## 9. Senarai semak sebelum menutup tugasan\n\n` +
        `- [ ] Tenant betul.\n- [ ] Dokumen dan sumber telah disahkan.\n- [ ] Metadata/rujukan/tarikh tepat.\n- [ ] Fail dan sensitiviti tepat.\n- [ ] Penerima tindakan dan s.k. tepat.\n- [ ] Toast/status kejayaan dilihat.\n- [ ] Notifikasi atau audit disahkan jika berkaitan.\n- [ ] Tiada fail sensitif tertinggal pada peranti awam.\n\n` +
        `## 10. Bantuan dan pelaporan masalah\n\n` +
        `1. Catat masa kejadian, role, nama tenant, URL halaman, tindakan terakhir dan mesej ralat.\n` +
        `2. Jika berkaitan rekod/fail, sertakan nombor rujukan atau ID sahaja; lindungi kandungan dan data peribadi.\n` +
        `3. Hantar kepada Admin/Kerani. Admin/Kerani mengeskalasi kepada operator platform jika isu melibatkan tenant, keselamatan, intake atau servis luar.\n` +
        `4. Jangan hantar kata laluan, token, pautan sekali guna, kunci API atau keseluruhan dokumen sensitif.\n` +
        `5. Jika data tenant lain kelihatan, berhenti menggunakan halaman itu dan laporkan sebagai insiden keselamatan segera.\n`;
}

function publicManual() {
    return `# Manual Pengguna Diwan - Orang Awam / Pendaftaran Masjid\n\n` +
        `**Versi UI disahkan:** ${generatedDate}\n\n**Skop:** pendaftaran awam hingga log masuk dan persediaan pertama.\n\n` +
        `## 1. Sebelum mendaftar\n\n` +
        `Sediakan nama rasmi masjid, negeri/daerah, kod akronim 3-6 huruf, cadangan slug URL, nama pentadbir pertama, e-mel aktif dan nombor WhatsApp format negara seperti \`60123456789\`. Pentadbir pertama akan menjadi **Admin / Kerani** tenant selepas diluluskan.\n\n` +
        `Jangan gunakan e-mel atau telefon yang anda tidak kawal. Baca Terma/DPA dan dasar retensi; rekod cukup tempoh boleh dipadam selepas notifikasi dan proses pelupusan yang berkenaan.\n\n` +
        `## 2. Buka laman utama\n\n${mdImage('imej/01-laman-utama.png', 'Laman utama Diwan')}\n\n` +
        `1. Buka \`https://bakwim.my\`.\n2. Pilih **Daftar Masjid** untuk permohonan baharu.\n3. Jika sudah mempunyai akaun, pilih **Log Masuk** dan jangan daftar tenant pendua.\n\n` +
        `## 3. Lengkapkan borang pendaftaran\n\n${mdImage('imej/02-borang-daftar.png', 'Borang daftar masjid bernombor')}\n\n` +
        `1. **Nama Masjid:** nama rasmi penuh.\n` +
        `2. **Negeri/Daerah:** lokasi pentadbiran sebenar.\n` +
        `3. **Kod Akronim:** 3-6 huruf sahaja dan mesti unik, contoh \`MAM\`. Kod digunakan pada nombor fail.\n` +
        `4. **Slug URL:** huruf kecil/nombor, ringkas dan unik. Sistem boleh mengkanonkan slug berdasarkan nama.\n` +
        `5. **Nama Pentadbir:** individu bertanggungjawab, bukan nama jawatan umum.\n` +
        `6. **E-mel:** alamat aktif untuk menerima pautan selepas kelulusan.\n` +
        `7. **No. WhatsApp:** gunakan format \`60...\` tanpa ruang atau simbol.\n` +
        `8. Tandakan persetujuan Terma Perkhidmatan dan DPA selepas dibaca.\n` +
        `9. Tandakan pengakuan dasar retensi selepas difahami.\n` +
        `10. Semak semula dan tekan **Hantar Permohonan** sekali.\n\n` +
        `Jika kod, slug, e-mel atau telefon tidak sah/pendua, baca mesej medan dan betulkan. Jangan tambah digit pada kod akronim kerana medan itu huruf sahaja.\n\n` +
        `## 4. Permohonan diterima\n\n${mdImage('imej/03-permohonan-diterima.png', 'Permohonan diterima')}\n\n` +
        `1. Pastikan mesej **Permohonan diterima!** kelihatan.\n2. Permohonan berstatus menunggu kelulusan platform.\n3. Jangan daftar semula. Tunggu e-mel/WhatsApp rasmi.\n4. Jika terlalu lama, hubungi pentadbir platform dengan nama masjid, kod dan masa permohonan; jangan kirim kata laluan.\n\n` +
        `## 5. Buka pautan kelulusan dan tetapkan kata laluan\n\n${mdImage('imej/04-tetapkan-kata-laluan.png', 'Tetapkan kata laluan pertama')}\n\n` +
        `1. Selepas diluluskan, buka pautan log masuk yang diterima. Pautan sah 15 minit dan sekali guna.\n` +
        `2. Pastikan domain ialah \`bakwim.my\`; jangan masukkan kata laluan pada domain lain.\n` +
        `3. Cipta kata laluan panjang dan unik.\n` +
        `4. Taip semula kata laluan yang sama.\n` +
        `5. Tekan **Simpan & Teruskan**.\n` +
        `6. Jika pautan tamat, gunakan halaman Log Masuk untuk meminta pautan baharu.\n\n` +
        `## 6. Persediaan kali pertama\n\n${mdImage('imej/05-persediaan-pertama.png', 'Persediaan tenant kali pertama')}\n\n` +
        `1. Tekan **Mula Persediaan Berpandu**.\n` +
        `2. Isi jawatan, telefon rasmi dan pilihan nombor WhatsApp.\n` +
        `3. Jemput ahli awal dengan role yang tepat.\n` +
        `4. Lengkapkan tetapan masjid dan notifikasi.\n` +
        `5. Jika perlu Langkau, kembali kemudian melalui menu Persediaan; jangan biarkan tetapan intake/ahli tidak disahkan.\n` +
        `6. Selepas ini, teruskan panduan dalam folder \`01-Admin-Kerani\`.\n\n` +
        `## 7. Log masuk tanpa kata laluan\n\n${mdImage('imej/06-log-masuk-pautan.png', 'Minta pautan log masuk')}\n\n` +
        `1. Buka \`https://bakwim.my/log-masuk\`.\n2. Masukkan e-mel atau nombor telefon berdaftar.\n3. Tekan **Hantar Pautan Log Masuk** sekali.\n4. Semak e-mel/WhatsApp dan buka pautan dalam 15 minit.\n5. Respons halaman tidak mengesahkan kewujudan akaun demi keselamatan.\n6. Jika sudah menetapkan kata laluan, gunakan pautan **Log masuk dengan kata laluan**.\n\n` +
        `## 8. Keselamatan pendaftaran\n\n` +
        `- Jangan daftar bagi pihak masjid tanpa kuasa.\n- Jangan kongsi pautan sekali guna, kata laluan atau kod Telegram/WhatsApp.\n- Jangan cipta permohonan pendua untuk mengatasi kelewatan.\n- Jika menerima pautan tanpa memohon, abaikan dan laporkan.\n- Selepas masuk, sahkan nama/slug tenant. Jika salah, log keluar dan hubungi platform.\n\n` +
        `## 9. Bantuan dan pelaporan masalah\n\n` +
        `1. Catat nama masjid, kod akronim, masa permohonan dan mesej ralat.\n` +
        `2. Hubungi operator platform melalui saluran rasmi yang dipaparkan organisasi.\n` +
        `3. Jangan sertakan kata laluan, token, pautan sekali guna atau dokumen pengenalan dalam laporan awal.\n` +
        `4. Jika menerima pautan atau mesej mencurigakan, jangan klik; laporkan domain/nombor penghantar dan masa penerimaan.\n`;
}

const internalKeys = ['admin_masjid', 'pengerusi', 'setiausaha', 'bendahari', 'nazir', 'ketua_imam', 'ajk', 'audit'];
for (const key of internalKeys) {
    const role = manifest.roles[key];
    await writeFile(path.join(root, role.folder, 'MANUAL-PENGGUNA.md'), internalManual(key, role), 'utf8');
}
await writeFile(path.join(root, manifest.public.folder, 'MANUAL-PENGGUNA.md'), publicManual(), 'utf8');

const matrixRows = internalKeys.map((key, index) => {
    const role = manifest.roles[key];
    return `| ${index + 1} | ${role.label} | ${role.actualPages} | ${role.extras.length + 1} | ${role.crossTenantStatus} | [Buka manual](<${role.folder}/MANUAL-PENGGUNA.md>) |`;
}).join('\n');

const readme = `# Manual Pengguna Diwan\n\n` +
    `Pakej manual ini mempunyai **9 folder persona**: lapan role tenant dan satu aliran orang awam. Superadmin ialah operator platform global, bukan role tenant, maka tidak termasuk dalam sembilan folder yang diminta.\n\n` +
    `## Ringkasan verifikasi\n\n` +
    `- Tarikh: ${generatedDate}.\n` +
    `- Browser: Google Chrome melalui Playwright, konteks berasingan bagi setiap role.\n` +
    `- Pangkalan data: SQLite latihan terasing; tiada mutasi data production.\n` +
    `- Halaman sidebar: 111/111 mendapat HTTP 200.\n` +
    `- Ujian URL tenant lain: 8/8 mendapat HTTP 404.\n` +
    `- Tangkapan: 223 PNG beranotasi, termasuk modal, viewer PDF dan pendaftaran penuh.\n` +
    `- Viewer: setiap role menunggu “Halaman 1 dipaparkan” dan canvas PDF berisi sebelum gambar.\n\n` +
    `| # | Persona | Halaman | Gambar tindakan + login | Silang tenant | Manual |\n|---:|---|---:|---:|---:|---|\n${matrixRows}\n` +
    `| 9 | Orang Awam / Pendaftaran | 6 keadaan | 6 | Tidak berkenaan | [Buka manual](<09-Orang-Awam-Pendaftaran/MANUAL-PENGGUNA.md>) |\n\n` +
    `## Cara membaca gambar\n\n` +
    `Garis merah menunjukkan kawalan penting. Bulatan merah bernombor dipadankan dengan langkah “Nombor pada gambar”. Gambar menggunakan data latihan MAM; jangan anggap nama contoh sebagai data sebenar.\n\n` +
    `## Rujukan pengurusan rekod\n\n` +
    `Manual diselaraskan dengan prinsip dalam dokumen rujukan pengguna:\n\n` +
    `- Tatacara Pengurusan Rekod Elektronik dalam DDMS di Pejabat Awam (2020): pewujudan, penawanan, klasifikasi, minit, carian, pembetulan, fail/jilid, sistem hibrid, retensi dan pelupusan.\n` +
    `- Panduan Pengguna DDMS 2.0: log masuk, dashboard, menawan rekod, lampiran, paparan, cetak/muat turun, minit dan carian.\n\n` +
    `Diwan bukan salinan DDMS 2.0; nama butang dan permission dalam manual mesti mengikut UI Diwan yang ditangkap.\n\n` +
    `## Istilah ringkas\n\n` +
    `- **Tenant:** ruang data satu masjid.\n- **Peti Masuk:** dokumen belum diklasifikasi/difailkan.\n- **Rekod:** dokumen rasmi selepas difailkan.\n- **Fail:** bekas klasifikasi untuk rekod/kandungan.\n- **Minit:** arahan atau edaran tindakan.\n- **s.k.:** salinan kepada, untuk makluman.\n- **u.p.:** untuk perhatian, metadata orang/unit khusus.\n- **Principal/Delegate:** pemilik kuasa dan wakil sementara.\n- **Legal Hold:** tahan pelupusan atas sebab undang-undang/audit/siasatan.\n- **Hibrid:** kandungan elektronik dengan salinan/fail fizikal yang dijejak.\n`;

await writeFile(path.join(root, 'README.md'), readme, 'utf8');
console.log(JSON.stringify({ manuals: 9, root: path.relative(process.cwd(), root) }, null, 2));
