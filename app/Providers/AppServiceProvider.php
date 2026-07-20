<?php

namespace App\Providers;

use App\Contracts\DriveClient;
use App\Models\RetentionRule;
use App\Models\User;
use App\Observers\MediaObserver;
use App\Observers\RetentionRuleObserver;
use App\Services\GoogleDrive\GoogleDriveClient;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // §4.6′ — Klien Google Drive (mirror backup). Ujian tukar dengan
        // FakeDriveClient melalui app()->instance(DriveClient::class, ...).
        $this->app->singleton(
            DriveClient::class,
            GoogleDriveClient::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // §5.14 — kaunter kuota storan atomik.
        Media::observe(MediaObserver::class);
        RetentionRule::observe(RetentionRuleObserver::class);

        // §6.0 — superadmin lulus semua kebenaran kecuali pemadaman kekal.
        // Tenant mesti boleh dipulihkan; pemusnahan kekal hanya melalui runbook operasi.
        Gate::before(function (User $user, string $ability): ?bool {
            if (! $user->is_superadmin) {
                return null;
            }

            return in_array($ability, ['forceDelete', 'forceDeleteAny'], true) ? false : true;
        });

        Gate::define('viewHorizon', fn (User $user): bool => (bool) $user->is_superadmin);

        // §11.2 — Suntik tetapan Telegram dari DB (UI superadmin) ke runtime config
        // (DB-dahulu, fallback env; selamat bila DB belum wujud). Cache 5 min.
        TelegramService::hydrateRuntimeConfig();
    }
}
