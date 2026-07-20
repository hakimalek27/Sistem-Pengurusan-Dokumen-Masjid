<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Saluran Platform</x-slot>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex items-center justify-between gap-2">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Gateway WhatsApp</dt>
                <dd>
                    @if (($gatewayStatus['ok'] ?? null) === true)
                        <x-filament::badge color="success">OK</x-filament::badge>
                    @elseif (($gatewayStatus['ok'] ?? null) === false)
                        <x-filament::badge color="danger">GAGAL</x-filament::badge>
                    @else
                        <x-filament::badge color="gray">Belum diuji</x-filament::badge>
                    @endif
                </dd>
            </div>
            <div class="flex items-center justify-between gap-2">
                <dt class="text-sm text-gray-500 dark:text-gray-400">IMAP intake e-mel</dt>
                <dd>
                    @if (! $imapEnabled)
                        <x-filament::badge color="gray">Dimatikan</x-filament::badge>
                    @elseif ($imapStreak === 0)
                        <x-filament::badge color="success">OK</x-filament::badge>
                    @else
                        <x-filament::badge color="danger">Gagal ({{ $imapStreak }}×)</x-filament::badge>
                    @endif
                </dd>
            </div>
        </dl>
        @if ($imapError && $imapStreak > 0)
            <p class="mt-3 rounded-lg bg-danger-50 p-2 text-xs text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">{{ $imapError }}</p>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Sesi WhatsApp</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="py-2 pr-3">Masjid / Platform</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Nombor</th>
                        <th class="py-2 pr-3">Segerak Terakhir</th>
                        <th class="py-2 pr-3">Ralat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($integrations as $it)
                        <tr>
                            <td class="py-3 pr-3 font-medium text-gray-950 dark:text-white">{{ $it->mosque?->name ?? '★ Platform' }}</td>
                            <td class="py-3 pr-3">
                                @if ($it->status === 'connected')
                                    <x-filament::badge color="success">Bersambung</x-filament::badge>
                                @elseif (! $it->enabled)
                                    <x-filament::badge color="gray">Dimatikan</x-filament::badge>
                                @else
                                    <x-filament::badge color="warning">{{ ucfirst($it->status ?? 'unlinked') }}</x-filament::badge>
                                @endif
                            </td>
                            <td class="py-3 pr-3">{{ $it->phone ?? '—' }}</td>
                            <td class="py-3 pr-3 text-gray-500 dark:text-gray-400">{{ $it->last_synced_at?->diffForHumans() ?? '—' }}</td>
                            <td class="py-3 pr-3 text-xs text-danger-600 dark:text-danger-400">{{ \Illuminate\Support\Str::limit($it->last_error, 40) ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-gray-400">Tiada integrasi WhatsApp lagi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
