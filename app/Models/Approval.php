<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// §5.10 approvals (+mosque_id)
class Approval extends Model
{
    use BelongsToMosque, HasFactory;

    protected $fillable = [
        'mosque_id', 'record_id', 'requested_by', 'approver_id', 'status',
        'request_note', 'decision_note', 'decided_at', 'decision_ip',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
