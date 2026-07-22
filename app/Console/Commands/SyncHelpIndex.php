<?php

namespace App\Console\Commands;

use App\Services\HelpCatalog;
use Illuminate\Console\Command;
use Meilisearch\Client;
use Throwable;

class SyncHelpIndex extends Command
{
    public const HELP_STOP_WORDS = [
        'bagaimana',
        'bolehkah',
        'hendak',
        'macam',
        'mahu',
        'nak',
        'saya',
        'tolong',
    ];

    protected $signature = 'diwan:sync-help-index {--delete : Padam indeks bantuan sebelum bina semula}';

    protected $description = 'Sahkan katalog dan segerakkan indeks panduan bantuan tanpa data tenant';

    public function handle(HelpCatalog $catalog): int
    {
        $errors = $catalog->validate();
        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (config('scout.driver') !== 'meilisearch') {
            $this->warn('Scout driver bukan meilisearch; katalog sah dan fallback PHP kekal aktif.');

            return self::SUCCESS;
        }

        try {
            $client = new Client(
                (string) config('scout.meilisearch.host', env('MEILISEARCH_HOST')),
                (string) config('scout.meilisearch.key', env('MEILISEARCH_KEY')),
            );
            $uid = (string) config('diwan.guidance.help_index');
            if ($this->option('delete')) {
                $deleteTask = rescue(fn () => $client->deleteIndex($uid), report: false);
                if (is_array($deleteTask) && isset($deleteTask['taskUid'])) {
                    $deleted = $client->waitForTask($deleteTask['taskUid'], 30_000, 100);
                    if (($deleted['status'] ?? null) === 'failed') {
                        throw new \RuntimeException('Indeks lama gagal dipadam: '.data_get($deleted, 'error.message', 'ralat tidak diketahui'));
                    }
                }
            }
            $index = $client->index($uid);
            $documents = collect($catalog->raw()['guides'] ?? [])->map(fn (array $guide): array => [
                'document_id' => self::documentId($guide['id']),
                'guide_id' => $guide['id'],
                'panel' => $guide['panel'],
                'roles' => $guide['roles'],
                'title' => $guide['title'],
                'summary' => $guide['summary'],
                'keywords' => $guide['keywords'],
                'steps_text' => collect($guide['steps'] ?? [])->pluck('instruction')->implode(' '),
                'troubleshooting_text' => collect($guide['troubleshooting'] ?? [])->implode(' '),
            ])->all();
            $tasks = [
                $index->addDocuments($documents, 'document_id'),
                $index->updateFilterableAttributes(['panel', 'roles']),
                $index->updateSearchableAttributes(['title', 'summary', 'keywords', 'steps_text', 'troubleshooting_text']),
                $index->updateStopWords(self::HELP_STOP_WORDS),
            ];
            $taskUids = collect($tasks)->pluck('taskUid')->filter()->values()->all();
            $completed = $client->waitForTasks($taskUids, 30_000, 100);
            $failed = collect($completed)->firstWhere('status', 'failed');
            if ($failed) {
                throw new \RuntimeException('Task indeks gagal: '.data_get($failed, 'error.message', 'ralat tidak diketahui'));
            }
            $indexed = (int) data_get($index->stats(), 'numberOfDocuments', 0);
            if ($indexed !== count($documents)) {
                throw new \RuntimeException("Bilangan guide indeks {$indexed} tidak sepadan dengan katalog ".count($documents).'.');
            }
            $this->info(count($documents).' guide disegerakkan ke indeks '.$uid.'.');
        } catch (Throwable $exception) {
            $this->error('Meilisearch gagal: '.$exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    public static function documentId(string $guideId): string
    {
        return hash('sha256', $guideId);
    }
}
