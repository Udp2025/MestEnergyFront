<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Autentica (lanza error/redirect si las credenciales son inválidas)
        $request->authenticate();

        // Regenerar sesión para seguridad
        $request->session()->regenerate();

        // Usuario autenticado
        $user = Auth::user();

        if (!$user) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'No se pudo determinar el usuario autenticado.');
        }

        $isSuperAdmin = (int) $user->cliente_id === 0;

        if (!$isSuperAdmin && $user->cliente_id === null) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'El usuario no está vinculado a un cliente.');
        }

        // Cargar solo los campos necesarios del cliente para ahorrar queries
        // incluimos 'id' porque es requerido por Eloquent cuando se limita columnas
        $user->load('cliente:id,site');

        // Obtener el valor 'site' desde la relación cliente (puede ser null)
        $site = $user->cliente?->site ?? null;

        // Guardar en sesión para que esté disponible en todo el proyecto
        $request->session()->put('user_id', $user->id);
        $request->session()->put('site', $site);
        $request->session()->put('is_super_admin', $isSuperAdmin);

        // Redirigir a la URL intencional (dashboard u otra)
        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Logout
        Auth::guard('web')->logout();

        // Limpiar las variables que guardamos en sesión
        $request->session()->forget([
            'user_id',
            'site',
            'is_super_admin',
            'selected_cliente_id',
            'selected_cliente_name',
        ]);

        // Invalidar sesión y regenerar token CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
