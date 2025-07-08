<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Asegúrate de que tu modelo User tenga los campos necesarios
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;


class PermisosUserController extends Controller
{
    // Muestra la vista con la tabla de usuarios
    public function index()
    {
        $usuarios = User::all();
        return view('permisosuser', compact('usuarios'));
    }

    // Devuelve un usuario en formato JSON (para cargar datos en el modal de edición)
    public function show($id)
    {
        $usuario = User::find($id);
        if ($usuario) {
            return response()->json($usuario);
        }
        return response()->json(['message' => 'Usuario no encontrado'], 404);
    }

    // Crea un nuevo usuario
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|max:255|unique:users,correo',
            'rol'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()], 422);
        }

        $usuario = User::create([
            'nombre'      => $request->nombre,
            'correo'      => $request->correo,
            'rol'         => $request->rol, // Si almacenas roles como JSON o array, ajústalo aquí
            // Ejemplo: guardamos la fecha formateada
            'actualizado' => now()->format('d M Y - h:i A'),
        ]);

        return response()->json($usuario, 201);
    }

    // Actualiza un usuario existente
    public function update(Request $request, $id)
    {
        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|max:255|unique:users,correo,' . $id,
            'rol'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()], 422);
        }

        $usuario->update([
            'nombre'      => $request->nombre,
            'correo'      => $request->correo,
            'rol'         => $request->rol,
            'actualizado' => now()->format('d M Y - h:i A'),
        ]);

        return response()->json($usuario);
    }

    // Elimina un usuario
    public function destroy($id)
    {
        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        $usuario->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }
}
