{{-- resources/views/benchmark.blade.php --}}
@extends('layouts.app')

@section('title','Benchmark')
@section('content')
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<script>
  window.APP_CONF = {
    API_BASE : "{{ env('PLOT_API_BASE') }}",   
    API_KEY  : "{{ env('PLOT_API_KEY') }}"     
};
</script>

<script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
<script src="{{ asset('js/benchmark.js') }}" defer></script>


<link rel="stylesheet" href="{{ asset('css/benchmark.css') }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Benchmarking</title>
    <link rel="stylesheet" href="{{ asset('css/benchmark.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
 @php
    // Datos “hardcodeados”
    $mainSite = (object)['name' => 'LAPROBA EL ÁGUILA SA DE CV'];
    $subSites = [
      'Accesorios De Prensa Favole',
      'Extrusora Prensa Doble',
      'Oficinas',
      'Oficinas Nuevas',
      'Taller Mantemien',
      'Transformador Seco 1127V',
    ];
    $periodStart = \Carbon\Carbon::now()->startOfWeek();
    $periodEnd   = \Carbon\Carbon::now()->endOfWeek();
    $chartData = [
      ['label'=>'Accesorios De Prensa Favole','value'=>0.019,'color'=>'#8fc5fe'],
      ['label'=>'Extrusora Prensa Doble','value'=>510,'color'=>'#a3591a'],
      ['label'=>'Oficinas','value'=>148,'color'=>'#f0ad4e'],
      ['label'=>'Oficinas Nuevas','value'=>215,'color'=>'#7f4b1a'],
      ['label'=>'Taller Mantemien','value'=>257,'color'=>'#843b1a'],
      ['label'=>'Transformador Seco 1127V','value'=>577,'color'=>'#5ea6ad'],
    ];
@endphp

<div class="container">
  {{-- Toolbar superior --}}
  <div class="re-toolbar">
  <div class="re-filters">
    @foreach(['Device','Energy','None','None','None','Weekly'] as $i => $opt)
      @php
        $labels = ['Benchmark By','Show By','Group By','Normalize by','Show Line','Period'];
      @endphp
      <div class="re-filter">
        <label>{{ $labels[$i] }}</label>
        <select><option>{{ $opt }}</option></select>
      </div>
    @endforeach
  </div>

        <div class="main-content">
            <h1>Consumo de Energía Semanal</h1>
            <div class="filters">
                <div>
                    <label for="filter-type">Filtrar por:</label>
                    <select id="filter-type">
                        <option value="energia">Energía</option>
                        <option value="costo">Costo</option>
                    </select>
                </div>
                <div>
                    <label for="filter-date">Fecha:</label>
                    <input type="date" id="filter-date">
                </div>
                <div>
                    <label for="normalize-by">Normalize by:</label>
                    <input type="text" id="normalize-by" placeholder="Ej. kWh/m²">
                </div>
                <div>
                    <input type="checkbox" id="show-line">
                    <label for="show-line">Show Line</label>
                </div>
                <div>
                    <label for="period">Periodo:</label>
                    <select id="period">
                        <option value="dias">Días</option>
                        <option value="semanas">Semanas</option>
                        <option value="meses">Meses</option>
                        <option value="años">Años</option>
                    </select>
                </div>
            </div>
            <div class="chart-container">
                <div id="energyChart" style="width:100%;height:420px"></div>
            </div>
        </div>
    </div>
</body>

</html>

 


@endsection