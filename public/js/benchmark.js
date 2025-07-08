document.querySelectorAll('#sensor-list > li').forEach(item => {
    item.addEventListener('click', function () {
        const submenu = this.querySelector('.submenu');
        submenu.classList.toggle('display');
        if (submenu.classList.contains('display')) {
            submenu.style.display = 'block';
        } else {
            submenu.style.display = 'none';
        }
    });
});

const ctx = document.getElementById('energyChart').getContext('2d');
const energyChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Sensor de Luz A', 'Sensor de Luz B', 'Sensor de Luz C'],
        datasets: [{
            label: 'Consumo de Energía (MWh/kWh)',
            data: [6.21, 4.5, 7.3],
            backgroundColor: ['#ffa726', '#000000', '#ffffff'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Consumo de Energía por Sensor'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

const sensorList = document.getElementById('sensor-list');
const filterType = document.getElementById('filter-type');
const filterDate = document.getElementById('filter-date');
const normalizeBy = document.getElementById('normalize-by');
const showLine = document.getElementById('show-line');
const period = document.getElementById('period');

sensorList.addEventListener('change', function () {
    updateChart();
});

filterType.addEventListener('change', function () {
    updateChart();
});

filterDate.addEventListener('change', function () {
    updateChart();
});

normalizeBy.addEventListener('input', function () {
    updateChart();
});

showLine.addEventListener('change', function () {
    updateChart();
});

period.addEventListener('change', function () {
    updateChart();
});

function updateChart() {
    const selectedSensors = Array.from(sensorList.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
    const newData = getSensorData(selectedSensors);

    energyChart.data.labels = selectedSensors;
    energyChart.data.datasets[0].data = newData;

    if (showLine.checked) {
        energyChart.config.type = 'line';
    } else {
        energyChart.config.type = 'bar';
    }

    if (filterType.value === 'costo') {
        // Cambiar los datos para mostrar el costo en lugar de la energía
        energyChart.data.datasets[0].data = selectedSensors.map(sensor => getCostData(sensor));
        energyChart.data.datasets[0].label = 'Costo (USD)';
    } else {
        // Cambiar los datos para mostrar la energía
        energyChart.data.datasets[0].data = newData;
        energyChart.data.datasets[0].label = 'Consumo de Energía (MWh/kWh)';
    }

    energyChart.update();
}

function getSensorData(sensors) {
    // Aquí puedes agregar la lógica para obtener los datos de los sensores seleccionados
    const data = {
        'sensor1-op1': 6.21,
        'sensor1-op2': 2.5,
        'sensor2-op1': 4.5,
        'sensor2-op2': 3.2,
        'sensor3-op1': 7.3,
        'sensor3-op2': 5.1
        // Agrega más datos según sea necesario
    };
    return sensors.map(sensor => data[sensor] || 0);
}

function getCostData(sensor) {
    // Aquí puedes agregar la lógica para obtener los datos de costo para los sensores seleccionados
    const costData = {
        'sensor1-op1': 60,
        'sensor1-op2': 25,
        'sensor2-op1': 45,
        'sensor2-op2': 32,
        'sensor3-op1': 73,
        'sensor3-op2': 51
        // Agrega más datos según sea necesario
    };
    return costData[sensor] || 0;
}
