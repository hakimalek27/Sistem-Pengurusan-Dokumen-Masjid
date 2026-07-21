<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordCorrectionRequest extends Model
{
    use BelongsToMosque;

    protected $fillable = [
        'mosque_id', 'record_id', 'requested_by', 'reason', 'proposed_changes',
        'status', 'reviewed_by', 'review_note', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['proposed_changes' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
