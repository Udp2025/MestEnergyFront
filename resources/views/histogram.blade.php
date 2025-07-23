@extends('layouts.app')
@section('title','Histograma')
@push('head')
  <script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
@endpush

@vite([
  'resources/css/plot/common.css',
  'resources/css/pages/histogram.css',        {{-- optional, mirrors heat_map.css --}}
  'resources/js/pages/histogram.js'
])

@section('content')
@php
    $isAdmin = FALSE;                 // auth()->user()->hasRole('admin')
    $siteId  = 186431;                // session('currentSiteId')
@endphp

<script>
  window.currentUserIsAdmin = @json($isAdmin);
  window.currentSiteId      = @json($siteId);
</script>

<form id="plot-filters" class="plot-filters" aria-label="Histogram filters">
  {{-- SITE (only for admins) ---------------------------------------- --}}
  @if ($isAdmin)
    <label>Sitio:
      <select id="site" name="site" required></select>
    </label>
  @endif

  {{-- DEVICE --------------------------------------------------------- --}}
  <label>Dispositivo:
    <select id="device" name="device" required></select>
  </label>

  {{-- METRIC & FUNCTION --------------------------------------------- --}}
  <label>Métrica:
    <select name="metric" id="metric">
      @foreach (['power_w'=>'Potencia','energy_wh'=>'Energía','current_a'=>'Corriente',
                 'voltage_v'=>'Voltaje','power_factor'=>'Factor Potencia'] as $k=>$v)
        <option value="{{ $k }}">{{ $v }}</option>
      @endforeach
    </select>
  </label>

  <label>Función:
    <select name="agg" id="agg">
      @foreach (['avg'=>'Promedio','sum'=>'Suma','min'=>'Mín','max'=>'Máx','count'=>'Conteo',
                 'std'=>'Std','median'=>'Mediana','mode'=>'Moda','distinct'=>'Distintos'] as $k=>$v)
        <option value="{{ $k }}" {{ $k==='avg' ? 'selected' : '' }}>{{ $v }}</option>

      @endforeach
         <option value="raw" >Raw (sin función)</option>
    </select>
  </label>

  {{-- DATE RANGE ----------------------------------------------------- --}}
  <label>Desde:
    <input type="date" id="from" name="from" required>
  </label>
  <label>Hasta:
    <input type="date" id="to" name="to" required>
  </label>

  {{-- BINS (optional) ------------------------------------------------ --}}
  <label>Bins máx:
    <input type="number" id="bins" name="bins" min="1" placeholder="auto">
  </label>

  <button id="run" type="submit" class="plot-button"><span>Aplicar</span></button>
</form>

<div class="chart-container">
  <div id="histChart" style="max-height:600px"></div>
</div>
@endsection
