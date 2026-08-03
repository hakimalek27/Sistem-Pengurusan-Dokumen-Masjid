// D11 #8 — Validator manifest baseline (PELAN-PEMBAIKAN.md §1 F0(ii-a) #3 + (ii-b)).
// SENGAJA mengira semula partition wave/shard daripada resources/help/guides.json dengan
// pelaksanaan BERASINGAN daripada build-manifest.mjs — dua pelaksanaan bebas saling menjaga.
// Assertion set-union EXACT (hilang/lebihan/bertindih dinamakan, bukan kiraan sahaja);
// keluar bukan-sifar pada kegagalan pertama kategori.
//
// Guna: node scripts/audit/validate-plan-manifest.mjs \
//         --manifest "Audit Review Round Robin/bukti/plan-baseline/manifest.json" \
//         [--catalog resources/help/guides.json] [--mobile <bukti mobile json>]

import { readFileSync } from 'node:fs';

const args = { catalog: 'resources/help/guides.json', mobile: 'Audit Review Round Robin/bukti/pusingan-11-codex/production-mobile-all-tour-steps.json' };
for (let i = 2; i < process.argv.length; i += 2) args[process.argv[i].replace(/^--/, '')] = process.argv[i + 1];
if (!args.manifest) { console.error('FAIL: --manifest wajib'); process.exit(1); }

const GEN = new Set(['page-primary', 'page-content']);
const WAVES = ['W0', 'W1', 'W2', 'W3', 'W4', 'W5', 'W6'];
const SHARDS = ['screen', 'workflow', 'tenant-admin-public'];
const EXPECT = {
    waveGuides: { W0: 2, W1: 28, W2: 13, W3: 1, W4: 1, W5: 35, W6: 3 },
    waveSteps: { W0: 10, W1: 140, W2: 145, W3: 11, W4: 13, W5: 146, W6: 8 },
    // STRUKTUR (mesti sama) vs BASELINE KEMAJUAN (mesti ≤ — pelan §7 menjangka 200→0,
    // 258→0, mobile 6→0; mengassert kesamaan akan menolak setiap pembaikan F6).
    totals: { guides: 83, steps: 473 },
    baselineProgress: { actionGeneric: 200, placeholder: 258, mobileDefects: 6 },
};

let failures = 0;
const fail = (msg, ids = []) => {
    failures++;
    console.error(`FAIL: ${msg}`);
    if (ids.length) {
        console.error(`  (${ids.length} ID) ${ids.slice(0, 50).join(', ')}${ids.length > 50 ? ` … +${ids.length - 50} lagi` : ''}`);
    }
};
const setDiff = (a, b) => [...a].filter((x) => !b.has(x));

const manifest = JSON.parse(readFileSync(args.manifest, 'utf8'));
const catalog = JSON.parse(readFileSync(args.catalog, 'utf8'));
const mobileRows = (() => {
    const d = JSON.parse(readFileSync(args.mobile, 'utf8'));
    return (Array.isArray(d) ? d : d.steps ?? []).filter((r) => r.centerCovered === true);
})();

// ── 1. Kira semula partition secara bebas daripada katalog ─────────────────────────────
const w0Guides = new Set(mobileRows.map((r) => r.guide));
const defectKeys = new Set(mobileRows.map((r) => `${r.guide}#${r.index}`));
const expected = new Map(); // guide_id -> {wave, shard, stepKeys[]}
for (const g of catalog.guides) {
    const fam = g.id.split('.')[0];
    const hasActionGeneric = g.steps.some((s) => s.wait_for_user && GEN.has(s.target));
    let wave;
    if (w0Guides.has(g.id)) wave = 'W0';
    else if (fam === 'screen') wave = hasActionGeneric ? 'W1' : 'W3';
    else if (fam === 'workflow') wave = hasActionGeneric ? 'W2' : 'W4';
    else if (fam === 'public') wave = 'W6';
    else wave = 'W5';
    const shard = fam === 'screen' ? 'screen' : fam === 'workflow' ? 'workflow' : 'tenant-admin-public';
    expected.set(g.id, { wave, shard, keys: g.steps.map((_, i) => `${g.id}#${i + 1}`) });
}

// ── 2. Union wave (guide + langkah) = semesta katalog, tanpa duplikat/yatim/lebihan ────
const catalogGuideIds = new Set(expected.keys());
const catalogStepKeys = new Set([...expected.values()].flatMap((v) => v.keys));
const manifestGuides = manifest.catalogue ?? [];
const byWaveGuides = Object.fromEntries(WAVES.map((w) => [w, new Set()]));
const byWaveSteps = Object.fromEntries(WAVES.map((w) => [w, new Set()]));
const byShardGuides = Object.fromEntries(SHARDS.map((s) => [s, new Set()]));
const seenGuideIds = new Set();
const seenStepKeys = new Set();
let actionGeneric = 0; let placeholder = 0; let mobileDefects = 0;

for (const g of manifestGuides) {
    if (seenGuideIds.has(g.guide_id)) fail(`guide berganda dalam manifest: ${g.guide_id}`);
    seenGuideIds.add(g.guide_id);
    if (!WAVES.includes(g.wave)) fail(`guide ${g.guide_id} wave tidak sah: ${g.wave}`);
    if (!SHARDS.includes(g.shard)) fail(`guide ${g.guide_id} shard tidak sah: ${g.shard}`);
    byWaveGuides[g.wave]?.add(g.guide_id);
    byShardGuides[g.shard]?.add(g.guide_id);
    const exp = expected.get(g.guide_id);
    if (!exp) fail(`guide manifest tiada dalam katalog: ${g.guide_id}`);
    else {
        if (exp.wave !== g.wave) fail(`guide ${g.guide_id}: wave manifest ${g.wave} ≠ pengiraan bebas ${exp.wave}`);
        if (exp.shard !== g.shard) fail(`guide ${g.guide_id}: shard manifest ${g.shard} ≠ pengiraan bebas ${exp.shard}`);
    }
    for (const s of g.steps ?? []) {
        if (seenStepKeys.has(s.key)) fail(`kunci langkah berganda: ${s.key}`);
        seenStepKeys.add(s.key);
        if (s.wave == null || s.shard == null) fail(`langkah ${s.key} tiada wave/shard`);
        byWaveSteps[s.wave]?.add(s.key);
        if (s.wait_for_user && s.generic_declared) actionGeneric++;
        if (s.title_placeholder) placeholder++;
        if (s.mobile_defect) mobileDefects++;
        if (!['specific', 'generic-justified', 'not-applicable', 'risk-accepted', 'blocked'].includes(s.status)) {
            fail(`langkah ${s.key} status tidak sah: ${s.status}`);
        }
        if (s.status === 'risk-accepted') {
            for (const field of ['reason', 'impact', 'fallback', 'ticket', 'owner', 'expires']) {
                if (!s[field]) fail(`langkah ${s.key} risk-accepted tiada medan ${field}`);
            }
        }
    }
}

const missingGuides = setDiff(catalogGuideIds, seenGuideIds);
const extraGuides = setDiff(seenGuideIds, catalogGuideIds);
if (missingGuides.length) fail('guide katalog HILANG dari manifest', missingGuides);
if (extraGuides.length) fail('guide LEBIHAN dalam manifest', extraGuides);
const missingSteps = setDiff(catalogStepKeys, seenStepKeys);
const extraSteps = setDiff(seenStepKeys, catalogStepKeys);
if (missingSteps.length) fail('kunci langkah HILANG dari manifest', missingSteps);
if (extraSteps.length) fail('kunci langkah LEBIHAN dalam manifest', extraSteps);

// Persilangan pasangan wave mesti kosong (dijamin oleh keunikan guide+wave tunggal, tetapi
// diassert eksplisit — dua partition bebas ke atas semesta sama).
for (let i = 0; i < WAVES.length; i++) {
    for (let j = i + 1; j < WAVES.length; j++) {
        const inter = [...byWaveGuides[WAVES[i]]].filter((x) => byWaveGuides[WAVES[j]].has(x));
        if (inter.length) fail(`persilangan ${WAVES[i]}∩${WAVES[j]} tidak kosong`, inter);
    }
}
const shardUnion = new Set(SHARDS.flatMap((s) => [...byShardGuides[s]]));
if (setDiff(catalogGuideIds, shardUnion).length || setDiff(shardUnion, catalogGuideIds).length) {
    fail('union shard ≠ semesta katalog');
}

// ── 3. Kiraan exact jadual beku ────────────────────────────────────────────────────────
for (const w of WAVES) {
    if (byWaveGuides[w].size !== EXPECT.waveGuides[w]) fail(`wave ${w} guide ${byWaveGuides[w].size} ≠ ${EXPECT.waveGuides[w]}`);
    if (byWaveSteps[w].size !== EXPECT.waveSteps[w]) fail(`wave ${w} langkah ${byWaveSteps[w].size} ≠ ${EXPECT.waveSteps[w]}`);
}
if (seenGuideIds.size !== EXPECT.totals.guides) fail(`jumlah guide ${seenGuideIds.size} ≠ ${EXPECT.totals.guides}`);
if (seenStepKeys.size !== EXPECT.totals.steps) fail(`jumlah langkah ${seenStepKeys.size} ≠ ${EXPECT.totals.steps}`);
// Metrik kemajuan: turun = pembaikan (dilaporkan), naik = regresi (gagal).
const kemajuan = [];
const takLebihTeruk = (nama, semasa, asas) => {
    if (semasa > asas) fail(`REGRESI ${nama}: ${semasa} > baseline ${asas}`);
    if (semasa < asas) kemajuan.push(`${nama} ${asas} → ${semasa} (−${asas - semasa})`);
};
takLebihTeruk('action generic', actionGeneric, EXPECT.baselineProgress.actionGeneric);
takLebihTeruk('placeholder', placeholder, EXPECT.baselineProgress.placeholder);
takLebihTeruk('defect mobile', mobileDefects, EXPECT.baselineProgress.mobileDefects);

// Kunci defect mobile yang MASIH ditanda dalam manifest mesti subset bukti pusingan-11 —
// defect yang sudah dibaiki hilang dari manifest (itu matlamatnya), tetapi manifest tidak
// boleh mencipta defect yang tiada dalam bukti produksi.
const manifestDefectKeys = new Set(manifestGuides.flatMap((g) => (g.steps ?? []).filter((s) => s.mobile_defect).map((s) => s.key)));
const extraDefects = setDiff(manifestDefectKeys, defectKeys);
if (extraDefects.length) fail('kunci defect mobile LEBIHAN dalam manifest (tiada dlm bukti produksi)', extraDefects);
const fixedDefects = setDiff(defectKeys, manifestDefectKeys);
if (fixedDefects.length) kemajuan.push(`defect mobile DIBAIKI: ${fixedDefects.join(', ')}`);

// ── 4. Skema role_routes (F0(ii-b)) ────────────────────────────────────────────────────
const rr = manifest.role_routes ?? {};
if ((rr.identities ?? []).length !== 10) fail(`role_routes identiti ${rr.identities?.length} ≠ 10`);
if ((rr.mismatches ?? []).length !== 0) fail(`role_routes mismatches ${rr.mismatches?.length} ≠ 0`);
const rrRequired = ['identity', 'route_template', 'panel', 'expected_access', 'expected_status', 'declared_access', 'in_navigation', 'category'];
for (const e of rr.entries ?? []) {
    for (const f of rrRequired) {
        if (!(f in e)) { fail(`entri role_routes tiada medan ${f}: ${e.identity} ${e.route_template}`); break; }
    }
    if (e.expected_access !== e.declared_access) {
        fail(`role_routes expected≠declared: ${e.identity} ${e.route_template} (${e.expected_access} vs ${e.declared_access})`);
    }
}
// expected_page_counts mesti KONSISTEN dengan entri (dikira dari array, bukan ditaip — gate #1)
for (const [identity, count] of Object.entries(rr.expected_page_counts ?? {})) {
    const computed = (rr.entries ?? []).filter((e) => e.identity === identity && e.panel === 'app'
        && e.expected_access === 'allow' && e.in_navigation).length;
    if (computed !== count) fail(`expected_page_counts[${identity}] ${count} ≠ dikira ${computed}`);
}

if (failures > 0) {
    console.error(`\nVALIDATOR GAGAL: ${failures} kategori kegagalan.`);
    process.exit(1);
}
if (kemajuan.length) {
    console.log('KEMAJUAN berbanding baseline F0:');
    for (const baris of kemajuan) console.log(`  ${baris}`);
}
console.log('OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact, role_routes konsisten.');
