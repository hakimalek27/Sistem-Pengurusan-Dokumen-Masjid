<?php

namespace App\Services;

use App\Enums\SourceChannel;
use App\Models\Mosque;
use App\Models\User;
use App\Models\WhatsAppIntegration;
use App\Notifications\InboxNewItemNotification;
use App\Support\AllowedFormats;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/** Proses event message.received selepas HMAC/envelope disahkan oleh controller. */
class WhatsAppInboundService
{
    public function __construct(
        protected InboxIngestService $ingest,
        protected WhatsAppGateway $gateway,
        protected WhatsAppRecipientResolver $recipients,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(array $data): void
    {
        if (($data['from_me'] ?? false) || ($data['is_group'] ?? false)) {
            return;
        }

        $media = is_array($data['media'] ?? null) ? $data['media'] : [];
        $session = is_scalar($data['session_id'] ?? $data['session'] ?? null)
            ? (string) ($data['session_id'] ?? $data['session'])
            : null;
        $rawFrom = $data['from_phone'] ?? $data['from'] ?? null;
        $from = $this->recipients->normalize(is_scalar($rawFrom) ? (string) $rawFrom : null);
        $messageId = is_scalar($data['message_id'] ?? null) ? (string) $data['message_id'] : null;

        if ($this->alreadyProcessed($session, $messageId)) {
            return;
        }

        $integration = $session ? WhatsAppIntegration::query()->withoutMosqueScope()
            ->where('session_id', $session)
            ->where('enabled', true)
            ->where('status', 'connected')
            ->first() : null;
        $mosque = $integration?->mosque;
        if (! $mosque) {
            Log::info('[WA webhook] sesi tidak dikenali: '.$session);

            return;
        }

        if (! $mosque->isActive() || ! $mosque->waIntakeEnabled()) {
            $this->reply($mosque, $session, $from, 'Maaf, penerimaan dokumen untuk masjid ini tidak diaktifkan.', null, 'wa_reject');

            return;
        }

        if ($mosque->storage_used_bytes >= $mosque->effectiveQuotaBytes()) {
            $this->reply($mosque, $session, $from, '⚠️ Kuota storan masjid penuh. Dokumen tidak disimpan.', null, 'wa_quota');

            return;
        }

        $user = $from ? $mosque->users()->where('mosque_user.phone_wa', $from)->first() : null;
        if (! $user || ! $user->isMemberOf($mosque)) {
            $this->reply($mosque, $session, $from, "Maaf, nombor anda tidak berdaftar sebagai ahli {$mosque->name} dalam Diwan.", $user?->id, 'wa_reject');

            return;
        }

        $this->processContent($mosque, $user, $session, $from, $messageId, $data, $media);
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $media */
    protected function processContent(Mosque $mosque, User $user, ?string $session, ?string $from, ?string $messageId, array $data, array $media): void
    {
        $caption = (string) ($media['caption'] ?? $data['caption'] ?? $data['text'] ?? '');
        $mediaBase64 = $media['base64'] ?? $data['media_base64'] ?? null;
        $keyword = mb_strtolower(trim($mosque->waIntakeKeyword()));
        $intakeKey = $this->intakeKey($session, $from);

        if (! $mediaBase64) {
            if ($keyword !== '' && mb_strtolower(trim($caption)) === $keyword) {
                Cache::put($intakeKey, true, now()->addMinutes(10));
                $this->reply($mosque, $session, $from, '✅ Mod upload Diwan aktif selama 10 minit. Sila hantar satu dokumen sekarang.', $user->id, 'wa_intake_ready');
            }

            return;
        }

        $keywordInCaption = $keyword !== '' && str_contains(mb_strtolower($caption), $keyword);
        if (! Cache::has($intakeKey) && ! $keywordInCaption) {
            $this->reply($mosque, $session, $from, "Hantar kata kunci *{$mosque->waIntakeKeyword()}* dahulu, kemudian hantar dokumen dalam masa 10 minit.", $user->id, 'wa_reject');

            return;
        }

        $contents = base64_decode((string) $mediaBase64, true);
        if ($contents === false || strlen($contents) > 25 * 1024 * 1024) {
            $this->reply($mosque, $session, $from, '⚠️ Fail tidak sah atau melebihi 25MB.', $user->id, 'wa_reject');

            return;
        }

        $filename = (string) ($media['filename'] ?? $data['filename'] ?? ('wa-'.($messageId ?? 'dokumen').'.jpg'));
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Semak format sebelum ingest — tolak dengan mesej, bukan biarkan
        // ValidationException memecah webhook (pengirim tak dapat maklum balas).
        if (! AllowedFormats::allowsExtension($ext)) {
            $this->reply($mosque, $session, $from, '⚠️ Format fail tidak disokong. Hantar '.AllowedFormats::label().' sahaja.', $user->id, 'wa_reject');
            Cache::forget($intakeKey);

            return;
        }

        // MIME kanonik daripada extension (WhatsApp selalu octet-stream/tiada).
        $mime = AllowedFormats::mimeForExtension($ext)
            ?? (string) ($media['mime_type'] ?? $data['media_mime'] ?? 'application/octet-stream');

        try {
            $record = $this->ingest->ingest($mosque, $contents, $filename, $mime, $user, SourceChannel::WhatsApp, [
                'from' => $from, 'session' => $session, 'caption' => $caption ?: null,
            ], skipIfDuplicate: true);
        } catch (ValidationException $e) {
            $this->reply($mosque, $session, $from, '⚠️ Dokumen tidak dapat diproses: '.collect($e->errors())->flatten()->first(), $user->id, 'wa_reject');
            Cache::forget($intakeKey);

            return;
        }
        Cache::forget($intakeKey);

        if (! $record) {
            $this->reply($mosque, $session, $from, 'ℹ️ Dokumen yang sama telah diterima sebelum ini; salinan pendua tidak disimpan.', $user->id, 'wa_duplicate');

            return;
        }

        $ref = strtoupper(substr($record->ulid, -6));
        $this->reply($mosque, $session, $from, "✅ Diterima untuk *{$mosque->name}*. Rujukan sementara: #{$ref}. Kerani akan memprosesnya.", $user->id, 'wa_ack');
        $this->notifyInboxHolders($mosque);
    }

    protected function alreadyProcessed(?string $session, ?string $messageId): bool
    {
        if (! $messageId) {
            return false;
        }

        $key = 'wa_msg:'.hash('sha256', (string) $session).':'.$messageId;
        if (Cache::has($key)) {
            return true;
        }
        Cache::put($key, true, now()->addHours(24));

        return false;
    }

    protected function intakeKey(?string $session, ?string $from): string
    {
        return 'wa_intake:'.hash('sha256', (string) $session).':'.hash('sha256', (string) $from);
    }

    protected function reply(Mosque $mosque, ?string $session, ?string $to, string $message, ?int $userId, string $type): void
    {
        $this->gateway->send((string) $session, (string) $to, $message, $mosque->id, $userId, $type);
    }

    protected function notifyInboxHolders(Mosque $mosque): void
    {
        $recipients = $mosque->users()->get()->filter(fn (User $user) => $user->canIn($mosque, 'inbox.view'));
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new InboxNewItemNotification($mosque, 1, 'WhatsApp'));
        }
    }
}
