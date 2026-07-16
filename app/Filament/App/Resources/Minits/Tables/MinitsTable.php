<?php

namespace App\Filament\App\Resources\Minits\Tables;

use App\Enums\MinitPriority;
use App\Models\MinitRecipient;
use App\Services\MinitService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MinitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('record.title')->label('Rekod')->wrap()->limit(50),
                TextColumn::make('fromUser.name')->label('Daripada'),
                TextColumn::make('body')->label('Arahan / Catatan')->wrap()->limit(120)->tooltip(fn ($state) => $state),
                TextColumn::make('recipient_summary')->label('Penerima')
                    ->state(fn ($record) => $record->recipients()->with('user')->get()
                        ->map(fn ($recipient) => ($recipient->jenis === 'tindakan' ? 'Tindakan: ' : 's.k.: ').$recipient->user?->name)
                        ->filter()->join(', '))
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('priority')->label('Keutamaan')->badge(),
                TextColumn::make('due_at')->label('Tarikh Akhir')->date('d/m/Y')
                    ->color(fn ($record) => $record->due_at && $record->due_at->isPast() && $record->status->value === 'terbuka' ? 'danger' : null),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                // Ganti "tab" §9.C.5 (Filament 4 tenant-tabs bermasalah) — filter kategori.
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'tindakan' => 'Perlu Tindakan',
                        'makluman' => 'Makluman',
                        'hantar' => 'Saya Hantar',
                        'selesai' => 'Selesai',
                    ])
                    ->query(function ($query, array $data) {
                        $uid = Auth::id();
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'tindakan' => $query->where('status', 'terbuka')->whereIn('id',
                                MinitRecipient::query()->where('user_id', $uid)->where('jenis', 'tindakan')->where('status', '!=', 'selesai')->pluck('minit_id')->all()),
                            'makluman' => $query->whereIn('id',
                                MinitRecipient::query()->where('user_id', $uid)->where('jenis', 'makluman')->pluck('minit_id')->all()),
                            'hantar' => $query->where('from_user_id', $uid),
                            'selesai' => $query->where('status', 'selesai'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('selesai')
                    ->label('Tanda Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('complete')
                    ->visible(fn ($record) => $record->recipients()
                        ->where('user_id', Auth::id())->where('jenis', 'tindakan')->where('status', '!=', 'selesai')->exists())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        app(MinitService::class)->markDone($record, Auth::user());
                        Notification::make()->title('Tindakan minit ditanda selesai.')->success()->send();
                    }),
                Action::make('balas')
                    ->label('Balas & Edarkan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->authorize('reply')
                    ->visible(fn ($record) => $record->recipients()
                        ->where('user_id', Auth::id())->where('jenis', 'tindakan')->exists())
                    ->schema([
                        Select::make('action')->label('Penerima Tindakan')->multiple()
                            ->options(fn ($record) => self::eligibleRecipients($record))->required(),
                        Select::make('cc')->label('Makluman (s.k.)')->multiple()
                            ->options(fn ($record) => self::eligibleRecipients($record)),
                        Textarea::make('body')->label('Catatan')->required(),
                        Select::make('priority')->label('Keutamaan')
                            ->options(['biasa' => 'Biasa', 'segera' => 'Segera', 'kritikal' => 'Kritikal'])->default('biasa')->required(),
                    ])
                    ->action(function ($record, array $data) {
                        app(MinitService::class)->replyAndRoute(
                            $record, Auth::user(), $data['action'], $data['cc'] ?? [], $data['body'], MinitPriority::from($data['priority']),
                        );
                        Notification::make()->title('Balasan minit diedarkan.')->success()->send();
                    }),
            ]);
    }

    protected static function eligibleRecipients($minit): array
    {
        return $minit->mosque->users()->where('users.is_active', true)->get()
            ->filter(fn ($user) => $user->can('view', $minit->record))
            ->pluck('name', 'id')
            ->toArray();
    }
}
