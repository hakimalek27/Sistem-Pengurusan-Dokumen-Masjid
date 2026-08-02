<?php

use App\Enums\SourceChannel;
use App\Models\Record;
use App\Services\AntivirusScanner;
use App\Services\InboxIngestService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * D11 #14 / §0.6 S7 (PELAN-PEMBAIKAN.md — P18-04): gate fail-closed antivirus intake.
 * Cabang `InboxIngestService.php:72-78` ialah SATU throw sebelum DB::transaction(:91) —
 * assertion "0 rekod / 0 media / 0 log" ialah ujian regresi sebenar, bukan hiasan.
 * `config/diwan.php:32` lalai CLAMAV_ENABLED=false bermakna suite biasa TIDAK PERNAH
 * melalui cabang ini — itulah sebab ujian ber-mock ini wajib (tiada service ClamAV diperlukan).
 */
function ingestWithScan(array $scanResult): void
{
    config()->set('diwan.clamav.enabled', true);
    config()->set('diwan.clamav.fail_closed', true);
    Storage::fake(config('diwan.storage_disk'));

    $mock = Mockery::mock(AntivirusScanner::class);
    $mock->shouldReceive('scan')->once()->andReturn($scanResult);
    app()->instance(AntivirusScanner::class, $mock);

    $mosque = makeMosque('AVA', 'av-a');
    $kerani = makeMember($mosque, 'admin_masjid');

    app(InboxIngestService::class)->ingest(
        $mosque, 'kandungan dokumen ujian antivirus', 'dokumen.pdf', 'application/pdf',
        $kerani, SourceChannel::MuatNaik,
    );
}

dataset('status antivirus ditolak', [
    'infected' => [['status' => 'infected', 'signature' => 'Eicar-Test-Signature', 'message' => 'FOUND']],
    'unavailable' => [['status' => 'unavailable', 'signature' => null, 'message' => 'ClamAV tidak dapat dicapai']],
    'error' => [['status' => 'error', 'signature' => null, 'message' => 'Sambungan ClamAV tamat masa.']],
]);

test('intake fail-closed menolak dokumen dan TIADA kesan sampingan tercipta', function (array $scan) {
    // Tenant lain wujud dahulu — mesti kekal tidak berubah (isolasi keperluan #1).
    $other = makeMosque('AVB', 'av-b');
    $otherNode = makeNode($other, '100-1');
    $otherRecord = makeRecord($other, makeFile($other, $otherNode));
    $baseline = [
        'records' => DB::table('records')->count(),
        'media' => DB::table('media')->count(),
        'logs' => DB::table('mosque_activity_logs')->count(),
    ];

    expect(fn () => ingestWithScan($scan))->toThrow(ValidationException::class);

    expect(DB::table('records')->count())->toBe($baseline['records'], '0 Record baharu — throw mendahului transaksi')
        ->and(DB::table('media')->count())->toBe($baseline['media'], '0 media tercipta')
        ->and(DB::table('mosque_activity_logs')->count())->toBe($baseline['logs'], '0 log aktiviti intake')
        ->and(Record::query()->withoutGlobalScope('mosque')->where('mosque_id', $other->id)->count())->toBe(1)
        ->and($otherRecord->fresh())->not->toBeNull();
})->with('status antivirus ditolak');

test('kawalan: status clean melepasi gate fail-closed (wiring mock betul)', function () {
    config()->set('diwan.clamav.enabled', true);
    config()->set('diwan.clamav.fail_closed', true);
    Storage::fake(config('diwan.storage_disk'));

    $mock = Mockery::mock(AntivirusScanner::class);
    $mock->shouldReceive('scan')->once()->andReturn(['status' => 'clean', 'signature' => null, 'message' => null]);
    app()->instance(AntivirusScanner::class, $mock);

    $mosque = makeMosque('AVC', 'av-c');
    $kerani = makeMember($mosque, 'admin_masjid');
    $record = app(InboxIngestService::class)->ingest(
        $mosque, 'kandungan bersih', 'bersih.pdf', 'application/pdf', $kerani, SourceChannel::MuatNaik,
    );

    expect($record)->not->toBeNull()
        ->and($record->virus_scan_status)->toBe('clean');
});
