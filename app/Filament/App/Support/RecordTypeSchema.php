<?php

namespace App\Filament\App\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

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

        return match ($field['type']) {
            'textarea' => Textarea::make($field['name'])->label($label),
            'date' => DatePicker::make($field['name'])->label($label)->native(false)->displayFormat('d/m/Y'),
            'number' => TextInput::make($field['name'])->label($label)->numeric(),
            'select' => Select::make($field['name'])->label($label)->options($field['options'] ?? []),
            'toggle' => Toggle::make($field['name'])->label($label),
            default => TextInput::make($field['name'])->label($label),
        };
    }
}
