<?php

namespace App\Filament\Admin\Resources\Mosques\Tables;

use App\Enums\MosqueStatus;
use App\Services\MosqueProvisioningService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MosquesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->wrap(),
                TextColumn::make('code')->label('Kod')->searchable(),
                TextColumn::make('slug')->label('Slug'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('users_count')->counts('users')->label('Ahli'),
                TextColumn::make('storage_used_bytes')->label('Guna')
                    ->formatStateUsing(fn ($state, $record) => round($state / (1024 ** 3), 2).' / '.round($record->effectiveQuotaBytes() / (1024 ** 3), 1).' GB'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')
                    ->options(collect(MosqueStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('lulus')
                    ->label('Lulus')->color('success')->icon('heroicon-o-check')
                    ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                    ->visible(fn ($record) => $record->status === MosqueStatus::Menunggu)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        app(MosqueProvisioningService::class)->approve($record, Auth::user());
                        Notification::make()->title('Masjid diluluskan & disediakan.')->success()->send();
                    }),

                Action::make('tolak')
                    ->label('Tolak')->color('danger')->icon('heroicon-o-x-mark')
                    ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                    ->visible(fn ($record) => $record->status === MosqueStatus::Menunggu)
                    ->schema([Textarea::make('reason')->label('Sebab')->required()])
                    ->action(function ($record, array $data) {
                        app(MosqueProvisioningService::class)->reject($record, $data['reason']);
                        Notification::make()->title('Permohonan ditolak.')->success()->send();
                    }),

                Action::make('gantung')
                    ->label(fn ($record) => $record->status === MosqueStatus::Digantung ? 'Aktifkan' : 'Gantung')
                    ->color(fn ($record) => $record->status === MosqueStatus::Digantung ? 'success' : 'warning')
                    ->icon('heroicon-o-pause')
                    ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                    ->visible(fn ($record) => in_array($record->status, [MosqueStatus::Aktif, MosqueStatus::Digantung], true))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $new = $record->status === MosqueStatus::Digantung ? MosqueStatus::Aktif : MosqueStatus::Digantung;
                        $record->update(['status' => $new]);
                        Notification::make()->title('Status masjid: '.$new->getLabel())->success()->send();
                    }),

                Action::make('ubahKuota')
                    ->label('Ubah Kuota')->icon('heroicon-o-circle-stack')
                    ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                    ->schema([
                        TextInput::make('gb')->label('Kuota Asas (GB)')->numeric()->required()
                            ->default(fn ($record) => (int) round($record->storage_quota_bytes / (1024 ** 3))),
                        Textarea::make('reason')->label('Sebab (wajib)')->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['storage_quota_bytes' => (int) $data['gb'] * (1024 ** 3)]);
                        activity()->performedOn($record)->causedBy(Auth::user())
                            ->withProperties(['gb' => $data['gb'], 'reason' => $data['reason']])->log('ubah_kuota');
                        Notification::make()->title('Kuota dikemas kini.')->success()->send();
                    }),

                Action::make('masukPanel')
                    ->label('Masuk Panel Masjid')->icon('heroicon-o-arrow-top-right-on-square')
                    ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                    ->url(fn ($record) => url('/app/'.$record->slug))
                    ->openUrlInNewTab(),
            ]);
    }
}
