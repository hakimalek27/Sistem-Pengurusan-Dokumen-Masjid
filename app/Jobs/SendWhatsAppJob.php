<?php

namespace App\Jobs;

use App\Services\WhatsAppGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// §14 — Penghantaran WhatsApp dengan retry [30,120]; membawa mosque_id (tiada konteks global).
class SendWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $session,
        public string $to,
        public string $message,
        public ?int $mosqueId = null,
        public ?int $userId = null,
        public string $type = 'whatsapp',
    ) {}

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(WhatsAppGateway $gateway): void
    {
        $ok = $gateway->send($this->session, $this->to, $this->message, $this->mosqueId, $this->userId, $this->type);

        if (! $ok) {
            throw new \RuntimeException('Penghantaran WhatsApp gagal — akan dicuba semula.');
        }
    }
}
