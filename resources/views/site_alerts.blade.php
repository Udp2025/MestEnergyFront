@extends('layouts.app')

@section('title', 'Site Alerts')

@section('content')
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Site Alerts</title>
  <link rel="stylesheet" href="{{ asset('css/site_alerts.css') }}">

 
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>Site Alerts</h2>
      </div>
      <ul class="menu">
        <li data-filter="active" class="menu-item active">
          Active <span class="count">6</span>
        </li>
        <li data-filter="resolved" class="menu-item">
          Resolved <span class="count">5</span>
        </li>
        <li data-filter="all" class="menu-item">
          All <span class="count">11</span>
        </li>
      </ul>
      <div class="filters">
        <h3>Filters</h3>
        <div class="filter-group">
          <label for="dateFilter">Date:</label>
          <select id="dateFilter">
            <option value="all">All</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="severityFilter">Severity:</label>
          <select id="severityFilter">
            <option value="all">All</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="statusFilter">Status:</label>
          <select id="statusFilter">
            <option value="all">All</option>
            <option value="open">Open</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="ackFilter">Acknowledged by:</label>
          <input type="text" id="ackFilter" placeholder="User">
        </div>
        <div class="filter-group">
          <button id="resetFilters">Reset Filters</button>
        </div>
      </div>
    </aside>
    <!-- Contenido principal -->
    <main class="main-content">

      <!-- === Resumen de Alertas del Sistema === -->
      <section class="alerts-summary">
        <h1 class="alerts-summary-title">Alertas del Sistema</h1>
        <div class="alerts-summary-cards">
          <div class="summary-card">
            <span class="number">14</span>
            <span class="label">Critical alerts</span>
          </div>
          <div class="summary-card">
            <span class="number">7</span>
            <span class="label">High alerts</span>
          </div>
          <div class="summary-card">
            <span class="number">9</span>
            <span class="label">Medium alerts</span>
          </div>
        </div>
      </section>

      <header class="main-header">
        <input type="text" id="searchInput" placeholder="Search alerts...">
      </header>
      <section class="alert-list" id="alertList">
        <!-- Las alertas se renderizarán dinámicamente -->
      </section>
    </main>
  </div>
</body>
</html>
<script src="{{ asset('js/site_alerts.js') }}"></script>

@endsection
