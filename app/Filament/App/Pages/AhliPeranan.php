<?php

namespace App\Filament\App\Pages;

use App\Services\MembershipService;
use App\Support\Roles;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AhliPeranan extends Page
{
    protected string $view = 'filament.app.pages.ahli-peranan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $slug = 'ahli-peranan';

    protected static ?string $navigationLabel = 'Ahli & Peranan';

    protected static ?string $title = 'Ahli & Peranan';

    protected static string|UnitEnum|null $navigationGroup = 'Pentadbiran';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant && Auth::user()?->canIn($tenant, 'users.manage');
    }

    public function changeRole(int $userId, string $role): void
    {
        $mosque = Filament::getTenant();
        $target = $mosque->users()->whereKey($userId)->firstOrFail();

        try {
            app(MembershipService::class)->changeRole($mosque, $target, $role, Auth::user());
            Notification::make()->title('Peranan dikemas kini.')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function removeMember(int $userId): void
    {
        $mosque = Filament::getTenant();
        $target = $mosque->users()->whereKey($userId)->firstOrFail();

        try {
            app(MembershipService::class)->remove($mosque, $target, Auth::user());
            Notification::make()->title('Ahli dikeluarkan.')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('jemput')
                ->label('Jemput Ahli')
                ->icon('heroicon-o-user-plus')
                ->schema([
                    TextInput::make('email')->label('E-mel')->email()->required(),
                    TextInput::make('name')->label('Nama')->required(),
                    TextInput::make('phone_wa')->label('No. WhatsApp')->nullable(),
                    Select::make('role')->label('Peranan')->options(Roles::options())->required(),
                ])
                ->action(function (array $data) {
                    app(MembershipService::class)->invite(Filament::getTenant(), $data['email'], $data['name'], $data['role'], $data['phone_wa'] ?? null);
                    Notification::make()->title('Jemputan dihantar.')->success()->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $mosque = Filament::getTenant();

        return [
            'members' => $mosque->users()->get(),
            'roleOptions' => Roles::options(),
        ];
    }
}
