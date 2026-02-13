@extends('layouts.complete')

@section('title', 'Groups')

@section('content')
  <link rel="stylesheet" href="{{ asset('css/groups.css') }}">

  <header class="header">
    <div class="header-left">
      <h1>Medidas de Energía</h1>
      <p class="card-type">Periodo: {{ $periodLabel }}</p>
    </div>
    <div class="header-right">
      <form method="GET" action="{{ route('groups') }}">
        <label>
          Sitio:
          <select name="site_id" onchange="this.form.submit()" {{ $canViewAllSites ? '' : 'disabled' }}>
            @foreach($sites as $site)
              <option value="{{ $site->site_id }}" {{ (int) $site->site_id === (int) $selectedSiteId ? 'selected' : '' }}>
                {{ $site->site_name ?? ('Sitio ' . $site->site_id) }}
              </option>
            @endforeach
          </select>
        </label>
      </form>
    </div>
  </header>

  <section class="card-container" id="cardContainer">
    <div class="card active">
      <div class="card-left">
        <i class="icon fas fa-bolt"></i>
        <div class="card-info">
          <h2>Consumo Eléctrico</h2>
          <p class="card-type">Medida</p>
          <div class="card-description">
            {{ number_format($metrics['consumption_kwh'] ?? 0, 2, '.', ',') }} kWh
          </div>
        </div>
      </div>
    </div>

    <div class="card active">
      <div class="card-left">
        <i class="icon fas fa-sun"></i>
        <div class="card-info">
          <h2>Generación Energía</h2>
          <p class="card-type">Generación</p>
          <div class="card-description">
            {{ number_format($metrics['generation_kwh'] ?? 0, 2, '.', ',') }} kWh
          </div>
        </div>
      </div>
    </div>

    <div class="card active">
      <div class="card-left">
        <i class="icon fas fa-chart-line"></i>
        <div class="card-info">
          <h2>Medición de Voltaje</h2>
          <p class="card-type">Medida</p>
          <div class="card-description">
            {{ number_format($metrics['voltage_avg'] ?? 0, 2, '.', ',') }} V
          </div>
        </div>
      </div>
    </div>

    <a class="card active" href="{{ route('reports') }}" style="text-decoration:none; color:inherit;">
      <div class="card-left">
        <i class="icon fas fa-chart-pie"></i>
        <div class="card-info">
          <h2>Análisis de Consumo</h2>
          <p class="card-type">Análisis</p>
          <div class="card-description">Ver Reportes automáticos</div>
        </div>
      </div>
    </a>
  </section>
@endsection
