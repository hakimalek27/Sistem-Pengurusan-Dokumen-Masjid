<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use App\Enums\OcrStatus;
use App\Enums\RecordDirection;
use App\Enums\RecordStatus;
use App\Enums\Sensitivity;
use App\Enums\SourceChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

// §5.7 records (TERAS)
class Record extends Model implements HasMedia
{
    use BelongsToMosque, HasFactory, HasUlids, InteractsWithMedia, Searchable, SoftDeletes;

    protected $fillable = [
        'mosque_id', 'ulid', 'registry_file_id', 'record_type', 'title', 'our_ref', 'their_ref',
        'record_date', 'received_date', 'direction', 'sender_name', 'sender_org', 'recipient_name',
        'sensitivity', 'status', 'enclosure_no', 'metadata', 'ocr_status', 'ocr_text', 'sha256',
        'source_channel', 'source_meta', 'created_by', 'filed_by', 'filed_at',
        'superseded_by_record_id', 'legal_hold', 'retention_due_at', 'retention_notified',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'received_date' => 'date',
            'sensitivity' => Sensitivity::class,
            'status' => RecordStatus::class,
            'direction' => RecordDirection::class,
            'ocr_status' => OcrStatus::class,
            'source_channel' => SourceChannel::class,
            'metadata' => 'array',
            'source_meta' => 'array',
            'retention_notified' => 'array',
            'enclosure_no' => 'integer',
            'legal_hold' => 'boolean',
            'filed_at' => 'datetime',
            'retention_due_at' => 'date',
        ];
    }

    protected $attributes = [
        'metadata' => '{}',
        'source_meta' => '{}',
        'retention_notified' => '{}',
    ];

    /** Jana ULID untuk kolum 'ulid' sahaja — 'id' kekal auto-increment PK (§5.7). */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    // ---- Media (§5.7 / §4.2) ----

    public function registerMediaCollections(): void
    {
        $disk = config('diwan.storage_disk', 'local');

        $this->addMediaCollection('original')->useDisk($disk)->singleFile();
        $this->addMediaCollection('derived')->useDisk($disk)->singleFile();
        $this->addMediaCollection('attachments')->useDisk($disk);
    }

    // ---- Scout (§13) ----

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'mosque_id' => $this->mosque_id,
            'title' => $this->title,
            'our_ref' => $this->our_ref,
            'their_ref' => $this->their_ref,
            'sender_name' => $this->sender_name,
            'sender_org' => $this->sender_org,
            'recipient_name' => $this->recipient_name,
            'record_type' => $this->record_type,
            'file_no' => $this->registryFile?->file_no,
            'registry_file_id' => $this->registry_file_id,
            'sensitivity' => $this->sensitivity?->value,
            'status' => $this->status?->value,
            'record_date' => $this->record_date?->timestamp,
            'ocr_text' => mb_substr($this->ocr_text ?? '', 0, 100_000),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return in_array($this->status, [RecordStatus::Difailkan, RecordStatus::Diganti], true);
    }

    /**
     * Senarai rekod yang benar-benar boleh dilihat pengguna dalam satu tenant.
     * Policy kekal gate per-rekod; scope ini mencegah metadata sulit bocor di senarai.
     */
    public function scopeVisibleTo(Builder $query, User $user, Mosque $mosque): Builder
    {
        $query->where($query->qualifyColumn('mosque_id'), $mosque->id);

        if ($user->is_superadmin) {
            return $query;
        }

        $role = $user->roleIn($mosque);

        if ($role === null || ! $user->canIn($mosque, 'records.view')) {
            return $query->whereRaw('1 = 0');
        }

        if (in_array($role, ['admin_masjid', 'kerani', 'pengerusi', 'setiausaha', 'nazir'], true)) {
            return $query;
        }

        return $query->where(function (Builder $allowed) use ($role, $user): void {
            $allowed->where(function (Builder $notSensitive): void {
                $notSensitive
                    ->where('sensitivity', '!=', Sensitivity::Sulit->value)
                    ->whereDoesntHave('registryFile', fn (Builder $file) => $file->where('sensitivity', Sensitivity::Sulit->value));
            });

            if ($role === 'bendahari') {
                $allowed->orWhereHas('registryFile.classificationNode', fn (Builder $node) => $node
                    ->where(function (Builder $prefix): void {
                        $prefix->where('code', 'like', '200%')->orWhere('code', 'like', '300%');
                    }));
            }

            $allowed->orWhereHas('registryFile.accessGrants', fn (Builder $grant) => $grant->where('user_id', $user->id));
        });
    }

    // ---- Relationships ----

    public function registryFile(): BelongsTo
    {
        return $this->belongsTo(RegistryFile::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function filedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_record_id');
    }

    public function supersedes(): HasMany
    {
        return $this->hasMany(self::class, 'superseded_by_record_id');
    }

    public function minits(): HasMany
    {
        return $this->hasMany(Minit::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function sensitiveAccessLogs(): HasMany
    {
        return $this->hasMany(SensitiveAccessLog::class);
    }
}
