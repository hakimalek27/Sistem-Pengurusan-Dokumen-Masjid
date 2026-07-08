<?php

namespace App\Models;

use App\Enums\RetentionAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// §5.11 retention_rules (mosque_id NULLABLE — NULL = lalai platform)
// Nota: TIDAK guna BelongsToMosque — resolusi retensi perlu lihat peraturan platform (NULL)
// bersama peraturan masjid (§5.11 resolusi). Skop dikuruskan eksplisit dalam RetentionEngine.
class RetentionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'mosque_id', 'record_type', 'classification_prefix', 'retain_years', 'action', 'note',
    ];

    protected function casts(): array
    {
        return [
            'action' => RetentionAction::class,
            'retain_years' => 'integer',
        ];
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    public function isPlatformDefault(): bool
    {
        return $this->mosque_id === null;
    }
}
