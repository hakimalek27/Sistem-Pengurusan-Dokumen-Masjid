<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// §5.16 file_access_grants — akses khas fail sulit kepada individu luar peranan lalai
class FileAccessGrant extends Model
{
    use HasFactory;

    protected $fillable = ['registry_file_id', 'user_id', 'granted_by'];

    public function registryFile(): BelongsTo
    {
        return $this->belongsTo(RegistryFile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
