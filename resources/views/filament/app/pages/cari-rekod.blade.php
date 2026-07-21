<x-filament-panels::page>
    <div class="flex flex-wrap items-end gap-2 border-b border-gray-200 pb-4 dark:border-white/10">
        <label class="min-w-64 flex-1 text-sm font-medium">Carian tersimpan
            <select class="fi-input mt-1 block w-full" wire:change="loadSearch($event.target.value)">
                <option value="">Pilih carian</option>
                @foreach ($this->savedSearchOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="min-w-52 flex-1 text-sm font-medium">Nama carian
            <input class="fi-input mt-1 block w-full" wire:model="savedSearchName" maxlength="100">
        </label>
        <label class="flex min-h-9 items-center gap-2 text-sm"><input type="checkbox" wire:model="savedSearchDefault"> Lalai</label>
        <x-filament::button type="button" color="gray" wire:click="saveSearch">Simpan</x-filament::button>
        @if (count($this->savedSearchOptions()) > 0)
            <select class="fi-input min-h-9" wire:change="deleteSearch($event.target.value)">
                <option value="">Padam carian...</option>
                @foreach ($this->savedSearchOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <form wire:submit="search" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <label class="text-sm font-medium md:col-span-2 xl:col-span-4">Teks
            <input type="search" wire:model="query" placeholder="Tajuk, rujukan atau kandungan OCR" class="fi-input mt-1 block w-full">
        </label>
        <label class="text-sm font-medium">Jenis rekod
            <select wire:model="recordType" class="fi-input mt-1 block w-full"><option value="">Semua</option>@foreach ($this->recordTypeOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
        </label>
        <label class="text-sm font-medium">Fail
            <select wire:model="registryFileId" class="fi-input mt-1 block w-full"><option value="">Semua</option>@foreach ($this->registryFileOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
        </label>
        <label class="text-sm font-medium">Arah
            <select wire:model="direction" class="fi-input mt-1 block w-full"><option value="">Semua</option><option value="masuk">Masuk</option><option value="keluar">Keluar</option><option value="dalaman">Dalaman</option></select>
        </label>
        <label class="text-sm font-medium">Sensitiviti
            <select wire:model="sensitivity" class="fi-input mt-1 block w-full"><option value="">Semua dibenarkan</option><option value="umum">Umum</option><option value="dalaman">Dalaman</option><option value="sulit">Sulit</option></select>
        </label>
        <label class="text-sm font-medium">Status
            <select wire:model="status" class="fi-input mt-1 block w-full"><option value="">Semua</option><option value="peti_masuk">Peti Masuk</option><option value="difailkan">Difailkan</option><option value="diganti">Diganti</option></select>
        </label>
        <label class="text-sm font-medium">Saluran
            <select wire:model="sourceChannel" class="fi-input mt-1 block w-full"><option value="">Semua</option><option value="muat_naik">Muat Naik UI</option><option value="emel">E-mel</option><option value="whatsapp">WhatsApp</option><option value="imbasan">Imbasan</option></select>
        </label>
        <label class="text-sm font-medium">Pengirim / organisasi<input wire:model="sender" class="fi-input mt-1 block w-full"></label>
        <label class="text-sm font-medium">Ruj. kami / tuan<input wire:model="reference" class="fi-input mt-1 block w-full"></label>
        <label class="text-sm font-medium">Penerima<input wire:model="recipient" class="fi-input mt-1 block w-full"></label>
        <div></div>
        <label class="text-sm font-medium">Tarikh rekod dari<input type="date" wire:model="recordDateFrom" class="fi-input mt-1 block w-full"></label>
        <label class="text-sm font-medium">Tarikh rekod hingga<input type="date" wire:model="recordDateTo" class="fi-input mt-1 block w-full"></label>
        <label class="text-sm font-medium">Tarikh terima dari<input type="date" wire:model="receivedDateFrom" class="fi-input mt-1 block w-full"></label>
        <label class="text-sm font-medium">Tarikh terima hingga<input type="date" wire:model="receivedDateTo" class="fi-input mt-1 block w-full"></label>
        <div class="md:col-span-2 xl:col-span-4"><x-filament::button type="submit" icon="heroicon-o-magnifying-glass">Cari</x-filament::button></div>
    </form>

    @if ($searched)
        <div>
            <p class="mb-2 text-sm text-gray-500">{{ count($results) }} hasil ditemui.</p>
            <div class="divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-white/10 dark:border-white/10">
                @forelse ($results as $r)
                    <article class="flex items-start gap-3 p-3 hover:bg-gray-50 dark:hover:bg-white/5">
                        <a href="{{ url('/r/'.$r['ulid']) }}" class="min-w-0 flex-1">
                            <div class="font-medium">{{ $r['title'] }}</div>
                            <div class="text-sm text-gray-500">{{ $r['ref'] }} · {{ $r['type'] }} · {{ $r['sensitivity'] }} · {{ $r['date'] }}</div>
                            <div class="text-sm text-gray-500">{{ $r['sender'] }} · {{ $r['source'] }}</div>
                            @if (! empty($r['snippet']))<div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{!! $r['snippet'] !!}</div>@endif
                        </a>
                        <button type="button" wire:click="toggleFavourite({{ $r['id'] }})" class="h-9 w-9 shrink-0" title="{{ $r['favourite'] ? 'Buang kegemaran' : 'Tambah kegemaran' }}" aria-label="{{ $r['favourite'] ? 'Buang kegemaran' : 'Tambah kegemaran' }}">{{ $r['favourite'] ? '★' : '☆' }}</button>
                    </article>
                @empty
                    <p class="p-3 text-gray-500">Tiada rekod sepadan.</p>
                @endforelse
            </div>
        </div>
    @endif
</x-filament-panels::page>
