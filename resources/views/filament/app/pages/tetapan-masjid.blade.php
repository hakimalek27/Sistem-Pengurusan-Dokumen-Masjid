<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Profil Masjid</x-slot>
        <dl class="grid grid-cols-2 gap-2 text-sm">
            <dt class="text-gray-500">Nama</dt><dd>{{ $mosque->name }}</dd>
            <dt class="text-gray-500">Kod (kunci selepas ada fail)</dt><dd>{{ $mosque->code }}</dd>
            <dt class="text-gray-500">Negeri / Daerah</dt><dd>{{ $mosque->state }} {{ $mosque->district ? '· '.$mosque->district : '' }}</dd>
        </dl>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">WhatsApp Masjid</x-slot>
        <dl class="grid grid-cols-2 gap-2 text-sm">
            <dt class="text-gray-500">ID Sesi (gateway)</dt><dd>{{ $mosque->wa_session_id ?? '— (belum daftar QR)' }}</dd>
            <dt class="text-gray-500">Nombor</dt><dd>{{ $mosque->wa_number ?? '—' }}</dd>
            <dt class="text-gray-500">Kata Kunci Intake</dt><dd>{{ $mosque->waIntakeKeyword() }}</dd>
            <dt class="text-gray-500">Terima Dokumen WA</dt><dd>{{ $mosque->waIntakeEnabled() ? 'Ya' : 'Tidak' }}</dd>
        </dl>
        <p class="mt-2 text-xs text-gray-500">✋ Daftar nombor WA masjid dengan imbas QR di gateway wassap.wehdah.my, kemudian isi ID Sesi di atas.</p>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">E-mel Pengimbas</x-slot>
        <p class="text-sm">Set mesin pengimbas untuk hantar PDF ke: <strong>{{ $scanEmail }}</strong></p>
    </x-filament::section>
</x-filament-panels::page>
