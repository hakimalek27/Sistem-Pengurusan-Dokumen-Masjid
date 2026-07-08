<?php

use App\Enums\MinitPriority;
use App\Enums\MinitStatus;
use App\Notifications\MinitReminderNotification;
use App\Notifications\MinitRoutedNotification;
use App\Services\MinitService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->svc = app(MinitService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->from = makeMember($this->mam, 'kerani', 'k@mam.test');
    $this->pengerusi = makeMember($this->mam, 'pengerusi', 'p@mam.test');
    $this->nazir = makeMember($this->mam, 'nazir', 'n@mam.test');
    $this->su = makeMember($this->mam, 'setiausaha', 's@mam.test');
    $this->record = makeRecord($this->mam, makeFile($this->mam, makeNode($this->mam, '100-4')));
});

it('cipta minit + recipients + due ikut SLA + notifikasi', function () {
    $minit = $this->svc->create($this->record, $this->from, [$this->pengerusi->id], [$this->su->id], 'Sila tindak', MinitPriority::Segera);

    expect($minit->recipients()->count())->toBe(2)
        ->and($minit->recipients()->where('jenis', 'tindakan')->count())->toBe(1)
        ->and($minit->due_at->toDateString())->toBe(now()->addDays(3)->toDateString()); // segera = 3 hari

    Notification::assertSentTo($this->pengerusi, MinitRoutedNotification::class);
    Notification::assertSentTo($this->su, MinitRoutedNotification::class);
});

it('tanda selesai: minit selesai hanya bila SEMUA penerima tindakan selesai', function () {
    $minit = $this->svc->create($this->record, $this->from, [$this->pengerusi->id, $this->nazir->id], [], 'X', MinitPriority::Biasa);

    $this->svc->markDone($minit, $this->pengerusi);
    expect($minit->fresh()->status)->toBe(MinitStatus::Terbuka); // nazir belum

    $this->svc->markDone($minit, $this->nazir);
    expect($minit->fresh()->status)->toBe(MinitStatus::Selesai)
        ->and($minit->fresh()->completed_at)->not->toBeNull();
});

it('balas & edarkan mencipta minit anak dalam bebenang', function () {
    $parent = $this->svc->create($this->record, $this->from, [$this->pengerusi->id], [], 'Untuk perhatian', MinitPriority::Biasa);

    $child = $this->svc->replyAndRoute($parent, $this->pengerusi, [$this->su->id], [], 'Sila sediakan jawapan', MinitPriority::Biasa);

    expect($child->parent_id)->toBe($parent->id)
        ->and($parent->children()->count())->toBe(1);
});

it('minit due semalam → peringatan LEWAT (§18.21)', function () {
    $minit = $this->svc->create($this->record, $this->from, [$this->pengerusi->id], [], 'X', MinitPriority::Biasa);
    $minit->update(['due_at' => now()->subDay()->toDateString()]);

    $this->artisan('diwan:send-minit-reminders')->assertSuccessful();

    Notification::assertSentTo($this->pengerusi, MinitReminderNotification::class, fn ($n) => $n->late === true);
});

it('minit due esok → peringatan biasa', function () {
    $minit = $this->svc->create($this->record, $this->from, [$this->pengerusi->id], [], 'X', MinitPriority::Biasa);
    $minit->update(['due_at' => now()->addDay()->toDateString()]);

    $this->artisan('diwan:send-minit-reminders')->assertSuccessful();

    Notification::assertSentTo($this->pengerusi, MinitReminderNotification::class, fn ($n) => $n->late === false);
});
