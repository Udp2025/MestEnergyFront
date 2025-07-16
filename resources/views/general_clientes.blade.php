{{-- resources/views/vista_general.blade.php --}}
@extends('layouts.complete')

@section('title', 'Vista General')

@section('content')
<link rel="stylesheet" href="{{ asset('css/general_cliente.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="vg-container">
  <h1 class="vg-title">Vista General</h1>

  <div class="vg-cards">
    <div class="vg-card">
      <div class="vg-card-icon"><i class="fas fa-globe"></i></div>
      <div>
        <div class="vg-card-label">Sitios</div>
        <div class="vg-card-value">2 Sitios</div>
      </div>
    </div>
    <div class="vg-card">
      <div class="vg-card-icon"><i class="fas fa-list"></i></div>
      <div>
        <div class="vg-card-label">Loggers</div>
        <div class="vg-card-value">0 Offline</div>
      </div>
    </div>
    <div class="vg-card">
      <div class="vg-card-icon"><i class="fas fa-microchip"></i></div>
      <div>
        <div class="vg-card-label">268 Sensores</div>
        <div class="vg-card-sub">+12 Medidores de Energía</div>
      </div>
    </div>
    <div class="vg-card vg-card-highlight">
      <div class="vg-card-icon"><i class="fas fa-database"></i></div>
      <div>
        <div class="vg-card-label">262</div>
        <div class="vg-card-sub">Registradores de Consumo</div>
      </div>
    </div>
    <div class="vg-card">
      <div class="vg-card-icon"><i class="fas fa-bolt"></i></div>
      <div>
        <div class="vg-card-label">40</div>
        <div class="vg-card-sub">Puentes</div>
      </div>
    </div>
  </div>

  <div class="vg-chart-card">
    <canvas id="consumoChart"></canvas>
    <div class="vg-tooltip">
      <div class="vg-tooltip-value">32 kW</div>
      <div class="vg-tooltip-label">Consumo Actual</div>
      <div class="vg-tooltip-line"></div>
    </div>
  </div>
</div>

<script>
  const ctx = document.getElementById('consumoChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['12 a.m.', '2 a.m.', '4 a.m.', '6 a.m.', '8 a.m.', '10 a.m.', '12 p.m.'],
      datasets: [{
        data: [15, 22, 28, 24, 18, 30, 27],
        fill: 'start',
        tension: 0.4,
        borderWidth: 0,
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#a37b53' }
        },
        y: {
          min: 0, max: 60, ticks: { stepSize: 20, color: '#a37b53' },
          grid: { color: 'rgba(163,123,83,0.1)' }
        }
      },
      elements: {
        point: { radius: 0 }
      },
      responsive: true,
      maintainAspectRatio: false
    }
  });
</script>
@endsection
