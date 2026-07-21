<x-filament-panels::page>
    <div class="divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-white/10 dark:border-white/10">
        @forelse ($items as $item)
            <article class="flex items-center gap-3 p-3">
                <a href="{{ $item['url'] }}" class="min-w-0 flex-1">
                    <div class="font-medium">{{ $item['title'] }}</div>
                    <div class="text-sm text-gray-500">{{ $item['label'] }} · {{ $item['reference'] }}</div>
                </a>
                <button type="button" wire:click="remove('{{ $item['type'] }}', {{ $item['id'] }})" class="h-9 w-9" title="Buang kegemaran" aria-label="Buang kegemaran">★</button>
            </article>
        @empty
            <p class="p-4 text-gray-500">Belum ada rekod atau fail kegemaran.</p>
        @endforelse
    </div>
</x-filament-panels::page>
