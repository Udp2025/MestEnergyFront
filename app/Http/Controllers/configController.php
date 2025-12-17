<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class configController extends Controller
{
    /**
     * Datos de perfil propios.
     */
    public function index()
    {
        $user = Auth::user();
        return view('config', compact('user'));
    }

    /**
     * Gestión de usuarios (solo admin o super admin).
     */
    public function manageUsers(Request $request)
    {
        $currentUser = $request->user();
        $this->ensureManager($currentUser);

        $query = User::orderBy('name');
        if (!$currentUser->isSuperAdmin()) {
            $query->where('cliente_id', $currentUser->cliente_id);
        }
        $users = $query->get(['id', 'name', 'email', 'role', 'profile_image', 'cliente_id']);

        return view('config_users', [
            'user' => $currentUser,
            'users' => $users,
        ]);
    }

    /**
     * Crear un nuevo usuario.
     */
    public function storeUser(Request $request)
    {
        $currentUser = $request->user();
        $this->ensureManager($currentUser);

        $clienteId = $currentUser->cliente_id;
        if ($clienteId === null) {
            return back()->withErrors(['cliente_id' => 'No se puede crear el usuario sin un cliente asignado.']);
        }

        $data = $request->validate([
            'new_name' => ['required', 'string', 'max:255'],
            'new_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'new_role' => ['required', Rule::in(['admin', 'operaciones'])],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
            'new_profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
        ]);

        $disk = config('filesystems.default', 'public');
        $directory = env('AWS_S3_IMAGES_PATH', 'profile_images');
        $profileImagePath = null;

        if ($request->hasFile('new_profile_image')) {
            $profileImagePath = Storage::disk($disk)->putFile($directory, $request->file('new_profile_image'), 'public');
        }

        $newUser = User::create([
            'name' => $data['new_name'],
            'email' => $data['new_email'],
            'role' => $data['new_role'],
            'password' => Hash::make($data['new_password']),
            'cliente_id' => $clienteId,
            'profile_image' => $profileImagePath,
        ]);

        return redirect()
            ->route('config.users')
            ->with('user-created', "Usuario {$newUser->name} creado exitosamente.");
    }

    public function updateUser(Request $request, User $user)
    {
        $currentUser = $request->user();
        $this->ensureManager($currentUser);
        $this->ensureSameClient($currentUser, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'operaciones'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('profile_image')) {
            $disk = config('filesystems.default', 'public');
            $directory = env('AWS_S3_IMAGES_PATH', 'profile_images');

            if ($user->profile_image) {
                $this->deleteFromKnownDisks($user->profile_image, $disk);
            }

            $user->profile_image = Storage::disk($disk)->putFile($directory, $request->file('profile_image'), 'public');
        }

        $user->save();

        return redirect()->route('config.users')->with('user-updated', "Usuario {$user->name} actualizado.");
    }

    public function destroyUser(Request $request, User $user)
    {
        $currentUser = $request->user();
        $this->ensureManager($currentUser);
        $this->ensureSameClient($currentUser, $user);

        if ($currentUser->id === $user->id) {
            return back()->withErrors(['delete' => 'No puedes eliminar tu propia cuenta.']);
        }

        if ($user->profile_image) {
            $disk = config('filesystems.default', 'public');
            $this->deleteFromKnownDisks($user->profile_image, $disk);
        }

        $user->delete();

        return redirect()->route('config.users')->with('user-deleted', 'Usuario eliminado.');
    }

    protected function ensureManager(?User $user): void
    {
        if (!$user || ($user->role !== 'admin' && !$user->isSuperAdmin())) {
            abort(403, 'No tienes permisos para gestionar usuarios.');
        }
    }

    protected function ensureSameClient(User $current, User $target): void
    {
        if ($current->isSuperAdmin()) {
            return;
        }
        if ($current->cliente_id === null || $target->cliente_id !== $current->cliente_id) {
            abort(403, 'Solo puedes gestionar usuarios de tu cliente.');
        }
    }

    protected function deleteFromKnownDisks(string $path, string $preferredDisk): void
    {
        foreach (array_unique([$preferredDisk, 'public']) as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
}
