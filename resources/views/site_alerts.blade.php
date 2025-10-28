@extends('layouts.app')

@section('title', 'Site Alerts')

@section('content')
<link rel="stylesheet" href="{{ asset('css/site_alerts.css') }}">

<div class="alerts-page">

  <!-- Header / título -->
  <header class="alerts-header">
    <h1>Alertas del Sistema</h1>
    <p class="alerts-sub">Monitoreo unificado de sitios y clientes (MEST)</p>
  </header>

  <!-- fila superior de tarjetas resumen -->
  <section class="summary-row">
    <div class="summary-card-wrapper">
      <div class="summary-card card-critical">
        <div class="card-info">
          <div class="card-title">Critical alerts</div>
          <div class="card-number">1</div>
        </div>
        <div class="card-icon" aria-hidden>
          <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden>
            <path d="M11.001 7h2v6h-2zM11 15h2v2h-2z" fill="#B71C1C"/>
            <path d="M12 2L3 20h18L12 2z" fill="#FFEDED"/>
          </svg>
        </div>
      </div>

      <div class="summary-card card-high">
        <div class="card-info">
          <div class="card-title">High alerts</div>
          <div class="card-number">1</div>
        </div>
        <div class="card-icon" aria-hidden>
          <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden>
            <path d="M12 4v10" stroke="#C65D11" stroke-width="1.6" stroke-linecap="round"/>
            <circle cx="12" cy="17" r="1.2" fill="#C65D11"/>
          </svg>
        </div>
      </div>

      <div class="summary-card card-medium">
        <div class="card-info">
          <div class="card-title">Medium alerts</div>
          <div class="card-number">2</div>
        </div>
        <div class="card-icon" aria-hidden>
          <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden>
            <path d="M6 12h12" stroke="#B45309" stroke-width="1.6" stroke-linecap="round"/>
            <path d="M12 6v12" stroke="#FBBF24" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
    </div>
  </section>

  <!-- Contenido principal: chart izquierda, filtros derecha -->
  <section class="main-grid">
    <main class="left-area">
      <div class="card chart-card">
        <h3 class="card-heading">Distribución por empresa</h3>
        <div id="chartContainer" class="chart-box">
          <!-- canvas para Chart.js -->
          <canvas id="companyChart" aria-label="Distribución por empresa" role="img"></canvas>
        </div>
      </div>
    </main>

    <aside class="right-area">
      <div class="card filters">
        <div class="filters-top">
          <h3>Filtros</h3>
          <button id="resetBtn" class="reset-btn">Reset</button>
        </div>

        <div class="filter-field">
          <label for="estadoFilter">Estado</label>
          <select id="estadoFilter">
            <option value="activa">Activas</option>
            <option value="todas">Todas</option>
          </select>
        </div>

        <div class="filter-field">
          <label for="severityFilter">Severidad</label>
          <select id="severityFilter">
            <option value="all">Todas</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>

        <div class="filter-field">
          <label for="searchFilter">Buscar</label>
          <input id="searchFilter" type="text" placeholder="Busca por empresa, dueño, estado...">
        </div>

        <div class="filter-actions">
          <button id="applyBtn" class="apply-btn">Aplicar</button>
        </div>
      </div>
    </aside>
  </section>

  <!-- Tabla listado de alertas (ancho completo) -->
  <section class="table-section">
    <div class="card table-card">
      <h3 class="card-heading">Listado de alertas</h3>

      <div class="table-wrap">
        <table class="alerts-table" aria-label="Listado de alertas">
          <thead>
            <tr>
              <th>Empresa</th>
              <th>Fecha</th>
              <th>Severidad</th>
              <th>Estado</th>
              <th>Responsable</th>
              <th>Detalle</th>
            </tr>
          </thead>
          <tbody id="alertList">
            <!-- filas de ejemplo: tu JS debe reemplazar por datos reales -->
            <tr>
              <td>Mest Manufacturing</td>
              <td>Apr 13, 2024</td>
              <td><span class="badge critical">critical</span></td>
              <td><span class="badge active">Active</span></td>
              <td>Vanessa Nest</td>
              <td>Detalle de la alerta</td>
            </tr>
            <tr>
              <td>Mest Manufacturing</td>
              <td>Apr 12, 2024</td>
              <td><span class="badge high">high</span></td>
              <td><span class="badge active">Active</span></td>
              <td>Robert Brown</td>
              <td>Detalle de la alerta</td>
            </tr>
            <tr>
              <td>Mest Retail</td>
              <td>Apr 09, 2024</td>
              <td><span class="badge medium">medium</span></td>
              <td><span class="badge active">Active</span></td>
              <td>Vanessa Gest</td>
              <td>Detalle de la alerta</td>
            </tr>
            <tr>
              <td>Mest Hotels</td>
              <td>Apr 07, 2024</td>
              <td><span class="badge medium">medium</span></td>
              <td><span class="badge active">Active</span></td>
              <td>Equipo NOC</td>
              <td>Detalle de la alerta</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </section>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Script para inicializar la gráfica (datos ficticios) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Datos ficticios
  const companies = [
    'Mest Manufacturing',
    'Mest Retail',
    'Mest Energy West',
    'Mest Hotels'
  ];

  // Valores ficticios por severidad (orden: critical, high, medium)
  const criticalData = [1, 0, 0, 0];
  const highData     = [0, 1, 0, 0];
  const mediumData   = [1, 1, 1, 1];

  // Obtener canvas
  const canvas = document.getElementById('companyChart');
  const ctx = canvas.getContext('2d');

  // Si ya existe un chart anterior, destrúyelo (por si haces hot-reload)
  if (window.companyChartInstance) {
    window.companyChartInstance.destroy();
  }

  // Crear chart
  window.companyChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: companies,
      datasets: [
        {
          label: 'critical',
          data: criticalData,
          stack: 'Stack 0',
          backgroundColor: '#ff6b6b',
          borderRadius: 6,
          barThickness: 28
        },
        {
          label: 'high',
          data: highData,
          stack: 'Stack 0',
          backgroundColor: '#ff9e7d',
          borderRadius: 6,
          barThickness: 28
        },
        {
          label: 'medium',
          data: mediumData,
          stack: 'Stack 0',
          backgroundColor: '#ffe08c',
          borderRadius: 6,
          barThickness: 28
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: {
          stacked: true,
          grid: { display: false },
          ticks: {
            color: '#5b6b6d',
            font: { size: 12 }
          }
        },
        y: {
          stacked: true,
          beginAtZero: true,
          grid: {
            color: '#eef4f4'
          },
          ticks: {
            stepSize: 1,
            color: '#5b6b6d',
            font: { size: 12 }
          }
        }
      },
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          enabled: true,
          backgroundColor: '#ffffff',
          titleColor: '#1f3a3c',
          bodyColor: '#2b3d3f',
          borderColor: 'rgba(30,40,40,0.06)',
          borderWidth: 1,
          padding: 10,
          displayColors: false,
          callbacks: {
            title: function(tooltipItems) {
              return tooltipItems[0].label;
            },
            label: function(context) {
              const label = context.dataset.label || '';
              const value = context.parsed.y ?? context.parsed;
              return label + ': ' + value;
            }
          }
        }
      },
      interaction: {
        mode: 'nearest',
        intersect: false
      },
      layout: {
        padding: { top: 8, right: 6, left: 6, bottom: 6 }
      }
    }
  });

  // Ajuste de tamaño inicial del canvas para mantener proporción similar a la imagen
  function resizeChartCanvas() {
    const chartBox = document.querySelector('.chart-box');
    if (chartBox && canvas) {
      // altura aproximada acorde al ancho
      const h = Math.max(220, Math.round(chartBox.clientWidth * 0.36));
      canvas.style.width = '100%';
      canvas.style.height = h + 'px';
      if (window.companyChartInstance) window.companyChartInstance.resize();
    }
  }

  // resize al cargar y al redimensionar ventana
  resizeChartCanvas();
  window.addEventListener('resize', resizeChartCanvas);

  // (Opcional) handlers básicos para filtros — aquí solo ejemplo visual
  document.getElementById('resetBtn').addEventListener('click', function () {
    document.getElementById('estadoFilter').value = 'activa';
    document.getElementById('severityFilter').value = 'all';
    document.getElementById('searchFilter').value = '';
  });
});
</script>

@endsection
