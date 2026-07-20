<?php

use App\Jobs\FetchMailJob;
use App\Models\Record;
use App\Notifications\MailIntakeRejectedNotification;
use App\Services\MailIngestService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    cache()->flush(); // reset kaunter had kadar submission (CI redis dikongsi antara ujian)
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

it('FetchMailJob WithoutOverlapping mempunyai expiry (elak kunci tersekat kekal §11.3)', function () {
    // Punca sebenar e-mel intake tersekat 20 Jul: kunci job-level tanpa expiry
    // (expiresAfter=0) kekal selepas larian terbunuh → fetch-mail dilangkau selamanya.
    //
    // DIPINDA (20 Jul petang, peraturan #9): dahulu memaku nilai TEPAT 600.
    // Kunci ini disahkan tersangkut semula sebaik selepas deploy (container
    // recreate mid-run), menyekat intake 10 minit penuh setiap kali — jadi
    // nilai diturunkan kepada 120. Ujian kini mengunci NIAT (wujud + pendek)
    // supaya penalaan operasi tidak memerlukan pindaan ujian setiap kali.
    $mw = (new FetchMailJob)->middleware();

    expect($mw)->toHaveCount(1)
        ->and($mw[0])->toBeInstanceOf(WithoutOverlapping::class)
        ->and($mw[0]->expiresAfter)->toBeGreaterThan(0)
        ->and($mw[0]->expiresAfter)->toBeLessThanOrEqual(180);
});

it('mengekstrak slug daripada plus-addressing', function () {
    expect($this->svc->slugFromAddress('scan.diwan+man@gmail.com'))->toBe('man')
        ->and($this->svc->slugFromAddress('scan.diwan+mam@gmail.com'))->toBe('mam')
        ->and($this->svc->intakeAddress($this->mam))->toBe('scan.diwan+mam@gmail.com')
        ->and($this->svc->slugFromAddress('scan.diwan+mam@evil.test'))->toBeNull()
        ->and($this->svc->slugFromAddress('penipu+mam@gmail.com'))->toBeNull()
        ->and($this->svc->slugFromAddress('biasa@gmail.com'))->toBeNull();
});

it('mengutamakan alamat intake rasmi (scan@domain) berbanding IMAP username', function () {
    // Alias rasmi @bakwim.my bebas daripada log masuk peti mel sebenar (gmail).
    config()->set('diwan.mail_intake.address', 'scan@bakwim.my');

    expect($this->svc->intakeAddress($this->mam))->toBe('scan+mam@bakwim.my')
        ->and($this->svc->slugFromAddress('scan+man@bakwim.my'))->toBe('man')
        // Alias lama (IMAP username) tidak lagi dipadan bila alamat rasmi ditetapkan.
        ->and($this->svc->slugFromAddress('scan.diwan+mam@gmail.com'))->toBeNull()
        ->and($this->svc->slugFromAddress('scan+mam@evil.test'))->toBeNull();
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

it('menerima format tambahan (.txt, .doc) dan menolak .webp dengan rejected_format', function () {
    $result = $this->svc->ingestMessage(
        ['scan.diwan+mam@gmail.com'], 'a@b.com', 'SPDM pelbagai', 'MID-FMT',
        [
            ['content' => 'teks biasa', 'filename' => 'nota.txt', 'mime' => 'text/plain'],
            ['content' => 'dok word', 'filename' => 'surat.doc', 'mime' => 'application/msword'],
            ['content' => 'imej webp', 'filename' => 'gambar.webp', 'mime' => 'image/webp'],
        ],
    );

    expect($result['status'])->toBe('ok')
        ->and(Record::forMosque($this->mam)->count())->toBe(2)
        ->and($result['rejected_format'])->toContain('gambar.webp')
        ->and($result['rejected_format'])->not->toContain('nota.txt');
});

it('semua lampiran format tidak sah → status all_rejected', function () {
    $result = $this->svc->ingestMessage(
        ['scan.diwan+mam@gmail.com'], 'a@b.com', 'SPDM jahat', 'MID-ALLREJ',
        [['content' => 'x', 'filename' => 'jahat.exe', 'mime' => 'application/octet-stream']],
    );

    expect($result['status'])->toBe('all_rejected')
        ->and($result['rejected_format'])->toContain('jahat.exe')
        ->and(Record::query()->count())->toBe(0);
});

it('mod ketat (allow_public=false): pengirim bukan-allowlist ditolak sender_not_allowed', function () {
    config()->set('diwan.mail_intake.allow_public', false);
    $attachment = [['content' => 'dokumen', 'filename' => 'a.pdf', 'mime' => 'application/pdf']];

    expect($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'penipu@evil.test', 'SPDM surat', 'M1', $attachment)['status'])
        ->toBe('sender_not_allowed')
        ->and(Record::query()->count())->toBe(0);
});

it('menolak kata kunci hilang & intake dimatikan', function () {
    $attachment = [['content' => 'dokumen', 'filename' => 'a.pdf', 'mime' => 'application/pdf']];

    // a@b.com dibenarkan tetapi subjek tiada kata kunci 'spdm' → keyword_missing.
    expect($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'a@b.com', 'Surat biasa', 'M2', $attachment)['status'])
        ->toBe('keyword_missing');

    $this->mam->update(['settings' => array_merge($this->mam->settings, ['mail_intake_enabled' => false])]);
    expect($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'a@b.com', 'SPDM surat', 'M3', $attachment)['status'])
        ->toBe('disabled')
        ->and(Record::query()->count())->toBe(0);
});

it('submission awam (allow_public lalai): pengirim luar + kata kunci → diterima', function () {
    // Punca sebenar bug pemilik BUKAN allowlist (kunci mutex), tetapi pemilik mahu
    // mana-mana pengirim boleh hantar terus. Lalai allow_public=true.
    $result = $this->svc->ingestMessage(
        ['scan.diwan+mam@gmail.com'], 'orang.luar@example.test', 'SPDM sumbangan', 'MID-PUB',
        [['content' => 'dokumen awam', 'filename' => 'surat.pdf', 'mime' => 'application/pdf']],
    );

    expect($result['status'])->toBe('ok')
        ->and(Record::forMosque($this->mam)->count())->toBe(1);
});

it('had kadar submission awam menyekat selepas cap dicapai', function () {
    config()->set('diwan.mail_intake.submission_cap', 2);
    $this->mam->update(['settings' => array_merge($this->mam->settings, ['mail_intake_keyword' => ''])]);
    $att = fn ($n) => [['content' => "isi-{$n}", 'filename' => "s{$n}.pdf", 'mime' => 'application/pdf']];

    expect($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'flood@example.test', 'X', 'F1', $att(1))['status'])->toBe('ok')
        ->and($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'flood@example.test', 'X', 'F2', $att(2))['status'])->toBe('ok')
        ->and($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'flood@example.test', 'X', 'F3', $att(3))['status'])->toBe('rate_limited')
        ->and(Record::forMosque($this->mam)->count())->toBe(2);
});

it('pengirim dalam allowlist mendapat had lebih tinggi daripada awam', function () {
    config()->set('diwan.mail_intake.submission_cap', 1);   // awam: 1
    config()->set('diwan.mail_intake.allowlist_cap', 5);    // dipercayai: 5
    $this->mam->update(['settings' => array_merge($this->mam->settings, ['mail_intake_keyword' => ''])]);
    $att = fn ($n) => [['content' => "isi-{$n}", 'filename' => "s{$n}.pdf", 'mime' => 'application/pdf']];

    // a@b.com dalam allowlist → melepasi had awam (1).
    expect($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'a@b.com', 'X', 'A1', $att(1))['status'])->toBe('ok')
        ->and($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'a@b.com', 'X', 'A2', $att(2))['status'])->toBe('ok')
        // pengirim awam disekat pada dokumen ke-2.
        ->and($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'awam@example.test', 'X', 'P1', $att(3))['status'])->toBe('ok')
        ->and($this->svc->ingestMessage(['scan.diwan+mam@gmail.com'], 'awam@example.test', 'X', 'P2', $att(4))['status'])->toBe('rate_limited');
});

it('lampiran melebihi had saiz → rejected_oversize tanpa exception (bukan mesej racun)', function () {
    config()->set('diwan.max_upload_mb', 1);
    $this->mam->update(['settings' => array_merge($this->mam->settings, ['mail_intake_keyword' => ''])]);

    $result = $this->svc->ingestMessage(
        ['scan.diwan+mam@gmail.com'], 'a@b.com', 'Besar', 'MID-BIG',
        [['content' => str_repeat('x', 2 * 1024 * 1024), 'filename' => 'besar.pdf', 'mime' => 'application/pdf']],
    );

    expect($result['status'])->toBe('all_rejected')
        ->and($result['rejected_oversize'])->toContain('besar.pdf')
        ->and(Record::query()->count())->toBe(0);
});

it('campuran lampiran sah + oversize → rekod sah dicipta, oversize direkod', function () {
    config()->set('diwan.max_upload_mb', 1);
    $this->mam->update(['settings' => array_merge($this->mam->settings, ['mail_intake_keyword' => ''])]);

    $result = $this->svc->ingestMessage(
        ['scan.diwan+mam@gmail.com'], 'a@b.com', 'Campur', 'MID-MIX',
        [
            ['content' => 'kecil', 'filename' => 'ok.pdf', 'mime' => 'application/pdf'],
            ['content' => str_repeat('x', 2 * 1024 * 1024), 'filename' => 'besar.pdf', 'mime' => 'application/pdf'],
        ],
    );

    expect($result['status'])->toBe('ok')
        ->and($result['rejected_oversize'])->toContain('besar.pdf')
        ->and(Record::forMosque($this->mam)->count())->toBe(1);
});

it('recordOutcome sebab oversize memaklum admin dan menyimpan diagnostik oversize', function () {
    Notification::fake();
    $admin = makeMember($this->mam, 'admin_masjid', 'admin-ov@mam.test');

    $result = ['status' => 'all_rejected', 'mosque' => $this->mam->fresh(), 'rejected_format' => [], 'rejected_oversize' => ['besar.pdf']];
    $this->svc->recordOutcome($result, 'a@b.com', 'Besar');

    Notification::assertSentTo($admin, MailIntakeRejectedNotification::class);
    expect($this->mam->fresh()->settings['mail_intake_last']['status'])->toBe('oversize');
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

it('kata kunci kosong → terima semua e-mel daripada pengirim dibenarkan', function () {
    $this->mam->update(['settings' => array_merge($this->mam->settings, ['mail_intake_keyword' => ''])]);

    $result = $this->svc->ingestMessage(
        ['scan.diwan+mam@gmail.com'], 'a@b.com', 'Surat tanpa kata kunci', 'MID-NOKW',
        [['content' => 'dokumen', 'filename' => 'surat.pdf', 'mime' => 'application/pdf']],
    );

    expect($result['status'])->toBe('ok')
        ->and(Record::forMosque($this->mam)->count())->toBe(1);
});

it('isIntakeAddress mengenal pasti alamat intake sistem (bukan pengirim)', function () {
    config()->set('diwan.mail_intake.address', 'scan@bakwim.my');

    expect($this->svc->isIntakeAddress('scan+mam@bakwim.my'))->toBeTrue()
        ->and($this->svc->isIntakeAddress('scan@bakwim.my'))->toBeTrue()
        ->and($this->svc->isIntakeAddress('admin@masjid.org'))->toBeFalse();
});

it('recordOutcome memaklum admin sekali (throttle) dan menyimpan diagnostik', function () {
    Notification::fake();
    $admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');

    $result = ['status' => 'sender_not_allowed', 'mosque' => $this->mam->fresh(), 'rejected_format' => []];
    $this->svc->recordOutcome($result, 'penipu@evil.test', 'Cubaan');
    $this->svc->recordOutcome($result, 'penipu@evil.test', 'Cubaan lagi'); // dithrottle

    Notification::assertSentToTimes($admin, MailIntakeRejectedNotification::class, 1);
    expect($this->mam->fresh()->settings['mail_intake_last']['status'])->toBe('sender_not_allowed');
});

it('recordOutcome tidak memaklum bila tiada masjid (no_slug)', function () {
    Notification::fake();

    $this->svc->recordOutcome(['status' => 'no_slug'], 'x@y.test', 'X');

    Notification::assertNothingSent();
});

it('mod ketat: allowlist pengirim satu tenant tidak terpakai kepada tenant lain', function () {
    config()->set('diwan.mail_intake.allow_public', false);
    $this->man->update(['settings' => array_merge($this->man->settings, [
        'mail_intake_senders' => ['khusus-man@example.test'],
    ])]);

    // a@b.com dibenarkan di MAM tetapi BUKAN MAN → ditolak di MAN (mod ketat).
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
