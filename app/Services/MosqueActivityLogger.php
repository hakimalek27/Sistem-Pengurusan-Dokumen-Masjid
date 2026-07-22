<?php

namespace App\Services;

use App\Enums\SourceChannel;
use App\Models\Mosque;
use App\Models\MosqueActivityLog;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;

class MosqueActivityLogger
{
    /**
     * Tulis snapshot append-only. Semua medan tenant dan paparan ditentukan
     * ketika peristiwa berlaku supaya sejarah kekal boleh dibaca selepas data berubah.
     */
    public function log(
        Mosque $mosque,
        string $action,
        string $description,
        ?User $actor = null,
        ?Model $subject = null,
        ?Record $record = null,
        ?RegistryFile $file = null,
        array $metadata = [],
        SourceChannel|string|null $source = null,
        ?string $sourceIdentifier = null,
        ?string $ip = null,
    ): MosqueActivityLog {
        if ($record && $record->mosque_id !== $mosque->id) {
            throw new \InvalidArgumentException('Rekod log bukan milik tenant ini.');
        }
        if ($file && $file->mosque_id !== $mosque->id) {
            throw new \InvalidArgumentException('Fail log bukan milik tenant ini.');
        }
        if ($subject && $subject->getAttribute('mosque_id') !== null
            && (int) $subject->getAttribute('mosque_id') !== (int) $mosque->id) {
            throw new \InvalidArgumentException('Subjek log bukan milik tenant ini.');
        }

        $file ??= $record?->registryFile;
        $source ??= $record?->source_channel;
        $sourceValue = $source instanceof SourceChannel ? $source->value : $source;
        $sourceIdentifier ??= $record ? $this->sourceIdentifier($record) : null;
        $ip ??= $this->requestIp();

        return MosqueActivityLog::query()->withoutGlobalScope('mosque')->create([
            'mosque_id' => $mosque->id,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_role' => $actor ? Roles::label($actor->roleIn($mosque) ?? '') : null,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'record_id' => $record?->id,
            'record_title' => $record?->title,
            'record_reference' => $record ? $this->recordReference($record) : null,
            'registry_file_id' => $file?->id,
            'file_no' => $file?->file_no,
            'file_title' => $file?->title,
            'source_channel' => $sourceValue,
            'source_identifier' => $sourceIdentifier,
            'ip_address' => $ip,
            'metadata' => $metadata ?: null,
        ]);
    }

    public function actorLabel(?User $actor, Mosque $mosque): string
    {
        if (! $actor) {
            return 'Sistem';
        }

        $role = $actor->roleIn($mosque);

        return $role ? $actor->name.' ('.Roles::label($role).')' : $actor->name;
    }

    public function sourceLabel(SourceChannel|string|null $source): string
    {
        $value = $source instanceof SourceChannel ? $source->value : $source;

        return match ($value) {
            SourceChannel::MuatNaik->value => 'Dashboard',
            SourceChannel::Emel->value => 'e-mel',
            SourceChannel::WhatsApp->value => 'WhatsApp',
            SourceChannel::Imbasan->value => 'imbasan',
            default => 'sistem',
        };
    }

    protected function sourceIdentifier(Record $record): ?string
    {
        $meta = $record->source_meta ?? [];

        return match ($record->source_channel) {
            SourceChannel::Emel, SourceChannel::WhatsApp => data_get($meta, 'from'),
            SourceChannel::MuatNaik, SourceChannel::Imbasan => $record->createdBy?->name,
            default => null,
        };
    }

    protected function recordReference(Record $record): ?string
    {
        if ($record->our_ref) {
            return $record->our_ref;
        }

        if ($record->registryFile?->file_no && $record->enclosure_no) {
            return $record->registryFile->file_no.'('.$record->enclosure_no.')';
        }

        return $record->ulid ? '#'.strtoupper(substr($record->ulid, -6)) : null;
    }

    protected function requestIp(): ?string
    {
        return app()->runningInConsole() ? null : request()->ip();
    }
}
