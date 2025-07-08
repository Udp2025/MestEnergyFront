@extends('layouts.app')

@section('title', 'Panel Personalizable Futurista')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
 <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">

<!-- ================== ESTILOS PERSONALIZADOS ================== -->
<style>
   body {
    margin: 0;
    font-family: 'Montserrat', sans-serif;
    background-color: #f0f2f5;
    color: #333;
  }
   .header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 25px;
    background: #ffffff;
    border-bottom: 1px solid #ddd;
  }
  .header-bar h1 {
    margin: 0;
    font-size: 28px;
    color: #333;
  }
  .header-bar .new-dash-btn {
    font-size: 28px;
    color: #ff9800;
    cursor: pointer;
  }
  /* Contenedores generales */
  #dashboardView {
    padding: 20px;
    margin-top: 10px;
  }
  .dashboard-container,
  .builder-widgets {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 20px;
    grid-auto-flow: dense;
  }
   .widget-item {
    position: relative;
    overflow: hidden;
    border: 1px solid #ffcc80;
    border-radius: 8px;
    background: #ffffff;
    padding: 12px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
  }
  .widget-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
  }
  .widget-item .widget-header {
    flex: 0 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
  }
  .widget-item .widget-header h4 {
    font-size: 20px;
    margin: 0;
    color: #333;
  }
  .widget-config-btn,
  .widget-remove-btn {
    background: none;
    border: none;
    padding: 5px;
    font-size: 20px;
    cursor: pointer;
    color: #888;
    margin-left: 4px;
    transition: color 0.2s;
  }
  .widget-config-btn:hover,
  .widget-remove-btn:hover {
    color: #333;
  }
  /* Canvas dentro de la tarjeta */
  .chart-canvas {
    flex: 1 1 auto;
    width: 100% !important;
    border-radius: 4px;
    box-sizing: border-box;
  }
  /* Modales: fondo claro en contenido */
  .modal {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }
  .modal-content {
    background: #ffffff;
    width: 90%;
    max-width: 1200px;
    max-height: 90%;
    overflow-y: auto;
    border-radius: 8px;
    padding: 20px;
    position: relative;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
  }
  .modal-header,
  .modal-footer {
    padding: 10px;
    background: #f7f7f7;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .modal-header h2,
  .modal-footer button {
    margin: 0;
    color: #333;
  }
  .modal-header button,
  .modal-footer button {
    background: #ff9800;
    color: #fff;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
  }
  /* Builder: Vista previa de gráficas con canvas */
  .builder-selection .chart-options {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
  }
  .chart-option {
    width: 280px;
    height: 280px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    background: #fafafa;
    text-align: center;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .chart-option:hover {
    transform: scale(1.05);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
  }
  .chart-option canvas {
    width: 100% !important;
    height: 200px !important;
    border-radius: 4px;
    background: #fefefe;
    margin-bottom: 10px;
  }
  .option-label {
    font-size: 20px;
    font-weight: 600;
    color: #333;
  }
  /* Panel de Configuración: estilos claros */
  .builder-config-panel label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    color: #555;
  }
  .builder-config-panel input,
  .builder-config-panel select {
    width: 100%;
    padding: 6px;
    margin-bottom: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #ffffff;
    color: #333;
  }
  .date-range {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
  }
  /* Tamaños de Widgets */
  .size-small { height: 240px; }
  .size-medium { height: 300px; }
  .size-large { height: 400px; }
  .size-wide { grid-column: span 2; height: 300px; }
  /* Modal de Eliminación */
  #deleteModal .modal-content {
    max-width: 400px;
    text-align: center;
    padding: 30px 20px;
  }
  #deleteModal p {
    font-size: 18px;
    margin-bottom: 20px;
    color: #333;
  }
  #deleteModal .modal-footer {
    justify-content: center;
    gap: 10px;
    background: none;
    padding: 0;
  }
  #deleteModal .modal-footer button {
    padding: 10px 18px;
    font-size: 16px;
    color: #333;
    background: #f1c40f;
  }
</style>

<!-- ================== HEADER DEL DASHBOARD ================== -->
<div class="header-bar">
  <h1>Dashboard Personalizable Futurista</h1>
  <i class="fas fa-plus new-dash-btn" title="Crear nuevo Dashboard" onclick="openBuilderModal()"></i>
</div>

<!-- ================== DASHBOARD FINAL ================== -->
<div id="dashboardView">
  <!-- Aquí se renderizará el dashboard final guardado -->
</div>

<!-- ================== MODAL: BUILDER (SELECCIÓN DE GRÁFICAS) ================== -->
<div id="builderModal" class="modal">
  <div class="modal-content">
    <div class="modal-body">
       <div class="builder-selection">
        <h3 style="color:#333; text-align:center;">Selecciona una Gráfica</h3>
        <div class="chart-options">
          <div class="chart-option" onclick="addWidget('line')">
            <canvas id="previewChart_line"></canvas>
            <div class="option-label">Línea con Tiempo</div>
          </div>
          <div class="chart-option" onclick="addWidget('bar')">
            <canvas id="previewChart_bar"></canvas>
            <div class="option-label">Barras Tradicional</div>
          </div>
          <div class="chart-option" onclick="addWidget('area')">
            <canvas id="previewChart_area"></canvas>
            <div class="option-label">Área Degradada</div>
          </div>
          <div class="chart-option" onclick="addWidget('mixed')">
            <canvas id="previewChart_mixed"></canvas>
            <div class="option-label">Mixto Línea/Barras</div>
          </div>
          <div class="chart-option" onclick="addWidget('radar')">
            <canvas id="previewChart_radar"></canvas>
            <div class="option-label">Radar</div>
          </div>
         </div>
      </div>
       <div class="builder-widgets" id="builderWidgets"></div>
    </div>
    <div class="modal-footer">
      <button onclick="openPreviewModal()">Previsualizar Dashboard</button>
    </div>
  </div>
</div>

<!-- ================== MODAL: CONFIGURACIÓN ================== -->
<div id="configModal" class="modal">
  <div class="modal-content">
    <div class="modal-body">
      <div class="builder-config-panel">
        <label for="widgetTitle">Título</label>
        <input type="text" id="widgetTitle" placeholder="Título del widget">
        <label for="widgetSource">Campo a Visualizar</label>
        <select id="widgetSource">
          <option value="energy" selected>Energía</option>
          <option value="cost">Costo</option>
          <option value="power">Potencia</option>
          <option value="voltage">Voltaje</option>
          <option value="current">Corriente</option>
        </select>
        <div class="date-range">
          <div>
            <label for="widgetStartDate">Fecha Inicio</label>
            <input type="date" id="widgetStartDate">
          </div>
          <div>
            <label for="widgetEndDate">Fecha Fin</label>
            <input type="date" id="widgetEndDate">
          </div>
        </div>
        <label for="widgetPeriod">Agrupar por</label>
        <select id="widgetPeriod">
          <option value="ninguno" selected>Ninguno</option>
          <option value="diario">Diario</option>
          <option value="semanal">Semanal</option>
          <option value="mensual">Mensual</option>
        </select>
        <label for="widgetLimit">Límite de Puntos</label>
        <input type="number" id="widgetLimit" value="7" min="1" max="100">
        <!-- No se incluyen campos exclusivos para gauge -->
        <label for="widgetColor">Color (Fondo de Tarjeta)</label>
        <!-- Para una paleta blanco-naranja; se usa un tono crema -->
        <input type="color" id="widgetColor" value="#fff3e0">
        <label for="widgetBorderWidth">Grosor de Borde</label>
        <input type="number" id="widgetBorderWidth" value="1" min="1" max="10">
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

<!-- ================== MODAL: PREVISUALIZACIÓN DEL DASHBOARD ================== -->
<div id="previewModal" class="modal">
  <div class="modal-content">
    <div class="modal-body">
      <div class="dashboard-container" id="previewDashboard">
        <!-- Se renderizan los widgets con datos reales -->
      </div>
    </div>
    <div class="modal-footer">
      <button onclick="addMoreWidgets()">Agregar Más</button>
      <button onclick="saveDashboard()">Guardar Dashboard</button>
    </div>
  </div>
</div>

<!-- ================== MODAL: CONFIRMAR ELIMINACIÓN ================== -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <div class="modal-body">
      <p>¿Estás seguro de eliminar este widget?</p>
    </div>
    <div class="modal-footer">
      <button onclick="closeDeleteModal()">Cancelar</button>
      <button onclick="confirmDeleteWidget()" style="background: #e74c3c;">Eliminar</button>
    </div>
  </div>
</div>

<!-- ================== INYECCIÓN DE DATOS (desde la BD) ================== -->
<script>
  const datos = @json($datos);
  console.log('Datos de la BD:', datos);
</script>

<!-- ================== SCRIPT: FUNCIONALIDAD DEL PANEL ================== -->
<script>
  let widgetsData = [];
  let currentWidgetIndex = null;
  let sortableInstance = null;
  let chartInstances = {};
  let widgetToDeleteIndex = null;

  function hexToRGBA(hex, opacity) {
    let r = parseInt(hex.slice(1,3), 16),
        g = parseInt(hex.slice(3,5), 16),
        b = parseInt(hex.slice(5,7), 16);
    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
  }

  
 
  function renderChart(widget, containerId) {
    const containerElem = document.getElementById(containerId);
    if(chartInstances[containerId]) { chartInstances[containerId].destroy(); }
    const ctx = containerElem.getContext('2d');

    let gradient = ctx.createLinearGradient(0, 0, 0, containerElem.clientHeight);
    gradient.addColorStop(0, widget.config.color);
    gradient.addColorStop(1, hexToRGBA(widget.config.color, 0.15));

    let isPreview = containerId.startsWith("previewChart_") || containerId.startsWith("widgetChart_");

    if(!isPreview && (!widget.config.data || widget.config.data.length === 0)){
      let agg = extractAggregatedData(widget.config.sourceField, widget.config.period,
                                      widget.config.startDate || '', widget.config.endDate || '',
                                      widget.config.limit);
      widget.config.data = agg.data;
      widget.config.labels = agg.labels;
    }
    const dataToUse = (!isPreview) ? (widget.config.data || []) :
                          (widget.config.data && widget.config.data.length ? widget.config.data : []);
    const labelsToUse = (!isPreview) ? (widget.config.labels || []) :
                           (widget.config.labels && widget.config.labels.length ? widget.config.labels : []);

    let config = {};
    switch(widget.type) {
      case 'line':
      case 'area': {
        let xType = (widget.config.startDate && widget.config.endDate) ? 'time' : 'category';
        config = {
          type: 'line',
          data: {
            labels: labelsToUse.length ? labelsToUse : (isPreview ? ['2024-01-01','2024-02-01','2024-03-01','2024-04-01'] : []),
            datasets: [{
              label: widget.config.title,
              data: dataToUse.length ? dataToUse : (isPreview ? [10,20,15,25] : []),
              borderColor: widget.config.color,
              backgroundColor: widget.config.fill ? gradient : 'transparent',
              borderWidth: widget.config.borderWidth,
              tension: widget.config.tension,
              fill: widget.config.fill
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
              x: {
                type: xType,
                time: { unit: 'month' },
                grid: { color: 'rgba(200,200,200,0.2)' },
                ticks: {
                  color: '#333',
                  font: { size: 12, family: 'Montserrat' },
                  autoSkip: true,
                  maxRotation: 0,
                  padding: 4
                }
              },
              y: {
                grid: { color: 'rgba(200,200,200,0.2)' },
                ticks: {
                  color: '#333',
                  font: { size: 12, family: 'Montserrat' },
                  autoSkip: true,
                  padding: 4
                }
              }
            },
            plugins: {
              legend: { labels: { color: '#333', font: { size: 14, family: 'Montserrat' } } },
              datalabels: { color: '#333', display: 'auto', anchor: 'end', align: 'end', font: { weight: 'bold' } }
            },
            animation: { duration: 1000, easing: 'easeInOutQuad' }
          }
        };
        break;
      }
      case 'bar': {
        config = {
          type: 'bar',
          data: {
            labels: labelsToUse.length ? labelsToUse : (isPreview ? ['A','B','C','D'] : []),
            datasets: [{
              label: widget.config.title,
              data: dataToUse.length ? dataToUse : (isPreview ? [15,10,20,30] : []),
              backgroundColor: gradient,
              borderWidth: widget.config.borderWidth
            }]
          },
          options: {
            indexAxis: 'x',
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
              x: {
                grid: { color: 'rgba(200,200,200,0.2)' },
                ticks: {
                  color: '#333',
                  font: { size: 12, family: 'Montserrat' },
                  autoSkip: true,
                  maxRotation: 0,
                  padding: 4
                }
              },
              y: {
                grid: { color: 'rgba(200,200,200,0.2)' },
                ticks: {
                  color: '#333',
                  font: { size: 12, family: 'Montserrat' },
                  autoSkip: true,
                  padding: 4
                }
              }
            },
            plugins: {
              legend: { labels: { color: '#333', font: { size: 14, family: 'Montserrat' } } },
              datalabels: { color: '#333', display: 'auto', anchor: 'center', align: 'center' }
            },
            animation: { duration: 1000, easing: 'easeInOutQuad' }
          }
        };
        break;
      }
      case 'mixed': {
        config = {
          type: 'bar',
          data: {
            labels: labelsToUse.length ? labelsToUse : (isPreview ? ['Ene','Feb','Mar','Abr'] : []),
            datasets: [
              {
                type: 'line',
                label: widget.config.title + ' (Línea)',
                data: dataToUse.length ? dataToUse.map(v => v * 0.85) : (isPreview ? [8,16,12,20] : []),
                borderColor: widget.config.color,
                backgroundColor: 'transparent',
                borderWidth: widget.config.borderWidth,
                tension: widget.config.tension,
                fill: false
              },
              {
                type: 'bar',
                label: widget.config.title + ' (Barras)',
                data: dataToUse.length ? dataToUse : (isPreview ? [10,20,15,25] : []),
                backgroundColor: hexToRGBA(widget.config.color, 0.5),
                borderWidth: widget.config.borderWidth
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
              x: {
                grid: { color: 'rgba(200,200,200,0.2)' },
                ticks: {
                  color: '#333',
                  font: { size: 12, family: 'Montserrat' },
                  autoSkip: true,
                  maxRotation: 0,
                  padding: 4
                }
              },
              y: {
                grid: { color: 'rgba(200,200,200,0.2)' },
                ticks: {
                  color: '#333',
                  font: { size: 12, family: 'Montserrat' },
                  autoSkip: true,
                  padding: 4
                }
              }
            },
            plugins: {
              legend: { labels: { color: '#333', font: { size: 14, family: 'Montserrat' } } },
              datalabels: { color: '#333', display: 'auto', anchor: 'end', align: 'end' }
            },
            animation: { duration: 1000, easing: 'easeInOutQuad' }
          }
        };
        break;
      }
      case 'radar': {
        config = {
          type: 'radar',
          data: {
            labels: labelsToUse.length ? labelsToUse : (isPreview ? ['Metric1','Metric2','Metric3','Metric4'] : []),
            datasets: [{
              label: widget.config.title,
              data: dataToUse.length ? dataToUse : (isPreview ? [10,20,30,40] : []),
              backgroundColor: hexToRGBA(widget.config.color, 0.3),
              borderColor: widget.config.color,
              borderWidth: widget.config.borderWidth
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              r: {
                angleLines: { color: 'rgba(200,200,200,0.2)' },
                grid: { color: 'rgba(200,200,200,0.2)' },
                pointLabels: { color: '#333', font: { size: 12, family: 'Montserrat' } }
              }
            },
            plugins: {
              legend: { labels: { color: '#333', font: { size: 14, family: 'Montserrat' } } },
              datalabels: { color: '#333' }
            },
            animation: { duration: 1000, easing: 'easeInOutQuad' }
          }
        };
        break;
      }
      default: {
        config = {
          type: 'line',
          data: {
            labels: labelsToUse.length ? labelsToUse : (isPreview ? ['X','Y','Z'] : []),
            datasets: [{
              label: widget.config.title,
              data: dataToUse.length ? dataToUse : (isPreview ? [10,20,30] : []),
              borderColor: widget.config.color,
              backgroundColor: gradient,
              borderWidth: widget.config.borderWidth
            }]
          },
          options: { responsive: true, maintainAspectRatio: false }
        };
      }
    }
    let newChart = new Chart(ctx, config);
    chartInstances[containerId] = newChart;
  }

  /***** FUNCIONES DE MODALES Y BUILDER *****/
  function openBuilderModal() {
    document.getElementById('builderModal').style.display = 'flex';
    initPreviewCharts();
    initializeSortable('builderWidgets');
  }
  function closeBuilderModal() { document.getElementById('builderModal').style.display = 'none'; }
  function openConfigModal(index) {
    currentWidgetIndex = index;
    const widget = widgetsData[index];
    document.getElementById('widgetTitle').value = widget.config.title;
    document.getElementById('widgetColor').value = widget.config.color;
    document.getElementById('widgetBorderWidth').value = widget.config.borderWidth;
    document.getElementById('widgetTension').value = widget.config.tension || 0;
    document.getElementById('widgetFill').value = widget.config.fill ? "true" : "false";
    document.getElementById('widgetSize').value = widget.config.size || 'medium';
    if(!widget.config.sourceField){ widget.config.sourceField = 'energy'; }
    document.getElementById('widgetSource').value = widget.config.sourceField;
    document.getElementById('widgetStartDate').value = widget.config.startDate || '';
    document.getElementById('widgetEndDate').value = widget.config.endDate || '';
    if(!widget.config.period){ widget.config.period = 'ninguno'; }
    document.getElementById('widgetPeriod').value = widget.config.period;
    if(!widget.config.limit){ widget.config.limit = 7; }
    document.getElementById('widgetLimit').value = widget.config.limit;
    document.getElementById('configModal').style.display = 'flex';
  }
  function closeConfigModal() { document.getElementById('configModal').style.display = 'none'; }
  function openPreviewModal() { document.getElementById('previewModal').style.display = 'flex'; setTimeout(renderPreviewDashboard, 200); }
  function closePreviewModal() { document.getElementById('previewModal').style.display = 'none'; }
  function closeDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; widgetToDeleteIndex = null; }

  function getDefaultConfig(type) {
    if(type === 'line'){
      return { title: 'Línea - Energía', sourceField: 'energy', data: [], labels: [], color: '#00d2ff', borderWidth: 2, tension: 0.3, fill: false, size: 'medium', startDate:'2024-01-01', endDate:'2024-04-01', period: 'diario', limit: 7 };
    } else if(type === 'bar'){
      return { title: 'Barras - Costo', sourceField: 'cost', data: [], labels: [], color: '#00d2ff', borderWidth: 2, size: 'medium', period: 'diario', limit: 7 };
    } else if(type === 'area'){
      return { title: 'Área - Potencia', sourceField: 'power', data: [], labels: [], color: '#00d2ff', borderWidth: 2, tension: 0.3, fill: true, size: 'medium', startDate:'2024-01-01', endDate:'2024-04-01', period: 'diario', limit: 7 };
    } else if(type === 'scatter'){
      return { title: 'Dispersión - Voltaje', sourceField: 'voltage', data: [], labels: [], color: '#00d2ff', borderWidth: 2, size: 'medium', period: 'diario', limit: 7 };
    } else if(type === 'radar'){
      return { title: 'Radar - Corriente', sourceField: 'current', data: [], labels: [], color: '#00d2ff', borderWidth: 2, size: 'medium', period: 'diario', limit: 7 };
    } else if(type === 'bubble'){
      return { title: 'Burbuja', sourceField: 'energy', data: [], labels: [], color: '#00d2ff', borderWidth: 1, size: 'medium', period: 'ninguno', limit: 50 };
    } else if(type === 'hbar'){
      return { title: 'Barras Horiz.', sourceField: 'energy', data: [], labels: [], color: '#00d2ff', borderWidth: 2, size: 'medium', period: 'ninguno', limit: 50 };
    } else if(type === 'doughnut'){
      return { title: 'Doughnut', sourceField: 'energy', data: [], labels: [], colors: ['#00d2ff','#ff0062','#f1c40f'], borderWidth: 2, size: 'medium', period: 'ninguno', limit: 50 };
    } else if(type === 'pie'){
      return { title: 'Pie', sourceField: 'energy', data: [], labels: [], colors: ['#00d2ff','#8e44ad','#e67e22'], borderWidth: 2, size: 'medium', period: 'ninguno', limit: 50 };
    } else if(type === 'mixed'){
      return { title: 'Mixto - Ejemplo', sourceField: 'energy', data: [], labels: [], color: '#00d2ff', borderWidth: 2, tension: 0.3, fill: false, size: 'medium', startDate:'2024-01-01', endDate:'2024-04-01', period: 'diario', limit: 7 };
    }
    return {};
  }
  function addWidget(type) {
    const defaultConfig = getDefaultConfig(type);
    widgetsData.push({ type: type, config: defaultConfig });
    renderBuilderWidgets();
  }
  function renderBuilderWidgets() {
    const container = document.getElementById('builderWidgets');
    container.innerHTML = '';
    widgetsData.forEach((widget, index) => {
      const widgetDiv = document.createElement('div');
      widgetDiv.className = 'widget-item ' + getSizeClass(widget.config.size);
      widgetDiv.innerHTML = `
        <div class="widget-header">
          <h4>${widget.config.title}</h4>
          <div>
            <button class="widget-config-btn" onclick="openConfigModal(${index})" title="Editar"><i class="fas fa-pencil-alt"></i></button>
            <button class="widget-remove-btn" onclick="promptDeleteWidget(${index})" title="Eliminar"><i class="fas fa-trash"></i></button>
          </div>
        </div>
        <canvas id="widgetChart_${index}" class="chart-canvas"></canvas>
      `;
      container.appendChild(widgetDiv);
      renderChart(widget, 'widgetChart_' + index);
    });
    initializeSortable('builderWidgets');
  }
  function promptDeleteWidget(index) {
    widgetToDeleteIndex = index;
    document.getElementById('deleteModal').style.display = 'flex';
  }
  function confirmDeleteWidget() {
    if(widgetToDeleteIndex !== null) {
      widgetsData.splice(widgetToDeleteIndex, 1);
      renderBuilderWidgets();
      renderPreviewDashboard();
      closeDeleteModal();
    }
  }
  function getSizeClass(size) {
    if(size === 'small') return 'size-small';
    if(size === 'large') return 'size-large';
    if(size === 'wide') return 'size-wide';
    return 'size-medium';
  }
  function saveWidgetConfig() {
    if(currentWidgetIndex !== null) {
      const widget = widgetsData[currentWidgetIndex];
      widget.config.title = document.getElementById('widgetTitle').value;
      if(widget.type !== 'gauge') {
        widget.config.sourceField = document.getElementById('widgetSource').value;
        widget.config.startDate = document.getElementById('widgetStartDate').value;
        widget.config.endDate = document.getElementById('widgetEndDate').value;
        widget.config.period = document.getElementById('widgetPeriod').value;
        widget.config.limit = parseInt(document.getElementById('widgetLimit').value);
        let agg = extractAggregatedData(widget.config.sourceField, widget.config.period, widget.config.startDate, widget.config.endDate, widget.config.limit);
        widget.config.data = agg.data;
        widget.config.labels = agg.labels;
      }
      widget.config.color = document.getElementById('widgetColor').value;
      widget.config.borderWidth = parseInt(document.getElementById('widgetBorderWidth').value);
      widget.config.tension = parseFloat(document.getElementById('widgetTension').value);
      widget.config.fill = (document.getElementById('widgetFill').value === "true");
      widget.config.size = document.getElementById('widgetSize').value;
      closeConfigModal();
      renderBuilderWidgets();
    }
  }
  function renderPreviewDashboard() {
    const previewContainer = document.getElementById('previewDashboard');
    previewContainer.innerHTML = '';
    widgetsData.forEach((widget, index) => {
      const widgetDiv = document.createElement('div');
      widgetDiv.className = 'widget-item ' + getSizeClass(widget.config.size);
      widgetDiv.innerHTML = `
        <div class="widget-header"><h4>${widget.config.title}</h4></div>
        <canvas id="previewChart_${index}" class="chart-canvas"></canvas>
      `;
      previewContainer.appendChild(widgetDiv);
      renderChart(widget, 'previewChart_' + index);
    });
  }
  function addMoreWidgets() {
    closePreviewModal();
    openBuilderModal();
  }
  function saveDashboard() {
    fetch('/panels/save', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ widgetsData })
    })
    .then(response => response.json())
    .then(data => {
      console.log(data.message);
      renderFinalDashboard();
      closePreviewModal();
      closeBuilderModal();
    })
    .catch(error => console.error('Error al guardar el panel:', error));
  }
  function renderFinalDashboard() {
    const dashboardView = document.getElementById('dashboardView');
    dashboardView.innerHTML = '<div class="dashboard-container" id="finalDashboard"></div>';
    const finalContainer = document.getElementById('finalDashboard');
    widgetsData.forEach((widget, index) => {
      const widgetDiv = document.createElement('div');
      widgetDiv.className = 'widget-item ' + getSizeClass(widget.config.size);
      widgetDiv.innerHTML = `
        <div class="widget-header"><h4>${widget.config.title}</h4></div>
        <canvas id="finalChart_${index}" class="chart-canvas"></canvas>
      `;
      finalContainer.appendChild(widgetDiv);
      renderChart(widget, 'finalChart_' + index);
    });
  }
  function initializeSortable(containerId) {
    const container = document.getElementById(containerId);
    if(sortableInstance) { sortableInstance.destroy(); }
    sortableInstance = Sortable.create(container, {
      animation: 150,
      onEnd: function(evt) {
        if(evt.oldIndex !== evt.newIndex) {
          let movedItem = widgetsData.splice(evt.oldIndex, 1)[0];
          widgetsData.splice(evt.newIndex, 0, movedItem);
          renderBuilderWidgets();
        }
      }
    });
  }
  // Agrupa y extrae datos reales de la BD según fechas o periodos
  function extractAggregatedData(field, period, startDate, endDate, limit) {
    let grouped = {};
    datos.forEach(d => {
      let fecha = d.fecha.substring(0,10);
      if(startDate && fecha < startDate) return;
      if(endDate && fecha > endDate) return;
      let key = fecha;
      if(period === 'mensual') { key = d.fecha.substring(0,7); }
      else if(period === 'semanal'){
        let dateObj = new Date(d.fecha);
        let firstDayOfWeek = new Date(dateObj.setDate(dateObj.getDate() - dateObj.getDay()));
        key = firstDayOfWeek.toISOString().substring(0,10);
      }
      if(!grouped[key]) grouped[key] = [];
      grouped[key].push(parseFloat(d[field]));
    });
    let aggregatedData = [];
    let labels = [];
    Object.keys(grouped).sort().forEach(key => {
      let avg = grouped[key].reduce((a,b) => a + b, 0) / grouped[key].length;
      aggregatedData.push(parseFloat(avg.toFixed(2)));
      labels.push(key);
    });
    if(limit && aggregatedData.length > limit){
      let factor = aggregatedData.length / limit;
      let newData = [];
      let newLabels = [];
      for(let i = 0; i < aggregatedData.length; i += factor){
        newData.push(aggregatedData[Math.floor(i)]);
        newLabels.push(labels[Math.floor(i)]);
      }
      return { data: newData, labels: newLabels };
    }
    return { data: aggregatedData, labels: labels };
  }
  // Inicializa las vistas previas en el builder (renderiza gráficos demo en los canvas)
  function initPreviewCharts() {
    renderChart({ type: 'line', config: getDefaultConfig('line') }, 'previewChart_line');
    renderChart({ type: 'bar', config: getDefaultConfig('bar') }, 'previewChart_bar');
    renderChart({ type: 'area', config: getDefaultConfig('area') }, 'previewChart_area');
    renderChart({ type: 'mixed', config: getDefaultConfig('mixed') }, 'previewChart_mixed');
    renderChart({ type: 'radar', config: getDefaultConfig('radar') }, 'previewChart_radar');
    // Se han removido las llamadas para Polar Area y Gauge
  }
  window.onload = function() {
    fetch('/panels/get', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    .then(response => response.json())
    .then(data => { widgetsData = data.widgetsData || []; renderFinalDashboard(); })
    .catch(error => console.error('Error al cargar el panel:', error));
  };
</script>
@endsection
