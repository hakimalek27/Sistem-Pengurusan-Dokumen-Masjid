<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use App\Enums\MinitPriority;
use App\Enums\MinitStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// §5.8 minits (+mosque_id)
class Minit extends Model
{
    use BelongsToMosque, HasFactory;

    protected $fillable = [
        'mosque_id', 'record_id', 'from_user_id', 'body', 'priority', 'due_at',
        'status', 'parent_id', 'completed_at', 'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'priority' => MinitPriority::class,
            'status' => MinitStatus::class,
            'due_at' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MinitRecipient::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
