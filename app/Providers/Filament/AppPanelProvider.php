<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Http\Middleware\ApplyTenantScopes;
use App\Http\Middleware\EnsureMosqueActive;
use App\Http\Middleware\EnsurePasswordIsSet;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\Mosque;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// §9.C — Panel MASJID /app/{slug} (tenancy Mosque; pendaftaran tenant DIMATIKAN)
class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->brandName('Diwan')
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.svg'))
            ->viteTheme('resources/css/filament/theme.css')
            ->login(Login::class)
            ->strictAuthorization()
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => view('filament.auth.login-hints', ['panel' => 'app'])->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => view('filament.help-launcher', ['panel' => 'app'])->render(),
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): string => view('filament.help-assets')->render(),
            )
            // §10 Aliran I — banner persediaan pada Dashboard sehingga onboarding selesai.
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                function (): string {
                    $mosque = Filament::getTenant();
                    $user = Auth::user();
                    if (! $mosque || ! $user?->canIn($mosque, 'mosque.settings')
                        || filled(data_get($mosque->settings, 'onboarding_done'))) {
                        return '';
                    }

                    return view('filament.app.partials.onboarding-banner')->render();
                },
                scopes: [Dashboard::class],
            )
            ->tenant(Mosque::class, slugAttribute: 'slug')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsActive::class,
                EnsurePasswordIsSet::class,
            ])
            ->tenantMiddleware([
                ApplyTenantScopes::class,
                EnsureMosqueActive::class,
            ], isPersistent: true);
    }
}
