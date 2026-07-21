<?php

/*
|--------------------------------------------------------------------------
| Peranan & Kebenaran per-masjid (§6.0–§6.2)
|--------------------------------------------------------------------------
| Peranan = string pada pivot mosque_user.role. Kebenaran = peta statik ini
| (matriks §6.2). is_superadmin = bendera global dengan Gate::before (§6.0).
|
| Nota penguatkuasaan tambahan (di Policy, bukan di sini):
| - bendahari: records.create/update terhad kepada rekod dalam fail klasifikasi
|   `200` (Kewangan) — dikuatkuasa dalam RecordPolicy (§6.2 *).
| - admin_masjid ialah role kanonik Admin / Kerani dan menggabungkan semua
|   kebenaran operasi pentadbiran serta kerja kerani.
| - Pengasingan tugas: disposal.approve (pengerusi) ≠ disposal.execute (admin_masjid).
*/

return [

    // Senarai peranan per masjid (§6.1).
    'list' => [
        'admin_masjid', 'pengerusi', 'setiausaha', 'bendahari',
        'nazir', 'ketua_imam', 'ajk', 'audit',
    ],

    // Label BM untuk UI.
    'labels' => [
        'admin_masjid' => 'Admin / Kerani',
        'pengerusi' => 'Pengerusi',
        'setiausaha' => 'Setiausaha',
        'bendahari' => 'Bendahari',
        'nazir' => 'Nazir',
        'ketua_imam' => 'Ketua Imam',
        'ajk' => 'AJK',
        'audit' => 'Juruaudit',
    ],

    // Senarai semua kunci kebenaran (rujukan).
    'permissions' => [
        'inbox.view', 'inbox.classify',
        'records.view', 'records.create', 'records.update', 'records.move', 'records.supersede',
        'files.view', 'files.open', 'files.close', 'files.grant_access',
        'minit.create', 'minit.respond',
        'approvals.request', 'approvals.decide',
        'classification.manage',
        'retention.manage', 'retention.hold',
        'export.create',
        'disposal.prepare', 'disposal.approve', 'disposal.execute',
        'users.manage', 'mosque.settings',
        'usage.view', 'storage.order', 'audit.view',
    ],

    // Matriks §6.2 — peranan => senarai kebenaran yang dibenarkan.
    'matrix' => [

        'admin_masjid' => [
            'inbox.view', 'inbox.classify',
            'records.view', 'records.create', 'records.update', 'records.move', 'records.supersede',
            'files.view', 'files.open', 'files.close', 'files.grant_access',
            'minit.create', 'minit.respond',
            'approvals.request',
            'classification.manage',
            'retention.manage', 'retention.hold',
            'export.create',
            'disposal.prepare', 'disposal.execute',
            'users.manage', 'mosque.settings',
            'usage.view', 'storage.order', 'audit.view',
        ],

        'pengerusi' => [
            'records.view',
            'files.view', 'files.grant_access',
            'minit.create', 'minit.respond',
            'approvals.decide',
            'disposal.approve',
            'usage.view', 'audit.view',
        ],

        'setiausaha' => [
            'inbox.view', 'inbox.classify',
            'records.view', 'records.create', 'records.update', 'records.supersede',
            'files.view',
            'minit.create', 'minit.respond',
            'approvals.request',
        ],

        'bendahari' => [
            'records.view', 'records.create', 'records.update',
            'files.view',
            'minit.create', 'minit.respond',
            'approvals.request',
            'usage.view', 'storage.order',
        ],

        'nazir' => [
            'records.view',
            'files.view',
            'minit.create', 'minit.respond',
            'approvals.decide',
        ],

        'ketua_imam' => [
            'records.view',
            'files.view',
            'minit.create', 'minit.respond',
        ],

        'ajk' => [
            'records.view',
            'files.view',
            'minit.create', 'minit.respond',
        ],

        'audit' => [
            'records.view',
            'files.view',
            'audit.view',
        ],

    ],

];
