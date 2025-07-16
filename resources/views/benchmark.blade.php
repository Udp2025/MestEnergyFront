@extends('layouts.complete')

@section('title', 'Benchmark')

@section('content')
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Benchmarking</title>
    <link rel="stylesheet" href="{{ asset('css/benchmark.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
 @php
    // Datos “hardcodeados”
    $mainSite = (object)['name' => 'LAPROBA EL ÁGUILA SA DE CV'];
    $subSites = [
      'Accesorios De Prensa Favole',
      'Extrusora Prensa Doble',
      'Oficinas',
      'Oficinas Nuevas',
      'Taller Mantemien',
      'Transformador Seco 1127V',
    ];
    $periodStart = \Carbon\Carbon::now()->startOfWeek();
    $periodEnd   = \Carbon\Carbon::now()->endOfWeek();
    $chartData = [
      ['label'=>'Accesorios De Prensa Favole','value'=>0.019,'color'=>'#8fc5fe'],
      ['label'=>'Extrusora Prensa Doble','value'=>510,'color'=>'#a3591a'],
      ['label'=>'Oficinas','value'=>148,'color'=>'#f0ad4e'],
      ['label'=>'Oficinas Nuevas','value'=>215,'color'=>'#7f4b1a'],
      ['label'=>'Taller Mantemien','value'=>257,'color'=>'#843b1a'],
      ['label'=>'Transformador Seco 1127V','value'=>577,'color'=>'#5ea6ad'],
    ];
@endphp

<div class="container">
  {{-- Toolbar superior --}}
  <div class="re-toolbar">
  <div class="re-filters">
    @foreach(['Device','Energy','None','None','None','Weekly'] as $i => $opt)
      @php
        $labels = ['Benchmark By','Show By','Group By','Normalize by','Show Line','Period'];
      @endphp
      <div class="re-filter">
        <label>{{ $labels[$i] }}</label>
        <select><option>{{ $opt }}</option></select>
      </div>
    @endforeach
  </div>

  <div class="re-dates">
    <button>&lt;</button>
    <span>{{ $periodStart->format('M j, Y') }} – {{ $periodEnd->format('M j, Y') }}</span>
    <button>&gt;</button>
    <button class="download"><i class="fas fa-download"></i></button>
  </div>
</div>

  <div class="re-body">
    {{-- Sidebar --}}
    <aside class="re-sidebar">
      <h2>{{ $mainSite->name }}</h2>
      <ul>
        @foreach($subSites as $name)
          <li><label><input type="checkbox" checked> {{ $name }}</label></li>
        @endforeach
        <li class="other">+ Other Sites</li>
      </ul>
    </aside>

    {{-- Gráfico y leyenda --}}
    <section class="re-chart">
      <canvas id="energyChart"></canvas>
      <div class="re-legend">
        @foreach($chartData as $item)
          <div class="legend-item">
            <span class="dot" style="background:{{ $item['color'] }}"></span>
            {{ $item['label'] }}
          </div>
        @endforeach
      </div>
    </section>
  </div>
</div>

<script>
  const data = @json($chartData);
  new Chart(document.getElementById('energyChart'), {
    type: 'bar',
    data: {
      labels: data.map(d => d.label),
      datasets: [{
        data: data.map(d => d.value),
        backgroundColor: data.map(d => d.color),
        borderRadius: 6,
        barPercentage: 0.7
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        x: {
          ticks: { maxRotation: 45, minRotation: 30, color: '#7f4b1a' },
          grid: { display: false }
        },
        y: {
          beginAtZero: true,
          ticks: { callback: v => v + ' kWh', color: '#7f4b1a' },
          grid: { color: 'rgba(127,75,26,0.1)' }
        }
      },
      responsive: true,
      maintainAspectRatio: false
    }
  });
</script>
@endsection