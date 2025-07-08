// ----------------------------------------
// 1. CONFIGURACIÓN GLOBAL CHART.JS
// ----------------------------------------

// Fuentes y colores base según nuestro CSS
Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
Chart.defaults.font.size = 14;
Chart.defaults.color = '#914C2E';                // text-primary
Chart.defaults.elements.line.borderWidth = 2;
Chart.defaults.elements.point.radius = 4;
Chart.defaults.elements.point.hoverRadius = 6;
Chart.defaults.elements.point.backgroundColor = '#914C2E';  // color-accent
Chart.defaults.elements.point.borderColor = '#ffffff';
Chart.defaults.plugins.legend.labels.color = '#914C2E';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.7)';
Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
Chart.defaults.plugins.tooltip.bodyColor = '#ffffff';


       async function fetchEnergyData() {
    try {
        const response = await fetch('/api/energy-data');
        
        // Verificar si la respuesta es OK (status 200-299)
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! Status: ${response.status}, Response: ${errorText}`);
        }

        // Intentar parsear como JSON
        try {
            const data = await response.json();
            console.log('Datos de energía:', data);
            // Aquí puedes procesar los datos para los gráficos
        } catch (jsonError) {
            // Si falla el parseo JSON, obtener el texto plano
            const rawResponse = await response.text();
            console.error('Error parsing JSON:', jsonError);
            console.error('Raw response:', rawResponse);
        }
    } catch (error) {
        console.error('Error al obtener datos:', error);
    }
}

fetchEnergyData();


// Función auxiliar para ejes con líneas de rejilla muy tenues
function gridOptions() {
  return {
    grid: {
      color: '#E8DAD4',   // color-surface muy tenue
      lineWidth: 1,
      drawBorder: false
    },
    ticks: {
      color: '#A77058',   // text-secondary
      padding: 6
    }
  };
}


// ----------------------------------------
// 2. GRÁFICO: Hourly Consumption (Línea)
// ----------------------------------------
const ctxHourly = document.getElementById('hourlyChart').getContext('2d');
const hourlyChart = new Chart(ctxHourly, {
  type: 'line',
  data: {
    labels: ['12 AM','1 AM','2 AM','3 AM','4 AM','5 AM','6 AM','7 AM','8 AM','9 AM','10 AM','11 AM'],
    datasets: [
      {
        label: 'Hourly Consumption (kWh)',
        data: [0.8,1.1,0.9,1.5,1.3,1.7,2.0,2.3,2.1,1.8,1.6,1.9],
        borderColor: '#914C2E',                    // color-accent
        backgroundColor: 'rgba(145,76,46,0.2)',     // color-accent transparente
        tension: 0.4,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#914C2E',
        pointHoverBackgroundColor: '#A77058',       // color-accent-dark
        fill: true
      },
      {
        label: 'Hourly Cost ($)',
        data: [0.1,0.2,0.15,0.25,0.2,0.3,0.35,0.4,0.38,0.3,0.28,0.35],
        borderColor: '#A77058',                     // color-accent-dark
        backgroundColor: 'rgba(167,112,88,0.2)',    // color-accent-dark transparente
        tension: 0.4,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#A77058',
        pointHoverBackgroundColor: '#914C2E',       // invertir hover
        fill: true
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      x: {
        ...gridOptions(),
        border: { display: false }
      },
      y: {
        ...gridOptions(),
        border: { display: false },
        beginAtZero: true,
        max: 3,
        ticks: {
          stepSize: 0.5,
          callback: value => value + ' kWh'
        }
      }
    },
    plugins: {
      legend: {
        position: 'top',
        labels: { boxWidth: 12, boxHeight: 12, padding: 10 }
      },
      tooltip: {
        backgroundColor: '#333',
        bodyColor: '#fff',
        borderColor: '#914C2E',
        borderWidth: 2,
        titleFont: { size: 14 }
      }
    }
  }
});

// Permitir hacer clic en la leyenda para ocultar/mostrar series
hourlyChart.options.plugins.legend.onClick = (e, legendItem) => {
  const idx = legendItem.datasetIndex;
  const meta = hourlyChart.getDatasetMeta(idx);
  meta.hidden = !meta.hidden;
  hourlyChart.update();
};


// ----------------------------------------
// 3. GRÁFICO: Monthly Trends (Barras)
// ----------------------------------------
const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(ctxMonthly, {
  type: 'bar',
  data: {
    labels: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
    datasets: [
      {
        label: 'Monthly Consumption (kWh)',
        data: [1200,1300,1150,1400,1500,1250,1300,1350,1400,1450,1550,1600],
        backgroundColor: '#914C2E',       // color-accent
        borderColor: '#A77058',           // color-accent-dark
        borderWidth: 1,
        borderRadius: 6,
        maxBarThickness: 32
      },
      {
        label: 'Monthly Cost ($)',
        data: [120,130,115,140,150,125,130,135,140,145,155,160],
        backgroundColor: '#A77058',       // color-accent-dark
        borderColor: '#914C2E',
        borderWidth: 1,
        borderRadius: 6,
        maxBarThickness: 32
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      x: {
        ...gridOptions(),
        border: { display: false }
      },
      y: {
        ...gridOptions(),
        border: { display: false },
        beginAtZero: true,
        ticks: { stepSize: 200 }
      }
    },
    plugins: {
      legend: {
        position: 'top',
        labels: { boxWidth: 12, boxHeight: 12, padding: 10 }
      },
      tooltip: {
        backgroundColor: '#333',
        bodyColor: '#fff',
        borderColor: '#914C2E',
        borderWidth: 2,
        titleFont: { size: 14 }
      }
    }
  }
});

// Toggle de series
monthlyChart.options.plugins.legend.onClick = (e, legendItem) => {
  const idx = legendItem.datasetIndex;
  const meta = monthlyChart.getDatasetMeta(idx);
  meta.hidden = !meta.hidden;
  monthlyChart.update();
};


// ----------------------------------------
// 4. GRÁFICO: Energy Sources (Área)
// ----------------------------------------
const ctxSources = document.getElementById('sourcesChart').getContext('2d');
const sourcesChart = new Chart(ctxSources, {
  type: 'line',
  data: {
    labels: ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],
    datasets: [
      {
        label: 'Solar Energy',
        data: [80,90,100,95,110,120,130],
        fill: true,
        backgroundColor: 'rgba(145,76,46,0.2)',  // color-accent transparente
        borderColor: '#914C2E',
        borderWidth: 2,
        tension: 0.4,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#914C2E',
        pointHoverBackgroundColor: '#A77058'
      },
      {
        label: 'Wind Energy',
        data: [60,70,80,85,90,100,110],
        fill: true,
        backgroundColor: 'rgba(167,112,88,0.2)', // color-accent-dark traslúcido
        borderColor: '#A77058',
        borderWidth: 2,
        tension: 0.4,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#A77058',
        pointHoverBackgroundColor: '#914C2E'
      },
      {
        label: 'Hydropower',
        data: [40,50,60,70,80,90,100],
        fill: true,
        backgroundColor: 'rgba(232,218,212,0.5)', // surface traslúcido
        borderColor: '#E8DAD4',
        borderWidth: 2,
        tension: 0.4,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#E8DAD4',
        pointHoverBackgroundColor: '#A77058'
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      x: {
        ...gridOptions(),
        border: { display: false },
        ticks: { autoSkip: true, maxRotation: 45, minRotation: 45 }
      },
      y: {
        ...gridOptions(),
        border: { display: false },
        beginAtZero: true,
        ticks: { stepSize: 20, callback: v => v + ' kWh' }
      }
    },
    plugins: {
      legend: {
        position: 'top',
        labels: { boxWidth: 12, boxHeight: 12, padding: 10 }
      },
      tooltip: {
        backgroundColor: '#333',
        bodyColor: '#fff',
        borderColor: '#914C2E',
        borderWidth: 2,
        titleFont: { size: 14 }
      }
    }
  }
});

// Toggle de series
sourcesChart.options.plugins.legend.onClick = (e, legendItem) => {
  const idx = legendItem.datasetIndex;
  const meta = sourcesChart.getDatasetMeta(idx);
  meta.hidden = !meta.hidden;
  sourcesChart.update();
};


// ----------------------------------------
// 5. GRÁFICO: Weekly Comparison (Radar)
// ----------------------------------------
const ctxComparison = document.getElementById('comparisonChart').getContext('2d');
const comparisonChart = new Chart(ctxComparison, {
  type: 'radar',
  data: {
    labels: ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],
    datasets: [
      {
        label: 'This Week',
        data: [100,120,130,90,150,110,140],
        backgroundColor: 'rgba(145,76,46,0.2)',  // color-accent
        borderColor: '#914C2E',
        borderWidth: 2,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#914C2E',
        pointHoverBackgroundColor: '#A77058'
      },
      {
        label: 'Last Week',
        data: [80,100,110,70,120,95,130],
        backgroundColor: 'rgba(167,112,88,0.2)', // color-accent-dark
        borderColor: '#A77058',
        borderWidth: 2,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#A77058',
        pointHoverBackgroundColor: '#914C2E'
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      r: {
        angleLines: { color: '#E8DAD4' },
        grid:       { color: '#E8DAD4' },
        pointLabels:{ color: '#A77058', font: { size: 12 } },
        suggestedMin: 0,
        suggestedMax: 200,
        ticks: {
          color: '#A77058',
          backdropColor: 'rgba(255,255,255,0.8)',
          padding: 6
        }
      }
    },
    plugins: {
      legend: {
        position: 'top',
        labels: { boxWidth: 12, boxHeight: 12, padding: 10 }
      },
      tooltip: {
        backgroundColor: '#333',
        bodyColor: '#fff',
        borderColor: '#914C2E',
        borderWidth: 2,
        titleFont: { size: 14 }
      }
    }
  }
});
