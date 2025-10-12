{{-- resources/views/vista_general.blade.php --}}
@extends('layouts.complete')

@section('title', 'Vista General')

@push('head')
  <script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
@endpush

@vite(['resources/js/pages/timeseries.js', 'resources/css/pages/timeseries.css', 'resources/css/plot/common.css'])

@section('content')
@php
  $today = \Carbon\Carbon::today()->format('Y-m-d');
@endphp

<div class="plot-page">
  <h1 class="plot-page__title">Vista General</h1>

  <section class="plot-card">
    <form id="plot-filters" class="plot-filters">
      @csrf
      <div class="plot-notice" data-notice role="alert"></div>

      @if(($authContext['abilities']['canViewAllSites'] ?? false) === true)
      <label>
        Sitio:
        <select id="site" name="site"></select>
      </label>
      @endif

      <label>
        Dispositivo:
        <select id="device" name="device"></select>
      </label>

      {{-- Metric -----------------------------------------------------------}}
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

      {{-- Dates ------------------------------------------------------------}}
      <label>
        Desde:
        <input type="date" name="from" id="from" value="{{ $today }}">
      </label>
      <label>
        Hasta:
        <input type="date" name="to" id="to" value="{{ $today }}">
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
        {{-- Aggregation ------------------------------------------------------}}
        <label>
          Frecuencia:
          <select name="period" id="period">
            <option value="H" selected>Hora</option>
            <option value="2H">2H</option>
            <option value="4H">4H</option>
            <option value="6H">6H</option>
            <option value="12H">12H</option>
            <option value="D">Día</option>
            <option value="BD">Business Days</option>
            <option value="W">Semana</option>
            <option value="BW">Quincena</option>
            <option value="M">Mes</option>
            <option value="Q">Trimestre</option>
            <option value="S">Semestre</option>
            <option value="Y">Año</option>
          </select>
        </label>

        <label>
          Función:
          <select name="agg" id="agg">
            <option value="avg" selected>Promedio</option>
            <option value="sum">Suma</option>
            <option value="min">Mín</option>
            <option value="max">Máx</option>
            <option value="count">Conteo</option>
            <option value="distinct">Conteo de distintos</option>
            <option value="std">Desviación Estándar</option>
            <option value="mode">Moda</option>
            <option value="cumsum">Suma Acumulada</option>
          </select>
        </label>
      </div>

      <button id="run" type="submit" class="plot-button">
        <span>Aplicar</span>
      </button>
    </form>

    <div class="chart-container">
      <div id="lineChart" style="max-height:560px"></div>
    </div>
  </section>
</div>
@endsection
