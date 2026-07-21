<?php

use App\Enums\ApprovalStatus;
use App\Enums\MinitPriority;
use App\Enums\SourceChannel;
use App\Models\Favourite;
use App\Models\SavedSearch;
use App\Services\ApprovalService;
use App\Services\DelegationService;
use App\Services\FavouriteService;
use App\Services\FileTrackingService;
use App\Services\InboxIngestService;
use App\Services\MinitService;
use App\Services\RecordCorrectionService;
use App\Services\SavedSearchService;
use App\Services\SearchService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    $this->mamAdmin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
    $this->mamKerani = makeMember($this->mam, 'kerani', 'kerani@mam.test');
    $this->mamPengerusi = makeMember($this->mam, 'pengerusi', 'pengerusi@mam.test');
    $this->mamAjk = makeMember($this->mam, 'ajk', 'ajk@mam.test');
    $this->mamFile = makeFile($this->mam, makeNode($this->mam, '100-4'));
    $this->record = makeRecord($this->mam, $this->mamFile, 'dalaman', 'surat_menyurat', [
        'title' => 'Surat Asal', 'our_ref' => 'MAM/100/1', 'source_meta' => ['from' => 'pengirim@test'],
    ]);
});

it('saved search dan favourite terikat kepada pengguna serta tenant', function () {
    app(SavedSearchService::class)->save($this->mamKerani, $this->mam, 'Surat kewangan', ['query' => 'kewangan'], true);
    expect(SavedSearch::query()->where('mosque_id', $this->mam->id)->where('user_id', $this->mamKerani->id)->first()->criteria)->toBe(['query' => 'kewangan']);

    expect(app(FavouriteService::class)->toggle($this->mamKerani, $this->mam, Favourite::RECORD, $this->record->id))->toBeTrue()
        ->and(Favourite::query()->where('user_id', $this->mamKerani->id)->count())->toBe(1)
        ->and(app(FavouriteService::class)->resolveVisible($this->mamKerani, $this->man, Favourite::RECORD, $this->record->id))->toBeNull();
});

it('carian lanjutan filter tarikh, pengirim dan sumber tanpa melangkaui tenant', function () {
    $this->record->update([
        'record_date' => '2026-07-20', 'received_date' => '2026-07-21', 'sender_name' => 'Jabatan Ujian',
        'source_channel' => 'emel',
    ]);

    $results = app(SearchService::class)->for($this->mamKerani, $this->mam, '', [
        'record_date_from' => '2026-07-20', 'record_date_to' => '2026-07-20', 'sender' => 'jabatan', 'source_channel' => 'emel',
    ]);

    expect($results->pluck('id')->all())->toBe([$this->record->id]);
});

it('pembetulan rekod memerlukan semakan dan menyimpan audit sebelum/sesudah', function () {
    $request = app(RecordCorrectionService::class)->request($this->record, $this->mamAjk, 'Tajuk tersalah tawan dan perlu dibaiki.', [
        'title' => 'Surat Dibetulkan', 'our_ref' => 'MAM/100/2',
    ]);

    expect($request->status)->toBe('menunggu')->and($this->record->fresh()->title)->toBe('Surat Asal');

    app(RecordCorrectionService::class)->review($request, $this->mamKerani, true, 'Disahkan daripada dokumen asal.');
    expect($this->record->fresh()->title)->toBe('Surat Dibetulkan')
        ->and($request->fresh()->status)->toBe('diluluskan')
        ->and($request->fresh()->reviewed_by)->toBe($this->mamKerani->id);
});

it('delegasi approval merekodkan actor sebenar dan principal asal', function () {
    app(DelegationService::class)->create($this->mamPengerusi, $this->mam, [
        'principal_user_id' => $this->mamPengerusi->id,
        'delegate_user_id' => $this->mamAjk->id,
        'capabilities' => ['approvals'],
        'starts_at' => now()->subMinute(), 'ends_at' => now()->addDay(),
        'reason' => 'Cuti',
    ]);
    $request = app(ApprovalService::class)->request($this->record, $this->mamKerani, $this->mamPengerusi);
    app(ApprovalService::class)->decide($request, $this->mamAjk, ApprovalStatus::Lulus, 'Diluluskan bagi pihak.', '1.2.3.4');

    expect($request->fresh()->status)->toBe(ApprovalStatus::Lulus)
        ->and($request->fresh()->decided_by)->toBe($this->mamAjk->id)
        ->and($request->fresh()->on_behalf_of)->toBe($this->mamPengerusi->id);
});

it('delegasi minit membolehkan principal menerima tindakan tanpa membuka rekod tenant lain', function () {
    $audit = makeMember($this->mam, 'audit', 'audit@mam.test');
    app(DelegationService::class)->create($this->mamPengerusi, $this->mam, [
        'principal_user_id' => $this->mamPengerusi->id,
        'delegate_user_id' => $audit->id,
        'capabilities' => ['minit'],
        'starts_at' => now()->subMinute(), 'ends_at' => now()->addDay(),
        'reason' => 'Cuti',
    ]);
    $minit = app(MinitService::class)->create($this->record, $this->mamAdmin, [$this->mamPengerusi->id], [], 'Sila semak.', MinitPriority::Biasa);
    app(MinitService::class)->markDone($minit, $audit);

    $recipient = $minit->recipients()->first();
    expect($recipient->status)->toBe('selesai')
        ->and($recipient->acted_by_user_id)->toBe($audit->id)
        ->and($recipient->acted_on_behalf_of_user_id)->toBe($this->mamPengerusi->id);
});

it('tracking fizikal merekodkan keluar, pulang dan tenant file yang tepat', function () {
    $this->mamFile->update(['medium' => 'hibrid', 'physical_location' => 'Bilik Rekod']);
    $movement = app(FileTrackingService::class)->checkout($this->mamFile, $this->mamKerani, [
        'holder_user_id' => $this->mamPengerusi->id, 'to_location' => 'Bilik Mesyuarat', 'due_at' => now()->addDay(), 'notes' => 'Mesyuarat',
    ]);
    expect($movement->action)->toBe('keluar')->and($this->mamFile->fresh()->custody_status)->toBe('dipinjam');

    app(FileTrackingService::class)->return($this->mamFile->fresh(), $this->mamKerani, 'Bilik Rekod', 'Pulangan');
    expect($this->mamFile->fresh()->custody_status)->toBe('dalam_simpanan')
        ->and($this->mamFile->fresh()->movements()->count())->toBe(2);
});

it('fail elektronik tidak boleh menggunakan tracking fizikal', function () {
    $this->mamFile->update(['medium' => 'elektronik']);

    expect(fn () => app(FileTrackingService::class)->relocate($this->mamFile, $this->mamKerani, 'Bilik Rekod'))
        ->toThrow(ValidationException::class);
});

it('tracking fizikal menolak pemegang tenant lain dan pulangan tanpa rekod keluar', function () {
    $this->mamFile->update(['medium' => 'hibrid', 'physical_location' => 'Bilik Rekod']);
    $outsider = makeMember($this->man, 'pengerusi', 'pengerusi@man.test');

    expect(fn () => app(FileTrackingService::class)->checkout($this->mamFile, $this->mamKerani, [
        'holder_user_id' => $outsider->id,
        'notes' => 'Tidak sah',
    ]))->toThrow(ValidationException::class)
        ->and(fn () => app(FileTrackingService::class)->return($this->mamFile->fresh(), $this->mamKerani, 'Bilik Rekod'))
        ->toThrow(ValidationException::class);
});

it('delegate boleh melihat tetapi tidak boleh membatalkan delegasi principal', function () {
    $delegation = app(DelegationService::class)->create($this->mamPengerusi, $this->mam, [
        'principal_user_id' => $this->mamPengerusi->id,
        'delegate_user_id' => $this->mamAjk->id,
        'capabilities' => ['minit'],
        'starts_at' => now()->subMinute(),
        'ends_at' => now()->addDay(),
        'reason' => 'Cuti',
    ]);

    expect($this->mamAjk->can('view', $delegation))->toBeTrue()
        ->and($this->mamAjk->can('delete', $delegation))->toBeFalse()
        ->and($this->mamPengerusi->can('delete', $delegation))->toBeTrue();
});

it('intake menyimpan status antivirus dan metadata masa masuk', function () {
    config()->set('diwan.clamav.enabled', false);
    $record = app(InboxIngestService::class)->ingest($this->mam, 'dokumen ujian', 'surat.txt', 'text/plain', $this->mamKerani, SourceChannel::MuatNaik);

    expect($record->virus_scan_status)->toBe('disabled')
        ->and($record->source_meta['ingested_at'])->not->toBeEmpty()
        ->and($record->source_meta['uploader_id'])->toBe($this->mamKerani->id);
});
