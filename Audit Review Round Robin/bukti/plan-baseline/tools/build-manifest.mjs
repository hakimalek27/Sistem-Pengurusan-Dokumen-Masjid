// F0(ii)/(ii-a)/(iii) pelan pembaikan — penjana manifest baseline TIGA set:
//   cohort     : kohort audit P11 (25 guide tenant / 124 langkah) + angka beku produksi
//   catalogue  : 83 guide / 473 langkah + medan `wave` (W0–W6) & `shard` — GATE keluaran F6/F8
//   role_routes: output `php artisan diwan:role-routes` (lapisan A/B; C = PlanManifestTest/F8)
//
// Partition wave ialah FUNGSI DETERMINISTIK ke atas katalog beku (bukan senarai ditaip):
//   W0 = guide yang mengandungi langkah popover mobile terbukti rosak (centerCovered=true,
//        bukti pusingan-11) · W1 = `screen` dgn ≥1 langkah tindakan bersasar generik ·
//   W2 = `workflow` sama syarat · W3 = baki `screen` · W4 = baki `workflow` ·
//   W5 = `tenant`+`admin` tolak W0 · W6 = `public`.
// Kunci langkah = `<guide_id>#<index1>` (step.id TIDAK unik global — 470/473).
//
// Guna: node build-manifest.mjs --catalog <guides.json> --mobile <production-mobile-all-tour-steps.json>
//         --role-routes <role-routes.json> --out <manifest.json>
// Skrip GAGAL (exit 1) jika mana-mana invarian beku tidak sepadan — ia bukan penjana buta.

import { readFileSync, writeFileSync } from 'node:fs';

const args = {};
for (let i = 2; i < process.argv.length; i += 2) args[process.argv[i].replace(/^--/, '')] = process.argv[i + 1];
for (const k of ['catalog', 'mobile', 'role-routes', 'out']) {
    if (!args[k]) { console.error(`FAIL: --${k} wajib`); process.exit(1); }
}

const GEN = new Set(['page-primary', 'page-content']);
const catalog = JSON.parse(readFileSync(args.catalog, 'utf8'));
const mobile = JSON.parse(readFileSync(args.mobile, 'utf8'));
const roleRoutes = JSON.parse(readFileSync(args['role-routes'], 'utf8'));

// ── Invarian beku (PELAN-PEMBAIKAN.md §1 F0(ii)/(ii-a), disahkan bebas 5×) ──────────────
//
// DUA JENIS NILAI — dibezakan sejak F6-W0 (3 Ogos 2026):
//
//   STRUKTUR  — 83 guide / 473 langkah / partition wave / shard / kohort 25-124 /
//               wait_for_user / unique_step_ids. Ini SKOP kerja; ia tidak boleh berubah
//               tanpa keputusan sedar. Diassert SAMA (fail keras).
//
//   KEMAJUAN  — generic_declared, generic_pp/pc, placeholder_titles,
//               action_steps_with_generic_target, dan medan wave action_generic/
//               placeholder/mobile_defects. Pelan MENJANGKA nilai ini turun
//               (§7: 200→0, 258→0, mobile 6→0). Mengassert kesamaan bermakna gate akan
//               menolak setiap pembaikan F6 — songsang. Diassert sebagai
//               **tidak lebih teruk daripada baseline** (monotonik menurun): kemajuan
//               dibenarkan, regresi ditangkap, dan delta dilaporkan.
//
// `catalog_version` direkod sahaja di sini; PlanManifestTest yang menguatkuasakan manifest
// dijana semula setiap kali katalog berubah.
// PERUBAHAN DENOMINATOR (prosedur README "Nota" — sebab bertulis + kemas KEDUA-DUA penjaga
// dalam commit sama + catat dalam bukti fasa). Dikemas pada F5, 4 Ogos 2026:
//
//  (1) wait_for_user 229 → 228. F5 §6.2 memberi `screen.muat-naik-dokumen` sasaran spesifik.
//      Langkah 4 ("Sahkan toast dan baris baharu") dan 5 ("Semak antivirus sebelum
//      klasifikasi") ialah langkah PEMERHATIAN, bukan tindakan — `wait_for_user: true` pada
//      keduanya menyebabkan butang berkata "Buat pada skrin" untuk sesuatu yang pengguna
//      hanya perlu BACA (−2). `public.login#2` pula ialah tindakan sebenar (hantar pautan)
//      dan mesti menunggu supaya tour tamat apabila pautan dihantar (+1). Net 229 − 2 + 1.
//
//  (2) W1 28/140 → 27/135 dan W3 1/11 → 2/16. `waveOf()` mengelaskan guide `screen`
//      mengikut ADA/TIADA langkah tindakan-generik. F5 membaiki kesemua 5 langkah
//      `screen.muat-naik-dokumen`, jadi guide itu tidak lagi mempunyai kerja W1 dan
//      berpindah ke W3 ("screen, tiada tindakan generik"). Jumlah kekal 83/473 dan
//      struktur shard `screen` kekal 29/151 — hanya senarai kerja yang mengecil, iaitu
//      maksud wave itu sendiri. Jangkakan perpindahan serupa pada setiap gelombang F6.
//  (4) F6-W1 — 4 Ogos 2026. Kesemua 27 guide `screen` yang berbaki diberi sasaran
//      spesifik, jadi `waveOf()` memindahkan SEMUANYA W1 → W3 (definisi wave: "screen yang
//      MASIH ada langkah tindakan-generik"). W1 kini 0/0 dan W3 memuatkan seluruh shard
//      `screen` 29/151. Jumlah 83/473 TIDAK berubah — hanya senarai kerja yang kosong,
//      iaitu maksud wave itu siap.
//      `wait_for_user` 228 → 190 (−38): langkah PEMERHATIAN yang tersalah label sebagai
//      tindakan (cth. "Semak baki kuota", "Semak tarikh dan masa tepat", "Pantau status")
//      dibetulkan kepada `false` seperti diarahkan §7.2 langkah 3 — nilai salah itulah punca
//      CTA "Buat pada skrin" pada langkah yang pengguna hanya perlu BACA.
//      Shard `screen.action_steps` 151 → 111 mengikut pembetulan yang sama.
//  (5) F6-W2 — 5 Ogos 2026. Kesemua 13 guide `workflow` yang berbaki diberi sasaran
//      spesifik, jadi `waveOf()` memindahkan SEMUANYA W2 → W4 (definisi wave: "workflow yang
//      MASIH ada langkah tindakan-generik"). W2 kini 0/0 dan W4 memuatkan seluruh shard
//      `workflow` 14/158. Jumlah 83/473 TIDAK berubah — hanya senarai kerja yang kosong.
//      `wait_for_user` 190 → 172 (−18): 18 langkah PEMERHATIAN yang tersalah label sebagai
//      tindakan dibetulkan kepada `false` seperti diarahkan §7.2 langkah 3. Setiap satu
//      ialah arahan BACA ("Semak jumlah hasil", "Sahkan status penerima berubah",
//      "Semak media dan metadata kewangan") atau kerja DI LUAR sistem ("Kemas kini label
//      fizikal yang sebenar", "Selesaikan kerja sebenar") — pada kedua-duanya CTA
//      "Buat pada skrin" menunggu tindakan UI yang tidak pernah wujud, iaitu punca tepat
//      aduan pemilik. Senarai penuh 18 langkah: bukti/plan-f6-w2/LAPORAN-F6-W2.md §(b).
//      Shard `workflow.action_steps` 75 → 57 mengikut pembetulan yang sama (metrik KEMAJUAN).
const FROZEN = {
    guides: 83, steps: 473, generic_declared: 443, generic_pp: 238, generic_pc: 205,
    placeholder_titles: 258, wait_for_user: 172, action_steps_with_generic_target: 200,
    unique_step_ids: 470, mobile_defects: 6, catalog_version: '2026.07.22.2',
    waves: {
        W0: { guides: 2, steps: 10, action_generic: 0, placeholder: 10, mobile_defects: 6 },
        W1: { guides: 0, steps: 0, action_generic: 140, placeholder: 140, mobile_defects: 0 },
        W2: { guides: 0, steps: 0, action_generic: 60, placeholder: 0, mobile_defects: 0 },
        W3: { guides: 29, steps: 151, action_generic: 0, placeholder: 0, mobile_defects: 0 },
        W4: { guides: 14, steps: 158, action_generic: 0, placeholder: 0, mobile_defects: 0 },
        W5: { guides: 35, steps: 146, action_generic: 0, placeholder: 108, mobile_defects: 0 },
        W6: { guides: 3, steps: 8, action_generic: 0, placeholder: 0, mobile_defects: 0 },
    },
    shards: {
        //  (3) tenant-admin-public.action_steps 3 → 4. `action_steps` dibandingkan sebagai
        //      metrik KEMAJUAN (≤), tetapi ia sebenarnya metrik KANDUNGAN: metrik kecacatan
        //      ialah `action_steps_with_generic_target` (200 → 0). `public.login#2` menjadi
        //      langkah tindakan BERSASAR SPESIFIK (`login-submit`) supaya tour tamat apabila
        //      pautan benar-benar dihantar — bertambah tanpa menambah satu pun tindakan
        //      generik. Denominator dinaikkan; arah perbandingan TIDAK diubah (di luar skop F5).
        'screen': { guides: 29, steps: 151, action_steps: 111 },
        'workflow': { guides: 14, steps: 158, action_steps: 75 },
        'tenant-admin-public': { guides: 40, steps: 164, action_steps: 4 },
    },
    //  (6) F6-W3 — 5 Ogos 2026. Wave yang KERJANYA SUDAH DITUTUP. Setiap langkah generik
    //      dalam wave ini mesti membawa justifikasi EKSPLISIT daripada
    //      `resources/help/step-justifications.json`; sebab baseline automatik
    //      ("penambahbaikan dijadualkan Wn") tidak lagi diterima di sini. Tambah wave ke
    //      senarai ini HANYA apabila wave itu benar-benar ditutup — ia mengunci wave supaya
    //      langkah generik baharu tidak boleh diselitkan tanpa sebab bertulis dan bertarikh.
    //      W0/W1/W2 sudah 0 langkah generik, jadi liputan mereka kosong secara sah.
    justified_waves: ['W0', 'W1', 'W2', 'W3', 'W4', 'W5', 'W6'],
    // Kohort audit P11 (produksi 1 Ogos 2026) — perbandingan apple-to-apple SAHAJA, bukan gate.
    cohort_baseline: {
        resolved_to_generic: '119/124',
        title_equals_instruction: '77/124',
        title_truncated_mid_word: '20/124',
        cta: { seterusnya: 79, selesai: 25, buat_pada_skrin: 20 },
        mobile_center_covered: 6,
        source: 'PUSINGAN-11-CODEX + PUSINGAN-13-CODEX (bakwim.my, commit 4e07a70)',
    },
};

const fail = (msg) => { console.error(`FAIL: ${msg}`); process.exit(1); };
const familyOf = (id) => id.split('.')[0];

// ── Allowlist justifikasi per-langkah (F6-W3, §7.2 gate registri (f)) ───────────────────
// Sebelum ini SETIAP langkah generik menerima satu sebab yang dijana automatik
// ("penambahbaikan dijadualkan Wn"). Sebaik wave itu ditutup, ayat itu bercanggah dengan
// dirinya sendiri dan gate tidak dapat membezakan "dijustifikasikan" daripada "belum dibuat".
// Fail ini menjadikan justifikasi EKSPLISIT, per-langkah, dan bertarikh (§7.3 G5:
// "disenaraikan sebagai ID guide + indeks langkah, bukan pada aras guide").
// `--justifications` opsyenal supaya alat kekal boleh dijalankan atas checkout lama.
const JUSTIFY_STATUSES = new Set(['generic-justified', 'not-applicable']);
const justifications = new Map();
if (args.justifications) {
    const raw = JSON.parse(readFileSync(args.justifications, 'utf8'));
    for (const j of raw.justifications ?? []) {
        if (!j.key) fail('entri justifikasi tanpa `key`');
        if (justifications.has(j.key)) fail(`justifikasi berganda untuk ${j.key}`);
        if (!JUSTIFY_STATUSES.has(j.status)) fail(`justifikasi ${j.key}: status tidak sah ${j.status}`);
        if (!j.reason || j.reason.length < 40) fail(`justifikasi ${j.key}: sebab terlalu pendek/kosong`);
        if (!/^\d{4}-\d{2}-\d{2}$/.test(j.since ?? '')) fail(`justifikasi ${j.key}: \`since\` mesti tarikh YYYY-MM-DD`);
        justifications.set(j.key, j);
    }
}
const justificationsUsed = new Set();

// ── W0 daripada bukti produksi (deterministik, bukan pilihan rasa) ──────────────────────
const mobileRows = Array.isArray(mobile) ? mobile : (mobile.steps ?? []);
const defectRows = mobileRows.filter((r) => r.centerCovered === true);
if (defectRows.length !== FROZEN.mobile_defects) fail(`defect mobile ${defectRows.length} ≠ ${FROZEN.mobile_defects}`);
const w0Guides = new Set(defectRows.map((r) => r.guide));
const defectKeys = new Set(defectRows.map((r) => `${r.guide}#${r.index}`));

const waveOf = (guide, hasActionGeneric) => {
    if (w0Guides.has(guide.id)) return 'W0';
    const fam = familyOf(guide.id);
    if (fam === 'screen') return hasActionGeneric ? 'W1' : 'W3';
    if (fam === 'workflow') return hasActionGeneric ? 'W2' : 'W4';
    if (fam === 'public') return 'W6';
    return 'W5'; // tenant + admin (W0 sudah ditolak di atas)
};
const shardOf = (fam) => (fam === 'screen' ? 'screen' : fam === 'workflow' ? 'workflow' : 'tenant-admin-public');
const accessMethodOf = (fam) => (fam === 'screen' || fam === 'workflow' ? 'deeplink' : fam === 'public' ? 'auto-public' : 'route');

// ── Bina set catalogue + status liputan awal ────────────────────────────────────────────
// Status baseline (jadual §7.3):
//   specific         — 30 langkah bersasar spesifik sedia ada (disahkan DOM oleh G2).
//                      Selepas F5: **48**.
//   risk-accepted    — public.login#1–2: tour SENTIASA jatuh ke ralat palsu (RR-01-01);
//                      fallback artikel /bantuan DIUJI oleh G4.
//                      ✅ DITUTUP OLEH F5 (§6.1) pada 4 Ogos 2026 — jauh sebelum tarikh
//                      luput 2026-09-30. Kedua-dua langkah kini `specific`, jadi cabang
//                      di bawah TIDAK LAGI tercapai. Ia sengaja DIKEKALKAN: jika satu
//                      perubahan masa depan menjadikan semula sasaran `public.login`
//                      generik, manifest akan melabelnya risiko-diterima dengan sebab
//                      penuh dan bukan senyap-senyap sebagai "generic-justified".
//   generic-justified— baki: sasaran generik sedia ada pra-F6, dijadualkan wave masing-masing
const catalogueGuides = [];
const stats = { steps: 0, generic: 0, pp: 0, pc: 0, placeholder: 0, wfu: 0, actGen: 0 };
const stepKeySet = new Set();
const stepIdSet = new Set();
const waveAgg = {}; const shardAgg = {};
for (const w of Object.keys(FROZEN.waves)) waveAgg[w] = { guides: 0, steps: 0, action_generic: 0, placeholder: 0, mobile_defects: 0 };
for (const s of Object.keys(FROZEN.shards)) shardAgg[s] = { guides: 0, steps: 0, action_steps: 0 };

for (const guide of catalog.guides) {
    const fam = familyOf(guide.id);
    const hasActionGeneric = guide.steps.some((st) => st.wait_for_user && GEN.has(st.target));
    const wave = waveOf(guide, hasActionGeneric);
    const shard = shardOf(fam);
    waveAgg[wave].guides++; shardAgg[shard].guides++;

    const steps = guide.steps.map((st, i) => {
        const index = i + 1;
        const key = `${guide.id}#${index}`;
        if (stepKeySet.has(key)) fail(`kunci langkah berganda: ${key}`);
        stepKeySet.add(key); stepIdSet.add(st.id);
        const generic = GEN.has(st.target);
        const placeholder = /^Langkah\s+\d+$/i.test(st.title);
        const wfu = st.wait_for_user === true;
        stats.steps++; if (generic) { stats.generic++; st.target === 'page-primary' ? stats.pp++ : stats.pc++; }
        if (placeholder) stats.placeholder++;
        if (wfu) { stats.wfu++; if (generic) stats.actGen++; }
        waveAgg[wave].steps++; shardAgg[shard].steps++;
        if (wfu) shardAgg[shard].action_steps++;
        if (wfu && generic) waveAgg[wave].action_generic++;
        if (placeholder) waveAgg[wave].placeholder++;
        if (defectKeys.has(key)) waveAgg[wave].mobile_defects++;

        let status;
        if (!generic) {
            status = { status: 'specific' };
        } else if (guide.id === 'public.login') {
            status = {
                status: 'risk-accepted',
                reason: 'RR-01-01/RR-08-02 — layout tetamu tiada <main>, sasaran page-content tidak wujud; tour sentiasa jatuh ke popover ralat palsu',
                impact: 'Pengguna kali pertama di /log-masuk melihat "Tindakan belum tersedia" dan tiada tour; halaman itu sendiri berfungsi normal',
                fallback: 'Popover fallback menawarkan artikel /bantuan public.login — kewujudan laluan fallback diuji G4 (guidance-full)',
                ticket: 'PELAN-PEMBAIKAN.md F5 §6.1 (RR-01-01)',
                owner: 'pemilik — arahan mula pelaksanaan F0–F10 (2 Ogos 2026) meluluskan jadual pembaikan F5',
                since: '2026-08-02',
                expires: '2026-09-30',
            };
        } else if (justifications.has(key)) {
            // F6-W3: justifikasi EKSPLISIT mengatasi sebab baseline automatik.
            const j = justifications.get(key);
            justificationsUsed.add(key);
            status = {
                status: j.status,
                reason: j.reason,
                since: j.since,
                ...(j.followup ? { followup: j.followup } : {}),
            };
        } else {
            status = {
                status: 'generic-justified',
                reason: `Baseline pra-F6: sasaran generik sedia ada; penambahbaikan dijadualkan ${wave} (F6)${wfu ? ' — langkah tindakan, dikira dalam metrik 200 action_steps_with_generic_target' : ''}`,
                since: '2026-08-02',
            };
        }

        return {
            key, index, step_id: st.id, title: st.title, title_placeholder: placeholder,
            instruction_preview: String(st.instruction ?? '').slice(0, 80),
            target: st.target, generic_declared: generic, wait_for_user: wfu,
            route: st.route ?? guide.route, wave, shard,
            viewport: guide.id === 'public.registration' ? 'both' : 'desktop',
            mobile_defect: defectKeys.has(key),
            ...status,
        };
    });

    catalogueGuides.push({
        guide_id: guide.id, family: fam, panel: guide.panel, route: guide.route,
        roles: guide.roles, version: guide.version, wave, shard,
        access_method: accessMethodOf(fam), steps,
    });
}

// ── Verifikasi kendiri ──────────────────────────────────────────────────────────────────
// (a) STRUKTUR — mesti sama; perubahan di sini bermakna skop kerja berubah.
if (catalogueGuides.length !== FROZEN.guides) fail(`guides ${catalogueGuides.length} ≠ ${FROZEN.guides}`);
if (stats.steps !== FROZEN.steps) fail(`steps ${stats.steps} ≠ ${FROZEN.steps}`);
if (stats.wfu !== FROZEN.wait_for_user) fail(`wait_for_user ${stats.wfu} ≠ ${FROZEN.wait_for_user}`);
if (stepIdSet.size !== FROZEN.unique_step_ids) fail(`unique step.id ${stepIdSet.size} ≠ ${FROZEN.unique_step_ids}`);

// (b) KEMAJUAN — mesti ≤ baseline (turun = pembaikan; naik = regresi).
const kemajuan = [];
const takLebihTeruk = (nama, semasa, asas) => {
    if (semasa > asas) fail(`REGRESI ${nama}: ${semasa} > baseline ${asas}`);
    if (semasa < asas) kemajuan.push(`${nama} ${asas} → ${semasa} (−${asas - semasa})`);
};
takLebihTeruk('generic_declared', stats.generic, FROZEN.generic_declared);
takLebihTeruk('generic_pp', stats.pp, FROZEN.generic_pp);
takLebihTeruk('generic_pc', stats.pc, FROZEN.generic_pc);
takLebihTeruk('placeholder_titles', stats.placeholder, FROZEN.placeholder_titles);
takLebihTeruk('action_steps_with_generic_target', stats.actGen, FROZEN.action_steps_with_generic_target);

const STRUKTUR_WAVE = ['guides', 'steps'];
for (const [w, exp] of Object.entries(FROZEN.waves)) {
    for (const k of Object.keys(exp)) {
        if (STRUKTUR_WAVE.includes(k)) {
            if (waveAgg[w][k] !== exp[k]) fail(`wave ${w}.${k} ${waveAgg[w][k]} ≠ ${exp[k]}`);
        } else {
            takLebihTeruk(`wave ${w}.${k}`, waveAgg[w][k], exp[k]);
        }
    }
}
for (const [s, exp] of Object.entries(FROZEN.shards)) {
    for (const k of Object.keys(exp)) {
        if (k === 'action_steps') takLebihTeruk(`shard ${s}.${k}`, shardAgg[s][k], exp[k]);
        else if (shardAgg[s][k] !== exp[k]) fail(`shard ${s}.${k} ${shardAgg[s][k]} ≠ ${exp[k]}`);
    }
}
if (kemajuan.length) {
    console.error('KEMAJUAN berbanding baseline F0:');
    for (const baris of kemajuan) console.error(`  ${baris}`);
} else {
    console.error(`Tiada delta — katalog masih pada baseline (catalog_version ${catalog.catalog_version}).`);
}

// (c) ALLOWLIST JUSTIFIKASI (F6-W3) — dua arah, supaya ia tidak boleh lapuk secara senyap.
//
//   (i)  Tiada entri YATIM/BASI: setiap kunci dalam allowlist mesti benar-benar dipakai.
//        Entri yang kuncinya tidak wujud dalam katalog, atau yang langkahnya sudah dinaikkan
//        kepada `specific`, akan gagal di sini — jadi justifikasi lapuk tidak boleh menumpuk.
//   (ii) LIPUTAN PENUH bagi wave yang sudah ditutup: setiap langkah generik dalam wave itu
//        mesti mempunyai justifikasi eksplisit. Tanpa semakan ini, sebuah wave boleh
//        diisytihar "siap" sedangkan setiap langkahnya masih berkata "dijadualkan wave ini".
const justifikasiYatim = [...justifications.keys()].filter((k) => !justificationsUsed.has(k));
if (justifikasiYatim.length) {
    fail(`justifikasi YATIM/BASI (kunci tiada dalam katalog atau langkah sudah specific): ${justifikasiYatim.join(', ')}`);
}
const generikTanpaJustifikasi = [];
for (const g of catalogueGuides) {
    for (const s of g.steps) {
        if (!s.generic_declared) continue;
        if (!FROZEN.justified_waves.includes(s.wave)) continue;
        if (!justifications.has(s.key)) generikTanpaJustifikasi.push(s.key);
    }
}
if (generikTanpaJustifikasi.length) {
    fail(`wave TERTUTUP (${FROZEN.justified_waves.join(', ')}) masih ada langkah generik tanpa justifikasi eksplisit: ${generikTanpaJustifikasi.join(', ')}`);
}
console.error(`Justifikasi eksplisit: ${justificationsUsed.size} langkah; wave tertutup ${FROZEN.justified_waves.join(', ')} liputan PENUH.`);

// ── Set cohort (family tenant = kohort audit P11) ───────────────────────────────────────
const cohortGuides = catalogueGuides.filter((g) => g.family === 'tenant').map((g) => g.guide_id);
if (cohortGuides.length !== 25) fail(`kohort ${cohortGuides.length} ≠ 25`);
const cohortSteps = catalogueGuides.filter((g) => g.family === 'tenant').reduce((n, g) => n + g.steps.length, 0);
if (cohortSteps !== 124) fail(`kohort langkah ${cohortSteps} ≠ 124`);

// ── role_routes: semakan bentuk minimum ─────────────────────────────────────────────────
if ((roleRoutes.identities ?? []).length !== 10) fail(`role_routes identities ${roleRoutes.identities?.length} ≠ 10`);
if ((roleRoutes.mismatches ?? []).length !== 0) fail(`role_routes mismatches ${roleRoutes.mismatches.length} ≠ 0 — baiki dahulu`);

const manifest = {
    schema_version: 1,
    generated_by: 'Audit Review Round Robin/bukti/plan-baseline/tools/build-manifest.mjs',
    catalog_version: catalog.catalog_version,
    sources: {
        catalogue: 'resources/help/guides.json',
        mobile_defects: 'Audit Review Round Robin/bukti/pusingan-11-codex/production-mobile-all-tour-steps.json',
        role_routes: 'php artisan diwan:role-routes --json=…',
    },
    invariants: { ...FROZEN, waves: waveAgg, shards: shardAgg },
    cohort: {
        definition: 'Kohort audit produksi P11 = family `tenant` (25 guide / 124 langkah) — perbandingan apple-to-apple sahaja, BUKAN gate keluaran',
        guides: cohortGuides,
        steps: cohortSteps,
        baseline: FROZEN.cohort_baseline,
    },
    catalogue: catalogueGuides,
    role_routes: roleRoutes,
};

writeFileSync(args.out, JSON.stringify(manifest, null, 2) + '\n');
console.log(`OK: manifest ditulis ke ${args.out}`);
console.log(`  guides=${catalogueGuides.length} steps=${stats.steps} actionGeneric=${stats.actGen} placeholder=${stats.placeholder}`);
console.log(`  waves=${Object.entries(waveAgg).map(([w, v]) => `${w}:${v.guides}g/${v.steps}s`).join(' ')}`);
console.log(`  role_routes entries=${roleRoutes.entries.length} counts=${JSON.stringify(roleRoutes.expected_page_counts)}`);
