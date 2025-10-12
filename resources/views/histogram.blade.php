{{-- resources/views/histogram.blade.php --}}
@extends('layouts.app')
@section('title', 'histogram')
@push('head')
  <script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
@endpush

@vite([
    'resources/js/pages/histogram.js',       
    'resources/css/pages/histogram.css',
    'resources/css/plot/common.css'
])

@section('content')
@php
    use Carbon\Carbon;

    $canViewAllSites = $authContext['abilities']['canViewAllSites'] ?? false;
    $todayDate  = Carbon::today();
    $today      = $todayDate->format('Y-m-d');
    $lastWeek   = $todayDate->copy()->subWeek()->format('Y-m-d');
@endphp


<form id="plot-filters" class="plot-filters">
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

    {{-- === X-axis metric ================================================= --}}
    <label>
        Métrica X:
        <select name="metric1" id="metric1">
            <option value="current_a" selected>Corriente</option>
            <option value="voltage_v">Voltaje</option>
            <option value="power_w">Potencia</option>
            <option value="energy_wh">Energía</option>
            <option value="power_factor">Factor Potencia</option>
        </select>
    </label>

    {{-- === Date range ==================================================== --}}
    <label>Desde:
        <input type="date" name="from" id="from" value="{{ $lastWeek }}">
    </label>
    <label>Hasta:
        <input type="date" name="to"   id="to"   value="{{ $today }}">
    </label>

    {{-- === Resampling window ============================================ --}}
    <label>Frecuencia:
        <select name="freq" id="freq">
            <option value="5min" selected>5min</option>
            <option value="H">Hora</option>
            <option value="2H">2h</option>
            <option value="4H">4h</option>
            <option value="6H">6h</option>
            <option value="12H">12h</option>
            <option value="D">Día</option>
            <option value="BD">Día laboral</option>
            <option value="W">Semana</option>
            <option value="BW">Quincena</option>
            <option value="M">Mes</option>
            <option value="Q">Trimestre</option>
            <option value="S">Semestre</option>
            <option value="Y">Año</option>
        </select>
    </label>

    {{-- === Aggregations ================================================== --}}
    <label>Función X:
        <select name="agg1" id="agg1" disabled>
            <option value="original" selected>Original</option>
            <option value="avg">Promedio</option>
            <option value="sum">Suma</option>
            <option value="min">Mín</option>
            <option value="max">Máx</option>
            <option value="std">Desv. Estándar</option>
        </select>
    </label>

     {{-- BINS (optional) ------------------------------------------------ --}}
  <label>Bins máx:
    <input type="number" id="bins" name="bins" min="3" placeholder="auto">
  </label>


  <button id="run" type="submit" class="plot-button"><span>Aplicar</span></button>

</form>

<div class="chart-container">
    <div id="histogramChart" class="plot-chart" style="max-height:600px"></div>
</div>
@endsection
