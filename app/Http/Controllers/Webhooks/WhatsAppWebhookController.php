<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\SourceChannel;
use App\Http\Controllers\Controller;
use App\Models\Mosque;
use App\Models\User;
use App\Notifications\InboxNewItemNotification;
use App\Services\InboxIngestService;
use App\Services\WhatsAppGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

// §11.1 — Webhook masuk (gateway → Diwan). Hanya dokumen layak (kata kunci di sisi gateway).
class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request, InboxIngestService $ingest, WhatsAppGateway $gateway): JsonResponse
    {
        // (1) HMAC-SHA256 wajib.
        $raw = $request->getContent();
        $signature = $request->header('X-Diwan-Signature');
        $expected = hash_hmac('sha256', $raw, (string) config('diwan.whatsapp.webhook_secret'));

        if (! $signature || ! hash_equals($expected, $signature)) {
            return response()->json([], 401); // tanpa maklumat
        }

        $data = $request->json()->all();
        $session = $data['session'] ?? null;
        $from = $data['from'] ?? null;
        $messageId = $data['message_id'] ?? null;

        // (2) Idempotensi 24 jam.
        if ($messageId) {
            // Message ID perlu berskop sesi; dua tenant tidak boleh saling
            // menyekat ingest jika penyedia mengitar/mengulang ID yang sama.
            $key = 'wa_msg:'.hash('sha256', (string) $session).':'.$messageId;
            if (Cache::has($key)) {
                return response()->json([]);
            }
            Cache::put($key, true, now()->addHours(24));
        }

        // (3) Sesi → masjid; tidak dikenali → 200 + log (jangan dedah).
        $mosque = $session ? Mosque::query()->where('wa_session_id', $session)->first() : null;
        if (! $mosque) {
            Log::info('[WA webhook] sesi tidak dikenali: '.$session);

            return response()->json([]);
        }

        // (4) Masjid aktif + intake dihidupkan + kuota.
        if (! $mosque->isActive() || ! $mosque->waIntakeEnabled()) {
            $gateway->send($session, (string) $from, 'Maaf, penerimaan dokumen untuk masjid ini tidak diaktifkan.', $mosque->id, null, 'wa_reject');

            return response()->json([]);
        }

        if ($mosque->storage_used_bytes >= $mosque->effectiveQuotaBytes()) {
            $gateway->send($session, (string) $from, '⚠️ Kuota storan masjid penuh. Dokumen tidak disimpan.', $mosque->id, null, 'wa_quota');

            return response()->json([]);
        }

        // (5) Penghantar mesti AHLI masjid itu.
        $user = User::query()->where('phone_wa', $from)->first();
        if (! $user || ! $user->isMemberOf($mosque)) {
            $gateway->send($session, (string) $from, "Maaf, nombor anda tidak berdaftar sebagai ahli {$mosque->name} dalam Diwan.", $mosque->id, $user?->id, 'wa_reject');

            return response()->json([]);
        }

        // (6) Simpan media → Peti Masuk.
        $mediaBase64 = $data['media_base64'] ?? null;
        if (! $mediaBase64) {
            return response()->json([]);
        }

        $contents = base64_decode($mediaBase64, true);
        if ($contents === false || strlen($contents) > 25 * 1024 * 1024) {
            $gateway->send($session, (string) $from, '⚠️ Fail tidak sah atau melebihi 25MB.', $mosque->id, $user->id, 'wa_reject');

            return response()->json([]);
        }

        $filename = $data['filename'] ?? ('wa-'.($messageId ?? 'dokumen').'.jpg');
        $mime = $data['media_mime'] ?? 'application/octet-stream';

        $record = $ingest->ingest($mosque, $contents, $filename, $mime, $user, SourceChannel::WhatsApp, [
            'from' => $from,
            'session' => $session,
            'caption' => $data['caption'] ?? null,
        ]);

        // Ack melalui sesi masjid.
        $ref = strtoupper(substr($record->ulid, -6));
        $gateway->send($session, (string) $from, "✅ Diterima untuk *{$mosque->name}*. Rujukan sementara: #{$ref}. Kerani akan memprosesnya.", $mosque->id, $user->id, 'wa_ack');

        // (7) Notifikasi pemegang inbox.view masjid itu.
        $this->notifyInboxHolders($mosque, 1);

        return response()->json([]);
    }

    protected function notifyInboxHolders(Mosque $mosque, int $count): void
    {
        $recipients = $mosque->users()->get()
            ->filter(fn (User $u) => $u->canIn($mosque, 'inbox.view'));

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new InboxNewItemNotification($mosque, $count, 'WhatsApp'));
        }
    }
}
