<?php

namespace App\Models;

use App\Enums\MosqueStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

// §5.1 mosques (TENANT)
class Mosque extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'code', 'state', 'district', 'address', 'phone', 'status',
        'storage_quota_bytes', 'storage_used_bytes', 'auto_disposal_enabled',
        'retention_ack_at', 'retention_ack_by', 'wa_session_id', 'wa_number',
        'settings', 'approved_at', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => MosqueStatus::class,
            'storage_quota_bytes' => 'integer',
            'storage_used_bytes' => 'integer',
            'auto_disposal_enabled' => 'boolean',
            'settings' => 'array',
            'retention_ack_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'settings' => '{}',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $mosque): void {
            if ($mosque->isDirty('code') && $mosque->registryFiles()->exists()) {
                throw new LogicException('Kod tenant tidak boleh diubah selepas fail registri digunakan.');
            }
        });
    }

    /**
     * Elakkan nilai route statik seperti `create` dihantar ke kolum bigint
     * PostgreSQL apabila halaman cipta resource memang tidak didaftarkan.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $routeField = $field ?? $this->getRouteKeyName();

        if ($routeField === $this->getKeyName() && ! ctype_digit((string) $value)) {
            return $query->whereRaw('1 = 0');
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }

    // ---- Relationships ----

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mosque_user')
            ->withPivot('role', 'phone_wa', 'notify_whatsapp', 'joined_at')
            ->withTimestamps();
    }

    public function whatsappIntegration(): HasOne
    {
        return $this->hasOne(WhatsAppIntegration::class);
    }

    public function classificationNodes(): HasMany
    {
        return $this->hasMany(ClassificationNode::class);
    }

    public function registryFiles(): HasMany
    {
        return $this->hasMany(RegistryFile::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    public function retentionRules(): HasMany
    {
        return $this->hasMany(RetentionRule::class);
    }

    public function disposalBatches(): HasMany
    {
        return $this->hasMany(DisposalBatch::class);
    }

    public function storageOrders(): HasMany
    {
        return $this->hasMany(StorageOrder::class);
    }

    public function storageAddons(): HasMany
    {
        return $this->hasMany(StorageAddon::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function retentionAckBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retention_ack_by');
    }

    // ---- Helpers ----

    /** Kuota efektif (bait) = base + Σ(addon aktif) (§5.13). */
    public function effectiveQuotaBytes(): int
    {
        $addonGb = (int) $this->storageAddons()->where('status', 'aktif')->sum('gb');

        return (int) $this->storage_quota_bytes + ($addonGb * (1024 ** 3));
    }

    public function isActive(): bool
    {
        return $this->status === MosqueStatus::Aktif;
    }

    public function waIntakeEnabled(): bool
    {
        return (bool) ($this->settings['wa_intake_enabled'] ?? true);
    }

    public function waIntakeKeyword(): string
    {
        return (string) ($this->settings['wa_intake_keyword'] ?? config('diwan.whatsapp.default_keyword', 'spdm'));
    }

    public function mailIntakeEnabled(): bool
    {
        return (bool) ($this->settings['mail_intake_enabled'] ?? false);
    }

    public function mailIntakeKeyword(): string
    {
        return (string) ($this->settings['mail_intake_keyword'] ?? config('diwan.mail_intake.default_keyword', 'spdm'));
    }

    /** @return array<int, string> */
    public function mailIntakeSenders(): array
    {
        return collect($this->settings['mail_intake_senders'] ?? [])
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn ($email) => strtolower(trim($email)))
            ->unique()
            ->values()
            ->all();
    }
}
