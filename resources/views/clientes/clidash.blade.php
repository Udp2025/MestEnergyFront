@extends('layouts.app')

@section('title', 'Energy Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/usuarios.css') }}">

<div class="container">
    <div class="main-content">
        <h2>Energy Dashboard</h2>
        <div class="filters">
            <!-- Estos selects se pueden utilizar para filtrar aún más, 
                 por ahora se activan el refresco de la gráfica -->
            <select id="dataType">
                <option value="all">All</option>
                <option value="power">Power</option>
                <option value="cost">Cost</option>
            </select>
            <select id="timeRange">
                <option value="all">All Time</option>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
        </div>
        <div class="chart-container">
            <canvas id="energyChart"></canvas>
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

<!-- Incluimos Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Datos dinámicos pasados desde el controlador
    const datos = @json($datos);

    /**
     * Función para agrupar datos por sensor (usando la propiedad "tipo")
     * y filtrarlos según los sensores seleccionados.
     */
    function groupDataBySensor(selectedSensors) {
        let filteredData = datos;
        // Si se han seleccionado sensores, filtrar por el campo "tipo"
        if (selectedSensors.length > 0) {
            filteredData = datos.filter(d => selectedSensors.includes(d.tipo));
        }
        // Agrupar por "tipo"
        const grouped = {};
        filteredData.forEach(d => {
            const sensor = d.tipo;
            if (!grouped[sensor]) {
                grouped[sensor] = { labels: [], values: [] };
            }
            // Se asume que "fecha" viene en formato YYYY-MM-DD (o similar)
            grouped[sensor].labels.push(d.fecha);
            grouped[sensor].values.push(d.valor);
        });
        return grouped;
    }

    // Función para obtener los sensores seleccionados
    function getSelectedSensors() {
        return [...document.querySelectorAll('.sensor:checked')].map(sensor => sensor.value);
    }

    const ctx = document.getElementById('energyChart').getContext('2d');
    let chart;

    function updateChart() {
        const selectedSensors = getSelectedSensors();
        const groupedData = groupDataBySensor(selectedSensors);

        // Se crea un arreglo de etiquetas globales (únicas) a partir de los datos filtrados
        let labels = [];
        Object.values(groupedData).forEach(group => {
            group.labels.forEach(label => {
                if (!labels.includes(label)) {
                    labels.push(label);
                }
            });
        });
        // Ordenar las fechas (asumiendo formato ISO)
        labels = labels.sort();

        // Crear datasets para cada sensor agrupado
        const datasets = [];
        for (let sensor in groupedData) {
            // Mapear los valores según la fecha, rellenando con null si no hay dato en esa fecha
            const dataMap = {};
            groupedData[sensor].labels.forEach((label, idx) => {
                dataMap[label] = groupedData[sensor].values[idx];
            });
            const sensorData = labels.map(label => dataMap[label] || null);
            datasets.push({
                label: sensor,
                data: sensorData,
                borderColor: '#' + Math.floor(Math.random() * 16777215).toString(16),
                fill: false,
                tension: 0.3,
                pointRadius: 5,
                pointHoverRadius: 7
            });
        }

        // Si no se han seleccionado sensores o no hay datos filtrados, muestra todos los datos en un solo dataset
        if (datasets.length === 0) {
            const allValues = datos.map(d => d.valor);
            const allLabels = datos.map(d => d.fecha);
            datasets.push({
                label: 'Datos',
                data: allValues,
                borderColor: '#3498db',
                fill: false
            });
            labels = allLabels;
        }

        if (chart) {
            chart.destroy();
        }

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Fecha'
                        },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Valor'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Lógica para mostrar/ocultar los submenús en la barra lateral
    document.querySelectorAll('.expandable').forEach(item => {
        item.addEventListener('click', function () {
            const nestedList = this.querySelector('ul');
            if (nestedList) {
                nestedList.style.display = nestedList.style.display === 'block' ? 'none' : 'block';
            }
        });
    });

    // Agregar eventos a los selects y checkboxes para actualizar la gráfica
    document.getElementById('dataType').addEventListener('change', updateChart);
    document.getElementById('timeRange').addEventListener('change', updateChart);
    document.querySelectorAll('.sensor').forEach(sensor => {
        sensor.addEventListener('change', updateChart);
    });

    // Inicializar la gráfica
    updateChart();
</script>

@endsection
