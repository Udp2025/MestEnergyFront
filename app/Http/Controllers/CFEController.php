<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CFEController extends Controller
{
    /**
     * Mostrar vista principal
     */
    public function index()
    {
        return view('datos_cfe');
    }

    /**
     * Endpoint AJAX: devuelve el último registro de tarifa_region para la region solicitada
     */
    public function getRegion(Request $request)
    {
        $region = $request->query('region');
        if (!$region) {
            return response()->json(null, 400);
        }

        $row = DB::table('tarifa_region')
            ->where('region', $region)
            ->orderBy('id','desc')
            ->first();

        // devolver null si no hay datos
        if (!$row) {
            return response()->json(null);
        }

        return response()->json($row);
    }

    /**
     * Guarda un nuevo registro en tarifa_region usando los arrays mensuales.
     * - guarda JSON de los meses en la columna "mes"
     * - calcula promedios para llenar variable_base, variable_intermedia, variable_punta, distribucion, capacidad
     * - guarda date_actualizacion como timestamp (epoch)
     */
    /**
 * Guarda 12 registros (uno por mes) por cada envío. Siempre INSERT, nunca update.
 */
public function store(Request $request)
{
    $request->validate([
        'region_select' => 'required|string',
        'fijo' => 'nullable',
        'base' => 'required|array',
        'intermedia' => 'required|array',
        'punta' => 'required|array',
        'distribucion' => 'required|array',
        'capacidad' => 'required|array',
    ]);

    $region = $request->input('region_select');
    $fijoRaw = str_replace(',', '.', $request->input('fijo', '0'));
    $fijo = is_numeric($fijoRaw) ? floatval($fijoRaw) : 0;

    $baseArr = $request->input('base', []);
    $interArr = $request->input('intermedia', []);
    $puntaArr = $request->input('punta', []);
    $distArr = $request->input('distribucion', []);
    $capArr = $request->input('capacidad', []);

    // lista fija de meses (en el mismo orden que tu vista)
    $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    // helper: normaliza valor a float (coma -> punto)
    $toFloat = function($v){
        $v = is_null($v) ? '' : $v;
        $v = str_replace(',', '.', (string)$v);
        return is_numeric($v) ? floatval($v) : 0.0;
    };

    $now = time();
    $rows = [];

    foreach ($meses as $mes) {
        $valorBase = array_key_exists($mes, $baseArr) ? $toFloat($baseArr[$mes]) : 0.0;
        $valorInter = array_key_exists($mes, $interArr) ? $toFloat($interArr[$mes]) : 0.0;
        $valorPunta = array_key_exists($mes, $puntaArr) ? $toFloat($puntaArr[$mes]) : 0.0;
        $valorDist  = array_key_exists($mes, $distArr) ? $toFloat($distArr[$mes]) : 0.0;
        $valorCap   = array_key_exists($mes, $capArr)  ? $toFloat($capArr[$mes])  : 0.0;

        $rows[] = [
            'region' => $region,
            'fijo' => $fijo,
            'variable_base' => $valorBase,
            'variable_intermedia' => $valorInter,
            'variable_punta' => $valorPunta,
            'distribucion' => $valorDist,
            'capacidad' => $valorCap,
            'date_actualizacion' => $now,
            'mes' => $mes,
        ];
    }

    try {
        \DB::transaction(function() use ($rows) {
            // insert en batch: 12 filas en una sola query
            \DB::table('tarifa_region')->insert($rows);
        });

        return redirect()->back()->with('success', 'Se insertaron '.count($rows).' registros correctamente para la región: '.$region);
    } catch (\Throwable $e) {
        // log y retorno amigable
        \Log::error('Error al insertar tarifas por mes: '.$e->getMessage(), [
            'region' => $region,
            'rows_count' => count($rows)
        ]);
        return redirect()->back()->with('error', 'Error al guardar tarifas: '.$e->getMessage());
    }
}


    // los demás métodos (opcionalmente mantener vacíos o eliminarlos si no los usas)
    public function create() {}
    public function show(string $id) {}
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}
