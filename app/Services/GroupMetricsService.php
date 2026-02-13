<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GroupMetricsService
{
    public function getMonthlyMetrics(int $siteId): array
    {
        $today = Carbon::today();
        $startDate = $today->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $today->format('Y-m-d');

        $baseQuery = DB::table('device_daily_kpi as ddk')
            ->join('devices as d', 'd.device_id', '=', 'ddk.device_id')
            ->where('ddk.site_id', $siteId)
            ->whereBetween('ddk.kpi_date', [$startDate, $endDate]);

        $consumptionWh = (clone $baseQuery)
            ->where('d.device_name', 'not like', '%generacion%')
            ->sum('ddk.energy_wh_sum');

        $generationWh = (clone $baseQuery)
            ->where('d.device_name', 'like', '%generacion%')
            ->sum('ddk.energy_wh_sum');

        $voltageAvg = DB::table('device_hourly_agg as dha')
            ->join('devices as d', 'd.device_id', '=', 'dha.device_id')
            ->where('dha.site_id', $siteId)
            ->whereBetween('dha.hour_start', [$startDate, $endDate])
            ->where('d.device_name', 'not like', '%generacion%')
            ->avg('dha.voltage_v_avg');

        return [
            'consumption_kwh' => round(((float) $consumptionWh) / 1000, 2),
            'generation_kwh' => round(((float) $generationWh) / 1000, 2),
            'voltage_avg' => round((float) $voltageAvg, 2),
        ];
    }

    public function emptyMetrics(): array
    {
        return [
            'consumption_kwh' => 0,
            'generation_kwh' => 0,
            'voltage_avg' => 0,
        ];
    }

    public function periodLabel(): string
    {
        $today = Carbon::today();
        $start = $today->copy()->startOfMonth()->format('Y-m-d');
        $end = $today->format('Y-m-d');
        return "{$start} a {$end}";
    }
}
