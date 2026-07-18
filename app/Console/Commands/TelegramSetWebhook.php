<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

// §11.2 — Tetapkan webhook Telegram ke laluan SPDM (sekali selepas deploy).
// Logik sebenar dalam TelegramService (dikongsi dengan UI superadmin).
class TelegramSetWebhook extends Command
{
    protected $signature = 'diwan:telegram-set-webhook {--fresh : Buang kemas kini tertangguh}';

    protected $description = 'Tetapkan webhook Telegram Bot API ke laluan webhook SPDM';

    public function handle(TelegramService $telegram): int
    {
        $result = $telegram->setWebhook((bool) $this->option('fresh'));

        if ($result['ok']) {
            $this->info($result['message'].(isset($result['url']) ? ' '.$result['url'] : ''));

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
