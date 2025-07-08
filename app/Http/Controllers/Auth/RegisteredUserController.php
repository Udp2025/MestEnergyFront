<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Muestra la vista de registro.
     */
    public function create(): View
    {
        // Obtenemos todos los clientes para mostrarlos en el select
        $clientes = Cliente::all();
        return view('auth.register', compact('clientes'));
    }

    /**
     * Maneja el registro de un nuevo usuario.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validación:
        // - El campo 'cliente_id' es obligatorio solo si el rol es normal.
        // - Se permite que sea null en caso de ser admin.
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'role'          => ['required', 'string', 'in:normal,admin'],
            'cliente_id'    => ['required_if:role,normal', 'nullable', 'exists:clientes,id'],
        ]);

        // Procesar la imagen de perfil si se cargó
        $profileImagePath = null;
        if ($request->hasFile('profile_image')) {
            $profileImagePath = $request->file('profile_image')->store('profile_images', 'public');
        }

        // Si el rol es admin, se asigna null al cliente; de lo contrario se usa el valor enviado
        $clienteId = $request->role === 'admin' ? null : $request->cliente_id;

        // Crear el usuario asignándole el cliente (si corresponde)
        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'profile_image' => $profileImagePath,
            'role'          => $request->role,
            'cliente_id'    => $clienteId,
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', false));
    }
}
