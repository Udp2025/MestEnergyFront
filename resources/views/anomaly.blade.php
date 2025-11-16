{{-- resources/views/anomaly.blade.php --}}
@extends('layouts.complete')
@section('title','Detección de anomalías')
@push('head')
  <script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
@endpush

@vite([
  'resources/js/pages/anomaly.js',
  'resources/css/pages/anomaly.css',
  'resources/css/plot/common.css'
])

@section('content')
@php
    use Carbon\Carbon;

    $canViewAllSites = $authContext['abilities']['canViewAllSites'] ?? false;
    $todayDate  = Carbon::today();
    $today      = $todayDate->format('Y-m-d');
    $lastMonth  = $todayDate->copy()->subMonth()->format('Y-m-d');
@endphp
<div class="plot-page">
  <h1 class="plot-page__title">Detección de anomalías</h1>

  <section class="plot-card">
    <form id="plot-filters" class="plot-filters">
      @csrf
      <div class="plot-notice" data-notice role="alert"></div>

      {{-- SITE (only for admins) -------------------------------------------- --}}
      @if ($canViewAllSites)
      <label>Sitio:
        <select id="site" name="site" required></select>
      </label>
      @endif

      {{-- DEVICE ------------------------------------------------------------- --}}
      <label>Dispositivo:
        <select id="device" name="device" required></select>
      </label>

      {{-- Metric ------------------------------------------------------------- --}}
      <label>
        Métrica:
        <select name="metric" id="metric">
          <option value="power_w" selected>Potencia</option>
          <option value="energy_wh">Energía</option>
          <option value="current_a">Corriente</option>
          <option value="voltage_v">Voltaje</option>
          <option value="power_factor">Factor Potencia</option>
        </select>
      </label>

      {{-- Dates (start fixed) ------------------------------------------------ --}}
      <input type="hidden" name="from" id="from" value="{{ $lastMonth }}">
      <input type="hidden" name="to" id="to" value="{{ $today }}">

      {{-- Check window & frequency ------------------------------------------- --}}
      <label>
        Ventana a evaluar:
        <input type="number" name="check_last" id="check_last" value="24" min="1" step="1">
      </label>
      <label>
        Frecuencia:
        <select name="frequency" id="frequency">
          <option value="H" selected>Hora</option>
          <option value="D">Día</option>
          <option value="W">Semana</option>
        </select>
      </label>

      <button
        type="button"
        class="plot-button plot-button--ghost advanced-toggle"
        data-advanced-toggle
        data-close-label="Ocultar filtros avanzados"
        aria-expanded="false"
      >
        Filtros Avanzados
      </button>

      <div class="advanced-filters" data-advanced-container>
        <label>
          % votos modelo:
          <input type="number" name="pct_vote" id="pct_vote" value="0.25" min="0" max="1" step="0.05">
        </label>
        <label>
          Umbral anomalía:
          <input type="number" name="threshold" id="threshold" value="0.5" min="0" max="1" step="0.05">
        </label>
        <label>
          Agregación (up-sampling):
          <select name="agg" id="agg">
            <option value="sum" selected>Suma</option>
            <option value="avg">Promedio</option>
          </select>
        </label>
      </div>

      <button id="run" type="submit" class="plot-button">
        <span>Aplicar</span>
      </button>
    </form>

    <div class="chart-container">
      <div id="anomalyChart" class="plot-chart" style="max-height:640px"></div>
      <div id="anomalyMeta" class="anomaly-meta" role="status" aria-live="polite" hidden>
        <div class="anomaly-meta__item">
          <span>Anomalías detectadas</span>
          <strong data-count>–</strong>
        </div>
        <div class="anomaly-meta__item">
          <span>Periodo evaluado</span>
          <strong data-window>–</strong>
        </div>
        <div class="anomaly-meta__item">
          <span>Resultado</span>
          <strong data-status>–</strong>
        </div>
      </div>
      <p class="plot-hint">Filtros avanzados: ajusta el % de votos requerido (consenso de modelos), el umbral que marca el periodo como anómalo y la agregación (sum/promedio) usada al escalar la serie.</p>
    </div>
  </section>
</div>
@endsection
