<?php

namespace App\Services\GoogleDrive;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

/**
 * §4.6′ — Konfigurasi Google Drive (kredensial OAuth + bendera) daripada
 * platform_settings (client_secret + refresh_token tersulit). Dicache 5 minit
 * (corak sama TelegramService). forget() selepas simpan/putus.
 */
class DriveConfig
{
    public const CACHE_KEY = 'platform:gdrive';

    /** @return array{client_id:string,client_secret:string,refresh_token:string,enabled:bool,keep_dumps:int} */
    public static function all(bool $useCache = true): array
    {
        $read = fn (): array => [
            'client_id' => (string) PlatformSetting::get('gdrive_client_id', ''),
            'client_secret' => (string) PlatformSetting::getEncrypted('gdrive_client_secret'),
            'refresh_token' => (string) PlatformSetting::getEncrypted('gdrive_refresh_token'),
            'enabled' => (bool) PlatformSetting::get('gdrive_enabled', false),
            'keep_dumps' => (int) PlatformSetting::get('gdrive_keep_dumps', 7),
        ];

        try {
            return $useCache ? Cache::remember(self::CACHE_KEY, 300, $read) : $read();
        } catch (\Throwable $e) {
            return ['client_id' => '', 'client_secret' => '', 'refresh_token' => '', 'enabled' => false, 'keep_dumps' => 7];
        }
    }

    /** Client id + secret sudah diisi (boleh mula aliran OAuth). */
    public static function configured(): bool
    {
        $c = self::all();

        return $c['client_id'] !== '' && $c['client_secret'] !== '';
    }

    /** Sudah disambung (ada refresh token). */
    public static function connected(): bool
    {
        return self::all()['refresh_token'] !== '';
    }

    /** Diaktifkan DAN disambung DAN litar tidak terputus — mirror aktif. */
    public static function enabled(): bool
    {
        $c = self::all();

        if ($c['enabled'] !== true || $c['refresh_token'] === '') {
            return false;
        }

        // Pemutus litar: selepas ralat maut (token dibatal / kuota penuh) reconcile
        // menetapkan bendera ini (6 jam) supaya job berhenti hammer sehingga pulih.
        return ! Cache::get('gdrive_circuit', false);
    }

    public static function keepDumps(): int
    {
        return max(1, self::all()['keep_dumps']);
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
