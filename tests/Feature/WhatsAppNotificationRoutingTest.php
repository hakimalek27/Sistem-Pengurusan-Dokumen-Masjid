<?php

use App\Jobs\SendWhatsAppJob;
use App\Models\WhatsAppIntegration;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\MembershipService;
use App\Services\WhatsAppRecipientResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

function tenantWhatsAppTestNotification(int $mosqueId): Notification
{
    return new class($mosqueId) extends Notification
    {
        public function __construct(public int $mosqueId) {}

        public function toWhatsApp(object $notifiable): array
        {
            return ['mosque_id' => $this->mosqueId, 'message' => 'Ujian tenant'];
        }
    };
}

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    $this->user = makeMember($this->mam, 'ajk', 'ahli@dua-tenant.test', [
        'phone_wa' => '60100000000',
        'notify_whatsapp' => false,
    ]);
    $this->man->users()->attach($this->user->id, ['role' => 'ajk', 'joined_at' => now()]);
    $this->mam->users()->updateExistingPivot($this->user->id, [
        'phone_wa' => '60111111111',
        'notify_whatsapp' => true,
    ]);
    $this->man->users()->updateExistingPivot($this->user->id, [
        'phone_wa' => '60122222222',
        'notify_whatsapp' => true,
    ]);
});

it('menyelesaikan nombor berbeza bagi pengguna sama mengikut tenant', function () {
    $resolver = app(WhatsAppRecipientResolver::class);

    expect($resolver->resolve($this->user, $this->mam->id))->toBe('60111111111')
        ->and($resolver->resolve($this->user, $this->man->id))->toBe('60122222222')
        ->and($resolver->resolve($this->user, null))->toBeNull();
});

it('tetapan mati atau nombor kosong pada satu tenant tidak fallback ke nombor global atau tenant lain', function () {
    $resolver = app(WhatsAppRecipientResolver::class);
    $this->mam->users()->updateExistingPivot($this->user->id, [
        'phone_wa' => null,
        'notify_whatsapp' => true,
    ]);
    $this->man->users()->updateExistingPivot($this->user->id, ['notify_whatsapp' => false]);

    expect($resolver->resolve($this->user->fresh(), $this->mam->id))->toBeNull()
        ->and($resolver->resolve($this->user->fresh(), $this->man->id))->toBeNull();
});

it('menghantar job hanya menggunakan sesi dan nombor tenant tepat', function () {
    Queue::fake();
    WhatsAppIntegration::query()->create([
        'mosque_id' => $this->mam->id,
        'external_id' => 'test:mam',
        'gateway_tenant_id' => 'gateway:mam',
        'api_key' => 'sk_'.str_repeat('a', 40),
        'enabled' => true,
        'status' => 'connected',
        'session_id' => 'sess_mam',
    ]);
    WhatsAppIntegration::query()->create([
        'mosque_id' => $this->man->id,
        'external_id' => 'test:man',
        'gateway_tenant_id' => 'gateway:man',
        'api_key' => 'sk_'.str_repeat('b', 40),
        'enabled' => true,
        'status' => 'connected',
        'session_id' => 'sess_man',
    ]);

    app(WhatsAppChannel::class)->send($this->user, tenantWhatsAppTestNotification($this->mam->id));

    Queue::assertPushed(SendWhatsAppJob::class, 1);
    Queue::assertPushed(SendWhatsAppJob::class, fn (SendWhatsAppJob $job) => $job->mosqueId === $this->mam->id
        && $job->session === 'sess_mam'
        && $job->to === '60111111111');
});

it('integrasi tenant yang mati tidak boleh menggunakan sesi tenant lain', function () {
    Queue::fake();
    WhatsAppIntegration::query()->create([
        'mosque_id' => $this->man->id,
        'external_id' => 'test:man',
        'gateway_tenant_id' => 'gateway:man',
        'api_key' => 'sk_'.str_repeat('b', 40),
        'enabled' => true,
        'status' => 'connected',
        'session_id' => 'sess_man',
    ]);

    app(WhatsAppChannel::class)->send($this->user, tenantWhatsAppTestNotification($this->mam->id));

    Queue::assertNothingPushed();
    $this->assertDatabaseHas('notification_logs', [
        'mosque_id' => $this->mam->id,
        'status' => 'failed',
    ]);
});

it('kemas kini routing hanya menyentuh pivot tenant yang dibenarkan', function () {
    $adminMam = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');

    app(MembershipService::class)->updateWhatsAppRouting(
        $this->mam,
        $this->user,
        '013-333 4444',
        false,
        $adminMam,
    );

    $mamPivot = $this->mam->users()->whereKey($this->user->id)->first()->pivot;
    $manPivot = $this->man->users()->whereKey($this->user->id)->first()->pivot;
    expect($mamPivot->phone_wa)->toBe('60133334444')
        ->and((bool) $mamPivot->notify_whatsapp)->toBeFalse()
        ->and($manPivot->phone_wa)->toBe('60122222222')
        ->and((bool) $manPivot->notify_whatsapp)->toBeTrue();
});

it('menolak pelakon tenant lain dan nombor pendua dalam tenant sama', function () {
    $adminMan = makeMember($this->man, 'admin_masjid', 'admin@man.test');
    $otherMam = makeMember($this->mam, 'ajk', 'lain@mam.test');
    $this->mam->users()->updateExistingPivot($otherMam->id, [
        'phone_wa' => '60199999999',
        'notify_whatsapp' => true,
    ]);

    expect(fn () => app(MembershipService::class)->updateWhatsAppRouting(
        $this->mam,
        $this->user,
        '60188888888',
        true,
        $adminMan,
    ))->toThrow(AuthorizationException::class);

    expect(fn () => app(MembershipService::class)->updateWhatsAppRouting(
        $this->mam,
        $this->user,
        '60199999999',
        true,
        makeMember($this->mam, 'admin_masjid', 'admin2@mam.test'),
    ))->toThrow(ValidationException::class);
});
