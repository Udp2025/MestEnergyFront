@extends('layouts.complete')

@section('title', 'Visualize')

@section('content')
<!-- Incluir token CSRF -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- CDN de Font Awesome, SortableJS y ApexCharts -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/visualize.css') }}">

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<!-- ================== HEADER ================== -->
<div class="header-bar">
  <h1>Dashboard</h1>
  <i class="fas fa-plus new-dash-btn" title="Crear nuevo Dashboard" onclick="openBuilderModal()"></i>
</div>

<!-- ================== DASHBOARD FINAL ================== -->
<div id="dashboardView">
  <!-- Se cargará el dashboard guardado -->
</div>

<!-- ================== MODAL: BUILDER ================== -->
<div id="builderModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Crear y Personalizar Dashboard</h2>
      <button onclick="closeBuilderModal()">X</button>
    </div>
    <div class="modal-body">
      <!-- Selección de gráficas -->
      <div class="builder-selection">
        <h3>Selecciona una Gráfica</h3>
        <div class="chart-options">
          <!-- Se muestran previsualizaciones básicas creadas con ApexCharts -->
          <div class="chart-option" onclick="addWidget('line')">
            <div id="previewLine" class="preview-chart"></div>
            <div class="option-label">Línea</div>
          </div>
          <div class="chart-option" onclick="addWidget('bar')">
            <div id="previewBar" class="preview-chart"></div>
            <div class="option-label">Barras</div>
          </div>
          <div class="chart-option" onclick="addWidget('area')">
            <div id="previewArea" class="preview-chart"></div>
            <div class="option-label">Área</div>
          </div>
          <div class="chart-option" onclick="addWidget('scatter')">
            <div id="previewScatter" class="preview-chart"></div>
            <div class="option-label">Dispersión</div>
          </div>
          <div class="chart-option" onclick="addWidget('radar')">
            <div id="previewRadar" class="preview-chart"></div>
            <div class="option-label">Radar</div>
          </div>
          <div class="chart-option" onclick="addWidget('bubble')">
            <div id="previewBubble" class="preview-chart"></div>
            <div class="option-label">Burbuja</div>
          </div>
          <div class="chart-option" onclick="addWidget('hbar')">
            <div id="previewHBar" class="preview-chart"></div>
            <div class="option-label">Barras Horiz.</div>
          </div>
          <div class="chart-option" onclick="addWidget('doughnut')">
            <div id="previewDoughnut" class="preview-chart"></div>
            <div class="option-label">Doughnut</div>
          </div>
          <div class="chart-option" onclick="addWidget('pie')">
            <div id="previewPie" class="preview-chart"></div>
            <div class="option-label">Pie</div>
          </div>
          <div class="chart-option" onclick="addWidget('polar')">
            <div id="previewPolar" class="preview-chart"></div>
            <div class="option-label">Polar Area</div>
          </div>
          <div class="chart-option" onclick="addWidget('gauge')">
            <div id="previewGauge" class="preview-chart"></div>
            <div class="option-label">Medidor</div>
          </div>
        </div>
      </div>
      <!-- Área donde se muestran los widgets agregados -->
      <div class="builder-widgets" id="builderWidgets">
        <!-- Se renderizan los widgets aquí -->
      </div>
    </div>
    <div class="modal-footer">
      <button onclick="openPreviewModal()">Previsualizar Dashboard</button>
    </div>
  </div>
</div>

<!-- ================== MODAL: CONFIGURACIÓN ================== -->
<div id="configModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Configurar Widget</h2>
      <button onclick="closeConfigModal()">X</button>
    </div>
    <div class="modal-body">
      <div class="builder-config-panel">
        <label for="widgetTitle">Título</label>
        <input type="text" id="widgetTitle" placeholder="Título del widget">
        <label for="widgetData">Datos (coma separada)</label>
        <input type="text" id="widgetData" placeholder="e.g. 10,20,30,40">
        <label for="widgetLabels">Etiquetas (coma separada)</label>
        <input type="text" id="widgetLabels" placeholder="e.g. Jan,Feb,Mar,Apr">
        <!-- Campos adicionales para gauge -->
        <div id="gaugeFields" style="display:none;">
          <label for="widgetValue">Valor Actual</label>
          <input type="number" id="widgetValue" placeholder="Valor Actual">
          <label for="widgetMin">Valor Mínimo</label>
          <input type="number" id="widgetMin" placeholder="Mínimo">
          <label for="widgetMax">Valor Máximo</label>
          <input type="number" id="widgetMax" placeholder="Máximo">
        </div>
        <label for="widgetColor">Color</label>
        <input type="color" id="widgetColor" value="#3498db">
        <label for="widgetBorderWidth">Grosor de Borde</label>
        <input type="number" id="widgetBorderWidth" value="2" min="1" max="10">
        <label for="widgetTension">Tensión (Line/Área)</label>
        <input type="number" step="0.1" id="widgetTension" value="0.3" min="0" max="1">
        <label for="widgetFill">Rellenar (Line/Área)</label>
        <select id="widgetFill">
          <option value="false">No</option>
          <option value="true">Sí</option>
        </select>
        <label for="widgetSize">Tamaño</label>
        <select id="widgetSize">
          <option value="small">Pequeño</option>
          <option value="medium" selected>Mediano</option>
          <option value="large">Grande</option>
          <option value="wide">Wide (Rectangular)</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button onclick="saveWidgetConfig()">Guardar Configuración</button>
    </div>
  </div>
</div>

<!-- ================== MODAL: PREVISUALIZACIÓN ================== -->
<div id="previewModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Previsualizar Dashboard</h2>
      <button onclick="closePreviewModal()">X</button>
    </div>
    <div class="modal-body">
      <div class="dashboard-container" id="previewDashboard">
        <!-- Se renderizan los widgets para previsualización -->
      </div>
    </div>
    <div class="modal-footer">
      <button onclick="addMoreWidgets()">Agregar Más</button>
      <button onclick="saveDashboard()">Guardar Dashboard</button>
    </div>
  </div>
</div>
<script src="{{ asset('js/visualize.js') }}"></script>

@endsection
