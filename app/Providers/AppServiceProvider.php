<?php
namespace App\Providers;

use App\Models\FootballMatch;
use App\Observers\FootballMatchObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        Schema::defaultStringLength(191);

        // Enregistrer l'observer pour évaluer automatiquement les pronostics
        FootballMatch::observe(FootballMatchObserver::class);

        // Forcer HTTPS en production (Coolify utilise un reverse proxy)
        // if (config('app.env') === 'production' || request()->header('X-Forwarded-Proto') === 'https') {
        //     URL::forceScheme('https');
        // }
    }
}
