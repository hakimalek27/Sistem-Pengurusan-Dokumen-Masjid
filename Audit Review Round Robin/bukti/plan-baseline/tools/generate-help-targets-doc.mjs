// Penjana docs/HELP-TARGETS.md daripada resources/help/targets.json (D11 #13b).
// Dokumen DIJANA — jangan sunting tangan; sunting registry kemudian jana semula.
// Guna: node "Audit Review Round Robin/bukti/plan-baseline/tools/generate-help-targets-doc.mjs"

import { readFileSync, writeFileSync } from 'node:fs';

const registry = JSON.parse(readFileSync('resources/help/targets.json', 'utf8'));
const rows = registry.targets;
const active = rows.filter((t) => t.status === 'active');
const reserved = rows.filter((t) => t.status !== 'active');

const table = (items) => [
    '| ID | Family | Route | Sumber | Viewport | Prasyarat (`state`) | Permission |',
    '|---|---|---|---|---|---|---|',
    ...items.map((t) => `| \`${t.id}\` | ${t.family} | \`${t.route}\` | \`${t.owner_source}\` | ${t.viewport} | ${t.state} | ${t.permission} |`),
].join('\n');

const doc = `# HELP-TARGETS — Registry Sasaran \`data-help-target\`

> **DIJANA** daripada \`resources/help/targets.json\` oleh
> \`Audit Review Round Robin/bukti/plan-baseline/tools/generate-help-targets-doc.mjs\` —
> JANGAN sunting fail ini secara tangan (PELAN-PEMBAIKAN.md §7.2 langkah 4).
> Registry ialah sumber kebenaran; ujian membaca registry, bukan grep sumber.

## Sasaran AKTIF (${active.length}) — dirujuk katalog; mesti unik + kelihatan dlm DOM route-nya

${table(active)}

## Sasaran RIZAB (${reserved.length}) — wujud dlm DOM, belum dirujuk katalog

${table(reserved)}

${reserved.map((t) => `- \`${t.id}\`: ${t.reason ?? '-'} (sejak ${t.since})`).join('\n')}

## Peraturan (gate registry §7.2)

1. Skema sah (ujian struktur) · setiap sasaran \`active\` **unik dan kelihatan** dalam render
   halaman \`route\`-nya pada \`viewport\` yang dinyatakan, selepas \`state\` disediakan.
2. Tahan morph Livewire (atribut datang dari HTML server).
3. Yatim dua hala = 0: setiap \`target\` bukan-generik dalam katalog wujud dalam registry;
   setiap entri registry dirujuk ≥1 guide ATAU bertanda \`reserved\`.
4. Penamaan: \`{skrin}-{fungsi}\` (cth \`records-search\`, \`approvals-approve\`).
5. \`generic-justified\` hanya melalui allowlist bersebab + bertarikh (manifest baseline).
`;

writeFileSync('docs/HELP-TARGETS.md', doc);
console.log(`OK: docs/HELP-TARGETS.md dijana (${active.length} aktif + ${reserved.length} rizab).`);
