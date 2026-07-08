<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// §5.5 classification_nodes (+mosque_id)
class ClassificationNode extends Model
{
    use BelongsToMosque, HasFactory;

    protected $fillable = [
        'mosque_id', 'parent_id', 'level', 'code', 'title', 'default_sensitivity', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function registryFiles(): HasMany
    {
        return $this->hasMany(RegistryFile::class);
    }

    public function isFungsi(): bool
    {
        return $this->level === 'fungsi';
    }

    public function isAktiviti(): bool
    {
        return $this->level === 'aktiviti';
    }

    public function isSubAktiviti(): bool
    {
        return $this->level === 'sub_aktiviti';
    }
}
