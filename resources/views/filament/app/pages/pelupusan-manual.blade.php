<x-filament-panels::page>
    <div class="rounded-lg bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 p-4 text-sm text-red-700 dark:text-red-300">
        <strong>Amaran (§16.2):</strong> Pelupusan memadam blob dokumen secara <strong>kekal</strong> dan tidak boleh
        dikembalikan. Metadata rekod (batu nisan) kekal tersimpan. Pastikan sandaran luar (Eksport ZIP) telah dibuat.
    </div>

    <x-filament::section class="mt-4">
        <x-slot name="heading">Calon Pelupusan</x-slot>
        <p>{{ $candidatesCount }} rekod telah cukup tempoh dan boleh dimasukkan ke batch pelupusan.</p>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Batch Pelupusan</x-slot>
        @if ($batches->isEmpty())
            <p class="text-gray-500">Tiada batch pelupusan.</p>
        @else
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500">
                    <th class="py-1">Batch</th><th>Jenis</th><th>Status</th><th>Bil. Item</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach ($batches as $b)
                        <tr class="border-t border-gray-100 dark:border-white/10" wire:key="batch-{{ $b->id }}">
                            <td class="py-2">#{{ $b->id }}</td>
                            <td>{{ ucfirst($b->kind) }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst($b->status)) }}</td>
                            <td>{{ $b->items()->count() }}</td>
                            <td class="text-right space-x-1">
                                @if ($b->status === 'menunggu_kelulusan' && $canApprove)
                                    <x-filament::button size="xs" color="success" wire:click="approve({{ $b->id }})">Lulus</x-filament::button>
                                @endif
                                @if ($b->status === 'lulus' && $canExecute)
                                    <x-filament::button size="xs" color="danger" wire:click="execute({{ $b->id }})"
                                        wire:confirm="Laksana pelupusan? Blob dipadam kekal.">Laksana</x-filament::button>
                                @endif
                                @if ($b->certificate_path)
                                    <a class="text-xs text-primary-600 underline" href="{{ app(\App\Services\SecureDownloadUrl::class)->certificate($b) }}">Muat Turun Sijil</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-panels::page>
