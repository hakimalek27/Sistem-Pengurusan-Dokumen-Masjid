// F6-W4 langkah 2 — tambah 17 entri registri BAHARU sebagai `reserved`.
// `reserved` kerana katalog belum merujuknya: gate registri (d) menolak entri `active` yatim.
// Ia ditukar kepada `active` dalam batch yang sama dengan suntingan katalog (langkah 3).
import { readFileSync, writeFileSync } from 'node:fs';

const LALUAN = 'resources/help/targets.json';
const SINCE = '2026-08-06';
const d = JSON.parse(readFileSync(LALUAN, 'utf8'));

const baharu = [
    // ── Elemen VENDOR dipetakan `decorateTargets()` per halaman (§7.2 langkah 2) ──────────
    ['records-search', 'workflow', '/app/{tenant}/records', 'js:decorateTargets (resources/js/help.js:92)',
        '.fi-ta-search-field[data-help-target="records-search"]', 'both', '-', 'records.view'],
    ['regfiles-search', 'workflow', '/app/{tenant}/registry-files', 'js:decorateTargets (resources/js/help.js:110)',
        '.fi-ta-search-field[data-help-target="regfiles-search"]', 'both', '-', 'files.view'],
    ['log-search', 'workflow', '/app/{tenant}/log-aktiviti', 'js:decorateTargets (resources/js/help.js:98)',
        '.fi-ta-search-field[data-help-target="log-search"]', 'both', '-', 'audit.view'],
    ['log-filters', 'workflow', '/app/{tenant}/log-aktiviti', 'js:decorateTargets (resources/js/help.js:99)',
        '.fi-ta-filters-trigger-action-ctn[data-help-target="log-filters"]', 'both', '-', 'audit.view'],
    ['minit-filters', 'workflow', '/app/{tenant}/minit-saya', 'js:decorateTargets (resources/js/help.js:105)',
        '.fi-ta-filters-trigger-action-ctn[data-help-target="minit-filters"]', 'both',
        'MinitsTable TIADA searchable(); kategori ialah SelectFilter (MinitsTable.php:48)', 'minit.view'],

    // ── Senarai: butang Lihat + sel baris pertama ────────────────────────────────────────
    ['records-view', 'workflow', '/app/{tenant}/records', 'app/Filament/App/Resources/Records/Tables/RecordsTable.php:65',
        '[data-help-target="records-view"]', 'both', 'jadual tidak kosong', 'records.view'],
    ['regfiles-view', 'workflow', '/app/{tenant}/registry-files', 'app/Filament/App/Resources/RegistryFiles/Tables/RegistryFilesTable.php:64',
        '[data-help-target="regfiles-view"]', 'both', 'jadual tidak kosong', 'files.view'],
    ['regfiles-medium', 'workflow', '/app/{tenant}/registry-files', 'app/Filament/App/Resources/RegistryFiles/Tables/RegistryFilesTable.php:54',
        '[data-help-target="regfiles-medium"]', 'both', 'jadual tidak kosong (sel Medium baris pertama)', 'files.view'],

    // ── /pembetulan-rekod ───────────────────────────────────────────────────────────────
    ['correction-diff', 'workflow', '/app/{tenant}/pembetulan-rekod', 'app/Filament/App/Resources/RecordCorrections/Tables/RecordCorrectionsTable.php:41',
        '[data-help-target="correction-diff"]', 'both', 'jadual tidak kosong (sel Perubahan baris pertama)', 'records.update'],
    ['correction-status', 'workflow', '/app/{tenant}/pembetulan-rekod', 'app/Filament/App/Resources/RecordCorrections/Tables/RecordCorrectionsTable.php:44',
        '[data-help-target="correction-status"]', 'both', 'jadual tidak kosong (sel Status baris pertama)', 'records.update'],
    ['correction-decision', 'workflow', '/app/{tenant}/pembetulan-rekod', 'app/Filament/App/Resources/RecordCorrections/Tables/RecordCorrectionsTable.php:51',
        '[data-help-target="correction-decision"]', 'both', 'ada permohonan status `menunggu` DAN kebenaran review', 'records.update'],

    // ── /retensi ────────────────────────────────────────────────────────────────────────
    ['retention-schedule', 'workflow', '/app/{tenant}/retensi', 'resources/views/filament/app/pages/retensi-pegangan.blade.php:19',
        '[data-help-target="retention-schedule"]', 'both', '-', 'retention.hold'],
    ['retention-hold', 'workflow', '/app/{tenant}/retensi', 'resources/views/filament/app/pages/retensi-pegangan.blade.php:44',
        '[data-help-target="retention-hold"]', 'both', '-', 'retention.hold'],
    ['retention-export', 'workflow', '/app/{tenant}/retensi', 'app/Filament/App/Pages/RetensiPegangan.php:79',
        '[data-help-target="retention-export"]', 'both', 'kebenaran export.create (butang disembunyikan tanpanya)', 'export.create'],

    // ── /sensitive-access-logs + /laporan ───────────────────────────────────────────────
    ['sensitive-log-record', 'workflow', '/app/{tenant}/sensitive-access-logs', 'app/Filament/App/Resources/SensitiveAccessLogs/Tables/SensitiveAccessLogsTable.php:34',
        '[data-help-target="sensitive-log-record"]', 'both', 'jadual tidak kosong (baris pertama)', 'audit.view'],
    ['report-summary', 'workflow', '/app/{tenant}/laporan', 'resources/views/filament/app/pages/laporan.blade.php:5',
        '[data-help-target="report-summary"]', 'both', '-', 'records.view'],
    ['report-export', 'workflow', '/app/{tenant}/laporan', 'app/Filament/App/Pages/Laporan.php:48',
        '[data-help-target="report-export"]', 'both', 'kebenaran export.create', 'export.create'],
];

const sedia = new Set(d.targets.map((t) => t.id));
let ditambah = 0;
for (const [id, family, route, owner_source, selector_hint, viewport, state, permission] of baharu) {
    if (sedia.has(id)) { console.log('  LANGKAU (sudah ada): ' + id); continue; }
    d.targets.push({ id, family, route, owner_source, selector_hint, viewport, state, permission, status: 'reserved', since: SINCE });
    ditambah += 1;
}

writeFileSync(LALUAN, JSON.stringify(d, null, 2) + '\n');
console.log('ditambah:', ditambah, '| jumlah entri kini:', d.targets.length);
