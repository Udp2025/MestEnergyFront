<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;


class CFEController extends Controller
{
    /**
     * Mostrar vista principal
     */
    public function index()
    {
        // traer catálogo de regiones para poblar el select
        $regions = DB::table('catalogo_regiones')->orderBy('region')->get();
        return view('datos_cfe', compact('regions'));
    }


    /**
     * Endpoint AJAX: devuelve el último registro de tarifa_region para la region solicitada
     */
    public function getRegion(Request $request)
    {
        // aceptamos region_id como parámetro GET (también soportamos 'region' por compatibilidad)
        $regionId = $request->query('region_id') ?? $request->query('region');
        if (!$regionId) {
            return response()->json(null, 400);
        }

        $row = DB::table('tarifa_region')
            ->where('id_region', $regionId)
            ->orderBy('id', 'desc')
            ->first();

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
        'region_select' => 'required|integer|exists:catalogo_regiones,id',
        'fijo' => 'nullable',
        'base' => 'required|array',
        'intermedia' => 'required|array',
        'punta' => 'required|array',
        'distribucion' => 'required|array',
        'capacidad' => 'required|array',
    ]);

    $regionId = (int) $request->input('region_select');
    $fijoRaw = str_replace(',', '.', $request->input('fijo', '0'));
    $fijo = is_numeric($fijoRaw) ? floatval($fijoRaw) : 0.0;

    $baseArr = $request->input('base', []);
    $interArr = $request->input('intermedia', []);
    $puntaArr = $request->input('punta', []);
    $distArr = $request->input('distribucion', []);
    $capArr = $request->input('capacidad', []);

    $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    $toFloat = function($v){
        $v = is_null($v) ? '' : $v;
        $v = str_replace(',', '.', (string)$v);
        return is_numeric($v) ? floatval($v) : 0.0;
    };

    // LOG inicial: confirmamos que la petición llegó
    Log::info('CFE::store called', [
        'ip' => request()->ip(),
        'user_id' => auth()->id() ?? null,
        'inputs_keys' => array_keys($request->all()),
        'region_select' => $request->input('region_select'),
    ]);

    $now = time();
    $rows = [];

    foreach ($meses as $mes) {
        $valorBase = array_key_exists($mes, $baseArr) ? $toFloat($baseArr[$mes]) : 0.0;
        $valorInter = array_key_exists($mes, $interArr) ? $toFloat($interArr[$mes]) : 0.0;
        $valorPunta = array_key_exists($mes, $puntaArr) ? $toFloat($puntaArr[$mes]) : 0.0;
        $valorDist  = array_key_exists($mes, $distArr) ? $toFloat($distArr[$mes]) : 0.0;
        $valorCap   = array_key_exists($mes, $capArr)  ? $toFloat($capArr[$mes])  : 0.0;

        $rows[] = [
            'fijo' => $fijo,
            'variable_base' => $valorBase,
            'variable_intermedia' => $valorInter,
            'variable_punta' => $valorPunta,
            'distribucion' => $valorDist,
            'capacidad' => $valorCap,
            'date_actualizacion' => $now,
            'mes' => $mes,
            'id_region' => $regionId,
        ];
    }

    // LOG: conteo y preview
    Log::info('CFE rows prepared', [
        'rows_count' => count($rows),
        'rows_preview' => array_slice($rows, 0, 3)
    ]);

    // ### OPCIÓN TEMPORAL: ver en pantalla los datos antes de insertar ###
    // Descomenta la siguiente línea para detener la ejecución y ver $rows en el navegador:
    // dd(['llego_al_store' => true, 'rows_count' => count($rows), 'rows' => $rows]);

    try {
        \DB::transaction(function() use ($rows) {
            \DB::table('tarifa_region')->insert($rows);
        });

        Log::info('CFE insert OK', ['region_id' => $regionId, 'inserted' => count($rows)]);

        return redirect()->back()->with('success', 'Se insertaron '.count($rows).' registros correctamente para la región (id): '.$regionId);
    } catch (\Throwable $e) {
        Log::error('Error al insertar tarifas por mes: '.$e->getMessage(), [
            'region_id' => $regionId,
            'rows_count' => count($rows),
            'exception' => (string)$e
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
