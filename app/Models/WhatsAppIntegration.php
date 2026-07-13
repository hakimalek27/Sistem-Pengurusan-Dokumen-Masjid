<?php

namespace App\Models;

use App\Concerns\BelongsToMosque;
use Illuminate\Database\Eloquent\Model;

/** Sambungan gateway WhatsApp yang diasingkan satu-ke-satu bagi setiap tenant. */
class WhatsAppIntegration extends Model
{
    use BelongsToMosque;

    protected $table = 'whatsapp_integrations';

    protected $fillable = [
        'mosque_id', 'external_id', 'gateway_tenant_id', 'api_key', 'api_key_prefix',
        'enabled', 'status', 'session_id', 'phone', 'last_synced_at', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function isReady(): bool
    {
        return $this->enabled
            && $this->status === 'connected'
            && filled($this->api_key)
            && filled($this->session_id);
    }
}
