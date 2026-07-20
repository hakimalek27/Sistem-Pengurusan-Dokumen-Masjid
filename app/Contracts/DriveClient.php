<?php

namespace App\Contracts;

/**
 * Abstraksi klien Google Drive supaya enjin sync (DriveSyncService/jobs) tidak
 * bergantung terus kepada google/apiclient — membolehkan FakeDriveClient dalam
 * ujian (tiada panggilan Google sebenar) + jaminan isolasi tenant boleh diuji.
 *
 * PENTING (§15.2): resolusi folder mesti SENTIASA melalui id induk yang diketahui
 * (ensureFolder(parentId, name)) — JANGAN sesekali cari dari root ikut nama.
 * Ini menjamin folder satu tenant tidak boleh terpetakan ke tenant lain.
 */
interface DriveClient
{
    /** Adakah integrasi Drive dikonfigur + disambung (refresh token ada + diaktifkan)? */
    public function isConnected(): bool;

    /** Cari folder bernama $name TEPAT DI BAWAH $parentId; cipta jika tiada. Pulang id folder. */
    public function ensureFolder(string $parentId, string $name): string;

    /** Muat naik fail baharu di bawah $parentId. Pulang id fail Drive. */
    public function upload(string $parentId, string $name, string $contents, string $mime): string;

    /** Kemas kini kandungan dan/atau nama fail sedia ada. */
    public function update(string $fileId, ?string $contents = null, ?string $newName = null): void;

    /** Pindah fail ke induk baharu (dan/atau namakan semula). */
    public function move(string $fileId, string $newParentId, ?string $newName = null): void;

    /** Padam fail/folder. 404 (sudah tiada) dianggap berjaya. */
    public function delete(string $fileId): void;

    /**
     * Senarai kandungan langsung sesuatu folder.
     *
     * @return array<int, array{id: string, name: string, mimeType: string}>
     */
    public function children(string $parentId): array;

    /** Adakah fail/folder ini masih wujud (tidak trashed)? */
    public function exists(string $fileId): bool;

    /**
     * Maklumat akaun tersambung (untuk kad status superadmin + semakan kuota).
     *
     * @return array{email: ?string, limit: ?int, usage: ?int}
     */
    public function about(): array;
}
