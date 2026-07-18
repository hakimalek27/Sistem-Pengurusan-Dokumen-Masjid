<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

// §5.13 platform_settings (key -> jsonb)
class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->first()?->value ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Simpan nilai rahsia tersulit (cth token bot Telegram) dalam JSONB.
     * Kosong/null → simpan null (bukan cipher). Guna APP_KEY Laravel.
     */
    public static function putEncrypted(string $key, ?string $value): void
    {
        static::put($key, ($value === null || $value === '')
            ? null
            : ['cipher' => Crypt::encryptString($value)]);
    }

    /**
     * Baca nilai rahsia tersulit. Pulang null jika tiada atau gagal nyahsulit
     * (cth APP_KEY dirotasi) — pemanggil patut jatuh balik ke env.
     */
    public static function getEncrypted(string $key): ?string
    {
        $stored = static::get($key);
        if (! is_array($stored) || ! isset($stored['cipher'])) {
            return null;
        }

        try {
            return Crypt::decryptString($stored['cipher']);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
