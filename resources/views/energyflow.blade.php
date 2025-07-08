@extends('layouts.app')

@section('title', 'Energyflow')

@section('content')
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard de Flujo de Energía Mejorado</title>
  <!-- Cargamos D3.js y d3-sankey -->
  <script src="https://d3js.org/d3.v7.min.js"></script>
  <script src="https://unpkg.com/d3-sankey@0.12.3/dist/d3-sankey.min.js"></script>
  <link rel="stylesheet" href="{{ asset('css/energyflow.css') }}">

   
</head>
<body>
  <div class="dashboard">
    <!-- Panel lateral: selección de sitios -->
    <aside class="sidebar">
      <h2>Sitios</h2>
      <select id="site-select">
        <option value="textiles" selected>Textiles Manufacturer (Optimized)</option>
        <option value="food">Food Processing Plant</option>
        <option value="chemical">Chemical Factory</option>
      </select>
      <select id="site-select">
        <option value="textiles" selected>Textiles Manufacturer (Optimized)</option>
        <option value="food">Food Processing Plant</option>
        <option value="chemical">Chemical Factory</option>
      </select>
      <select id="site-select">
        <option value="textiles" selected>Textiles Manufacturer (Optimized)</option>
        <option value="food">Food Processing Plant</option>
        <option value="chemical">Chemical Factory</option>
      </select>
      <select id="site-select">
        <option value="textiles" selected>Textiles Manufacturer (Optimized)</option>
        <option value="food">Food Processing Plant</option>
        <option value="chemical">Chemical Factory</option>
      </select>
    </aside>
    <!-- Contenido principal -->
    <main class="main-content">
      <!-- Cabecera: título y filtros -->
      <header class="header">
        <h1>Energy Flow</h1>
        <div class="filters">
          <select id="period-select">
            <option value="daily" selected>Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
          </select>
          <input type="date" id="date-filter" />
        </div>
      </header>
      <!-- Sección del diagrama -->
      <section class="diagram-section">
        <svg id="sankey" width="800" height="500"></svg>
        <div class="tooltip" id="tooltip"></div>
      </section>
      <!-- Pie de página -->
      <footer class="footer">
        <div class="datetime" id="datetime">lunes, 10 de febrero de 2025</div>
        <div class="help-icon" title="Ayuda">
          <svg width="24" height="24" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" fill="#ccc" />
            <text x="12" y="17" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial">?</text>
          </svg>
        </div>
      </footer>
    </main>
  </div>
</body>
</html>
<script src="{{ asset('js/energyflow.js') }}"></script>



@endsection