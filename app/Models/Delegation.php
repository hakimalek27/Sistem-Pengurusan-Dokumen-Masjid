<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delegation extends Model
{
    use BelongsToMosque;

    protected $fillable = [
        'mosque_id', 'principal_user_id', 'delegate_user_id', 'capabilities',
        'starts_at', 'ends_at', 'is_active', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query, ?string $capability = null): Builder
    {
        $query->where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>=', now());

        return $capability ? $query->whereJsonContains('capabilities', $capability) : $query;
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'principal_user_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
