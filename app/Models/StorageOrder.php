<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// §5.13 storage_orders
class StorageOrder extends Model
{
    use BelongsToMosque, HasFactory;

    protected $fillable = [
        'mosque_id', 'ordered_by', 'gb', 'unit_price_cents', 'amount_cents', 'period_months',
        'status', 'invoice_no', 'invoice_path', 'paid_at', 'confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'gb' => 'integer',
            'unit_price_cents' => 'integer',
            'amount_cents' => 'integer',
            'period_months' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(StorageAddon::class);
    }
}
