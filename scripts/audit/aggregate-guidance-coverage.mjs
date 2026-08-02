// D11 #7 (PELAN-PEMBAIKAN.md §1 F0(iv)(c)) — agregator gate `guidance-e2e-gate`.
// Perbandingan SET terhadap manifest, BUKAN kiraan: dua ID silap boleh membatalkan antara
// satu sama lain; kesamaan kardinaliti sahaja tidak diterima (P16-02).
// Shard yang tidak berjalan ≠ shard yang lulus — artifak hilang = GAGAL.
//
// Guna: node scripts/audit/aggregate-guidance-coverage.mjs \
//         --manifest "Audit Review Round Robin/bukti/plan-baseline/manifest.json" \
//         --shards "storage/app/plan-f6/artifacts/guidance-shard-*/shard-*.json" \
//         --out storage/app/plan-f6/coverage-gate.json
// Exit 0 hanya jika SEMUA assertion lulus; selainnya 1 dengan senarai ID penyebab.

import { globSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname } from 'node:path';

const args = {};
for (let i = 2; i < process.argv.length; i += 2) args[process.argv[i].replace(/^--/, '')] = process.argv[i + 1];
for (const k of ['manifest', 'shards', 'out']) {
    if (!args[k]) { console.error(`FAIL: --${k} wajib`); process.exit(1); }
}

const EXPECTED_SHARDS = ['screen', 'workflow', 'tenant-admin-public'];
const problems = [];
const listIds = (ids) => `${ids.slice(0, 50).join(', ')}${ids.length > 50 ? ` … +${ids.length - 50} lagi (jumlah ${ids.length})` : ''}`;
const problem = (msg, ids = []) => problems.push(ids.length ? `${msg}: ${listIds(ids)}` : msg);

const manifest = JSON.parse(readFileSync(args.manifest, 'utf8'));
const files = globSync(args.shards);
const shards = [];
for (const file of files) {
    try {
        shards.push({ file, data: JSON.parse(readFileSync(file, 'utf8')) });
    } catch (e) {
        problem(`artifak shard tidak boleh dibaca/parse: ${file} (${e.message})`);
    }
}

// 1) Ketiga-tiga shard mesti HADIR — hilang = gagal, dengan nama shardnya.
const present = new Set(shards.map((s) => s.data.shard));
for (const name of EXPECTED_SHARDS) {
    if (!present.has(name)) problem(`missing shard (tidak berjalan ≠ lulus): ${name}`);
}
for (const s of shards) {
    if (!EXPECTED_SHARDS.includes(s.data.shard)) problem(`shard tidak dikenali: ${s.data.shard} (${s.file})`);
}

// 2) schema_version / catalog_version / manifest_sha256 mesti sepadan merentas shard.
for (const s of shards) {
    if (s.data.schema_version !== 1) problem(`schema_version ${s.data.schema_version} ≠ 1 (${s.data.shard})`);
    if (s.data.catalog_version !== manifest.catalog_version) {
        problem(`catalog_version shard ${s.data.shard} (${s.data.catalog_version}) ≠ manifest (${manifest.catalog_version})`);
    }
}
const shas = new Set(shards.map((s) => s.data.manifest_sha256));
if (shas.size > 1) problem(`manifest_sha256 berbeza antara shard: ${[...shas].join(' vs ')}`);

// 3) Set manifest (semesta penuh).
const mGuides = new Set(manifest.catalogue.map((g) => g.guide_id));
const mSteps = new Set(manifest.catalogue.flatMap((g) => g.steps.map((s) => s.key)));
const mActions = new Set(manifest.catalogue.flatMap((g) => g.steps.filter((s) => s.wait_for_user).map((s) => s.key)));

// 4) Union + duplikat dalam-shard + bertindih antara-shard, per kategori.
const categories = [
    ['guide_ids', mGuides, 83],
    ['step_ids', mSteps, 473],
    ['action_step_ids', mActions, 229],
];
const summary = {};
for (const [field, manifestSet, exactTotal] of categories) {
    const union = new Set();
    const overlap = new Set();
    for (const s of shards) {
        const seen = new Set();
        for (const id of s.data[field] ?? []) {
            if (seen.has(id)) problem(`duplikat dalam shard ${s.data.shard} (${field})`, [id]);
            seen.add(id);
            if (union.has(id)) overlap.add(id);
            union.add(id);
        }
    }
    const missing = [...manifestSet].filter((id) => !union.has(id));
    const extra = [...union].filter((id) => !manifestSet.has(id));
    if (missing.length) problem(`${field} HILANG daripada gabungan shard`, missing);
    if (extra.length) problem(`${field} LEBIHAN (tiada dalam manifest)`, extra);
    if (overlap.size) problem(`${field} BERTINDIH antara shard`, [...overlap]);
    // Semakan kedua (selepas set): kardinaliti exact.
    if (!missing.length && !extra.length && !overlap.size && union.size !== exactTotal) {
        problem(`${field} jumlah ${union.size} ≠ ${exactTotal}`);
    }
    summary[field] = { union: union.size, expected: exactTotal, missing: missing.length, extra: extra.length, overlap: overlap.size };
}

// 5) blocked == 0 (release blocker — P14-06) · failures kosong · complete true.
for (const s of shards) {
    const blocked = s.data.blocked ?? [];
    if (blocked.length) problem(`shard ${s.data.shard} mempunyai langkah blocked`, blocked.map((b) => b.step));
    const failures = s.data.failures ?? [];
    if (failures.length) problem(`shard ${s.data.shard} mempunyai failures`, failures.map((f) => `${f.step}(${f.gate})`));
    if (s.data.complete !== true) problem(`shard ${s.data.shard} melaporkan complete=${s.data.complete}`);
}

const gate = {
    schema_version: 1,
    manifest: args.manifest,
    manifest_sha256: [...shas][0] ?? null,
    shards_found: shards.map((s) => ({ shard: s.data.shard, file: s.file, complete: s.data.complete })),
    summary,
    problems,
    pass: problems.length === 0,
};
mkdirSync(dirname(args.out), { recursive: true });
writeFileSync(args.out, JSON.stringify(gate, null, 2) + '\n');

if (problems.length) {
    console.error(`GATE GAGAL (${problems.length} masalah):`);
    for (const p of problems) console.error(`  - ${p}`);
    console.error(`Laporan: ${args.out}`);
    process.exit(1);
}
console.log(`GATE LULUS: 83 guide · 473 langkah · 229 langkah tindakan — union tiga shard sepadan manifest (set, bukan kiraan). Laporan: ${args.out}`);
