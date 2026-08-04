<div class="space-y-5 text-sm">
    <dl class="grid gap-4 sm:grid-cols-2">
        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-400">Tarikh dan masa</dt>
            <dd class="mt-1 text-gray-950 dark:text-white">{{ $log->created_at?->format('d/m/Y h:i:s A') ?? '—' }}</dd>
        </div>
        <div data-help-target="log-actor">
            <dt class="font-medium text-gray-500 dark:text-gray-400">Pelaku</dt>
            <dd class="mt-1 text-gray-950 dark:text-white">
                {{ $log->actor_name ?: 'Sistem / penghantar luar' }}
                @if ($log->actor_role)
                    <span class="text-gray-500">({{ $log->actor_role }})</span>
                @endif
            </dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="font-medium text-gray-500 dark:text-gray-400">Aktiviti</dt>
            <dd class="mt-1 text-gray-950 dark:text-white">{{ $log->description }}</dd>
        </div>
        <div data-help-target="log-record">
            <dt class="font-medium text-gray-500 dark:text-gray-400">Rekod</dt>
            <dd class="mt-1 text-gray-950 dark:text-white">{{ $log->record_title ?: '—' }}</dd>
            @if ($log->record_reference)
                <dd class="text-gray-500">{{ $log->record_reference }}</dd>
            @endif
        </div>
        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-400">Fail</dt>
            <dd class="mt-1 text-gray-950 dark:text-white">{{ $log->file_no ?: '—' }}</dd>
            @if ($log->file_title)
                <dd class="text-gray-500">{{ $log->file_title }}</dd>
            @endif
        </div>
        <div data-help-target="log-source">
            <dt class="font-medium text-gray-500 dark:text-gray-400">Sumber / pengirim</dt>
            <dd class="mt-1 text-gray-950 dark:text-white">
                {{ match ($log->source_channel) {
                    'muat_naik' => 'Dashboard',
                    'emel' => 'E-mel',
                    'whatsapp' => 'WhatsApp',
                    'imbasan' => 'Imbasan',
                    default => $log->source_channel ?: '—',
                } }}@if ($log->source_identifier): {{ $log->source_identifier }}@endif
            </dd>
        </div>
        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-400">Alamat IP</dt>
            <dd class="mt-1 font-mono text-gray-950 dark:text-white">{{ $log->ip_address ?: '—' }}</dd>
        </div>
    </dl>

    {{-- F6-W1: dirender TANPA SYARAT — sasaran tour mesti wujud walaupun peristiwa itu
         tiada metadata, jika tidak langkah terakhir jatuh ke ralat palsu. --}}
    <div data-help-target="log-metadata">
        <h3 class="font-medium text-gray-500 dark:text-gray-400">Metadata peristiwa</h3>
        @if ($log->metadata)
            <pre class="mt-2 max-h-72 overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-900 dark:text-gray-200">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        @else
            <p class="mt-2 text-gray-500">Tiada metadata tambahan bagi peristiwa ini.</p>
        @endif
    </div>
</div>
