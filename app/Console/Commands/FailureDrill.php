<?php

namespace App\Console\Commands;

use App\Jobs\FailureProbeJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class FailureDrill extends Command
{
    protected $signature = 'diwan:failure-drill {target : cos|queue|smtp} {--confirm-production}';

    protected $description = 'Suntik kegagalan terkawal untuk membuktikan pengesanan COS, queue atau SMTP';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('confirm-production')) {
            $this->error('Gunakan staging. Jika benar-benar perlu di production, tambah --confirm-production.');

            return self::FAILURE;
        }

        return match ($this->argument('target')) {
            'queue' => $this->queueProbe(),
            'cos' => $this->cosProbe(),
            'smtp' => $this->smtpProbe(),
            default => $this->invalidTarget(),
        };
    }

    protected function queueProbe(): int
    {
        if (config('queue.default') === 'sync') {
            $this->error('Queue sync tidak sesuai untuk drill. Gunakan Redis/Horizon di staging.');

            return self::FAILURE;
        }

        $id = (string) Str::uuid();
        FailureProbeJob::dispatch($id)->onQueue('default');
        $this->warn("Probe {$id} dihantar. Sahkan ia muncul dalam Horizon/failed_jobs dan alert operasi diterima.");

        return self::SUCCESS;
    }

    protected function cosProbe(): int
    {
        try {
            Storage::build(['driver' => 'local', 'root' => '/proc/diwan-failure-probe', 'throw' => true])
                ->put('probe.txt', 'mesti gagal');
        } catch (Throwable $e) {
            $this->info('LULUS: kegagalan storan dikesan — '.$e->getMessage());

            return self::SUCCESS;
        }

        $this->error('Probe storan tidak gagal seperti dijangka.');

        return self::FAILURE;
    }

    protected function smtpProbe(): int
    {
        $original = config('mail.mailers.smtp');
        config([
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 2,
        ]);
        Mail::purge('smtp');

        try {
            Mail::mailer('smtp')->raw('failure probe', fn ($message) => $message->to('probe@invalid.test')->subject('probe'));
        } catch (Throwable $e) {
            $this->info('LULUS: kegagalan SMTP dikesan — '.$e->getMessage());

            return self::SUCCESS;
        } finally {
            config(['mail.mailers.smtp' => $original]);
            Mail::purge('smtp');
        }

        $this->error('Probe SMTP tidak gagal seperti dijangka.');

        return self::FAILURE;
    }

    protected function invalidTarget(): int
    {
        $this->error('Target mesti cos, queue atau smtp.');

        return self::INVALID;
    }
}
