<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GrupoTarifario;

class GrupoTarifarioController extends Controller
{
    // Mostrar todos los grupos tarifarios
    public function index()
    {
        $grupoTarifarios = GrupoTarifario::all();
        return view('tarifas.index', compact('grupoTarifarios'));
    }

    // Crear un nuevo grupo tarifario
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'factor_carga' => 'required|numeric|min:0',
        ]);

        GrupoTarifario::create([
            'nombre' => $request->nombre,
            'factor_carga' => $request->factor_carga,
        ]);

        return redirect()->route('tarifas.index')->with('success', 'Grupo tarifario creado con éxito.');
    }

    // Mostrar el formulario para editar un grupo tarifario
    public function edit($id)
    {
        $grupoTarifario = GrupoTarifario::findOrFail($id);
        return view('tarifas.edit', compact('grupoTarifario'));
    }

    // Actualizar un grupo tarifario
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'factor_carga' => 'required|numeric|min:0',
        ]);

        $grupoTarifario = GrupoTarifario::findOrFail($id);
        $grupoTarifario->update([
            'nombre' => $request->nombre,
            'factor_carga' => $request->factor_carga,
        ]);

        return redirect()->route('tarifas.index')->with('success', 'Grupo tarifario actualizado con éxito.');
    }

    // Eliminar un grupo tarifario
    public function destroy($id)
    {
        $grupoTarifario = GrupoTarifario::findOrFail($id);
        $grupoTarifario->delete();

        return redirect()->route('tarifas.index')->with('success', 'Grupo tarifario eliminado con éxito.');
    }
}
