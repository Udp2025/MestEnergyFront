  // Si el plugin Chartjs-Chart-Matrix no se registró automáticamente, se intenta registrar manualmente.
        if (typeof ChartMatrix !== 'undefined') {
            Chart.register(ChartMatrix.MatrixController, ChartMatrix.MatrixElement);
            console.log("MatrixController registrado vía ChartMatrix.");
        } else {
            console.log("No se encontró ChartMatrix; se asume que el plugin se auto-registra.");
        }

        /* ----- Funciones para el Árbol ----- */
        function toggleSelection(node, isSelected) {
            if (isSelected === undefined) {
                isSelected = !node.classList.contains('selected');
            }
            if (isSelected) {
                node.classList.add('selected');
            } else {
                node.classList.remove('selected');
            }
            const children = node.querySelectorAll(':scope > .tree-children > .tree-node');
            children.forEach(child => {
                toggleSelection(child, isSelected);
            });
        }

        function getSelectedSensors() {
            const sensors = [];
            document.querySelectorAll('#sensorTree .tree-node').forEach(node => {
                if (node.classList.contains('selected')) {
                    sensors.push(node.getAttribute('data-value'));
                }
            });
            if (sensors.length === 0) {
                sensors.push("Sensor1", "Sensor3", "Sensor4", "Sensor5", "Sensor2");
            }
            return sensors;
        }

        function getAdvancedOptions() {
            const options = {};
            document.querySelectorAll('#advancedTree .tree-node').forEach(node => {
                options[node.getAttribute('data-value')] = node.classList.contains('selected');
            });
            return options;
        }

        function attachTreeEvents() {
            document.querySelectorAll('.tree-list .tree-node').forEach(node => {
                const toggleIcon = node.querySelector('.toggle-icon');
                if (toggleIcon) {
                    toggleIcon.addEventListener('click', function(e) {
                        e.stopPropagation();
                        node.classList.toggle('expanded');
                        toggleIcon.textContent = node.classList.contains('expanded') ? '▼' : '►';
                    });
                }
                const label = node.querySelector('.node-label');
                if (label) {
                    label.addEventListener('click', function(e) {
                        e.stopPropagation();
                        toggleSelection(node);
                        updateChart();
                    });
                }
            });
        }

        attachTreeEvents();

        document.getElementById('viewSelect').addEventListener('change', updateChart);
        document.getElementById('dateInput').addEventListener('change', updateChart);

        /* ----- Heatmap con Diseño de Cuadrados ----- */
        let chart;
        const ctx = document.getElementById('heatmap').getContext('2d');

        // Paleta de colores basada en RGB(191,74,64)
        function getColor(value, viewType) {
            // Base RGB(191,74,64) - rojo terracota
            const baseColor = { r: 191, g: 74, b: 64 };
            
            // Verde más claro para valores bajos
            const lowColor = { r: 245, g: 190, b: 160 };
            
            // Amarillo/naranja para valores medios
            const midColor = { r: 238, g: 146, b: 121 };
            
            if (viewType === 'energy') {
                if (value > 2) {
                    return `rgba(${baseColor.r}, ${baseColor.g}, ${baseColor.b}, 0.85)`;
                } else if (value > 1) {
                    return `rgba(${midColor.r}, ${midColor.g}, ${midColor.b}, 0.8)`;
                } else {
                    return `rgba(${lowColor.r}, ${lowColor.g}, ${lowColor.b}, 0.8)`;
                }
            } else { // cost
                if (value > 8) {
                    return `rgba(${baseColor.r}, ${baseColor.g}, ${baseColor.b}, 0.85)`;
                } else if (value > 4) {
                    return `rgba(${midColor.r}, ${midColor.g}, ${midColor.b}, 0.8)`;
                } else {
                    return `rgba(${lowColor.r}, ${lowColor.g}, ${lowColor.b}, 0.8)`;
                }
            }
        }

        function generateMatrixData(date, sensors, viewType, advancedOptions) {
            const data = [];
            sensors.forEach((sensor) => {
                for (let hour = 0; hour < 24; hour++) {
                    let value = (viewType === 'energy')
                        ? parseFloat((Math.random() * 2.5).toFixed(2))
                        : parseFloat((Math.random() * 10).toFixed(2));
                    
                    let alertFlag = "";
                    if (advancedOptions.mostrar_alertas) {
                        if ((viewType === 'energy' && value > 2) || (viewType === 'cost' && value > 8)) {
                            alertFlag = "¡Alerta!";
                        }
                    }
                    
                    data.push({
                        x: hour,
                        y: sensor,
                        v: value,
                        alert: alertFlag
                    });
                }
            });
            return data;
        }

        function updateChart() {
            const viewType = document.getElementById('viewSelect').value;
            const date = document.getElementById('dateInput').value;
            const selectedSensors = getSelectedSensors();
            const advancedOptions = getAdvancedOptions();
            
            console.log("Actualizando heatmap con sensores:", selectedSensors, "Modo:", viewType, "Fecha:", date);
            
            // Generar datos principales
            const mainData = generateMatrixData(date, selectedSensors, viewType, advancedOptions);
            
            const datasets = [{
                label: `Datos Principales (${viewType})`,
                data: mainData,
                backgroundColor: function(context) {
                    const pt = context.dataset.data[context.dataIndex];
                    return getColor(pt.v, viewType);
                },
                borderColor: 'rgba(0,0,0,0.3)',
                borderWidth: 1,
                borderRadius: 6,
                width: function(context) {
                    const chart = context.chart;
                    const area = chart.chartArea || {};
                    const baseWidth = (area.right - area.left) / 24;
                    const baseHeight = (area.bottom - area.top) / selectedSensors.length;
                    const cellSize = Math.min(baseWidth, baseHeight);
                    return cellSize * 0.85;
                },
                height: function(context) {
                    const chart = context.chart;
                    const area = chart.chartArea || {};
                    const baseWidth = (area.right - area.left) / 24;
                    const baseHeight = (area.bottom - area.top) / selectedSensors.length;
                    const cellSize = Math.min(baseWidth, baseHeight);
                    return cellSize * 0.85;
                }
            }];
            
            // Modo Comparativo: agrega dataset comparativo
            if (advancedOptions.modo_comparativo) {
                const compData = generateMatrixData(date, selectedSensors, viewType, advancedOptions);
                compData.forEach(pt => {
                    let offset = (viewType === 'energy') ? (Math.random() * 0.2 - 0.1) : (Math.random() * 1 - 0.5);
                    pt.v = parseFloat((pt.v * (1 + offset)).toFixed(2));
                });
                const compDataset = {
                    label: `Comparativo (${viewType})`,
                    data: compData,
                    backgroundColor: function(context) {
                        const pt = context.dataset.data[context.dataIndex];
                        return getColor(pt.v, viewType);
                    },
                    borderColor: 'rgba(0,0,0,0.3)',
                    borderWidth: 1,
                    borderRadius: 6,
                    width: function(context) {
                        const chart = context.chart;
                        const area = chart.chartArea || {};
                        const baseWidth = (area.right - area.left) / 24;
                        const baseHeight = (area.bottom - area.top) / selectedSensors.length;
                        const cellSize = Math.min(baseWidth, baseHeight);
                        return cellSize * 0.85;
                    },
                    height: function(context) {
                        const chart = context.chart;
                        const area = chart.chartArea || {};
                        const baseWidth = (area.right - area.left) / 24;
                        const baseHeight = (area.bottom - area.top) / selectedSensors.length;
                        const cellSize = Math.min(baseWidth, baseHeight);
                        return cellSize * 0.85;
                    }
                };
                datasets.push(compDataset);
            }
            
            // Datos de respaldo: agrega dataset extra (color azul)
            if (advancedOptions.datos_respaldo) {
                const backupData = generateMatrixData(date, selectedSensors, viewType, advancedOptions);
                const backupDataset = {
                    label: `Respaldo (${viewType})`,
                    data: backupData,
                    backgroundColor: 'rgba(66, 133, 244, 0.7)',
                    borderColor: 'rgba(0,0,0,0.3)',
                    borderWidth: 1,
                    borderRadius: 6,
                    width: function(context) {
                        const chart = context.chart;
                        const area = chart.chartArea || {};
                        const baseWidth = (area.right - area.left) / 24;
                        const baseHeight = (area.bottom - area.top) / selectedSensors.length;
                        const cellSize = Math.min(baseWidth, baseHeight);
                        return cellSize * 0.85;
                    },
                    height: function(context) {
                        const chart = context.chart;
                        const area = chart.chartArea || {};
                        const baseWidth = (area.right - area.left) / 24;
                        const baseHeight = (area.bottom - area.top) / selectedSensors.length;
                        const cellSize = Math.min(baseWidth, baseHeight);
                        return cellSize * 0.85;
                    }
                };
                datasets.push(backupDataset);
            }
            
            if (chart) chart.destroy();
            chart = new Chart(ctx, {
                type: 'matrix',
                data: { datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            type: 'linear',
                            min: 0,
                            max: 24,
                            offset: true,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return value + ':00';
                                },
                                font: {
                                    size: 12
                                }
                            },
                            title: { 
                                display: true, 
                                text: 'Hora del Día',
                                font: { 
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            grid: { 
                                display: false 
                            }
                        },
                        y: {
                            type: 'category',
                            labels: selectedSensors,
                            offset: true,
                            title: { 
                                display: true, 
                                text: 'Sensores',
                                font: { 
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            grid: { 
                                display: false 
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            titleFont: { 
                                size: 14, 
                                weight: 'bold' 
                            },
                            bodyFont: { 
                                size: 13 
                            },
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                title: function(tooltipItems) {
                                    const data = tooltipItems[0].dataset.data[tooltipItems[0].dataIndex];
                                    return `Hora: ${Math.floor(data.x)}:00 | Sensor: ${data.y}`;
                                },
                                label: function(context) {
                                    const data = context.dataset.data[context.dataIndex];
                                    let label = `Valor: ${data.v}`;
                                    if (data.alert) label += ` | ${data.alert}`;
                                    return label;
                                }
                            }
                        },
                        legend: { 
                            position: 'top',
                            labels: {
                                font: { 
                                    size: 13 
                                },
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'rectRounded'
                            }
                        }
                    },
                    elements: {
                        matrix: {
                            spacing: 0.15
                        }
                    }
                }
            });
        }

        // Llamamos a updateChart() para mostrar el heatmap al cargar la página.
        updateChart();