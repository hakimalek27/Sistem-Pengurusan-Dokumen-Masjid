<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class HealthCheck extends Command
{
    protected $signature = 'diwan:health';

    protected $description = 'Healthcheck dalaman untuk PHP, pangkalan data dan cache/Redis';

    public function handle(): int
    {
        try {
            DB::select('SELECT 1');
            $key = 'health:'.Str::uuid();
            Cache::put($key, 'ok', 10);
            $cacheOk = Cache::get($key) === 'ok';
            Cache::forget($key);

            if (! $cacheOk) {
                throw new \RuntimeException('Cache read-after-write gagal.');
            }

            if (! $this->option('quiet')) {
                $this->info('OK');
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            if (! $this->option('quiet')) {
                $this->error($e->getMessage());
            }

            return self::FAILURE;
        }
    }
}
