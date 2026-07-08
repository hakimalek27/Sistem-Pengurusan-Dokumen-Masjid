<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Ahli Masjid ({{ $members->count() }})</x-slot>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-500">
                <th class="py-1">Nama</th><th>E-mel</th><th>Peranan</th><th></th>
            </tr></thead>
            <tbody>
                @foreach ($members as $m)
                    <tr class="border-t border-gray-100 dark:border-white/10" wire:key="member-{{ $m->id }}">
                        <td class="py-2">{{ $m->name }} @if ($m->is_superadmin)<span class="text-xs text-emerald-600">(superadmin)</span>@endif</td>
                        <td>{{ $m->email }}</td>
                        <td>
                            <select class="text-sm rounded border-gray-300 dark:bg-white/5 dark:border-white/10"
                                    wire:change="changeRole({{ $m->id }}, $event.target.value)"
                                    @if ($m->is_superadmin) disabled @endif>
                                @foreach ($roleOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($m->pivot->role === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-right">
                            @unless ($m->is_superadmin)
                                <x-filament::button size="xs" color="danger"
                                    wire:click="removeMember({{ $m->id }})"
                                    wire:confirm="Keluarkan ahli ini dari masjid?">Keluarkan</x-filament::button>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>
