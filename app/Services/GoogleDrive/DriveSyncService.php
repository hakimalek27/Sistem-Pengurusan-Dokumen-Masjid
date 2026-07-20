<?php

namespace App\Services\GoogleDrive;

use App\Contracts\DriveClient;
use App\Enums\RecordStatus;
use App\Models\ClassificationNode;
use App\Models\Mosque;
use App\Models\PlatformSetting;
use App\Models\Record;
use App\Models\RegistryFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * §4.6′ — Enjin mirror per-tenant ke Google Drive.
 * Pokok: SPDM/Backup/{slug}/{fungsi}/{aktiviti}/[{sub}]/{file_no - tajuk}/{fail}.
 *
 * ISOLASI (§15.2, keperluan #1): resolusi folder SENTIASA bermula dari folder
 * masjid yang disimpan (mosques.gdrive_folder_id) dan setiap hop klasifikasi
 * ditegaskan mosque_id sama. Sebuah rekod tidak boleh terpetakan ke folder tenant
 * lain — dijamin oleh refetch berskop mosque_id (job) + assertion di sini.
 */
class DriveSyncService
{
    public function __construct(protected DriveClient $drive) {}

    public function enabled(): bool
    {
        return $this->drive->isConnected();
    }

    /** Folder root SPDM/Backup (id disimpan platform_settings, dicipta sekali). */
    public function rootFolderId(): string
    {
        $existing = (string) PlatformSetting::get('gdrive_root_folder_id', '');
        if ($existing !== '' && $this->drive->exists($existing)) {
            return $existing;
        }

        $spdm = $this->drive->ensureFolder('root', 'SPDM');
        $backup = $this->drive->ensureFolder($spdm, 'Backup');
        PlatformSetting::put('gdrive_root_folder_id', $backup);

        return $backup;
    }

    /** Folder masjid (SPDM/Backup/{slug}); id disimpan mosques.gdrive_folder_id. */
    public function mosqueFolderId(Mosque $mosque): string
    {
        if ($mosque->gdrive_folder_id && $this->drive->exists($mosque->gdrive_folder_id)) {
            return $mosque->gdrive_folder_id;
        }

        $id = $this->drive->ensureFolder($this->rootFolderId(), $this->sanitize($mosque->slug));
        $mosque->forceFill(['gdrive_folder_id' => $id])->saveQuietly();

        return $id;
    }

    /** Folder fail registri: walk klasifikasi (max 3 aras) + folder file_no (isolasi ditegaskan). */
    public function folderIdForFile(RegistryFile $file): string
    {
        $mosque = Mosque::query()->findOrFail($file->mosque_id);
        $parent = $this->mosqueFolderId($mosque);

        foreach ($this->classificationChain($file) as $node) {
            $parent = $this->drive->ensureFolder($parent, $this->sanitize($node->code.' - '.$node->title));
        }

        if ($file->gdrive_folder_id && $this->drive->exists($file->gdrive_folder_id)) {
            return $file->gdrive_folder_id;
        }

        $folder = $this->drive->ensureFolder($parent, $this->sanitize($file->file_no.' - '.$file->title));
        $file->forceFill(['gdrive_folder_id' => $folder])->saveQuietly();

        return $folder;
    }

    /**
     * Rantaian nod klasifikasi dari fungsi ke daun. SETIAP hop ditegaskan tenant
     * sama — pertahanan berlapis terhadap kebocoran silang-tenant.
     *
     * @return array<int, ClassificationNode>
     */
    protected function classificationChain(RegistryFile $file): array
    {
        $chain = [];
        $node = $this->fetchNode((int) $file->classification_node_id, (int) $file->mosque_id);
        $guard = 0;

        while ($node && $guard++ < 5) {
            if ((int) $node->mosque_id !== (int) $file->mosque_id) {
                throw new RuntimeException('Isolasi Drive: nod klasifikasi tenant lain dikesan.');
            }
            array_unshift($chain, $node);
            $node = $node->parent_id ? $this->fetchNode((int) $node->parent_id, (int) $file->mosque_id) : null;
        }

        return $chain;
    }

    protected function fetchNode(int $nodeId, int $mosqueId): ?ClassificationNode
    {
        return ClassificationNode::query()->withoutGlobalScope('mosque')
            ->where('mosque_id', $mosqueId)
            ->find($nodeId);
    }

    /** Sync satu rekod (original + attachments) ke Drive. Idempotent + short-circuit sha256. */
    public function syncRecord(Record $record): void
    {
        if (! $this->enabled()
            || ! $record->registry_file_id
            || ! in_array($record->status, [RecordStatus::Difailkan, RecordStatus::Diganti], true)) {
            return;
        }

        $file = RegistryFile::query()->withoutGlobalScope('mosque')
            ->where('mosque_id', $record->mosque_id)
            ->findOrFail($record->registry_file_id);

        $folderId = $this->folderIdForFile($file);
        $meta = $record->gdrive_meta ?? [];

        $this->syncOriginal($record, $folderId, $meta);
        $this->syncAttachments($record, $folderId, $meta);

        // Bookkeeping TANPA menyentuh updated_at (elak reconcile self-trigger).
        $record->gdrive_meta = $meta;
        $record->gdrive_synced_at = now();
        $record->timestamps = false;
        $record->saveQuietly();
        $record->timestamps = true;
    }

    protected function syncOriginal(Record $record, string $folderId, array &$meta): void
    {
        $original = $record->getFirstMedia('original');
        if (! $original) {
            return;
        }

        $ext = pathinfo((string) $original->file_name, PATHINFO_EXTENSION);
        $name = $this->sanitize($record->enclosure_no.' - '.($record->title ?: 'dokumen')).($ext ? '.'.$ext : '');
        $sha = (string) ($original->getCustomProperty('sha256') ?: '');

        $hasRemote = $record->gdrive_file_id && $this->drive->exists($record->gdrive_file_id);

        if (! $hasRemote) {
            $record->gdrive_file_id = $this->drive->upload($folderId, $name, $this->mediaBytes($original), $this->mimeFor($original));
        } else {
            $contentChanged = $sha !== '' && ($meta['original_sha'] ?? null) !== $sha;
            $nameChanged = ($meta['original_name'] ?? null) !== $name;

            if ($contentChanged) {
                $this->drive->update($record->gdrive_file_id, $this->mediaBytes($original), $nameChanged ? $name : null);
            } elseif ($nameChanged) {
                $this->drive->update($record->gdrive_file_id, null, $name);
            }
            if (($meta['folder'] ?? null) !== $folderId) {
                $this->drive->move($record->gdrive_file_id, $folderId, $name);
            }
        }

        $meta['original_sha'] = $sha;
        $meta['original_name'] = $name;
        $meta['folder'] = $folderId;
    }

    protected function syncAttachments(Record $record, string $folderId, array &$meta): void
    {
        $map = $meta['attachments'] ?? [];

        foreach ($record->getMedia('attachments') as $att) {
            $key = (string) $att->id;
            if (isset($map[$key]) && $this->drive->exists($map[$key])) {
                continue;
            }
            $name = $this->sanitize($record->enclosure_no.' - Lampiran - '.$att->file_name);
            $map[$key] = $this->drive->upload($folderId, $name, $this->mediaBytes($att), $this->mimeFor($att));
        }

        $meta['attachments'] = $map;
    }

    /** Kumpul semua id Drive milik rekod (untuk pelupusan). */
    public function driveIdsFor(Record $record): array
    {
        $ids = [];
        if ($record->gdrive_file_id) {
            $ids[] = $record->gdrive_file_id;
        }
        foreach ((array) data_get($record->gdrive_meta, 'attachments', []) as $id) {
            $ids[] = $id;
        }

        return array_values(array_filter(array_unique($ids)));
    }

    /** Padam fail Drive (pelupusan). */
    public function deleteFiles(array $driveFileIds): void
    {
        if (! $this->enabled()) {
            return;
        }
        foreach (array_filter(array_unique($driveFileIds)) as $id) {
            $this->drive->delete($id);
        }
    }

    public function driveExists(string $fileId): bool
    {
        return $this->drive->exists($fileId);
    }

    /** Folder DB dump: SPDM/Backup/_Pangkalan-Data. */
    public function dumpFolderId(): string
    {
        return $this->drive->ensureFolder($this->rootFolderId(), '_Pangkalan-Data');
    }

    /**
     * Muat naik DB dump terkini dari disk backup (cos_backup) ke Drive (skip jika
     * nama sama sudah ada), kemudian prune supaya simpan $keep salinan terkini.
     */
    public function syncDatabaseDump(int $keep): void
    {
        if (! $this->enabled()) {
            return;
        }

        $disk = Storage::disk((string) (config('backup.backup.destination.disks')[0] ?? 'cos_backup'));
        $name = (string) config('backup.backup.name', 'diwan');

        $dumps = collect($disk->files($name))
            ->filter(fn ($f) => str_ends_with(strtolower($f), '.zip'))
            ->sort()->values();
        if ($dumps->isEmpty()) {
            return;
        }

        $folderId = $this->dumpFolderId();
        $existing = collect($this->drive->children($folderId))->pluck('name');

        $latest = (string) $dumps->last();
        $base = basename($latest);
        if (! $existing->contains($base)) {
            $this->drive->upload($folderId, $base, (string) $disk->get($latest), 'application/zip');
        }

        // Prune di Drive — simpan $keep terkini.
        $remote = collect($this->drive->children($folderId))
            ->filter(fn ($c) => str_ends_with(strtolower($c['name']), '.zip'))
            ->sortBy('name')->values();
        $excess = $remote->count() - max(1, $keep);
        for ($i = 0; $i < $excess; $i++) {
            $this->drive->delete($remote[$i]['id']);
        }
    }

    protected function mediaBytes($media): string
    {
        return (string) Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
    }

    protected function mimeFor($media): string
    {
        return $media->mime_type ?: 'application/octet-stream';
    }

    /** Namakan segmen folder/fail selamat: '/'→'-', buang kawalan, mampat spasi, had 120. */
    public function sanitize(string $value): string
    {
        $value = str_replace(['/', '\\'], '-', $value);
        // Buang aksara kawalan KECUALI whitespace (\t \n \r) yang dimampatkan jadi ruang.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        return mb_substr($value === '' ? 'tanpa-nama' : $value, 0, 120);
    }
}
