<?php

namespace App\Services;

use App\Models\User;

/**
 * §9.A — SATU sumber kebenaran untuk "ke mana pengguna pergi selepas berjaya log masuk".
 *
 * Dipakai oleh KEDUA-DUA laluan log masuk supaya keduanya tidak boleh menyimpang:
 *   • magic link  — App\Http\Controllers\MagicLoginController (§15.1)
 *   • kata laluan — App\Filament\Auth\Login (kedua-dua panel)
 *
 * Sebelum ini hanya magic link tahu peraturan ini. Log masuk kata laluan bergantung pada
 * lalai Filament (`redirect()->intended(Filament::getUrl())`) yang menggunakan panel
 * SEMASA, jadi superadmin yang masuk melalui /app/login mendarat dalam masjid tenant
 * lalai — bukan /admin. Dibuktikan pada produksi: log nginx 24 jam menunjukkan
 * 1× GET /app/login dan 0× GET /admin/login (tiada halaman awam memaut ke /admin/login).
 */
class PanelLandingResolver
{
    /**
     * Pendaratan §9.A: superadmin → /admin; 1 masjid → /app/{slug}; >1 atau 0 → pemilih tenant.
     *
     * $withOnboarding = lonjakan §10 Aliran I ke wizard persediaan. SENGAJA berbeza antara
     * dua laluan, dan perbezaan itu diuji:
     *   • magic link (jemputan)  → TRUE. Tugas pautan jemputan termasuk membawa admin masjid
     *     baharu terus ke wizard.
     *   • log masuk kata laluan  → FALSE. Pengguna yang kembali mendarat di papan pemuka;
     *     banner persediaan (render hook PAGE_START) tetap menuntun mereka sehingga selesai.
     *     Menjadikannya TRUE akan menukar kelakuan produksi jauh melebihi BUG-A dan
     *     memindahkan destinasi log masuk setiap admin masjid yang belum selesai persediaan.
     */
    public function urlFor(User $user, bool $withOnboarding = true): string
    {
        if ($user->is_superadmin) {
            return '/admin';
        }

        $mosques = $user->mosques()->where('mosques.status', 'aktif')->get();

        if ($mosques->count() === 1) {
            $mosque = $mosques->first();

            // §10 Aliran I — admin masjid yang belum selesai persediaan dibawa
            // terus ke wizard onboarding (auto-buka melalui ?mula=1).
            if ($withOnboarding
                && blank(data_get($mosque->settings, 'onboarding_done'))
                && $user->canIn($mosque, 'mosque.settings')) {
                return '/app/'.$mosque->slug.'/persediaan?mula=1';
            }

            return '/app/'.$mosque->slug;
        }

        return '/app';
    }

    /**
     * Pendaratan untuk pengguna yang sedang log masuk, atau null jika tetamu.
     * Digunakan oleh layout tetamu supaya halaman awam boleh menawarkan "Ke Panel"
     * dan tidak menghantar pengguna bersesi kembali ke borang log masuk.
     */
    public static function urlForCurrentUser(): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        // Akaun magic-link yang belum menetapkan kata laluan TIDAK boleh masuk panel:
        // EnsurePasswordIsSet melantunkannya balik ke /tetapkan-kata-laluan (dan halaman
        // itu sendiri memakai layout tetamu). Menawarkan "Ke Panel" di sana bermakna
        // menjemput lantunan. Syarat dicerminkan TEPAT daripada middleware itu.
        if ($user->password === null) {
            return null;
        }

        // Navigasi, bukan log masuk → tiada lonjakan wizard (sama seperti log masuk kata laluan).
        return app(static::class)->urlFor($user, withOnboarding: false);
    }
}
