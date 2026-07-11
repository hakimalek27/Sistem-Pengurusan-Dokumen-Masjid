<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoredExport extends Model
{
    use BelongsToMosque;

    protected $fillable = ['mosque_id', 'requested_by', 'label', 'path', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
