<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

// §11.2 — Webhook Telegram: sambung akaun melalui /start {token}.
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret): JsonResponse
    {
        if (! hash_equals((string) config('diwan.telegram.webhook_secret'), $secret)) {
            abort(403);
        }

        $text = (string) $request->input('message.text', '');
        $chatId = $request->input('message.chat.id');

        if ($chatId && str_starts_with($text, '/start ')) {
            $token = trim(substr($text, 7));

            // Cabang utama: token pendek berasaskan cache (deep-link t.me terhad
            // 64 aksara — output Crypt terlalu panjang untuk deep link).
            $userId = Cache::pull('telegram_connect:'.$token);

            if ($userId) {
                User::query()->whereKey($userId)->update(['telegram_chat_id' => (string) $chatId]);
                $this->reply($chatId, 'Akaun Diwan anda kini bersambung dengan Telegram. Anda akan menerima notifikasi di sini.');

                return response()->json(['ok' => true]);
            }

            // Fallback serasi-belakang: token Crypt lama.
            try {
                $payload = Crypt::decrypt($token);

                if (($payload['exp'] ?? 0) >= now()->timestamp && ! empty($payload['user_id'])) {
                    User::query()->whereKey($payload['user_id'])->update(['telegram_chat_id' => (string) $chatId]);
                    $this->reply($chatId, 'Akaun Diwan anda kini bersambung dengan Telegram.');
                }
            } catch (\Throwable $e) {
                // token tidak sah — abaikan.
            }
        }

        return response()->json(['ok' => true]);
    }

    /** Balas mesej pengesahan (best-effort — tidak menghalang webhook). */
    protected function reply(mixed $chatId, string $text): void
    {
        $token = (string) config('diwan.telegram.bot_token');
        if (blank($token)) {
            return;
        }

        try {
            Http::asJson()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            // abaikan kegagalan balasan.
        }
    }
}
