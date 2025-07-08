@extends('layouts.app')

@section('title', 'Optimize')

@section('content')
<link rel="stylesheet" href="{{ asset('css/optimize.css') }}">

<div class="dashboard-container">
  <div class="dashboard-header">
    <h1 class="dashboard-title">Optimize Dashboard</h1>
    <button class="btn-report">Generar Reporte</button>
  </div>

  <!-- Tarjetas de resumen -->
  <div class="summary-cards">
    <div class="summary-card">
      <h2 class="card-title">Ahorro Energético</h2>
      <p class="card-text">Ahorro hoy: <strong>30 kWh</strong></p>
    </div>
    <div class="summary-card">
      <h2 class="card-title">Reducción de Costos</h2>
      <p class="card-text">Ahorro mensual: <strong>$150</strong></p>
    </div>
    <div class="summary-card">
      <h2 class="card-title">Índice de Eficiencia</h2>
      <p class="card-text">Actual: <strong>95%</strong></p>
    </div>
  </div>

  <!-- Gráficos principales -->
  <div class="main-charts">
    <div class="chart-card">
      <div class="chart-title">Optimización de Energía (Diario)</div>
      <canvas id="dailyOptimizationChart"></canvas>
    </div>
    <div class="chart-card">
      <div class="chart-title">Comparación de Ahorro</div>
      <canvas id="savingsComparisonChart"></canvas>
    </div>
  </div>

  <!-- Gráficos secundarios -->
  <div class="secondary-charts">
    <div class="secondary-chart-card">
      <div class="chart-title">Historial Semanal de Ahorro</div>
      <canvas id="weeklySavingsChart"></canvas>
    </div>
    <div class="secondary-chart-card">
      <div class="chart-title">Optimización por Dispositivo</div>
      <canvas id="deviceOptimizationChart"></canvas>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/optimize.js') }}"></script>
@endsection
