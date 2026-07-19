<?php

namespace App\Filament\App\Support;

use App\Models\Mosque;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * §8 / §9.C.3 — Jana medan borang Filament secara DINAMIK dari config/record_types.php.
 * Satu komponen, 17 definisi.
 */
class RecordTypeSchema
{
    /** Medan teras (kolum sebenar) yang relevan untuk jenis. */
    public static function coreFields(string $type): array
    {
        $def = config("record_types.{$type}");
        if (! $def) {
            return [];
        }

        $components = [
            TextInput::make('title')->label('Tajuk')->maxLength(255),
        ];

        foreach ($def['core'] ?? [] as $core) {
            $required = str_ends_with($core, '*');
            $name = rtrim($core, '*');
            $component = self::coreComponent($name);
            if ($component) {
                $components[] = $required ? $component->required() : $component;
            }
        }

        return $components;
    }

    /** Medan metadata khusus jenis (JSONB, statePath metadata.*). */
    public static function metadataFields(string $type): array
    {
        $def = config("record_types.{$type}");
        $components = [];

        foreach ($def['fields'] ?? [] as $field) {
            $component = self::metaComponent($field)->statePath("metadata.{$field['name']}");
            $components[] = ($field['required'] ?? false) ? $component->required() : $component;
        }

        return $components;
    }

    protected static function coreComponent(string $name): ?object
    {
        return match ($name) {
            'direction' => Select::make('direction')->label('Arah')
                ->options(['masuk' => 'Masuk', 'keluar' => 'Keluar', 'dalaman' => 'Dalaman']),
            'our_ref' => TextInput::make('our_ref')->label('Ruj. Kami'),
            'their_ref' => TextInput::make('their_ref')->label('Ruj. Tuan'),
            'record_date' => DatePicker::make('record_date')->label('Tarikh Rekod')->native(false)->displayFormat('d/m/Y'),
            'received_date' => DatePicker::make('received_date')->label('Tarikh Terima')->native(false)->displayFormat('d/m/Y'),
            'sender_name' => TextInput::make('sender_name')->label('Nama Pengirim'),
            'sender_org' => TextInput::make('sender_org')->label('Organisasi Pengirim'),
            'recipient_name' => TextInput::make('recipient_name')->label('Nama Penerima'),
            default => null,
        };
    }

    protected static function metaComponent(array $field): object
    {
        $label = $field['label'] ?? $field['name'];

        // §6.5.9 — "u.p." (Untuk Perhatian) hibrid: teks bebas + autocomplete nama ahli.
        // Jika nilai padan ahli aktif, cadang penerima "Untuk Tindakan" + s.k. Pengerusi —
        // TETAPI hanya bila borang mengandungi edaran minit (Peti Masuk), bukan wizard lain.
        if (($field['name'] ?? null) === 'untuk_perhatian') {
            return TextInput::make($field['name'])
                ->label($label)
                ->datalist(fn () => static::activeMemberNames())
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, Get $get, Set $set, Component $component): void {
                    $hasMinitField = (bool) $component->getRootContainer()
                        ->getComponentByStatePath('minit_action_ids', withAbsoluteStatePath: true);

                    if ($hasMinitField) {
                        static::suggestAttentionRecipients((string) $state, $get, $set);
                    }
                });
        }

        return match ($field['type']) {
            'textarea' => Textarea::make($field['name'])->label($label),
            'date' => DatePicker::make($field['name'])->label($label)->native(false)->displayFormat('d/m/Y'),
            'number' => TextInput::make($field['name'])->label($label)->numeric(),
            'select' => Select::make($field['name'])->label($label)->options($field['options'] ?? []),
            'toggle' => Toggle::make($field['name'])->label($label),
            default => TextInput::make($field['name'])->label($label),
        };
    }

    /** Nama ahli aktif tenant semasa untuk datalist autocomplete u.p. */
    protected static function activeMemberNames(): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant instanceof Mosque) {
            return [];
        }

        return $tenant->users()
            ->where('users.is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /** Terapkan cadangan u.p. ke medan minit borang (dipanggil dari afterStateUpdated). */
    protected static function suggestAttentionRecipients(string $value, Get $get, Set $set): void
    {
        $tenant = Filament::getTenant();
        if (! $tenant instanceof Mosque) {
            return;
        }

        $suggestion = static::attentionSuggestion(
            $tenant,
            $value,
            (array) ($get('minit_action_ids', isAbsolute: true) ?? []),
            (array) ($get('minit_cc_ids', isAbsolute: true) ?? []),
        );

        $set('minit_action_ids', $suggestion['action_ids'], isAbsolute: true);
        $set('minit_cc_ids', $suggestion['cc_ids'], isAbsolute: true);
    }

    /**
     * §6.5.9 — logik tulen cadangan u.p. (boleh diuji tanpa borang Filament).
     * Bila u.p. padan nama ahli aktif (tepat, tidak sensitif huruf besar/kecil):
     * cadang ahli itu sebagai penerima "Untuk Tindakan" (HANYA jika belum ada pilihan)
     * + tambah Pengerusi ke s.k. Nama luar sistem / tiada padanan → nilai kekal seadanya.
     *
     * @param  array<int, mixed>  $currentActionIds
     * @param  array<int, mixed>  $currentCcIds
     * @return array{action_ids: array<int, int>, cc_ids: array<int, int>}
     */
    public static function attentionSuggestion(Mosque $tenant, ?string $value, array $currentActionIds, array $currentCcIds): array
    {
        $result = [
            'action_ids' => array_values(array_map('intval', $currentActionIds)),
            'cc_ids' => array_values(array_map('intval', $currentCcIds)),
        ];

        $value = trim((string) $value);
        if ($value === '') {
            return $result;
        }

        $match = $tenant->users()
            ->where('users.is_active', true)
            ->whereRaw('LOWER(users.name) = ?', [mb_strtolower($value)])
            ->first();
        if (! $match) {
            return $result;
        }

        // Cadang penerima tindakan hanya bila belum ada pilihan (jangan tindih kerani).
        if (blank($currentActionIds)) {
            $result['action_ids'] = [(int) $match->id];
        }

        // s.k. automatik kepada Pengerusi jika ada & bukan orang yang sama.
        $chair = $tenant->users()
            ->where('users.is_active', true)
            ->wherePivot('role', 'pengerusi')
            ->first();

        if ($chair && $chair->id !== $match->id) {
            $cc = collect($result['cc_ids']);
            if (! $cc->contains((int) $chair->id)) {
                $result['cc_ids'] = $cc->push((int) $chair->id)->unique()->values()->all();
            }
        }

        return $result;
    }
}
