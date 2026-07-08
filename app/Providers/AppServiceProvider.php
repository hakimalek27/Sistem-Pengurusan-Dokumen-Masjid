<?php

namespace App\Providers;

use App\Observers\MediaObserver;
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
    }
}
