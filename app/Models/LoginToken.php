<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// §5.4 login_tokens (magic link — simpan hash SHA-256; luput 15 min; sekali guna)
class LoginToken extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'email', 'token', 'expires_at', 'used_at', 'ip'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at !== null && $this->expires_at->isFuture();
    }
}
