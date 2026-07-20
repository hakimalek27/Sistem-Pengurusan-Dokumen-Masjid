<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Services\MagicLinkService;

/**
 * §15.1 — Pautan deep-link magic dalam notifikasi: penerima (User aktif) yang
 * di-mention/ditugaskan klik pautan → auto-login + terus ke sasaran (rekod/minit)
 * tanpa perlu log masuk manual.
 *
 * Dimemo per penerima supaya SATU token sahaja dijana walaupun dipanggil merentas
 * saluran (mail + WhatsApp + Telegram) untuk penerima yang sama. Penerima bukan
 * User sebenar atau tidak aktif → pautan biasa (perlu log masuk).
 */
trait HasMagicDeepLink
{
    /** @var array<int|string, string> */
    protected array $magicLinks = [];

    protected function deepLink(object $notifiable, string $target): string
    {
        if (! $notifiable instanceof User || ! ($notifiable->is_active ?? false)) {
            return url($target);
        }

        return $this->magicLinks[$notifiable->getKey()]
            ??= app(MagicLinkService::class)->deepLinkFor($notifiable, $target);
    }
}
