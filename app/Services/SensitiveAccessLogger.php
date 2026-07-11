<?php

namespace App\Services;

use App\Concerns\ChecksSensitivity;
use App\Enums\Sensitivity;
use App\Models\Record;
use App\Models\SensitiveAccessLog;
use App\Models\User;
use Illuminate\Http\Request;

class SensitiveAccessLogger
{
    use ChecksSensitivity;

    public function log(Record $record, User $user, string $action, Request $request): void
    {
        if ($this->effectiveSensitivity($record) !== Sensitivity::Sulit) {
            return;
        }

        SensitiveAccessLog::query()->create([
            'mosque_id' => $record->mosque_id,
            'is_superadmin' => (bool) $user->is_superadmin,
            'user_id' => $user->id,
            'record_id' => $record->id,
            'action' => $action,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
