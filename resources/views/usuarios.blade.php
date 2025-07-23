@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<link rel="stylesheet" href="{{ asset('css/usuarios.css') }}">

<script src="https://cdn.plot.ly/plotly-2.32.0.min.js" defer></script>
<script src="{{ asset('js/timeseries.js') }}" defer></script>



<div class="container">
    <div class="main-content">
        <h2>Energy Dashboard</h2>
        <div class="filters">
            <select id="dataType">
                <option value="power">Power</option>
                <option value="cost">Cost</option>
            </select>
            <select id="timeRange">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
        </div>
            <div class="chart-container">
                <div id="energyChart" style="width:100%;height:420px"></div>
            </div>
    </div>

    <div class="sidebar">
        <h3>Group By: <span>Measurement Type</span></h3>
        <ul>
            <li class="expandable">Electric Sensors
                <ul>
                    <li><input type="checkbox" class="sensor" value="Mains"> Mains</li>
                    <li><input type="checkbox" class="sensor" value="Generation"> Generation</li>
                    <li><input type="checkbox" class="sensor" value="EV Charging"> EV Charging</li>
                </ul>
            </li>
            <li class="expandable">Gas
                <ul>
                    <li><input type="checkbox" class="sensor" value="Gas Usage"> Gas Usage</li>
                    <li><input type="checkbox" class="sensor" value="Pipeline"> Pipeline</li>
                </ul>
            </li>
        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/usuarios.js') }}"></script>


@endsection
