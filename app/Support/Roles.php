<?php

namespace App\Support;

/**
 * §6.0 — Baca peta peranan/kebenaran statik config/roles.php.
 */
class Roles
{
    /** Role lama yang mungkin masih wujud pada pivot sebelum migration dijalankan. */
    public static function canonical(string $role): string
    {
        return $role === 'kerani' ? 'admin_masjid' : $role;
    }

    /** @return array<int,string> senarai peranan per masjid (§6.1) */
    public static function all(): array
    {
        return config('roles.list', []);
    }

    public static function label(string $role): string
    {
        $role = static::canonical($role);

        return config("roles.labels.$role", $role);
    }

    /** @return array<string,string> peranan => label BM (untuk dropdown) */
    public static function options(): array
    {
        return array_intersect_key(config('roles.labels', []), array_flip(static::all()));
    }

    /** @return array<int,string> senarai kebenaran bagi peranan (§6.2) */
    public static function permissions(string $role): array
    {
        return config('roles.matrix.'.static::canonical($role), []);
    }

    public static function can(string $role, string $permission): bool
    {
        return in_array($permission, static::permissions($role), true);
    }
}
