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

// ── kandungan tajuk: dikira daripada KATALOG semasa, bukan daripada preview manifest ──────
const guidesById = new Map((katalog.guides ?? katalog).map((g) => [g.id, g]));
const bersih = (t) => String(t ?? '').trim().replace(/[.!?]+$/, '').toLowerCase();

function kandunganKohort() {
    let sama = 0;
    let terpotong = 0;
    for (const s of kohort) {
        const g = guidesById.get(s.guide.guide_id);
        const st = g?.steps?.[s.index - 1];
        if (!st) continue;
        if (bersih(st.title) === bersih(st.instruction)) sama += 1;
        if (/[…]$|\.\.\.$/.test(String(st.title ?? '').trim())) terpotong += 1;
    }
    return { sama, terpotong };
}

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

const status = kumpul(langkah, 's' in {} ? 'status' : 'status');
const statusKira = Object.fromEntries(Object.entries(status).map(([k, v]) => [k, v.length]));
const kandungan = kandunganKohort();

const hasil = {
    dijana: 'metrik-f8.mjs',
    catalog_version: katalog.catalog_version,
    definisi_kohort: definisiKohort,

    paras_i_kohort: {
        denominator: `${new Set(kohort.map((s) => s.guide.guide_id)).size}/${kohort.length}`,
        resolved_to_generic: { asas: kohortAsas.resolved_to_generic, kini: `${kira(kohort, (s) => s.generic_declared)}/${kohort.length}` },
        title_equals_instruction: { asas: kohortAsas.title_equals_instruction, kini: `${kandungan.sama}/${kohort.length}` },
        title_truncated_mid_word: { asas: kohortAsas.title_truncated_mid_word, kini: `${kandungan.terpotong}/${kohort.length}` },
        cta_buat_pada_skrin: { asas: kohortAsas.cta.buat_pada_skrin, kini: kira(kohort, (s) => s.wait_for_user) },
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
        mobile_defects: { asas: asas.mobile_defects, kini: kira(langkah, (s) => s.mobile_defect) },
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
    if (k === 'denominator') continue;
    console.log(`  ${k.padEnd(28)} ${String(v.asas).padStart(9)} -> ${v.kini}`);
}
console.log('\n── PARAS (ii) KATALOG PENUH 83/473 ──');
for (const [k, v] of Object.entries(p2)) {
    if (k === 'empat_kategori' || k === 'tanpa_status') continue;
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
