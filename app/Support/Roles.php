<?php

namespace App\Support;

/**
 * §6.0 — Baca peta peranan/kebenaran statik config/roles.php.
 */
class Roles
{
    /** @return array<int,string> senarai peranan per masjid (§6.1) */
    public static function all(): array
    {
        return config('roles.list', []);
    }

    public static function label(string $role): string
    {
        return config("roles.labels.$role", $role);
    }

    /** @return array<string,string> peranan => label BM (untuk dropdown) */
    public static function options(): array
    {
        return config('roles.labels', []);
    }

    /** @return array<int,string> senarai kebenaran bagi peranan (§6.2) */
    public static function permissions(string $role): array
    {
        return config("roles.matrix.$role", []);
    }

    public static function can(string $role, string $permission): bool
    {
        return in_array($permission, static::permissions($role), true);
    }
}
