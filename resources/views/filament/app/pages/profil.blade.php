<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Maklumat Akaun</x-slot>

        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-2 dark:border-white/5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Nama</dt>
                <dd class="text-sm font-medium text-gray-950 dark:text-white">{{ $user->name }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-2 dark:border-white/5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">E-mel</dt>
                <dd class="text-sm text-gray-950 dark:text-white">{{ $user->email ?: '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-2 dark:border-white/5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">No. WhatsApp</dt>
                <dd class="text-sm text-gray-950 dark:text-white">{{ $user->phone_wa ?: '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-2 dark:border-white/5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Telegram</dt>
                <dd>
                    @if ($user->telegram_chat_id)
                        <x-filament::badge color="success">Bersambung</x-filament::badge>
                    @else
                        <x-filament::badge color="gray">Belum bersambung</x-filament::badge>
                    @endif
                </dd>
            </div>
        </dl>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Saluran Notifikasi</x-slot>
        <x-slot name="description">Saluran aktif untuk menerima notifikasi Diwan. Ubah melalui "Tetapan Notifikasi".</x-slot>

        <div class="flex flex-wrap gap-3">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">E-mel</span>
                <x-filament::badge :color="$user->notify_email ? 'success' : 'gray'">{{ $user->notify_email ? 'ON' : 'OFF' }}</x-filament::badge>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">WhatsApp</span>
                <x-filament::badge :color="$user->notify_whatsapp ? 'success' : 'gray'">{{ $user->notify_whatsapp ? 'ON' : 'OFF' }}</x-filament::badge>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Telegram</span>
                <x-filament::badge :color="$user->notify_telegram ? 'success' : 'gray'">{{ $user->notify_telegram ? 'ON' : 'OFF' }}</x-filament::badge>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
