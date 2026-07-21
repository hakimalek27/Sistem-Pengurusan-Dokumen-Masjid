<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\Record;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

        $query = trim($query);
        if ($query !== '') {
            $matchedIds = Record::search($query)
                ->where('mosque_id', $tenant->id)
                ->whereIn('id', $visibleIds)
                ->whereIn('sensitivity', $allowed)
                ->take(500)
                ->get()
                ->pluck('id')
                ->all();

            if ($matchedIds === []) {
                return collect();
            }

            $visibleIds = array_values(array_intersect($visibleIds, $matchedIds));
        }

        $records = Record::query()
            ->visibleTo($user, $tenant)
            ->with('registryFile')
            ->whereIn('id', $visibleIds);

        $this->applyFilters($records, $filters);

        return $records->latest('record_date')->latest('id')->limit(500)->get();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        foreach (['record_type', 'registry_file_id', 'direction', 'sensitivity', 'status', 'source_channel'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        foreach (['record_date_from' => '>=', 'record_date_to' => '<=', 'received_date_from' => '>=', 'received_date_to' => '<='] as $field => $operator) {
            if (! empty($filters[$field])) {
                $column = str_starts_with($field, 'record_') ? 'record_date' : 'received_date';
                $query->whereDate($column, $operator, $filters[$field]);
            }
        }

        $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        foreach (['sender' => ['sender_name', 'sender_org'], 'reference' => ['our_ref', 'their_ref'], 'recipient' => ['recipient_name']] as $filter => $columns) {
            if (! empty($filters[$filter])) {
                $value = '%'.trim((string) $filters[$filter]).'%';
                $query->where(function (Builder $nested) use ($columns, $like, $value): void {
                    foreach ($columns as $index => $column) {
                        $index === 0 ? $nested->where($column, $like, $value) : $nested->orWhere($column, $like, $value);
                    }
                });
            }
        }
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
