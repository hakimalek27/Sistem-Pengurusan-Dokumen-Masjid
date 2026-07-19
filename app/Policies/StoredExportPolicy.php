<?php

namespace App\Policies;

use App\Models\StoredExport;
use App\Models\User;

class StoredExportPolicy
{
    public function download(User $user, StoredExport $export): bool
    {
        if ($export->expires_at->isPast()) {
            return false;
        }

        if ($user->is_superadmin) {
            return true;
        }

        return $user->isMemberOf($export->mosque)
            && ($user->canIn($export->mosque, 'export.create')
                || $user->canIn($export->mosque, 'audit.view'));
    }
}
