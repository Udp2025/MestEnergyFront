{{-- resources/views/heat_map.blade.php --}}
@extends('layouts.app')
@section('title','Heat Map')
@push('head')
  <script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
@endpush

@vite([
  'resources/css/plot/common.css',
  'resources/css/pages/heat_map.css',
  'resources/js/pages/heat_map.js'
])

@section('content')
@php
    $isAdmin = FALSE; // auth()->user()->hasRole('admin');   // or any gate/check you use
    $siteId = 186431;// session('currentSiteId');           // populated by middleware
@endphp

<script>
  window.currentUserIsAdmin = @json($isAdmin);
  window.currentSiteId      = @json($siteId);
</script>


<form id="plot-filters" class="plot-filters" aria-label="Heat-map filters">
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

  {{-- AXES ---------------------------------------------------------------- --}}
  <label>X:
    <select name="x" id="x" class="axisSelect">
      @foreach (['hour'=>'Hora','weekday'=>'Día Semana','day'=>'Día Mes','week'=>'Semana','month'=>'Mes','year'=>'Año'] as $k=>$v)
        <option value="{{ $k }}" {{ $k==='hour' ? 'selected' : '' }}>{{ $v }}</option>
      @endforeach
    </select>
  </label>

  <label>Y:
    <select name="y" id="y" class="axisSelect">
      @foreach (['hour'=>'Hora','weekday'=>'Día Semana','day'=>'Día Mes','week'=>'Semana','month'=>'Mes','year'=>'Año'] as $k=>$v)
        <option value="{{ $k }}" {{ $k==='weekday' ? 'selected' : '' }}>{{ $v }}</option>
      @endforeach
    </select>
  </label>

  {{-- Metric & Function -------------------------------------------------- --}}
  <label>Métrica:
    <select name="z" id="z">
      @foreach (['power_w'=>'Potencia','energy_wh'=>'Energía','current_a'=>'Corriente','voltage_v'=>'Voltaje','power_factor'=>'Factor Potencia'] as $k=>$v)
        <option value="{{ $k }}">{{ $v }}</option>
      @endforeach
    </select>
  </label>

  <label>Función:
    <select name="agg" id="agg">
      @foreach (['avg'=>'Promedio','sum'=>'Suma','min'=>'Mín','max'=>'Máx','count'=>'Conteo','std'=>'Std','median'=>'Mediana','mode'=>'Moda','distinct'=>'Distintos'] as $k=>$v)
        <option value="{{ $k }}" {{ $k==='avg' ? 'selected' : '' }}>{{ $v }}</option>
      @endforeach
    </select>
  </label>

  {{-- Period navigation -------------------------------------------------- --}}
  <div class="period-nav">
    <button type="button" id="prev" class="plot-button" aria-label="Periodo anterior">‹</button>
    <span   id="periodLabel" role="status" aria-live="polite"></span>
    <button type="button" id="next" class="plot-button" aria-label="Periodo siguiente">›</button>
  </div>

  <button id="run" type="submit" class="plot-button"><span>Aplicar</span></button>
</form>

<div class="chart-container">
  <div id="heatChart" style="max-height:600px"></div>
</div>
@endsection
