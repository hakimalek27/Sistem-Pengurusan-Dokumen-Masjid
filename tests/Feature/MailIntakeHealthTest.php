<?php

use App\Jobs\FetchMailJob;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Notifications\ConnectionAlertNotification;
use App\Support\MailIntakeHealth;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;

/*
 * §11.3 — Kesihatan intake e-mel.
 *
 * Insiden 19-20 Jul: mutex jadual tersangkut (TTL 24 jam) → diwan:fetch-mail
 * TIDAK dijalankan langsung selama ~14 jam. Kerana `imap_failure_streak` hanya
 * bertambah apabila job BERJALAN, streak kekal 0 → semua penunjuk memaparkan
 * "OK" hijau dan sifar alert dihantar sementara e-mel pemilik hilang senyap.
 * Ujian di bawah mengunci pengesanan keadaan TERSEKAT itu.
 */

beforeEach(function () {
    cache()->flush(); // CI kongsi Redis antara ujian
    config()->set('diwan.imap_enabled', true);
    Notification::fake();
});

it('menandakan intake TERSEKAT bila tiada larian berjaya melebihi ambang', function () {
    PlatformSetting::put('imap_last_success_at', now()->subMinutes(MailIntakeHealth::STALE_AFTER_MINUTES + 5)->toIso8601String());

    $health = MailIntakeHealth::evaluate();

    expect($health['state'])->toBe(MailIntakeHealth::STATE_STALLED)
        ->and($health['label'])->toBe('Tersekat')
        ->and($health['color'])->toBe('danger')
        ->and(MailIntakeHealth::isUnhealthy($health['state']))->toBeTrue();
});

it('menandakan intake OK bila detak jantung segar', function () {
    PlatformSetting::put('imap_last_success_at', now()->subMinutes(2)->toIso8601String());

    $health = MailIntakeHealth::evaluate();

    expect($health['state'])->toBe(MailIntakeHealth::STATE_OK)
        ->and($health['color'])->toBe('success')
        ->and(MailIntakeHealth::isUnhealthy($health['state']))->toBeFalse();
});

it('tidak melaporkan OK bila streak 0 tetapi tiada detak jantung langsung', function () {
    // Ini keadaan TEPAT semasa insiden: job tak pernah jalan, streak 0.
    // Tingkah laku lama memaparkan "OK" hijau — regresi yang mesti kekal ditutup.
    PlatformSetting::put('imap_failure_streak', 0);

    $health = MailIntakeHealth::evaluate();

    expect($health['state'])->not->toBe(MailIntakeHealth::STATE_OK)
        ->and($health['label'])->not->toBe('OK');
});

it('mengutamakan keadaan GAGAL bila sambungan IMAP ditolak', function () {
    PlatformSetting::put('imap_last_success_at', now()->subMinutes(2)->toIso8601String());
    PlatformSetting::put('imap_failure_streak', 3);

    expect(MailIntakeHealth::evaluate()['state'])->toBe(MailIntakeHealth::STATE_FAILING);
});

it('sentiasa Dimatikan bila IMAP_ENABLED=false (tiada alert palsu pada dev)', function () {
    config()->set('diwan.imap_enabled', false);
    PlatformSetting::put('imap_last_success_at', now()->subDay()->toIso8601String());

    $health = MailIntakeHealth::evaluate();

    expect($health['state'])->toBe(MailIntakeHealth::STATE_DISABLED)
        ->and(MailIntakeHealth::isUnhealthy($health['state']))->toBeFalse();
});

it('FetchMailJob merekod detak jantung imap_last_success_at', function () {
    // Sahkan job MENULIS cap masa selepas pusingan berjaya (bukan hanya reset streak).
    $job = new FetchMailJob;
    $method = (new ReflectionClass($job))->getMethod('recordImapSuccess');
    $method->setAccessible(true);

    expect(PlatformSetting::get('imap_last_success_at'))->toBeNull();

    $method->invoke($job);

    expect(PlatformSetting::get('imap_last_success_at'))->not->toBeNull();
    expect(MailIntakeHealth::lastSuccessAt())->not->toBeNull();
});

it('check-wa-sessions menghantar alert superadmin bila intake tersekat', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true, 'is_active' => true]);
    PlatformSetting::put('imap_last_success_at', now()->subHours(2)->toIso8601String());

    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    Notification::assertSentTo($superadmin, ConnectionAlertNotification::class);
    expect((bool) PlatformSetting::get('imap_alerted'))->toBeTrue();
});

it('tidak menghantar alert bila intake sihat', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true, 'is_active' => true]);
    PlatformSetting::put('imap_last_success_at', now()->subMinutes(1)->toIso8601String());

    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    Notification::assertNotSentTo($superadmin, ConnectionAlertNotification::class);
});

it('tidak menghantar alert bila IMAP dimatikan walaupun detak jantung basi', function () {
    config()->set('diwan.imap_enabled', false);
    $superadmin = User::factory()->create(['is_superadmin' => true, 'is_active' => true]);
    PlatformSetting::put('imap_last_success_at', now()->subDay()->toIso8601String());

    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    Notification::assertNotSentTo($superadmin, ConnectionAlertNotification::class);
});

it('alert tersekat dithrottle — tidak berulang setiap 10 minit', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true, 'is_active' => true]);
    PlatformSetting::put('imap_last_success_at', now()->subHours(2)->toIso8601String());

    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();
    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    Notification::assertSentToTimes($superadmin, ConnectionAlertNotification::class, 1);
});

it('memaklumkan pulih selepas intake tersekat kembali sihat', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true, 'is_active' => true]);
    PlatformSetting::put('imap_last_success_at', now()->subHours(2)->toIso8601String());
    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    PlatformSetting::put('imap_last_success_at', now()->toIso8601String());
    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    Notification::assertSentToTimes($superadmin, ConnectionAlertNotification::class, 2);
    expect((bool) PlatformSetting::get('imap_alerted'))->toBeFalse();
});

it('setiap mutex jadual mempunyai tempoh luput eksplisit (punca insiden 20 Jul)', function () {
    // withoutOverlapping() TANPA argumen = 24 jam. Satu larian terbunuh →
    // tugasan dilangkau senyap sehari penuh. Kunci corak ini supaya tidak berulang.
    $schedule = app(Schedule::class);

    foreach ($schedule->events() as $event) {
        if ($event->withoutOverlapping) {
            expect($event->expiresAt)->toBeLessThanOrEqual(
                60,
                "Tugasan [{$event->command}] guna mutex {$event->expiresAt} minit — terlalu panjang; beri nilai eksplisit hampir kadar lariannya."
            );
        }
    }
});
