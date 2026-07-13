<?php

namespace App\Services;

use App\Models\User;

/** Resolve nombor dan opt-in WhatsApp mengikut keahlian tenant, bukan tetapan global sahaja. */
class WhatsAppRecipientResolver
{
    public function resolve(User $user, ?int $mosqueId): ?string
    {
        if (! $user->is_active) {
            return null;
        }

        if ($mosqueId === null || $user->is_superadmin) {
            return $user->notify_whatsapp ? $this->normalize($user->phone_wa) : null;
        }

        $membership = $user->mosques()->where('mosques.id', $mosqueId)->first();
        if (! $membership || ! (bool) $membership->pivot->notify_whatsapp) {
            return null;
        }

        // Fail-closed: nombor global tidak boleh bocor masuk ke tenant yang
        // belum menetapkan nombor keahliannya sendiri.
        return $this->normalize($membership->pivot->phone_wa);
    }

    public function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';
        if (str_starts_with($digits, '0')) {
            $digits = '60'.substr($digits, 1);
        }

        return strlen($digits) >= 8 && strlen($digits) <= 15 ? $digits : null;
    }
}
