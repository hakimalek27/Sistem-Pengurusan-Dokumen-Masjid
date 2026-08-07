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
        self::$barisPertamaId = null;

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('record.title')->label('Rekod')->wrap()->limit(50)
                    ->extraCellAttributes(fn ($record): array => self::baris1($record, 'approval-record')),
                TextColumn::make('requestedBy.name')->label('Pemohon'),
                TextColumn::make('request_note')->label('Nota')->wrap()->limit(40)->placeholder('—'),
                TextColumn::make('created_at')->label('Tarikh')->date('d/m/Y'),
                TextColumn::make('status')->label('Status')->badge()
                    ->extraCellAttributes(fn ($record): array => self::baris1($record, 'approval-status')),
                TextColumn::make('onBehalfOf.name')->label('Bagi pihak')->placeholder('—')->toggleable(),
            ])
            // F7 §8.3 (axe `empty-table-header` minor) — sel header lajur tindakan
            // kosong walaupun `aria-label` wujud; axe menuntut TEKS atau `aria-hidden`.
            // API semasa: `recordActionsColumnLabel()` (HasRecordActions.php:76);
            // `actionsColumnLabel()` ialah alias @deprecated (:162-164) — jangan guna.
            ->recordActionsColumnLabel('Tindakan')
            ->recordActions([
                self::decideAction('lulus', 'Lulus', 'success', ApprovalStatus::Lulus, false),
                self::decideAction('tolak', 'Tolak', 'danger', ApprovalStatus::Tolak, true),
            ]);
    }

    /** F6-W1 — sasaran hanya pada baris pertama yang dirender (rujuk MinitsTable::baris1). */
    protected static ?int $barisPertamaId = null;

    protected static function baris1($record, string $target): array
    {
        self::$barisPertamaId ??= (int) $record->getKey();

        return self::$barisPertamaId === (int) $record->getKey()
            ? ['data-help-target' => $target]
            : [];
    }

    protected static function decideAction(string $name, string $label, string $color, ApprovalStatus $decision, bool $noteRequired): Action
    {
        // F6-W1 (§7.2) — sasaran tour pada baris PERTAMA sahaja (keunikan G2).
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->icon($decision === ApprovalStatus::Lulus ? 'heroicon-o-check' : 'heroicon-o-x-mark')
            ->extraAttributes(fn ($record): array => self::baris1($record, "approval-{$name}"))
            ->modalSubmitAction(fn (Action $action): Action => $action
                ->extraAttributes(['data-help-target' => 'approval-submit']))
            ->authorize('decide')
            ->visible(fn ($record) => $record->status === ApprovalStatus::Menunggu
                && app(DelegationService::class)->canActFor(Auth::user(), $record->approver, $record->mosque, 'approvals'))
            ->schema([
                TextInput::make('password')->label('Sahkan Kata Laluan')->password()->required()
                    ->extraFieldWrapperAttributes(['data-help-target' => 'approval-password']),
                Textarea::make('note')->label('Nota')->required($noteRequired)
                    ->extraFieldWrapperAttributes(['data-help-target' => 'approval-note']),
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
