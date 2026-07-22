<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportRequest extends Model
{
    protected $fillable = [
        'reference', 'mosque_id', 'user_id', 'assigned_to', 'reporter_session_hash', 'panel',
        'role', 'category', 'subject', 'expected', 'actual', 'route_template', 'request_id',
        'browser_context', 'unmatched_query', 'query_consent', 'status', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'browser_context' => 'array',
            'query_consent' => 'boolean',
            'resolved_at' => 'datetime',
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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportAttachment::class);
    }
}
