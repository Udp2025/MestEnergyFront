@extends('layouts.complete')
@section('title', 'Energy Dashboard')
@section('content')
<link rel="stylesheet" href="{{ asset('css/usuarios.css') }}">

@php
use App\Models\CostMonth;

// Obtener los datos más recientes de la base de datos
$latestCost = CostMonth::orderBy('fecha_inicio', 'desc')->first();

// Si no hay datos, usar valores por defecto
if (!$latestCost) {
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
        'fecha_inicio' => null
    ];
}

// Función para formatear el timestamp de la base de datos
function formatTimestamp($timestamp) {
    if (!$timestamp) return 'N/A';
    
    // Si es un timestamp numérico grande (como en tu BD)
    if (is_numeric($timestamp)) {
        // Asumiendo que es un timestamp de Unix en milisegundos o segundos
        if ($timestamp > 1000000000000) {
            // Probablemente en milisegundos
            return date('Y-m-d H:i', $timestamp / 1000);
        } else {
            // En segundos
            return date('Y-m-d H:i', $timestamp);
        }
    }
    
    return 'Formato inválido';
}

// Datos del sitio
$mainSite = (object)['name'=>'LAPROBA EL ÁGUILA SA DE CV'];
$subSites = [
    'Accesorios De Prensa','Extrusora Prensa Doble','Oficinas','Oficinas Nuevas',
    'Taller Mantenimiento','Transformador Seco 112'
];

// Calcular totales desde la base de datos
$subtotal = $latestCost->subtotal;
$iva = $latestCost->iva;
$total = $latestCost->total;

// Detalles de costos desde la base de datos
$costDetails = [
    ['label'=>'Cargo Fijo (Suministro)','pct'=>$latestCost->cargo_fijo_pt,'value'=>$latestCost->cargo_fijo],
    ['label'=>'Capacidad','pct'=>$latestCost->consumo_capa_pt,'value'=>$latestCost->cargo_capacidad],
    ['label'=>'Distribución','pct'=>$latestCost->consumo_dist_pt,'value'=>$latestCost->cargo_distribucion],
    ['label'=>'Base','pct'=>$latestCost->consumo_base_pt,'value'=>$latestCost->cargo_base],
    ['label'=>'Intermedia','pct'=>$latestCost->consumo_intermedio_pt,'value'=>$latestCost->cargo_intermedio],
    ['label'=>'Punta','pct'=>$latestCost->consumo_punta_pt,'value'=>$latestCost->cargo_punta],
];

$factorPotencia = $latestCost->factor_potencia_pt;

// Datos para gráfica de barras (porcentajes)
$barLabels = ['Cargo Fijo','Capacidad','Distribución','Base','Intermedia','Punta'];
$barData = [
    $latestCost->cargo_fijo_pt,
    $latestCost->consumo_capa_pt,
    $latestCost->consumo_dist_pt,
    $latestCost->consumo_base_pt,
    $latestCost->consumo_intermedio_pt,
    $latestCost->consumo_punta_pt
];

// Datos para gráfica de doughnut (valores monetarios)
$doughnutLabels = ['Base','Capacidad','Cargo Fijo','Distribución','Intermedia','Punta'];
$doughnutData = [
    $latestCost->cargo_base,
    $latestCost->cargo_capacidad,
    $latestCost->cargo_fijo,
    $latestCost->cargo_distribucion,
    $latestCost->cargo_intermedio,
    $latestCost->cargo_punta
];

// Top drivers de gasto (ordenados por valor descendente)
$costValues = [
    ['label'=>'Intermedia','value'=>$latestCost->cargo_intermedio],
    ['label'=>'Capacidad','value'=>$latestCost->cargo_capacidad],
    ['label'=>'Base','value'=>$latestCost->cargo_base],
    ['label'=>'Distribución','value'=>$latestCost->cargo_distribucion],
    ['label'=>'Punta','value'=>$latestCost->cargo_punta],
    ['label'=>'Cargo Fijo','value'=>$latestCost->cargo_fijo],
];

// Ordenar por valor descendente y tomar los top 3
usort($costValues, function($a, $b) {
    return $b['value'] <=> $a['value'];
});
$topDrivers = array_slice($costValues, 0, 3);

@endphp

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
        <div class="muted">Última actualización: {{ formatTimestamp($latestCost->fecha_inicio) }}</div>
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