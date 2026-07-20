<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    cache()->flush(); // reset kaunter throttle:60,1 laluan webhook (CI redis dikongsi)
    config()->set('diwan.telegram.webhook_secret', 'rahsia-webhook');
    config()->set('diwan.telegram.bot_token', 'bot-token-123');
    config()->set('diwan.telegram.bot_username', 'DiwanBot');
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
});

it('token cache sah menyambungkan telegram_chat_id DAN menghidupkan notify_telegram', function () {
    $user = User::query()->create(['name' => 'A', 'email' => 'a@x.test', 'password' => bcrypt('x'), 'is_active' => true]);
    Cache::put('telegram_connect:tok123', $user->id, now()->addMinutes(15));

    $this->postJson('/api/webhooks/telegram/rahsia-webhook', [
        'message' => ['text' => '/start tok123', 'chat' => ['id' => 987654321]],
    ])->assertOk();

    // notify_telegram MESTI true — tanpa ini via() SKIP Telegram walau "Bersambung".
    expect($user->fresh()->telegram_chat_id)->toBe('987654321')
        ->and($user->fresh()->notify_telegram)->toBeTrue()
        ->and(Cache::get('telegram_connect:tok123'))->toBeNull();
});

it('token 48-aksara sah-bentuk tetapi tamat tempoh → bot balas mesej tamat tempoh', function () {
    $expired = str_repeat('a', 48); // sah bentuk (Str::random(48)) tetapi tiada dlm cache

    $this->postJson('/api/webhooks/telegram/rahsia-webhook', [
        'message' => ['text' => '/start '.$expired, 'chat' => ['id' => 555]],
    ])->assertOk();

    Http::assertSent(fn ($req) => str_contains($req->url(), '/sendMessage')
        && str_contains((string) json_encode($req->data()), 'tamat tempoh'));
});

it('/start payload rawak (bukan token 48-aksara) → senyap, tiada balasan', function () {
    $this->postJson('/api/webhooks/telegram/rahsia-webhook', [
        'message' => ['text' => '/start hello', 'chat' => ['id' => 666]],
    ])->assertOk();

    Http::assertNotSent(fn ($req) => str_contains($req->url(), '/sendMessage'));
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
