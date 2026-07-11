<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Checklist Onboarding ({{ $complete }}/{{ count($items) }})</x-slot>
        <div class="grid gap-2 md:grid-cols-2">
            @foreach ($items as [$label, $done])
                <div class="flex items-center gap-2 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                    <span class="{{ $done ? 'text-emerald-600' : 'text-amber-600' }}">{{ $done ? '✓' : '○' }}</span>
                    <span>{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
