<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

// §11.1 — Simulator webhook WhatsApp (bina payload sebenar + HMAC sah → POST ke aplikasi).
class SimulateWhatsApp extends Command
{
    protected $signature = 'diwan:simulate-whatsapp {session} {phone} {path}';

    protected $description = 'Simulasi dokumen WhatsApp masuk untuk sesi masjid tertentu';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("Fail tidak wujud: {$path}");

            return self::FAILURE;
        }

        $contents = file_get_contents($path);
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        $payload = [
            'session' => $this->argument('session'),
            'from' => $this->argument('phone'),
            'type' => str_starts_with($mime, 'image/') ? 'image' : 'document',
            'media_base64' => base64_encode($contents),
            'media_mime' => $mime,
            'filename' => basename($path),
            'caption' => 'spdm',
            'message_id' => 'SIM'.now()->timestamp.random_int(100, 999),
            'timestamp' => now()->timestamp,
        ];

        $raw = json_encode($payload);
        $signature = hash_hmac('sha256', $raw, (string) config('diwan.whatsapp.webhook_secret'));
        $url = rtrim((string) config('app.url'), '/').'/api/webhooks/whatsapp';

        $response = Http::withBody($raw, 'application/json')
            ->withHeaders(['X-Diwan-Signature' => $signature])
            ->post($url);

        $this->info("HTTP {$response->status()} — payload dihantar ke {$url} (sesi: {$this->argument('session')})");

        return self::SUCCESS;
    }
}
