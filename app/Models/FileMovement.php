<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileMovement extends Model
{
    use BelongsToMosque;

    protected $fillable = [
        'mosque_id', 'registry_file_id', 'action', 'from_location', 'to_location',
        'holder_user_id', 'holder_name', 'due_at', 'returned_at', 'notes', 'handled_by',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'returned_at' => 'datetime'];
    }

    public function registryFile(): BelongsTo
    {
        return $this->belongsTo(RegistryFile::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_user_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
