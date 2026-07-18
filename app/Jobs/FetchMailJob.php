<?php

namespace App\Jobs;

use App\Models\Mosque;
use App\Models\PlatformSetting;
use App\Notifications\InboxNewItemNotification;
use App\Services\MailIngestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
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
            $this->recordImapFailure($e->getMessage());

            return;
        }

        $this->recordImapSuccess();

        foreach ($messages as $message) {
            try {
                $recipients = array_merge(
                    self::addresses($message->getTo()),
                    self::addresses($message->getHeader()?->get('delivered-to')),
                );
                $from = (string) optional($message->getFrom()[0] ?? null)->mail;
                $subject = (string) $message->getSubject();
                $messageId = (string) $message->getMessageId();
                $body = trim(strip_tags((string) ($message->getTextBody() ?: $message->getHTMLBody())));

                $attachments = [];
                foreach ($message->getAttachments() as $attachment) {
                    $attachments[] = [
                        'content' => $attachment->getContent(),
                        'filename' => $attachment->getName(),
                        'mime' => $attachment->getMimeType(),
                    ];
                }

                $result = $mail->ingestMessage($recipients, $from, $subject, $messageId, $attachments, $body);
                $message->setFlag('Seen');

                if (($result['status'] ?? '') === 'ok' && ! empty($result['records'])) {
                    $this->notifyInbox($result['mosque'], count($result['records']));
                }
            } catch (\Throwable $e) {
                Log::warning('[IMAP] ralat proses mesej: '.$e->getMessage());
            }
        }
    }

    /**
     * Rekod kegagalan sambungan IMAP dengan throttle log. 3 kegagalan pertama
     * dilog penuh; selepas itu 1 log setiap 10 minit sahaja — elak spam log
     * setiap minit. Streak disimpan (platform_settings) sebagai asas alert
     * kesihatan saluran untuk superadmin.
     */
    protected function recordImapFailure(string $message): void
    {
        $streak = (int) PlatformSetting::get('imap_failure_streak', 0) + 1;
        PlatformSetting::put('imap_failure_streak', $streak);
        PlatformSetting::put('imap_last_error', $message);

        $lastLoggedAt = PlatformSetting::get('imap_last_logged_at');
        $throttled = $streak > 3
            && $lastLoggedAt
            && Carbon::parse($lastLoggedAt)->addMinutes(10)->isFuture();

        if (! $throttled) {
            Log::warning("[IMAP] gagal sambung (streak {$streak}): ".$message);
            PlatformSetting::put('imap_last_logged_at', now()->toIso8601String());
        }
    }

    /** Reset kiraan kegagalan bila sambungan IMAP pulih. */
    protected function recordImapSuccess(): void
    {
        if ((int) PlatformSetting::get('imap_failure_streak', 0) !== 0) {
            PlatformSetting::put('imap_failure_streak', 0);
            PlatformSetting::put('imap_last_error', null);
            PlatformSetting::put('imap_last_logged_at', null);
            Log::info('[IMAP] sambungan pulih selepas gagal berturut.');
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
