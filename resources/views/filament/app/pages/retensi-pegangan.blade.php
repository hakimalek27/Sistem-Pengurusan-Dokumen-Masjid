<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Status Pelupusan Automatik</x-slot>
        @if ($autoDisposal)
            <p class="text-emerald-600">Pelupusan automatik <strong>AKTIF</strong> — rekod cukup tempoh akan dipadam
                selepas notis 90/30/7 hari (jenis kekal & rekod berpegangan dikecualikan).</p>
        @else
            <p class="text-gray-500">Pelupusan automatik <strong>DIMATIKAN</strong> untuk masjid ini —
                pelupusan hanya melalui aliran manual.</p>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Jadual Retensi Efektif</x-slot>
        <p class="mb-3 text-sm text-gray-500">Keputusan sebenar selepas resolusi override masjid, jenis rekod dan prefix klasifikasi.</p>
        @if ($effectiveRetention->isEmpty())
            <p class="text-gray-500">Tiada rekod dalam skop 12 bulan.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th>Rujukan</th><th>Tajuk</th><th>Sumber</th><th>Tempoh</th><th>Tindakan</th><th>Cukup Tempoh</th></tr></thead>
                    <tbody>
                        @foreach ($effectiveRetention as $row)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $row['reference'] }}</td><td>{{ $row['title'] }}</td><td>{{ $row['source'] }}</td>
                                <td>{{ $row['years'] === 'Kekal' ? 'Kekal' : $row['years'].' tahun' }}</td><td>{{ $row['action'] }}</td><td>{{ $row['due'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Akan Cukup Tempoh (≤ 12 bulan)</x-slot>
        @if ($records->isEmpty())
            <p class="text-gray-500">Tiada rekod menghampiri tempoh retensi.</p>
        @else
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500">
                    <th class="py-1">Rujukan</th><th>Tajuk</th><th>Cukup Tempoh</th><th>Pegangan</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach ($records as $r)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-1">{{ $r->registryFile?->file_no }}({{ $r->enclosure_no }})</td>
                            <td class="truncate max-w-xs">{{ $r->title }}</td>
                            <td>{{ optional($r->retention_due_at)->format('d/m/Y') }}</td>
                            <td>{{ $r->legal_hold ? '🔒 Ya' : '—' }}</td>
                            <td>
                                @if ($canHold)
                                    <x-filament::button size="xs" color="gray" wire:click="toggleHold({{ $r->id }})">
                                        {{ $r->legal_hold ? 'Tarik Hold' : 'Legal Hold' }}
                                    </x-filament::button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Eksport Sedia Dimuat Turun</x-slot>
        @if ($exports->isEmpty())
            <p class="text-gray-500">Belum ada eksport aktif.</p>
        @else
            <div class="space-y-2">
                @foreach ($exports as $export)
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-white/10">
                        <div>
                            <div class="font-medium">{{ $export->label }}</div>
                            <div class="text-xs text-gray-500">Luput {{ $export->expires_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <a class="text-primary-600 underline" href="{{ app(\App\Services\SecureDownloadUrl::class)->export($export) }}">Muat Turun ZIP</a>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
