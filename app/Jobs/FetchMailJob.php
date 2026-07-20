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
use Illuminate\Support\Facades\Cache;
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
        // §11.3 — expireAfter(120): kunci auto-luput selepas 2 minit. TANPA expiry,
        // expiresAfter lalai = 0 (KEKAL selamanya): jika satu larian terbunuh
        // (cth container di-recreate semasa deploy di tengah larian), kunci tinggal
        // dan SETIAP fetch-mail berikutnya dilangkau senyap → e-mel tak diproses.
        //
        // Diturunkan 600→120 pada 20 Jul: kunci ini DISAHKAN tersangkut semula
        // sebaik sahaja deploy dilakukan (container recreate mid-run), menyekat
        // intake 10 minit penuh setiap kali. Larian biasa <10 saat, jadi 2 minit
        // sudah longgar; tetingkap kecil = tempoh gagal-senyap maksimum kecil.
        // dontRelease() = jangan baris gilir semula pada perebutan; larian
        // berjadual seterusnya (setiap minit) cuba lagi.
        return [(new WithoutOverlapping('diwan-fetch-mail'))->expireAfter(120)->dontRelease()];
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

                // Log + diagnostik + notifikasi admin untuk penolakan (elak lesap senyap).
                $mail->recordOutcome($result, $from, $subject);
            } catch (\Throwable $e) {
                Log::warning('[IMAP] ralat proses mesej: '.$e->getMessage());

                // Mesej racun: selepas 3 kegagalan berturut, tandai Seen supaya ia
                // tidak diproses berulang setiap minit. Ralat sementara (IMAP/S3)
                // masih dicuba semula sebelum ambang dicapai.
                $uid = rescue(fn () => (string) $message->getUid(), '', report: false);
                if ($uid !== '') {
                    Cache::add('imap_fail:'.$uid, 0, now()->addDay());
                    if ((int) Cache::increment('imap_fail:'.$uid) >= 3) {
                        rescue(fn () => $message->setFlag('Seen'), report: false);
                        Cache::forget('imap_fail:'.$uid);
                        Log::error('[IMAP] mesej dilangkau selepas 3 kegagalan berturut (uid '.$uid.'): '.$e->getMessage());
                    }
                }
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

    /**
     * Reset kiraan kegagalan bila sambungan IMAP pulih, dan rekod cap masa
     * larian berjaya.
     *
     * imap_last_success_at ialah "detak jantung" intake: streak SAHAJA tidak
     * memadai kerana ia hanya bertambah apabila job BERJALAN. Jika job tidak
     * pernah dijalankan (cth mutex jadual tersangkut — punca sebenar intake
     * tersekat 14 jam pada 20 Jul), streak kekal 0 dan setiap penunjuk tersilap
     * papar "OK" hijau. Cap masa ini membolehkan pengesanan "tersekat senyap"
     * (lihat CheckWaSessions::checkImap).
     */
    protected function recordImapSuccess(): void
    {
        PlatformSetting::put('imap_last_success_at', now()->toIso8601String());

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
