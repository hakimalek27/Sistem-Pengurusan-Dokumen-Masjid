<?php

use App\Console\Commands\SyncHelpIndex;
use App\Models\HelpEvent;
use App\Services\HelpCatalog;
use App\Services\HelpSearchService;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid');
    $this->ajk = makeMember($this->mam, 'ajk');
});

it('memuatkan katalog sah dan menapis guide mengikut panel role dan permission', function () {
    $catalog = app(HelpCatalog::class);

    expect($catalog->validate())->toBe([])
        ->and($catalog->forContext('app', $this->admin, $this->mam)->pluck('id'))->toContain('tenant.peti-masuk')
        ->and($catalog->forContext('app', $this->ajk, $this->mam)->pluck('id'))->not->toContain('tenant.peti-masuk')
        ->and($catalog->forContext('public')->pluck('id'))->toContain('public.registration', 'public.login');
});

it('menerima istilah biasa singkatan dan salah ejaan melalui fallback PHP', function () {
    config()->set('scout.meilisearch.host', null);

    $classification = app(HelpSearchService::class)->search('nak klasfikasi surat wasap', 'app', $this->admin, $this->mam);
    $minute = app(HelpSearchService::class)->search('minit untuk tindakan sk', 'app', $this->ajk, $this->mam);

    expect($classification)->not->toBeEmpty()
        ->and($classification->pluck('id')->contains(fn (string $id) => str_contains($id, 'peti-masuk') || str_contains($id, 'klasifikasikan')))->toBeTrue()
        ->and($minute)->not->toBeEmpty();
});

it('tidak menyimpan teks carian mentah dalam analitik', function () {
    config()->set('scout.meilisearch.host', null);
    $secretQuery = 'rahsia pertanyaan unik 123';

    app(HelpSearchService::class)->search($secretQuery, 'app', $this->admin, $this->mam);

    $event = HelpEvent::query()->latest('id')->first();
    expect($event->query_hash)->toHaveLength(64)
        ->and(json_encode($event->toArray()))->not->toContain($secretQuery);
});

it('menjana primary key Meilisearch sah tanpa mengubah guide id rasmi', function () {
    $catalog = app(HelpCatalog::class)->raw();
    $guideIds = collect($catalog['guides'])->pluck('id');
    $documentIds = $guideIds->map(fn (string $guideId): string => SyncHelpIndex::documentId($guideId));

    expect($documentIds->unique()->count())->toBe($guideIds->count());
    $documentIds->each(fn (string $documentId) => expect($documentId)->toMatch('/^[a-z0-9_-]{1,511}$/'));
    expect($guideIds)->toContain('tenant.dashboard');
});

it('meliputi setiap halaman dan skrin tindakan dalam manifest manual dengan guide role', function () {
    $manifest = json_decode(file_get_contents(base_path('Manual Penguna/manifest-tangkapan.json')), true, flags: JSON_THROW_ON_ERROR);
    $catalog = app(HelpCatalog::class);

    foreach ($manifest['roles'] as $role => $data) {
        $user = makeMember($this->mam, $role);
        $visible = $catalog->forContext('app', $user, $this->mam);
        foreach ($data['pages'] as $page) {
            $path = str_replace('/app/mam', '/app/mam', $page['path']);
            expect($visible->contains(fn (array $guide): bool => $path === $guide['route'] || str_starts_with($path.'/', rtrim($guide['route'], '/').'/')))
                ->toBeTrue("Tiada guide untuk {$role}: {$path}");
        }

        $guideImages = $visible->flatMap(fn (array $guide) => $guide['images'] ?? [])->unique();
        foreach ($data['extras'] ?? [] as $extra) {
            $image = "Manual Penguna/{$data['folder']}/{$extra['image']}";
            expect($guideImages->contains($image))
                ->toBeTrue("Tiada guide untuk {$role}: {$extra['title']} ({$image})");
        }
    }
});
