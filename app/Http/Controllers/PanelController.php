<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Panel;
use App\Models\Dato; // Modelo que corresponde a la tabla 'datos'
use Illuminate\Support\Facades\Auth;

class PanelController extends Controller
{
    // Retorna la vista con la configuración del panel y los datos reales de la tabla
    public function index()
    {
        $user = Auth::user();
        // Se consultan los datos para el cliente asociado al usuario autenticado
        $datos = Dato::where('cliente_id', $user->cliente_id)
                     ->orderBy('fecha', 'asc')
                     ->get();
        return view('panels.index', compact('datos'));
    }

    // Guarda o actualiza la configuración del panel para el usuario autenticado
    public function save(Request $request)
    {
        $data = $request->input('widgetsData');
        $user = Auth::user();

        Panel::updateOrCreate(
            ['user_id' => $user->id],
            ['config' => json_encode($data)]
        );

        return response()->json(['message' => 'Panel guardado correctamente']);
    }

    // Recupera la configuración del panel para el usuario autenticado
    public function get()
    {
        $user = Auth::user();
        $panel = Panel::where('user_id', $user->id)->first();

        if ($panel) {
            return response()->json(['widgetsData' => json_decode($panel->config, true)]);
        }

        return response()->json(['widgetsData' => []]);
    }
}
