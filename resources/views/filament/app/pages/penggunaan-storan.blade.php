<x-filament-panels::page>
    @php($barColor = $percent >= 100 ? 'bg-red-500' : ($percent >= 90 ? 'bg-orange-500' : ($percent >= 80 ? 'bg-yellow-500' : 'bg-emerald-500')))

    <x-filament::section>
        <x-slot name="heading">Tolok Storan</x-slot>
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-500">{{ $usedGb }} GB / {{ $quotaGb }} GB</span>
            <span class="text-sm font-semibold">{{ $percent }}%</span>
        </div>
        <div class="w-full h-4 rounded-full bg-gray-200 dark:bg-white/10 overflow-hidden">
            <div class="h-4 {{ $barColor }}" style="width: {{ min(100, $percent) }}%"></div>
        </div>
        @if ($percent >= 100)
            <p class="mt-2 text-sm text-red-600">Kuota penuh — muat naik baharu disekat. Sila Tambah Storan.</p>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Add-on Storan</x-slot>
        @if ($addons->isEmpty())
            <p class="text-gray-500">Tiada add-on storan.</p>
        @else
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500">
                    <th class="py-1">Saiz</th><th>Mula</th><th>Luput</th><th>Status</th>
                </tr></thead>
                <tbody>
                    @foreach ($addons as $a)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-1">{{ $a->gb }} GB</td>
                            <td>{{ optional($a->starts_at)->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ optional($a->expires_at)->format('d/m/Y') ?? 'Kekal' }}</td>
                            <td>{{ ucfirst($a->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Sejarah Pesanan & Invois</x-slot>
        @if ($orders->isEmpty())
            <p class="text-gray-500">Tiada pesanan storan.</p>
        @else
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500">
                    <th class="py-1">No. Invois</th><th>Saiz</th><th>Jumlah (RM)</th><th>Status</th>
                </tr></thead>
                <tbody>
                    @foreach ($orders as $o)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-1">{{ $o->invoice_no }}</td>
                            <td>{{ $o->gb }} GB</td>
                            <td>{{ number_format($o->amount_cents / 100, 2) }}</td>
                            <td>{{ $o->status->getLabel() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-panels::page>
