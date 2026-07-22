<?php

use App\Enums\MinitPriority;
use App\Models\GuidancePreference;
use App\Models\HelpEvent;
use App\Models\Minit;
use App\Models\MinitRecipient;
use App\Notifications\GuidanceDigestNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->mosque = makeMosque('MAM', 'mam');
});

it('menghantar maksimum satu digest sehari dan menghormati quiet hours', function () {
    $admin = makeMember($this->mosque, 'admin_masjid');
    $preference = GuidancePreference::query()->create([
        'user_id' => $admin->id,
        'mosque_id' => $this->mosque->id,
        'context_key' => 'tenant:'.$this->mosque->id,
        'digest_email' => true,
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '06:00',
    ]);

    $this->travelTo(today()->setTime(22, 30));
    $this->artisan('diwan:send-guidance-digests')->assertSuccessful();
    Notification::assertNothingSent();

    $this->travelTo(today()->setTime(8, 0));
    $this->artisan('diwan:send-guidance-digests')->assertSuccessful();
    $this->artisan('diwan:send-guidance-digests')->assertSuccessful();

    Notification::assertSentToTimes($admin, GuidanceDigestNotification::class, 1);
    expect(HelpEvent::query()->where('event', 'digest_sent')->count())->toBe(1);
    $this->travelBack();
});

it('tidak mengulang peringatan minit dalam digest bantuan', function () {
    $ajk = makeMember($this->mosque, 'ajk');
    GuidancePreference::query()->create([
        'user_id' => $ajk->id,
        'mosque_id' => $this->mosque->id,
        'context_key' => 'tenant:'.$this->mosque->id,
        'digest_email' => true,
    ]);
    $file = makeFile($this->mosque, makeNode($this->mosque, '100-1'));
    $record = makeRecord($this->mosque, $file);
    $minit = Minit::query()->create([
        'mosque_id' => $this->mosque->id,
        'record_id' => $record->id,
        'from_user_id' => makeMember($this->mosque, 'admin_masjid')->id,
        'body' => 'Tindakan yang sudah mempunyai peringatan minit',
        'priority' => MinitPriority::Biasa,
        'due_at' => today()->subDay(),
        'status' => 'terbuka',
    ]);
    MinitRecipient::query()->create([
        'minit_id' => $minit->id,
        'user_id' => $ajk->id,
        'jenis' => 'tindakan',
        'status' => 'belum',
    ]);

    $this->artisan('diwan:send-guidance-digests')->assertSuccessful();

    Notification::assertNothingSent();
    expect(HelpEvent::query()->where('event', 'digest_sent')->count())->toBe(0);
});
