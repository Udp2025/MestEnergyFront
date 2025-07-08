
let events = [
    { title: "Inicio", start: "2023-07-31 08:30", end: "2023-07-31 09:00", type: "Incidente", description: "Inicio" },
    { title: "Reunión de equipo", start: "2023-08-01 10:00", end: "2023-08-01 11:00", type: "Reunión", description: "Discusión semanal del equipo" },
    { title: "Mantenimiento", start: "2023-08-02 14:00", end: "2023-08-02 15:30", type: "Tarea", description: "Actualización del sistema" },
    { title: "Webinar", start: "2023-08-03 09:00", end: "2023-08-03 10:00", type: "Evento", description: "Capacitación en línea" },
    { title: "Almuerzo de negocios", start: "2023-08-04 12:00", end: "2023-08-04 13:00", type: "Reunión", description: "Discusión con cliente" },
    { title: "Presentación de proyecto", start: "2023-08-05 16:00", end: "2023-08-05 17:00", type: "Evento", description: "Revisión del proyecto final" },
    { title: "Reunión de seguimiento", start: "2023-08-06 11:00", end: "2023-08-06 12:00", type: "Reunión", description: "Progreso del proyecto" },
    { title: "Capacitación interna", start: "2023-08-07 09:30", end: "2023-08-07 10:30", type: "Evento", description: "Formación de empleados" },
    { title: "Auditoría", start: "2023-08-08 13:00", end: "2023-08-08 14:30", type: "Incidente", description: "Revisión de procesos" },
    { title: "Evaluación trimestral", start: "2023-08-09 15:00", end: "2023-08-09 16:00", type: "Reunión", description: "Rendimiento del equipo" },
    // Agrega más eventos aquí si es necesario
];

let currentPage = 1;
let itemsPerPage = 10;

function displayPage(page) {
    let table = document.getElementById('eventTable');
    table.innerHTML = '';

    let start = (page - 1) * itemsPerPage;
    let end = start + itemsPerPage;
    let paginatedEvents = events.slice(start, end);

    paginatedEvents.forEach(function (event) {
        let row = document.createElement('tr');
        row.innerHTML = `
    <td>${event.title}</td>
    <td>${event.start}</td>
    <td>${event.end}</td>
    <td>${event.type}</td>
    <td>${event.description}</td>
`;
        table.appendChild(row);
    });

    document.getElementById('currentPage').textContent = `Página ${page} de ${Math.ceil(events.length / itemsPerPage)}`;
}

document.getElementById('prevPage').addEventListener('click', function () {
    if (currentPage > 1) {
        currentPage--;
        displayPage(currentPage);
    }
});

document.getElementById('nextPage').addEventListener('click', function () {
    if (currentPage < Math.ceil(events.length / itemsPerPage)) {
        currentPage++;
        displayPage(currentPage);
    }
});

// Funcionalidad de búsqueda
document.getElementById('search').addEventListener('input', function () {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#eventTable tr');

    rows.forEach(function (row) {
        let cells = row.querySelectorAll('td');
        let match = false;

        cells.forEach(function (cell) {
            if (cell.textContent.toLowerCase().includes(filter)) {
                match = true;
            }
        });

        if (match) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Funcionalidad del filtro por fechas
document.getElementById('filterToggle').addEventListener('click', function () {
    let filterContainer = document.getElementById('filterContainer');
    filterContainer.style.display = filterContainer.style.display === 'none' ? 'flex' : 'none';
});

document.getElementById('applyFilter').addEventListener('click', function () {
    let startDate = document.getElementById('startDate').value;
    let endDate = document.getElementById('endDate').value;
    filterEventsByDate(startDate, endDate);
});

document.getElementById('clearFilter').addEventListener('click', function () {
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    displayPage(currentPage);
});

function filterEventsByDate(startDate, endDate) {
    let filteredEvents = events.filter(function (event) {
        let eventStart = new Date(event.start);
        let eventEnd = new Date(event.end);
        return (!startDate || eventStart >= new Date(startDate)) && (!endDate || eventEnd <= new Date(endDate));
    });
    displayFilteredEvents(filteredEvents);
}

function displayFilteredEvents(filteredEvents) {
    let table = document.getElementById('eventTable');
    table.innerHTML = '';

    filteredEvents.forEach(function (event) {
        let row = document.createElement('tr');
        row.innerHTML = `
    <td>${event.title}</td>
    <td>${event.start}</td>
    <td>${event.end}</td>
    <td>${event.type}</td>
    <td>${event.description}</td>
`;
        table.appendChild(row);
    });

    document.getElementById('currentPage').textContent = `Mostrando ${filteredEvents.length} eventos filtrados`;
}

// Mostrar la primera página al cargar la página
displayPage(currentPage);

