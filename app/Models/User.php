<?php

namespace App\Models;

use App\Support\Roles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// §5.3 users (GLOBAL — tiada mosque_id)
class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'is_superadmin', 'phone_wa', 'telegram_chat_id',
        'jawatan', 'notify_whatsapp', 'notify_telegram', 'notify_email', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'notify_telegram' => 'boolean',
            'notify_email' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function mosques(): BelongsToMany
    {
        return $this->belongsToMany(Mosque::class, 'mosque_user')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /** Peranan pengguna dalam masjid tertentu, atau null jika bukan ahli (§6.0). */
    public function roleIn(Mosque $mosque): ?string
    {
        if ($this->relationLoaded('mosques')) {
            return $this->mosques->firstWhere('id', $mosque->getKey())?->pivot?->role;
        }

        return $this->mosques()->where('mosques.id', $mosque->getKey())->first()?->pivot?->role;
    }

    /** Boleh laksanakan kebenaran dalam masjid? superadmin lulus semua (§6.0). */
    public function canIn(Mosque $mosque, string $permission): bool
    {
        if ($this->is_superadmin) {
            return true;
        }

        $role = $this->roleIn($mosque);

        return $role !== null && Roles::can($role, $permission);
    }

    public function isMemberOf(Mosque $mosque): bool
    {
        return $this->is_superadmin || $this->roleIn($mosque) !== null;
    }

    // ---- Filament tenancy (§5.3) ----

    public function getTenants(Panel $panel): Collection
    {
        if ($this->is_superadmin) {
            return Mosque::query()->where('status', 'aktif')->get();
        }

        return $this->mosques()->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->is_superadmin
            || $this->mosques()->whereKey($tenant->getKey())->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $panel->getId() === 'admin'
            ? (bool) $this->is_superadmin
            : ($this->is_superadmin || $this->mosques()->exists());
    }
}
