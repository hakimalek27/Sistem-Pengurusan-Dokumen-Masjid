<?php

declare(strict_types=1);

use App\Enums\MinitPriority;
use App\Models\Delegation;
use App\Models\Favourite;
use App\Models\FileMovement;
use App\Models\RecordCorrectionRequest;
use App\Models\SavedSearch;
use App\Models\SensitiveAccessLog;
use App\Models\StorageOrder;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\MinitService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! app()->environment(['local', 'testing'])) {
    fwrite(STDERR, "Manual fixtures are allowed only in local/testing.\n");
    exit(1);
}

$mosque = \App\Models\Mosque::query()->where('slug', 'mam')->firstOrFail();
$users = $mosque->users()->get()->keyBy(fn (User $user): ?string => $user->pivot?->role);
$admin = $users->get('admin_masjid');
$secretary = $users->get('setiausaha');
$chairman = $users->get('pengerusi');
$nazir = $users->get('nazir');
$record = $mosque->records()->where('status', 'difailkan')->where('sensitivity', '!=', 'sulit')->firstOrFail();
$financeRecord = $mosque->records()->whereHas('registryFile.classificationNode', fn ($query) => $query->where('code', 'like', '200%'))->first();
$hybridFile = $record->registryFile;

$record->update([
    'our_ref' => 'MAM/100-4/2026/017',
    'their_ref' => 'JAWI.600-3/2/1',
    'record_date' => today()->subDays(3),
    'received_date' => today()->subDays(2),
    'direction' => 'masuk',
    'sender_name' => 'Pn. Nur Aisyah',
    'sender_org' => 'Bahagian Pentadbiran JAWI',
    'recipient_name' => 'Pengerusi Masjid',
    'virus_scan_status' => 'bersih',
    'virus_scanned_at' => now(),
    'ocr_status' => 'siap',
    'ocr_text' => 'Surat jemputan mesyuarat jawatankuasa masjid. Sila ambil tindakan dan sahkan kehadiran.',
]);

$hybridFile->update([
    'medium' => 'hibrid',
    'physical_reference' => 'Kotak MAM-2026-01',
    'physical_location' => 'Bilik Rekod, Rak A2',
    'custody_status' => 'dalam_simpanan',
]);

FileMovement::query()->firstOrCreate([
    'mosque_id' => $mosque->id,
    'registry_file_id' => $hybridFile->id,
    'action' => 'pindah',
    'to_location' => 'Bilik Rekod, Rak A2',
], [
    'from_location' => 'Kaunter Registri',
    'notes' => 'Penempatan awal fail fizikal untuk latihan manual.',
    'handled_by' => $admin->id,
]);

$samplePdf = getenv('MANUAL_SAMPLE_PDF') ?: '';
if ($samplePdf !== '' && is_file($samplePdf) && $record->getMedia('original')->isEmpty()) {
    $record->addMedia($samplePdf)
        ->preservingOriginal()
        ->usingName('Contoh Dokumen DDMS')
        ->usingFileName('contoh-dokumen-ddms.pdf')
        ->toMediaCollection('original');
}

$minitService = app(MinitService::class);
foreach ($users as $role => $user) {
    if ($user->id === $admin->id) {
        continue;
    }

    $exists = \App\Models\Minit::query()
        ->where('mosque_id', $mosque->id)
        ->where('body', "like", "Arahan latihan manual untuk {$role}:%")
        ->exists();

    if (! $exists) {
        $minitService->create(
            $record,
            $admin,
            [$user->id],
            [],
            "Arahan latihan manual untuk {$role}: semak dokumen, catat tindakan dan tandakan selesai.",
            MinitPriority::Biasa,
        );
    }
}

if (! \App\Models\Approval::query()->where('mosque_id', $mosque->id)->where('approver_id', $chairman->id)->where('status', 'menunggu')->exists()) {
    app(ApprovalService::class)->request($record, $secretary, $chairman, 'Mohon Pengerusi semak dan luluskan dokumen contoh ini.');
}

if (! \App\Models\Approval::query()->where('mosque_id', $mosque->id)->where('approver_id', $nazir->id)->where('status', 'menunggu')->exists()) {
    app(ApprovalService::class)->request($record, $secretary, $nazir, 'Mohon Nazir membuat keputusan bagi dokumen contoh ini.');
}

if ($financeRecord && ! \App\Models\Approval::query()->where('mosque_id', $mosque->id)->where('requested_by', $users->get('bendahari')->id)->exists()) {
    app(ApprovalService::class)->request($financeRecord, $users->get('bendahari'), $chairman, 'Permohonan kelulusan rekod kewangan contoh.');
}

foreach ($users as $role => $user) {
    Favourite::query()->firstOrCreate([
        'mosque_id' => $mosque->id,
        'user_id' => $user->id,
        'target_type' => Favourite::RECORD,
        'target_id' => $record->id,
    ]);
    Favourite::query()->firstOrCreate([
        'mosque_id' => $mosque->id,
        'user_id' => $user->id,
        'target_type' => Favourite::REGISTRY_FILE,
        'target_id' => $hybridFile->id,
    ]);
    SavedSearch::query()->firstOrCreate([
        'mosque_id' => $mosque->id,
        'user_id' => $user->id,
        'name' => 'Surat masuk 30 hari',
    ], [
        'criteria' => ['query' => 'surat', 'direction' => 'masuk', 'recordDateFrom' => today()->subDays(30)->toDateString()],
        'is_default' => true,
    ]);
    RecordCorrectionRequest::query()->firstOrCreate([
        'mosque_id' => $mosque->id,
        'record_id' => $record->id,
        'requested_by' => $user->id,
    ], [
        'reason' => "Contoh aliran pembetulan untuk peranan {$role}.",
        'proposed_changes' => ['recipient_name' => 'Setiausaha Masjid'],
        'status' => 'menunggu',
    ]);
}

Delegation::query()->firstOrCreate([
    'mosque_id' => $mosque->id,
    'principal_user_id' => $chairman->id,
    'delegate_user_id' => $nazir->id,
], [
    'capabilities' => ['minit', 'approvals'],
    'starts_at' => now()->subDay(),
    'ends_at' => now()->addDays(7),
    'is_active' => true,
    'reason' => 'Contoh wakil semasa Pengerusi menghadiri program luar.',
    'created_by' => $admin->id,
]);

SensitiveAccessLog::query()->firstOrCreate([
    'mosque_id' => $mosque->id,
    'record_id' => $record->id,
    'user_id' => $admin->id,
    'action' => 'view',
], [
    'ip' => '127.0.0.1',
    'user_agent' => 'Chrome Manual Capture',
]);

StorageOrder::query()->firstOrCreate([
    'mosque_id' => $mosque->id,
    'invoice_no' => 'INV-MANUAL-001',
], [
    'ordered_by' => $admin->id,
    'gb' => 10,
    'unit_price_cents' => 5000,
    'amount_cents' => 5000,
    'period_months' => 12,
    'status' => 'menunggu_bayaran',
    'idempotency_key' => '7ec8b852-5f24-4f84-8493-f83a37080fef',
]);

fwrite(STDOUT, json_encode([
    'mosque' => $mosque->slug,
    'users' => $users->count(),
    'record' => $record->id,
    'file' => $hybridFile->id,
    'media' => $record->getMedia('original')->count(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
