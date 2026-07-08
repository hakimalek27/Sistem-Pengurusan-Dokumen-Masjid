<?php

use App\Models\Record;
use App\Services\MailIngestService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->svc = app(MailIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
});

it('mengekstrak slug daripada plus-addressing', function () {
    expect($this->svc->slugFromAddress('scan.diwan+man@gmail.com'))->toBe('man')
        ->and($this->svc->slugFromAddress('scan.diwan+mam@gmail.com'))->toBe('mam')
        ->and($this->svc->slugFromAddress('biasa@gmail.com'))->toBeNull();
});

it('e-mel ke +man → rekod masuk peti MAN bukan MAM (§18.36)', function () {
    $result = $this->svc->ingestMessage(
        ['scan.diwan+man@gmail.com'],
        'pengirim@luar.com',
        'Surat MAIS 2026',
        'MID-1',
        [['content' => 'dokumen-pdf', 'filename' => 'surat.pdf', 'mime' => 'application/pdf']],
    );

    expect($result['status'])->toBe('ok')
        ->and(Record::forMosque($this->man)->count())->toBe(1)
        ->and(Record::forMosque($this->mam)->count())->toBe(0);
});

it('slug tidak dikenali → tiada rekod', function () {
    $result = $this->svc->ingestMessage(
        ['scan.diwan+tiada@gmail.com'], 'a@b.com', 'x', 'MID-2',
        [['content' => 'x', 'filename' => 'x.pdf', 'mime' => 'application/pdf']],
    );

    expect($result['status'])->toBe('unknown_or_inactive')
        ->and(Record::query()->count())->toBe(0);
});

it('lampiran duplikat skop-masjid diskip (§11.3)', function () {
    $attachment = [['content' => 'kandungan-sama', 'filename' => 'a.pdf', 'mime' => 'application/pdf']];

    $this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'a@b.com', 'x', 'MID-3', $attachment);
    $second = $this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'a@b.com', 'x', 'MID-4', $attachment);

    expect(Record::forMosque($this->mam)->count())->toBe(1)
        ->and($second['skipped_duplicate'])->toBe(1);
});

it('lampiran MIME tidak dibenarkan ditapis (§15.7)', function () {
    $this->svc->ingestMessage(
        ['scan.diwan+mam@gmail.com'], 'a@b.com', 'x', 'MID-5',
        [['content' => 'x', 'filename' => 'jahat.exe', 'mime' => 'application/octet-stream']],
    );

    expect(Record::query()->count())->toBe(0);
});
