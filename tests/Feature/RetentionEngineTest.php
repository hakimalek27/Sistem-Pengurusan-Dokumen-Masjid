<?php

use App\Enums\MosqueStatus;
use App\Enums\RecordStatus;
use App\Enums\SourceChannel;
use App\Models\DisposalItem;
use App\Models\Record;
use App\Services\DisposalService;
use App\Services\InboxIngestService;
use App\Services\RetentionEngine;
use Database\Seeders\RetentionRuleSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/** Cipta rekod difailkan backdate, dengan/ tanpa notis t30/t7. */
function mkFiled(int $backYears = 8, bool $notices = true, string $type = 'surat_menyurat'): Record
{
    $ingest = app(InboxIngestService::class);
    $engine = app(RetentionEngine::class);

    $record = $ingest->ingest(test()->mam, 'dok-'.uniqid(), 'd.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $record = $ingest->fileRecord($record, test()->file, ['record_type' => $type], test()->kerani);
    $record->update(['record_date' => now()->subYears($backYears)]);
    $engine->refreshForRecord($record->fresh());

    $record = $record->fresh();
    if ($notices) {
        $record->update(['retention_notified' => ['t90' => 'x', 't30' => 'x', 't7' => 'x']]);
    }

    return $record->fresh();
}

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    Notification::fake();
    $this->seed(RetentionRuleSeeder::class);
    $this->disposal = app(DisposalService::class);
    $this->mam = makeMosque('MAM', 'mam'); // aktif, auto_disposal_enabled=true
    $this->kerani = makeMember($this->mam, 'kerani', 'k@mam.test');
    $this->file = makeFile($this->mam, makeNode($this->mam, '100-4', 'dalaman'), 'dalaman');
});

it('POSITIF: padam bila due + t30 + t7 → dilupus + snapshot + sijil + blob dipadam', function () {
    $record = mkFiled(backYears: 8, notices: true);
    expect($record->getFirstMedia('original'))->not->toBeNull();

    $batch = $this->disposal->executeAuto($this->mam);

    expect($batch)->not->toBeNull()
        ->and($record->fresh()->status)->toBe(RecordStatus::Dilupus)
        ->and($record->fresh()->ocr_text)->toBeNull()
        ->and($record->fresh()->getFirstMedia('original'))->toBeNull() // blob dipadam
        ->and(DisposalItem::query()->where('record_id', $record->id)->exists())->toBeTrue() // snapshot kekal
        ->and($batch->certificate_path)->not->toBeNull()
        ->and(Storage::disk(config('diwan.storage_disk'))->exists($batch->certificate_path))->toBeTrue();
});

it('NEGATIF kekal: jenis minit_mesyuarat (kekal) TIDAK dipadam', function () {
    $record = mkFiled(backYears: 20, notices: true, type: 'minit_mesyuarat');
    expect($record->retention_due_at)->toBeNull(); // kekal

    $batch = $this->disposal->executeAuto($this->mam);

    expect($batch)->toBeNull()
        ->and($record->fresh()->status)->toBe(RecordStatus::Difailkan);
});

it('NEGATIF legal_hold: rekod dipegang TIDAK dipadam', function () {
    $record = mkFiled(backYears: 8, notices: true);
    $record->update(['legal_hold' => true, 'retention_due_at' => null]);

    $batch = $this->disposal->executeAuto($this->mam);

    expect($batch)->toBeNull()
        ->and($record->fresh()->status)->toBe(RecordStatus::Difailkan);
});

it('NEGATIF toggle-off: masjid auto_disposal_enabled=false TIDAK dipadam', function () {
    $record = mkFiled(backYears: 8, notices: true);
    $this->mam->update(['auto_disposal_enabled' => false]);

    $batch = $this->disposal->executeAuto($this->mam->fresh());

    expect($batch)->toBeNull()
        ->and($record->fresh()->status)->toBe(RecordStatus::Difailkan);
});

it('NEGATIF tiada-notis: due tetapi t30/t7 belum dihantar → TIDAK dipadam (invarian kritikal §16.3)', function () {
    $record = mkFiled(backYears: 8, notices: false);
    expect($record->retention_due_at)->not->toBeNull();

    $batch = $this->disposal->executeAuto($this->mam);

    expect($batch)->toBeNull()
        ->and($record->fresh()->status)->toBe(RecordStatus::Difailkan);
});

it('NEGATIF masjid digantung: pelupusan DIJEDA (§10.M)', function () {
    $record = mkFiled(backYears: 8, notices: true);
    $this->mam->update(['status' => MosqueStatus::Digantung]);

    $batch = $this->disposal->executeAuto($this->mam->fresh());

    expect($batch)->toBeNull()
        ->and($record->fresh()->status)->toBe(RecordStatus::Difailkan);
});

it('kitaran penuh command: notices ×1 → t90/t30/t7 direkod → execute memadam', function () {
    $record = mkFiled(backYears: 8, notices: false);
    expect(($record->retention_notified ?? []))->not->toHaveKeys(['t30', 't7']);

    $this->artisan('diwan:run-retention-notices')->assertSuccessful();

    $notified = $record->fresh()->retention_notified;
    expect($notified)->toHaveKeys(['t90', 't30', 't7']);

    $this->artisan('diwan:run-retention-execute')->assertSuccessful();

    expect($record->fresh()->status)->toBe(RecordStatus::Dilupus);
});
