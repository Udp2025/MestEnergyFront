<?php

namespace App\Http\Controllers;

use App\Models\Mediciones;
use Illuminate\Http\Request;

class MedicionesController extends Controller
{
    /**
     * Mostrar todas las mediciones.
     */
    public function index()
    {
        $mediciones = Mediciones::all();
        return view('mediciones.index', compact('mediciones'));
    }

    /**
     * Mostrar el formulario para crear una nueva medición.
     */
    public function create()
    {
        return view('mediciones.create');
    }

    /**
     * Guardar una nueva medición en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'corriente' => 'required|numeric',
            'voltaje' => 'required|numeric',
            'poder' => 'required|numeric',
            'energia' => 'required|numeric',
        ]);

        Mediciones::create($validated);

        return redirect()->route('mediciones.index')->with('success', 'Medición creada exitosamente');
    }

    /**
     * Mostrar una medición específica.
     */
    public function show($id)
    {
        $medicion = Mediciones::findOrFail($id);
        return view('mediciones.show', compact('medicion'));
    }

    /**
     * Mostrar el formulario para editar una medición.
     */
    public function edit($id)
    {
        $medicion = Mediciones::findOrFail($id);
        return view('mediciones.edit', compact('medicion'));
    }

    /**
     * Actualizar una medición existente.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'corriente' => 'required|numeric',
            'voltaje' => 'required|numeric',
            'poder' => 'required|numeric',
            'energia' => 'required|numeric',
        ]);

        $medicion = Mediciones::findOrFail($id);
        $medicion->update($validated);

        return redirect()->route('mediciones.index')->with('success', 'Medición actualizada exitosamente');
    }

    /**
     * Eliminar una medición.
     */
    public function destroy($id)
    {
        $medicion = Mediciones::findOrFail($id);
        $medicion->delete();

        return redirect()->route('mediciones.index')->with('success', 'Medición eliminada exitosamente');
    }
}
