<?php

namespace App\Console\Commands;

use App\Models\Record;
use Illuminate\Console\Command;
use Meilisearch\Client;

// §13 — Segerakkan tetapan indeks Meilisearch (jalankan semasa deploy).
class SyncMeiliSettings extends Command
{
    protected $signature = 'diwan:sync-meili';

    protected $description = 'Kemas kini filterable/sortable/searchable attributes indeks Meilisearch';

    public function handle(): int
    {
        if (config('scout.driver') !== 'meilisearch') {
            $this->warn('Scout driver bukan meilisearch — dilangkau.');

            return self::SUCCESS;
        }

        try {
            $client = new Client(
                (string) config('scout.meilisearch.host', env('MEILISEARCH_HOST')),
                (string) config('scout.meilisearch.key', env('MEILISEARCH_KEY')),
            );

            $index = $client->index((new Record)->searchableAs());
            // mosque_id PERTAMA (§13).
            $index->updateFilterableAttributes(['id', 'mosque_id', 'sensitivity', 'record_type', 'status', 'registry_file_id', 'record_date']);
            $index->updateSortableAttributes(['record_date']);
            $index->updateSearchableAttributes(['title', 'our_ref', 'their_ref', 'sender_name', 'sender_org', 'recipient_name', 'file_no', 'ocr_text']);

            $this->info('Tetapan indeks Meilisearch dikemas kini.');
        } catch (\Throwable $e) {
            $this->error('Gagal menghubungi Meilisearch: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
