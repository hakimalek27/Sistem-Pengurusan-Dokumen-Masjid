<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Sesi WhatsApp Platform</x-slot>
        <x-slot name="description">
            Nombor WhatsApp khas platform untuk menghantar alert kepada superadmin
            (contoh: bila sesi WhatsApp sesebuah masjid terputus). Nombor ini tidak
            terikat mana-mana masjid.
        </x-slot>

        <dl class="grid grid-cols-2 gap-2 text-sm">
            <dt class="text-gray-500">Status</dt>
            <dd>
                @if ($pairStatus === 'connected')
                    <span class="text-emerald-600 font-medium">Bersambung</span>
                @elseif ($pairStatus)
                    <span class="text-amber-600">{{ ucfirst($pairStatus) }}</span>
                @else
                    <span class="text-gray-400">Belum diaktifkan</span>
                @endif
            </dd>
            <dt class="text-gray-500">Nombor</dt><dd>{{ $integration?->phone ?? '—' }}</dd>
            <dt class="text-gray-500">Diaktifkan</dt><dd>{{ $integration?->enabled ? 'Ya' : 'Tidak' }}</dd>
            @if ($integration?->last_error)
                <dt class="text-gray-500">Ralat terakhir</dt><dd class="text-danger-600">{{ $integration->last_error }}</dd>
            @endif
        </dl>

        @if ($linkingCode)
            <div class="mt-4 rounded-lg bg-primary-50 p-4 dark:bg-primary-500/10">
                <p class="text-sm">Masukkan kod pautan ini di WhatsApp telefon platform (Peranti Terpaut → Pautkan dengan nombor telefon):</p>
                <p class="mt-2 text-2xl font-mono font-bold tracking-widest">{{ $linkingCode }}</p>
            </div>
        @endif

        @if ($qr)
            <div class="mt-4">
                <p class="text-sm mb-2">Imbas kod QR ini dengan WhatsApp telefon platform:</p>
                <img src="data:image/png;base64,{{ $qr }}" alt="Kod QR WhatsApp" class="w-56 h-56" />
            </div>
        @endif

        @if ($pairStatus && $pairStatus !== 'connected')
            <p class="mt-4 text-xs text-gray-500">
                Selepas mengimbas/memasukkan kod, tekan <strong>Segerakkan Status</strong> untuk mengesahkan sambungan.
            </p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
