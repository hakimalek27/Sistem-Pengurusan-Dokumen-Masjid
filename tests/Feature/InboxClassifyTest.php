<?php

use App\Enums\RecordStatus;
use App\Enums\Sensitivity;
use App\Enums\SourceChannel;
use App\Filament\App\Resources\Inbox\Pages\ListInbox;
use App\Models\Minit;
use App\Notifications\MinitRoutedNotification;
use App\Services\InboxIngestService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Notification::fake();
    Storage::fake(config('diwan.storage_disk'));
    $this->svc = app(InboxIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->kerani = makeMember($this->mam, 'kerani');
    $this->pengerusi = makeMember($this->mam, 'pengerusi');
    $this->su = makeMember($this->mam, 'setiausaha');
    $this->node = makeNode($this->mam, '200-2', 'sulit');
    $this->file = makeFile($this->mam, $this->node, 'sulit');
});

it('failkan rekod: enclosure diperuntuk, difailkan, waris sensitiviti max, mosque_id betul', function () {
    $record = $this->svc->ingest($this->mam, 'dokumen', 'surat.pdf', 'application/pdf', null, SourceChannel::MuatNaik);

    expect($record->status)->toBe(RecordStatus::PetiMasuk)
        ->and($record->sensitivity)->toBe(Sensitivity::Dalaman)
        ->and($record->registry_file_id)->toBeNull();

    $filed = $this->svc->fileRecord($record, $this->file, ['title' => 'Resit Julai'], $this->kerani);

    expect($filed->status)->toBe(RecordStatus::Difailkan)
        ->and($filed->enclosure_no)->toBe(1)
        ->and($filed->sensitivity)->toBe(Sensitivity::Sulit) // max(dalaman, sulit)
        ->and($filed->registry_file_id)->toBe($this->file->id)
        ->and($filed->mosque_id)->toBe($this->mam->id)
        ->and($filed->title)->toBe('Resit Julai')
        ->and($filed->filed_at)->not->toBeNull();
});

it('enclosure_no berturutan untuk beberapa rekod dalam fail sama', function () {
    $r1 = $this->svc->ingest($this->mam, 'a', 'a.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $r2 = $this->svc->ingest($this->mam, 'b', 'b.pdf', 'application/pdf', null, SourceChannel::MuatNaik);

    $f1 = $this->svc->fileRecord($r1, $this->file, [], $this->kerani);
    $f2 = $this->svc->fileRecord($r2, $this->file, [], $this->kerani);

    expect($f1->enclosure_no)->toBe(1)
        ->and($f2->enclosure_no)->toBe(2)
        ->and($this->file->fresh()->enclosure_count)->toBe(2);
});

it('modal klasifikasi boleh terus edarkan minit dan menghantar notifikasi', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->mam, isQuiet: true);
    $this->actingAs($this->kerani);

    $record = $this->svc->ingest($this->mam, 'dokumen minit', 'surat.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);

    Livewire::test(ListInbox::class)
        ->callTableAction('klasifikasi', $record, data: [
            'record_type' => 'surat_menyurat',
            'title' => 'Surat Arahan Program',
            'direction' => 'masuk',
            'record_date' => now()->toDateString(),
            'registry_file_id' => $this->file->id,
            'sensitivity' => 'dalaman',
            'minit_action_ids' => [$this->pengerusi->id],
            'minit_cc_ids' => [$this->su->id],
            'minit_body' => 'Untuk perhatian dan arahan tuan.',
            'minit_priority' => 'segera',
        ]);

    $filed = $record->fresh();

    expect($filed->status)->toBe(RecordStatus::Difailkan)
        ->and(Minit::query()->where('record_id', $filed->id)->count())->toBe(1);

    Notification::assertSentTo($this->pengerusi, MinitRoutedNotification::class);
    Notification::assertSentTo($this->su, MinitRoutedNotification::class);

    Filament::setTenant(null, isQuiet: true);
});

it('modal klasifikasi membenarkan minit kosong tetapi menyekat kandungan ke-101 secara atomik', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->mam, isQuiet: true);
    $this->actingAs($this->kerani);
    $this->file->update(['enclosure_count' => 99]);

    $record = $this->svc->ingest($this->mam, 'tanpa minit', 'tanpa-minit.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);
    Livewire::test(ListInbox::class)
        ->callTableAction('klasifikasi', $record, data: [
            'record_type' => 'surat_menyurat',
            'title' => 'Rekod Tanpa Edaran Minit',
            'direction' => 'masuk',
            'record_date' => now()->toDateString(),
            'registry_file_id' => $this->file->id,
            'sensitivity' => 'dalaman',
            'minit_action_ids' => [],
            'minit_cc_ids' => [],
            'minit_body' => '',
            'minit_priority' => 'biasa',
        ]);

    expect($record->fresh()->status)->toBe(RecordStatus::Difailkan)
        ->and($this->file->fresh()->enclosure_count)->toBe(100)
        ->and(Minit::query()->where('record_id', $record->id)->count())->toBe(0);

    $overflow = $this->svc->ingest($this->mam, 'melebihi jilid', 'lebihan.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);
    Livewire::test(ListInbox::class)
        ->callTableAction('klasifikasi', $overflow, data: [
            'record_type' => 'surat_menyurat',
            'title' => 'Kandungan Ke-101',
            'direction' => 'masuk',
            'record_date' => now()->toDateString(),
            'registry_file_id' => $this->file->id,
            'sensitivity' => 'dalaman',
            'minit_action_ids' => [],
            'minit_cc_ids' => [],
            'minit_body' => '',
            'minit_priority' => 'biasa',
        ]);

    expect($overflow->fresh()->status)->toBe(RecordStatus::PetiMasuk)
        ->and($overflow->fresh()->registry_file_id)->toBeNull()
        ->and($this->file->fresh()->enclosure_count)->toBe(100);
    Filament::setTenant(null, isQuiet: true);
});

it('pindah fail merekod audit & memperuntuk enclosure baharu', function () {
    $node2 = makeNode($this->mam, '100-4', 'dalaman');
    $file2 = makeFile($this->mam, $node2, 'dalaman');

    $record = $this->svc->ingest($this->mam, 'x', 'x.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $filed = $this->svc->fileRecord($record, $this->file, [], $this->kerani);

    $moved = $this->svc->moveToFile($filed, $file2, 'Salah fail asal', $this->kerani);

    expect($moved->registry_file_id)->toBe($file2->id)
        ->and(Activity::query()->where('description', 'pindah_fail')->exists())->toBeTrue();
});
