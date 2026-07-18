<?php

namespace App\Services;

use App\Jobs\SendWhatsAppJob;
use App\Models\LoginToken;
use App\Models\User;
use App\Models\WhatsAppIntegration;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * §15.1 — Magic link: token 64 aksara rawak, simpan HASH SHA-256 sahaja,
 * luput 15 minit, sekali guna, IP direkod.
 *
 * Penghantaran: e-mel (jika ada) DAN WhatsApp (jika pengguna ada nombor +
 * sesi masjid bersambung). Membolehkan ahli telefon-sahaja terima pautan.
 */
class MagicLinkService
{
    /** Jana token e-mel (BC — token tanpa user_id, diselesaikan ikut e-mel). */
    public function createToken(string $email, ?string $ip = null): string
    {
        $raw = Str::random(64);

        LoginToken::query()->create([
            'email' => $email,
            'token' => hash('sha256', $raw),
            'expires_at' => now()->addMinutes(15),
            'ip' => $ip,
        ]);

        return $raw;
    }

    /** Jana token terikat kepada pengguna (menyokong akaun tanpa e-mel). */
    public function createTokenForUser(User $user, ?string $ip = null): string
    {
        $raw = Str::random(64);

        LoginToken::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'token' => hash('sha256', $raw),
            'expires_at' => now()->addMinutes(15),
            'ip' => $ip,
        ]);

        return $raw;
    }

    /** Terima e-mel ATAU nombor telefon; cari pengguna aktif & hantar pautan. */
    public function sendTo(string $identifier, ?string $ip = null): ?string
    {
        $user = $this->resolveUser($identifier);

        if (! $user || ! $user->is_active) {
            return null;
        }

        return $this->sendToUser($user, $ip);
    }

    /** Hantar pautan log masuk kepada pengguna aktif (e-mel + WhatsApp jika ada). */
    public function sendToUser(User $user, ?string $ip = null): ?string
    {
        if (! $user->is_active) {
            return null;
        }

        $raw = $this->createTokenForUser($user, $ip);
        $url = url('/masuk/'.$raw);

        if (filled($user->email)) {
            $this->sendEmail($user->email, $url);
        }

        $this->sendWhatsApp($user, $url);

        return $raw;
    }

    /** Sahkan & guna token mentah. Pulangkan User jika sah (dan tandakan used), atau null. */
    public function consume(string $rawToken, ?string $ip = null): ?User
    {
        $token = LoginToken::query()->where('token', hash('sha256', $rawToken))->first();

        if (! $token || ! $token->isValid()) {
            return null;
        }

        $user = $token->user_id
            ? User::query()->find($token->user_id)
            : User::query()->where('email', $token->email)->first();

        if (! $user || ! $user->is_active) {
            return null;
        }

        $token->update(['used_at' => now(), 'ip' => $ip ?? $token->ip]);

        return $user;
    }

    /** Cari pengguna melalui e-mel atau nombor telefon (dinormalkan). */
    protected function resolveUser(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if (preg_match('/^[+0-9][0-9 \-]*$/', $identifier)) {
            $phone = app(WhatsAppRecipientResolver::class)->normalize($identifier);

            return $phone ? User::query()->where('phone_wa', $phone)->first() : null;
        }

        return User::query()->where('email', mb_strtolower($identifier))->first();
    }

    protected function sendEmail(string $email, string $url): void
    {
        $html = '<p>Assalamualaikum,</p>'
            .'<p>Klik pautan di bawah untuk log masuk ke <strong>Diwan</strong> (sah 15 minit, sekali guna):</p>'
            .'<p><a href="'.e($url).'">'.e($url).'</a></p>'
            .'<p>Jika anda tidak memohon log masuk, sila abaikan e-mel ini.</p>';

        Mail::html($html, function ($message) use ($email) {
            $message->to($email)->subject('Pautan Log Masuk Diwan');
        });
    }

    /**
     * Hantar pautan melalui WhatsApp jika pengguna opt-in + ada sesi masjid
     * bersambung. Tiada kandungan dokumen (PDPA §14) — hanya pautan log masuk.
     */
    protected function sendWhatsApp(User $user, string $url): void
    {
        if (blank($user->phone_wa) || ! $user->notify_whatsapp) {
            return;
        }

        $to = app(WhatsAppRecipientResolver::class)->normalize($user->phone_wa);
        if (! $to) {
            return;
        }

        $session = $this->whatsAppSessionFor($user);
        if (! $session) {
            return;
        }

        SendWhatsAppJob::dispatch(
            $session['session_id'],
            $to,
            "Pautan log masuk Diwan (sah 15 minit, sekali guna): {$url}",
            $session['mosque_id'],
            $user->id,
            'magic_link',
        );
    }

    /**
     * Cari sesi WhatsApp untuk menghantar pautan: sesi masjid PERTAMA pengguna
     * yang sudah bersambung. (Fasa D menaik taraf → utamakan sesi platform.)
     *
     * @return array{session_id: string, mosque_id: int}|null
     */
    protected function whatsAppSessionFor(User $user): ?array
    {
        foreach ($user->mosques()->where('mosques.status', 'aktif')->get() as $mosque) {
            $integration = WhatsAppIntegration::query()->forMosque($mosque->id)->first();

            if ($integration?->isReady()) {
                return ['session_id' => $integration->session_id, 'mosque_id' => $mosque->id];
            }
        }

        return null;
    }
}
