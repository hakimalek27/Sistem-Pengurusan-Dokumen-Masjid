<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Maklumat Akaun</x-slot>
        <dl class="grid grid-cols-2 gap-2 text-sm">
            <dt class="text-gray-500">Nama</dt><dd>{{ $user->name }}</dd>
            <dt class="text-gray-500">E-mel</dt><dd>{{ $user->email }}</dd>
            <dt class="text-gray-500">No. WhatsApp</dt><dd>{{ $user->phone_wa ?? '—' }}</dd>
            <dt class="text-gray-500">Telegram</dt><dd>{{ $user->telegram_chat_id ? 'Bersambung' : 'Belum bersambung' }}</dd>
        </dl>
        <dl class="grid grid-cols-3 gap-2 text-sm mt-4">
            <dt class="text-gray-500">E-mel</dt><dt class="text-gray-500">WhatsApp</dt><dt class="text-gray-500">Telegram</dt>
            <dd>{{ $user->notify_email ? 'ON' : 'OFF' }}</dd>
            <dd>{{ $user->notify_whatsapp ? 'ON' : 'OFF' }}</dd>
            <dd>{{ $user->notify_telegram ? 'ON' : 'OFF' }}</dd>
        </dl>
    </x-filament::section>
</x-filament-panels::page>
