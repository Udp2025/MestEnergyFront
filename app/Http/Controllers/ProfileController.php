<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Mostrar el formulario de edición del perfil.
     * Redirige a la nueva página de configuración.
     */
    public function edit(Request $request): RedirectResponse
    {
        return redirect()->route('config');
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
        $incomingRole = $request->input('role', $user->role ?? 'operaciones');
        if ($incomingRole === 'normal') {
            $incomingRole = 'operaciones';
        }

        $request->merge([
            'name' => $request->input('name', $user->name),
            'email' => $request->input('email', $user->email),
            'role' => $incomingRole,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', Rule::in(['admin', 'operaciones'])],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->role = $data['role'];

        if ($user->email !== $data['email']) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
        }

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('profile_image')) {
            $disk = config('filesystems.default', 'public');
            $directory = env('AWS_S3_IMAGES_PATH', 'profile_images');

            if ($user->profile_image) {
                $disksToCheck = array_unique([$disk, 'public']);
                foreach ($disksToCheck as $diskName) {
                    if (Storage::disk($diskName)->exists($user->profile_image)) {
                        Storage::disk($diskName)->delete($user->profile_image);
                    }
                }
            }

            $path = Storage::disk($disk)->putFile($directory, $request->file('profile_image'), 'public');
            $user->profile_image = $path;
        }

        $user->save();

        $status = $request->filled('password') ? 'password-updated' : 'profile-updated';
        return Redirect::route('profile.edit')->with('status', $status);
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
