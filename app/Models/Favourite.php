<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favourite extends Model
{
    use BelongsToMosque;

    public const RECORD = 'record';

    public const REGISTRY_FILE = 'registry_file';

    protected $fillable = ['mosque_id', 'user_id', 'target_type', 'target_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
