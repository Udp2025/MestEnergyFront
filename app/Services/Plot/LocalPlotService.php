<?php

namespace App\Services\Plot;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class LocalPlotService
{
    protected ?array $measurements = null;
    protected ?array $siteDailyKpi = null;
    protected ?array $siteHourlyKpi = null;
    protected ?array $deviceDailyKpi = null;
    protected ?array $ingestionRunKpi = null;

    /**
     * Generate a Plotly-compatible response for chart requests when the remote
     * Plot API is not available.
     */
    public function plot(array $payload): array
    {
        $table = Arr::get($payload, 'table');
        if ($table !== 'measurements') {
            $dataset = $this->data($payload);
            $rows = $dataset['data'] ?? [];
            $chart = Arr::get($payload, 'chart', []);
            return $this->buildGenericChart($rows, $chart);
        }

        $rows = $this->filterMeasurements($payload);
        $chartType = Arr::get($payload, 'chart.chart_type');

        return match ($chartType) {
            'histogram' => $this->buildHistogram($rows),
            'scatter' => $this->buildScatter($rows),
            'line' => $this->buildTimeseries($rows),
            'bar' => $this->buildEnergyBar($rows),
            'heatmap' => $this->buildHeatmap($rows),
            default => $this->buildFigureResponse([], $this->baseLayout('Visualización no disponible')),
        };
    }

    /**
     * Provide dataset style responses for KPI helpers when the remote API is unavailable.
     */
    public function data(array $payload): array
    {
        $table = Arr::get($payload, 'table');
        $filterMap = Arr::get($payload, 'filter_map', []);
        $select = Arr::get($payload, 'select_columns');

        switch ($table) {
            case 'sites':
                $rows = $this->sites();
                $rows = $this->filterRows($rows, $filterMap, ['site_id']);
                break;
            case 'devices':
                $rows = $this->devices();
                $rows = $this->filterRows($rows, $filterMap, ['site_id', 'device_id']);
                break;
            case 'site_daily_kpi':
                $rows = $this->siteDailyKpi();
                $rows = $this->filterRows($rows, $filterMap, ['site_id', 'kpi_date']);
                break;
            case 'site_hourly_kpi':
                $rows = $this->siteHourlyKpi();
                $rows = $this->filterRows($rows, $filterMap, ['site_id', 'hour_start']);
                break;
            case 'device_daily_kpi':
                $rows = $this->deviceDailyKpi();
                $rows = $this->filterRows($rows, $filterMap, ['site_id', 'device_id', 'kpi_date']);
                break;
            case 'ingestion_run_kpi':
                $rows = $this->ingestionRunKpi();
                $rows = $this->filterRows($rows, $filterMap, ['run_date']);
                break;
            default:
                $rows = [];
                break;
        }

        if (!empty($rows) && isset($payload['aggregation']) && is_array($payload['aggregation'])) {
            $rows = $this->aggregateRows($rows, $payload['aggregation']);
        }

        if (is_array($select) && $select !== []) {
            $allowed = array_fill_keys($select, true);
            $rows = array_map(static function (array $row) use ($allowed) {
                return array_intersect_key($row, $allowed);
            }, $rows);
        }

        return ['data' => array_values($rows)];
    }

    protected function buildGenericChart(array $rows, array $chart): array
    {
        $type = Arr::get($chart, 'chart_type', 'line');
        $xKey = Arr::get($chart, 'x');
        $yKey = Arr::get($chart, 'y');
        $zKey = Arr::get($chart, 'z');
        $style = Arr::get($chart, 'style', []);
        $colorKey = Arr::get($style, 'color');

        $layout = $this->baseLayout('Vista de datos');
        $layout['legend'] = ['orientation' => 'h'];

        if ($type === 'heatmap' && $xKey && $yKey && $zKey) {
            $xLabels = array_values(array_unique(array_map(static fn ($row) => $row[$xKey] ?? null, $rows)));
            $yLabels = array_values(array_unique(array_map(static fn ($row) => $row[$yKey] ?? null, $rows)));
            $matrix = [];
            foreach ($yLabels as $y) {
                $rowData = [];
                foreach ($xLabels as $x) {
                    $value = 0;
                    foreach ($rows as $row) {
                        if (($row[$xKey] ?? null) === $x && ($row[$yKey] ?? null) === $y) {
                            $value = $row[$zKey] ?? 0;
                            break;
                        }
                    }
                    $rowData[] = $value;
                }
                $matrix[] = $rowData;
            }

            $data = [[
                'type' => 'heatmap',
                'x' => $xLabels,
                'y' => $yLabels,
                'z' => $matrix,
                'coloraxis' => 'coloraxis',
            ]];
            $layout['xaxis'] = ['title' => $xKey];
            $layout['yaxis'] = ['title' => $yKey];
            return $this->buildFigureResponse($data, $layout);
        }

        $groups = [];
        if ($colorKey) {
            foreach ($rows as $row) {
                $groups[$row[$colorKey] ?? 'Serie'][] = $row;
            }
        } else {
            $groups['Serie'] = $rows;
        }

        $data = [];
        foreach ($groups as $label => $groupRows) {
            usort($groupRows, static fn ($a, $b) => strcmp((string) ($a[$xKey] ?? ''), (string) ($b[$xKey] ?? '')));
            $xValues = array_map(static fn ($row) => $row[$xKey] ?? null, $groupRows);
            $yValues = array_map(static fn ($row) => $row[$yKey] ?? null, $groupRows);

            $trace = [
                'name' => (string) $label,
                'x' => $xValues,
                'y' => $yValues,
            ];

            switch ($type) {
                case 'bar':
                    $trace['type'] = 'bar';
                    $trace['orientation'] = Arr::get($style, 'orientation', 'v');
                    break;
                case 'scatter':
                    $trace['type'] = 'scatter';
                    $trace['mode'] = Arr::get($style, 'mode', 'lines+markers');
                    if ($colorKey) {
                        $trace['marker'] = ['color' => $label];
                    }
                    break;
                case 'line':
                default:
                    $trace['type'] = 'scatter';
                    $trace['mode'] = 'lines+markers';
                    if (Arr::get($style, 'shape') === 'spline') {
                        $trace['line'] = ['shape' => 'spline'];
                    }
                    break;
            }

            $data[] = $trace;
        }

        if ($xKey) {
            $layout['xaxis'] = ['title' => $xKey];
        }
        if ($yKey) {
            $layout['yaxis'] = ['title' => $yKey];
        }

        return $this->buildFigureResponse($data, $layout);
    }

    // ---------------------------------------------------------------------
    // Figure builders
    // ---------------------------------------------------------------------

    protected function buildHistogram(array $rows): array
    {
        if ($rows === []) {
            return $this->buildFigureResponse([], $this->baseLayout('Distribución de corriente'));
        }

        $series = [];
        foreach ($rows as $row) {
            $series[$row['device_id']][] = $row['current_a'];
        }

        $data = [];
        foreach ($series as $deviceId => $values) {
            $data[] = [
                'type' => 'histogram',
                'name' => $this->deviceLabel($deviceId),
                'x' => array_values($values),
                'opacity' => 0.75,
                'hovertemplate' => '%{x:.2f} A<extra>' . $this->deviceLabel($deviceId) . '</extra>',
                'nbinsx' => min(40, max(12, (int) round(count($values) / 1.75))),
            ];
        }

        $layout = $this->baseLayout('Distribución de corriente');
        $layout['barmode'] = 'overlay';
        $layout['xaxis'] = ['title' => 'Corriente promedio (A)'];
        $layout['yaxis'] = ['title' => 'Frecuencia'];
        $layout['legend'] = ['orientation' => 'h'];

        return $this->buildFigureResponse($data, $layout, [
            'devices' => $this->mappingForDevices(array_keys($series)),
        ]);
    }

    protected function buildScatter(array $rows): array
    {
        if ($rows === []) {
            return $this->buildFigureResponse([], $this->baseLayout('Voltaje vs corriente'));
        }

        $series = [];
        foreach ($rows as $row) {
            $series[$row['device_id']]['x'][] = $row['current_a'];
            $series[$row['device_id']]['y'][] = $row['voltage_v'];
            $series[$row['device_id']]['text'][] = Carbon::parse($row['measurement_time'])->format('d/m H:i');
        }

        $data = [];
        foreach ($series as $deviceId => $points) {
            $data[] = [
                'type' => 'scatter',
                'mode' => 'markers',
                'name' => $this->deviceLabel($deviceId),
                'x' => $points['x'],
                'y' => $points['y'],
                'text' => $points['text'],
                'hovertemplate' => "%{y:.1f} V<br>%{x:.1f} A<br>%{text}<extra>{$this->deviceLabel($deviceId)}</extra>",
                'marker' => [
                    'size' => 9,
                    'opacity' => 0.8,
                ],
            ];
        }

        $layout = $this->baseLayout('Voltaje vs corriente');
        $layout['xaxis'] = ['title' => 'Corriente promedio (A)'];
        $layout['yaxis'] = ['title' => 'Voltaje promedio (V)'];
        $layout['legend'] = ['orientation' => 'h'];

        return $this->buildFigureResponse($data, $layout, [
            'devices' => $this->mappingForDevices(array_keys($series)),
        ]);
    }

    protected function buildTimeseries(array $rows): array
    {
        if ($rows === []) {
            return $this->buildFigureResponse([], $this->baseLayout('Serie temporal de potencia'));
        }

        $series = [];
        foreach ($rows as $row) {
            $key = $row['measurement_time'];
            $series[$row['device_id']][$key][] = $row['power_w'];
        }

        $data = [];
        foreach ($series as $deviceId => $points) {
            ksort($points);
            $x = [];
            $y = [];
            foreach ($points as $timestamp => $values) {
                $x[] = $timestamp;
                $y[] = round(array_sum($values) / max(count($values), 1), 2);
            }
            $data[] = [
                'type' => 'scatter',
                'mode' => 'lines+markers',
                'name' => $this->deviceLabel($deviceId),
                'x' => $x,
                'y' => $y,
                'hovertemplate' => '%{y:.1f} W<br>%{x}<extra>' . $this->deviceLabel($deviceId) . '</extra>',
                'line' => [
                    'shape' => 'spline',
                    'smoothing' => 0.35,
                ],
                'marker' => [
                    'size' => 6,
                ],
            ];
        }

        $layout = $this->baseLayout('Serie temporal de potencia');
        $layout['xaxis'] = ['title' => 'Fecha / hora'];
        $layout['yaxis'] = ['title' => 'Potencia promedio (W)'];
        $layout['legend'] = ['orientation' => 'h'];

        return $this->buildFigureResponse($data, $layout, [
            'devices' => $this->mappingForDevices(array_keys($series)),
        ]);
    }

    protected function buildEnergyBar(array $rows): array
    {
        if ($rows === []) {
            return $this->buildFigureResponse([], $this->baseLayout('Energía acumulada'));
        }

        $totals = [];
        foreach ($rows as $row) {
            $totals[$row['device_id']] = ($totals[$row['device_id']] ?? 0) + $row['energy_wh'];
        }

        $deviceIds = array_keys($totals);
        $labels = array_map(fn ($deviceId) => $this->deviceLabel($deviceId), $deviceIds);
        $values = array_map(fn ($deviceId) => round($totals[$deviceId] / 1000, 2), $deviceIds); // convert to kWh

        $data = [
            [
                'type' => 'bar',
                'name' => 'Energía (kWh)',
                'x' => $labels,
                'y' => $values,
                'text' => array_map(fn ($value) => number_format($value, 2) . ' kWh', $values),
                'textposition' => 'auto',
            ],
        ];

        $layout = $this->baseLayout('Energía por dispositivo');
        $layout['xaxis'] = ['title' => 'Dispositivo'];
        $layout['yaxis'] = ['title' => 'Energía acumulada (kWh)'];

        return $this->buildFigureResponse($data, $layout, [
            'devices' => $this->mappingForDevices($deviceIds),
        ]);
    }

    protected function buildHeatmap(array $rows): array
    {
        if ($rows === []) {
            return $this->buildFigureResponse([], $this->baseLayout('Mapa de calor de potencia'));
        }

        $hours = range(0, 23);
        $hourLabels = array_map(static fn ($hour) => sprintf('%02d:00', $hour), $hours);
        $weekdayLabels = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        $grid = [];
        foreach ($weekdayLabels as $day => $label) {
            foreach ($hours as $hour) {
                $grid[$day][$hour] = ['sum' => 0.0, 'count' => 0];
            }
        }

        foreach ($rows as $row) {
            $time = Carbon::parse($row['measurement_time']);
            $day = (int) $time->isoWeekday();
            $hour = (int) $time->format('G');
            $grid[$day][$hour]['sum'] += $row['power_w'];
            $grid[$day][$hour]['count'] += 1;
        }

        $matrix = [];
        foreach ($weekdayLabels as $day => $label) {
            $matrixRow = [];
            foreach ($hours as $hour) {
                $bucket = $grid[$day][$hour];
                $matrixRow[] = $bucket['count'] > 0
                    ? round($bucket['sum'] / $bucket['count'], 2)
                    : 0;
            }
            $matrix[] = $matrixRow;
        }

        $data = [
            [
                'type' => 'heatmap',
                'x' => $hourLabels,
                'y' => array_values($weekdayLabels),
                'z' => $matrix,
                'coloraxis' => 'coloraxis',
                'hovertemplate' => 'Hora %{x}<br>%{y}<br>Potencia %{z:.1f} W<extra></extra>',
            ],
        ];

        $layout = $this->baseLayout('Patrón horario de potencia');
        $layout['xaxis'] = ['title' => 'Hora del día'];
        $layout['yaxis'] = ['title' => 'Día de la semana'];
        $layout['coloraxis'] = [
            'colorscale' => 'YlGnBu',
            'colorbar' => [
                'title' => 'Potencia (W)',
            ],
        ];

        return $this->buildFigureResponse($data, $layout);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    protected function buildFigureResponse(array $data, array $layout, array $mapping = []): array
    {
        return [
            'figure' => [
                'data' => array_values($data),
                'layout' => $layout,
            ],
            'config' => [
                'responsive' => true,
                'displayModeBar' => false,
            ],
            'mapping' => $mapping,
        ];
    }

    protected function baseLayout(string $title): array
    {
        return [
            'title' => [
                'text' => $title,
                'font' => ['size' => 18],
            ],
            'paper_bgcolor' => '#ffffff',
            'plot_bgcolor' => '#ffffff',
            'margin' => ['l' => 60, 'r' => 30, 't' => 60, 'b' => 60],
        ];
    }

    protected function filterMeasurements(array $payload): array
    {
        $filterMap = Arr::get($payload, 'filter_map', []);
        $siteId = $this->normaliseEquals(Arr::get($filterMap, 'site_id'));
        $deviceId = $this->normaliseEquals(Arr::get($filterMap, 'device_id'));
        [$from, $to] = $this->parseDateRange(Arr::get($filterMap, 'measurement_time'));

        $rows = $this->measurements();

        return array_values(array_filter($rows, static function (array $row) use ($siteId, $deviceId, $from, $to) {
            if ($siteId !== null && (string) $row['site_id'] !== (string) $siteId) {
                return false;
            }
            if ($deviceId !== null && (string) $row['device_id'] !== (string) $deviceId) {
                return false;
            }

            $timestamp = Carbon::parse($row['measurement_time']);
            if ($from && $timestamp->lt($from)) {
                return false;
            }
            if ($to && $timestamp->gt($to)) {
                return false;
            }

            return true;
        }));
    }

    protected function filterRows(array $rows, $filterMap, array $allowedKeys): array
    {
        if (!is_array($filterMap) || $filterMap === []) {
            return $rows;
        }

        return array_values(array_filter($rows, function (array $row) use ($filterMap, $allowedKeys) {
            foreach ($allowedKeys as $column) {
                if (!array_key_exists($column, $filterMap)) {
                    continue;
                }

                $raw = $filterMap[$column];
                if (is_array($raw)) {
                    $expected = array_map(function ($value) {
                        return $this->normaliseEquals($value) ?? $value;
                    }, $raw);
                    if (!in_array($row[$column], $expected, true)) {
                        return false;
                    }
                    continue;
                }

                $expected = $this->normaliseEquals($raw);
                if ($expected !== null && (string) $row[$column] !== (string) $expected) {
                    return false;
                }
            }

            return true;
        }));
    }

    protected function parseDateRange(?string $range): array
    {
        if (!$range || !is_string($range)) {
            return [null, null];
        }

        $clean = trim($range);
        $clean = trim($clean, "[]()");
        $parts = array_map('trim', explode(',', $clean));

        $from = $parts[0] ?? null;
        $to = $parts[1] ?? null;

        return [
            $from ? Carbon::parse($from)->startOfMinute() : null,
            $to ? Carbon::parse($to)->endOfMinute() : null,
        ];
    }

    protected function aggregateRows(array $rows, array $steps): array
    {
        $result = $rows;

        foreach ($steps as $step) {
            if (!is_array($step)) {
                continue;
            }

            $groupBy = array_values(array_filter((array) Arr::get($step, 'group_by', [])));
            $aggregations = Arr::get($step, 'aggregations', []);

            if ($groupBy === [] || !is_array($aggregations) || $aggregations === []) {
                continue;
            }

            $result = $this->applyAggregationStep($result, $groupBy, $aggregations);
        }

        return $result;
    }

    protected function applyAggregationStep(array $rows, array $groupBy, array $aggregations): array
    {
        if ($rows === []) {
            return [];
        }

        $groups = [];
        foreach ($rows as $row) {
            $keyValues = [];
            foreach ($groupBy as $column) {
                $keyValues[$column] = $row[$column] ?? null;
            }
            $key = json_encode($keyValues);

            if (!isset($groups[$key])) {
                $groups[$key] = ['meta' => $keyValues, 'rows' => []];
            }
            $groups[$key]['rows'][] = $row;
        }

        $results = [];
        foreach ($groups as $group) {
            $output = $group['meta'];
            foreach ($aggregations as $column => $operations) {
                $operations = (array) $operations;
                $values = array_column($group['rows'], $column);

                foreach ($operations as $operation) {
                    switch ($operation) {
                        case 'count':
                            $output["{$column}_count"] = count($group['rows']);
                            break;
                        case 'avg':
                            $numeric = array_values(array_filter($values, static fn ($value) => is_numeric($value)));
                            $output["{$column}_avg"] = $numeric === []
                                ? null
                                : round(array_sum($numeric) / count($numeric), 4);
                            break;
                        case 'sum':
                            $numeric = array_values(array_filter($values, static fn ($value) => is_numeric($value)));
                            $output["{$column}_sum"] = $numeric === [] ? 0 : round(array_sum($numeric), 4);
                            break;
                        default:
                            // no-op for unsupported aggregations
                            break;
                    }
                }
            }
            $results[] = $output;
        }

        return $results;
    }

    protected function normaliseEquals($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }

            if ($trimmed[0] === '=') {
                $trimmed = substr($trimmed, 1);
            }

            return $trimmed;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return null;
    }

    protected function mappingForDevices(array $deviceIds): array
    {
        $map = [];
        foreach ($deviceIds as $deviceId) {
            $map[$deviceId] = $this->deviceLabel($deviceId);
        }

        return ['devices' => $map];
    }

    protected function deviceLabel(string $deviceId): string
    {
        foreach ($this->devices() as $device) {
            if ((string) $device['device_id'] === (string) $deviceId) {
                return $device['device_name'];
            }
        }

        return $deviceId;
    }

    protected function sites(): array
    {
        return [
            ['site_id' => '1', 'site_name' => 'Sitio Solar Norte'],
            ['site_id' => '2', 'site_name' => 'Sitio Eólico Centro'],
            ['site_id' => '3', 'site_name' => 'Sitio Híbrido Sur'],
        ];
    }

    protected function devices(): array
    {
        return [
            ['site_id' => '1', 'device_id' => 'INV-101', 'device_name' => 'Inversor A1'],
            ['site_id' => '1', 'device_id' => 'INV-102', 'device_name' => 'Inversor A2'],
            ['site_id' => '1', 'device_id' => 'INV-103', 'device_name' => 'Inversor A3'],
            ['site_id' => '2', 'device_id' => 'TRF-201', 'device_name' => 'Transformador B1'],
            ['site_id' => '2', 'device_id' => 'TRF-202', 'device_name' => 'Transformador B2'],
            ['site_id' => '3', 'device_id' => 'HBD-301', 'device_name' => 'Batería C1'],
            ['site_id' => '3', 'device_id' => 'HBD-302', 'device_name' => 'Batería C2'],
        ];
    }

    protected function siteDailyKpi(): array
    {
        if ($this->siteDailyKpi !== null) {
            return $this->siteDailyKpi;
        }

        $today = Carbon::now()->startOfDay();
        $records = [];

        foreach ($this->sites() as $index => $site) {
            for ($offset = 0; $offset < 10; $offset++) {
                $date = $today->copy()->subDays($offset);
                $baselineAvailability = 91 + ($index * 2.5);
                $variationAvailability = sin(($offset + 1) / 3 + $index) * 4.5;
                $availability = max(78, min(99.5, $baselineAvailability + $variationAvailability));

                $energyBase = 1450 + ($index * 120) + cos(($offset + $index) / 3.2) * 140;
                $energy = max(450, $energyBase - ($offset * 35));
                $peakPower = ($energy / 6) + rand(80, 140);
                $loadFactor = max(0.25, min(0.92, ($energy / ($peakPower * 24))));
                $avgPowerFactor = 0.9 + ($index * 0.02) + sin(($offset + $index) / 4) * 0.03;
                $pfCompliance = max(70, min(99, $avgPowerFactor * 100 - rand(0, 8)));
                $freshness = rand(5, 90);

                $records[] = [
                    'site_id' => $site['site_id'],
                    'kpi_date' => $date->format('Y-m-d'),
                    'total_energy_wh' => round($energy * 1000, 2),
                    'peak_power_w' => round($peakPower * 1000, 2),
                    'load_factor' => round($loadFactor, 3),
                    'avg_power_factor' => round(min(0.99, max(0.85, $avgPowerFactor)), 3),
                    'pf_compliance_pct' => round($pfCompliance, 1),
                    'availability_pct' => round($availability, 1),
                    'availability_pct_avg' => round($availability, 1),
                    'data_freshness_minutes' => $freshness,
                    'hours_present' => rand(21, 24),
                ];
            }
        }

        $this->siteDailyKpi = $records;

        return $this->siteDailyKpi;
    }

    protected function siteHourlyKpi(): array
    {
        if ($this->siteHourlyKpi !== null) {
            return $this->siteHourlyKpi;
        }

        $records = [];
        $now = Carbon::now()->startOfHour();

        foreach ($this->sites() as $index => $site) {
            $totalDevices = $index === 0 ? 18 : ($index === 1 ? 14 : 10);
            for ($offset = 0; $offset < 72; $offset++) {
                $hour = $now->copy()->subHours($offset);
                $active = max(0, min($totalDevices, $totalDevices - rand(0, 3) + (int) round(sin(($offset + $index) / 5))));
                $availability = $totalDevices === 0 ? 0 : ($active / $totalDevices) * 100;
                $records[] = [
                    'site_id' => $site['site_id'],
                    'hour_start' => $hour->format('Y-m-d H:i:s'),
                    'active_devices' => $active,
                    'total_devices' => $totalDevices,
                    'availability_pct' => round($availability, 1),
                    'energy_wh_sum' => ($active * 420) + rand(120, 320),
                    'pf_compliance_pct' => round(80 + sin(($offset + $index) / 4) * 8, 1),
                    'data_freshness_minutes' => rand(3, 40),
                ];
            }
        }

        $this->siteHourlyKpi = $records;

        return $this->siteHourlyKpi;
    }

    protected function deviceDailyKpi(): array
    {
        if ($this->deviceDailyKpi !== null) {
            return $this->deviceDailyKpi;
        }

        $today = Carbon::now()->startOfDay();
        $records = [];

        foreach ($this->devices() as $device) {
            for ($offset = 0; $offset < 10; $offset++) {
                $date = $today->copy()->subDays($offset);
                $baseEnergy = 380 + rand(40, 120);
                $energy = $baseEnergy + sin(($offset + (int) $device['device_id']) / 4) * 60;
                $records[] = [
                    'site_id' => $device['site_id'],
                    'device_id' => $device['device_id'],
                    'kpi_date' => $date->format('Y-m-d'),
                    'energy_wh_sum' => round($energy * 1000, 2),
                    'power_w_avg' => round($energy * 35, 2),
                    'availability_pct' => 85 + rand(0, 10),
                ];
            }
        }

        $this->deviceDailyKpi = $records;

        return $this->deviceDailyKpi;
    }

    protected function ingestionRunKpi(): array
    {
        if ($this->ingestionRunKpi !== null) {
            return $this->ingestionRunKpi;
        }

        $today = Carbon::now()->startOfDay();
        $records = [];

        for ($offset = 0; $offset < 30; $offset++) {
            $date = $today->copy()->subDays($offset);
            $lag = max(5, rand(5, 60) + sin(($offset) / 3) * 10);
            $records[] = [
                'run_date' => $date->format('Y-m-d'),
                'sites_processed' => 12 + rand(0, 3),
                'devices_processed' => 42 + rand(0, 10),
                'records_loaded' => 120000 + rand(5000, 20000),
                'ingestion_lag_minutes' => round($lag, 1),
                'latest_measurement_time' => $date->copy()->setTime(23, rand(0, 59))->format('Y-m-d H:i:s'),
            ];
        }

        $this->ingestionRunKpi = $records;

        return $this->ingestionRunKpi;
    }

    protected function measurements(): array
    {
        if ($this->measurements !== null) {
            return $this->measurements;
        }

        $start = Carbon::now()->subDays(9)->startOfDay();
        $hours = 24 * 10;
        $measurements = [];
        $devices = $this->devices();

        foreach ($devices as $index => $device) {
            $siteIndex = $this->siteIndex($device['site_id']);
            for ($hour = 0; $hour < $hours; $hour++) {
                $timestamp = $start->copy()->addHours($hour);
                $hourOfDay = (int) $timestamp->format('G');

                $baseCurrent = 14 + ($siteIndex * 3.2) + ($index % 3) * 1.4;
                $current = $baseCurrent
                    + sin(($hour + $index) / 3.2) * 2.8
                    + cos(($hour + $siteIndex) / 6.4);
                $voltage = 220 + ($siteIndex * 3.5) + sin(($hourOfDay + $index) / 2.5) * 4.6;
                $power = $current * ($voltage / 220) * 10;
                $energy = $power * 0.92;

                $measurements[] = [
                    'measurement_time' => $timestamp->format('Y-m-d H:00:00'),
                    'site_id' => $device['site_id'],
                    'device_id' => $device['device_id'],
                    'power_w' => round($power, 2),
                    'current_a' => round($current, 2),
                    'voltage_v' => round($voltage, 2),
                    'energy_wh' => round($energy, 2),
                ];
            }
        }

        $this->measurements = $measurements;

        return $this->measurements;
    }

    protected function siteIndex(string $siteId): int
    {
        foreach (array_values($this->sites()) as $index => $site) {
            if ($site['site_id'] === $siteId) {
                return $index;
            }
        }

        return 0;
    }
}
