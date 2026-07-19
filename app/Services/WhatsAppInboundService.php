<?php

namespace App\Services;

use App\Enums\SourceChannel;
use App\Models\Mosque;
use App\Models\User;
use App\Models\WhatsAppIntegration;
use App\Notifications\InboxNewItemNotification;
use App\Support\AllowedFormats;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * §11.1 — Proses event message.received selepas HMAC/envelope disahkan oleh controller.
 *
 * DASAR KATA-KUNCI-DAHULU (elak mesej ke nombor tidak dikenali / gelung echo):
 * Diwan SENYAP sepenuhnya melainkan penghantar (a) menghantar kata kunci intake TUNGGAL
 * (cth "spdm") tepat, atau (b) sedang dalam tetingkap intake aktif. Perbualan biasa, echo
 * mesej keluar, atau mesej tanpa kata kunci TIDAK menerima sebarang balasan — jadi tiada
 * gelung ping-pong (balasan Diwan sendiri tidak mengandungi kata kunci).
 */
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
        // Log ringkas event masuk (tanpa media) — audit/nyahpepijat.
        Log::info('[WA webhook] masuk: keys=['.implode(',', array_keys($data)).']'
            .' from='.json_encode($data['from_phone'] ?? $data['from'] ?? null)
            .' from_me='.json_encode($data['from_me'] ?? '(tiada)')
            .' message_id='.json_encode($data['message_id'] ?? $data['id'] ?? '(tiada)')
            .' type='.json_encode($data['type'] ?? '(tiada)')
            .' has_media='.json_encode(isset($data['media']) || isset($data['media_base64'])));

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

        // --- Niat penghantar (dasar kata-kunci-dahulu §11.1) ---
        $caption = (string) ($media['caption'] ?? $data['caption'] ?? $data['text'] ?? '');
        $hasMedia = (bool) ($media['base64'] ?? $data['media_base64'] ?? null);
        $keyword = mb_strtolower(trim($mosque->waIntakeKeyword()));
        $isKeywordExact = $keyword !== '' && mb_strtolower(trim($caption)) === $keyword;
        $keywordInCaption = $keyword !== '' && str_contains(mb_strtolower($caption), $keyword);
        $intakeKey = $this->intakeKey($session, $from);
        $inWindow = Cache::has($intakeKey);

        // GATE UTAMA: SENYAP melainkan (a) kata kunci TUNGGAL tepat, (b) dalam tetingkap
        // intake aktif, atau (c) dokumen dengan kata kunci dalam kapsyen. Perbualan biasa,
        // echo mesej keluar, dan mesej tanpa kata kunci TIDAK menerima balasan → tiada gelung.
        $engage = $isKeywordExact || $inWindow || ($hasMedia && $keywordInCaption);
        if (! $engage) {
            return;
        }

        // Penghantar sedang cuba guna intake. Semak masjid aktif + intake dihidupkan.
        if (! $mosque->isActive() || ! $mosque->waIntakeEnabled()) {
            if ($isKeywordExact || $keywordInCaption) {
                $this->reply($mosque, $session, $from, 'Maaf, penerimaan dokumen untuk masjid ini tidak diaktifkan.', null, 'wa_reject');
            }

            return;
        }

        // Keahlian: ahli masjid ini (submit biasa) / pengguna berdaftar masjid LAIN
        // (blok — isolasi §18.37) / orang luar tiada akaun (submission awam jika dibenarkan).
        $user = $from ? $mosque->users()->where('mosque_user.phone_wa', $from)->first() : null;
        if (! $user) {
            $publicIntake = (bool) config('diwan.whatsapp.allow_public_intake', true);
            $registeredElsewhere = $from !== null && DB::table('mosque_user')->where('phone_wa', $from)->exists();
            if (! $publicIntake || $registeredElsewhere) {
                return; // senyap
            }
        }

        // Dokumen → proses (dalam tetingkap / kata kunci dlm kapsyen / kata kunci tepat + media).
        if ($hasMedia) {
            $this->processContent($mosque, $user, $session, $from, $messageId, $data, $media);

            return;
        }

        // Kata kunci tunggal tanpa media → buka tetingkap intake.
        if ($isKeywordExact) {
            $mins = (int) config('diwan.whatsapp.intake_window_minutes', 10);
            Cache::put($intakeKey, true, now()->addMinutes($mins));
            $this->reply($mosque, $session, $from, "✅ Mod upload Diwan aktif selama {$mins} minit. Sila hantar dokumen sekarang.", $user?->id, 'wa_intake_ready');
        }

        // (Teks dalam tetingkap tanpa kata kunci → abai senyap.)
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $media */
    protected function processContent(Mosque $mosque, ?User $user, ?string $session, ?string $from, ?string $messageId, array $data, array $media): void
    {
        // Had kadar submission dokumen per nombor — elak banjir intake.
        if ($this->submissionRateLimited($session, $from)) {
            return; // senyap
        }

        if ($mosque->storage_used_bytes >= $mosque->effectiveQuotaBytes()) {
            $this->reply($mosque, $session, $from, '⚠️ Kuota storan masjid penuh. Dokumen tidak disimpan.', $user?->id, 'wa_quota');

            return;
        }

        $mediaBase64 = $media['base64'] ?? $data['media_base64'] ?? null;
        $contents = base64_decode((string) $mediaBase64, true);
        if ($contents === false || strlen($contents) > 25 * 1024 * 1024) {
            $this->reply($mosque, $session, $from, '⚠️ Fail tidak sah atau melebihi 25MB.', $user?->id, 'wa_reject');

            return;
        }

        $caption = (string) ($media['caption'] ?? $data['caption'] ?? $data['text'] ?? '');
        $filename = (string) ($media['filename'] ?? $data['filename'] ?? ('wa-'.($messageId ?? 'dokumen').'.jpg'));
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Semak format sebelum ingest — tolak dengan mesej, bukan biarkan
        // ValidationException memecah webhook (pengirim tak dapat maklum balas).
        if (! AllowedFormats::allowsExtension($ext)) {
            $this->reply($mosque, $session, $from, '⚠️ Format fail tidak disokong. Hantar '.AllowedFormats::label().' sahaja.', $user?->id, 'wa_reject');

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
            $this->reply($mosque, $session, $from, '⚠️ Dokumen tidak dapat diproses: '.collect($e->errors())->flatten()->first(), $user?->id, 'wa_reject');

            return;
        }

        if (! $record) {
            return; // duplikat — senyap (elak spam "duplikat" pada echo/hantar semula)
        }

        $ref = strtoupper(substr($record->ulid, -6));
        $this->reply($mosque, $session, $from, "✅ Diterima untuk *{$mosque->name}*. Rujukan sementara: #{$ref}. Kerani akan memprosesnya.", $user?->id, 'wa_ack');
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
        if ($this->replySuppressed($session, $to, $type)) {
            Log::warning('[WA] balasan auto digugurkan (had kadar) session='.$session.' to='.$to.' type='.$type);

            return;
        }

        $this->gateway->send((string) $session, (string) $to, $message, $mosque->id, $userId, $type);
    }

    /**
     * §11.1 — Backstop had kadar balasan PENOLAKAN/RALAT (wa_reject/wa_quota). Punca utama
     * gelung sudah diputus oleh gate kata-kunci-dahulu; ini lapisan tambahan. Balasan
     * kejayaan (ack/intake_ready) sentiasa dihantar kerana ia hanya berlaku pada
     * penglibatan tulen. Penerima kosong → digugurkan.
     */
    protected function replySuppressed(?string $session, ?string $to, string $type): bool
    {
        if ($to === null || trim($to) === '') {
            return true;
        }

        if (! in_array($type, ['wa_reject', 'wa_quota'], true)) {
            return false;
        }

        $sid = (string) $session;

        $cooldown = (int) config('diwan.whatsapp.reject_cooldown_minutes', 60);
        if ($cooldown > 0
            && ! Cache::add('wa_reject_cd:'.hash('sha256', $sid.'|'.$to.'|'.$type), 1, now()->addMinutes($cooldown))) {
            return true;
        }

        $cap = (int) config('diwan.whatsapp.reply_cap', 5);
        $window = (int) config('diwan.whatsapp.reply_cap_window_minutes', 10);
        if ($cap > 0 && $window > 0) {
            $capKey = 'wa_reject_cap:'.hash('sha256', $sid.'|'.$to);
            $count = (int) Cache::get($capKey, 0);
            if ($count >= $cap) {
                return true;
            }
            Cache::put($capKey, $count + 1, now()->addMinutes($window));
        }

        return false;
    }

    /** §11.1 — Had submission dokumen per nombor (elak banjir intake awam). */
    protected function submissionRateLimited(?string $session, ?string $from): bool
    {
        if ($from === null || trim($from) === '') {
            return true;
        }

        $cap = (int) config('diwan.whatsapp.submission_cap', 10);
        $window = (int) config('diwan.whatsapp.submission_window_minutes', 60);
        if ($cap <= 0 || $window <= 0) {
            return false;
        }

        $key = 'wa_submit_cap:'.hash('sha256', (string) $session.'|'.$from);
        $count = (int) Cache::get($key, 0);
        if ($count >= $cap) {
            return true;
        }
        Cache::put($key, $count + 1, now()->addMinutes($window));

        return false;
    }

    protected function notifyInboxHolders(Mosque $mosque): void
    {
        $recipients = $mosque->users()->get()->filter(fn (User $user) => $user->canIn($mosque, 'inbox.view'));
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new InboxNewItemNotification($mosque, 1, 'WhatsApp'));
        }
    }
}
