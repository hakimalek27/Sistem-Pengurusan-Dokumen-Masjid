<?php

namespace App\Filament\App\Resources\RegistryFiles\Schemas;

use App\Models\ClassificationNode;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RegistryFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // §15.2 — nod DISKOP tenant; hanya aktiviti/sub-aktiviti boleh menyimpan fail.
                Select::make('classification_node_id')
                    ->label('Nod Klasifikasi (Aktiviti / Sub-Aktiviti)')
                    ->options(fn () => ClassificationNode::query()
                        ->where('mosque_id', Filament::getTenant()?->id)
                        ->whereIn('level', ['aktiviti', 'sub_aktiviti'])
                        ->where('is_active', true)
                        ->orderBy('code')
                        ->pluck('title', 'id')
                        ->map(fn ($title, $id) => $title)
                        ->toArray())
                    ->getOptionLabelUsing(fn ($value) => optional(ClassificationNode::find($value))->title)
                    ->searchable()
                    // F6-W1 (§7.2) — sasaran tour `screen.buka-fail-baharu`.
                    ->extraFieldWrapperAttributes(['data-help-target' => 'regfile-node'])
                    ->required(),
                TextInput::make('title')
                    ->label('Tajuk Fail')
                    ->extraFieldWrapperAttributes(['data-help-target' => 'regfile-title'])
                    ->required()
                    ->maxLength(255),
                Select::make('medium')->label('Medium Rekod')->options(['elektronik' => 'Elektronik', 'hibrid' => 'Hibrid', 'fizikal' => 'Fizikal'])->default('elektronik')
                    ->extraFieldWrapperAttributes(['data-help-target' => 'regfile-medium'])
                    ->required(),
                TextInput::make('physical_reference')->label('Rujukan Salinan Fizikal')->maxLength(255)
                    ->extraFieldWrapperAttributes(['data-help-target' => 'regfile-physical']),
                TextInput::make('physical_location')->label('Lokasi Fizikal')->maxLength(255)
                    ->extraFieldWrapperAttributes(['data-help-target' => 'regfile-location']),
            ]);
    }
}
