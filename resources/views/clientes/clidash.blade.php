@extends('layouts.complete')

@section('title', 'Energy Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/usuarios.css') }}">

@php
    // Sidebar
    $mainSite = (object)['name'=>'LAPROBA EL ÁGUILA SA DE CV'];
    $subSites = [
      'Accesorios De Prensa',
      'Extrusora Prensa Doble',
      'Oficinas',
      'Oficinas Nuevas',
      'Taller Mantenimiento',
      'Transformador Seco 112',
    ];

    // Cards
    $dailyCost   = 400;
    $monthlyCost = 1200;
    $genCost     = -300;

    // Gráfico: meses y dos series
    $months     = ['Ene','Feb'];
    $seriesCost = [1000, 1700];
    $seriesGen  = [800, 1400];
@endphp

<div class="fin-container">
  {{-- Header con filtro y título --}}
  <div class="fin-header">
    <div class="fin-filter">
      <label>Benchmark By:</label>
      <select><option>Device</option></select>
    </div>
    <h1>Financial</h1>
  </div>

  <div class="fin-body">
    {{-- Sidebar --}}
    <aside class="fin-sidebar">
      <h2><i class="fas fa-map-marker-alt"></i> {{ $mainSite->name }}</h2>
      <ul>
        @foreach($subSites as $s)
          <li><label><input type="checkbox" checked> {{ $s }}</label></li>
        @endforeach
        <li class="other">+ Other Sites</li>
      </ul>
    </aside>

    {{-- Contenido principal --}}
    <section class="fin-content">
      {{-- Tarjetas --}}
      <div class="fin-cards">
        <div class="fin-card">
          <div class="fin-value">${{ number_format($dailyCost) }}</div>
          <div class="fin-label">Costo Diario Estimado</div>
        </div>
        <div class="fin-card">
          <div class="fin-value">${{ number_format($monthlyCost) }} USD</div>
          <div class="fin-label">Costo Mensual Estimado</div>
        </div>
        <div class="fin-card highlight">
          <div class="fin-value">-${{ number_format(abs($genCost)) }} USD</div>
          <div class="fin-label">Costo Mensual de Generación Estimada</div>
        </div>
      </div>

      {{-- Gráfico --}}
      <div class="fin-chart-card">
        <canvas id="finChart"></canvas>
        <div class="fin-chart-legend">
          <span><i class="dot solid"></i> Costo</span>
          <span><i class="dot dashed"></i> Generación</span>
        </div>
      </div>
    </section>
  </div>
</div>

<script>
  const ctx = document.getElementById('finChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: @json($months),
      datasets: [
        {
          label: 'Costo',
          data: @json($seriesCost),
          borderColor: '#a3591a',
          tension: 0.3,
          pointStyle: 'circle',
          pointRadius: 5,
          fill: false
        },
        {
          label: 'Generación',
          data: @json($seriesGen),
          borderColor: '#a3591a',
          borderDash: [5,5],
          tension: 0.3,
          pointStyle: 'rect',
          pointRadius: 5,
          fill: false
        }
      ]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        x: { 
          grid: { display: false },
          ticks: { color: '#7f4b1a' }
        },
        y: {
          beginAtZero: false,
          grid: { color: 'rgba(127,75,26,0.1)' },
          ticks: { color: '#7f4b1a', callback: v => '$' + v }
        }
      },
      responsive: true,
      maintainAspectRatio: false
    }
  });
</script>
@endsection