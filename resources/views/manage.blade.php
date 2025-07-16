@extends('layouts.complete')

@section('title', 'Manage')

@section('content')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Eventos</title>
    <link rel="stylesheet" href="{{ asset('css/manage.css') }}">

</head>
<body>
    <div class="container">
        <h1>Administrar Eventos</h1>
        <div class="controls">
            <div class="search-bar">
                <input type="text" id="search" placeholder="Buscar eventos...">
            </div>
            <div class="filter-icon">
                <img src="https://img.icons8.com/material-outlined/24/000000/filter.png" alt="Filtrar" title="Filtrar">
            </div>
        </div>
        <div class="pagination">
            <span id="currentPage">Página 1 de 2</span>
            <span>Mostrando 20 de 25 Eventos</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Hora de inicio</th>
                    <th>Hora de fin</th>
                    <th>Tipo de evento</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody id="eventTable">
                <tr>
                    <td>Inicio</td>
                    <td>31-Jul-2023 08:30</td>
                    <td>31-Jul-2023 09:00</td>
                    <td>Incidente</td>
                    <td>Inicio</td>
                </tr>
                <tr>
                    <td>Reunión de equipo</td>
                    <td>01-Ago-2023 10:00</td>
                    <td>01-Ago-2023 11:00</td>
                    <td>Reunión</td>
                    <td>Discusión semanal del equipo</td>
                </tr>
                <tr>
                    <td>Mantenimiento</td>
                    <td>02-Ago-2023 14:00</td>
                    <td>02-Ago-2023 15:30</td>
                    <td>Tarea</td>
                    <td>Actualización del sistema</td>
                </tr>
                <tr>
                    <td>Webinar</td>
                    <td>03-Ago-2023 09:00</td>
                    <td>03-Ago-2023 10:00</td>
                    <td>Evento</td>
                    <td>Capacitación en línea</td>
                </tr>
                <tr>
                    <td>Almuerzo de negocios</td>
                    <td>04-Ago-2023 12:00</td>
                    <td>04-Ago-2023 13:00</td>
                    <td>Reunión</td>
                    <td>Discusión con cliente</td>
                </tr>
                <tr>
                    <td>Presentación de proyecto</td>
                    <td>05-Ago-2023 16:00</td>
                    <td>05-Ago-2023 17:00</td>
                    <td>Evento</td>
                    <td>Revisión del proyecto final</td>
                </tr>
                <tr>
                    <td>Reunión de seguimiento</td>
                    <td>06-Ago-2023 11:00</td>
                    <td>06-Ago-2023 12:00</td>
                    <td>Reunión</td>
                    <td>Progreso del proyecto</td>
                </tr>
                <tr>
                    <td>Capacitación interna</td>
                    <td>07-Ago-2023 09:30</td>
                    <td>07-Ago-2023 10:30</td>
                    <td>Evento</td>
                    <td>Formación de empleados</td>
                </tr>
                <tr>
                    <td>Auditoría</td>
                    <td>08-Ago-2023 13:00</td>
                    <td>08-Ago-2023 14:30</td>
                    <td>Incidente</td>
                    <td>Revisión de procesos</td>
                </tr>
                <tr>
                    <td>Evaluación trimestral</td>
                    <td>09-Ago-2023 15:00</td>
                    <td>09-Ago-2023 16:00</td>
                    <td>Reunión</td>
                    <td>Rendimiento del equipo</td>
                </tr>
                <tr>
                    <td>Evaluación trimestral</td>
                    <td>09-Ago-2023 15:00</td>
                    <td>09-Ago-2023 16:00</td>
                    <td>Reunión</td>
                    <td>Rendimiento del equipo</td>
                </tr>
                <tr>
                    <td>Evaluación trimestral</td>
                    <td>09-Ago-2023 15:00</td>
                    <td>09-Ago-2023 16:00</td>
                    <td>Reunión</td>
                    <td>Rendimiento del equipo</td>
                </tr>
                <tr>
                    <td>Evaluación trimestral</td>
                    <td>09-Ago-2023 15:00</td>
                    <td>09-Ago-2023 16:00</td>
                    <td>Reunión</td>
                    <td>Rendimiento del equipo</td>
                </tr>
                <tr>
                    <td>Evaluación trimestral</td>
                    <td>09-Ago-2023 15:00</td>
                    <td>09-Ago-2023 16:00</td>
                    <td>Reunión</td>
                    <td>Rendimiento del equipo</td>
                </tr>
                <tr>
                    <td>Evaluación trimestral</td>
                    <td>09-Ago-2023 15:00</td>
                    <td>09-Ago-2023 16:00</td>
                    <td>Reunión</td>
                    <td>Rendimiento del equipo</td>
                </tr>
                <tr>
                    <td>Evaluación trimestral</td>
                    <td>09-Ago-2023 15:00</td>
                    <td>09-Ago-2023 16:00</td>
                    <td>Reunión</td>
                    <td>Rendimiento del equipo</td>
                </tr>
                <tr>
                    <td>Evaluación trimestral</td>
                    <td>09-Ago-2023 15:00</td>
                    <td>09-Ago-2023 16:00</td>
                    <td>Reunión</td>
                    <td>Rendimiento del equipo</td>
                </tr>
                <tr>
                    <td>Evaluación trimestral</td>
                    <td>09-Ago-2023 15:00</td>
                    <td>09-Ago-2023 16:00</td>
                    <td>Reunión</td>
                    <td>Rendimiento del equipo</td>
                </tr>
                <!-- Agrega más filas aquí -->
            </tbody>
        </table>
        <div class="pagination">
            <button id="prevPage">Anterior</button>
            <button id="nextPage">Siguiente</button>
        </div>
    </div> 
</body>
</html>
<script src="{{ asset('js/manage.js') }}"></script>

@endsection
