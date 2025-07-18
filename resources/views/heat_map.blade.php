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

@php($today = \Carbon\Carbon::today()->format('Y-m-d'))

@section('content')
<form id="plot-filters" class="plot-filters">
  @csrf

  {{-- X-axis -----------------------------------------------------------}}
  <label>X:
    <select name="x" id="x">
      <option value="hour">Hora</option>
      <option value="weekday">Día Semana</option>
      <option value="day">Día Mes</option>
      <option value="month">Mes</option>
    </select>
  </label>

  {{-- Y-axis -----------------------------------------------------------}}
  <label>Y:
    <select name="y" id="y">
      <option value="weekday">Día Semana</option>
      <option value="hour">Hora</option>
      <option value="day">Día Mes</option>
      <option value="month">Mes</option>
    </select>
  </label>

  {{-- Z metric ---------------------------------------------------------}}
  <label>Métrica:
    <select name="z" id="z">
      <option value="power_w">Potencia</option>
      <option value="energy_wh">Energía</option>
      <option value="current_a">Corriente</option>
      <option value="voltage_v">Voltaje</option>
    </select>
  </label>

  {{-- Date range (auto-restricted) ------------------------------------}}
  <label>Desde:
    <input type="date" name="from" id="from" value="{{ $today }}">
  </label>
  <label>Hasta:
    <input type="date" name="to" id="to" value="{{ $today }}">
  </label>

  {{-- Submit -----------------------------------------------------------}}
  <button id="run" type="submit" class="plot-button" disabled>
    <i class="fas fa-sync-alt"></i><span class="sr-only">Actualizar</span>
  </button>
</form>

<div class="chart-container"><div id="heatChart"></div></div>
@endsection
