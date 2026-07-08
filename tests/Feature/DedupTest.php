<?php

use App\Enums\SourceChannel;
use App\Models\Record;
use App\Services\InboxIngestService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->svc = app(InboxIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
});

it('muat naik: duplikat dalam masjid sama tetap dicipta tetapi ditanda ⚠', function () {
    $r1 = $this->svc->ingest($this->mam, 'kandungan-sama', 'surat.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $r2 = $this->svc->ingest($this->mam, 'kandungan-sama', 'surat2.pdf', 'application/pdf', null, SourceChannel::MuatNaik);

    expect($r1)->not->toBeNull()
        ->and($r2)->not->toBeNull()
        ->and($r1->sha256)->toBe($r2->sha256)
        ->and($this->svc->isFlaggedDuplicate($r2->fresh()))->toBeTrue();
});

it('e-mel/webhook: duplikat dalam masjid sama diskip (null, tiada rekod kedua)', function () {
    $first = $this->svc->ingest($this->mam, 'sama', 'a.pdf', 'application/pdf', null, SourceChannel::Emel, [], skipIfDuplicate: true);
    $second = $this->svc->ingest($this->mam, 'sama', 'b.pdf', 'application/pdf', null, SourceChannel::Emel, [], skipIfDuplicate: true);

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull()
        ->and(Record::forMosque($this->mam)->count())->toBe(1);
});

it('sha256 sama DIBENARKAN merentas masjid berbeza', function () {
    $mam = $this->svc->ingest($this->mam, 'sama', 'a.pdf', 'application/pdf', null, SourceChannel::Emel, [], skipIfDuplicate: true);
    $man = $this->svc->ingest($this->man, 'sama', 'a.pdf', 'application/pdf', null, SourceChannel::Emel, [], skipIfDuplicate: true);

    expect($mam)->not->toBeNull()
        ->and($man)->not->toBeNull()
        ->and($mam->sha256)->toBe($man->sha256)
        ->and(Record::forMosque($this->mam)->count())->toBe(1)
        ->and(Record::forMosque($this->man)->count())->toBe(1);
});
