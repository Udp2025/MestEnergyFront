// -----------------------------
// 1. CONFIGURACIÓN GLOBAL CHART.JS
// -----------------------------

// Establecemos las fuentes y colores base para que concuerden con el CSS.
Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
Chart.defaults.font.size = 14;
Chart.defaults.color = '#2c3e50';           // Mismo color-text-primary
Chart.defaults.elements.line.borderWidth = 2;
Chart.defaults.elements.point.radius = 4;
Chart.defaults.elements.point.hoverRadius = 6;
Chart.defaults.elements.point.backgroundColor = '#1abc9c'; // color-accent
Chart.defaults.elements.point.borderColor = '#ffffff';
Chart.defaults.plugins.legend.labels.color = '#2c3e50';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.7)';
Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
Chart.defaults.plugins.tooltip.bodyColor = '#ffffff';

// Colores basados en variables CSS:
// --color-accent:      #1abc9c
// --color-accent-dark: #16a085
// --color-border:      #e6e9ec
// --color-text-secondary: #607d8b

// Función auxiliar para líneas de rejilla muy tenues
function gridOptions() {
  return {
    grid: {
      color: '#e6e9ec',     // color-border
      lineWidth: 1,
      drawBorder: false
    },
    ticks: {
      color: '#607d8b',     // color-text-secondary
      padding: 8
    }
  };
}

// -----------------------------
// 2. GRÁFICO: Optimización de Energía (Diario) - Línea
// -----------------------------
const ctxDailyOptimization = document
  .getElementById('dailyOptimizationChart')
  .getContext('2d');

const dailyOptimizationChart = new Chart(ctxDailyOptimization, {
  type: 'line',
  data: {
    labels: ['6:00', '9:00', '12:00', '15:00', '18:00', '21:00'],
    datasets: [
      {
        label: 'Optimización (%)',
        data: [70, 75, 80, 78, 82, 80],
        backgroundColor: 'rgba(26, 188, 156, 0.2)',    // color-accent con transparencia
        borderColor: '#1abc9c',                          // color-accent
        borderWidth: 2,
        tension: 0.4,
        pointStyle: 'circle',
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#1abc9c',
        pointHoverBackgroundColor: '#16a085',            // color-accent-dark
        pointHoverBorderColor: '#ffffff',
        fill: true
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
        labels: { boxWidth: 12, boxHeight: 12 }
      }
    },
    scales: {
      x: {
        ...gridOptions(),
        border: {
          display: false
        }
      },
      y: {
        ...gridOptions(),
        border: {
          display: false
        },
        beginAtZero: true,
        max: 100
      }
    }
  }
});

// -----------------------------
// 3. GRÁFICO: Comparación de Ahorro (Barras)
// -----------------------------
const ctxSavingsComparison = document
  .getElementById('savingsComparisonChart')
  .getContext('2d');

const savingsComparisonChart = new Chart(ctxSavingsComparison, {
  type: 'bar',
  data: {
    labels: ['Oficina', 'Producción', 'Almacén', 'Exterior'],
    datasets: [
      {
        label: 'Ahorro (kWh)',
        data: [15, 20, 10, 5],
        backgroundColor: [
          'rgba(26, 188, 156, 0.7)',   // un solo color-accent para todas las barras
          'rgba(26, 188, 156, 0.7)',
          'rgba(26, 188, 156, 0.7)',
          'rgba(26, 188, 156, 0.7)'
        ],
        borderColor: [
          '#1abc9c',
          '#1abc9c',
          '#1abc9c',
          '#1abc9c'
        ],
        borderWidth: 1,
        borderRadius: 8,           // esquinas redondeadas en columnas
        maxBarThickness: 40
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false
      }
    },
    scales: {
      x: {
        ...gridOptions(),
        border: {
          display: false
        }
      },
      y: {
        ...gridOptions(),
        border: {
          display: false
        },
        beginAtZero: true
      }
    }
  }
});

// -----------------------------
// 4. GRÁFICO: Historial Semanal de Ahorro (Línea con 2 datasets)
// -----------------------------
const ctxWeeklySavings = document
  .getElementById('weeklySavingsChart')
  .getContext('2d');

const weeklySavingsChart = new Chart(ctxWeeklySavings, {
  type: 'line',
  data: {
    labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
    datasets: [
      {
        label: 'Ahorro (kWh)',
        data: [20, 25, 22, 28, 26, 24, 23],
        backgroundColor: 'rgba(39, 174, 96, 0.2)',   // verde suave ligeramente diferente
        borderColor: '#27ae60',
        borderWidth: 2,
        tension: 0.4,
        pointStyle: 'circle',
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#27ae60',
        pointHoverBackgroundColor: '#2ecc71',
        pointHoverBorderColor: '#ffffff',
        fill: true
      },
      {
        label: 'Costo ($)',
        data: [50, 55, 53, 60, 58, 56, 54],
        backgroundColor: 'rgba(52, 152, 219, 0.2)',  // azul suave con transparencia
        borderColor: '#3498db',
        borderWidth: 2,
        tension: 0.4,
        pointStyle: 'circle',
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#3498db',
        pointHoverBackgroundColor: '#2980b9',
        pointHoverBorderColor: '#ffffff',
        fill: true
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
        labels: { boxWidth: 12, boxHeight: 12 }
      }
    },
    scales: {
      x: {
        ...gridOptions(),
        border: {
          display: false
        }
      },
      y: {
        ...gridOptions(),
        border: {
          display: false
        },
        beginAtZero: true
      }
    }
  }
});

// -----------------------------
// 5. GRÁFICO: Optimización por Dispositivo (Barras agrupadas)
// -----------------------------
const ctxDeviceOptimization = document
  .getElementById('deviceOptimizationChart')
  .getContext('2d');

const deviceOptimizationChart = new Chart(ctxDeviceOptimization, {
  type: 'bar',
  data: {
    labels: ['Sensor A', 'Sensor B', 'Sensor C', 'Sensor D'],
    datasets: [
      {
        label: 'Optimización (%)',
        data: [80, 85, 78, 82],
        backgroundColor: 'rgba(231, 76, 60, 0.7)',   // rojo suave para distinguir
        borderColor: '#e74c3c',
        borderWidth: 1,
        borderRadius: 8,
        maxBarThickness: 30
      },
      {
        label: 'Uso (kWh)',
        data: [12, 15, 11, 14],
        backgroundColor: 'rgba(241, 196, 15, 0.7)',  // amarillo tenue
        borderColor: '#f1c40f',
        borderWidth: 1,
        borderRadius: 8,
        maxBarThickness: 30
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
        labels: { boxWidth: 12, boxHeight: 12 }
      }
    },
    scales: {
      x: {
        ...gridOptions(),
        stacked: false,
        border: { display: false }
      },
      y: {
        ...gridOptions(),
        stacked: false,
        border: { display: false },
        beginAtZero: true
      }
    }
  }
});
