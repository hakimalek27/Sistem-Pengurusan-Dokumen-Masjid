<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// §5.13 storage_addons — Kuota efektif = base + Σ(addon aktif)
class StorageAddon extends Model
{
    use BelongsToMosque, HasFactory;

    protected $fillable = [
        'mosque_id', 'storage_order_id', 'gb', 'starts_at', 'expires_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'gb' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StorageOrder::class, 'storage_order_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }
}
