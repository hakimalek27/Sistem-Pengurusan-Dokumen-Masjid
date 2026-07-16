<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Models\User;
use App\Support\Roles;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Alamat E-mel')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->label('E-mel Disahkan')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_superadmin')
                    ->label('Superadmin')
                    ->boolean(),
                TextColumn::make('phone_wa')
                    ->label('No. WhatsApp')
                    ->searchable(),
                TextColumn::make('telegram_chat_id')
                    ->label('ID Telegram')
                    ->searchable(),
                TextColumn::make('jawatan')
                    ->label('Jawatan')
                    ->searchable(),
                TextColumn::make('tenant_roles')
                    ->label('Tenant & Peranan')
                    ->state(fn (User $record) => $record->mosques()->get()
                        ->map(fn ($mosque) => $mosque->name.' — '.Roles::label((string) $mosque->pivot->role))
                        ->join(', '))
                    ->placeholder('Tiada keahlian tenant')
                    ->wrap(),
                IconColumn::make('notify_whatsapp')
                    ->label('Notifikasi WA')
                    ->boolean(),
                IconColumn::make('notify_telegram')
                    ->label('Notifikasi Telegram')
                    ->boolean(),
                IconColumn::make('notify_email')
                    ->label('Notifikasi E-mel')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Log Masuk Terakhir')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Status Aktif'),
                TernaryFilter::make('is_superadmin')->label('Superadmin'),
            ])
            ->recordActions([
                EditAction::make()->label('Edit'),
                Action::make('toggleActive')
                    ->label(fn (User $record) => $record->is_active ? 'Nyahaktifkan' : 'Aktifkan')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-user-minus' : 'heroicon-o-user-plus')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        if ($record->is(Auth::user())) {
                            Notification::make()->title('Anda tidak boleh menyahaktifkan akaun sendiri.')->danger()->send();

                            throw new Halt;
                        }

                        if ($record->is_active && $record->is_superadmin
                            && User::query()->where('is_superadmin', true)->where('is_active', true)->count() <= 1) {
                            Notification::make()->title('Superadmin aktif terakhir tidak boleh dinyahaktifkan.')->danger()->send();

                            throw new Halt;
                        }

                        $record->update(['is_active' => ! $record->is_active]);
                        activity()->performedOn($record)->causedBy(Auth::user())
                            ->withProperties(['is_active' => $record->is_active, 'ip' => request()->ip()])
                            ->log('ubah_status_pengguna');
                        Notification::make()->title($record->is_active ? 'Akaun diaktifkan.' : 'Akaun dinyahaktifkan.')->success()->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
