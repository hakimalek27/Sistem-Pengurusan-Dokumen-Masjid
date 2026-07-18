<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            @if ($complete)
                Persediaan asas selesai ✓
            @else
                Selamat datang! Mari sediakan masjid anda
            @endif
        </x-slot>
        <x-slot name="description">
            Klik <strong>Mula Persediaan Berpandu</strong> di atas untuk menetapkan jawatan anda,
            nombor WhatsApp masjid, dan mendaftar ahli AJK sekali gus. Anda boleh membukanya
            semula bila-bila masa.
        </x-slot>

        <ul class="space-y-2 text-sm">
            @foreach ($items as [$label, $done])
                <li class="flex items-center gap-2">
                    @if ($done)
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-emerald-500" />
                    @else
                        <x-filament::icon icon="heroicon-o-minus-circle" class="h-5 w-5 text-gray-400" />
                    @endif
                    <span class="{{ $done ? 'text-gray-700 dark:text-gray-200' : 'text-gray-500' }}">{{ $label }}</span>
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-panels::page>
