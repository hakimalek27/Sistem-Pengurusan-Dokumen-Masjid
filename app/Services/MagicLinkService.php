<?php

namespace App\Services;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * §15.1 — Magic link: token 64 aksara rawak, simpan HASH SHA-256 sahaja,
 * luput 15 minit, sekali guna, IP direkod.
 */
class MagicLinkService
{
    /** Jana token & simpan hash. Pulangkan token MENTAH (untuk pautan/ujian). */
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

    /** Hantar pautan log masuk kepada pengguna aktif. Pulangkan token mentah atau null. */
    public function sendTo(string $email, ?string $ip = null): ?string
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! $user->is_active) {
            return null;
        }

        $raw = $this->createToken($email, $ip);
        $url = url('/masuk/'.$raw);

        $html = '<p>Assalamualaikum,</p>'
            .'<p>Klik pautan di bawah untuk log masuk ke <strong>Diwan</strong> (sah 15 minit, sekali guna):</p>'
            .'<p><a href="'.e($url).'">'.e($url).'</a></p>'
            .'<p>Jika anda tidak memohon log masuk, sila abaikan e-mel ini.</p>';

        Mail::html($html, function ($message) use ($email) {
            $message->to($email)->subject('Pautan Log Masuk Diwan');
        });

        return $raw;
    }

    /** Sahkan & guna token mentah. Pulangkan User jika sah (dan tandakan used), atau null. */
    public function consume(string $rawToken, ?string $ip = null): ?User
    {
        $token = LoginToken::query()->where('token', hash('sha256', $rawToken))->first();

        if (! $token || ! $token->isValid()) {
            return null;
        }

        $user = User::query()->where('email', $token->email)->first();

        if (! $user || ! $user->is_active) {
            return null;
        }

        $token->update(['used_at' => now(), 'ip' => $ip ?? $token->ip]);

        return $user;
    }
}
