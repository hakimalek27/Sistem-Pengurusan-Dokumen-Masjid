<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use App\Enums\Sensitivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// §5.6 registry_files (+mosque_id)
class RegistryFile extends Model
{
    use BelongsToMosque, HasFactory;

    protected $fillable = [
        'mosque_id', 'classification_node_id', 'transaction_no', 'volume', 'file_no',
        'title', 'sensitivity', 'status', 'enclosure_count', 'opened_at', 'closed_at',
        'closed_reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sensitivity' => Sensitivity::class,
            'transaction_no' => 'integer',
            'volume' => 'integer',
            'enclosure_count' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function classificationNode(): BelongsTo
    {
        return $this->belongsTo(ClassificationNode::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accessGrants(): HasMany
    {
        return $this->hasMany(FileAccessGrant::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'terbuka';
    }
}
