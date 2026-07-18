<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

// §11.2 — Tetapkan webhook Telegram ke laluan SPDM (sekali selepas deploy).
class TelegramSetWebhook extends Command
{
    protected $signature = 'diwan:telegram-set-webhook {--fresh : Buang kemas kini tertangguh}';

    protected $description = 'Tetapkan webhook Telegram Bot API ke laluan webhook SPDM';

    public function handle(): int
    {
        $token = (string) config('diwan.telegram.bot_token');
        $secret = (string) config('diwan.telegram.webhook_secret');

        if (blank($token) || blank($secret)) {
            $this->error('TELEGRAM_BOT_TOKEN / TELEGRAM_WEBHOOK_SECRET belum ditetapkan.');

            return self::FAILURE;
        }

        $url = route('webhooks.telegram', ['secret' => $secret]);

        $payload = ['url' => $url];
        if ($this->option('fresh')) {
            $payload['drop_pending_updates'] = true;
        }

        $response = Http::asJson()->post("https://api.telegram.org/bot{$token}/setWebhook", $payload);

        if ($response->successful() && $response->json('ok') === true) {
            $this->info('Webhook Telegram ditetapkan: '.$url);

            return self::SUCCESS;
        }

        $this->error('Gagal menetapkan webhook: '.$response->body());

        return self::FAILURE;
    }
}
