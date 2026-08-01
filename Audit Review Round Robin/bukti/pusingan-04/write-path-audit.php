<?php

// Audit-only fixture: runs inside one transaction and rolls back at the end.
$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\ApprovalStatus;
use App\Enums\MinitPriority;
use App\Enums\OcrStatus;
use App\Enums\RecordStatus;
use App\Enums\Sensitivity;
use App\Enums\SourceChannel;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Services\ApprovalService;
use App\Services\DisposalService;
use App\Services\InboxIngestService;
use App\Services\MinitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Throwable;

Notification::fake();
Mail::fake();
Queue::fake();
config(['scout.driver' => 'collection']);

$results = [];
$certificatePath = null;
$pass = static function (string $id, string $detail) use (&$results): void {
    $results[] = ['id' => $id, 'status' => 'PASS', 'detail' => $detail];
};
$fail = static function (string $id, string $detail) use (&$results): void {
    $results[] = ['id' => $id, 'status' => 'FAIL', 'detail' => $detail];
};
$expectReject = static function (string $id, Closure $callback) use ($pass, $fail): void {
    try {
        $callback();
        $fail($id, 'Mutasi silang tenant diterima tanpa exception.');
    } catch (Throwable $e) {
        $pass($id, get_class($e).': '.$e->getMessage());
    }
};

DB::beginTransaction();

try {
    $mam = Mosque::withoutGlobalScopes()->where('slug', 'mam')->firstOrFail();
    $man = Mosque::withoutGlobalScopes()->where('slug', 'man')->firstOrFail();
    $member = static function (Mosque $mosque, string $role) {
        return $mosque->users()->wherePivot('role', $role)->where('users.is_active', true)->firstOrFail();
    };
    $admin = $member($mam, 'admin_masjid');
    $chair = $member($mam, 'pengerusi');
    $secretary = $member($mam, 'setiausaha');
    $foreignAdmin = $member($man, 'admin_masjid');
    $mamFile = RegistryFile::withoutGlobalScopes()->where('mosque_id', $mam->id)->where('status', 'terbuka')->firstOrFail();
    $manFile = RegistryFile::withoutGlobalScopes()->where('mosque_id', $man->id)->where('status', 'terbuka')->firstOrFail();

    $record = Record::create([
        'mosque_id' => $mam->id,
        'record_type' => 'surat_menyurat',
        'title' => 'AUDIT-RR4 write path',
        'record_date' => now()->toDateString(),
        'sensitivity' => Sensitivity::Dalaman,
        'status' => RecordStatus::PetiMasuk,
        'ocr_status' => OcrStatus::Belum,
        'source_channel' => SourceChannel::MuatNaik,
        'source_meta' => ['audit' => true],
        'retention_notified' => [],
        'legal_hold' => false,
    ]);
    $foreignRecord = Record::create([
        'mosque_id' => $man->id,
        'record_type' => 'surat_menyurat',
        'title' => 'AUDIT-RR4 foreign record',
        'record_date' => now()->toDateString(),
        'sensitivity' => Sensitivity::Dalaman,
        'status' => RecordStatus::Difailkan,
        'ocr_status' => OcrStatus::Belum,
        'source_channel' => SourceChannel::MuatNaik,
        'source_meta' => ['audit' => true],
        'retention_notified' => [],
        'legal_hold' => false,
    ]);

    $expectReject('WRITE-01 klasifikasi fail asing', fn () => app(InboxIngestService::class)->fileRecord(
        $record,
        $manFile,
        ['title' => 'AUDIT-RR4 tidak boleh silang tenant'],
        $admin,
    ));
    $pass('WRITE-02 klasifikasi tenant sendiri', app(InboxIngestService::class)->fileRecord(
        $record,
        $mamFile,
        ['title' => 'AUDIT-RR4 diklasifikasi'],
        $admin,
    )->status->value);

    $expectReject('WRITE-03 minit penerima asing', fn () => app(MinitService::class)->create(
        $record,
        $admin,
        [$foreignAdmin->id],
        [],
        'Tidak boleh dihantar ke tenant asing.',
        MinitPriority::Biasa,
    ));
    $minit = app(MinitService::class)->create(
        $record,
        $admin,
        [$chair->id],
        [$secretary->id],
        'AUDIT-RR4 minit tenant sendiri.',
        MinitPriority::Biasa,
    );
    $pass('WRITE-04 minit tenant sendiri', 'minit_id='.$minit->id.' recipients='.$minit->recipients()->count());

    $expectReject('WRITE-05 pelulus asing', fn () => app(ApprovalService::class)->request(
        $record,
        $secretary,
        $foreignAdmin,
        'Tidak boleh diminta daripada tenant asing.',
    ));
    $approval = app(ApprovalService::class)->request($record, $secretary, $chair, 'AUDIT-RR4 kelulusan.');
    $expectReject('WRITE-06 keputusan oleh pengguna asing', fn () => app(ApprovalService::class)->decide(
        $approval,
        $foreignAdmin,
        ApprovalStatus::Lulus,
        'Tampering silang tenant.',
        '127.0.0.1',
    ));
    app(ApprovalService::class)->decide($approval, $chair, ApprovalStatus::Lulus, 'AUDIT-RR4 diluluskan.', '127.0.0.1');
    $pass('WRITE-07 keputusan pelulus tenant sendiri', $approval->fresh()->status->value);

    $batch = app(DisposalService::class)->prepareManual($mam, [$record->id], $admin);
    $expectReject('WRITE-08 kelulusan pelupusan asing', fn () => app(DisposalService::class)->approveManual($batch, $foreignAdmin));
    app(DisposalService::class)->approveManual($batch, $chair);
    $expectReject('WRITE-09 pelaksanaan pelupusan asing', fn () => app(DisposalService::class)->executeManual($batch, $foreignAdmin));
    $completed = app(DisposalService::class)->executeManual($batch, $admin);
    $certificatePath = $completed->certificate_path;
    $pass('WRITE-10 pelupusan tenant sendiri', 'status='.$completed->status.' record='.$record->fresh()->status->value);
} catch (Throwable $e) {
    $fail('WRITE-UNEXPECTED', get_class($e).': '.$e->getMessage());
} finally {
    DB::rollBack();
    if ($certificatePath) {
        Storage::disk(config('diwan.storage_disk'))->delete($certificatePath);
    }
}

$payload = [
    'transaction' => 'rolled_back',
    'production_touched' => false,
    'notifications_sent' => false,
    'mail_sent' => false,
    'queue_executed' => false,
    'results' => $results,
];
file_put_contents(__DIR__.'/write-path-results.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
