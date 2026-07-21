<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use App\Enums\Sensitivity;
use Illuminate\Database\Eloquent\Builder;
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
        'closed_reason', 'created_by', 'gdrive_folder_id', 'medium', 'physical_reference',
        'physical_location', 'custody_status', 'current_holder_user_id',
        'current_holder_name', 'custody_due_at',
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
            'custody_due_at' => 'datetime',
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

    public function currentHolder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_holder_user_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FileMovement::class)->latest();
    }

    public function isOpen(): bool
    {
        return $this->status === 'terbuka';
    }

    /** Senarai fail yang boleh dilihat tanpa membocorkan tajuk fail sulit. */
    public function scopeVisibleTo(Builder $query, User $user, Mosque $mosque): Builder
    {
        $query->where($query->qualifyColumn('mosque_id'), $mosque->id);

        if ($user->is_superadmin) {
            return $query;
        }

        $role = $user->roleIn($mosque);
        if ($role === null || ! $user->canIn($mosque, 'files.view')) {
            return $query->whereRaw('1 = 0');
        }

        if (in_array($role, ['admin_masjid', 'pengerusi', 'setiausaha', 'nazir'], true)) {
            return $query;
        }

        return $query->where(function (Builder $allowed) use ($role, $user): void {
            $allowed->where('sensitivity', '!=', Sensitivity::Sulit->value);

            if ($role === 'bendahari') {
                $allowed->orWhereHas('classificationNode', fn (Builder $node) => $node
                    ->where(fn (Builder $prefix) => $prefix
                        ->where('code', 'like', '200%')
                        ->orWhere('code', 'like', '300%')));
            }

            $allowed->orWhereHas('accessGrants', fn (Builder $grant) => $grant->where('user_id', $user->id));
        });
    }
}
