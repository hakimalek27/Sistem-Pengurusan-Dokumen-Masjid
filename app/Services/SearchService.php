<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\Record;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * §13 — SATU-SATUNYA titik masuk carian. MEMAKSA filter mosque_id + sensitivity
 * dibenarkan pada peringkat enjin (§6.3). Tiada laluan query Meili lain.
 */
class SearchService
{
    /** Peranan yang boleh melihat rekod sulit dalam carian (§6.3). */
    protected const SULIT_ROLES = ['admin_masjid', 'kerani', 'pengerusi', 'setiausaha', 'nazir'];

    public function for(User $user, Mosque $tenant, string $query, array $filters = []): Collection
    {
        // Fail-closed: service boleh dipanggil di luar halaman Filament pada masa depan.
        // Jangan bergantung pada UI untuk membuktikan keahlian tenant.
        if (! $user->isMemberOf($tenant)) {
            return collect();
        }

        $allowed = $this->allowedSensitivities($user, $tenant);

        $search = Record::search($query)
            ->where('mosque_id', $tenant->id)
            ->whereIn('sensitivity', $allowed);

        if (! empty($filters['record_type'])) {
            $search->where('record_type', $filters['record_type']);
        }
        if (! empty($filters['registry_file_id'])) {
            $search->where('registry_file_id', $filters['registry_file_id']);
        }

        return $search->get();
    }

    /** Tahap sensitiviti yang dibenarkan untuk pengguna dalam masjid (§6.3). */
    public function allowedSensitivities(User $user, Mosque $tenant): array
    {
        if ($user->is_superadmin) {
            return ['umum', 'dalaman', 'sulit'];
        }

        $role = $user->roleIn($tenant);
        $allowed = ['umum', 'dalaman'];

        if (in_array($role, self::SULIT_ROLES, true)) {
            $allowed[] = 'sulit';
        }

        // ⚠️ bendahari: sulit hanya bagi fail 200/300 — ditemui melalui halaman Fail dalam MVP
        // (carian kekal umum/dalaman). file_access_grants individu = Fasa 2 (multi-index) §13.

        return $allowed;
    }
}
