// F8 §9 — jadual metrik penutup, DIKIRA daripada sumber yang sama seperti audit asal.
//
// §9.3 menuntut pelaporan TIGA PARAS: (i) kohort 25/124, (ii) katalog penuh 83/473,
// (iii) pecahan family × wave × shard. Ia juga melarang melaporkan "0 dalam skop Wn" —
// setiap angka mesti pada denominator PENUH.
//
// Empat kategori dipisahkan, tiada lajur "baki": passed · not-applicable · risk-accepted ·
// blocked. Menggabungkan dua yang terakhir menyembunyikan perbezaan antara "pemilik terima
// secara sedar dengan fallback dan tarikh luput" dan "rosak, tiada sesiapa memutuskan apa-apa".
//
// Guna: node "Audit Review Round Robin/bukti/plan-f8/skrip/metrik-f8.mjs"

import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';

const AKAR = 'Audit Review Round Robin/bukti';
const manifest = JSON.parse(readFileSync(`${AKAR}/plan-baseline/manifest.json`, 'utf8'));
const katalog = JSON.parse(readFileSync('resources/help/guides.json', 'utf8'));
const registri = JSON.parse(readFileSync('resources/help/targets.json', 'utf8')).targets;

const asas = manifest.invariants;
const kohortAsas = asas.cohort_baseline;
const langkah = manifest.catalogue.flatMap((g) => g.steps.map((s) => ({ ...s, guide: g })));

/** Kohort audit = family `tenant` (definisi dibekukan F0). */
const definisiKohort = manifest.cohort.definition ?? 'family=tenant';
const kohort = langkah.filter((s) => s.guide.family === 'tenant');

const kira = (arr, pred) => arr.filter(pred).length;
const kumpul = (arr, kunci) => arr.reduce((a, s) => {
    const k = typeof kunci === 'function' ? kunci(s) : s[kunci];
    (a[k] ??= []).push(s);
    return a;
}, {});

// ── metrik KANDUNGAN kohort: TIDAK dikira di sini ─────────────────────────────────────────
//
// 🔴 Versi pertama skrip ini mengira `title == instruction`, "tajuk terpotong" dan CTA daripada
// KATALOG. Ketiga-tiganya SALAH sebagai perbandingan apple-to-apple, dan tentukuran
// membuktikannya: dijalankan pada katalog commit audit `4e07a70` ia memberi 0 pada KEDUA-DUA
// belah, bukan 77/20/20.
//
//   • Asas audit ialah ukuran RUNTIME. Pada `4e07a70`, 118/124 tajuk kohort ialah placeholder
//     `"Langkah N"`, jadi tour MENERBITKAN tajuk daripada arahan (`HelpCatalog.php` sekitar
//     :196-220). Katalog tidak boleh menunjukkannya.
//   • CTA lebih tegas lagi: label "Buat pada skrin" diputuskan oleh `step-advance-plan.js`
//     daripada keadaan sasaran/route/DOM — BUKAN oleh medan `wait_for_user`. Dan pada
//     `4e07a70` medan itu tidak wujud sama sekali. Mengira `wait_for_user` bukan mengira CTA.
//
// Maka ketiga-tiganya diukur oleh `ukur-runtime-kohort-f8.mjs` pada popover SEBENAR, dan skrip
// ini hanya MERUJUK hasil itu. Ia tidak mengira semula, supaya tiada dua nombor bercanggah.
let runtime = null;
try {
    runtime = JSON.parse(readFileSync(`${AKAR}/plan-f8/runtime-kohort-f8.json`, 'utf8'));
} catch { /* belum diukur */ }

// ── gate agregator (jika larian tempatan wujud) ───────────────────────────────────────────
let gate = null;
const laluanGate = 'storage/app/plan-f6/coverage-gate.json';
if (existsSync(laluanGate)) {
    const g = JSON.parse(readFileSync(laluanGate, 'utf8'));
    // Skema sebenar: `summary.{guide_ids,step_ids,action_step_ids}.{union,expected,missing,extra,overlap}`.
    // Versi pertama skrip ini meneka `g.guide_ids` dan diam-diam memberi `null` — pembacaan yang
    // kelihatan sah tetapi tidak mengukur apa-apa. Semak skema sebelum menulis alat terhadapnya.
    const s = g.summary ?? {};
    gate = {
        pass: g.pass,
        shards: g.shards_found?.length ?? null,
        masalah: g.problems?.length ?? null,
        guide_ids: s.guide_ids, step_ids: s.step_ids, action_step_ids: s.action_step_ids,
    };
}

const statusKira = Object.fromEntries(
    Object.entries(kumpul(langkah, 'status')).map(([k, v]) => [k, v.length]),
);

const hasil = {
    dijana: 'metrik-f8.mjs',
    catalog_version: katalog.catalog_version,
    definisi_kohort: definisiKohort,

    paras_i_kohort: {
        denominator: `${new Set(kohort.map((s) => s.guide.guide_id)).size}/${kohort.length}`,
        // Dikira di sini kerana `generic_declared` ialah medan katalog pada KEDUA-DUA belah.
        resolved_to_generic: { asas: kohortAsas.resolved_to_generic, kini: `${kira(kohort, (s) => s.generic_declared)}/${kohort.length}` },
        // Tiga metrik KANDUNGAN datang daripada ukuran RUNTIME, bukan dikira semula di sini.
        sumber_metrik_kandungan: 'runtime-kohort-f8.json (popover sebenar, desktop 1440x1000)',
        title_equals_description: runtime
            ? { asas: kohortAsas.title_equals_instruction, kini: `${runtime.kini.title_equals_description}/${runtime.kini.popover}`, kaedah: 'runtime' }
            : { asas: kohortAsas.title_equals_instruction, kini: 'BELUM DIUKUR — jalankan ukur-runtime-kohort-f8.mjs' },
        title_truncated_mid_word: runtime
            ? { asas: kohortAsas.title_truncated_mid_word, kini: `${runtime.kini.truncated}/${runtime.kini.popover}`, kaedah: 'runtime' }
            : { asas: kohortAsas.title_truncated_mid_word, kini: 'BELUM DIUKUR' },
        cta_buat_pada_skrin: runtime
            ? { asas: kohortAsas.cta.buat_pada_skrin, kini: runtime.kini.cta_buat_pada_skrin, kaedah: 'runtime (teks butang popover)' }
            : { asas: kohortAsas.cta.buat_pada_skrin, kini: 'BELUM DIUKUR' },
        placeholder_popover: runtime
            ? { kini: `${runtime.kini.placeholder}/${runtime.kini.popover}`, kaedah: 'runtime' }
            : { kini: 'BELUM DIUKUR' },
    },

    paras_ii_katalog_penuh: {
        guides: { asas: asas.guides, kini: manifest.catalogue.length },
        steps: { asas: asas.steps, kini: langkah.length },
        generic_declared: { asas: asas.generic_declared, kini: kira(langkah, (s) => s.generic_declared) },
        placeholder_titles: { asas: asas.placeholder_titles, kini: kira(langkah, (s) => s.title_placeholder) },
        action_steps_with_generic_target: {
            asas: asas.action_steps_with_generic_target,
            kini: kira(langkah, (s) => s.wait_for_user && s.generic_declared),
        },
        // ⚠️ `mobile_defect` dalam manifest ialah INPUT BEKU audit — membacanya semula sentiasa
        // memberi 6. Ia BUKAN ukuran keadaan semasa, jadi ia dinamakan sedemikian di sini.
        // Ukuran semasa hidup dalam `mobile-kohort-f8.json` (45/124) dan dirujuk, tidak dikira
        // semula — versi pertama menerbitkan `kini: 6` yang bercanggah dengan laporan.
        mobile_defects_asas_beku: { asas: asas.mobile_defects, dalam_manifest: kira(langkah, (s) => s.mobile_defect) },
        mobile_centercovered_diukur: (() => {
            try {
                const d = JSON.parse(readFileSync(`${AKAR}/plan-f8/mobile-kohort-f8.json`, 'utf8'));
                const h = d.hasil ?? [];
                return { sumber: 'mobile-kohort-f8.json', kini: h.filter((x) => x.centerCovered).length, daripada: h.length };
            } catch {
                return { kini: 'BELUM DIUKUR' };
            }
        })(),
        wait_for_user: { asas: asas.wait_for_user, kini: kira(langkah, (s) => s.wait_for_user) },
        empat_kategori: {
            specific: statusKira.specific ?? 0,
            'not-applicable': statusKira['not-applicable'] ?? 0,
            'generic-justified': statusKira['generic-justified'] ?? 0,
            'risk-accepted': statusKira['risk-accepted'] ?? 0,
            blocked: statusKira.blocked ?? 0,
        },
        tanpa_status: kira(langkah, (s) => !s.status),
    },

    paras_iii_pecahan: {
        family: Object.fromEntries(Object.entries(kumpul(langkah, (s) => s.guide.family)).map(([k, v]) => [k, {
            guides: new Set(v.map((s) => s.guide.guide_id)).size,
            steps: v.length,
            generic: kira(v, (s) => s.generic_declared),
            action_generic: kira(v, (s) => s.wait_for_user && s.generic_declared),
            placeholder: kira(v, (s) => s.title_placeholder),
        }])),
        wave: Object.fromEntries(Object.entries(kumpul(langkah, 'wave')).map(([k, v]) => [k, {
            guides: new Set(v.map((s) => s.guide.guide_id)).size, steps: v.length,
            generic: kira(v, (s) => s.generic_declared),
        }])),
        shard: Object.fromEntries(Object.entries(kumpul(langkah, 'shard')).map(([k, v]) => [k, {
            guides: new Set(v.map((s) => s.guide.guide_id)).size, steps: v.length,
            action_steps: kira(v, (s) => s.wait_for_user),
        }])),
        viewport: Object.fromEntries(Object.entries(kumpul(langkah, 'viewport')).map(([k, v]) => [k, v.length])),

        // §9.3 menuntut pecahan SILANG `family × role × viewport`, bukan tiga agregat
        // berasingan. Versi pertama memberi agregat sahaja; itu bukan pecahan silang.
        // `roles` ialah medan guide (satu guide boleh melayan banyak role), jadi langkah
        // dikira SEKALI bagi setiap role yang boleh melihatnya — jumlah lajur melebihi 473
        // dengan sengaja, dan itu dinyatakan supaya ia tidak dibaca sebagai denominator.
        silang_family_role_viewport: (() => {
            const t = {};
            for (const s of langkah) {
                const roles = s.guide.roles?.length ? s.guide.roles : ['(tiada role)'];
                for (const r of roles) {
                    const kunci = `${s.guide.family}|${r}`;
                    t[kunci] ??= { desktop: 0, mobile: 0, both: 0, generik: 0, langkah: 0 };
                    t[kunci][s.viewport] = (t[kunci][s.viewport] ?? 0) + 1;
                    t[kunci].langkah += 1;
                    if (s.generic_declared) t[kunci].generik += 1;
                }
            }
            return t;
        })(),
        nota_silang: 'langkah dikira sekali per role yang boleh melihatnya; jumlah > 473 dengan sengaja',
    },

    registri: {
        jumlah: registri.length,
        aktif: kira(registri, (t) => t.status === 'active'),
        rizab: kira(registri, (t) => t.status === 'reserved'),
    },

    role_routes: {
        identiti: manifest.role_routes.identities?.length ?? null,
        entri: manifest.role_routes.entries?.length ?? null,
        mismatches: manifest.role_routes.mismatches?.length ?? 0,
        probe_silang_tenant: manifest.role_routes.cross_tenant_probes?.length ?? null,
    },

    gate_agregator: gate,
};

mkdirSync(`${AKAR}/plan-f8`, { recursive: true });
writeFileSync(`${AKAR}/plan-f8/metrik-f8.json`, JSON.stringify(hasil, null, 2) + '\n');

// ── cetakan manusia ───────────────────────────────────────────────────────────────────────
const p1 = hasil.paras_i_kohort;
const p2 = hasil.paras_ii_katalog_penuh;
console.log(`catalog_version ${hasil.catalog_version}   kohort: ${definisiKohort} (${p1.denominator})\n`);
console.log('── PARAS (i) KOHORT 25/124 — apple-to-apple dengan audit asal ──');
for (const [k, v] of Object.entries(p1)) {
    if (k === 'denominator' || typeof v === 'string') continue;
    const asas = v.asas === undefined ? '—' : String(v.asas);
    const kaedah = v.kaedah ? `   [${v.kaedah}]` : '';
    console.log(`  ${k.padEnd(28)} ${asas.padStart(9)} -> ${v.kini}${kaedah}`);
}
console.log(`  (metrik kandungan: ${p1.sumber_metrik_kandungan})`);
console.log('\n── PARAS (ii) KATALOG PENUH 83/473 ──');
for (const [k, v] of Object.entries(p2)) {
    if (k === 'empat_kategori' || k === 'tanpa_status') continue;
    if (k === 'mobile_defects_asas_beku') {
        console.log(`  ${k.padEnd(34)} ${String(v.asas).padStart(4)} (input beku; dalam manifest ${v.dalam_manifest})`);
        continue;
    }
    if (k === 'mobile_centercovered_diukur') {
        console.log(`  ${k.padEnd(34)} DIUKUR ${v.kini}/${v.daripada ?? '?'}  <- ${v.sumber ?? '-'}`);
        continue;
    }
    const tanda = v.kini === v.asas ? ' ' : (v.kini < v.asas ? '↓' : '↑');
    console.log(`  ${k.padEnd(34)} ${String(v.asas).padStart(4)} -> ${String(v.kini).padStart(4)} ${tanda}`);
}
console.log('  empat kategori (tiada lajur "baki"):');
for (const [k, v] of Object.entries(p2.empat_kategori)) console.log(`     ${k.padEnd(20)} ${v}`);
console.log(`  langkah TANPA status              ${p2.tanpa_status}`);

console.log('\n── PARAS (iii) PECAHAN ──');
for (const [nama, tbl] of Object.entries(hasil.paras_iii_pecahan)) {
    console.log(`  ${nama}:`);
    for (const [k, v] of Object.entries(tbl)) {
        console.log(`     ${String(k).padEnd(22)} ${typeof v === 'object' ? JSON.stringify(v) : v}`);
    }
}
console.log(`\nregistri  ${hasil.registri.aktif} aktif + ${hasil.registri.rizab} rizab (${hasil.registri.jumlah})`);
console.log(`role_routes  identiti=${hasil.role_routes.identiti} entri=${hasil.role_routes.entri} mismatch=${hasil.role_routes.mismatches}`);
console.log(`gate agregator  ${gate ? JSON.stringify(gate) : '(tiada larian tempatan)'}`);
console.log(`\nditulis: ${AKAR}/plan-f8/metrik-f8.json`);
