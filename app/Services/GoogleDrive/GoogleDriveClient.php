<?php

namespace App\Services\GoogleDrive;

use App\Contracts\DriveClient;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Exception as GoogleServiceException;
use RuntimeException;

/**
 * §4.6′ — Implementasi sebenar DriveClient melalui google/apiclient (skop
 * drive.file — least privilege; hanya fail yang dicipta aplikasi ini kelihatan).
 * Kredensial dari platform_settings (DriveConfig). Refresh token auto bila tamat.
 * Retry pada ralat sementara (429/5xx / 403 kadar).
 */
class GoogleDriveClient implements DriveClient
{
    private ?Client $client = null;

    private ?Drive $service = null;

    private string $refreshToken = '';

    public function isConnected(): bool
    {
        return DriveConfig::enabled();
    }

    public function ensureFolder(string $parentId, string $name): string
    {
        // Cari TEPAT di bawah induk yang diberi (bukan carian global) — isolasi.
        $q = sprintf(
            "'%s' in parents and name = '%s' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            $parentId,
            $this->escape($name),
        );

        $found = $this->withRetry(fn () => $this->service()->files->listFiles([
            'q' => $q,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
            'spaces' => 'drive',
        ]));

        if (count($found->getFiles()) > 0) {
            return $found->getFiles()[0]->getId();
        }

        $folder = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);

        return $this->withRetry(fn () => $this->service()->files->create($folder, ['fields' => 'id']))->getId();
    }

    public function upload(string $parentId, string $name, string $contents, string $mime): string
    {
        $file = new DriveFile(['name' => $name, 'parents' => [$parentId]]);

        return $this->withRetry(fn () => $this->service()->files->create($file, [
            'data' => $contents,
            'mimeType' => $mime,
            'uploadType' => 'multipart',
            'fields' => 'id',
        ]))->getId();
    }

    public function update(string $fileId, ?string $contents = null, ?string $newName = null): void
    {
        $file = new DriveFile;
        if ($newName !== null) {
            $file->setName($newName);
        }

        $params = ['fields' => 'id'];
        if ($contents !== null) {
            $params['data'] = $contents;
            $params['uploadType'] = 'multipart';
        }

        $this->withRetry(fn () => $this->service()->files->update($fileId, $file, $params));
    }

    public function move(string $fileId, string $newParentId, ?string $newName = null): void
    {
        $current = $this->withRetry(fn () => $this->service()->files->get($fileId, ['fields' => 'parents']));
        $prev = implode(',', $current->getParents() ?? []);

        $file = new DriveFile;
        if ($newName !== null) {
            $file->setName($newName);
        }

        $this->withRetry(fn () => $this->service()->files->update($fileId, $file, [
            'addParents' => $newParentId,
            'removeParents' => $prev,
            'fields' => 'id,parents',
        ]));
    }

    public function delete(string $fileId): void
    {
        try {
            $this->withRetry(fn () => $this->service()->files->delete($fileId));
        } catch (GoogleServiceException $e) {
            if ($e->getCode() !== 404) {
                throw $e;
            }
        }
    }

    public function children(string $parentId): array
    {
        $out = [];
        $pageToken = null;

        do {
            $res = $this->withRetry(fn () => $this->service()->files->listFiles([
                'q' => sprintf("'%s' in parents and trashed = false", $parentId),
                'fields' => 'nextPageToken, files(id,name,mimeType)',
                'pageToken' => $pageToken,
                'pageSize' => 1000,
                'spaces' => 'drive',
            ]));

            foreach ($res->getFiles() as $f) {
                $out[] = ['id' => $f->getId(), 'name' => $f->getName(), 'mimeType' => $f->getMimeType()];
            }
            $pageToken = $res->getNextPageToken();
        } while ($pageToken);

        return $out;
    }

    public function exists(string $fileId): bool
    {
        try {
            $f = $this->withRetry(fn () => $this->service()->files->get($fileId, ['fields' => 'id,trashed']));

            return ! $f->getTrashed();
        } catch (GoogleServiceException $e) {
            if ($e->getCode() === 404) {
                return false;
            }
            throw $e;
        }
    }

    public function about(): array
    {
        $about = $this->withRetry(fn () => $this->service()->about->get(['fields' => 'user(emailAddress),storageQuota(limit,usage)']));
        $quota = $about->getStorageQuota();

        return [
            'email' => $about->getUser()?->getEmailAddress(),
            'limit' => $quota?->getLimit() !== null ? (int) $quota->getLimit() : null,
            'usage' => $quota?->getUsage() !== null ? (int) $quota->getUsage() : null,
        ];
    }

    private function client(): Client
    {
        if ($this->client === null) {
            $cfg = DriveConfig::all();
            $c = new Client;
            $c->setClientId($cfg['client_id']);
            $c->setClientSecret($cfg['client_secret']);
            $c->setScopes([Drive::DRIVE_FILE]);
            $c->setAccessType('offline');
            $this->refreshToken = $cfg['refresh_token'];
            $this->client = $c;
        }

        if ($this->client->isAccessTokenExpired()) {
            if ($this->refreshToken === '') {
                throw new RuntimeException('Google Drive belum disambung (tiada refresh token).');
            }
            $token = $this->client->fetchAccessTokenWithRefreshToken($this->refreshToken);
            if (isset($token['error'])) {
                throw new RuntimeException('Google Drive: '.($token['error_description'] ?? $token['error']));
            }
        }

        return $this->client;
    }

    private function service(): Drive
    {
        $this->client(); // pastikan token segar

        return $this->service ??= new Drive($this->client);
    }

    /** Ulang cuba pada ralat sementara Google (429/5xx / 403 kadar). */
    private function withRetry(callable $fn, int $tries = 4)
    {
        $delayMs = 500;

        for ($i = 1; ; $i++) {
            try {
                return $fn();
            } catch (GoogleServiceException $e) {
                $code = $e->getCode();
                $transient = in_array($code, [429, 500, 502, 503], true)
                    || ($code === 403 && str_contains(strtolower($e->getMessage()), 'rate'));

                if (! $transient || $i >= $tries) {
                    throw $e;
                }
                usleep($delayMs * 1000);
                $delayMs *= 2;
            }
        }
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
