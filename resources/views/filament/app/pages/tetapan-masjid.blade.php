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
        <x-slot name="heading">WhatsApp Masjid — Notifikasi Pilihan</x-slot>
        <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">
            Aktifkan fungsi ini untuk menghantar notifikasi Diwan daripada nombor WhatsApp rasmi organisasi anda.
            Akaun gateway, API key dan pairing diurus terus di SPDM—anda tidak perlu membuka tab lain.
            E-mel kekal dihantar sebagai saluran sandaran.
        </p>
        <dl class="grid grid-cols-2 gap-2 text-sm">
            <dt class="text-gray-500">Status integrasi</dt><dd>{{ $whatsappIntegration?->status ?? 'Belum diaktifkan' }}</dd>
            <dt class="text-gray-500">Notifikasi WhatsApp</dt><dd>{{ $whatsappIntegration?->enabled ? 'Aktif' : 'Tidak aktif' }}</dd>
            <dt class="text-gray-500">Nombor penghantar</dt><dd>{{ $whatsappIntegration?->phone ?? '—' }}</dd>
            <dt class="text-gray-500">API key</dt><dd>{{ $whatsappIntegration?->api_key_prefix ?? '—' }} <span class="text-xs text-gray-400">(nilai penuh disulitkan)</span></dd>
            <dt class="text-gray-500">Kata Kunci Intake</dt><dd>{{ $mosque->waIntakeKeyword() }}</dd>
            <dt class="text-gray-500">Terima Dokumen WA</dt><dd>{{ $mosque->waIntakeEnabled() ? 'Ya' : 'Tidak' }}</dd>
            <dt class="text-gray-500">Segerak terakhir</dt><dd>{{ $whatsappIntegration?->last_synced_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        </dl>

        @if ($whatsappIntegration?->last_error)
            <p class="mt-3 rounded bg-danger-50 p-2 text-sm text-danger-700">{{ $whatsappIntegration->last_error }}</p>
        @endif

        @if ($whatsappQr)
            <div class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-white/10" wire:poll.3s="pollWhatsAppStatus">
                <p class="mb-2 font-medium">Imbas QR ini: WhatsApp → Peranti Terpaut → Pautkan Peranti</p>
                <img class="h-64 w-64 bg-white p-2" src="data:image/png;base64,{{ $whatsappQr }}" alt="Kod QR pairing WhatsApp">
                <x-filament::button class="mt-3" size="sm" color="gray" wire:click="refreshWhatsAppQr">Jana Semula QR</x-filament::button>
            </div>
        @endif

        @if ($whatsappLinkingCode)
            <div class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-white/10" wire:poll.3s="pollWhatsAppStatus">
                <p>Masukkan kod ini di WhatsApp → Peranti Terpaut → Pautkan dengan nombor telefon:</p>
                <p class="mt-2 text-3xl font-bold tracking-widest">{{ $whatsappLinkingCode }}</p>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">E-mel Pengimbas</x-slot>
        @if ($scanEmail)
            <p class="text-sm">Alamat unik tenant: <strong>{{ $scanEmail }}</strong></p>
            <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300">
                <li>Admin aktifkan fungsi dan tetapkan e-mel pengirim dibenarkan melalui “Edit Tetapan”.</li>
                @if ($mosque->mailIntakeKeyword() !== '')
                    <li>Hantar e-mel daripada salah satu alamat itu dengan kata kunci <strong>{{ $mosque->mailIntakeKeyword() }}</strong> pada subjek atau isi.</li>
                @else
                    <li>Hantar e-mel daripada salah satu alamat itu (tiada kata kunci diperlukan).</li>
                @endif
                <li>Lampirkan {{ \App\Support\AllowedFormats::label() }}. Sistem mengambil e-mel setiap minit, memasukkannya ke Peti Masuk dan memulakan OCR.</li>
            </ol>
            <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                <dt class="text-gray-500">Status</dt><dd>{{ $mosque->mailIntakeEnabled() ? 'Aktif' : 'Tidak aktif' }}</dd>
                <dt class="text-gray-500">Kata kunci</dt><dd>{{ $mosque->mailIntakeKeyword() !== '' ? $mosque->mailIntakeKeyword() : 'Tiada (terima semua daripada pengirim dibenarkan)' }}</dd>
                <dt class="text-gray-500">Pengirim dibenarkan</dt><dd>{{ implode(', ', $mosque->mailIntakeSenders()) ?: 'Belum ditetapkan' }}</dd>
            </dl>

            @php($lastIntake = $mosque->settings['mail_intake_last'] ?? null)
            @if ($lastIntake)
                @php($reasonLabels = [
                    'sender_not_allowed' => 'Pengirim tidak dalam senarai dibenarkan',
                    'keyword_missing' => 'Subjek/isi tiada kata kunci intake',
                    'quota' => 'Kuota storan penuh',
                    'rejected_format' => 'Lampiran bukan format disokong',
                    'disabled' => 'Intake e-mel dimatikan',
                ])
                <div class="mt-3 rounded-lg border border-warning-300 bg-warning-50 p-3 text-sm dark:border-warning-500/30 dark:bg-warning-500/10">
                    <p class="font-medium text-warning-800 dark:text-warning-200">E-mel masuk terakhir TIDAK diproses</p>
                    <p class="text-warning-700 dark:text-warning-300">
                        {{ $reasonLabels[$lastIntake['status']] ?? $lastIntake['status'] }}
                        — daripada {{ $lastIntake['from'] ?? '?' }}
                        @if (! empty($lastIntake['at'])) ({{ \Illuminate\Support\Carbon::parse($lastIntake['at'])->format('d/m/Y H:i') }}) @endif
                    </p>
                </div>
            @endif
        @else
            <p class="text-sm text-danger-700">Pentadbir platform belum mengkonfigurasi akaun IMAP. Intake e-mel belum tersedia.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
