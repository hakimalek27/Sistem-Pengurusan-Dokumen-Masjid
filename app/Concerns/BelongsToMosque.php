<?php

namespace App\Concerns;

use App\Models\Mosque;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * §15.2 — Pengasingan tenant. Global scope where mosque_id = tenant semasa bila
 * konteks tenant Filament wujud, + auto-isi mosque_id semasa cipta. Middleware
 * ApplyTenantScopes mendaftarkan konteks untuk semua request panel tenant.
 *
 * Query di luar panel (Services/Jobs/Widgets/Commands) TIDAK berskop automatik —
 * guna scopeForMosque($mosque) secara eksplisit (§0.4, §15.2).
 */
trait BelongsToMosque
{
    public static function bootBelongsToMosque(): void
    {
        static::addGlobalScope('mosque', function (Builder $builder) {
            if ($mosque = static::currentMosque()) {
                $builder->where($builder->getModel()->getTable().'.mosque_id', $mosque->getKey());
            }
        });

        static::creating(function (Model $model) {
            if (empty($model->mosque_id) && ($mosque = static::currentMosque())) {
                $model->mosque_id = $mosque->getKey();
            }
        });
    }

    /** Tenant Filament semasa, atau null bila di luar konteks panel (seeder/ujian/console). */
    protected static function currentMosque(): ?Mosque
    {
        try {
            $tenant = Filament::getTenant();

            return $tenant instanceof Mosque ? $tenant : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    /** Skop eksplisit tenant untuk query luar-panel (§15.2). */
    public function scopeForMosque(Builder $query, Mosque|int $mosque): Builder
    {
        $id = $mosque instanceof Mosque ? $mosque->getKey() : $mosque;

        return $query->withoutGlobalScope('mosque')
            ->where($query->getModel()->getTable().'.mosque_id', $id);
    }

    /** Buang skop tenant (untuk operasi merentas-tenant superadmin yang disengajakan). */
    public function scopeWithoutMosqueScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('mosque');
    }
}
