<?php

use App\Enums\ApprovalStatus;
use App\Notifications\ApprovalDecidedNotification;
use App\Notifications\ApprovalRequestedNotification;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Notification::fake();
    $this->svc = app(ApprovalService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->su = makeMember($this->mam, 'setiausaha', 's@mam.test');
    $this->pengerusi = makeMember($this->mam, 'pengerusi', 'p@mam.test');
    $this->record = makeRecord($this->mam, makeFile($this->mam, makeNode($this->mam, '100-4')));
});

it('mohon kelulusan → status menunggu + notifikasi approver', function () {
    $approval = $this->svc->request($this->record, $this->su, $this->pengerusi, 'Sila lulus');

    expect($approval->status)->toBe(ApprovalStatus::Menunggu)
        ->and($approval->approver_id)->toBe($this->pengerusi->id);

    Notification::assertSentTo($this->pengerusi, ApprovalRequestedNotification::class);
});

it('lulus dengan IP + timestamp direkod + audit + notifikasi pemohon (§18.22)', function () {
    $approval = $this->svc->request($this->record, $this->su, $this->pengerusi, null);

    $this->svc->decide($approval, $this->pengerusi, ApprovalStatus::Lulus, 'Diluluskan', '1.2.3.4');

    expect($approval->fresh()->status)->toBe(ApprovalStatus::Lulus)
        ->and($approval->fresh()->decided_at)->not->toBeNull()
        ->and($approval->fresh()->decision_ip)->toBe('1.2.3.4');

    Notification::assertSentTo($this->su, ApprovalDecidedNotification::class);
    expect(Activity::query()->where('description', 'kelulusan')->exists())->toBeTrue();
});

it('tolak → status tolak + nota keputusan', function () {
    $approval = $this->svc->request($this->record, $this->su, $this->pengerusi, null);

    $this->svc->decide($approval, $this->pengerusi, ApprovalStatus::Tolak, 'Perlu pembetulan', '5.6.7.8');

    expect($approval->fresh()->status)->toBe(ApprovalStatus::Tolak)
        ->and($approval->fresh()->decision_note)->toBe('Perlu pembetulan');
});
