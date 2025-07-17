{{-- resources/views/benchmark.blade.php --}}
@extends('layouts.app')
@section('title','Benchmark')

@push('head')
  <script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
@endpush

@vite(['resources/js/pages/benchmark.js','resources/css/pages/benchmark.css'])

@section('content')
@php
  $today = \Carbon\Carbon::today()->format('Y-m-d');
@endphp

<form id="plot-filters" class="filters">
  @csrf

  {{-- Metric -----------------------------------------------------------}}
  <label>
    Métrica
    <select name="metric" id="metric" >
      <option value="power_w" selected>Potencia</option>
      <option value="energy_wh">Energía</option>
    </select>
  </label>

  {{-- Dates ------------------------------------------------------------}}
  <label>
    Desde
    <input type="date" name="from" id="from" value="{{ $today }}">
  </label>
  <label>
    Hasta
    <input type="date" name="to"   id="to"   value="{{ $today }}">
  </label>

  {{-- Aggregation ------------------------------------------------------}}
  <label>
    Frecuencia
    <select name="period" id="period">
      <option value="H"  selected>Hora</option>
      <option value="D">Día</option>
      <option value="W">Semana</option>
    </select>
  </label>

  <label>
    Función
    <select name="agg" id="agg">
      <option value="avg" selected>Avg</option>
      <option value="sum">Sum</option>
      <option value="min">Min</option>
      <option value="max">Max</option>
      <option value="count">Count</option>
    </select>
  </label>

  <button type="submit">Actualizar</button>
</form>

<div id="energyChart" style="width:100%;height:420px"></div>
@endsection
