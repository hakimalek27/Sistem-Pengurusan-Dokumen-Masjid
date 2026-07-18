<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * §11.2 — Perkhidmatan Telegram Bot API. Konfigurasi (token/username/rahsia)
 * boleh datang daripada UI superadmin (platform_settings, tersulit) ATAU env;
 * runtime config disuntik oleh AppServiceProvider::boot().
 */
class TelegramService
{
    /**
     * Suntik tetapan Telegram dari DB (UI superadmin) ke runtime config.
     * DB-dahulu, fallback env. Try/catch: DB mungkin belum wujud semasa
     * package:discover / migrate pertama. $useCache=false untuk baca segar
     * (cth sejurus selepas simpan tetapan sebelum Set Webhook).
     */
    public static function hydrateRuntimeConfig(bool $useCache = true): void
    {
        try {
            $read = fn () => [
                'bot_token' => PlatformSetting::getEncrypted('telegram_bot_token'),
                'bot_username' => PlatformSetting::get('telegram_bot_username'),
                'webhook_secret' => PlatformSetting::getEncrypted('telegram_webhook_secret'),
            ];
            $settings = $useCache
                ? Cache::remember('platform:telegram', 300, $read)
                : $read();
        } catch (\Throwable $e) {
            return;
        }

        if (! empty($settings['bot_token'])) {
            config()->set('diwan.telegram.bot_token', $settings['bot_token']);
            config()->set('services.telegram-bot-api.token', $settings['bot_token']);
        }
        if (! empty($settings['bot_username'])) {
            config()->set('diwan.telegram.bot_username', $settings['bot_username']);
        }
        if (! empty($settings['webhook_secret'])) {
            config()->set('diwan.telegram.webhook_secret', $settings['webhook_secret']);
        }
    }

    protected function token(): string
    {
        return (string) config('diwan.telegram.bot_token');
    }

    protected function secret(): string
    {
        return (string) config('diwan.telegram.webhook_secret');
    }

    public function isConfigured(): bool
    {
        return filled($this->token()) && filled($this->secret());
    }

    /**
     * Tetapkan webhook Telegram ke laluan SPDM. Simpan status ke platform_settings.
     *
     * @return array{ok: bool, message: string, at?: string, url?: string}
     */
    public function setWebhook(bool $fresh = false): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Token bot / rahsia webhook Telegram belum ditetapkan.'];
        }

        $url = route('webhooks.telegram', ['secret' => $this->secret()]);
        $payload = ['url' => $url];
        if ($fresh) {
            $payload['drop_pending_updates'] = true;
        }

        try {
            $response = Http::asJson()->post("https://api.telegram.org/bot{$this->token()}/setWebhook", $payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Ralat rangkaian: '.$e->getMessage()];
        }

        $ok = $response->successful() && $response->json('ok') === true;
        $result = [
            'ok' => $ok,
            'message' => $ok ? 'Webhook Telegram ditetapkan.' : 'Gagal menetapkan webhook: '.$response->body(),
            'at' => now()->toIso8601String(),
            'url' => $url,
        ];
        PlatformSetting::put('telegram_webhook_status', $result);

        return $result;
    }

    /** @return array<string, mixed> */
    public function getWebhookInfo(): array
    {
        if (blank($this->token())) {
            return ['ok' => false, 'message' => 'Token bot belum ditetapkan.'];
        }

        try {
            return Http::asJson()->get("https://api.telegram.org/bot{$this->token()}/getWebhookInfo")->json() ?? ['ok' => false];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
