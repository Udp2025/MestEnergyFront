<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AreadeCarga;
use App\Models\Cliente;


class AreadeCargaController extends Controller
{
    // Mostrar todas las áreas de carga
    public function index()
    {
        $areasCarga = AreadeCarga::all();
        $clientes = Cliente::all();
        return view('areas_carga.index', compact('areasCarga', 'clientes'));
    }

    // Guardar una nueva área de carga
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        AreadeCarga::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('areas_carga.index')->with('success', 'Área de carga creada exitosamente.');
    }

    // Actualizar una área de carga existente
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $areaCarga = AreadeCarga::findOrFail($id);
        $areaCarga->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('areas_carga.index')->with('success', 'Área de carga actualizada exitosamente.');
    }

    // Eliminar un área de carga
    public function destroy($id)
    {
        $areaCarga = AreadeCarga::findOrFail($id);
        $areaCarga->delete();

        return redirect()->route('areas_carga.index')->with('success', 'Área de carga eliminada exitosamente.');
    }
}
