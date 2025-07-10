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
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Sensores de Luz</h2>
            <ul id="sensor-list">
                <li>
                    Sensor de Luz A
                    <ul class="submenu">
                        <li><input type="checkbox" value="sensor1-op1"> Opción 1</li>
                        <li><input type="checkbox" value="sensor1-op2"> Opción 2</li>
                    </ul>
                </li>
                <li>
                    Sensor de Luz B
                    <ul class="submenu">
                        <li><input type="checkbox" value="sensor2-op1"> Opción 1</li>
                        <li><input type="checkbox" value="sensor2-op2"> Opción 2</li>
                    </ul>
                </li>
                <li>
                    Sensor de Luz C
                    <ul class="submenu">
                        <li><input type="checkbox" value="sensor3-op1"> Opción 1</li>
                        <li><input type="checkbox" value="sensor3-op2"> Opción 2</li>
                    </ul>
                </li>
            </ul>
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