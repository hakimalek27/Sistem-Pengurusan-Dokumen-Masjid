<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// §5.12 disposal_items — metadata_snapshot KEKAL selamanya
class DisposalItem extends Model
{
    use HasFactory;

    protected $fillable = ['batch_id', 'record_id', 'metadata_snapshot'];

    protected function casts(): array
    {
        return [
            'metadata_snapshot' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DisposalBatch::class, 'batch_id');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }
}
