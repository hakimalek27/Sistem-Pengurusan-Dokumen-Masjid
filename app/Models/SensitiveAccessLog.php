<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

// §5.16 sensitive_access_logs (+mosque_id, +is_superadmin) — created_at sahaja
class SensitiveAccessLog extends Model
{
    use BelongsToMosque;

    const UPDATED_AT = null;

    protected $fillable = [
        'mosque_id', 'is_superadmin', 'user_id', 'record_id', 'action', 'ip', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'is_superadmin' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Log akses sulit tidak boleh diubah.'));
        static::deleting(fn () => throw new LogicException('Log akses sulit tidak boleh dipadam.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }
}
