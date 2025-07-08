<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
       // Fuerza la URL raíz según APP_URL
    URL::forceRootUrl(config('app.url'));

    // Si usas HTTPS en el túnel, fuerza el esquema https
    if (str_starts_with(config('app.url'), 'https://')) {
        URL::forceScheme('https');
    }
    }
}
