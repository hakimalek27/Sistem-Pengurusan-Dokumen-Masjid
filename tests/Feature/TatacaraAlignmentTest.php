<?php

use App\Enums\MinitPriority;
use App\Enums\SourceChannel;
use App\Filament\App\Resources\Inbox\Pages\ListInbox;
use App\Filament\App\Support\RecordTypeSchema;
use App\Services\InboxIngestService;
use App\Services\MinitService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
 * Penjajaran workflow dengan Tatacara Pengurusan Rekod Elektronik dalam DDMS (ANM 2020):
 * §10 Ruj. Kami auto, §6.5.9 u.p. hibrid, carta 8.1 Tarikh Terima, §6.4.2 s.k. boleh balas.
 */

beforeEach(function () {
    Notification::fake();
    Storage::fake(config('diwan.storage_disk'));
    $this->svc = app(InboxIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->kerani = makeMember($this->mam, 'kerani', 'kerani@mam.test');
    $this->pengerusi = makeMember($this->mam, 'pengerusi', 'pengerusi@mam.test');
    $this->file = makeFile($this->mam, makeNode($this->mam, '100-4', 'dalaman'), 'dalaman');
});

it('Ruj. Kami auto-diisi = file_no(enclosure) apabila kosong (§10 Aliran D)', function () {
    $record = $this->svc->ingest($this->mam, 'x', 'surat.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $filed = $this->svc->fileRecord($record, $this->file, ['title' => 'Surat'], $this->kerani);

    expect($filed->our_ref)->toBe($this->file->file_no.'('.$filed->enclosure_no.')');
});

it('Ruj. Kami yang diisi manual TIDAK ditindih', function () {
    $record = $this->svc->ingest($this->mam, 'y', 'surat.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $filed = $this->svc->fileRecord($record, $this->file, ['title' => 'Surat', 'our_ref' => 'MANUAL/2026/1'], $this->kerani);

    expect($filed->our_ref)->toBe('MANUAL/2026/1');
});

it('u.p. padan ahli aktif → cadang penerima tindakan + s.k. Pengerusi (§6.5.9)', function () {
    $ustaz = makeMember($this->mam, 'nazir', 'ustaz@mam.test');
    $ustaz->update(['name' => 'Ustaz Hafiz']);

    $suggestion = RecordTypeSchema::attentionSuggestion($this->mam, 'ustaz hafiz', [], []);

    expect($suggestion['action_ids'])->toBe([$ustaz->id])
        ->and($suggestion['cc_ids'])->toContain($this->pengerusi->id);
});

it('u.p. nama luar sistem → tiada cadangan (teks kekal bebas)', function () {
    $suggestion = RecordTypeSchema::attentionSuggestion($this->mam, 'Orang Luar Bukan Ahli', [], []);

    expect($suggestion['action_ids'])->toBe([])
        ->and($suggestion['cc_ids'])->toBe([]);
});

it('u.p. TIDAK menindih pilihan tindakan kerani yang sedia ada', function () {
    $ustaz = makeMember($this->mam, 'nazir', 'ustaz2@mam.test');
    $ustaz->update(['name' => 'Ustaz Kamal']);
    $su = makeMember($this->mam, 'setiausaha', 'su@mam.test');

    $suggestion = RecordTypeSchema::attentionSuggestion($this->mam, 'Ustaz Kamal', [$su->id], []);

    expect($suggestion['action_ids'])->toBe([$su->id]); // kekal pilihan kerani
});

it('modal klasifikasi prefill Tarikh Terima = tarikh masuk Peti Masuk (carta 8.1)', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->mam, isQuiet: true);
    $this->actingAs($this->kerani);

    $record = $this->svc->ingest($this->mam, 'a', 'surat.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);

    Livewire::test(ListInbox::class)
        ->mountTableAction('klasifikasi', $record)
        ->assertTableActionDataSet(['received_date' => $record->created_at->toDateString()]);

    Filament::setTenant(null, isQuiet: true);
});

it('penerima s.k. (makluman) boleh Balas & Edarkan minit, orang luar tidak (§6.4.2)', function () {
    $record = $this->svc->ingest($this->mam, 'z', 'surat.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $filed = $this->svc->fileRecord($record, $this->file, ['title' => 'Surat'], $this->kerani);
    $su = makeMember($this->mam, 'setiausaha', 'su3@mam.test');

    $minit = app(MinitService::class)->create(
        $filed,
        $this->kerani,
        [$this->pengerusi->id],   // tindakan
        [$su->id],                // makluman (s.k.)
        'Sila ambil tindakan sewajarnya.',
        MinitPriority::Biasa,
    );

    $outsider = makeMember($this->mam, 'ajk', 'ajk@mam.test');

    expect($su->can('reply', $minit))->toBeTrue()          // s.k. boleh balas
        ->and($this->pengerusi->can('reply', $minit))->toBeTrue()  // tindakan boleh balas
        ->and($this->kerani->can('reply', $minit))->toBeTrue()     // pengirim boleh susuli
        ->and($outsider->can('reply', $minit))->toBeFalse();       // bukan penerima → tidak

    // Tanda Selesai kekal terhad kepada penerima tindakan (s.k. tidak boleh).
    expect($su->can('complete', $minit))->toBeFalse()
        ->and($this->pengerusi->can('complete', $minit))->toBeTrue();
});
