// F6-W5 — PEMETAAN MUKTAMAD 144 langkah generik.
//
// Sumber kebenaran tunggal untuk kedua-dua `sunting-katalog-w5.mjs` dan
// `tambah-registri-w5.mjs`. Setiap entri ialah salah satu:
//   ['<target>']                 → naik taraf, route langkah TIDAK diubah
//   ['<target>', '<route>']      → naik taraf DAN tetapkan `route` langkah
//   null                         → kekal generik; sebab wajib dalam JUSTIFIKASI di bawah
//
// Peraturan W5-1 (rujuk INVENTORI-W5.md §2): sasaran hanya dinaikkan jika ia kelihatan dalam
// keadaan LALAI halaman. Kesemua 144 langkah `wait_for_user:false`, jadi tour tidak pernah
// melakukan tindakan — sasaran dalam modal/wizard/halaman butiran mustahil dicapai.

export const R = {
    retensi: '/app/{tenant}/retensi',
    delegasiCreate: '/app/{tenant}/delegasi/create',
    classnodeCreate: '/app/{tenant}/classification-nodes/create',
    retensiRuleCreate: '/app/{tenant}/retensi-peraturan/create',
};

export const PETA = {
    'tenant.dashboard': { 2: ['dashboard-stats'], 3: ['dashboard-checklist'] },

    'tenant.sensitive-access-logs': {
        1: ['sensitive-log-search'], 2: ['sensitive-log-record'], 3: ['sensitive-log-target'], 4: null,
    },

    'tenant.log-aktiviti': {
        1: ['log-filters'], 2: ['log-search'], 3: ['log-detail'], 4: ['log-time'], 5: null,
    },

    'tenant.persediaan': {
        1: ['onboarding-start'], 2: null, 3: null, 4: null, 5: null, 6: ['nav-primary'],
    },

    'tenant.ahli-peranan': {
        1: ['members-invite'], 2: null, 3: ['members-list'], 4: ['members-actions'], 5: null, 6: ['members-role'],
    },

    'tenant.classification-nodes': {
        1: ['classnode-search'],
        2: ['classnode-parent', R.classnodeCreate],
        3: ['classnode-code', R.classnodeCreate],
        4: ['classnode-title', R.classnodeCreate],
        5: null,
    },

    'tenant.retensi-peraturan': {
        1: ['retention-schedule', R.retensi],
        2: ['retention-record-type', R.retensiRuleCreate],
        3: ['retention-years', R.retensiRuleCreate],
        4: ['retention-action', R.retensiRuleCreate],
        5: ['retention-hold', R.retensi],
    },

    'tenant.tetapan-masjid': {
        1: ['mosque-settings-profile'], 2: null, 3: null, 4: null, 5: ['mosque-settings-whatsapp'], 6: null,
    },

    'tenant.penggunaan': {
        1: ['storage-usage'], 2: ['storage-orders'], 3: ['storage-add'], 4: null, 5: null,
    },

    'tenant.retensi': {
        1: ['retention-hold'], 2: ['retention-schedule'], 3: null, 4: null, 5: ['retention-export'],
    },

    'tenant.delegasi': {
        1: ['delegation-principal', R.delegasiCreate],
        2: ['delegation-delegate', R.delegasiCreate],
        3: ['delegation-capabilities', R.delegasiCreate],
        4: ['delegation-starts', R.delegasiCreate],
        5: null,
        6: ['delegation-revoke'],
    },

    'tenant.profil': {
        1: ['profil-akaun'], 2: ['profil-notifikasi'], 3: null, 4: ['profil-ujian'], 5: ['profil-kata-laluan'], 6: null,
    },

    'tenant.peti-masuk': {
        1: ['inbox-record'], 2: ['inbox-view'], 3: ['inbox-upload'],
        4: ['inbox-classify'], 5: ['inbox-spam'], 6: ['inbox-source'],
    },

    'tenant.records': { 1: ['records-search'], 2: ['records-view'], 3: null, 4: null, 5: null },

    'tenant.registry-files': {
        1: ['regfiles-search'], 2: ['regfiles-status'], 3: ['regfiles-medium'], 4: null, 5: null, 6: null,
    },

    'tenant.minit-saya': {
        1: ['minit-filters'], 2: ['minit-record'], 3: ['minit-complete'], 4: ['minit-reply'], 5: null, 6: null,
    },

    // ⛔ KESELURUHAN guide dijustifikasikan. `/kelulusan` menapis `approver_id = saya` dan
    // `admin_masjid` (akaun yang `accountFor()` pilih, dan peranan pertama guide ini) TIADA
    // `approvals.decide`. Bukan jurang benih — reka bentuk peranan. Lihat INVENTORI §3.1(a).
    'tenant.kelulusan': { 1: null, 2: null, 3: null, 4: null, 5: null, 6: null },

    'tenant.carian': {
        1: ['search-text'], 2: ['search-filters'], 3: ['search-parties'], 4: ['search-submit'],
        5: ['search-save'], 6: ['search-saved'], 7: ['search-favourite'],
    },

    'tenant.laporan': { 1: ['report-summary'], 2: ['report-breakdown'], 3: ['report-export'], 4: null },

    'tenant.pembetulan-rekod': { 1: null, 2: null, 3: null, 4: ['correction-decision'], 5: ['correction-diff'] },

    'tenant.bantuan': { 1: ['help-search'], 2: ['nav-primary'] },
    'tenant.analitik-bantuan': { 1: ['analytics-metrics'], 2: ['nav-primary'] },
    'tenant.tiket-sokongan': { 1: ['tickets-search'], 2: ['nav-primary'] },

    // ── Panel admin: corak tetap 3 langkah ──────────────────────────────────────────────
    'admin.dashboard': { 1: ['nav-primary'], 2: ['dashboard-stats'], 3: null },
    'admin.mosques': { 1: ['nav-primary'], 2: ['platform-mosques'], 3: null },
    'admin.users': { 1: ['nav-primary'], 2: ['platform-users'], 3: null },
    'admin.storage-orders': { 1: ['nav-primary'], 2: ['platform-storage-orders'], 3: null },
    'admin.status-sambungan': { 1: ['nav-primary'], 2: ['platform-channels'], 3: null },
    'admin.whatsapp-platform': { 1: ['nav-primary'], 2: ['platform-whatsapp'], 3: null },
    'admin.tetapan-platform': { 1: ['nav-primary'], 2: ['platform-settings'], 3: null },
    'admin.profil-saya': { 1: ['nav-primary'], 2: ['profil-akaun'], 3: null },

    // ── Panel admin: corak 2 langkah ────────────────────────────────────────────────────
    'admin.bantuan': { 1: ['help-search'], 2: ['nav-primary'] },
    'admin.analitik-bantuan': { 1: ['analytics-metrics'], 2: ['nav-primary'] },
    'admin.tiket-sokongan': { 1: ['tickets-search'], 2: ['nav-primary'] },
    'admin.help-announcements': { 1: ['platform-announcements'], 2: ['nav-primary'] },
};

/** Sebab justifikasi per langkah (≥40 aksara, DIUKUR — bukan ayat automatik). */
export const JUSTIFIKASI = {
    'tenant.sensitive-access-logs#4': 'Melaporkan akses tidak dikenali kepada Admin dan wakil perlindungan data ialah tindakan di LUAR sistem; tiada kawalan skrin yang sepadan.',
    'tenant.log-aktiviti#5': 'Skop log bendahari ditentukan dasar akses peranan, bukan kawalan pada skrin; tiada elemen yang boleh disorot tanpa mengelirukan.',
    'tenant.persediaan#2': 'Medan jawatan dan telefon hidup dalam wizard MODAL yang hanya terbuka selepas menekan Mula Persediaan Berpandu; guide halaman ini tiada langkah tindakan (wait_for_user:false).',
    'tenant.persediaan#3': 'Pilihan sumber nombor WhatsApp berada pada langkah 2 wizard modal; aliran penuh bersasar spesifik dalam screen.persediaan-berpandu.',
    'tenant.persediaan#4': 'Repeater ahli awal berada pada langkah 3 wizard modal; ia tidak wujud dalam keadaan lalai halaman.',
    'tenant.persediaan#5': 'Semakan peranan berlaku pada langkah 4 wizard modal sebelum simpan; tiada kawalan setara pada halaman.',
    'tenant.ahli-peranan#2': 'Medan e-mel dan pilihan peranan berada dalam modal Jemput Ahli yang belum terbuka pada langkah ini.',
    'tenant.ahli-peranan#5': 'Set Kata Laluan Sementara ialah item DALAM dropdown Tindakan yang sama seperti langkah 4; menyasarkannya akan menyorot elemen yang identik.',
    'tenant.classification-nodes#5': 'Nasihat dasar (nyahaktifkan nod lama, jangan ubah nod terpakai) — tiada satu kawalan tunggal pada skrin yang mewakilinya.',
    'tenant.tetapan-masjid#2': 'Suis intake WhatsApp dan e-mel berada dalam modal Sunting Tetapan; halaman lalai hanya memaparkan nilai semasa.',
    'tenant.tetapan-masjid#3': 'Medan kata kunci intake berada dalam modal Sunting Tetapan yang belum terbuka pada langkah ini.',
    'tenant.tetapan-masjid#4': 'Senarai alamat pengirim dipercayai berada dalam modal Sunting Tetapan; halaman lalai tidak merendernya.',
    'tenant.tetapan-masjid#6': 'Mematikan notifikasi apabila peranti hilang ialah keputusan operasi merentas beberapa kawalan, bukan satu butang.',
    'tenant.penggunaan#4': 'Nombor invois hanya wujud selepas pesanan storan dijana; benih demo dan keadaan lalai halaman tiada pesanan.',
    'tenant.penggunaan#5': 'Amaran dasar bahawa pesanan menunggu bukan kuota aktif — konsep, bukan kawalan skrin.',
    'tenant.retensi#3': 'Butang Legal Hold hanya dirender pada baris rekod yang menghampiri tempoh retensi; benih demo tiada rekod dalam skop 12 bulan (diukur: retention_due_at 0).',
    'tenant.retensi#4': 'Tarik balik hold memerlukan rekod yang sedang berpegangan; keadaan itu tidak wujud dalam keadaan lalai mahupun benih demo.',
    'tenant.delegasi#5': 'Label "bagi pihak" muncul pada TINDAKAN yang delegate buat (minit, kelulusan), bukan pada halaman Delegasi ini.',
    'tenant.profil#3': 'Pautan sambung Telegram berada dalam modal Tetapan Notifikasi; halaman lalai hanya memaparkan status.',
    'tenant.profil#6': 'Tindakan pemulihan kecemasan merentas dua kawalan berbeza yang langkah 2 dan 5 sudah sorot secara berasingan.',
    'tenant.records#3': 'Tab OCR, Lampiran, Minit, Kelulusan dan Audit hidup pada halaman BUTIRAN rekod; URLnya dinamik (/records/{id}) jadi katalog tidak boleh mengisytiharkannya sebagai route langkah.',
    'tenant.records#4': 'Butang Kegemaran berada pada halaman butiran rekod, bukan pada senarai ini.',
    'tenant.records#5': 'Amaran dasar bahawa tindakan ditentukan peranan — tiada elemen tunggal yang mewakilinya.',
    'tenant.registry-files#4': 'Keluarkan Fail, Terima Pulangan dan Pindah Lokasi ialah tindakan halaman BUTIRAN fail; route butiran dinamik dan tidak boleh diisytihar dalam katalog.',
    'tenant.registry-files#5': 'Tutup fail dan buka jilid baharu ialah tindakan pada halaman butiran fail berkenaan.',
    'tenant.registry-files#6': 'Geran akses khas diurus dalam relation manager pada halaman butiran fail sulit.',
    'tenant.minit-saya#5': 'Perbezaan penerima tindakan dan s.k. ialah konsep tanggungjawab; lajur Penerima boleh-togol dan tersembunyi secara lalai.',
    'tenant.minit-saya#6': 'Atribusi "oleh X bagi pihak Y" hanya muncul selepas seorang delegate benar-benar bertindak; ia tiada dalam keadaan lalai.',
    'tenant.kelulusan#1': 'Jadual kelulusan menapis approver_id = pengguna semasa, dan admin_masjid TIADA kebenaran approvals.decide (config/roles.php) — skrin ini sentiasa kosong untuk peranan utama guide ini.',
    'tenant.kelulusan#2': 'Membuka rekod asal membawa pengguna ke halaman butiran rekod; tiada baris pada skrin ini untuk peranan guide.',
    'tenant.kelulusan#3': 'Butang Lulus/Tolak ialah aksi baris; tiada baris kerana admin_masjid tidak boleh menjadi pelulus.',
    'tenant.kelulusan#4': 'Medan kata laluan dan nota berada dalam modal keputusan yang hanya boleh dibuka oleh pelulus yang ditetapkan.',
    'tenant.kelulusan#5': 'Amaran dasar keselamatan kata laluan — konsep, bukan kawalan skrin.',
    'tenant.kelulusan#6': 'Lajur status dan "bagi pihak" ialah sel baris; jadual kosong untuk peranan yang guide ini tujukan.',
    'tenant.laporan#4': 'Membuka dan mengawal perkongsian fail CSV berlaku di luar sistem selepas muat turun.',
    'tenant.pembetulan-rekod#1': 'Butang Mohon Pembetulan berada pada halaman BUTIRAN rekod, bukan pada senarai permohonan ini.',
    'tenant.pembetulan-rekod#2': 'Medan sebab dan cadangan perubahan berada dalam modal Mohon Pembetulan pada halaman butiran rekod.',
    'tenant.pembetulan-rekod#3': 'Penghantaran berlaku dalam modal pada halaman butiran rekod; skrin ini hanya memaparkan hasilnya.',
    'admin.dashboard#3': 'Amaran operasi supaya tidak mengulang tindakan semasa sistem memproses — konsep merentas halaman, bukan kawalan tunggal.',
    'admin.mosques#3': 'Amaran operasi supaya tidak mengulang tindakan semasa sistem memproses — konsep merentas halaman, bukan kawalan tunggal.',
    'admin.users#3': 'Amaran operasi supaya tidak mengulang tindakan semasa sistem memproses — konsep merentas halaman, bukan kawalan tunggal.',
    'admin.storage-orders#3': 'Amaran operasi supaya tidak mengulang tindakan semasa sistem memproses — konsep merentas halaman, bukan kawalan tunggal.',
    'admin.status-sambungan#3': 'Amaran operasi supaya tidak mengulang tindakan semasa sistem memproses — konsep merentas halaman, bukan kawalan tunggal.',
    'admin.whatsapp-platform#3': 'Amaran operasi supaya tidak mengulang tindakan semasa sistem memproses — konsep merentas halaman, bukan kawalan tunggal.',
    'admin.tetapan-platform#3': 'Amaran operasi supaya tidak mengulang tindakan semasa sistem memproses — konsep merentas halaman, bukan kawalan tunggal.',
    'admin.profil-saya#3': 'Amaran operasi supaya tidak mengulang tindakan semasa sistem memproses — konsep merentas halaman, bukan kawalan tunggal.',
};
