<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOcrJob;
use App\Services\WhatsAppGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Symfony\Component\Process\Process;
use Throwable;
use Webklex\IMAP\Facades\Client as ImapClient;

class StagingCheck extends Command
{
    protected $signature = 'diwan:staging-check {--mail-to= : Alamat penerima e-mel ujian sebenar} {--skip-imap : Langkau autentikasi IMAP} {--json : Output JSON}';

    protected $description = 'Periksa PostgreSQL, Redis/Horizon, COS, OCR, Meili, SMTP, IMAP dan gateway di staging';

    public function handle(WhatsAppGateway $gateway): int
    {
        $checks = [];
        $this->check($checks, 'postgresql', fn () => DB::getDriverName() === 'pgsql' && (bool) DB::select('SELECT 1'));
        $this->check($checks, 'redis_cache', function () {
            $key = 'staging:'.Str::uuid();
            Cache::put($key, 'ok', 30);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            return $ok;
        });
        $this->check($checks, 'horizon', fn () => count(app(MasterSupervisorRepository::class)->all()) > 0);
        $this->check($checks, 'cos', function () {
            $disk = Storage::disk(config('diwan.storage_disk'));
            $path = 'platform/health/'.Str::uuid().'.txt';
            $disk->put($path, 'diwan-staging-check');
            $ok = $disk->get($path) === 'diwan-staging-check';
            $disk->delete($path);

            return $ok;
        });
        $this->check($checks, 'ocr', fn () => ProcessOcrJob::toolingAvailable()
            && (new Process(['tesseract', '--version']))->run() === 0
            && (new Process(['img2pdf', '--version']))->run() === 0);
        $this->check($checks, 'meilisearch', function () {
            $response = Http::timeout(8)
                ->withToken((string) config('diwan.meilisearch.key'))
                ->get(rtrim((string) config('diwan.meilisearch.host'), '/').'/health');

            return $response->successful() && $response->json('status') === 'available';
        });

        $mailTo = $this->option('mail-to');
        if ($mailTo) {
            $this->check($checks, 'smtp', function () use ($mailTo) {
                Mail::raw('Ujian staging Diwan pada '.now()->toIso8601String(), fn ($message) => $message->to($mailTo)->subject('Diwan staging check'));

                return true;
            });
        } else {
            $checks['smtp'] = ['ok' => false, 'detail' => 'WAJIB beri --mail-to untuk bukti penghantaran sebenar'];
        }

        if ($this->option('skip-imap')) {
            $checks['imap'] = ['ok' => true, 'detail' => 'dilangkau secara eksplisit'];
        } else {
            $this->check($checks, 'imap', function () {
                if (! config('diwan.imap_enabled')) {
                    throw new \RuntimeException('IMAP_ENABLED=false');
                }

                $client = ImapClient::account('default');

                try {
                    $client->connect();
                    $folders = $client->getFolders(false);

                    return $client->isConnected() && $folders->isNotEmpty();
                } finally {
                    if ($client->isConnected()) {
                        $client->disconnect();
                    }
                }
            });
        }

        $this->check($checks, 'gateway', fn () => $gateway->ping());

        $passed = collect($checks)->every(fn (array $check) => $check['ok']);

        if ($this->option('json')) {
            $this->line(json_encode(['ok' => $passed, 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($checks as $name => $result) {
                $this->line(sprintf('%-16s %s %s', $name, $result['ok'] ? 'LULUS' : 'GAGAL', $result['detail']));
            }
        }

        return $passed ? self::SUCCESS : self::FAILURE;
    }

    protected function check(array &$checks, string $name, callable $callback): void
    {
        try {
            $ok = (bool) $callback();
            $checks[$name] = ['ok' => $ok, 'detail' => $ok ? 'ok' : 'semakan memulangkan false'];
        } catch (Throwable $e) {
            $checks[$name] = ['ok' => false, 'detail' => mb_substr($e->getMessage(), 0, 300)];
        }
    }
}
