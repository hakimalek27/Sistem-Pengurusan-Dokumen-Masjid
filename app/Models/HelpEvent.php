<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpEvent extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'mosque_id', 'panel', 'guide_id', 'event', 'result_count', 'query_hash', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
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
