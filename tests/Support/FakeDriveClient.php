<?php

namespace Tests\Support;

use App\Contracts\DriveClient;
use RuntimeException;

/**
 * Klien Drive palsu (pokok dalam-memori) untuk ujian — tiada panggilan Google.
 * ensureFolder/upload MENEGASKAN induk wujud → menangkap pepijat folder tak
 * teresolusi (asas ujian isolasi tenant). Helper pathOf()/filesUnder() untuk
 * assertion susunan folder.
 */
class FakeDriveClient implements DriveClient
{
    /** @var array<string, array{name:string,parent:?string,mime:string,content:?string,trashed:bool}> */
    public array $nodes = [];

    public int $seq = 0;

    public bool $connected = true;

    public function __construct()
    {
        // 'root' = My Drive (induk terunggul).
        $this->nodes['root'] = ['name' => 'My Drive', 'parent' => null, 'mime' => 'application/vnd.google-apps.folder', 'content' => null, 'trashed' => false];
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function ensureFolder(string $parentId, string $name): string
    {
        $this->assertParent($parentId);

        foreach ($this->nodes as $id => $n) {
            if (! $n['trashed'] && $n['parent'] === $parentId && $n['name'] === $name && $this->isFolder($n)) {
                return $id;
            }
        }

        return $this->put($parentId, $name, 'application/vnd.google-apps.folder', null);
    }

    public function upload(string $parentId, string $name, string $contents, string $mime): string
    {
        $this->assertParent($parentId);

        return $this->put($parentId, $name, $mime, $contents);
    }

    public function update(string $fileId, ?string $contents = null, ?string $newName = null): void
    {
        $this->assertExists($fileId);
        if ($contents !== null) {
            $this->nodes[$fileId]['content'] = $contents;
        }
        if ($newName !== null) {
            $this->nodes[$fileId]['name'] = $newName;
        }
    }

    public function move(string $fileId, string $newParentId, ?string $newName = null): void
    {
        $this->assertExists($fileId);
        $this->assertParent($newParentId);
        $this->nodes[$fileId]['parent'] = $newParentId;
        if ($newName !== null) {
            $this->nodes[$fileId]['name'] = $newName;
        }
    }

    public function delete(string $fileId): void
    {
        // 404 (sudah tiada) = berjaya.
        unset($this->nodes[$fileId]);
    }

    public function children(string $parentId): array
    {
        $out = [];
        foreach ($this->nodes as $id => $n) {
            if (! $n['trashed'] && $n['parent'] === $parentId) {
                $out[] = ['id' => $id, 'name' => $n['name'], 'mimeType' => $n['mime']];
            }
        }

        return $out;
    }

    public function exists(string $fileId): bool
    {
        return isset($this->nodes[$fileId]) && ! $this->nodes[$fileId]['trashed'];
    }

    public function about(): array
    {
        return ['email' => 'ujian@gmail.com', 'limit' => 15 * (1024 ** 3), 'usage' => 0];
    }

    // ---- Pembantu ujian ----

    /** Laluan boleh-baca dari root (tanpa "My Drive"), cth "SPDM/Backup/mam/…". */
    public function pathOf(string $id): string
    {
        $parts = [];
        $cursor = $id;
        while ($cursor !== null && $cursor !== 'root' && isset($this->nodes[$cursor])) {
            array_unshift($parts, $this->nodes[$cursor]['name']);
            $cursor = $this->nodes[$cursor]['parent'];
        }

        return implode('/', $parts);
    }

    /** @return array<int, string> nama fail (bukan folder) di bawah $parentId */
    public function filesUnder(string $parentId): array
    {
        $out = [];
        foreach ($this->nodes as $n) {
            if (! $n['trashed'] && $n['parent'] === $parentId && ! $this->isFolder($n)) {
                $out[] = $n['name'];
            }
        }

        return $out;
    }

    public function contentOf(string $id): ?string
    {
        return $this->nodes[$id]['content'] ?? null;
    }

    private function put(string $parentId, string $name, string $mime, ?string $content): string
    {
        $id = 'node-'.(++$this->seq);
        $this->nodes[$id] = ['name' => $name, 'parent' => $parentId, 'mime' => $mime, 'content' => $content, 'trashed' => false];

        return $id;
    }

    private function isFolder(array $node): bool
    {
        return $node['mime'] === 'application/vnd.google-apps.folder';
    }

    private function assertParent(string $parentId): void
    {
        if (! isset($this->nodes[$parentId]) || $this->nodes[$parentId]['trashed']) {
            throw new RuntimeException("FakeDriveClient: induk '{$parentId}' tidak wujud (folder tak teresolusi).");
        }
    }

    private function assertExists(string $fileId): void
    {
        if (! isset($this->nodes[$fileId])) {
            throw new RuntimeException("FakeDriveClient: fail '{$fileId}' tidak wujud.");
        }
    }
}
