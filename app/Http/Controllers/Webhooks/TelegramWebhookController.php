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
                // notify_telegram=true: tekan Start = niat eksplisit menerima notifikasi.
                // Tanpa ini, chat_id disimpan tetapi via() masih SKIP Telegram (toggle
                // lalai false) → pengguna "Bersambung" tapi tiada notifikasi (bug 20 Jul).
                User::query()->whereKey($userId)->update(['telegram_chat_id' => (string) $chatId, 'notify_telegram' => true]);
                $this->reply($chatId, 'Akaun Diwan anda kini bersambung dengan Telegram. Anda akan menerima notifikasi di sini.');

                return response()->json(['ok' => true]);
            }

            // Fallback serasi-belakang: token Crypt lama.
            try {
                $payload = Crypt::decrypt($token);

                if (($payload['exp'] ?? 0) >= now()->timestamp && ! empty($payload['user_id'])) {
                    User::query()->whereKey($payload['user_id'])->update(['telegram_chat_id' => (string) $chatId, 'notify_telegram' => true]);
                    $this->reply($chatId, 'Akaun Diwan anda kini bersambung dengan Telegram.');

                    return response()->json(['ok' => true]);
                }
            } catch (\Throwable $e) {
                // token tidak sah — jatuh ke semakan tamat tempoh di bawah.
            }

            // Token pendek sah-bentuk (48 aksara) tetapi tiada dalam cache = tamat
            // tempoh / sudah diguna. Beri maklum balas supaya pengguna tidak
            // tertanya-tanya bila bot "senyap". Payload /start rawak kekal senyap.
            if (preg_match('/^[A-Za-z0-9]{48}$/', $token)) {
                $this->reply($chatId, 'Pautan sambungan Telegram telah tamat tempoh atau telah digunakan. Sila jana pautan baharu di Diwan (Profil Saya → Sambung Telegram), kemudian tekan Start semula.');
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
