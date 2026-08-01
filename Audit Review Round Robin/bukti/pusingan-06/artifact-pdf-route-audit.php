<?php

// Audit-only local fixture. Database transaction is rolled back; generated PDFs are deleted.
$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\OcrStatus;
use App\Enums\RecordStatus;
use App\Enums\Sensitivity;
use App\Enums\SourceChannel;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Services\BillingService;
use App\Services\DisposalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

Notification::fake();
Mail::fake();
Queue::fake();
config(['scout.driver' => 'collection']);
$disk = Storage::disk(config('diwan.storage_disk'));
$paths = [];
$result = ['route' => [], 'pdf' => []];

DB::beginTransaction();
try {
    $mam = Mosque::withoutGlobalScopes()->where('slug', 'mam')->firstOrFail();
    $man = Mosque::withoutGlobalScopes()->where('slug', 'man')->firstOrFail();
    $admin = $mam->users()->wherePivot('role', 'admin_masjid')->firstOrFail();
    $chair = $mam->users()->wherePivot('role', 'pengerusi')->firstOrFail();
    $bendahari = $mam->users()->wherePivot('role', 'bendahari')->firstOrFail();
    $file = RegistryFile::withoutGlobalScopes()->where('mosque_id', $mam->id)->where('status', 'terbuka')->firstOrFail();

    $record = Record::create([
        'mosque_id' => $mam->id,
        'registry_file_id' => $file->id,
        'record_type' => 'surat_menyurat',
        'title' => 'AUDIT-RR6 sijil pelupusan',
        'record_date' => now()->toDateString(),
        'enclosure_no' => 1,
        'sensitivity' => Sensitivity::Dalaman,
        'status' => RecordStatus::Difailkan,
        'ocr_status' => OcrStatus::Siap,
        'source_channel' => SourceChannel::MuatNaik,
        'source_meta' => ['audit' => true],
        'retention_notified' => [],
        'legal_hold' => false,
    ]);
    $batch = app(DisposalService::class)->prepareManual($mam, [$record->id], $admin);
    app(DisposalService::class)->approveManual($batch, $chair);
    $done = app(DisposalService::class)->executeManual($batch, $admin);
    $paths[] = $done->certificate_path;
    $tmpCertificate = tempnam(sys_get_temp_dir(), 'rr6-cert-');
    $tmpCertificateText = $tmpCertificate.'.txt';
    file_put_contents($tmpCertificate, $disk->get($done->certificate_path));
    $certExit = 0;
    exec('pdftotext '.escapeshellarg($tmpCertificate).' '.escapeshellarg($tmpCertificateText), $unused, $certExit);
    $certText = is_file($tmpCertificateText) ? file_get_contents($tmpCertificateText) : '';
    $result['pdf']['certificate'] = [
        'status' => $certExit === 0 && str_contains($certText, 'SIJIL PELUPUSAN') && str_contains($certText, $mam->name) && ! str_contains($certText, $man->name) ? 'PASS' : 'FAIL',
        'text' => $certText,
        'tenant_leak' => str_contains($certText, $man->name),
    ];
    @unlink($tmpCertificate);
    @unlink($tmpCertificateText);

    $order = app(BillingService::class)->createOrder($mam, $bendahari, 1, 12, 'audit-rr6-'.strtolower((string) str()->ulid()));
    $paths[] = $order->invoice_path;
    $tmpInvoice = tempnam(sys_get_temp_dir(), 'rr6-invoice-');
    $tmpInvoiceText = $tmpInvoice.'.txt';
    file_put_contents($tmpInvoice, $disk->get($order->invoice_path));
    $invoiceExit = 0;
    exec('pdftotext '.escapeshellarg($tmpInvoice).' '.escapeshellarg($tmpInvoiceText), $unused, $invoiceExit);
    $invoiceText = is_file($tmpInvoiceText) ? file_get_contents($tmpInvoiceText) : '';
    $result['pdf']['invoice'] = [
        'status' => $invoiceExit === 0 && str_contains($invoiceText, 'INVOIS') && str_contains($invoiceText, $mam->name) && ! str_contains($invoiceText, $man->name) ? 'PASS' : 'FAIL',
        'invoice_no' => $order->invoice_no,
        'text' => $invoiceText,
        'tenant_leak' => str_contains($invoiceText, $man->name),
    ];
    @unlink($tmpInvoice);
    @unlink($tmpInvoiceText);

    $result['route'] = [
        '/app/mam/penggunaan' => 'expected 200 (slug in PenggunaanStoran.php)',
        '/app/mam/penggunaan-storan' => 'expected 404 (not the registered slug)',
    ];
} finally {
    DB::rollBack();
    foreach (array_unique(array_filter($paths)) as $path) {
        $disk->delete($path);
    }
}

file_put_contents(__DIR__.'/artifact-pdf-route-results.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
