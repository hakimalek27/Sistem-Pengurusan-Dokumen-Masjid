<?php

namespace App\Filament\App\Resources\Approvals\Tables;

use App\Enums\ApprovalStatus;
use App\Services\ApprovalService;
use App\Services\DelegationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ApprovalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('record.title')->label('Rekod')->wrap()->limit(50),
                TextColumn::make('requestedBy.name')->label('Pemohon'),
                TextColumn::make('request_note')->label('Nota')->wrap()->limit(40)->placeholder('—'),
                TextColumn::make('created_at')->label('Tarikh')->date('d/m/Y'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('onBehalfOf.name')->label('Bagi pihak')->placeholder('—')->toggleable(),
            ])
            ->recordActions([
                self::decideAction('lulus', 'Lulus', 'success', ApprovalStatus::Lulus, false),
                self::decideAction('tolak', 'Tolak', 'danger', ApprovalStatus::Tolak, true),
            ]);
    }

    protected static function decideAction(string $name, string $label, string $color, ApprovalStatus $decision, bool $noteRequired): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->icon($decision === ApprovalStatus::Lulus ? 'heroicon-o-check' : 'heroicon-o-x-mark')
            ->authorize('decide')
            ->visible(fn ($record) => $record->status === ApprovalStatus::Menunggu
                && app(DelegationService::class)->canActFor(Auth::user(), $record->approver, $record->mosque, 'approvals'))
            ->schema([
                TextInput::make('password')->label('Sahkan Kata Laluan')->password()->required(),
                Textarea::make('note')->label('Nota')->required($noteRequired),
            ])
            ->action(function ($record, array $data) use ($decision) {
                if (! Auth::user()->password || ! Hash::check($data['password'], Auth::user()->password)) {
                    Notification::make()->title('Kata laluan salah.')->danger()->send();

                    throw new Halt;
                }

                app(ApprovalService::class)->decide($record, Auth::user(), $decision, $data['note'] ?? null, request()->ip());

                Notification::make()->title('Keputusan kelulusan direkodkan.')->success()->send();
            });
    }
}
