<x-filament-panels::page>
    <div data-help-target="page-content" class="space-y-6">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['Carian', $searches],
                ['Tanpa Hasil', $noResults],
                ['Tour Dimulakan', $started],
                ['Tour Selesai', $completed],
                ['Sasaran Hilang', $missingTargets],
            ] as [$label, $value])
                <div class="rounded-md border border-gray-200 p-4 dark:border-white/10">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <section>
            <h2 class="mb-3 text-base font-semibold">Guide Paling Digunakan, 30 Hari</h2>
            <div class="divide-y divide-gray-100 border-y border-gray-200 dark:divide-white/10 dark:border-white/10">
                @forelse ($topGuides as $guide)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <code class="min-w-0 truncate text-sm">{{ $guide->guide_id }}</code>
                        <strong class="tabular-nums">{{ $guide->total }}</strong>
                    </div>
                @empty
                    <p class="py-4 text-sm text-gray-500">Belum ada event bantuan.</p>
                @endforelse
            </div>
        </section>

        <p class="text-sm text-gray-500 dark:text-gray-400">Data ini agregat. Teks carian mentah dan kandungan dokumen tidak disimpan; analitik dipangkas selepas 90 hari.</p>
    </div>
</x-filament-panels::page>
