{{-- resources/views/timeseries_plot.blade.php --}}
@extends('layouts.complete')
@section('title','Serie temporal')
@push('head')
  <script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
@endpush

@vite([
    'resources/js/pages/timeseries.js',
    'resources/css/pages/timeseries.css',
    'resources/css/plot/common.css'
])

@section('content')
@php
    use Carbon\Carbon;

    $canViewAllSites = $authContext['abilities']['canViewAllSites'] ?? false;
    $todayDate  = Carbon::today();
    $today      = $todayDate->format('Y-m-d');
    $yesterday  = $todayDate->copy()->subDay()->format('Y-m-d');
@endphp
<div class="plot-page">
  <h1 class="plot-page__title">Serie temporal</h1>

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

    {{-- === Metric ========================================================= --}}
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

    {{-- === Date range ==================================================== --}}
    <div class="date-range">
      <label>
        Desde:
        <input
          type="date"
          name="from"
          id="from"
          value="{{ $yesterday }}"
          max="{{ $today }}"
          required
        >
      </label>
      <label>
        Hasta:
        <input
          type="date"
          name="to"
          id="to"
          value="{{ $today }}"
          max="{{ $today }}"
          required
        >
      </label>
    </div>

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
      <label>Frecuencia:
        <select name="period" id="period">
            <option value="H">Hora</option>
            <option value="2H">2h</option>
            <option value="4H">4h</option>
            <option value="6H">6h</option>
            <option value="12H">12h</option>
            <option value="D" selected>Día</option>
            <option value="BD">Día laboral</option>
            <option value="W">Semana</option>
            <option value="BW">Quincena</option>
            <option value="M">Mes</option>
            <option value="Q">Trimestre</option>
            <option value="S">Semestre</option>
            <option value="Y">Año</option>
        </select>
      </label>

      <label>Función:
        <select name="agg" id="agg">
            <option value="avg" selected>Promedio</option>
            <option value="sum">Suma</option>
            <option value="min">Mín</option>
            <option value="max">Máx</option>
            <option value="std">Desv. Estándar</option>
        </select>
      </label>
    </div>

  <button id="run" type="submit" class="plot-button"><span>Aplicar</span></button>

    </form>

    <div class="chart-container">
        <div id="lineChart" class="plot-chart" style="max-height:640px"></div>
        <p class="plot-hint">Filtros avanzados: ajusta la frecuencia de muestreo y la función de agregación aplicada a la métrica seleccionada.</p>
    </div>
  </section>
</div>
@endsection
