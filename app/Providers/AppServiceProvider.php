<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\MediaObserver;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // §5.14 — kaunter kuota storan atomik.
        Media::observe(MediaObserver::class);

        // §6.0 — superadmin lulus semua kebenaran (Gate::before).
        Gate::before(fn (User $user, string $ability) => $user->is_superadmin ? true : null);
    }
}
