{{-- resources/views/scatter.blade.php --}}
@extends('layouts.app')
@section('title', 'Scatter')
@push('head')
  <script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
@endpush

@vite([
    'resources/js/pages/scatter.js',       
    'resources/css/pages/scatter.css',
    'resources/css/plot/common.css'
])

@section('content')
@php
    use Carbon\Carbon;

    $isAdmin    = false; // auth()->user()->hasRole('admin');
    $siteId     = session('currentSiteId', 186431);
    $todayDate  = Carbon::today();
    $today      = $todayDate->format('Y-m-d');
    $lastWeek   = $todayDate->copy()->subWeek()->format('Y-m-d');
@endphp

<script>
  window.currentUserIsAdmin = @json($isAdmin);
  window.currentSiteId      = @json($siteId);
</script>


<form id="plot-filters" class="plot-filters">
    {{-- SITE (only for admins) -------------------------------------------- --}}
    @if ($isAdmin)
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

    {{-- === Y-axis metric ================================================= --}}
    <label>
        Métrica Y:
        <select name="metric2" id="metric2">
            <option value="voltage_v" selected>Voltaje</option>
            <option value="current_a">Corriente</option>
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

    <label>Función Y:
        <select name="agg2" id="agg2" disabled>
            {{-- same list as agg1; keep or prune as needed --}}
            <option value="original" selected>Original</option>
            <option value="avg">Promedio</option>
            <option value="sum">Suma</option>
            <option value="min">Mín</option>
            <option value="max">Máx</option>
            <option value="std">Desv. Estándar</option>
        </select>
    </label>

    <label>Color&nbsp;por:
        <select id="color_by" name="color_by">
            <option value="device_id" selected>Dispositivo</option>
            <option value="hour">Hora</option>
            <option value="weekday">Día de la semana</option>
        </select>
    </label>


  <button id="run" type="submit" class="plot-button"><span>Aplicar</span></button>

</form>

<div class="chart-container">
    <div id="scatterChart" class="plot-chart" style="max-height:600px"></div>
</div>
@endsection
