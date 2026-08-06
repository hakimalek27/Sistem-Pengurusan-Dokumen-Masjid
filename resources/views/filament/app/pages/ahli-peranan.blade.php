<x-filament-panels::page>
    {{-- F6-W5: `tenant.ahli-peranan` #3 ("Selepas jemputan, pastikan ahli muncul dalam
         senarai tenant ini"). Sasaran pada SEKSYEN supaya ia wujud walaupun senarai kosong. --}}
    <x-filament::section data-help-target="members-list">
        <x-slot name="heading">Ahli Masjid ({{ $members->count() }})</x-slot>
        <x-slot name="description">Urus peranan, nombor WhatsApp, kata laluan dan keahlian — untuk masjid ini sahaja.</x-slot>

        {{-- F6-W5: id ahli pertama yang dropdown "Tindakan"nya benar-benar dirender.
             Dikira SEKALI di sini supaya sasaran itu deterministik dan unik (keunikan G2). --}}
        @php ($idTindakanPertama = $members->first(fn ($u) => ! $u->is_superadmin)?->id)

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="py-2 pr-3">Nama</th>
                        <th class="py-2 pr-3">E-mel</th>
                        <th class="py-2 pr-3">Peranan</th>
                        <th class="py-2 pr-3">No. WhatsApp Tenant</th>
                        <th class="py-2 pr-3 text-center">Noti WA</th>
                        <th class="py-2 pr-3 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($members as $m)
                        <tr wire:key="member-{{ $m->id }}">
                            <td class="py-3 pr-3 font-medium text-gray-950 dark:text-white">
                                {{ $m->name }}
                                @if ($m->is_superadmin)
                                    <x-filament::badge color="info" class="ml-1 inline-flex">Superadmin</x-filament::badge>
                                @endif
                            </td>
                            <td class="py-3 pr-3 text-gray-500 dark:text-gray-400">{{ $m->email ?: '—' }}</td>
                            {{-- F6-W5: `tenant.ahli-peranan` #6 ("Untuk ubah peranan atau
                                 keluarkan ahli, semak kesan terhadap tugasan/minit dahulu").
                                 Baris PERTAMA yang boleh diubah — bukan `$loop->first`: select
                                 baris superadmin `@disabled`, jadi menyorotnya menunjukkan
                                 kawalan yang pengguna tidak boleh guna. --}}
                            <td class="py-3 pr-3" @if ($m->id === $idTindakanPertama) data-help-target="members-role" @endif>
                                <select
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-50 dark:border-white/10 dark:bg-white/5"
                                    wire:change="changeRole({{ $m->id }}, $event.target.value)"
                                    @disabled($m->is_superadmin)
                                >
                                    @foreach ($roleOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(\App\Support\Roles::canonical((string) $m->pivot->role) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-3 pr-3">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="text"
                                        class="w-36 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-50 dark:border-white/10 dark:bg-white/5"
                                        placeholder="60123456789"
                                        wire:model.defer="whatsappSettings.{{ $m->id }}.phone_wa"
                                        @disabled($m->is_superadmin)
                                    >
                                    @unless ($m->is_superadmin)
                                        <x-filament::button size="xs" color="gray" wire:click="saveWhatsAppSettings({{ $m->id }})">
                                            Simpan
                                        </x-filament::button>
                                    @endunless
                                </div>
                            </td>
                            <td class="py-3 pr-3 text-center">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 disabled:opacity-50 dark:border-white/10 dark:bg-white/5"
                                    wire:model.defer="whatsappSettings.{{ $m->id }}.notify_whatsapp"
                                    @disabled($m->is_superadmin)
                                >
                            </td>
                            <td class="py-3 pr-3 text-right">
                                @unless ($m->is_superadmin)
                                    {{-- F6-W5: `tenant.ahli-peranan` #4 ("Gunakan Hantar Semula
                                         Pautan"). Sasaran pada PENCETUS dropdown, bukan pada
                                         item di dalamnya: item Filament dirender ke DOM tetapi
                                         disembunyikan Alpine sehingga dropdown dibuka, jadi
                                         menyasarkannya memberi sorotan pada elemen yang tidak
                                         kelihatan.
                                         `$loop->first` TIDAK boleh digunakan: baris pertama
                                         boleh jadi superadmin dan `@unless` di atas
                                         menghapuskan dropdownnya — sasaran hilang senyap
                                         (keluarga defect `disposal-actions` W4). Sebab itu id
                                         ahli pertama yang BUKAN superadmin dikira di atas.
                                         ⚠️ Pembalut `<span>` diperlukan: `@if` dalam kedudukan
                                         ATRIBUT tag komponen Blade tidak dikompil
                                         (`syntax error, unexpected token "endif"`). --}}
                                    <x-filament::dropdown placement="bottom-end">
                                        <x-slot name="trigger">
                                            <span @if ($m->id === $idTindakanPertama) data-help-target="members-actions" @endif>
                                            <x-filament::button size="xs" color="gray" icon="heroicon-m-ellipsis-horizontal">
                                                Tindakan
                                            </x-filament::button>
                                            </span>
                                        </x-slot>
                                        <x-filament::dropdown.list>
                                            <x-filament::dropdown.list.item
                                                icon="heroicon-m-link"
                                                wire:click="resendLoginLink({{ $m->id }})"
                                                wire:confirm="Hantar semula pautan log masuk kepada ahli ini?"
                                            >
                                                Hantar Semula Pautan Log Masuk
                                            </x-filament::dropdown.list.item>
                                            <x-filament::dropdown.list.item
                                                icon="heroicon-m-key"
                                                wire:click="resetPassword({{ $m->id }})"
                                                wire:confirm="Jana kata laluan sementara untuk ahli ini?"
                                            >
                                                Set Kata Laluan Sementara
                                            </x-filament::dropdown.list.item>
                                            <x-filament::dropdown.list.item
                                                icon="heroicon-m-trash"
                                                color="danger"
                                                wire:click="removeMember({{ $m->id }})"
                                                wire:confirm="Keluarkan ahli ini dari masjid?"
                                            >
                                                Keluarkan Ahli
                                            </x-filament::dropdown.list.item>
                                        </x-filament::dropdown.list>
                                    </x-filament::dropdown>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
