<?php

namespace App\Console\Commands;

use App\Jobs\FetchMailJob;
use App\Support\MailIntakeHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

// §11.3 — Tarik e-mel pengimbas (dijadualkan setiap minit di Fasa 8).
class FetchMail extends Command
{
    protected $signature = 'diwan:fetch-mail {--force : Lepaskan kunci WithoutOverlapping tersangkut sebelum jalan}';

    protected $description = 'Tarik e-mel pengimbas IMAP dan route ke masjid mengikut slug';

    /** Kunci middleware WithoutOverlapping milik FetchMailJob. */
    private const LOCK = 'laravel-queue-overlap:App\Jobs\FetchMailJob:diwan-fetch-mail';

    public function handle(): int
    {
        // Middleware WithoutOverlapping menelan larian yang bertindih SENYAP.
        // Tanpa semakan ini command melaporkan "selesai" walaupun job tidak
        // pernah dijalankan — laporan palsu yang menyukarkan diagnosis semasa
        // insiden intake tersekat 20 Jul. Kunci ini tersangkut setiap kali
        // container di-recreate di tengah larian (iaitu setiap deploy).
        if ($this->option('force')) {
            Cache::lock(self::LOCK)->forceRelease();
            $this->warn('Kunci WithoutOverlapping dilepaskan secara paksa (--force).');
        }

        // Kesan kunci dengan CUBA mengambilnya, kemudian lepaskan serta-merta
        // supaya job sendiri boleh mengambilnya. Cache::has() tidak boleh
        // digunakan: pada store `array` (ujian) kunci disimpan berasingan
        // daripada cache biasa, jadi ia sentiasa pulang false. Tetingkap
        // perlumbaan antara release dan dispatch hanya menjejaskan mesej
        // diagnostik ini — job masih dilindungi middlewarenya sendiri.
        $probe = Cache::lock(self::LOCK, 5);
        $blocked = ! $probe->get();

        if (! $blocked) {
            $probe->release();
        }

        FetchMailJob::dispatchSync();

        if ($blocked) {
            $this->error('DILANGKAU: kunci WithoutOverlapping sedang dipegang larian lain.');
            $this->line('Jika tiada larian lain sedang berjalan, kunci ini tersangkut — jalankan semula dengan --force.');

            return self::FAILURE;
        }

        $health = MailIntakeHealth::evaluate();
        $this->info('FetchMailJob selesai (IMAP '.(config('diwan.imap_enabled') ? 'aktif' : 'dimatikan').'). Status intake: '.$health['label'].'.');

        return self::SUCCESS;
    }
}
