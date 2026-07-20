<?php

use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\TestNotification;
use GuzzleHttp\Psr7\Response;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\Telegram;

/*
 * §14 / §11.2 — Wrapper App\Notifications\Channels\TelegramChannel: log
 * NotificationLog (sent/failed) + telan ralat supaya kegagalan Telegram TIDAK
 * memecahkan penghantaran saluran lain. Klien Telegram vendor di-mock supaya
 * tiada HTTP sebenar (Http::fake tidak memintas Guzzle dalaman pakej).
 */

function telegramUser(): User
{
    return User::query()->create([
        'name' => 'TG', 'email' => 'tg@x.test', 'password' => bcrypt('x'), 'is_active' => true,
        'telegram_chat_id' => '424242', 'notify_telegram' => true,
        'notify_email' => false, 'notify_whatsapp' => false, // asingkan → hanya saluran Telegram
    ]);
}

it('TestNotification mempunyai kaedah toTelegram', function () {
    expect(method_exists(new TestNotification, 'toTelegram'))->toBeTrue();
});

it('hantaran Telegram berjaya menulis NotificationLog sent', function () {
    $telegram = Mockery::mock(Telegram::class);
    $telegram->shouldReceive('setToken')->andReturnSelf();
    $telegram->shouldReceive('sendMessage')->once()
        ->andReturn(new Response(200, [], json_encode(['ok' => true, 'result' => ['message_id' => 7]])));
    app()->instance(Telegram::class, $telegram);

    telegramUser()->notify(new TestNotification);

    $log = NotificationLog::query()->where('channel', 'telegram')->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('sent')
        ->and($log->to)->toBe('424242')
        ->and($log->notification_type)->toBe('TestNotification');
});

it('kegagalan Telegram TIDAK meletup dan menulis NotificationLog failed', function () {
    $telegram = Mockery::mock(Telegram::class);
    $telegram->shouldReceive('setToken')->andReturnSelf();
    $telegram->shouldReceive('sendMessage')->andThrow(new CouldNotSendNotification('Telegram API tolak'));
    app()->instance(Telegram::class, $telegram);

    // Tidak melontar exception (aliran tidak pecah) walau Telegram gagal.
    telegramUser()->notify(new TestNotification);

    $log = NotificationLog::query()->where('channel', 'telegram')->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('failed')
        ->and($log->error)->toContain('Telegram API tolak');
});
