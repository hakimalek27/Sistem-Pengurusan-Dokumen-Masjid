<x-filament::section class="mb-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <x-filament::icon
                icon="heroicon-o-rocket-launch"
                class="mt-0.5 h-6 w-6 flex-shrink-0 text-primary-500"
            />
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    Siapkan persediaan masjid anda
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Tetapkan jawatan, nombor WhatsApp masjid, dan daftar ahli AJK dalam satu langkah berpandu.
                </p>
            </div>
        </div>
        <x-filament::button
            tag="a"
            :href="\App\Filament\App\Pages\OnboardingWizard::getUrl(['mula' => 1])"
            icon="heroicon-o-rocket-launch"
            class="flex-shrink-0"
        >
            Mula Persediaan Berpandu
        </x-filament::button>
    </div>
</x-filament::section>
