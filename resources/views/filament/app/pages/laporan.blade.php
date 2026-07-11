<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-4">
        <x-filament::section><div class="text-sm text-gray-500">Jumlah Rekod</div><div class="text-3xl font-semibold">{{ $total }}</div></x-filament::section>
        <x-filament::section><div class="text-sm text-gray-500">Akan Luput ≤90 Hari</div><div class="text-3xl font-semibold">{{ $expiring90 }}</div></x-filament::section>
        <x-filament::section><div class="text-sm text-gray-500">Minit Lewat</div><div class="text-3xl font-semibold">{{ $overdueMinits }}</div></x-filament::section>
        <x-filament::section><div class="text-sm text-gray-500">Akses Sulit 30 Hari</div><div class="text-3xl font-semibold">{{ $sensitiveViews30 ?? 'Terhad' }}</div></x-filament::section>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        @foreach ([['Jenis Rekod', $byType, true], ['Status', $byStatus, false], ['Sumber', $bySource, false]] as [$heading, $rows, $isType])
            <x-filament::section>
                <x-slot name="heading">{{ $heading }}</x-slot>
                <div class="space-y-2">
                    @forelse ($rows as $key => $count)
                        <div class="flex justify-between gap-3 border-b border-gray-100 pb-2 dark:border-white/10">
                            <span>{{ $isType ? config("record_types.{$key}.label", $key) : $key }}</span><strong>{{ $count }}</strong>
                        </div>
                    @empty
                        <p class="text-gray-500">Tiada data.</p>
                    @endforelse
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
