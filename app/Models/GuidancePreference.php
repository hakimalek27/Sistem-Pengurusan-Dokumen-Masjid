<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuidancePreference extends Model
{
    protected $fillable = [
        'user_id', 'mosque_id', 'context_key', 'mode', 'auto_start_enabled', 'nudges_enabled',
        'digest_email', 'digest_whatsapp', 'digest_telegram', 'quiet_hours_start',
        'quiet_hours_end', 'snoozed_until',
    ];

    protected function casts(): array
    {
        return [
            'auto_start_enabled' => 'boolean',
            'nudges_enabled' => 'boolean',
            'digest_email' => 'boolean',
            'digest_whatsapp' => 'boolean',
            'digest_telegram' => 'boolean',
            'snoozed_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }
}
