<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// §5.16 notification_logs (+mosque_id nullable) — created_at sahaja
// Nota: TIDAK guna BelongsToMosque — log peringkat platform ada mosque_id NULL.
class NotificationLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'mosque_id', 'user_id', 'channel', 'to', 'notification_type', 'status', 'error',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
