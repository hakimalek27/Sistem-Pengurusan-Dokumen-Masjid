<?php

namespace App\Services\GoogleDrive;

use App\Models\PlatformSetting;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * §4.6′ — Aliran OAuth Google (akaun pemilik). Superadmin klik "Sambung" →
 * consent Google → callback tukar kod jadi refresh token (disimpan tersulit).
 * access_type=offline + prompt=consent memaksa refresh token dikembalikan.
 */
class GoogleOAuthService
{
    public function authUrl(string $state): string
    {
        $client = $this->baseClient();
        $client->setScopes([Drive::DRIVE_FILE]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setState($state);

        return $client->createAuthUrl();
    }

    /**
     * Tukar kod kebenaran jadi token; ambil maklumat akaun.
     *
     * @return array{refresh_token:?string, email:?string, limit:?int, usage:?int}
     */
    public function exchangeCode(string $code): array
    {
        $client = $this->baseClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            throw new RuntimeException('Google OAuth: '.($token['error_description'] ?? $token['error']));
        }
        $client->setAccessToken($token);

        $service = new Drive($client);
        $about = $service->about->get(['fields' => 'user(emailAddress),storageQuota(limit,usage)']);
        $quota = $about->getStorageQuota();

        return [
            'refresh_token' => $token['refresh_token'] ?? $client->getRefreshToken(),
            'email' => $about->getUser()?->getEmailAddress(),
            'limit' => $quota?->getLimit() !== null ? (int) $quota->getLimit() : null,
            'usage' => $quota?->getUsage() !== null ? (int) $quota->getUsage() : null,
        ];
    }

    /** @param array{refresh_token:?string, email:?string, limit:?int, usage:?int} $result */
    public function storeConnection(array $result): void
    {
        // Google tidak sentiasa kembalikan refresh_token baharu — kekalkan yang
        // sedia ada jika kosong (prompt=consent sepatutnya memaksa satu).
        if (! empty($result['refresh_token'])) {
            PlatformSetting::putEncrypted('gdrive_refresh_token', $result['refresh_token']);
        }

        PlatformSetting::put('gdrive_account', [
            'email' => $result['email'] ?? null,
            'limit' => $result['limit'] ?? null,
            'usage' => $result['usage'] ?? null,
            'connected_at' => now()->toIso8601String(),
        ]);
        PlatformSetting::put('gdrive_status', ['ok' => true, 'at' => now()->toIso8601String()]);
        Cache::forget('gdrive_circuit');
        Cache::forget('gdrive_alerted');
        DriveConfig::forget();
    }

    private function baseClient(): Client
    {
        $cfg = DriveConfig::all(false);
        if ($cfg['client_id'] === '' || $cfg['client_secret'] === '') {
            throw new RuntimeException('Client ID/Secret Google Drive belum ditetapkan.');
        }

        $client = new Client;
        $client->setClientId($cfg['client_id']);
        $client->setClientSecret($cfg['client_secret']);
        $client->setRedirectUri(route('gdrive.callback'));

        return $client;
    }
}
