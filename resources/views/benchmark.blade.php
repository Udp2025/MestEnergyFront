{{-- resources/views/benchmark.blade.php --}}

@extends('layouts.app')
@section('title','Benchmark')

@push('head')
  <script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
@endpush

@vite(['resources/js/pages/benchmark.js','resources/css/pages/benchmark.css', 'resources/css/plot/common.css'])

@section('content')
@php
  $today = \Carbon\Carbon::today()->format('Y-m-d');
@endphp

<form id="plot-filters" class="plot-filters">
  @csrf
  <head>
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  integrity="sha512-pY1u+Vx..." crossorigin="anonymous" referrerpolicy="no-referrer"
/>

  </head>

  {{-- Metric -----------------------------------------------------------}}
  <label>
    Métrica:
    <select name="metric" id="metric" >
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
    <input type="date" name="to"   id="to"   value="{{ $today }}">
  </label>

  {{-- Aggregation ------------------------------------------------------}}
  <label>
    Frecuencia:
    <select name="period" id="period">
      <option value="H"  selected>Hora</option>
      <option value="2H">2H</option>
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

<button type="submit" class="plot-button">
  <i class="fas fa-sync-alt" aria-hidden="true"></i>
  <span class="sr-only">Actualizar</span>
</button>

</form>

<div class="chart-container">
   <div id="energyChart" style="max-height:500px"></div>
</div>

@endsection
