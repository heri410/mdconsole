<?php

namespace App\Providers;

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
        // Locale auf Deutsch setzen
        app()->setLocale('de');
        
        // HTTPS erzwingen für Produktion und ngrok
        if (config('app.env') !== 'local' || config('app.force_https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            // Auch für Asset-URLs HTTPS erzwingen
            if (app()->environment('production') || config('app.force_https')) {
                $this->app['url']->forceScheme('https');
            }
        }
        
        // Explizit Asset-URLs auf HTTPS setzen wenn APP_URL https ist
        if (str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
