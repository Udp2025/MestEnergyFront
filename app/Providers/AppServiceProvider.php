<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        // Fuerza la URL raíz según APP_URL solo en producción (evita conflictos en dev)
        $appUrl = config('app.url');
        if (app()->environment('production') && $appUrl) {
            URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
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

            $view->with('authContext', [
                'isAuthenticated' => (bool) $user,
                'user' => $user
                    ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'cliente_id' => $user->cliente_id,
                        'site_id' => $site,
                    ]
                    : null,
                'abilities' => [
                    'canViewAllSites' => (bool) $user?->isSuperAdmin(),
                ],
            ]);
        });
    }
}
