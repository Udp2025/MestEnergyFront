<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class EnergyCostService
{
    // PUBLIC: método principal que usa la misma firma que tu script original
    public function getCosts(string $fecha_inicio, string $fecha_fin, int $device_id, int $site_id): array
    {
        // normalizar fechas
        $fecha_inicio = $this->normalizar_fecha($fecha_inicio, true);
        $fecha_fin = $this->normalizar_fecha($fecha_fin, false);

        $rangos = $this->dividir_rangos_completos($fecha_inicio, $fecha_fin);

        // acumulador
        $acum = $this->inicializar_arreglo_vacio();

        foreach ($rangos as $rango) {
            $reg = $this->main_costs($rango['sumas'], $rango['maximos'], $device_id, $site_id);
            if (!$reg) continue;

            list($distribucion, $capacidad) = $this->cost_distribution_capacity($rango['sumas'], [$reg]);
            $real_charge_bonus = $this->get_power_factor([$reg]);
            list($power_factor_component, $subtotal, $iva, $total) = $this->calculate_total($reg, $distribucion, $capacidad, $real_charge_bonus);

            // acumular
            $acum['cargo_fijo']         += $reg['fixed_charge'] ?? 0;
            $acum['capacidad']          += round($capacidad ?? 0, 2);
            $acum['distribucion']       += round($distribucion ?? 0, 2);
            $acum['costo_base']         += round($reg['total_base'] ?? 0, 2);
            $acum['costo_intermedio']   += round($reg['total_intermedio'] ?? 0, 2);
            $acum['costo_punta']        += round($reg['total_punta'] ?? 0, 2);
            $acum['subtotal']           += $subtotal ?? 0;
            $acum['iva']                += $iva ?? 0;
            $acum['total']              += $total ?? 0;
            $acum['factor_potencia']    += $power_factor_component ?? 0;
        }

        // porcentajes
        list(
            $cargo_fijo_pct,
            $cargo_capacidad_pct,
            $cargo_distribucion_pct,
            $cargo_base_pct,
            $cargo_intermedio_pct,
            $cargo_punta_pct,
            $factor_carga_pct
        ) = $this->calculate_percentage(
            $acum['subtotal'],
            $acum['capacidad'],
            $acum['distribucion'],
            $acum['factor_potencia'],
            $acum
        );

        // mapear a claves que usa tu blade
        $result = [
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
            // porcentajes con el sufijo _pt (compatibilidad con tu Blade)
            'cargo_fijo_pt' => (float)$cargo_fijo_pct,
            'consumo_capa_pt' => (float)$cargo_capacidad_pct,
            'consumo_dist_pt' => (float)$cargo_distribucion_pct,
            'consumo_base_pt' => (float)$cargo_base_pct,
            'consumo_intermedio_pt' => (float)$cargo_intermedio_pct,
            'consumo_punta_pt' => (float)$cargo_punta_pct,
            'factor_potencia_pt' => (float)$factor_carga_pct,
            'fecha_inicio' => $fecha_inicio,
        ];

        return $result;
    }

    /* -------------------------
       Helpers / traducción de tus funciones originales
       usan DB::select para ejecutar consultas contra la conexión por defecto de Laravel
       Si tu tabla está en otra conexión, ajusta DB::connection('tu_conexion')->select(...)
       ------------------------- */

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
        ];
    }

    private function main_costs($rango_sumas, $rango_maximos, $device_id, $site_id) {
        // SUMAS
        $sql_sumas = "
            SELECT
                SUM(energy_kwh) AS total_kwh,
                SUM(energy_kvarh) AS total_kvarh,
                SUM(cost_base) AS total_base,
                SUM(cost_intermediate) AS total_intermedio,
                SUM(cost_peak) AS total_punta
            FROM cost_agg
            WHERE timestamp BETWEEN ? AND ?
            AND site_id = ?
            AND device_id = ?
        ";
        $sumas = DB::select($sql_sumas, [$rango_sumas['inicio'], $rango_sumas['fin'], $site_id, $device_id]);
        $sumas = $sumas[0] ?? null;

        // MAXIMOS
        $sql_maximos = "
            SELECT
                MAX(fixed_charge) AS fixed_charge,
                MAX(load_factor) AS load_factor,
                MAX(power_kw) AS max_power_kw,
                MAX(CASE WHEN rate = 'punta' THEN power_kw END) AS max_power_punta
            FROM cost_agg
            WHERE timestamp BETWEEN ? AND ?
            AND site_id = ?
            AND device_id = ?
        ";
        $maximos = DB::select($sql_maximos, [$rango_maximos['inicio'], $rango_maximos['fin'], $site_id, $device_id]);
        $maximos = $maximos[0] ?? null;

        // Combinar (si no hay nada, devolver null)
        if (!$sumas && !$maximos) return null;

        $combined = array_merge((array)$sumas, (array)$maximos);
        return $combined;
    }

    private function normalizar_fecha($fecha, $es_inicio = true) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $es_inicio ? $fecha . " 00:00:00" : $fecha . " 23:59:00";
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
            $fin_mes_completo = (clone $cursor)->modify('last day of this month')->setTime(23,59,0);

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

    private function cost_distribution_capacity($rango_fechas, $registros) {
        if (empty($registros)) return [0,0];

        $max_power = 0;
        $max_power_peak = 0;
        $q_periodo = 0;
        $load_factor = 0;

        foreach ($registros as $r) {
            if (isset($r['max_power_kw']) && isset($r['max_power_punta']) && isset($r['total_kwh']) && isset($r['load_factor'])) {
                $max_power = is_numeric($r['max_power_kw']) ? (float)$r['max_power_kw'] : 0;
                $max_power_peak = is_numeric($r['max_power_punta']) ? (float)$r['max_power_punta'] : 0;
                $q_periodo = is_numeric($r['total_kwh']) ? (float)$r['total_kwh'] : 0;
                $load_factor = is_numeric($r['load_factor']) ? (float)$r['load_factor'] : 0;
                break;
            }
        }

        if ($load_factor <= 0) $load_factor = 0.01;

        try {
            $dt_inicio = new \DateTime($rango_fechas['inicio']);
            $dt_fin = new \DateTime($rango_fechas['fin']);
            $diff = $dt_inicio->diff($dt_fin);
            $dias = $diff->days;
            if ($dias == 0) {
                $segundos = abs($dt_fin->getTimestamp() - $dt_inicio->getTimestamp());
                $dias = $segundos / 86400;
            }
            if ($dias <= 0) $dias = 0.01;
        } catch (Exception $e) {
            $dias = 1;
        }

        $demanda = $q_periodo / (24 * $dias * $load_factor);
        if ($demanda < 0) $demanda = 0;

        $costo_capacidad = min($max_power_peak, $demanda);
        $costo_distribucion = min($max_power, $demanda);

        return [$costo_capacidad, $costo_distribucion];
    }

    private function get_power_factor($registros) {
        if (empty($registros)) return 0;

        $total_active = 0;
        $total_reactive = 0;

        foreach ($registros as $r) {
            if (isset($r['total_kwh']) && isset($r['total_kvarh'])) {
                $total_active = is_numeric($r['total_kwh']) ? (float)$r['total_kwh'] : 0;
                $total_reactive = is_numeric($r['total_kvarh']) ? (float)$r['total_kvarh'] : 0;
                break;
            }
        }

        if ($total_active <= 0) return 0;

        $denominador = sqrt(($total_active ** 2) + ($total_reactive ** 2));
        if ($denominador == 0) return 0;

        $total_pf = $total_active / $denominador;

        if ($total_pf >= 0.9) {
            $charge_bonus = -0.25 * (1 - (0.9 / $total_pf));
        } else {
            $charge_bonus = (3 / 5) * ((0.9 / $total_pf) - 1);
        }

        $charge_bonus = round($charge_bonus, 3);

        if ($total_pf >= 0.9) {
            $real_charge_bonus = min($charge_bonus, 0.025);
        } else {
            $real_charge_bonus = min($charge_bonus, 1.2);
        }

        return $real_charge_bonus;
    }

    private function calculate_total($registros, $distribucion, $capacidad, $real_charge_bonus) {
        $total_base = $registros['total_base'] ?? 0;
        $total_intermedio = $registros['total_intermedio'] ?? 0;
        $total_punta = $registros['total_punta'] ?? 0;
        $cargo_fijo = $registros['fixed_charge'] ?? 0;

        $power_factor_component = ($distribucion + $capacidad + $cargo_fijo + $total_base + $total_intermedio + $total_punta) * $real_charge_bonus;
        $power_factor_component = round($power_factor_component, 0);

        $subtotal = $distribucion + $capacidad + $total_base + $total_intermedio + $total_punta + $power_factor_component + $cargo_fijo;
        $subtotal = round($subtotal, 2);
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
