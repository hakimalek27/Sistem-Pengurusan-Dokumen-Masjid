<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('diwan.telegram.webhook_secret', 'rahsia-webhook');
    config()->set('diwan.telegram.bot_token', 'bot-token-123');
    config()->set('diwan.telegram.bot_username', 'DiwanBot');
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
});

it('token cache sah menyambungkan telegram_chat_id', function () {
    $user = User::query()->create(['name' => 'A', 'email' => 'a@x.test', 'password' => bcrypt('x'), 'is_active' => true]);
    Cache::put('telegram_connect:tok123', $user->id, now()->addMinutes(15));

    $this->postJson('/api/webhooks/telegram/rahsia-webhook', [
        'message' => ['text' => '/start tok123', 'chat' => ['id' => 987654321]],
    ])->assertOk();

    expect($user->fresh()->telegram_chat_id)->toBe('987654321')
        ->and(Cache::get('telegram_connect:tok123'))->toBeNull();
});

it('token cache tidak wujud → tiada perubahan', function () {
    $user = User::query()->create(['name' => 'A', 'email' => 'a@x.test', 'password' => bcrypt('x'), 'is_active' => true]);

    $this->postJson('/api/webhooks/telegram/rahsia-webhook', [
        'message' => ['text' => '/start tokSalah', 'chat' => ['id' => 111]],
    ])->assertOk();

    expect($user->fresh()->telegram_chat_id)->toBeNull();
});

it('secret webhook salah → 403', function () {
    $this->postJson('/api/webhooks/telegram/secret-salah', [
        'message' => ['text' => '/start x', 'chat' => ['id' => 1]],
    ])->assertForbidden();
});

it('command set-webhook memanggil Telegram API', function () {
    $this->artisan('diwan:telegram-set-webhook')->assertSuccessful();

    Http::assertSent(fn ($req) => str_contains($req->url(), '/setWebhook'));
});

it('command set-webhook gagal jika token kosong', function () {
    config()->set('diwan.telegram.bot_token', '');

    $this->artisan('diwan:telegram-set-webhook')->assertFailed();
});
