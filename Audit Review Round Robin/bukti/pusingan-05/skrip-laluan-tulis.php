<?php
// Pusingan 5d (betul) — happy-path TULIS via service + probe silang-tenant. Tempatan sahaja.
use Illuminate\Support\Facades\Auth;

$mam = \App\Models\Mosque::withoutGlobalScopes()->where('slug', 'mam')->firstOrFail();
$man = \App\Models\Mosque::withoutGlobalScopes()->where('slug', 'man')->firstOrFail();
$admin = \App\Models\User::where('email', 'admin_masjid@demo.test')->firstOrFail();
$pengerusi = \App\Models\User::where('email', 'pengerusi@demo.test')->firstOrFail();

$recMam = \App\Models\Record::withoutGlobalScopes()->find(4);
$recMan = \App\Models\Record::withoutGlobalScopes()->find(5);
$fileMam = \App\Models\RegistryFile::withoutGlobalScopes()->where('mosque_id', $mam->id)->first();
$fileMan = \App\Models\RegistryFile::withoutGlobalScopes()->where('mosque_id', $man->id)->first();

$snap = fn () => [
    'difailkan' => \App\Models\Record::withoutGlobalScopes()->where('status', 'difailkan')->count(),
    'minit' => \App\Models\Minit::withoutGlobalScopes()->count(),
    'approval' => \App\Models\Approval::withoutGlobalScopes()->count(),
    'minit_man' => \App\Models\Minit::withoutGlobalScopes()->where('mosque_id', $man->id)->count(),
    'appr_man' => \App\Models\Approval::withoutGlobalScopes()->where('mosque_id', $man->id)->count(),
];
$before = $snap();
echo 'SEBELUM: '.json_encode($before).PHP_EOL.PHP_EOL;

Auth::login($admin);
$attr = [
    'record_type' => 'surat_menyurat',
    'title' => '[AUDIT RR5] Rekod diklasifikasikan',
    'direction' => \App\Enums\RecordDirection::Masuk,
    'record_date' => now()->toDateString(),
];

echo "=== 1. KLASIFIKASI (happy path) rekod #4 → fail MAM #{$fileMam->id} ===".PHP_EOL;
try {
    app(\App\Services\InboxIngestService::class)->fileRecord($recMam, $fileMam, $attr, $admin);
    $recMam->refresh();
    echo "  ✅ status={$recMam->status->value} fail={$recMam->registry_file_id} our_ref={$recMam->our_ref} enclosure={$recMam->enclosure_no}".PHP_EOL;
} catch (\Throwable $e) {
    echo '  ❌ '.class_basename($e).': '.mb_substr($e->getMessage(), 0, 140).PHP_EOL;
}

echo "  [SILANG-TENANT] failkan rekod MAM ke fail MAN #{$fileMan->id}:".PHP_EOL;
try {
    app(\App\Services\InboxIngestService::class)->fileRecord($recMam, $fileMan, $attr, $admin);
    $recMam->refresh();
    echo $recMam->registry_file_id == $fileMan->id
        ? '     ⚠️⚠️ BOCOR — rekod MAM kini dalam fail MAN'.PHP_EOL
        : "     ✅ tidak berkesan (fail kekal {$recMam->registry_file_id})".PHP_EOL;
} catch (\Throwable $e) {
    echo '     ✅ DITOLAK: '.class_basename($e).' — '.mb_substr($e->getMessage(), 0, 100).PHP_EOL;
}

echo PHP_EOL.'=== 2. MINIT: admin → pengerusi ==='.PHP_EOL;
try {
    $m = app(\App\Services\MinitService::class)->create($recMam, $admin, [$pengerusi->id], [], '[AUDIT RR5] Sila ambil tindakan.', \App\Enums\MinitPriority::Segera);
    echo "  ✅ minit #{$m->id} keutamaan={$m->priority->value} penerima=".$m->recipients()->count().PHP_EOL;
} catch (\Throwable $e) {
    echo '  ❌ '.class_basename($e).': '.mb_substr($e->getMessage(), 0, 140).PHP_EOL;
}
echo '  [SILANG-TENANT] minit ke atas rekod MAN #5:'.PHP_EOL;
try {
    app(\App\Services\MinitService::class)->create($recMan, $admin, [$pengerusi->id], [], 'CUBAAN SILANG TENANT', \App\Enums\MinitPriority::Biasa);
    echo '     ⚠️⚠️ BOCOR — minit tercipta pada rekod MAN'.PHP_EOL;
} catch (\Throwable $e) {
    echo '     ✅ DITOLAK: '.class_basename($e).' — '.mb_substr($e->getMessage(), 0, 100).PHP_EOL;
}

echo PHP_EOL.'=== 3. KELULUSAN: admin mohon → pengerusi putus ==='.PHP_EOL;
$appId = null;
try {
    $a = app(\App\Services\ApprovalService::class)->request($recMam, $admin, $pengerusi, '[AUDIT RR5] Mohon kelulusan.');
    $appId = $a->id;
    echo "  ✅ mohon approval #{$appId} status={$a->status->value}".PHP_EOL;
} catch (\Throwable $e) {
    echo '  ❌ mohon: '.class_basename($e).' — '.mb_substr($e->getMessage(), 0, 130).PHP_EOL;
}
if ($appId) {
    Auth::login($pengerusi);
    try {
        $a = \App\Models\Approval::withoutGlobalScopes()->find($appId);
        app(\App\Services\ApprovalService::class)->decide($a, $pengerusi, \App\Enums\ApprovalStatus::Lulus, '[AUDIT RR5] Diluluskan.', '127.0.0.1');
        $a->refresh();
        echo "  ✅ putus status={$a->status->value} oleh={$a->decided_by}".PHP_EOL;
    } catch (\Throwable $e) {
        echo '  ❌ putus: '.class_basename($e).' — '.mb_substr($e->getMessage(), 0, 130).PHP_EOL;
    }
    Auth::login($admin);
}
echo '  [SILANG-TENANT] mohon kelulusan rekod MAN #5:'.PHP_EOL;
try {
    app(\App\Services\ApprovalService::class)->request($recMan, $admin, $pengerusi, 'CUBAAN SILANG TENANT');
    echo '     ⚠️⚠️ BOCOR'.PHP_EOL;
} catch (\Throwable $e) {
    echo '     ✅ DITOLAK: '.class_basename($e).' — '.mb_substr($e->getMessage(), 0, 100).PHP_EOL;
}

echo PHP_EOL.'=== 4. PINDAH FAIL silang-tenant (moveToFile) ==='.PHP_EOL;
try {
    app(\App\Services\InboxIngestService::class)->moveToFile($recMam, $fileMan, 'CUBAAN SILANG TENANT', $admin);
    $recMam->refresh();
    echo $recMam->registry_file_id == $fileMan->id ? '     ⚠️⚠️ BOCOR' : '     ✅ tidak berkesan'.PHP_EOL;
} catch (\Throwable $e) {
    echo '     ✅ DITOLAK: '.class_basename($e).' — '.mb_substr($e->getMessage(), 0, 100).PHP_EOL;
}

$after = $snap();
echo PHP_EOL.'SELEPAS: '.json_encode($after).PHP_EOL;
echo 'DELTA: difailkan +'.($after['difailkan'] - $before['difailkan'])
    .' | minit +'.($after['minit'] - $before['minit'])
    .' | approval +'.($after['approval'] - $before['approval'])
    .' | MINIT DALAM MAN +'.($after['minit_man'] - $before['minit_man'])
    .' | APPROVAL DALAM MAN +'.($after['appr_man'] - $before['appr_man']).PHP_EOL;
