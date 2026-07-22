<x-filament-widgets::widget>
    <x-filament::section data-help-target="what-next">
        <x-slot name="heading">Apa Perlu Dibuat Sekarang</x-slot>
        <x-slot name="description">Tugasan dalam skop akaun dan tenant semasa.</x-slot>

        <div class="divide-y divide-gray-100 dark:divide-white/10">
            @forelse ($tasks as $task)
                @php
                    $badge = match ($task['status']) {
                        'Lewat' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
                        'Perlu tindakan' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                        'Menunggu sistem' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
                        'Menunggu orang lain' => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
                        'Cadangan' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                        default => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                    };
                @endphp
                <a href="{{ $task['url'] }}" class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 py-3 first:pt-0 last:pb-0">
                    <span class="min-w-0">
                        <span class="block truncate font-medium">{{ $task['label'] }}</span>
                        <span class="block text-sm text-gray-500 dark:text-gray-400">{{ $task['description'] }}</span>
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="rounded-md px-2 py-1 text-xs font-semibold {{ $badge }}">{{ $task['status'] }}</span>
                        <strong class="min-w-7 text-right tabular-nums">{{ $task['count'] }}</strong>
                    </span>
                </a>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Tiada tugasan yang memerlukan perhatian sekarang.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
