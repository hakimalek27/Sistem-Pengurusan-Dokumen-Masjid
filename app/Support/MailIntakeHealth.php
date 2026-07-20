<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Carbon;

/**
 * §11.3 — Kesihatan intake e-mel (IMAP), satu sumber kebenaran untuk command
 * alert, widget dashboard dan halaman Status Sambungan.
 *
 * KENAPA WUJUD: sebelum ini kesihatan dinilai daripada `imap_failure_streak`
 * SAHAJA. Streak hanya bertambah apabila FetchMailJob BERJALAN — jadi apabila
 * job langsung tidak dijalankan (mutex jadual tersangkut, 19-20 Jul: ~14 jam),
 * streak kekal 0 dan setiap penunjuk memaparkan "OK" hijau sedangkan intake
 * mati sepenuhnya. Kelas ini menambah "detak jantung" (imap_last_success_at)
 * supaya keadaan TERSEKAT dapat dibezakan daripada SIHAT.
 */
class MailIntakeHealth
{
    /** Ambang tanpa larian berjaya sebelum intake dikira tersekat. */
    public const STALE_AFTER_MINUTES = 30;

    public const STATE_DISABLED = 'disabled';

    public const STATE_OK = 'ok';

    public const STATE_FAILING = 'failing';

    public const STATE_STALLED = 'stalled';

    /**
     * @return array{state:string,label:string,description:string,color:string,streak:int,last_success_at:?Carbon,minutes_since:?int}
     */
    public static function evaluate(): array
    {
        $enabled = (bool) config('diwan.imap_enabled');
        $streak = (int) PlatformSetting::get('imap_failure_streak', 0);
        $lastSuccess = self::lastSuccessAt();
        $minutesSince = $lastSuccess?->diffInMinutes(now());

        if (! $enabled) {
            return self::state(self::STATE_DISABLED, 'Dimatikan', 'IMAP_ENABLED=false', 'gray', $streak, $lastSuccess, $minutesSince);
        }

        // Kegagalan sambungan aktif diutamakan: puncanya diketahui (kredential/rangkaian).
        if ($streak > 0) {
            return self::state(self::STATE_FAILING, "Gagal ({$streak}×)", 'Semak App Password', 'danger', $streak, $lastSuccess, $minutesSince);
        }

        // Tiada detak jantung langsung (belum pernah berjaya sejak ciri ini
        // dipasang) ATAU detak terakhir terlalu lama → intake tersekat senyap.
        if ($lastSuccess === null) {
            return self::state(self::STATE_STALLED, 'Tiada data', 'Belum ada larian berjaya direkod', 'warning', $streak, null, null);
        }

        if ($minutesSince > self::STALE_AFTER_MINUTES) {
            return self::state(
                self::STATE_STALLED,
                'Tersekat',
                "Tiada larian berjaya sejak {$minutesSince} minit lalu",
                'danger',
                $streak,
                $lastSuccess,
                $minutesSince,
            );
        }

        return self::state(self::STATE_OK, 'OK', 'Poll setiap minit', 'success', $streak, $lastSuccess, $minutesSince);
    }

    public static function lastSuccessAt(): ?Carbon
    {
        $raw = PlatformSetting::get('imap_last_success_at');

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        return rescue(fn () => Carbon::parse($raw), null, report: false);
    }

    /** Keadaan yang wajar mencetuskan alert superadmin. */
    public static function isUnhealthy(string $state): bool
    {
        return in_array($state, [self::STATE_FAILING, self::STATE_STALLED], true);
    }

    /**
     * @return array{state:string,label:string,description:string,color:string,streak:int,last_success_at:?Carbon,minutes_since:?int}
     */
    protected static function state(
        string $state,
        string $label,
        string $description,
        string $color,
        int $streak,
        ?Carbon $lastSuccess,
        ?int $minutesSince,
    ): array {
        return [
            'state' => $state,
            'label' => $label,
            'description' => $description,
            'color' => $color,
            'streak' => $streak,
            'last_success_at' => $lastSuccess,
            'minutes_since' => $minutesSince,
        ];
    }
}
