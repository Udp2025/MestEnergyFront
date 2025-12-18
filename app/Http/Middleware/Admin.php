<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Importa el facade Auth

class Admin
{
    /**
     * Maneja la petición entrante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo super admins (cliente_id = 0) pueden acceder a rutas de clientes.
        $user = Auth::user();
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Redirige a una ruta segura, por ejemplo, al dashboard, en caso de que el usuario no sea admin
        return redirect()->route('home')
            ->with('error', 'No tienes permisos para acceder a esta sección.');
    }
}
