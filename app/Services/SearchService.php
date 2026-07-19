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

        // ACL sebenar dikira daripada query pangkalan data yang sama dengan
        // senarai Rekod (termasuk prefix kewangan dan geran fail individu).
        // ID ini kemudian menjadi filter wajib pada enjin carian/OCR.
        $visibleIds = Record::query()->visibleTo($user, $tenant)->pluck('id')->all();
        if ($visibleIds === []) {
            return collect();
        }

        $allowed = $this->allowedSensitivities($user, $tenant);

        // Bendahari/geran individu mungkin dibenarkan melihat sebahagian rekod
        // sulit. Filter ID mengehadkan subset itu tanpa membuka rekod sulit lain.
        if ($user->roleIn($tenant) === 'bendahari'
            || Record::query()->visibleTo($user, $tenant)->where('sensitivity', 'sulit')->exists()) {
            $allowed[] = 'sulit';
            $allowed = array_values(array_unique($allowed));
        }

        $search = Record::search($query)
            ->where('mosque_id', $tenant->id)
            ->whereIn('id', $visibleIds)
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

    /**
     * Petikan konteks (±90 aksara) sekitar padanan pertama untuk hasil carian (§13′).
     * Semak ocr_text (kandungan) dahulu, kemudian medan metadata utama. Teks BIASA
     * (belum di-escape) — gunakan highlight() untuk render selamat.
     */
    public function snippetFor(Record $record, string $query): ?string
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $haystacks = array_filter(
            [$record->ocr_text, $record->title, $record->our_ref, $record->their_ref, $record->sender_name, $record->sender_org],
            static fn ($value) => is_string($value) && $value !== '',
        );

        foreach ($haystacks as $text) {
            $pos = mb_stripos($text, $query);
            if ($pos !== false) {
                return static::excerpt($text, $pos, mb_strlen($query));
            }
        }

        return null;
    }

    protected static function excerpt(string $text, int $pos, int $length): string
    {
        $radius = 90;
        $start = max(0, $pos - $radius);
        $end = min(mb_strlen($text), $pos + $length + $radius);

        $slice = trim((string) preg_replace('/\s+/u', ' ', mb_substr($text, $start, $end - $start)));

        return ($start > 0 ? '… ' : '').$slice.($end < mb_strlen($text) ? ' …' : '');
    }

    /**
     * Tandakan padanan query dalam petikan sebagai HTML SELAMAT: teks di-escape dahulu,
     * kemudian padanan dibalut <mark> (gaya inline — tiada pergantungan purge Tailwind).
     * Nilai pulangan boleh dirender dengan {!! !!} tanpa risiko XSS.
     */
    public static function highlight(?string $plain, string $query): ?string
    {
        if ($plain === null) {
            return null;
        }

        $escaped = e($plain);
        $query = trim($query);
        if ($query === '') {
            return $escaped;
        }

        $pattern = '/('.preg_quote(e($query), '/').')/iu';

        return preg_replace(
            $pattern,
            '<mark style="background-color:#fde68a;color:#111827;border-radius:2px;padding:0 1px;">$1</mark>',
            $escaped,
        ) ?? $escaped;
    }
}
