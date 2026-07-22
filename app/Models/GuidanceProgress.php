<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuidanceProgress extends Model
{
    protected $table = 'guidance_progress';

    protected $fillable = [
        'user_id', 'mosque_id', 'context_key', 'guide_id', 'guide_version', 'step_index',
        'status', 'started_at', 'last_seen_at', 'completed_at', 'dismissed_until',
    ];

    protected function casts(): array
    {
        return [
            'guide_version' => 'integer',
            'step_index' => 'integer',
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'completed_at' => 'datetime',
            'dismissed_until' => 'datetime',
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
