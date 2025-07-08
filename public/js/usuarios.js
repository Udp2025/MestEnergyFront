     document.querySelectorAll('.expandable').forEach(item => {
        item.addEventListener('click', function () {
            const nestedList = this.querySelector('ul');
            if (nestedList) {
                nestedList.style.display = nestedList.style.display === 'block' ? 'none' : 'block';
            }
        });
    });

    const ctx = document.getElementById('energyChart').getContext('2d');
    let chart;

    function getSelectedSensors() {
        return [...document.querySelectorAll('.sensor:checked')].map(sensor => sensor.value);
    }

    function updateChart() {
        const type = document.getElementById('dataType').value;
        const range = document.getElementById('timeRange').value;
        const selectedSensors = getSelectedSensors();

        const dataSets = {
            power: {
                daily: [10, 20, 30, 40, 50],
                weekly: [50, 100, 150, 200, 250],
                monthly: [200, 400, 600, 800, 1000],
                yearly: [1000, 2000, 3000, 4000, 5000]
            },
            cost: {
                daily: [5, 15, 25, 35, 45],
                weekly: [55, 105, 155, 205, 255],
                monthly: [205, 405, 605, 805, 1005],
                yearly: [1005, 2005, 3005, 4005, 5005]
            }
        };

        const labels = ['00:00', '06:00', '12:00', '18:00', '24:00'];
        const newData = dataSets[type][range];
        
        if (chart) {
            chart.destroy();
        }

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: selectedSensors.length > 0 ? selectedSensors.map(sensor => ({
                    label: sensor,
                    borderColor: '#'+Math.floor(Math.random()*16777215).toString(16),
                    data: newData.map(v => v * (Math.random() + 0.5)),
                    fill: false
                })) : [{
                    label: type + ' Data',
                    borderColor: '#3498db',
                    data: newData,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    document.getElementById('dataType').addEventListener('change', updateChart);
    document.getElementById('timeRange').addEventListener('change', updateChart);
    document.querySelectorAll('.sensor').forEach(sensor => {
        sensor.addEventListener('change', updateChart);
    });

    updateChart();
 