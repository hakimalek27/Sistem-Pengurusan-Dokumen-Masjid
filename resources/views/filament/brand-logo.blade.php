{{-- Jenama Diwan — ikon kubah masjid ringkas + wordmark. Warna waris (currentColor
     ikut tema terang/gelap Filament) dengan aksen emerald pada kubah. --}}
<div class="flex items-center gap-2">
    <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        {{-- kubah --}}
        <path d="M16 3c3.6 2.2 6 6 6 10v1H10v-1c0-4 2.4-7.8 6-10z" class="fill-primary-500" />
        {{-- bulan sabit kecil di puncak --}}
        <circle cx="16" cy="3" r="1.4" class="fill-primary-400" />
        {{-- badan bangunan --}}
        <path d="M8 15h16v3H8zM9 19h14v9H9z" fill="currentColor" opacity="0.85" />
        {{-- pintu gerbang --}}
        <path d="M14 28v-4a2 2 0 1 1 4 0v4z" class="fill-primary-500" />
        {{-- menara kiri/kanan --}}
        <rect x="5" y="13" width="2.5" height="15" rx="1.2" fill="currentColor" opacity="0.7" />
        <rect x="24.5" y="13" width="2.5" height="15" rx="1.2" fill="currentColor" opacity="0.7" />
    </svg>
    <span class="flex items-baseline gap-1.5">
        <span class="text-lg font-bold tracking-tight">Diwan</span>
        @isset($subtitle)
            <span class="text-xs font-medium text-gray-400">· {{ $subtitle }}</span>
        @endisset
    </span>
</div>
