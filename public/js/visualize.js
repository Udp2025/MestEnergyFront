   /***** Variables Globales *****/
  let widgetsData = []; // Array que contendrá cada widget
  let currentWidgetIndex = null; // Índice del widget en edición
  let sortableInstance = null; // Instancia de SortableJS
  let chartInstances = {}; // Objeto para almacenar las instancias de ApexCharts

  /***** Función para renderizar gráfico con ApexCharts *****/
  function renderChart(widget, containerId) {
    const container = document.getElementById(containerId);
    // Si existe un gráfico previo en el contenedor, destrúyelo
    if(chartInstances[containerId]){
      chartInstances[containerId].destroy();
    }
    let options = {};
    // Configuración según el tipo de gráfico
    if(widget.type === 'line'){
      options = {
        chart: { type: 'line', height: '100%', toolbar: { show: true } },
        series: [{ name: widget.config.title, data: widget.config.data }],
        xaxis: { categories: widget.config.labels },
        stroke: { curve: widget.config.tension > 0 ? 'smooth' : 'straight', width: widget.config.borderWidth },
        colors: [widget.config.color],
        title: { text: widget.config.title, align: 'center', style: { fontSize: '16px', fontWeight: 'bold' } },
        tooltip: { enabled: true }
      };
    } else if(widget.type === 'bar'){
      options = {
        chart: { type: 'bar', height: '100%', toolbar: { show: true } },
        series: [{ name: widget.config.title, data: widget.config.data }],
        xaxis: { categories: widget.config.labels },
        colors: [widget.config.color],
        title: { text: widget.config.title, align: 'center', style: { fontSize: '16px', fontWeight: 'bold' } },
        tooltip: { enabled: true }
      };
    } else if(widget.type === 'area'){
      options = {
        chart: { type: 'area', height: '100%', toolbar: { show: true } },
        series: [{ name: widget.config.title, data: widget.config.data }],
        xaxis: { categories: widget.config.labels },
        stroke: { curve: 'smooth', width: widget.config.borderWidth },
        colors: [widget.config.color],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.2 } },
        title: { text: widget.config.title, align: 'center', style: { fontSize: '16px', fontWeight: 'bold' } },
        tooltip: { enabled: true }
      };
    } else if(widget.type === 'scatter'){
      options = {
        chart: { type: 'scatter', height: '100%', toolbar: { show: true } },
        series: [{ name: widget.config.title, data: widget.config.data.map((v,i)=> [i, v]) }],
        xaxis: { title: { text: 'X' } },
        yaxis: { title: { text: 'Y' } },
        colors: [widget.config.color],
        title: { text: widget.config.title, align: 'center', style: { fontSize: '16px', fontWeight: 'bold' } },
        tooltip: { enabled: true }
      };
    } else if(widget.type === 'radar'){
      options = {
        chart: { type: 'radar', height: '100%', toolbar: { show: true } },
        series: [{ name: widget.config.title, data: widget.config.data }],
        labels: widget.config.labels,
        title: { text: widget.config.title, align: 'center', style: { fontSize: '16px', fontWeight: 'bold' } },
        tooltip: { enabled: true }
      };
    } else if(widget.type === 'bubble'){
      options = {
        chart: { type: 'bubble', height: '100%', toolbar: { show: true } },
        series: [{
          name: widget.config.title,
          data: widget.config.data.map((v,i) => ({ x: widget.config.labels[i] || i, y: v, z: Math.max(v,10) }))
        }],
        xaxis: { type: 'category' },
        colors: [widget.config.color],
        title: { text: widget.config.title, align: 'center', style: { fontSize: '16px', fontWeight: 'bold' } },
        tooltip: { enabled: true }
      };
    } else if(widget.type === 'hbar'){
      options = {
        chart: { type: 'bar', height: '100%', toolbar: { show: true } },
        plotOptions: { bar: { horizontal: true } },
        series: [{ name: widget.config.title, data: widget.config.data }],
        xaxis: { categories: widget.config.labels },
        colors: [widget.config.color],
        title: { text: widget.config.title, align: 'center', style: { fontSize: '16px', fontWeight: 'bold' } },
        tooltip: { enabled: true }
      };
    } else if(widget.type === 'doughnut'){
      options = {
        chart: { type: 'donut', height: '100%' },
        series: widget.config.data,
        labels: widget.config.labels,
        colors: widget.config.colors || [widget.config.color],
        title: { text: widget.config.title, align: 'center', style: { fontSize: '16px', fontWeight: 'bold' } },
        tooltip: { enabled: true }
      };
    } else if(widget.type === 'pie'){
      options = {
        chart: { type: 'pie', height: '100%' },
        series: widget.config.data,
        labels: widget.config.labels,
        colors: widget.config.colors || [widget.config.color],
        title: { text: widget.config.title, align: 'center', style: { fontSize: '16px', fontWeight: 'bold' } },
        tooltip: { enabled: true }
      };
    } else if(widget.type === 'polar'){
      // Emulamos una gráfica polar usando radialBar
      options = {
        chart: { type: 'radialBar', height: '100%', toolbar: { show: true } },
        series: widget.config.data,
        labels: widget.config.labels,
        plotOptions: {
          radialBar: {
            dataLabels: {
              total: {
                show: true,
                label: 'Total',
                formatter: function() {
                  return widget.config.data.reduce((a,b) => a + b, 0);
                }
              }
            }
          }
        },
        colors: widget.config.colors || [widget.config.color],
        title: { text: widget.config.title, align: 'center', style: { fontSize: '16px', fontWeight: 'bold' } },
        tooltip: { enabled: true }
      };
    } else if(widget.type === 'gauge'){
      options = {
        chart: { type: 'radialBar', height: '100%', toolbar: { show: true } },
        series: [widget.config.value],
        plotOptions: {
          radialBar: {
            startAngle: -135,
            endAngle: 135,
            hollow: { size: '70%' },
            dataLabels: {
              name: { offsetY: -10, color: '#888', fontSize: '13px' },
              value: { offsetY: 10, color: '#111', fontSize: '16px' }
            }
          }
        },
        fill: {
          type: 'gradient',
          gradient: {
            shade: 'light',
            type: 'horizontal',
            shadeIntensity: 0.5,
            gradientToColors: [widget.config.color],
            inverseColors: false,
            opacityFrom: 1,
            opacityTo: 1,
            stops: [0, 100]
          }
        },
        labels: [widget.config.title],
        title: { text: widget.config.title, align: 'center', style: { fontSize: '16px', fontWeight: 'bold' } }
      };
    }
    // Crear instancia de ApexCharts y guardarla
    let chart = new ApexCharts(container, options);
    chart.render();
    chartInstances[containerId] = chart;
  }

  /***** Funciones de Modales *****/
  function openBuilderModal(){
    document.getElementById('builderModal').style.display = 'flex';
    initPreviewCharts();
    initializeSortable('builderWidgets');
  }
  function closeBuilderModal(){
    document.getElementById('builderModal').style.display = 'none';
  }
  function openConfigModal(index){
    currentWidgetIndex = index;
    const widget = widgetsData[index];
    document.getElementById('widgetTitle').value = widget.config.title;
    if(widget.type === 'gauge'){
      document.getElementById('gaugeFields').style.display = 'block';
      document.getElementById('widgetValue').value = widget.config.value;
      document.getElementById('widgetMin').value = widget.config.min;
      document.getElementById('widgetMax').value = widget.config.max;
      document.getElementById('widgetData').value = '';
      document.getElementById('widgetLabels').value = '';
    } else {
      document.getElementById('gaugeFields').style.display = 'none';
      document.getElementById('widgetData').value = widget.config.data.join(',');
      document.getElementById('widgetLabels').value = widget.config.labels.join(',');
    }
    document.getElementById('widgetColor').value = widget.config.color;
    document.getElementById('widgetBorderWidth').value = widget.config.borderWidth;
    document.getElementById('widgetTension').value = widget.config.tension || 0;
    document.getElementById('widgetFill').value = widget.config.fill ? "true" : "false";
    document.getElementById('widgetSize').value = widget.config.size || 'medium';
    document.getElementById('configModal').style.display = 'flex';
  }
  function closeConfigModal(){
    document.getElementById('configModal').style.display = 'none';
  }
  // Para solucionar el problema de que en previsualización aparezcan cuadros en blanco,
  // primero mostramos el modal y luego (tras un breve retraso) renderizamos las gráficas.
  function openPreviewModal(){
    document.getElementById('previewModal').style.display = 'flex';
    setTimeout(renderPreviewDashboard, 200);
  }
  function closePreviewModal(){
    document.getElementById('previewModal').style.display = 'none';
  }

  /***** Funciones para agregar, editar y renderizar widgets *****/
  function getDefaultConfig(type){
    if(type === 'line'){
      return { title: 'Línea Chart', data: [10,20,30,40], labels: ['A','B','C','D'], color: '#3498db', borderWidth: 2, tension: 0.3, fill: false, size: 'medium' };
    } else if(type === 'bar'){
      return { title: 'Bar Chart', data: [10,20,30,40], labels: ['A','B','C','D'], color: '#e67e22', borderWidth: 2, size: 'medium' };
    } else if(type === 'area'){
      return { title: 'Área Chart', data: [10,20,30,40], labels: ['A','B','C','D'], color: '#27ae60', borderWidth: 2, tension: 0.3, fill: true, size: 'medium' };
    } else if(type === 'scatter'){
      return { title: 'Scatter Chart', data: [10,20,30,40], labels: ['A','B','C','D'], color: '#9b59b6', borderWidth: 2, size: 'medium' };
    } else if(type === 'radar'){
      return { title: 'Radar Chart', data: [10,20,30,40], labels: ['A','B','C','D'], color: '#2ecc71', borderWidth: 2, size: 'medium' };
    } else if(type === 'bubble'){
      return { title: 'Bubble Chart', data: [10,20,30,40], labels: ['A','B','C','D'], color: '#e74c3c', borderWidth: 1, size: 'medium' };
    } else if(type === 'hbar'){
      return { title: 'Barras Horiz.', data: [10,20,30,40], labels: ['A','B','C','D'], color: '#f39c12', borderWidth: 2, size: 'medium' };
    } else if(type === 'doughnut'){
      return { title: 'Doughnut Chart', data: [30,20,50], labels: ['Red','Blue','Yellow'], colors: ['#e74c3c', '#3498db', '#f1c40f'], borderWidth: 2, size: 'medium' };
    } else if(type === 'pie'){
      return { title: 'Pie Chart', data: [40,30,30], labels: ['A','B','C'], colors: ['#8e44ad', '#3498db', '#e67e22'], borderWidth: 2, size: 'medium' };
    } else if(type === 'polar'){
      return { title: 'Polar Area Chart', data: [11,16,7,3,14], labels: ['Red','Green','Yellow','Grey','Blue'], colors: ['#e74c3c', '#27ae60', '#f1c40f', '#95a5a6', '#3498db'], borderWidth: 2, size: 'medium' };
    } else if(type === 'gauge'){
      return { title: 'Medidor', value: 75, min: 0, max: 100, color: '#3498db', borderWidth: 2, size: 'medium' };
    }
    return {};
  }
  function addWidget(type){
    const defaultConfig = getDefaultConfig(type);
    widgetsData.push({ type: type, config: defaultConfig });
    renderBuilderWidgets();
  }
  function renderBuilderWidgets(){
    const container = document.getElementById('builderWidgets');
    container.innerHTML = '';
    widgetsData.forEach((widget, index) => {
      const widgetDiv = document.createElement('div');
      widgetDiv.className = 'widget-item ' + getSizeClass(widget.config.size);
      widgetDiv.innerHTML = `
        <div class="widget-header">
          <h4>${widget.config.title}</h4>
          <div>
            <button class="widget-config-btn" onclick="openConfigModal(${index})">Editar</button>
            <button class="widget-remove-btn" onclick="removeWidget(${index})">Eliminar</button>
          </div>
        </div>
        <div id="widgetChart_${index}" class="apex-chart"></div>
      `;
      container.appendChild(widgetDiv);
      renderChart(widget, 'widgetChart_' + index);
    });
    initializeSortable('builderWidgets');
  }
  function removeWidget(index){
    if(confirm("¿Estás seguro de eliminar este widget?")){
      widgetsData.splice(index, 1);
      renderBuilderWidgets();
    }
  }
  function getSizeClass(size){
    if(size === 'small') return 'size-small';
    if(size === 'large') return 'size-large';
    if(size === 'wide') return 'size-wide';
    return 'size-medium';
  }

  /***** Funciones de Configuración *****/
  function saveWidgetConfig(){
    if(currentWidgetIndex !== null){
      const widget = widgetsData[currentWidgetIndex];
      widget.config.title = document.getElementById('widgetTitle').value;
      if(widget.type === 'gauge'){
        widget.config.value = parseFloat(document.getElementById('widgetValue').value);
        widget.config.min = parseFloat(document.getElementById('widgetMin').value);
        widget.config.max = parseFloat(document.getElementById('widgetMax').value);
      } else {
        widget.config.data = document.getElementById('widgetData').value.split(',').map(Number);
        widget.config.labels = document.getElementById('widgetLabels').value.split(',');
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

  /***** Funciones de Previsualización y Guardado *****/
  function renderPreviewDashboard(){
    const previewContainer = document.getElementById('previewDashboard');
    previewContainer.innerHTML = '';
    widgetsData.forEach((widget, index) => {
      const widgetDiv = document.createElement('div');
      widgetDiv.className = 'widget-item ' + getSizeClass(widget.config.size);
      widgetDiv.innerHTML = `
        <div class="widget-header"><h4>${widget.config.title}</h4></div>
        <div id="previewChart_${index}" class="apex-chart"></div>
      `;
      previewContainer.appendChild(widgetDiv);
      renderChart(widget, 'previewChart_' + index);
    });
  }
  function addMoreWidgets(){
    closePreviewModal();
    openBuilderModal();
  }
  function saveDashboard(){
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
  function renderFinalDashboard(){
    const dashboardView = document.getElementById('dashboardView');
    dashboardView.innerHTML = '<div class="dashboard-container" id="finalDashboard"></div>';
    const finalContainer = document.getElementById('finalDashboard');
    widgetsData.forEach((widget, index) => {
      const widgetDiv = document.createElement('div');
      widgetDiv.className = 'widget-item ' + getSizeClass(widget.config.size);
      widgetDiv.innerHTML = `
        <div class="widget-header"><h4>${widget.config.title}</h4></div>
        <div id="finalChart_${index}" class="apex-chart"></div>
      `;
      finalContainer.appendChild(widgetDiv);
      renderChart(widget, 'finalChart_' + index);
    });
  }

  /***** Drag & Drop: Inicializar SortableJS *****/
  function initializeSortable(containerId){
    const container = document.getElementById(containerId);
    if(sortableInstance){
      sortableInstance.destroy();
    }
    sortableInstance = Sortable.create(container, {
      animation: 150,
      onEnd: function(evt){
        if(evt.oldIndex !== evt.newIndex){
          let movedItem = widgetsData.splice(evt.oldIndex, 1)[0];
          widgetsData.splice(evt.newIndex, 0, movedItem);
          renderBuilderWidgets();
        }
      }
    });
  }

  /***** Inicialización de mini gráficas en la previsualización *****/
  function initPreviewCharts(){
    // Se usan configuraciones avanzadas para cada preview
    renderChart({ type: 'line', config: { title: 'Línea', data: [10,20,15,25], labels: ['A','B','C','D'], color: '#3498db', borderWidth: 2, tension: 0.3, fill: true, size: 'medium' } }, 'previewLine');
    renderChart({ type: 'bar', config: { title: 'Barras', data: [15,10,20,30], labels: ['A','B','C','D'], color: '#e67e22', borderWidth: 2, size: 'medium' } }, 'previewBar');
    renderChart({ type: 'area', config: { title: 'Área', data: [5,15,10,20], labels: ['A','B','C','D'], color: '#27ae60', borderWidth: 2, tension: 0.3, fill: true, size: 'medium' } }, 'previewArea');
    renderChart({ type: 'scatter', config: { title: 'Dispersión', data: [10,20,15,25], labels: ['A','B','C','D'], color: '#9b59b6', borderWidth: 2, size: 'medium' } }, 'previewScatter');
    renderChart({ type: 'radar', config: { title: 'Radar', data: [10,20,30,40], labels: ['A','B','C','D'], color: '#2ecc71', borderWidth: 2, size: 'medium' } }, 'previewRadar');
    renderChart({ type: 'bubble', config: { title: 'Burbuja', data: [10,20,15,25], labels: ['A','B','C','D'], color: '#e74c3c', borderWidth: 1, size: 'medium' } }, 'previewBubble');
    renderChart({ type: 'hbar', config: { title: 'Barras Horiz.', data: [10,20,15,25], labels: ['A','B','C','D'], color: '#f39c12', borderWidth: 2, size: 'medium' } }, 'previewHBar');
    renderChart({ type: 'doughnut', config: { title: 'Doughnut', data: [30,20,50], labels: ['Red','Blue','Yellow'], color: '#3498db', borderWidth: 2, size: 'medium' } }, 'previewDoughnut');
    renderChart({ type: 'pie', config: { title: 'Pie', data: [40,30,30], labels: ['A','B','C'], color: '#8e44ad', borderWidth: 2, size: 'medium' } }, 'previewPie');
    renderChart({ type: 'polar', config: { title: 'Polar Area', data: [11,16,7,3,14], labels: ['Red','Green','Yellow','Grey','Blue'], color: '#27ae60', borderWidth: 2, size: 'medium', colors: ['#e74c3c','#27ae60','#f1c40f','#95a5a6','#3498db'] } }, 'previewPolar');
    renderChart({ type: 'gauge', config: { title: 'Medidor', value: 60, min: 0, max: 100, color: '#3498db', borderWidth: 2, size: 'medium' } }, 'previewGauge');
  }

  /***** Cargar Panel Guardado al Iniciar *****/
  window.onload = function(){
    fetch('/panels/get', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    .then(response => response.json())
    .then(data => {
      widgetsData = data.widgetsData || [];
      renderFinalDashboard();
    })
    .catch(error => console.error('Error al cargar el panel:', error));
  }
 