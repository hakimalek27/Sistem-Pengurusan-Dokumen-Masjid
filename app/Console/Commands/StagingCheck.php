<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOcrJob;
use App\Notifications\StagingSkeletonNotification;
use App\Services\WhatsAppGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
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
                // 🔴 F8: dahulu `Mail::raw(...)` — teks kosong TANPA kerangka. Ia membuktikan
                // penghantaran, tetapi BUKAN perkara yang gate ini wujud untuk buktikan: bahawa
                // kerangka e-mel kekal Bahasa Melayu selepas melalui penghantar sebenar
                // (§4.8 / nota F). E-mel mentah tiada "Salam sejahtera"/"Sekian" untuk dilihat,
                // jadi pemilik yang membacanya tidak dapat mengesahkan apa-apa.
                //
                // Kini ia menghantar notifikasi BERKERANGKA (templat markdown yang SAMA seperti
                // 18 kelas `toMail()` produk) DAN mengassert kerangka itu BM sebelum menghantar,
                // supaya kegagalan terjemahan gagal DI SINI dan bukan senyap dalam peti masuk.
                $notifikasi = new StagingSkeletonNotification;
                $html = (string) $notifikasi->toMail(null)->render();
                foreach (['Salam sejahtera', 'Sekian', 'Hak cipta terpelihara'] as $frasa) {
                    if (! str_contains($html, $frasa)) {
                        throw new \RuntimeException("kerangka e-mel kehilangan \"{$frasa}\" — locale ms tidak dimuatkan");
                    }
                }
                foreach (['Hello!', 'Regards,', 'All rights reserved'] as $bocor) {
                    if (str_contains($html, $bocor)) {
                        throw new \RuntimeException("kerangka e-mel bocor bahasa Inggeris: \"{$bocor}\"");
                    }
                }
                Notification::route('mail', $mailTo)->notify($notifikasi);

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
