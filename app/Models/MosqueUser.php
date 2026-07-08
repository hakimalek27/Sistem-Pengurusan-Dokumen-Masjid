<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

// §5.2 mosque_user (pivot keahlian + PERANAN) — model berasingan untuk audit tukar peranan (§15.4)
class MosqueUser extends Pivot
{
    protected $table = 'mosque_user';

    public $incrementing = true;

    protected $fillable = ['mosque_id', 'user_id', 'role', 'joined_at'];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }
}
