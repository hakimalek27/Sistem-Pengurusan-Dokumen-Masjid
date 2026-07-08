<?php

namespace App\Jobs;

use App\Models\Mosque;
use App\Notifications\InboxNewItemNotification;
use App\Services\MailIngestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Webklex\IMAP\Client;

/**
 * §11.3 — Tarik e-mel pengimbas (IMAP) & route ikut slug. Dipagar IMAP_ENABLED.
 * Logik penghalaan/ingest sebenar dalam MailIngestService (diuji).
 */
class FetchMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function middleware(): array
    {
        return [new WithoutOverlapping('diwan-fetch-mail')];
    }

    public function handle(MailIngestService $mail): void
    {
        if (! config('diwan.imap_enabled')) {
            return; // guard §11.3
        }

        try {
            /** @var Client $client */
            $client = \Webklex\IMAP\Facades\Client::account('default');
            $client->connect();
            $messages = $client->getFolderByName('INBOX')->query()->unseen()->get();
        } catch (\Throwable $e) {
            Log::warning('[IMAP] gagal sambung: '.$e->getMessage());

            return;
        }

        foreach ($messages as $message) {
            try {
                $recipients = array_merge(
                    self::addresses($message->getTo()),
                    self::addresses($message->getHeader()?->get('delivered-to')),
                );
                $from = (string) optional($message->getFrom()[0] ?? null)->mail;
                $subject = (string) $message->getSubject();
                $messageId = (string) $message->getMessageId();

                $attachments = [];
                foreach ($message->getAttachments() as $attachment) {
                    $attachments[] = [
                        'content' => $attachment->getContent(),
                        'filename' => $attachment->getName(),
                        'mime' => $attachment->getMimeType(),
                    ];
                }

                $result = $mail->ingestMessage($recipients, $from, $subject, $messageId, $attachments);
                $message->setFlag('Seen');

                if (($result['status'] ?? '') === 'ok' && ! empty($result['records'])) {
                    $this->notifyInbox($result['mosque'], count($result['records']));
                }
            } catch (\Throwable $e) {
                Log::warning('[IMAP] ralat proses mesej: '.$e->getMessage());
            }
        }
    }

    protected function notifyInbox(Mosque $mosque, int $count): void
    {
        $recipients = $mosque->users()->get()->filter(fn ($u) => $u->canIn($mosque, 'inbox.view'));

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new InboxNewItemNotification($mosque, $count, 'E-mel'));
        }
    }

    protected static function addresses(mixed $collection): array
    {
        if (! $collection) {
            return [];
        }

        return collect(is_iterable($collection) ? $collection : [$collection])
            ->map(fn ($a) => is_object($a) ? ($a->mail ?? (string) $a) : (string) $a)
            ->all();
    }
}
