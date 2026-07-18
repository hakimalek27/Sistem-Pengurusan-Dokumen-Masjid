<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Saluran Platform</x-slot>
        <dl class="grid grid-cols-2 gap-2 text-sm">
            <dt class="text-gray-500">Gateway WhatsApp</dt>
            <dd>
                @if (($gatewayStatus['ok'] ?? null) === true)
                    <span class="text-emerald-600 font-medium">OK</span>
                @elseif (($gatewayStatus['ok'] ?? null) === false)
                    <span class="text-danger-600 font-medium">GAGAL</span>
                @else
                    <span class="text-gray-400">—</span>
                @endif
                @if ($gatewayStatus['checked_at'] ?? null)
                    <span class="text-xs text-gray-400">({{ \Illuminate\Support\Carbon::parse($gatewayStatus['checked_at'])->diffForHumans() }})</span>
                @endif
            </dd>

            <dt class="text-gray-500">IMAP intake e-mel</dt>
            <dd>
                @if ($imapStreak === 0)
                    <span class="text-emerald-600 font-medium">OK</span>
                @else
                    <span class="text-danger-600 font-medium">Gagal ({{ $imapStreak }} kali berturut)</span>
                @endif
            </dd>
        </dl>
        @if ($imapError && $imapStreak > 0)
            <p class="mt-2 text-xs text-danger-600">{{ $imapError }}</p>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Sesi WhatsApp</x-slot>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-500">
                <th class="py-1">Masjid / Platform</th><th>Status</th><th>Nombor</th><th>Segerak Terakhir</th><th>Ralat</th>
            </tr></thead>
            <tbody>
                @forelse ($integrations as $it)
                    <tr class="border-t border-gray-100 dark:border-white/10">
                        <td class="py-2">{{ $it->mosque?->name ?? '★ Platform' }}</td>
                        <td>
                            @if ($it->status === 'connected')
                                <span class="text-emerald-600 font-medium">Bersambung</span>
                            @elseif (! $it->enabled)
                                <span class="text-gray-400">Dimatikan</span>
                            @else
                                <span class="text-amber-600">{{ ucfirst($it->status ?? 'unlinked') }}</span>
                            @endif
                        </td>
                        <td>{{ $it->phone ?? '—' }}</td>
                        <td class="text-gray-500">{{ $it->last_synced_at?->diffForHumans() ?? '—' }}</td>
                        <td class="text-danger-600 text-xs">{{ \Illuminate\Support\Str::limit($it->last_error, 40) ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-3 text-gray-400">Tiada integrasi WhatsApp lagi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>
