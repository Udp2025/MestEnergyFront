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
    $regionId = $request->query('region_id') ?? $request->query('region');
    if (!$regionId) {
        return response()->json(null, 400);
    }

    // obtener el timestamp más reciente para esa región
    $latestTs = DB::table('tarifa_region')
        ->where('id_region', $regionId)
        ->max('date_actualizacion');

    if (!$latestTs) {
        // si no hay por timestamp, devolver null
        return response()->json(null);
    }

    $rows = DB::table('tarifa_region')
        ->where('id_region', $regionId)
        ->where('date_actualizacion', $latestTs)
        ->get();

    if ($rows->isEmpty()) {
        return response()->json(null);
    }

    // construir estructura por mes
    $months = [];
    foreach ($rows as $r) {
        $mesName = $r->mes;
        $months[$mesName] = [
            'variable_base' => $r->variable_base,
            'variable_intermedia' => $r->variable_intermedia,
            'variable_punta' => $r->variable_punta,
            'distribucion' => $r->distribucion,
            'capacidad' => $r->capacidad,
        ];
    }

    // tomar fijo / tariff_year / date_actualizacion de la primera fila (todas deberían compartirlo)
    $first = $rows->first();

    return response()->json([
        'fijo' => $first->fijo,
        'date_actualizacion' => $first->date_actualizacion,
        'tariff_year' => property_exists($first, 'tariff_year') ? $first->tariff_year : (int)date('Y', $first->date_actualizacion),
        'months' => $months,
    ]);
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
        'tariff_year' => 'nullable|integer',
        'base' => 'required|array',
        'intermedia' => 'required|array',
        'punta' => 'required|array',
        'distribucion' => 'required|array',
        'capacidad' => 'required|array',
    ]);

    $regionId = (int) $request->input('region_select');
    $fijoRaw = str_replace(',', '.', $request->input('fijo', '0'));
    $fijo = is_numeric($fijoRaw) ? floatval($fijoRaw) : 0.0;

    // si se envio tariff_year usarlo, si no usar año actual
    $year = $request->input('tariff_year') ? (int)$request->input('tariff_year') : (int)date('Y');

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

    Log::info('CFE::store called', [
        'ip' => request()->ip(),
        'user_id' => auth()->id() ?? null,
        'inputs_keys' => array_keys($request->all()),
        'region_select' => $request->input('region_select'),
        'tariff_year' => $year,
    ]);

    $now = time();
    $inserted = 0;
    $updated = 0;

    try {
        DB::transaction(function() use (&$inserted, &$updated, $meses, $baseArr, $interArr, $puntaArr, $distArr, $capArr, $toFloat, $regionId, $fijo, $now, $year) {
            foreach ($meses as $mes) {
                $valorBase = array_key_exists($mes, $baseArr) ? $toFloat($baseArr[$mes]) : 0.0;
                $valorInter = array_key_exists($mes, $interArr) ? $toFloat($interArr[$mes]) : 0.0;
                $valorPunta = array_key_exists($mes, $puntaArr) ? $toFloat($puntaArr[$mes]) : 0.0;
                $valorDist  = array_key_exists($mes, $distArr) ? $toFloat($distArr[$mes]) : 0.0;
                $valorCap   = array_key_exists($mes, $capArr)  ? $toFloat($capArr[$mes])  : 0.0;

                $keys = [
                    'id_region' => $regionId,
                    'mes' => $mes,
                    'tariff_year' => $year,
                ];

                $values = [
                    'fijo' => $fijo,
                    'variable_base' => $valorBase,
                    'variable_intermedia' => $valorInter,
                    'variable_punta' => $valorPunta,
                    'distribucion' => $valorDist,
                    'capacidad' => $valorCap,
                    'date_actualizacion' => $now,
                ];

                // updateOrInsert
                $updatedRows = DB::table('tarifa_region')->where($keys)->update($values);
                if ($updatedRows > 0) {
                    $updated++;
                } else {
                    // no se actualizó porque no existía -> insertar combinando keys + values
                    $toInsert = array_merge($keys, $values);
                    DB::table('tarifa_region')->insert($toInsert);
                    $inserted++;
                }
            }
        });

        Log::info('CFE upsert OK', ['region_id' => $regionId, 'inserted' => $inserted, 'updated' => $updated, 'tariff_year' => $year]);

        return redirect()->back()->with('success', "Se insertaron {$inserted} y actualizaron {$updated} registros para la región (id): {$regionId} (año: {$year}).");
    } catch (\Throwable $e) {
        Log::error('Error al upsert tarifas por mes: '.$e->getMessage(), [
            'region_id' => $regionId,
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
