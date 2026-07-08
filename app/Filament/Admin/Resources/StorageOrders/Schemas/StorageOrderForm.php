<?php

namespace App\Filament\Admin\Resources\StorageOrders\Schemas;

use App\Enums\OrderStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StorageOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('mosque_id')
                    ->relationship('mosque', 'name')
                    ->required(),
                TextInput::make('ordered_by')
                    ->numeric(),
                TextInput::make('gb')
                    ->required()
                    ->numeric(),
                TextInput::make('unit_price_cents')
                    ->required()
                    ->numeric(),
                TextInput::make('amount_cents')
                    ->required()
                    ->numeric(),
                TextInput::make('period_months')
                    ->required()
                    ->numeric()
                    ->default(12),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->default('menunggu_bayaran')
                    ->required(),
                TextInput::make('invoice_no')
                    ->required(),
                TextInput::make('invoice_path'),
                DateTimePicker::make('paid_at'),
                TextInput::make('confirmed_by')
                    ->numeric(),
            ]);
    }
}
