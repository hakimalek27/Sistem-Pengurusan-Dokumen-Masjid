<?php

namespace App\Filament\App\Resources\ClassificationNodes;

use App\Filament\App\Resources\ClassificationNodes\Pages\CreateClassificationNode;
use App\Filament\App\Resources\ClassificationNodes\Pages\EditClassificationNode;
use App\Filament\App\Resources\ClassificationNodes\Pages\ListClassificationNodes;
use App\Filament\App\Resources\ClassificationNodes\Schemas\ClassificationNodeForm;
use App\Filament\App\Resources\ClassificationNodes\Tables\ClassificationNodesTable;
use App\Models\ClassificationNode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ClassificationNodeResource extends Resource
{
    protected static ?string $model = ClassificationNode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Klasifikasi Fail';

    protected static ?string $modelLabel = 'Nod Klasifikasi';

    protected static ?string $pluralModelLabel = 'Klasifikasi Fail';

    protected static string|UnitEnum|null $navigationGroup = 'Pentadbiran';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ClassificationNodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassificationNodesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClassificationNodes::route('/'),
            'create' => CreateClassificationNode::route('/create'),
            'edit' => EditClassificationNode::route('/{record}/edit'),
        ];
    }
}
