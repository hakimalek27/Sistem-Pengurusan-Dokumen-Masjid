<?php

use App\Models\Record;
use App\Services\MailIngestService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    config()->set('imap.accounts.default.username', 'scan.diwan@gmail.com');
    $this->svc = app(MailIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    foreach ([$this->mam, $this->man] as $mosque) {
        $mosque->update(['settings' => array_merge($mosque->settings, [
            'mail_intake_enabled' => true,
            'mail_intake_keyword' => 'spdm',
            'mail_intake_senders' => ['pengirim@luar.com', 'a@b.com'],
        ])]);
    }
});

it('mengekstrak slug daripada plus-addressing', function () {
    expect($this->svc->slugFromAddress('scan.diwan+man@gmail.com'))->toBe('man')
        ->and($this->svc->slugFromAddress('scan.diwan+mam@gmail.com'))->toBe('mam')
        ->and($this->svc->intakeAddress($this->mam))->toBe('scan.diwan+mam@gmail.com')
        ->and($this->svc->slugFromAddress('scan.diwan+mam@evil.test'))->toBeNull()
        ->and($this->svc->slugFromAddress('penipu+mam@gmail.com'))->toBeNull()
        ->and($this->svc->slugFromAddress('biasa@gmail.com'))->toBeNull();
});

it('e-mel ke +man → rekod masuk peti MAN bukan MAM (§18.36)', function () {
    $result = $this->svc->ingestMessage(
        ['scan.diwan+man@gmail.com'],
        'pengirim@luar.com',
        'SPDM Surat MAIS 2026',
        'MID-1',
        [['content' => 'dokumen-pdf', 'filename' => 'surat.pdf', 'mime' => 'application/pdf']],
    );

    expect($result['status'])->toBe('ok')
        ->and(Record::forMosque($this->man)->count())->toBe(1)
        ->and(Record::forMosque($this->mam)->count())->toBe(0);
});

it('slug tidak dikenali → tiada rekod', function () {
    $result = $this->svc->ingestMessage(
        ['scan.diwan+tiada@gmail.com'], 'a@b.com', 'SPDM x', 'MID-2',
        [['content' => 'x', 'filename' => 'x.pdf', 'mime' => 'application/pdf']],
    );

    expect($result['status'])->toBe('unknown_or_inactive')
        ->and(Record::query()->count())->toBe(0);
});

it('lampiran duplikat skop-masjid diskip (§11.3)', function () {
    $attachment = [['content' => 'kandungan-sama', 'filename' => 'a.pdf', 'mime' => 'application/pdf']];

    $this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'a@b.com', 'SPDM x', 'MID-3', $attachment);
    $second = $this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'a@b.com', 'SPDM x', 'MID-4', $attachment);

    expect(Record::forMosque($this->mam)->count())->toBe(1)
        ->and($second['skipped_duplicate'])->toBe(1);
});

it('lampiran MIME tidak dibenarkan ditapis (§15.7)', function () {
    $this->svc->ingestMessage(
        ['scan.diwan+mam@gmail.com'], 'a@b.com', 'SPDM x', 'MID-5',
        [['content' => 'x', 'filename' => 'jahat.exe', 'mime' => 'application/octet-stream']],
    );

    expect(Record::query()->count())->toBe(0);
});

it('menolak pengirim, kata kunci atau tenant yang tidak dibenarkan', function () {
    $attachment = [['content' => 'dokumen', 'filename' => 'a.pdf', 'mime' => 'application/pdf']];

    expect($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'penipu@evil.test', 'SPDM surat', 'M1', $attachment)['status'])
        ->toBe('sender_not_allowed')
        ->and($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'a@b.com', 'Surat biasa', 'M2', $attachment)['status'])
        ->toBe('keyword_missing');

    $this->mam->update(['settings' => array_merge($this->mam->settings, ['mail_intake_enabled' => false])]);
    expect($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'a@b.com', 'SPDM surat', 'M3', $attachment)['status'])
        ->toBe('disabled')
        ->and(Record::query()->count())->toBe(0);
});

it('menerima kata kunci dalam isi e-mel dan kekal berskop tenant penerima', function () {
    $result = $this->svc->ingestMessage(
        ['scan.diwan+man@gmail.com'],
        'a@b.com',
        'Surat pengimbas',
        'MID-BODY',
        [['content' => 'dokumen-man', 'filename' => 'surat.pdf', 'mime' => 'application/pdf']],
        'Sila proses melalui SPDM',
    );

    expect($result['status'])->toBe('ok')
        ->and(Record::forMosque($this->man)->count())->toBe(1)
        ->and(Record::forMosque($this->mam)->count())->toBe(0);
});

it('allowlist pengirim satu tenant tidak terpakai kepada tenant lain', function () {
    $this->man->update(['settings' => array_merge($this->man->settings, [
        'mail_intake_senders' => ['khusus-man@example.test'],
    ])]);

    $result = $this->svc->ingestMessage(
        ['scan.diwan+man@gmail.com'],
        'a@b.com',
        'SPDM cubaan silang tenant',
        'MID-CROSS-SENDER',
        [['content' => 'dokumen', 'filename' => 'surat.pdf', 'mime' => 'application/pdf']],
    );

    expect($result['status'])->toBe('sender_not_allowed')
        ->and(Record::query()->count())->toBe(0);
});
