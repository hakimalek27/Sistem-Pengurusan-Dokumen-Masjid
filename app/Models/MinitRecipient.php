<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// §5.9 minit_recipients
class MinitRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'minit_id', 'user_id', 'acted_by_user_id', 'acted_on_behalf_of_user_id',
        'jenis', 'read_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function minit(): BelongsTo
    {
        return $this->belongsTo(Minit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_user_id');
    }

    public function actedOnBehalfOf(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_on_behalf_of_user_id');
    }
}
