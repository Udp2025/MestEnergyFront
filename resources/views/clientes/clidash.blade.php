@extends('layouts.complete')
@section('title', 'Energy Dashboard')
@vite([
    'resources/js/pages/energy-dashboard.js'
])
@section('content')
<link rel="stylesheet" href="{{ asset('css/usuarios.css') }}">

@php
// Si no se recibió $latestCost desde el controlador, creamos defaults (fallback)
if (!isset($latestCost)) {
    $latestCost = (object)[
        'cargo_fijo' => 0,
        'cargo_base' => 0,
        'cargo_intermedio' => 0,
        'cargo_punta' => 0,
        'cargo_distribucion' => 0,
        'cargo_capacidad' => 0,
        'subtotal' => 0,
        'iva' => 0,
        'total' => 0,
        'cargo_fijo_pt' => 0,
        'consumo_capa_pt' => 0,
        'consumo_dist_pt' => 0,
        'consumo_base_pt' => 0,
        'consumo_intermedio_pt' => 0,
        'consumo_punta_pt' => 0,
        'factor_potencia_pt' => 0,
        'fecha_inicio' => null,
        'kwh_base' => 0,
        'kwh_intermedio' => 0,
        'kwh_punta' => 0,
        'energia_generada' => 0,
        'kw_max' => 0,
        'kw_punta' => 0
    ];
}

// Datos estáticos del sitio (puedes cargarlos desde DB más adelante)
$mainSite = (object)['name'=>'LAPROBA EL ÁGUILA SA DE CV'];
$subSites = [
    'Accesorios De Prensa','Extrusora Prensa Doble','Oficinas','Oficinas Nuevas',
    'Taller Mantenimiento','Transformador Seco 112'
];

// prepara arrays que usa el blade original
$subtotal = $latestCost->subtotal ?? 0;
$iva = $latestCost->iva ?? 0;
$total = $latestCost->total ?? 0;
$kwhBase = $latestCost->kwh_base ?? 0;
$kwhIntermedio = $latestCost->kwh_intermedio ?? 0;
$kwhPunta = $latestCost->kwh_punta ?? 0;
$energiaGenerada = $latestCost->energia_generada ?? 0;
$kwMax = $latestCost->kw_max ?? 0;
$kwPunta = $latestCost->kw_punta ?? 0;

$costDetails = [
    ['label'=>'Cargo Fijo (Suministro)','pct'=>$latestCost->cargo_fijo_pt ?? 0,'value'=>$latestCost->cargo_fijo ?? 0],
    ['label'=>'Capacidad','pct'=>$latestCost->consumo_capa_pt ?? 0,'value'=>$latestCost->cargo_capacidad ?? 0],
    ['label'=>'Distribución','pct'=>$latestCost->consumo_dist_pt ?? 0,'value'=>$latestCost->cargo_distribucion ?? 0],
    ['label'=>'Base','pct'=>$latestCost->consumo_base_pt ?? 0,'value'=>$latestCost->cargo_base ?? 0],
    ['label'=>'Intermedia','pct'=>$latestCost->consumo_intermedio_pt ?? 0,'value'=>$latestCost->cargo_intermedio ?? 0],
    ['label'=>'Punta','pct'=>$latestCost->consumo_punta_pt ?? 0,'value'=>$latestCost->cargo_punta ?? 0],
];

$factorPotencia = $latestCost->factor_potencia_pt ?? $latestCost->factor_potencia ?? 0;

$barLabels = ['Cargo Fijo','Capacidad','Distribución','Base','Intermedia','Punta'];
$barData = [
    $latestCost->cargo_fijo_pt ?? 0,
    $latestCost->consumo_capa_pt ?? 0,
    $latestCost->consumo_dist_pt ?? 0,
    $latestCost->consumo_base_pt ?? 0,
    $latestCost->consumo_intermedio_pt ?? 0,
    $latestCost->consumo_punta_pt ?? 0
];

$doughnutLabels = ['Base','Capacidad','Cargo Fijo','Distribución','Intermedia','Punta'];
$doughnutData = [
    $latestCost->cargo_base ?? 0,
    $latestCost->cargo_capacidad ?? 0,
    $latestCost->cargo_fijo ?? 0,
    $latestCost->cargo_distribucion ?? 0,
    $latestCost->cargo_intermedio ?? 0,
    $latestCost->cargo_punta ?? 0
];

// Top drivers (mismo enfoque)
$costValues = [
    ['label'=>'Intermedia','value'=>$latestCost->cargo_intermedio ?? 0],
    ['label'=>'Capacidad','value'=>$latestCost->cargo_capacidad ?? 0],
    ['label'=>'Base','value'=>$latestCost->cargo_base ?? 0],
    ['label'=>'Distribución','value'=>$latestCost->cargo_distribucion ?? 0],
    ['label'=>'Punta','value'=>$latestCost->cargo_punta ?? 0],
    ['label'=>'Cargo Fijo','value'=>$latestCost->cargo_fijo ?? 0],
];
usort($costValues, function($a,$b){ return $b['value'] <=> $a['value']; });
$topDrivers = array_slice($costValues,0,3);
@endphp

{{-- FILTROS: formulario GET (envía a la misma ruta) --}}
<form method="GET" action="{{ route('energy.dashboard') }}" class="filter-form" style="margin-bottom:1rem; display:flex; gap:10px; align-items:flex-end;">
    <div>
        <label>Inicio</label>
        <input type="date" name="start_date" value="{{ request('start_date', $filters['start'] ?? '') }}" />
    </div>
    <div>
        <label>Fin</label>
        <input type="date" name="end_date" value="{{ request('end_date', $filters['end'] ?? '') }}" />
    </div>
    <!--
    <div>
        <label>Device ID</label>
        <input type="number" name="device_id" value="{{ request('device_id', $filters['device_id'] ?? '') }}" />
    </div>
    <div>
        <label>Site ID</label>
        <input type="number" name="site_id" value="{{ request('site_id', $filters['site_id'] ?? '') }}" />
    </div>
    -->
    <div>
        <label>Sitio</label>
        <select name="site_id" id="site-select"></select>
    </div>
    <div>
        <label>Sensor</label>
        <select name="device_id" id="device-select" disabled></select>
    </div>
    <div>
        <button class="btn btn-primary" type="submit">Aplicar filtros</button>
    </div>
</form>



<div class="dash-wrap enhanced">
    {{-- TOP METRICS --}}
    <div class="top-metrics">
        <div class="metric-card">
            <div class="metric-row">
                <div>
                    <div class="metric-title">Subtotal</div>
                    <div class="metric-value">${{ number_format($subtotal,2,'.',',') }}</div>
                </div>
                <div class="metric-actions">
                    <button class="icon-btn" title="Copiar"><i class="fas fa-copy"></i></button>
                </div>
            </div>
            <div class="metric-foot">Detalle de periodo: mensual</div>
        </div>
        <div class="metric-card">
            <div class="metric-row">
                <div>
                    <div class="metric-title">IVA (16%)</div>
                    <div class="metric-value">${{ number_format($iva,2,'.',',') }}</div>
                </div>
                <div class="metric-actions small">% </div>
            </div>
            <div class="metric-foot">Porcentaje aplicado</div>
        </div>
        <div class="metric-card card-strong">
            <div class="metric-row">
                <div>
                    <div class="metric-title">Total CFE</div>
                    <div class="metric-value">${{ number_format($total,2,'.',',') }}</div>
                </div>
                <div class="metric-actions">
                    <button class="icon-btn" title="Descargar"><i class="fas fa-download"></i></button>
                </div>
            </div>
            <div class="metric-foot">Comparativa con mes previo</div>
        </div>
    </div>

    <div class="top-metrics energy-metrics">
        <div class="metric-card">
            <div class="metric-row">
                <div>
                    <div class="metric-title">kWh base</div>
                    <div class="metric-value">{{ number_format($kwhBase,2,'.',',') }}</div>
                </div>
            </div>
            <div class="metric-foot">kWh</div>
        </div>
        <div class="metric-card">
            <div class="metric-row">
                <div>
                    <div class="metric-title">kWh intermedia</div>
                    <div class="metric-value">{{ number_format($kwhIntermedio,2,'.',',') }}</div>
                </div>
            </div>
            <div class="metric-foot">kWh</div>
        </div>
        <div class="metric-card">
            <div class="metric-row">
                <div>
                    <div class="metric-title">kWh punta</div>
                    <div class="metric-value">{{ number_format($kwhPunta,2,'.',',') }}</div>
                </div>
            </div>
            <div class="metric-foot">kWh</div>
        </div>
        <div class="metric-card">
            <div class="metric-row">
                <div>
                    <div class="metric-title">kW Max</div>
                    <div class="metric-value">{{ number_format($kwMax,2,'.',',') }}</div>
                </div>
            </div>
            <div class="metric-foot">kW</div>
        </div>
        <div class="metric-card">
            <div class="metric-row">
                <div>
                    <div class="metric-title">kW punta</div>
                    <div class="metric-value">{{ number_format($kwPunta,2,'.',',') }}</div>
                </div>
            </div>
            <div class="metric-foot">kW</div>
        </div>
        <div class="metric-card">
            <div class="metric-row">
                <div>
                    <div class="metric-title">Energia Generada</div>
                    <div class="metric-value">{{ number_format($energiaGenerada,2,'.',',') }}</div>
                </div>
            </div>
            <div class="metric-foot">kWh</div>
        </div>
    </div>

    {{-- Main layout --}}
    <div class="main-grid">
        {{-- Left column --}}
        <aside class="col-left card">
            <h3>Detalle de costos</h3>
            <p class="muted small">Incluye crédito/débito por Factor de Potencia</p>
            <div class="cost-list">
                @foreach($costDetails as $c)
                <div class="cost-row">
                    <div class="cost-meta">
                        <div class="cost-label">{{ $c['label'] }}</div>
                        <div class="cost-value">${{ number_format($c['value'],2,'.',',') }}</div>
                    </div>
                    <div class="cost-bar">
                        <div class="bar-fill" style="width: {{ $c['pct'] }}%"></div>
                    </div>
                </div>
                @endforeach
                <div class="cost-row factor">
                    <div class="cost-meta">
                        <div class="cost-label">Factor de Potencia aplicado</div>
                        <div class="cost-value {{ $factorPotencia < 0 ? 'text-green' : '' }}">
                            ${{ number_format($factorPotencia,2,'.',',') }}
                        </div>
                    </div>
                </div>
                <div class="summary">
                    <div class="summary-row"><span>Subtotal</span><strong>${{ number_format($subtotal,2,'.',',') }}</strong></div>
                    <div class="summary-row"><span>IVA (16%)</span><strong>${{ number_format($iva,2,'.',',') }}</strong></div>
                    <div class="summary-row total"><span>Total</span><strong>${{ number_format($total,2,'.',',') }}</strong></div>
                </div>
            </div>
        </aside>

        {{-- Center + Right combined --}}
        <section class="col-center">
            <div class="card chart-card elevated">
                <div class="card-header spaced">
                    <h4>Pesos tarifarios (%)</h4>
                    <div class="mini-legend"><span><i class="dot solid"></i> Porcentaje</span></div>
                </div>
                <div class="chart-wrap">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
            <div class="lower-row">
                <div class="card donut-card elevated">
                    <div class="donut-head">
                        <h4>Distribución de costos</h4>
                        <div class="muted small">Total base vs consumo</div>
                    </div>
                    <div class="donut-canvas">
                        <canvas id="doughnutChart"></canvas>
                        <div class="donut-center">
                            <div class="donut-total">${{ number_format($total,0,'.',',') }}</div>
                            <div class="donut-sub">Total CFE</div>
                        </div>
                    </div>
                    <div class="donut-legend">
                        @foreach($doughnutLabels as $i => $lbl)
                        <div class="legend-item"><span class="swatch sw-{{ $i }}"></span>{{ $lbl }}</div>
                        @endforeach
                    </div>
                </div>
                <div class="card drivers-card elevated">
                    <div class="drivers-header">
                        <h4>Top drivers de gasto</h4>
                        <div class="chip">Top 3</div>
                    </div>
                    <div class="drivers-list">
                        @php $driversSum = array_sum(array_column($topDrivers,'value')); @endphp
                        @foreach($topDrivers as $d)
                        @php $pct = $driversSum ? round($d['value'] / $driversSum * 100) : 0; @endphp
                        <div class="driver-row">
                            <div class="driver-info">
                                <div class="driver-left">
                                    <div class="driver-label">{{ $d['label'] }}</div>
                                    <div class="driver-value">${{ number_format($d['value'],2,'.',',') }}</div>
                                </div>
                            </div>
                            <div class="driver-bar">
                                <div class="driver-fill" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Footer card small --}}
    <div class="bottom-note card small">
        <div><i class="fas fa-info-circle"></i> Factor de Potencia aplicado: 
            <strong>${{ number_format($factorPotencia,2,'.',',') }}</strong>
        </div>
        <div class="muted">
            Última actualización:
            {{
                $latestCost->fecha_inicio
                    ? (
                        is_numeric($latestCost->fecha_inicio)
                        ? (
                            $latestCost->fecha_inicio > 1000000000000
                                ? \Carbon\Carbon::createFromTimestampMs($latestCost->fecha_inicio)->format('Y-m-d H:i')
                                : \Carbon\Carbon::createFromTimestamp($latestCost->fecha_inicio)->format('Y-m-d H:i')
                        )
                        : \Carbon\Carbon::parse($latestCost->fecha_inicio)->format('Y-m-d H:i')
                    )
                    : 'N/A'
            }}
        </div>

    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/* BAR CHART */
const barCtx = document.getElementById('barChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: @json($barLabels),
        datasets: [{
            label: '%',
            data: @json($barData),
            backgroundColor: Array(6).fill('rgba(163,89,26,0.95)'),
            borderRadius: 8,
            maxBarThickness: 36
        }]
    },
    options: {
        indexAxis: 'x',
        plugins: {
            legend: {display: false},
            tooltip: {
                callbacks: {
                    label: ctx => ctx.parsed.y + '%'
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: {color: '#7f4b1a'}
            },
            y: {
                beginAtZero: true,
                grid: {color: 'rgba(127,75,26,0.06)'},
                ticks: {
                    callback: v => v + '%',
                    color: '#7f4b1a'
                }
            }
        },
        responsive: true,
        maintainAspectRatio: false
    }
});

/* DOUGHNUT CHART */
const donutCtx = document.getElementById('doughnutChart').getContext('2d');
const centerTextPlugin = {
    id: 'centerText',
    afterDraw(chart) {
        const {ctx, chartArea: {width, height}} = chart;
        ctx.save();
        ctx.restore();
    }
};

new Chart(donutCtx, {
    type: 'doughnut',
    data: {
        labels: @json($doughnutLabels),
        datasets: [{
            data: @json($doughnutData),
            backgroundColor: [
                '#cfe7df','#e7cbbf','#aee1cf','#d1b9a2','#a3591a','#c7d6d0'
            ],
            hoverOffset: 6
        }]
    },
    options: {
        plugins: {legend: {display: false}},
        cutout: '68%',
        responsive: true,
        maintainAspectRatio: false
    },
    plugins: [centerTextPlugin]
});
</script>
@endsection
