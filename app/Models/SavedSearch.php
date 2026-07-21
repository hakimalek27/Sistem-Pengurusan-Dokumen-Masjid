<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    use BelongsToMosque;

    protected $fillable = ['mosque_id', 'user_id', 'name', 'criteria', 'is_default', 'last_used_at'];

    protected function casts(): array
    {
        return ['criteria' => 'array', 'is_default' => 'boolean', 'last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
