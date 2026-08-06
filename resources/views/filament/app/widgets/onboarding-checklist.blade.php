<x-filament-widgets::widget>
    {{-- F6-W5: `tenant.dashboard` #3 ("Semak senarai semak persediaan jika masih dipaparkan").
         Sasaran pada SEKSYEN, bukan grid item, supaya ia kekal walaupun senarai berubah.
         Tajuk dibetulkan daripada "Checklist Onboarding" (bocor EN) kepada Bahasa Melayu:
         tour merujuknya sebagai "senarai semak persediaan", jadi tajuk skrin yang berlainan
         bahasa akan mengelirukan pengguna pada langkah yang sama. --}}
    <x-filament::section data-help-target="dashboard-checklist">
        <x-slot name="heading">Senarai Semak Persediaan ({{ $complete }}/{{ count($items) }})</x-slot>
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
