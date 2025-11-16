{{-- resources/views/forecast.blade.php --}}
@extends('layouts.complete')
@section('title','Pronóstico de Consumo')
@push('head')
  <script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
@endpush

@vite([
  'resources/js/pages/forecast.js',
  'resources/css/pages/forecast.css',
  'resources/css/plot/common.css'
])

@section('content')
@php
    use Carbon\Carbon;

    $canViewAllSites = $authContext['abilities']['canViewAllSites'] ?? false;
    $todayDate  = Carbon::today();
    $today      = $todayDate->format('Y-m-d');
    $historyFrom = $todayDate->copy()->subDays(180)->format('Y-m-d');
@endphp
<div class="plot-page">
  <h1 class="plot-page__title">Pronóstico de Consumo</h1>

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

      {{-- === Date range ==================================================== --}}
      <label>Histórico desde:
        <input type="date" name="from" id="from" value="{{ $historyFrom }}">
      </label>
      <label>Hasta:
        <input type="date" name="to" id="to" value="{{ $today }}">
      </label>

      {{-- Horizon ----------------------------------------------------------- --}}
      <label>
        Horizonte (periodos):
        <input type="number" name="horizon" id="horizon" value="7" min="1" max="90">
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
          Frecuencia:
          <select name="frequency" id="frequency">
            <option value="D" selected>Día</option>
            <option value="H">Hora</option>
            <option value="W">Semana</option>
            <option value="M">Mes</option>
            <option value="Q">Trimestre</option>
            <option value="Y">Año</option>
          </select>
        </label>

        <label class="checkbox-inline">
          <input type="checkbox" name="include_conf_int" id="include_conf_int" checked>
          Incluir intervalo de confianza
        </label>
      </div>

      <button id="run" type="submit" class="plot-button">
        <span>Aplicar</span>
      </button>
    </form>

    <div class="chart-container">
      <div id="forecastChart" class="plot-chart" style="max-height:640px"></div>
      <div id="forecastMeta" class="forecast-meta" role="status" aria-live="polite" hidden>
        <div class="forecast-meta__item">
          <span>Modelo</span>
          <strong data-model></strong>
        </div>
        <div class="forecast-meta__item">
          <span>Horizonte</span>
          <strong data-horizon></strong>
        </div>
        <div class="forecast-meta__item">
          <span>Tiempo servidor</span>
          <strong data-runtime></strong>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
