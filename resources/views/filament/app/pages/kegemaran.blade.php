<x-filament-panels::page>
    {{-- F6-W0: sasaran spesifik menggantikan sorotan MAIN — lima langkah guide ini ialah
         lima daripada enam defect `centerCovered` pada mobile 390×664 (RR-10-05). --}}
    <div data-help-target="favourites-list"
        class="divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-white/10 dark:border-white/10">
        @forelse ($items as $item)
            <article class="flex items-center gap-3 p-3" @if ($loop->first) data-help-target="favourite-item" @endif>
                <a href="{{ $item['url'] }}" class="min-w-0 flex-1" @if ($loop->first) data-help-target="favourite-open" @endif>
                    <div class="font-medium">{{ $item['title'] }}</div>
                    <div class="text-sm text-gray-500">{{ $item['label'] }} · {{ $item['reference'] }}</div>
                </a>
                <button type="button" wire:click="remove('{{ $item['type'] }}', {{ $item['id'] }})" class="h-9 w-9"
                    title="Buang kegemaran" aria-label="Buang kegemaran"
                    @if ($loop->first) data-help-target="favourite-remove" @endif>★</button>
            </article>
        @empty
            {{-- `favourite-item` juga di sini: sasaran guide mesti wujud dalam KEDUA-DUA
                 keadaan, jika tidak pengguna yang belum ada kegemaran hanya nampak popover
                 "Tindakan belum tersedia" pada langkah 3 dan 4. --}}
            <p class="p-4 text-gray-500" data-help-target="favourite-item">Belum ada rekod atau fail kegemaran.</p>
        @endforelse
    </div>
</x-filament-panels::page>
