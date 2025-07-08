<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tarifas;
use App\Models\GrupoTarifario;


class TarifasController extends Controller
{
    // Mostrar todas las tarifas
    public function index()
    {
        $grupoTarifarios = GrupoTarifario::all();
        $tarifas = Tarifas::all();

        return view('tarifas.index', compact('grupoTarifarios', 'tarifas'));
    }

    // Crear una nueva tarifa
    public function store(Request $request)
    {
        $request->validate([
            'clasificacion' => 'required|string|max:255',
            'subtransmision' => 'required|numeric|min:0',
            'transmision' => 'required|numeric|min:0',
        ]);

        Tarifas::create([
            'clasificacion' => $request->clasificacion,
            'subtransmision' => $request->subtransmision,
            'transmision' => $request->transmision,
        ]);

        return redirect()->route('tarifas.index')->with('success', 'Tarifa creada con éxito.');
    }

    // Mostrar el formulario para editar una tarifa
    public function edit($id)
    {
        $tarifa = Tarifas::findOrFail($id);
        return view('tarifas.edit', compact('tarifa'));
    }

    // Actualizar una tarifa
    public function update(Request $request, $id)
    {
        $request->validate([
            'clasificacion' => 'required|string|max:255',
            'subtransmision' => 'required|numeric|min:0',
            'transmision' => 'required|numeric|min:0',
        ]);

        $tarifa = Tarifas::findOrFail($id);
        $tarifa->update([
            'clasificacion' => $request->clasificacion,
            'subtransmision' => $request->subtransmision,
            'transmision' => $request->transmision,
        ]);

        return redirect()->route('tarifas.index')->with('success', 'Tarifa actualizada con éxito.');
    }

    // Eliminar una tarifa
    public function destroy($id)
    {
        $tarifa = Tarifas::findOrFail($id);
        $tarifa->delete();

        return redirect()->route('tarifas.index')->with('success', 'Tarifa eliminada con éxito.');
    }
}
