<?php

namespace App\Services;

use App\Models\HelpEvent;
use App\Models\Mosque;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Meilisearch\Client;
use Throwable;

class HelpSearchService
{
    public function __construct(protected HelpCatalog $catalog) {}

    public function search(string $query, string $panel, ?User $user = null, ?Mosque $mosque = null): Collection
    {
        $query = trim($query);
        $visible = $this->catalog->forContext($panel, $user, $mosque)->keyBy('id');
        $results = collect();
        $engine = 'php';

        if ($query !== '' && filled(config('scout.meilisearch.host', env('MEILISEARCH_HOST')))) {
            try {
                // 🔴 F8 §9.2 — klien TANPA tempoh eksplisit mewarisi lalai yang sangat panjang.
                // DIUKUR pada laluan timeout sebenar (RFC 5737 TEST-NET-1, tiada rangkaian
                // luar): **24,232 ms** sebelum fallback PHP menyelamatkan hasil. Jadi apabila
                // Meilisearch TERGANTUNG, carian bukan sekadar menjadi lebih cetek — halaman
                // bantuan TERSEKAT ~24 saat sebelum memaparkan apa-apa.
                //
                // Fallback PHP sudah wujud dan pantas; satu-satunya yang hilang ialah keputusan
                // untuk BERHENTI MENUNGGU. Tempoh pendek menjadikan degradasi hampir tidak
                // dapat dirasai, dan `catch (Throwable)` di bawah sudah mengendalikan lemparannya.
                $tempoh = (float) config('diwan.guidance.meilisearch_timeout', 2.0);
                $client = new Client(
                    (string) config('scout.meilisearch.host', env('MEILISEARCH_HOST')),
                    (string) config('scout.meilisearch.key', env('MEILISEARCH_KEY')),
                    new \GuzzleHttp\Client(['timeout' => $tempoh, 'connect_timeout' => $tempoh]),
                );
                $response = $client->index((string) config('diwan.guidance.help_index'))->search($query, ['limit' => 30]);
                $results = collect($response->getHits())
                    ->map(fn (array $hit) => $visible->get($hit['guide_id'] ?? ''))
                    ->filter()
                    ->take(12)
                    ->values();
                $engine = 'meilisearch';
            } catch (Throwable) {
                $results = collect();
            }
        }

        if ($results->isEmpty()) {
            $results = $this->catalog->search($query, $panel, $user, $mosque);
            $engine = 'php';
        }

        $this->recordSearch($query, $panel, $user, $mosque, $results->count(), $engine, $results->first()['id'] ?? null);

        return $results;
    }

    protected function recordSearch(string $query, string $panel, ?User $user, ?Mosque $mosque, int $count, string $engine, ?string $guideId): void
    {
        try {
            HelpEvent::query()->create([
                'user_id' => $user?->id,
                'mosque_id' => $mosque?->id,
                'panel' => $panel,
                'guide_id' => $guideId,
                'event' => 'search',
                'result_count' => $count,
                'query_hash' => $query === '' ? null : hash_hmac('sha256', Str::lower(Str::ascii($query)), (string) config('app.key')),
                'metadata' => ['engine' => $engine],
            ]);
        } catch (Throwable) {
            // Bantuan mesti kekal berfungsi semasa migration atau stor analitik tergendala.
        }
    }
}
