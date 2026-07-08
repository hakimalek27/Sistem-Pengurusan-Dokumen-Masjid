<?php

namespace App\Console\Commands;

use App\Jobs\FetchMailJob;
use Illuminate\Console\Command;

// §11.3 — Tarik e-mel pengimbas (dijadualkan setiap minit di Fasa 8).
class FetchMail extends Command
{
    protected $signature = 'diwan:fetch-mail';

    protected $description = 'Tarik e-mel pengimbas IMAP dan route ke masjid mengikut slug';

    public function handle(): int
    {
        FetchMailJob::dispatchSync();

        $this->info('FetchMailJob selesai (IMAP '.(config('diwan.imap_enabled') ? 'aktif' : 'dimatikan').').');

        return self::SUCCESS;
    }
}
