<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

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

        /**
         * Compartir en todas las vistas el "site" y el user_id.
         * - Preferimos el valor guardado en sesión (session('site')) —esto lo seteas en AuthenticatedSessionController::store—.
         * - Si por alguna razón no está en sesión, tratamos de obtenerlo desde el usuario autenticado y su relación cliente.
         */
        View::composer('*', function ($view) {
            $user = Auth::user();

            // Preferimos session('site') porque lo seteamos en el login para evitar consultas repetidas
            $site = session('site') ?? $user?->cliente?->site ?? null;
            $userId = session('user_id') ?? $user?->id ?? null;

            $view->with('auth_user_site', $site);
            $view->with('auth_user_id', $userId);
        });
    }
}
