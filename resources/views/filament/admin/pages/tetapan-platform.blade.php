<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Harga & Bank</x-slot>
        <dl class="grid grid-cols-2 gap-2 text-sm">
            <dt class="text-gray-500">Harga RM/GB/tahun</dt><dd>{{ $pricing['per_gb_year_rm'] ?? '✋ belum ditetapkan' }}</dd>
            <dt class="text-gray-500">Saiz Blok (GB)</dt><dd>{{ $pricing['block_gb'] ?? 10 }}</dd>
            <dt class="text-gray-500">Bank</dt><dd>{{ $bank['bank'] ?? '—' }}</dd>
            <dt class="text-gray-500">No. Akaun</dt><dd>{{ $bank['account_no'] ?? '—' }}</dd>
            <dt class="text-gray-500">Pendaftaran</dt><dd>{{ $registrationOpen ? 'Terbuka' : 'Ditutup' }}</dd>
            <dt class="text-gray-500">Status Gateway WA</dt><dd>{{ is_null($gatewayStatus['ok']) ? '—' : ($gatewayStatus['ok'] ? 'OK' : 'GAGAL') }}</dd>
        </dl>
    </x-filament::section>
    <x-filament::section>
        <x-slot name="heading">Runbook Insiden (PDPA 72 jam)</x-slot>
        <p class="text-sm text-gray-500">Kesan & bendung (jam 0-4) → siasat skop (Log Audit/Akses Sulit) → maklum masjid terjejas serta-merta →
        notis Pesuruhjaya ≤72 jam (pengawal) → notis subjek ≤7 hari. Kontak: DPO platform di atas.</p>
    </x-filament::section>
</x-filament-panels::page>
