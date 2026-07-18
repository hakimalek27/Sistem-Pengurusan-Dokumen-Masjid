<?php

use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('putEncrypted/getEncrypted roundtrip; nilai bukan-cipher atau kosong → null', function () {
    PlatformSetting::putEncrypted('telegram_bot_token', '123:ABC-secret');
    expect(PlatformSetting::getEncrypted('telegram_bot_token'))->toBe('123:ABC-secret');

    // Nilai plaintext lama (bukan cipher) → null, bukan ralat.
    PlatformSetting::put('telegram_bot_token', 'plaintext');
    expect(PlatformSetting::getEncrypted('telegram_bot_token'))->toBeNull();

    // Kosong → null.
    PlatformSetting::putEncrypted('telegram_bot_token', '');
    expect(PlatformSetting::getEncrypted('telegram_bot_token'))->toBeNull();
});

it('hydrateRuntimeConfig menyuntik nilai DB ke atas config (DB-dahulu)', function () {
    config()->set('diwan.telegram.bot_token', 'env-token');
    config()->set('diwan.telegram.bot_username', null);
    Cache::forget('platform:telegram');

    PlatformSetting::putEncrypted('telegram_bot_token', 'db-token');
    PlatformSetting::put('telegram_bot_username', 'DiwanBot');
    PlatformSetting::putEncrypted('telegram_webhook_secret', 'db-secret');

    TelegramService::hydrateRuntimeConfig(false);

    expect(config('diwan.telegram.bot_token'))->toBe('db-token')
        ->and(config('services.telegram-bot-api.token'))->toBe('db-token')
        ->and(config('diwan.telegram.bot_username'))->toBe('DiwanBot')
        ->and(config('diwan.telegram.webhook_secret'))->toBe('db-secret');
});

it('hydrateRuntimeConfig mengekalkan env bila DB kosong (fallback)', function () {
    config()->set('diwan.telegram.bot_token', 'env-token');
    Cache::forget('platform:telegram');

    TelegramService::hydrateRuntimeConfig(false);

    expect(config('diwan.telegram.bot_token'))->toBe('env-token');
});

it('setWebhook memanggil Telegram API dan menyimpan status', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    config()->set('diwan.telegram.bot_token', 'T');
    config()->set('diwan.telegram.webhook_secret', 'S');

    $result = app(TelegramService::class)->setWebhook();

    expect($result['ok'])->toBeTrue()
        ->and(PlatformSetting::get('telegram_webhook_status')['ok'])->toBeTrue();
    Http::assertSent(fn ($req) => str_contains($req->url(), '/setWebhook'));
});

it('setWebhook gagal bila token/rahsia kosong', function () {
    config()->set('diwan.telegram.bot_token', '');
    config()->set('diwan.telegram.webhook_secret', '');

    expect(app(TelegramService::class)->setWebhook()['ok'])->toBeFalse();
});

it('webhook controller menerima rahsia yang disuntik dari DB', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    PlatformSetting::putEncrypted('telegram_webhook_secret', 'db-secret-xyz');
    Cache::forget('platform:telegram');
    TelegramService::hydrateRuntimeConfig(false);

    $user = User::query()->create(['name' => 'A', 'email' => 'a@x.test', 'password' => bcrypt('x'), 'is_active' => true]);
    Cache::put('telegram_connect:tokDB', $user->id, now()->addMinutes(15));

    $this->postJson('/api/webhooks/telegram/db-secret-xyz', [
        'message' => ['text' => '/start tokDB', 'chat' => ['id' => 555000111]],
    ])->assertOk();

    expect($user->fresh()->telegram_chat_id)->toBe('555000111');
});
