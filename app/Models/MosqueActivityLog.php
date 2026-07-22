<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class MosqueActivityLog extends Model
{
    use BelongsToMosque;

    const UPDATED_AT = null;

    protected $fillable = [
        'mosque_id', 'actor_id', 'actor_name', 'actor_role', 'action', 'description',
        'subject_type', 'subject_id', 'record_id', 'record_title', 'record_reference',
        'registry_file_id', 'file_no', 'file_title', 'source_channel',
        'source_identifier', 'ip_address', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Log aktiviti masjid tidak boleh diubah.'));
        static::deleting(fn () => throw new LogicException('Log aktiviti masjid tidak boleh dipadam.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function registryFile(): BelongsTo
    {
        return $this->belongsTo(RegistryFile::class);
    }
}
