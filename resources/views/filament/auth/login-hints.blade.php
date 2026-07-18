@php($panel = $panel ?? 'app')

<div class="mt-6 space-y-1 text-center text-sm text-gray-500 dark:text-gray-400">
    @if ($panel === 'admin')
        <p>
            Ahli masjid? Log masuk di panel masjid →
            <a href="{{ url('/app/login') }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">/app/login</a>
        </p>
    @else
        <p>
            Pentadbir platform? →
            <a href="{{ url('/admin/login') }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">/admin/login</a>
        </p>
        <p>
            Tiada kata laluan?
            <a href="{{ url('/log-masuk') }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Dapatkan pautan log masuk</a>
        </p>
    @endif
</div>
