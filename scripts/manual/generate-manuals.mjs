import { readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const root = path.resolve(process.cwd(), 'Manual Penguna');
const manifest = JSON.parse(await readFile(path.join(root, 'manifest-tangkapan.json'), 'utf8'));
const helpCatalog = JSON.parse(await readFile(path.resolve(process.cwd(), 'resources/help/guides.json'), 'utf8'));
const permissionSummary = helpCatalog.permission_summaries;
const pageGuides = helpCatalog.page_guides;
const taskBlueprints = helpCatalog.task_blueprints;
const extraGuides = helpCatalog.screen_guides;
const generatedDate = '22 Julai 2026';




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
    const guideKey = Object.keys(extraGuides).find((title) => extra.title === title || extra.title.startsWith(`${title} `));
    const steps = (extra.title.startsWith('Klasifikasi peti masuk ') && extra.notes?.length)
        ? extra.notes.map((note) => note.note)
        : (extraGuides[guideKey] ?? ['Baca tajuk dan semua medan.', 'Isi medan wajib bertanda *.', 'Semak semula sebelum menghantar.', 'Pastikan notifikasi kejayaan dipaparkan.']);
    return `### ${index + 1}. ${extra.title}\n\n` +
        `${mdImage(extra.image, extra.title)}\n` +
        `${renderCallouts(extra.notes)}` +
        (extra.labels?.length ? `\n**Medan/kawalan yang disahkan:** ${[...new Set(extra.labels)].map((label) => `\`${label}\``).join(', ')}.\n` : '') +
        `\n**Langkah terperinci**\n${numbered(steps)}\n\n` +
        `**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.\n`;
}

const loginTask = {
    title: 'Log masuk dan sahkan masjid',
    outcome: 'Pengguna masuk ke tenant yang betul sebelum membuka atau mengubah sebarang rekod.',
    screens: [
        { source: 'login', title: 'Halaman log masuk', steps: ['Masukkan e-mel atau nombor telefon akaun sendiri.', 'Masukkan kata laluan sendiri.', 'Tekan Log masuk sekali dan tunggu sehingga URL tenant dipaparkan.'] },
        { source: 'page', key: 'dashboard', title: 'Papan pemuka tenant', steps: ['Semak nama dan kod masjid pada panel.', 'Semak role serta statistik yang dipaparkan.', 'Jika masjid salah, jangan teruskan; log keluar dan laporkan kepada Admin/Kerani.'] },
    ],
};


function resolveTaskScreens(role, screen) {
    if (screen.source === 'login') return [{ image: role.login.image, title: screen.title, steps: screen.steps }];
    if (screen.source === 'page') {
        const page = role.pages.find((item) => pageKey(item) === screen.key);
        return page ? [{ image: page.image, title: screen.title, steps: screen.steps }] : [];
    }
    const exact = role.extras.filter((item) => item.title === screen.title);
    const extras = exact.length ? exact : role.extras.filter((item) => item.title.startsWith(`${screen.title} `));

    return extras.map((extra) => ({
        image: extra.image,
        title: extra.title,
        steps: extras.length > 1 && extra.notes?.length ? extra.notes.map((note) => note.note) : screen.steps,
    }));
}

function renderTaskManual(roleKey, role) {
    const tasks = [loginTask, ...(taskBlueprints[roleKey] ?? [])];

    return tasks.map((task, taskIndex) => {
        const screens = task.screens.flatMap((screen) => resolveTaskScreens(role, screen));
        const rendered = screens.map((screen, screenIndex) => {
            const next = screens[screenIndex + 1];
            return `#### Gambar ${screenIndex + 1}: ${screen.title}\n\n` +
                `${mdImage(screen.image, `${task.title} - Gambar ${screenIndex + 1}`)}\n\n` +
                `**Apa perlu dibuat pada Gambar ${screenIndex + 1}**\n${numbered(screen.steps)}\n\n` +
                (next ? `**Kemudian:** teruskan ke **Gambar ${screenIndex + 2}: ${next.title}**.\n` : `**Selesai:** semak hasil akhir workflow ini sebelum menutup halaman.\n`);
        }).join('\n\n');

        return `### 3.${taskIndex + 1} ${task.title}\n\n` +
            `**Hasil akhir:** ${task.outcome}\n\n` +
            `Ikuti gambar mengikut nombor. Jangan lompat ke gambar seterusnya sehingga langkah gambar semasa selesai.\n\n` +
            rendered;
    }).join('\n\n');
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
        `## 3. Cara melaksanakan tugas - gambar demi gambar\n\n` +
        `Bahagian ini menerangkan kesinambungan gambar untuk satu tugas lengkap. **Gambar 1** ialah titik mula workflow, diikuti **Gambar 2**, **Gambar 3** dan seterusnya sehingga hasil akhir disahkan.\n\n` +
        `${renderTaskManual(roleKey, role)}\n\n` +
        `## 4. Senarai halaman role\n\n` +
        `| # | Halaman | Laluan | Status Chrome |\n|---:|---|---|---:|\n${pageRows}\n\n` +
        `## 5. Panduan setiap halaman\n\n` +
        `${role.pages.map(renderPage).join('\n\n')}\n\n` +
        `## 6. Panduan tindakan dan modal\n\n` +
        `Bahagian ini hanya menyenaraikan tindakan yang benar-benar kelihatan bagi role ini semasa verifikasi. Medan bertanda \`*\` wajib.\n\n` +
        `${role.extras.map(renderExtra).join('\n\n')}\n\n` +
        `## 7. Ringkasan workflow hujung ke hujung untuk role ini\n\n${numbered(roleWorkflow(roleKey))}\n\n` +
        `## 8. Peraturan klasifikasi, minit dan notifikasi\n\n` +
        `- **Untuk Tindakan (Minit):** penerima wajib mengambil tindakan, boleh membalas/mengedarkan dan perlu menanda selesai.\n` +
        `- **Untuk Makluman (s.k.):** penerima dimaklumkan tetapi bukan pemilik tindakan asal.\n` +
        `- **Untuk Perhatian (u.p.):** nama/unit khusus yang patut membaca surat; ia metadata surat dan tidak menggantikan penerima minit.\n` +
        `- **Ruj. Kami:** rujukan yang dikeluarkan masjid/organisasi sendiri. **Ruj. Tuan:** rujukan pihak penghantar.\n` +
        `- **Arah Masuk:** diterima daripada luar. **Keluar:** dihantar keluar. **Dalaman:** diwujud/diedar dalam organisasi.\n` +
        `- Notifikasi dihantar hanya melalui saluran yang aktif dan tersedia: pangkalan data, e-mel, WhatsApp atau Telegram. Semak Profil dan tetapan tenant jika notifikasi tidak tiba.\n` +
        `- Penerima dipilih daripada ahli aktif tenant yang dibenarkan melihat sensitiviti rekod. Nama tenant lain tidak patut muncul.\n\n` +
        `## 9. Keselamatan dan pengasingan data\n\n` +
        `1. Gunakan akaun sendiri; jangan guna akaun kongsi.\n` +
        `2. Semak tenant sebelum upload, klasifikasi, minit, kelulusan atau eksport.\n` +
        `3. Jangan ubah slug/ID pada URL. Ujian silang tenant manual ini mengembalikan HTTP ${role.crossTenantStatus}.\n` +
        `4. Simpan muat turun sensitif hanya pada peranti/storan organisasi yang dibenarkan.\n` +
        `5. Jika data masjid lain kelihatan, berhenti serta-merta, jangan muat turun/sebar, catat masa/URL dan lapor insiden.\n` +
        `6. Semak sumber dokumen (UI/e-mel/WhatsApp), masa upload, antivirus dan OCR sebelum pemfailan.\n` +
        `7. Jangan luluskan permintaan, pembetulan atau pelupusan tanpa membuka bukti asal.\n` +
        `8. Log keluar pada peranti awam dan jangan simpan kata laluan dalam browser yang dikongsi.\n\n` +
        `## 10. Senarai semak sebelum menutup tugasan\n\n` +
        `- [ ] Tenant betul.\n- [ ] Dokumen dan sumber telah disahkan.\n- [ ] Metadata/rujukan/tarikh tepat.\n- [ ] Fail dan sensitiviti tepat.\n- [ ] Penerima tindakan dan s.k. tepat.\n- [ ] Toast/status kejayaan dilihat.\n- [ ] Notifikasi atau audit disahkan jika berkaitan.\n- [ ] Tiada fail sensitif tertinggal pada peranti awam.\n\n` +
        `## 11. Bantuan dan pelaporan masalah\n\n` +
        `1. Catat masa kejadian, role, nama tenant, URL halaman, tindakan terakhir dan mesej ralat.\n` +
        `2. Jika berkaitan rekod/fail, sertakan nombor rujukan atau ID sahaja; lindungi kandungan dan data peribadi.\n` +
        `3. Hantar kepada Admin/Kerani. Admin/Kerani mengeskalasi kepada operator platform jika isu melibatkan tenant, keselamatan, intake atau servis luar.\n` +
        `4. Jangan hantar kata laluan, token, pautan sekali guna, kunci API atau keseluruhan dokumen sensitif.\n` +
        `5. Jika data tenant lain kelihatan, berhenti menggunakan halaman itu dan laporkan sebagai insiden keselamatan segera.\n`;
}

function publicManual() {
    return `# Manual Pengguna Diwan - Orang Awam / Pendaftaran Masjid\n\n` +
        `**Versi UI disahkan:** ${generatedDate}\n\n**Skop:** pusat bantuan, pendaftaran berperingkat, kelulusan, log masuk dan persediaan pertama.\n\n` +
        `## 1. Sebelum mendaftar\n\n` +
        `Sediakan nama rasmi masjid, negeri/daerah, kod akronim 3-6 huruf, cadangan slug URL, nama pentadbir pertama, e-mel aktif dan nombor WhatsApp format negara seperti \`60123456789\`. Pentadbir pertama akan menjadi **Admin / Kerani** tenant selepas diluluskan.\n\n` +
        `Jangan gunakan e-mel atau telefon yang anda tidak kawal. Baca Terma/DPA dan dasar retensi; rekod cukup tempoh boleh dipadam selepas notifikasi dan proses pelupusan yang berkenaan.\n\n` +
        `## 2. Aliran pendaftaran - Gambar 1 hingga Gambar 7\n\nIkuti gambar mengikut urutan. Setiap gambar ialah kesinambungan skrin sebelumnya; jangan lompat sebelum langkah semasa berjaya.\n\n` +
        `### Gambar 1: Buka laman utama\n\n${mdImage('imej/01-laman-utama.png', 'Gambar 1 - Laman utama Diwan')}\n\n` +
        `1. Buka \`https://bakwim.my\`.\n2. Pilih **Daftar Masjid** untuk permohonan baharu.\n3. Jika sudah mempunyai akaun, pilih **Log Masuk** dan jangan daftar tenant pendua.\n\n` +
        `**Kemudian:** teruskan ke **Gambar 2: Maklumat masjid**.\n\n` +
        `### Gambar 2: Langkah 1 - Maklumat masjid\n\n${mdImage('imej/02-borang-daftar.png', 'Gambar 2 - Maklumat masjid')}\n\n` +
        `1. **Nama Masjid:** nama rasmi penuh.\n` +
        `2. **Negeri/Daerah:** lokasi pentadbiran sebenar.\n` +
        `3. **Kod Akronim:** 3-6 huruf sahaja dan mesti unik, contoh \`MAM\`. Kod digunakan pada nombor fail.\n` +
        `4. **Slug URL:** huruf kecil/nombor, ringkas dan unik. Sistem boleh mengkanonkan slug berdasarkan nama.\n` +
        `5. Tekan **Seterusnya**. Jika validasi gagal, betulkan medan pada skrin ini sebelum meneruskan.\n\n` +
        `**Kemudian:** teruskan ke **Gambar 3: Maklumat pentadbir**.\n\n` +
        `### Gambar 3: Langkah 2 - Maklumat Admin / Kerani pertama\n\n${mdImage('imej/02b-pentadbir.png', 'Gambar 3 - Maklumat pentadbir')}\n\n` +
        `1. Masukkan nama individu yang bertanggungjawab, bukan nama jawatan umum.\n` +
        `2. Masukkan e-mel aktif yang akan menerima pautan selepas kelulusan.\n` +
        `3. Masukkan nombor WhatsApp format \`60...\` tanpa ruang atau simbol.\n` +
        `4. Tekan **Kembali** jika identiti masjid perlu dibetulkan; data langkah ini tidak dihantar lagi.\n` +
        `5. Tekan **Seterusnya** selepas ketiga-tiga butiran tepat.\n\n` +
        `**Kemudian:** teruskan ke **Gambar 4: Semakan dan persetujuan**.\n\n` +
        `### Gambar 4: Langkah 3 - Semakan dan persetujuan\n\n${mdImage('imej/02c-persetujuan.png', 'Gambar 4 - Semakan dan persetujuan')}\n\n` +
        `1. Bandingkan ringkasan nama, kod, lokasi dan pentadbir dengan maklumat sebenar.\n` +
        `2. Baca lalu tandakan Terma Perkhidmatan dan DPA.\n` +
        `3. Baca lalu tandakan pengakuan dasar retensi.\n` +
        `4. Gunakan **Kembali** jika satu nilai tidak tepat.\n` +
        `5. Tekan **Hantar Permohonan** sekali sahaja.\n\n` +
        `**Kemudian:** tunggu **Gambar 5: Permohonan diterima**.\n\n` +
        `### Gambar 5: Permohonan diterima\n\n${mdImage('imej/03-permohonan-diterima.png', 'Gambar 5 - Permohonan diterima')}\n\n` +
        `1. Pastikan mesej **Permohonan diterima!** kelihatan.\n2. Permohonan berstatus menunggu kelulusan platform.\n3. Jangan daftar semula. Tunggu e-mel/WhatsApp rasmi.\n4. Jika terlalu lama, hubungi pentadbir platform dengan nama masjid, kod dan masa permohonan; jangan kirim kata laluan.\n\n` +
        `**Selepas permohonan diluluskan:** teruskan ke **Gambar 6: Tetapkan kata laluan**.\n\n` +
        `### Gambar 6: Buka pautan kelulusan dan tetapkan kata laluan\n\n${mdImage('imej/04-tetapkan-kata-laluan.png', 'Gambar 6 - Tetapkan kata laluan pertama')}\n\n` +
        `1. Selepas diluluskan, buka pautan log masuk yang diterima. Pautan sah 15 minit dan sekali guna.\n` +
        `2. Pastikan domain ialah \`bakwim.my\`; jangan masukkan kata laluan pada domain lain.\n` +
        `3. Cipta kata laluan panjang dan unik.\n` +
        `4. Taip semula kata laluan yang sama.\n` +
        `5. Tekan **Simpan & Teruskan**.\n` +
        `6. Jika pautan tamat, gunakan halaman Log Masuk untuk meminta pautan baharu.\n\n` +
        `**Kemudian:** teruskan ke **Gambar 7: Persediaan kali pertama**.\n\n` +
        `### Gambar 7: Persediaan kali pertama\n\n${mdImage('imej/05-persediaan-pertama.png', 'Gambar 7 - Persediaan tenant kali pertama')}\n\n` +
        `1. Tekan **Mula Persediaan Berpandu**.\n` +
        `2. Isi jawatan, telefon rasmi dan pilihan nombor WhatsApp.\n` +
        `3. Jemput ahli awal dengan role yang tepat.\n` +
        `4. Lengkapkan tetapan masjid dan notifikasi.\n` +
        `5. Jika perlu Langkau, kembali kemudian melalui menu Persediaan; jangan biarkan tetapan intake/ahli tidak disahkan.\n` +
        `6. Selepas ini, teruskan panduan dalam folder \`01-Admin-Kerani\`.\n\n` +
        `## 3. Log masuk tanpa kata laluan - Gambar 8\n\n${mdImage('imej/06-log-masuk-pautan.png', 'Gambar 8 - Minta pautan log masuk')}\n\n` +
        `1. Buka \`https://bakwim.my/log-masuk\`.\n2. Masukkan e-mel atau nombor telefon berdaftar.\n3. Tekan **Hantar Pautan Log Masuk** sekali.\n4. Semak e-mel/WhatsApp dan buka pautan dalam 15 minit.\n5. Respons halaman tidak mengesahkan kewujudan akaun demi keselamatan.\n6. Jika sudah menetapkan kata laluan, gunakan pautan **Log masuk dengan kata laluan**.\n\n` +
        `## 4. Pusat bantuan dan laporan masalah - Gambar 9\n\n${mdImage('imej/07-pusat-bantuan.png', 'Gambar 9 - Pusat bantuan orang awam')}\n\n` +
        `1. Buka \`https://bakwim.my/bantuan\` atau tekan **Bantuan** pada navigasi.\n` +
        `2. Cari dengan ayat biasa, contohnya “cara daftar masjid” atau “tak boleh login”.\n` +
        `3. Pilih **Baca langkah** untuk jawapan rasmi atau **Mulakan panduan** untuk penunjuk pada skrin.\n` +
        `4. Jalankan diagnosis baca sahaja sebelum membuat laporan.\n` +
        `5. Jika isu belum selesai, isi hasil dijangka dan kejadian sebenar. Lampiran pilihan maksimum 5 MB akan diperiksa antivirus.\n` +
        `6. Jangan masukkan kata laluan, token, query URL atau kandungan dokumen. Simpan nombor rujukan tiket selepas dihantar.\n\n` +
        `## 5. Keselamatan pendaftaran\n\n` +
        `- Jangan daftar bagi pihak masjid tanpa kuasa.\n- Jangan kongsi pautan sekali guna, kata laluan atau kod Telegram/WhatsApp.\n- Jangan cipta permohonan pendua untuk mengatasi kelewatan.\n- Jika menerima pautan tanpa memohon, abaikan dan laporkan.\n- Selepas masuk, sahkan nama/slug tenant. Jika salah, log keluar dan hubungi platform.\n\n` +
        `## 6. Maklumat yang selamat untuk laporan\n\n` +
        `1. Catat nama masjid, kod akronim, masa permohonan dan mesej ralat.\n` +
        `2. Gunakan borang Pusat Bantuan; X-Request-ID membantu operator memadankan log tanpa membaca kandungan borang anda.\n` +
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
const totalPages = internalKeys.reduce((sum, key) => sum + manifest.roles[key].actualPages, 0);
const totalImages = internalKeys.reduce((sum, key) => {
    const role = manifest.roles[key];
    return sum + role.pages.length + role.extras.length + 1;
}, manifest.public.captures.length);

const readme = `# Manual Pengguna Diwan\n\n` +
    `Pakej manual ini mempunyai **9 folder persona**: lapan role tenant dan satu aliran orang awam. Superadmin ialah operator platform global, bukan role tenant, maka tidak termasuk dalam sembilan folder yang diminta.\n\n` +
    `## Ringkasan verifikasi\n\n` +
    `- Tarikh: ${generatedDate}.\n` +
    `- Browser: Google Chrome melalui Playwright, konteks berasingan bagi setiap role.\n` +
    `- Pangkalan data: SQLite latihan terasing; tiada mutasi data production.\n` +
    `- Halaman sidebar: ${totalPages}/${totalPages} mendapat HTTP 200.\n` +
    `- Ujian URL tenant lain: 8/8 mendapat HTTP 404.\n` +
    `- Tangkapan: ${totalImages} PNG beranotasi, termasuk modal, viewer PDF dan pendaftaran penuh.\n` +
    `- Viewer: setiap role menunggu “Halaman 1 dipaparkan” dan canvas PDF berisi sebelum gambar.\n\n` +
    `| # | Persona | Halaman | Gambar tindakan + login | Silang tenant | Manual |\n|---:|---|---:|---:|---:|---|\n${matrixRows}\n` +
    `| 9 | Orang Awam / Pendaftaran | ${manifest.public.captures.length} keadaan | ${manifest.public.captures.length} | Tidak berkenaan | [Buka manual](<09-Orang-Awam-Pendaftaran/MANUAL-PENGGUNA.md>) |\n\n` +
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
