// F6-W5 langkah 3 — daftar sasaran BAHARU + tampal entri sedia ada yang skopnya melebar.
//
// Jalankan dari root repo:  node "Audit Review Round Robin/bukti/plan-f6-w5/skrip/tambah-registri-w5.mjs"
//
// ⚠️ Tulis dengan `JSON.stringify(d, null, 4) + "\n"` — format fail ini. `json_encode` PHP
// memecahkan seluruh fail (pelajaran W2).
import { readFileSync, writeFileSync } from 'node:fs';

const PATH = 'resources/help/targets.json';
const SINCE = '2026-08-07';

/** [id, family, route, owner_source, viewport, state, permission, status, reason?] */
const BAHARU = [
    // ── Papan pemuka ────────────────────────────────────────────────────────────────────
    ['dashboard-stats', 'tenant|admin', '/app/{tenant}|/admin', 'js:decorateTargets (page-target-plan.js → .fi-wi-stats-overview)', 'both', '-', '-', 'active'],
    ['dashboard-checklist', 'tenant', '/app/{tenant}', 'resources/views/filament/app/widgets/onboarding-checklist.blade.php:9', 'both', '-', 'mosque.settings', 'active'],

    // ── Carian lanjutan ─────────────────────────────────────────────────────────────────
    ['search-text', 'tenant', '/app/{tenant}/carian', 'resources/views/filament/app/pages/cari-rekod.blade.php:34', 'both', '-', 'records.view', 'active'],
    ['search-parties', 'tenant', '/app/{tenant}/carian', 'resources/views/filament/app/pages/cari-rekod.blade.php:56', 'both', '-', 'records.view', 'active'],
    ['search-submit', 'tenant', '/app/{tenant}/carian', 'resources/views/filament/app/pages/cari-rekod.blade.php:64', 'both', '-', 'records.view', 'active'],
    ['search-save', 'tenant', '/app/{tenant}/carian', 'resources/views/filament/app/pages/cari-rekod.blade.php:16', 'both', '-', 'records.view', 'active'],
    ['search-saved', 'tenant', '/app/{tenant}/carian', 'resources/views/filament/app/pages/cari-rekod.blade.php:6', 'both', '-', 'records.view', 'active'],

    // ── Ahli & peranan ──────────────────────────────────────────────────────────────────
    ['members-list', 'tenant', '/app/{tenant}/ahli-peranan', 'resources/views/filament/app/pages/ahli-peranan.blade.php:4', 'both', '-', 'users.manage', 'active'],
    ['members-role', 'tenant', '/app/{tenant}/ahli-peranan', 'resources/views/filament/app/pages/ahli-peranan.blade.php:32', 'desktop', 'ahli pertama yang BUKAN superadmin', 'users.manage', 'active'],
    ['members-actions', 'tenant', '/app/{tenant}/ahli-peranan', 'resources/views/filament/app/pages/ahli-peranan.blade.php:74', 'desktop', 'ahli pertama yang BUKAN superadmin', 'users.manage', 'active'],

    // ── Tetapan masjid ──────────────────────────────────────────────────────────────────
    ['mosque-settings-profile', 'tenant', '/app/{tenant}/tetapan-masjid', 'resources/views/filament/app/pages/tetapan-masjid.blade.php:4', 'both', '-', 'mosque.settings', 'active'],
    ['mosque-settings-whatsapp', 'tenant', '/app/{tenant}/tetapan-masjid', 'resources/views/filament/app/pages/tetapan-masjid.blade.php:15', 'both', '-', 'mosque.settings', 'active'],

    // ── Laporan & analitik ──────────────────────────────────────────────────────────────
    ['report-breakdown', 'tenant', '/app/{tenant}/laporan', 'resources/views/filament/app/pages/laporan.blade.php:14', 'both', '-', 'records.view', 'active'],
    ['analytics-metrics', 'tenant|admin', '/app/{tenant}/analitik-bantuan|/admin/analitik-bantuan', 'resources/views/filament/pages/help-analytics.blade.php:7', 'both', '-', 'help.analytics', 'active'],

    // ── Delegasi ────────────────────────────────────────────────────────────────────────
    ['delegation-revoke', 'tenant', '/app/{tenant}/delegasi', 'app/Filament/App/Resources/Delegations/Tables/DelegationsTable.php:44', 'desktop', 'ada delegasi AKTIF yang belum tamat (baris pertama)', 'delegations.manage', 'active'],

    // ── Peti masuk ──────────────────────────────────────────────────────────────────────
    ['inbox-view', 'tenant', '/app/{tenant}/peti-masuk', 'app/Filament/App/Resources/Inbox/Tables/InboxTable.php:88', 'desktop', 'jadual tidak kosong (baris pertama)', 'inbox.view', 'active'],
    ['inbox-source', 'tenant', '/app/{tenant}/peti-masuk', 'app/Filament/App/Resources/Inbox/Tables/InboxTable.php:66', 'desktop', 'jadual tidak kosong (sel Penghantar/Sumber baris pertama)', 'inbox.view', 'active'],
    ['inbox-spam', 'tenant', '/app/{tenant}/peti-masuk', 'app/Filament/App/Resources/Inbox/Tables/InboxTable.php:438', 'desktop', 'jadual tidak kosong (baris pertama)', 'inbox.classify', 'active'],

    // ── Log & audit ─────────────────────────────────────────────────────────────────────
    ['log-time', 'tenant', '/app/{tenant}/log-aktiviti', 'app/Filament/App/Resources/MosqueActivityLogs/Tables/MosqueActivityLogsTable.php:38', 'desktop', 'jadual tidak kosong (sel Tarikh & Masa baris pertama)', 'activity.view', 'active'],
    ['sensitive-log-search', 'tenant', '/app/{tenant}/sensitive-access-logs', 'js:decorateTargets (page-target-plan.js → .fi-ta-search-field)', 'desktop', '-', 'audit.view', 'active'],
    ['sensitive-log-target', 'tenant', '/app/{tenant}/sensitive-access-logs', 'app/Filament/App/Resources/SensitiveAccessLogs/Tables/SensitiveAccessLogsTable.php:44', 'desktop', 'jadual tidak kosong (sel Rekod baris pertama)', 'audit.view', 'active'],

    // ── Fail & klasifikasi ──────────────────────────────────────────────────────────────
    ['regfiles-status', 'tenant', '/app/{tenant}/registry-files', 'app/Filament/App/Resources/RegistryFiles/Tables/RegistryFilesTable.php:47', 'desktop', 'jadual tidak kosong (sel Status baris pertama)', 'files.view', 'active'],
    ['classnode-search', 'tenant', '/app/{tenant}/classification-nodes', 'js:decorateTargets (page-target-plan.js → .fi-ta-search-field)', 'desktop', '-', 'classification.manage', 'active'],

    // ── Tiket sokongan (dua panel, jadual dikongsi) ─────────────────────────────────────
    ['tickets-search', 'tenant|admin', '/app/{tenant}/tiket-sokongan|/admin/tiket-sokongan', 'js:decorateTargets (page-target-plan.js → .fi-ta-search-field)', 'desktop', '-', 'support.manage', 'active'],

    // ── Panel platform ──────────────────────────────────────────────────────────────────
    ['platform-mosques', 'admin', '/admin/mosques', 'js:decorateTargets (page-target-plan.js → .fi-ta-search-field)', 'desktop', '-', '-', 'active'],
    ['platform-users', 'admin', '/admin/users', 'js:decorateTargets (page-target-plan.js → .fi-ta-search-field)', 'desktop', '-', '-', 'active'],
    ['platform-storage-orders', 'admin', '/admin/storage-orders', 'js:decorateTargets (page-target-plan.js → .fi-ta-search-field)', 'desktop', '-', '-', 'active'],
    ['platform-announcements', 'admin', '/admin/help-announcements', 'js:decorateTargets (page-target-plan.js → .fi-ta-search-field)', 'desktop', '-', '-', 'active'],
    ['platform-channels', 'admin', '/admin/status-sambungan', 'resources/views/filament/admin/pages/status-sambungan.blade.php:4', 'both', '-', '-', 'active'],
    ['platform-whatsapp', 'admin', '/admin/whatsapp-platform', 'resources/views/filament/admin/pages/whatsapp-platform.blade.php:4', 'both', '-', '-', 'active'],
    ['platform-settings', 'admin', '/admin/tetapan-platform', 'resources/views/filament/admin/pages/tetapan-platform.blade.php:4', 'both', '-', '-', 'active'],

    // ── Pusat bantuan: sasaran yang SUDAH dirender tetapi tidak pernah didaftarkan ───────
    // Ditemui F6-W5 §4: enam `data-help-target` hidup dalam DOM sejak binaan asal tanpa satu
    // pun entri registri. Ujian yatim hanya membandingkan katalog ↔ registri, jadi ia buta
    // kepada atribut DOM yang tiada rujukan. Hanya `help-search` dirujuk katalog; empat lagi
    // didaftar `reserved` supaya ia kelihatan dan boleh dipakai kemudian tanpa penemuan semula.
    ['help-search', 'tenant|admin', '/app/{tenant}/bantuan|/admin/bantuan', 'resources/views/livewire/help-center.blade.php:23', 'both', '-', '-', 'active'],
    ['help-center', 'tenant|admin', '/app/{tenant}/bantuan|/admin/bantuan', 'resources/views/livewire/help-center.blade.php:1', 'both', '-', '-', 'reserved', 'Pembalut seluruh pusat bantuan; dipakai oleh gate laluan fallback (guidance-full.spec.js) tetapi belum dirujuk mana-mana langkah katalog.'],
    ['help-diagnosis', 'tenant|admin', '/app/{tenant}/bantuan|/admin/bantuan', 'resources/views/livewire/help-center.blade.php:128', 'both', '-', '-', 'reserved', 'Seksyen diagnosis pusat bantuan — calon W6/F7; belum dirujuk katalog.'],
    ['help-preferences', 'tenant|admin', '/app/{tenant}/bantuan|/admin/bantuan', 'resources/views/livewire/help-center.blade.php:158', 'both', '-', '-', 'reserved', 'Seksyen tetapan bantuan — calon W6/F7; belum dirujuk katalog.'],
    ['help-support', 'tenant|admin', '/app/{tenant}/bantuan|/admin/bantuan', 'resources/views/livewire/help-center.blade.php:199', 'both', '-', '-', 'reserved', 'Seksyen hantar tiket — calon W6/F7; belum dirujuk katalog.'],
];

/** Entri SEDIA ADA yang perlu ditampal: [id, {medan: nilai baharu}]. */
const TAMPAL = [
    // Dirujuk `tenant.laporan#3` mulai W5.
    ['report-export', { status: 'active' }],
    // Kini turut dipakai oleh `admin.profil-saya#2`; halaman profil superadmin merender
    // sasaran yang SAMA (diukur: /admin/profil-saya membawa profil-akaun).
    ['profil-akaun', { family: 'screen|admin', route: '/app/{tenant}/profil|/admin/profil-saya' }],
];

const doc = JSON.parse(readFileSync(PATH, 'utf8'));
const sedia = new Map(doc.targets.map((t) => [t.id, t]));
let tambah = 0;
let tampal = 0;

for (const [id, family, route, owner_source, viewport, state, permission, status, reason] of BAHARU) {
    if (sedia.has(id)) {
        console.log(`LANGKAU (sudah ada): ${id}`);
        continue;
    }
    const entri = {
        id,
        family,
        route,
        owner_source,
        selector_hint: `[data-help-target="${id}"]`,
        viewport,
        state,
        permission,
        status,
        since: SINCE,
    };
    if (reason) entri.reason = reason;
    doc.targets.push(entri);
    tambah += 1;
}

for (const [id, patch] of TAMPAL) {
    const entri = sedia.get(id);
    if (!entri) throw new Error(`TAMPAL gagal: ${id} tiada dalam registri`);
    Object.assign(entri, patch);
    tampal += 1;
}

doc.targets.sort((a, b) => a.id.localeCompare(b.id));
writeFileSync(PATH, JSON.stringify(doc, null, 4) + '\n');

const aktif = doc.targets.filter((t) => t.status === 'active').length;
console.log(`\n+${tambah} entri baharu · ${tampal} ditampal · jumlah ${doc.targets.length} (aktif ${aktif}, rizab ${doc.targets.length - aktif})`);
