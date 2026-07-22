<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportAttachment extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'support_request_id', 'mosque_id', 'disk', 'path', 'original_name', 'mime',
        'size_bytes', 'sha256', 'scan_status', 'scan_signature',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class, 'support_request_id');
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }
}
