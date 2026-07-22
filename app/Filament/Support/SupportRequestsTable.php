<?php

namespace App\Filament\Support;

use App\Models\SupportRequest;
use App\Models\User;
use App\Services\HelpDiagnosisService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SupportRequestsTable
{
    public static function configure(Table $table, bool $global = false): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->label('Rujukan')->searchable()->copyable(),
                TextColumn::make('created_at')->label('Diterima')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('mosque.name')->label('Tenant')->visible($global)->placeholder('Awam')->searchable(),
                TextColumn::make('user.name')->label('Pelapor')->placeholder('Orang awam')->searchable(),
                TextColumn::make('category')->label('Kategori')->badge(),
                TextColumn::make('subject')->label('Masalah')->wrap()->limit(70)->searchable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('request_id')->label('ID Permintaan')->toggleable(isToggledHiddenByDefault: true)->copyable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'baharu' => 'Baharu', 'dalam_semakan' => 'Dalam Semakan', 'menunggu_pengguna' => 'Menunggu Pengguna', 'selesai' => 'Selesai',
                ]),
                SelectFilter::make('category')->options(HelpDiagnosisService::CATEGORIES + ['lain' => 'Lain-lain']),
            ])
            ->recordActions([
                ViewAction::make()->label('Butiran'),
                Action::make('kemasKiniStatus')
                    ->label('Kemas Kini')
                    ->icon('heroicon-o-pencil-square')
                    ->authorize(fn (SupportRequest $record): bool => Auth::user()?->can('update', $record) ?? false)
                    ->fillForm(fn (SupportRequest $record): array => ['status' => $record->status, 'assigned_to' => $record->assigned_to])
                    ->schema([
                        Select::make('status')->label('Status')->options([
                            'baharu' => 'Baharu', 'dalam_semakan' => 'Dalam Semakan', 'menunggu_pengguna' => 'Menunggu Pengguna', 'selesai' => 'Selesai',
                        ])->required(),
                        Select::make('assigned_to')->label('Ditugaskan Kepada')
                            ->options(fn () => self::assigneeOptions($global))->searchable(),
                    ])
                    ->action(function (SupportRequest $record, array $data) use ($global): void {
                        $assignedTo = filled($data['assigned_to'] ?? null) ? (int) $data['assigned_to'] : null;
                        if ($assignedTo && ! array_key_exists($assignedTo, self::assigneeOptions($global))) {
                            throw ValidationException::withMessages(['assigned_to' => 'Pegawai tugasan tidak sah untuk skop tiket ini.']);
                        }
                        $record->update([
                            'status' => $data['status'],
                            'assigned_to' => $assignedTo,
                            'resolved_at' => $data['status'] === 'selesai' ? now() : null,
                        ]);
                        Notification::make()->title('Status tiket dikemas kini.')->success()->send();
                    }),
            ]);
    }

    protected static function assigneeOptions(bool $global): array
    {
        if ($global) {
            return User::query()->where('is_superadmin', true)->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
        }
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return [];
        }

        return $tenant->users()->where('users.is_active', true)->wherePivot('role', 'admin_masjid')->orderBy('name')->pluck('name', 'users.id')->all();
    }
}
