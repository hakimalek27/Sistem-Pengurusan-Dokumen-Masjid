<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpAnnouncement extends Model
{
    protected $fillable = ['mosque_id', 'created_by', 'panel', 'title', 'body', 'roles', 'is_active', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['roles' => 'array', 'is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function scopeCurrentlyVisible(Builder $query, string $panel, ?string $role, ?int $mosqueId): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereIn('panel', ['all', $panel])
            ->where(fn (Builder $scope) => $scope->whereNull('mosque_id')->when($mosqueId, fn (Builder $tenant) => $tenant->orWhere('mosque_id', $mosqueId)))
            ->where(fn (Builder $scope) => $scope->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $scope) => $scope->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->when($role, fn (Builder $scope) => $scope->where(fn (Builder $roles) => $roles->whereNull('roles')->orWhereJsonContains('roles', $role)));
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
