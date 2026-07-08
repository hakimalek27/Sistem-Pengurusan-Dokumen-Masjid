<x-filament-panels::page>
    <form wire:submit="search" class="flex gap-2">
        <input
            type="text"
            wire:model="query"
            placeholder="Cari tajuk, no. rujukan, atau kandungan surat…"
            class="fi-input block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5"
        />
        <x-filament::button type="submit">Cari</x-filament::button>
    </form>

    @if ($searched)
        <div class="mt-4">
            @if (count($results) === 0)
                <p class="text-gray-500">Tiada hasil ditemui untuk "{{ $query }}".</p>
            @else
                <p class="text-sm text-gray-500 mb-2">{{ count($results) }} hasil ditemui.</p>
                <div class="divide-y divide-gray-100 dark:divide-white/10 rounded-lg border border-gray-200 dark:border-white/10">
                    @foreach ($results as $r)
                        <a href="{{ url('/r/'.$r['ulid']) }}" class="block p-3 hover:bg-gray-50 dark:hover:bg-white/5">
                            <div class="font-medium">{{ $r['title'] }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $r['ref'] }} · {{ $r['type'] }} · {{ $r['sensitivity'] }}
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
