<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->orderBy('mes') // Ordenar por mes numérico
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(null);
        }

        // Mapeo de números de mes a nombres (para la respuesta JSON)
        $mesesNombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        // construir estructura por mes
        $months = [];
        foreach ($rows as $r) {
            $mesNum = $r->mes; // Número del mes (1-12)
            $mesName = $mesesNombres[$mesNum] ?? 'Desconocido'; // Convertir a nombre para la vista
            
            $months[$mesName] = [
                'variable_base' => $r->variable_base,
                'variable_intermedia' => $r->variable_intermedia,
                'variable_punta' => $r->variable_punta,
                'distribucion' => $r->distribucion,
                'capacidad' => $r->capacidad,
            ];
        }

        // tomar fijo / tariff_year / date_actualizacion de la primera fila
        $first = $rows->first();
        return response()->json([
            'fijo' => $first->fijo,
            'date_actualizacion' => $first->date_actualizacion,
            'tariff_year' => property_exists($first, 'tariff_year') ? $first->tariff_year : (int)date('Y', $first->date_actualizacion),
            'months' => $months,
        ]);
    }

    /**
     * Guarda 12 registros (uno por mes) por cada envío.
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

        // si se envió tariff_year usarlo, si no usar año actual
        $year = $request->input('tariff_year') ? (int)$request->input('tariff_year') : (int)date('Y');

        $baseArr = $request->input('base', []);
        $interArr = $request->input('intermedia', []);
        $puntaArr = $request->input('punta', []);
        $distArr = $request->input('distribucion', []);
        $capArr = $request->input('capacidad', []);

        // Mapeo de nombres de mes a números (1-12)
        $mesesMap = [
            'Enero' => 1, 'Febrero' => 2, 'Marzo' => 3, 'Abril' => 4,
            'Mayo' => 5, 'Junio' => 6, 'Julio' => 7, 'Agosto' => 8,
            'Septiembre' => 9, 'Octubre' => 10, 'Noviembre' => 11, 'Diciembre' => 12
        ];

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
            DB::transaction(function() use (&$inserted, &$updated, $mesesMap, $baseArr, $interArr, $puntaArr, $distArr, $capArr, $toFloat, $regionId, $fijo, $now, $year) {
                foreach ($mesesMap as $mesNombre => $mesNumero) {
                    $valorBase = array_key_exists($mesNombre, $baseArr) ? $toFloat($baseArr[$mesNombre]) : 0.0;
                    $valorInter = array_key_exists($mesNombre, $interArr) ? $toFloat($interArr[$mesNombre]) : 0.0;
                    $valorPunta = array_key_exists($mesNombre, $puntaArr) ? $toFloat($puntaArr[$mesNombre]) : 0.0;
                    $valorDist = array_key_exists($mesNombre, $distArr) ? $toFloat($distArr[$mesNombre]) : 0.0;
                    $valorCap = array_key_exists($mesNombre, $capArr) ? $toFloat($capArr[$mesNombre]) : 0.0;

                    $keys = [
                        'id_region' => $regionId,
                        'mes' => $mesNumero, // Guardar número en lugar de texto
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

    // Los demás métodos...
    public function create() {}
    public function show(string $id) {}
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}