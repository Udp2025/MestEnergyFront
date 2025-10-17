@extends('layouts.complete')

@section('title', 'Energy Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/usuarios.css') }}">

@php
    // Datos de ejemplo (reemplaza con tu backend real)
    $mainSite = (object)['name'=>'LAPROBA EL ÁGUILA SA DE CV'];
    $subSites = [
      'Accesorios De Prensa','Extrusora Prensa Doble','Oficinas','Oficinas Nuevas',
      'Taller Mantenimiento','Transformador Seco 112'
    ];

    $subtotal = 293532.92;
    $iva = 46965.27;
    $total = 340498.19;

    $costDetails = [
      ['label'=>'Cargo Fijo (Suministro)','pct'=>0.16,'value'=>464.57],
      ['label'=>'Capacidad','pct'=>33.33,'value'=>97845.44],
      ['label'=>'Distribución','pct'=>10.84,'value'=>31828.84],
      ['label'=>'Base','pct'=>11.50,'value'=>33769.75],
      ['label'=>'Intermedia','pct'=>43.60,'value'=>127980.85],
      ['label'=>'Punta','pct'=>3.02,'value'=>8861.47],
    ];

    $factorPotencia = -7218.00;

    $barLabels = ['Cargo Fijo','Capacidad','Distribución','Base','Intermedia','Punta'];
    $barData   = [0.16,33.33,10.84,11.50,43.60,3.02];

    $doughnutLabels = ['Base','Capacidad','Cargo Fijo','Distribución','Intermedia','Punta'];
    $doughnutData = [33769.75,97845.44,464.57,31828.84,127980.85,8861.47];

    $topDrivers = [
      ['label'=>'Intermedia','value'=>127980.85],
      ['label'=>'Capacidad','value'=>97845.44],
      ['label'=>'Base','value'=>33769.75],
    ];
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
            <div class="cost-value text-green">${{ number_format($factorPotencia,2,'.',',') }}</div>
          </div>
        </div>

        <div class="summary">
          <div class="summary-row"><span>Subtotal</span><strong>${{ number_format($subtotal,2,'.',',') }}</strong></div>
          <div class="summary-row"><span>IVA (16%)</span><strong>${{ number_format($iva,2,'.',',') }}</strong></div>
          <div class="summary-row total"><span>Total</span><strong>${{ number_format($total,2,'.',',') }}</strong></div>
        </div>
      </div>
    </aside>

    {{-- Center + Right combined (center large + right column integrated in lower row) --}}
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
    <div><i class="fas fa-info-circle"></i> Factor de Potencia aplicado: <strong>${{ number_format($factorPotencia,2,'.',',') }}</strong></div>
    <div class="muted">Última actualización: ahora</div>
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
      plugins:{legend:{display:false}, tooltip:{callbacks:{label: ctx => ctx.parsed.y + '%' }}},
      scales: {
        x: { grid: { display: false }, ticks:{color:'#7f4b1a'} },
        y: { beginAtZero:true, grid:{color:'rgba(127,75,26,0.06)'}, ticks:{callback: v => v + '%', color:'#7f4b1a'} }
      },
      responsive:true,
      maintainAspectRatio:false
    }
  });

  /* DOUGHNUT + center text plugin */
  const donutCtx = document.getElementById('doughnutChart').getContext('2d');

  // plugin to draw center text (Chart.js v3+)
  const centerTextPlugin = {
    id: 'centerText',
    afterDraw(chart) {
      const {ctx, chartArea: {width, height}} = chart;
      ctx.save();
      // We don't use plugin to draw because we already added HTML overlay (.donut-center).
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
      plugins:{legend:{display:false}},
      cutout: '68%',
      responsive:true,
      maintainAspectRatio:false
    },
    plugins: [centerTextPlugin]
  });
</script>
@endsection
