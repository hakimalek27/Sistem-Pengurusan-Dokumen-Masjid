// D11 #15 — Assert artifak JSON reporter Playwright (PELAN-PEMBAIKAN.md §1 F0(iv)(e)).
// Dipakai oleh SETIAP gate Playwright tanpa kecuali (canary · ci-guidance · ci-domain ·
// shard guidance-full · ci-ocr): job hijau dengan ujian di-skip BUKAN bukti.
//
// Guna: node scripts/audit/assert-playwright-json.mjs --file <path> --min-tests <N>
// Exit 1 pada MANA-MANA assertion gagal:
//   1. fail wujud + boleh JSON.parse (fail hilang = GAGAL — "tidak pernah berjalan" ≠ "lulus")
//   2. bilangan ujian ≥ --min-tests (menutup testMatch salah taip yang memadankan 0 fail)
//   3. tiada result.status ∈ skipped/timedOut/interrupted (rekursif merentas suites bersarang)
//   4. setiap spec.ok === true; stats.unexpected === 0; stats.flaky === 0
//   5. errors aras-atas kosong
//   6. skema tidak dikenali = gagal keras (ketiadaan TIDAK ditafsir sebagai kejayaan)
//   7. stats.skipped === 0 (bacaan aras-atas langsung — penjaga kedua atas (3))

import { readFileSync } from 'node:fs';

const args = {};
for (let i = 2; i < process.argv.length; i += 2) args[process.argv[i].replace(/^--/, '')] = process.argv[i + 1];
const minTests = Number(args['min-tests'] ?? 1);
if (!args.file || !Number.isFinite(minTests) || minTests < 1) {
    console.error('Guna: node assert-playwright-json.mjs --file <path> --min-tests <N≥1>');
    process.exit(1);
}

const fail = (msg) => { console.error(`ASSERT GAGAL [${args.file}]: ${msg}`); process.exit(1); };

let raw;
try {
    raw = readFileSync(args.file, 'utf8');
} catch {
    fail('fail JSON tidak wujud — larian Playwright tidak pernah berjalan / DIWAN_PW_JSON tidak diset (assertion 1)');
}
let data;
try {
    data = JSON.parse(raw);
} catch {
    fail('kandungan bukan JSON sah (assertion 1)');
}

// (6) skema mesti dikenali — reporter JSON @playwright/test menghasilkan `suites` + `stats`.
if (!Array.isArray(data.suites) || typeof data.stats !== 'object' || data.stats === null) {
    fail('skema JSON reporter tidak dikenali (tiada suites[]/stats{}) — semak versi @playwright/test (assertion 6)');
}

const badStatuses = new Set(['skipped', 'timedOut', 'interrupted']);
let testCount = 0;
const problems = [];
const walk = (suite, trail) => {
    for (const child of suite.suites ?? []) walk(child, `${trail} > ${child.title ?? ''}`);
    for (const spec of suite.specs ?? []) {
        for (const t of spec.tests ?? []) {
            testCount++;
            for (const r of t.results ?? []) {
                if (badStatuses.has(r.status)) problems.push(`${trail} > ${spec.title}: result.status=${r.status}`);
            }
            if (badStatuses.has(t.status)) problems.push(`${trail} > ${spec.title}: test.status=${t.status}`);
        }
        if (spec.ok !== true) problems.push(`${trail} > ${spec.title}: spec.ok=${spec.ok}`);
    }
};
for (const s of data.suites) walk(s, s.title ?? s.file ?? '');

if (testCount < minTests) fail(`bilangan ujian ${testCount} < minimum ${minTests} (assertion 2)`);
if (problems.length) fail(`(assertion 3/4)\n  ${problems.slice(0, 30).join('\n  ')}`);
if ((data.stats.unexpected ?? 0) !== 0) fail(`stats.unexpected=${data.stats.unexpected} (assertion 4)`);
if ((data.stats.flaky ?? 0) !== 0) fail(`stats.flaky=${data.stats.flaky} (assertion 4)`);
if (Array.isArray(data.errors) && data.errors.length > 0) fail(`errors aras-atas: ${data.errors.length} (assertion 5)`);
if ((data.stats.skipped ?? 0) !== 0) fail(`stats.skipped=${data.stats.skipped} (assertion 7)`);

console.log(`OK [${args.file}]: ${testCount} ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.`);
