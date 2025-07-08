<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Mostrar el formulario de edición del perfil.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Actualizar la información del perfil del usuario.
     *
     * Se detecta cuál formulario se envía:
     * - Si se envía el campo "password", se asume actualización de contraseña.
     * - En caso contrario, se actualizan nombre, correo y foto de perfil.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Si se envía el campo "password", se actualiza la contraseña
        if ($request->filled('password') || $request->filled('password_confirmation')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user->password = bcrypt($request->password);
            $user->save();

            return Redirect::route('profile.edit')->with('status', 'password-updated');
        } else {
            // Actualización de datos personales: nombre, email y foto de perfil
            $request->validate([
                'name'           => 'required|string|max:255',
                'email'          => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'profile_image'  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Máx. 2MB
            ]);

            // Actualizamos nombre
            $user->name = $request->name;

            // Si el correo cambia, se anula la verificación
            if ($user->email !== $request->email) {
                $user->email = $request->email;
                $user->email_verified_at = null;
            }

            // Manejo de la imagen de perfil
            if ($request->hasFile('profile_image')) {
                // Eliminar la imagen anterior (si existe)
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                // Guardar la nueva imagen en storage/app/public/profile_images
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $user->profile_image = $path;
            }

            $user->save();

            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        }
    }

    /**
     * Eliminar la cuenta del usuario.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Eliminar la imagen de perfil si existe
        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
