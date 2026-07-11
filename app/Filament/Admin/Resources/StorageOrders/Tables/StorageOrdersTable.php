<?php

namespace App\Filament\Admin\Resources\StorageOrders\Tables;

use App\Enums\OrderStatus;
use App\Services\BillingService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StorageOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_no')->label('No. Invois')->searchable(),
                TextColumn::make('mosque.name')->label('Masjid')->searchable(),
                TextColumn::make('gb')->label('Saiz')->formatStateUsing(fn ($s) => $s.' GB'),
                TextColumn::make('amount_cents')->label('Jumlah (RM)')->formatStateUsing(fn ($s) => number_format($s / 100, 2)),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])),
            ])
            ->recordActions([
                Action::make('tandakanDibayar')
                    ->label('Tandakan Dibayar')->color('success')->icon('heroicon-o-banknotes')
                    ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                    ->visible(fn ($record) => $record->status === OrderStatus::MenungguBayaran)
                    ->schema([TextInput::make('password')->label('Sahkan Kata Laluan')->password()->required()])
                    ->action(function ($record, array $data) {
                        if (! Auth::user()->password || ! Hash::check($data['password'], Auth::user()->password)) {
                            Notification::make()->title('Kata laluan salah.')->danger()->send();

                            throw new Halt;
                        }

                        app(BillingService::class)->markPaid($record, Auth::user());
                        Notification::make()->title('Pesanan ditandakan dibayar — add-on aktif.')->success()->send();
                    }),

                Action::make('batal')
                    ->label('Batal')->color('gray')->icon('heroicon-o-x-mark')
                    ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                    ->visible(fn ($record) => $record->status === OrderStatus::MenungguBayaran)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => OrderStatus::Dibatalkan])),
            ]);
    }
}
