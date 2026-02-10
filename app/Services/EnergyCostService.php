<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class EnergyCostService
{
    // PUBLIC: método principal que usa la misma firma que tu script original
    public function getCosts(string $fecha_inicio, string $fecha_fin, $device_id = null, int $site_id): array
    {
        $fecha_inicio = $this->normalizar_fecha($fecha_inicio, true);
        $fecha_fin    = $this->normalizar_fecha($fecha_fin, false);

        $rangos = $this->dividir_rangos_completos($fecha_inicio, $fecha_fin);

        $acum = $this->inicializar_arreglo_vacio();
        $acum_count = 0;

        $sensores_generacion = $this->get_sensor_generation($site_id);

        foreach ($rangos as $rango) {

            $reg = $this->main_costs($rango['sumas'], $device_id, $site_id);

            if (!$reg) continue;

            // DB::select devuelve stdClass; normalizamos a arreglo para acceso consistente
            if (is_object($reg)) {
                $reg = (array)$reg;
            }

            $kw_Max = $reg['max_power_kw'];
            $kw_punta = $reg['max_power_punta'];
            

            [
                $fijo,
                $costo_capacidad,
                $costo_distribucion,
                $costo_base,
                $costo_intermedio,
                $costo_punta,
                $vista_base,
                $vista_intermedio,
                $vista_punta,
                $vista_generada
            ] = $this->cost_distribution_capacity(
                $rango['sumas'],
                [$reg],
                $device_id,
                $site_id,
                $sensores_generacion
            );

            //$vista_generada = 200;


            $real_charge_bonus = $this->get_power_factor([$reg], (float)($vista_generada ?? 0), $device_id);

            [
                $power_factor_component,
                $subtotal,
                $iva,
                $total
            ] = $this->calculate_total(
                (float)$fijo,
                (float)$costo_distribucion,
                (float)$costo_capacidad,
                (float)$costo_base,
                (float)$costo_intermedio,
                (float)$costo_punta,
                (float)$real_charge_bonus
            );

            $acum['cargo_fijo']       += (float)$fijo;
            $acum['capacidad']        += round((float)$costo_capacidad, 2);
            $acum['distribucion']     += round((float)$costo_distribucion, 2);
            $acum['costo_base']       += round((float)$costo_base, 2);
            $acum['costo_intermedio'] += round((float)$costo_intermedio, 2);
            $acum['costo_punta']      += round((float)$costo_punta, 2);
            $acum['subtotal']         += (float)$subtotal;
            $acum['iva']              += (float)$iva;
            $acum['total']            += (float)$total;
            $acum['factor_potencia']  += (float)$power_factor_component;
            $acum['energia_generada'] += (float)$vista_generada;
            $acum['kwh_base']         += (float)$vista_base;
            $acum['kwh_intermedio']   += (float)$vista_intermedio;
            $acum['kwh_punta']        += (float)$vista_punta;

            //avg
            //Agregar detalle del power factor
            $acum['kw_max']           += (float)$kw_Max;
            $acum['kw_punta']           += (float)$kw_Max;
            $acum_count++;
        }

        [
            $cargo_fijo_pct,
            $cargo_capacidad_pct,
            $cargo_distribucion_pct,
            $cargo_base_pct,
            $cargo_intermedio_pct,
            $cargo_punta_pct,
            $factor_carga_pct
        ] = $this->calculate_percentage(
            $acum['subtotal'],
            $acum['capacidad'],
            $acum['distribucion'],
            $acum['factor_potencia'],
            $acum
        );

        return [
            'cargo_fijo' => round($acum['cargo_fijo'], 2),
            'cargo_capacidad' => round($acum['capacidad'], 2),
            'cargo_distribucion' => round($acum['distribucion'], 2),
            'cargo_base' => round($acum['costo_base'], 2),
            'cargo_intermedio' => round($acum['costo_intermedio'], 2),
            'cargo_punta' => round($acum['costo_punta'], 2),
            'subtotal' => round($acum['subtotal'], 2),
            'iva' => round($acum['iva'], 2),
            'total' => round($acum['total'], 2),
            'factor_potencia' => round($acum['factor_potencia'], 2),

            'cargo_fijo_pt' => (float)$cargo_fijo_pct,
            'consumo_capa_pt' => (float)$cargo_capacidad_pct,
            'consumo_dist_pt' => (float)$cargo_distribucion_pct,
            'consumo_base_pt' => (float)$cargo_base_pct,
            'consumo_intermedio_pt' => (float)$cargo_intermedio_pct,
            'consumo_punta_pt' => (float)$cargo_punta_pct,
            'factor_potencia_pt' => (float)$factor_carga_pct,

            'fecha_inicio' => $fecha_inicio,

            'energia_generada' => round($acum['energia_generada'],2),
            'kwh_base' => round($acum['kwh_base'],2),
            'kwh_intermedio' => round($acum['kwh_intermedio'],2),
            'kwh_punta' => round($acum['kwh_punta'],2),
            'kw_max' => round($acum_count > 0 ? ($acum['kw_max'] / $acum_count) : 0, 2),
            'kw_punta' => round($acum_count > 0 ? ($acum['kw_punta'] / $acum_count) : 0, 2),

        ];
    }


    private function get_sensor_generation($site_id){
        $sql = "
        select device_id FROM devices WHERE site_id = ? 
        and device_name like '%generacion%';
        ";

        $devices = DB::select($sql, [$site_id]);
        $ids = array_map(fn($r) => (int)$r->device_id, $devices); // devolver arreglo plano de IDs
        Log::info('Sensores de generacion para site_id', [
            'site_id' => $site_id,
            'device_ids' => $ids,
            'count' => count($ids),
        ]);
        return $ids;
    }

    private function get_energy_generation($fecha_inicio, $fecha_fin, $site_id, array $device_id)
    {
        if (empty($device_id)) {
            return 0;
        }

        return (float) DB::table('device_daily_kpi')
            ->where('site_id', $site_id)
            ->whereBetween('kpi_date', [$fecha_inicio, $fecha_fin])
            ->whereIn('device_id', $device_id)
            ->sum('energy_wh_sum');
    }

    private function get_energy_accumulated(int $site_id, int $month, int $year): float
    {
        $value = DB::table('energy_accumulated')
            ->where('site_id', $site_id)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->value('energy_kwh');

        Log::info('Energy accumulated lookup', [
            'site_id' => $site_id,
            'month' => $month,
            'year' => $year,
            'energy_kwh' => $value,
        ]);

        return $value !== null ? (float)$value : 0.0;
    }

    private function regionCharges(int $site_id, int $month, int $year): array
    {
        $tarifaRegion = DB::table('clientes')
            ->where('site', $site_id)
            ->orderByDesc('id')
            ->value('tarifa_region');

        if ($tarifaRegion === null || $tarifaRegion === '') {
            logger()->warning("Tarifa no encontrada para site_id={$site_id} (cliente sin tarifa_region)");
            return [0, 0, 0, 0, 0, 0];
        }

        $queries = [
            "
            SELECT
                fijo,
                variable_base,
                variable_intermedia,
                variable_punta,
                distribucion,
                capacidad
            FROM tarifa_region
            WHERE id_region = ?
            AND mes = ?
            AND tariff_year = ?
            ORDER BY date_actualizacion DESC
            LIMIT 1
            ",
            "
            SELECT
                fijo,
                variable_base,
                variable_intermedia,
                variable_punta,
                distribucion,
                capacidad
            FROM tarifa_region
            WHERE id = ?
            AND mes = ?
            AND tariff_year = ?
            ORDER BY date_actualizacion DESC
            LIMIT 1
            ",
        ];

        $row = null;

        foreach ($queries as $sql) {
            try {
                $result = DB::select($sql, [$tarifaRegion, $month, $year]);

                if (!empty($result)) {
                    $row = $result[0];
                    break;
                }
            } catch (\Exception $e) {
                logger()->warning("Tarifa query error: " . $e->getMessage());
            }
        }

        if (!$row) {
            logger()->warning("Tarifa no encontrada para site_id={$site_id}, mes={$month}, year={$year}");
            return [0, 0, 0, 0, 0, 0];
        }

        return [
            (float)$row->fijo,
            (float)$row->variable_base,
            (float)$row->variable_intermedia,
            (float)$row->variable_punta,
            (float)$row->distribucion,
            (float)$row->capacidad,
        ];
    }

    private function inicializar_arreglo_vacio(): array {
        return [
            'cargo_fijo'         => 0,
            'capacidad'          => 0,
            'distribucion'       => 0,
            'costo_base'         => 0,
            'costo_intermedio'   => 0,
            'costo_punta'        => 0,
            'subtotal'           => 0,
            'iva'                => 0,
            'total'              => 0,
            'factor_potencia'    => 0,
            'energia_generada'   => 0,
            'kwh_base'           => 0,
            'kwh_intermedio'     => 0,
            'kwh_punta'          => 0,
            'kw_max'             => 0,
            'kw_punta'           => 0,
        ];
    }

    private function get_devices($start_date, $end_date, $site_id)
    {
        $sql = "
            SELECT DISTINCT device_id
            FROM cost_agg
            WHERE timestamp BETWEEN ? AND ?
            AND site_id = ?
        ";

        $rows = DB::select($sql, [$start_date, $end_date, $site_id]);
        return array_map(fn($r) => (int)$r->device_id, $rows);  // devolver arreglo plano de IDs
    }

    private function main_costs($rango_sumas, $device_id = null, $site_id)
    {
        $sql = "
            SELECT
                SUM(CASE WHEN rate = 'base' THEN energy_kwh END) AS energy_base,
                SUM(CASE WHEN rate = 'intermedio' THEN energy_kwh END) AS energy_intermedio,
                SUM(CASE WHEN rate = 'punta' THEN energy_kwh END) AS energy_punta,
                SUM(energy_kwh) AS total_kwh,
                SUM(energy_kvarh) AS total_kvarh,
                MAX(load_factor) AS load_factor,
                MAX(power_kw) AS max_power_kw,
                MAX(CASE WHEN rate = 'punta' THEN power_kw END) AS max_power_punta
            FROM cost_agg
            WHERE timestamp BETWEEN ? AND ?
            AND site_id = ?
        ";

        $bindings = [$rango_sumas['inicio'], $rango_sumas['fin'], $site_id];

        if ($device_id !== null) {
            $sql .= " AND device_id = ?";
            $bindings[] = $device_id;
        }

        $rows = DB::select($sql, $bindings);
        return $rows[0] ?? null;
    }



    private function normalizar_fecha($fecha, $es_inicio = true) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $es_inicio ? $fecha . " 00:00:00" : $fecha . " 23:59:59";
        }
        try {
            return Carbon::parse($fecha)->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return Carbon::now()->format('Y-m-d H:i:s');
        }
    }

    private function dividir_rangos_completos($f_inicio, $f_fin) {
        $inicio = new \DateTime($f_inicio);
        $fin = new \DateTime($f_fin);

        $cursor = (clone $inicio)->modify('first day of this month')->setTime(0,0,0);
        $meses = [];

        while ($cursor <= $fin) {
            $inicio_mes = clone $cursor;
            $fin_mes_completo = (clone $cursor)->modify('last day of this month')->setTime(23,59,59);

            $inicio_sumas = $inicio_mes < $inicio ? clone $inicio : clone $inicio_mes;
            $fin_sumas = $fin_mes_completo > $fin ? clone $fin : clone $fin_mes_completo;

            $meses[] = [
                'sumas' => [
                    'inicio' => $inicio_sumas->format('Y-m-d H:i:s'),
                    'fin' => $fin_sumas->format('Y-m-d H:i:s'),
                ],
                'maximos' => [
                    'inicio' => $inicio_mes->format('Y-m-d H:i:s'),
                    'fin' => $fin_mes_completo->format('Y-m-d H:i:s'),
                ],
            ];

            $cursor->modify('first day of next month')->setTime(0,0,0);
        }

        return $meses;
    }

    private function cost_distribution_capacity(
        $rango_fechas,
        $registros,
        $device_id = null,
        $site_id,
        $sensores_generacion
    ) {
        if (empty($registros)) return [0, 0];

        $max_power = 0;
        $max_power_peak = 0;
        $q_periodo = 0;
        $load_factor = 0;
        $energy_base = 0;
        $energy_intermedio = 0;
        $energy_punta = 0;
        $vista_generada = 0;
        $energia_acumulada = 0;
        $energia_generada = 0; 

        // === Fechas ===
        $dt_inicio = new \DateTime($rango_fechas['inicio']);
        $dt_fin = new \DateTime($rango_fechas['fin']);
        $diff = $dt_inicio->diff($dt_fin);
        $dias = $diff->days + 1;

        // === Extraer datos de registros ===
        foreach ($registros as $r) {
            if (
                isset($r['max_power_kw'], $r['max_power_punta'], $r['load_factor'],
                    $r['energy_base'], $r['energy_intermedio'], $r['energy_punta'])
            ) {
                $max_power = (float)$r['max_power_kw'];
                $max_power_peak = (float)$r['max_power_punta'];
                $load_factor = (float)$r['load_factor'];

                $energy_base = (float)$r['energy_base'];
                $energy_intermedio = (float)$r['energy_intermedio'];
                $energy_punta = (float)$r['energy_punta'];

                $q_periodo = $energy_base + $energy_intermedio + $energy_punta;
                break;
            }
        }

        $vista_base = $energy_base;
        $vista_intermedio = $energy_intermedio;
        $vista_punta = $energy_punta; 

        if ($load_factor <= 0) $load_factor = 0.01;

        // === Ajuste días ===
        try {
            if ($dias == 0) {
                $segundos = abs($dt_fin->getTimestamp() - $dt_inicio->getTimestamp());
                $dias = $segundos / 86400;
            }
            if ($dias <= 0) $dias = 0.01;
        } catch (\Exception $e) {
            $dias = 1;
        }

        // ==========================================================
        // === BLOQUE GENERACIÓN ====================================
        // ==========================================================

        //$energia_generada = 0;

        if (!empty($sensores_generacion) && $device_id === null) {

            // Identificamos si es Mes completo
            $inicioMes = (clone $dt_inicio)->modify('first day of this month')->format('Y-m-d');
            $finMes    = (clone $dt_inicio)->modify('last day of this month')->format('Y-m-d');

            $esMesCompleto =
                $dt_inicio->format('Y-m-d') === $inicioMes &&
                $dt_fin->format('Y-m-d') === $finMes;

            Log::info('Generacion: chequeo mes completo', [
                'dt_inicio' => $dt_inicio->format('Y-m-d H:i:s'),
                'dt_fin' => $dt_fin->format('Y-m-d H:i:s'),
                'inicioMes' => $inicioMes,
                'finMes' => $finMes,
                'esMesCompleto' => $esMesCompleto,
                'sensores_generacion_count' => count($sensores_generacion),
                'device_id' => $device_id,
            ]);

            if ($esMesCompleto) {

                $energia_generada = $this->get_energy_generation(
                    $inicioMes,
                    $finMes,
                    $site_id,
                    $sensores_generacion
                );

                //Obtener el mes anterior
                $prev = (clone $dt_inicio)->modify('first day of previous month');
                $energia_acumulada = $this->get_energy_accumulated(
                    $site_id,
                    (int)$prev->format('m'),
                    (int)$prev->format('Y')
                );

                // pasar a kWh 
                $energia_generada = $energia_generada / 1000;
                $energia_generada = $energia_generada + $energia_acumulada;

                $vista_generada = $energia_generada;
                Log::info('Generacion: resultado', [
                    'energia_generada_kwh' => $energia_generada,
                    'energia_acumulada_kwh' => $energia_acumulada,
                    'vista_generada' => $vista_generada,
                ]);

                // === Descuento por prioridad ===
                $restante = $energia_generada;

                // intermedio
                $d = min($energy_intermedio, $restante);
                $energy_intermedio -= $d;
                $restante -= $d;

                // punta
                if ($restante > 0) {
                    $d = min($energy_punta, $restante);
                    $energy_punta -= $d;
                    $restante -= $d;
                }

                // base
                if ($restante > 0) {
                    $d = min($energy_base, $restante);
                    $energy_base -= $d;
                    $restante -= $d;
                }

                // nuevo q_periodo
                $q_periodo = $energy_base + $energy_intermedio + $energy_punta;
            }
        }

        // ==========================================================
        // === CÁLCULO FINAL periodos ===============================
        // ==========================================================
        [
            $fijo,
            $variable_base,
            $variable_intermedia,
            $variable_punta,
            $distribucion,
            $capacidad
        ] = $this->regionCharges($site_id,
                                (int)$dt_inicio->format('m'),
                                (int)$dt_inicio->format('Y'));


        // ==========================================================
        // === PRORRATEO CARGO FIJO ================================
        // ==========================================================

        $dias_mes = (int)$dt_inicio->format('t');

        $inicioMes = (clone $dt_inicio)->modify('first day of this month')->format('Y-m-d');
        $finMes    = (clone $dt_inicio)->modify('last day of this month')->format('Y-m-d');

        $esMesCompleto =
            $dt_inicio->format('Y-m-d') === $inicioMes &&
            $dt_fin->format('Y-m-d') === $finMes;
        
        // ==========================================================
        // === CÁLCULO FINAL ========================================
        // ==========================================================

        $demanda = $q_periodo / (24 * $dias * $load_factor);
        if ($demanda < 0) $demanda = 0;

        $costo_capacidad = min($max_power_peak, $demanda) * $capacidad;
        $costo_distribucion = min($max_power, $demanda) * $distribucion;

        if (!$esMesCompleto) {
            //Para cargo fijo
            $fijo = ($fijo / $dias_mes) * $dias;

            $factor_prorrateo = $dias / $dias_mes;
            $costo_capacidad    *= $factor_prorrateo;
            $costo_distribucion *= $factor_prorrateo;
        }

        $costo_base = $variable_base * $energy_base;
        $costo_intermedio = $variable_intermedia * $energy_intermedio;
        $costo_punta = $variable_punta * $energy_punta;


        return [$fijo, $costo_capacidad, $costo_distribucion, $costo_base, $costo_intermedio, $costo_punta, $vista_base, $vista_intermedio, $vista_punta, $vista_generada];
    }


    private function get_power_factor($registros, float $kwh_gen = 0.0, $device_id = null): float
    {
        if (empty($registros)) return 0.0;

        $kwh_old = 0.0;
        $kvarh_old = 0.0;

        foreach ($registros as $r) {
            // $r puede venir como objeto (DB::select) o como array
            $total_kwh  = is_array($r) ? ($r['total_kwh'] ?? null)  : ($r->total_kwh ?? null);
            $total_kvarh= is_array($r) ? ($r['total_kvarh'] ?? null): ($r->total_kvarh ?? null);

            if ($total_kwh !== null && $total_kvarh !== null) {
                $kwh_old   = is_numeric($total_kwh) ? (float)$total_kwh : 0.0;
                $kvarh_old = is_numeric($total_kvarh) ? (float)$total_kvarh : 0.0;
                break;
            }
        }

        if ($kwh_old <= 0) return 0.0;

        // ==============================
        // Ajuste por generación (solo si device_id es null, si no hay device, es sitio completo)
        // ==============================
        $kwh_used = $kwh_old;
        $kvarh_used = $kvarh_old;

        if ($device_id === null && $kwh_gen > 0) {
            $kwh_new = max(0.0, $kwh_old - $kwh_gen);
            $kwh_used = $kwh_new;

            // Escalar kvarh proporcionalmente para conservar el PF (tan(phi) constante)
            $kvarh_used = ($kwh_old > 0) ? ($kvarh_old * ($kwh_new / $kwh_old)) : 0.0;

            // Si todo quedó en 0, no hay PF que cobrar
            if ($kwh_used <= 0) return 0.0;
        }

        // PF promedio del periodo
        $den = sqrt(($kwh_used ** 2) + ($kvarh_used ** 2));
        if ($den <= 0) return 0.0;

        $pf = $kwh_used / $den;

        // Fórmula CFE (fracción: ej 0.025 = 2.5%)
        if ($pf >= 0.9) {
            $charge = -0.25 * (1 - (0.9 / $pf));
        } else {
            $charge = (3 / 5) * ((0.9 / $pf) - 1);
        }

        // Tope
        if ($pf >= 0.9) {
            $charge = min($charge, 0.025);
        } else {
            $charge = min($charge, 1.2);
        }

        // Checar el redondeo, no recuerdo cuantos decimales eran 
        return (float)$charge;
    }


    private function calculate_total(
        float $cargo_fijo,
        float $costo_distribucion,
        float $costo_capacidad,
        float $costo_base,
        float $costo_intermedio,
        float $costo_punta,
        float $real_charge_bonus
    ): array
    {
        $base_sub = $costo_distribucion + $costo_capacidad + $cargo_fijo + $costo_base + $costo_intermedio + $costo_punta;

        $power_factor_component = round($base_sub * $real_charge_bonus, 0);

        $subtotal = round($base_sub + $power_factor_component, 2);
        $iva = round($subtotal * 0.16, 2);
        $total = round($subtotal + $iva, 2);

        return [$power_factor_component, $subtotal, $iva, $total];
    }


    private function calculate_percentage($subtotal, $capacidad, $distribucion, $power_factor_component, $registros) {
        $total_base = $registros['costo_base'] ?? $registros['total_base'] ?? 0;
        $total_intermedio = $registros['costo_intermedio'] ?? $registros['total_intermedio'] ?? 0;
        $total_punta = $registros['costo_punta'] ?? $registros['total_punta'] ?? 0;
        $cargo_fijo = $registros['cargo_fijo'] ?? 0;

        if ($subtotal == 0) return [0,0,0,0,0,0,0];

        $pct = function($valor, $subtotal) {
            return number_format(($valor / $subtotal) * 100, 2, '.', '');
        };

        return [
            $pct($cargo_fijo, $subtotal),
            $pct($capacidad, $subtotal),
            $pct($distribucion, $subtotal),
            $pct($total_base, $subtotal),
            $pct($total_intermedio, $subtotal),
            $pct($total_punta, $subtotal),
            $pct($power_factor_component, $subtotal),
        ];
    }
}
