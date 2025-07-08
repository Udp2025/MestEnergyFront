@extends('layouts.app')

@section('title', 'Heatmap')

@section('content')
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Heat Map con Filtros y Árbol de Opciones</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix"></script>
  <link rel="stylesheet" href="{{ asset('css/heat_map.css') }}">
</head>
<body>
<div id="heatmap-widget">
  <!-- Sidebar: Árbol de Sensores y Opciones Avanzadas -->
  <div class="sidebar">
    <ul id="sensorTree" class="tree-list">
      <li class="tree-node" data-value="mains">
        <span class="toggle-icon"><i class="fa fa-caret-right text-main" aria-hidden="true"></i></span>
        <span class="node-label">Mains</span>
        <ul class="tree-children">
          <li class="tree-node" data-value="mains_sensor_A">
            <span class="node-label">Sensor A</span>
          </li>
          <li class="tree-node" data-value="mains_sensor_B">
            <span class="node-label">Sensor B</span>
          </li>
        </ul>
      </li>
      <li class="tree-node" data-value="generation">
        <span class="toggle-icon"><i class="fa fa-caret-right text-main" aria-hidden="true"></i></span>
        <span class="node-label">Generation</span>
        <ul class="tree-children">
          <li class="tree-node" data-value="gen_sensor_A">
            <span class="node-label">Sensor A</span>
          </li>
          <li class="tree-node" data-value="gen_sensor_B">
            <span class="node-label">Sensor B</span>
          </li>
          <li class="tree-node" data-value="gen_sensor_C">
            <span class="node-label">Sensor C</span>
          </li>
        </ul>
      </li>
      <li class="tree-node" data-value="ev_charging">
        <span class="toggle-icon"><i class="fa fa-caret-right text-main" aria-hidden="true"></i></span>
        <span class="node-label">EV Charging</span>
      </li>
      <li class="tree-node" data-value="heating_cooling">
        <span class="toggle-icon"><i class="fa fa-caret-right text-main" aria-hidden="true"></i></span>
        <span class="node-label">Heating &amp; Cooling</span>
      </li>
      <li class="tree-node" data-value="laser">
        <span class="toggle-icon"><i class="fa fa-caret-right text-main" aria-hidden="true"></i></span>
        <span class="node-label">Laser</span>
      </li>
    </ul>
    <h3 class="text-main">Opciones Avanzadas</h3>
    <ul id="advancedTree" class="tree-list">
      <li class="tree-node" data-value="modo_comparativo">
        <span class="node-label">&nbsp&nbspModo Comparativo</span>
        <ul class="tree-children">
          <li class="tree-node" data-value="sub_opcion_1">
            <span class="node-label">Sub Opción 1</span>
          </li>
          <li class="tree-node" data-value="sub_opcion_2">
            <span class="node-label">Sub Opción 2</span>
          </li>
        </ul>
      </li>
      <li class="tree-node" data-value="mostrar_alertas">
        <span class="node-label">&nbsp&nbspMostrar Alertas</span>
      </li>
      <li class="tree-node" data-value="datos_respaldo">
        <span class="node-label">&nbsp&nbspIncluir Datos de Respaldo</span>
      </li>
    </ul>
  </div>
  
  <!-- Área de Contenido -->
  <div class="content">
    <!-- Filtros Superiores -->
    <div class="top-filters">
      <div class="view-by">
        <label for="viewSelect">Show by:</label>
        <select id="viewSelect">
          <option value="energy" selected>Energy</option>
          <option value="cost">Cost</option>
        </select>
      </div>
      <div class="date-filter">
        <label for="dateInput">Fecha:</label>
        <input type="date" id="dateInput" value="2025-01-10">
      </div>
    </div>
    <!-- Contenedor del Gráfico -->
    <div class="chart-container">
      <canvas id="heatmap"></canvas>
    </div>
    <!-- Leyenda -->
    <div class="legend">
      <div class="legend-item"><div class="legend-color" style="background-color: rgb(238,146,121);"></div><span>Bajo</span></div>
      <div class="legend-item"><div class="legend-color" style="background-color: rgb(245,190,160 );"></div><span>Medio</span></div>
      <div class="legend-item"><div class="legend-color" style="background-color: rgb(191,74,64);"></div><span>Alto</span></div>
    </div>
  </div>
</div>
<script src="{{ asset('js/heat_map.js') }}"></script>
</body>
</html>
@endsection
