@extends('layouts.complete')

@section('title', 'Manage')

@section('content')
<div class="container">
    <h1>Administrar Eventos</h1>
    
    <!-- Notificaciones -->
    <div id="notification" class="notification" style="display: none;"></div>
    
    <!-- Botón para abrir modal de creación -->
    <div class="controls">
        <div class="search-bar">
            <form method="GET" action="{{ route('manage') }}" id="searchForm">
                <input type="text" 
                       name="search" 
                       id="search" 
                       placeholder="Buscar eventos..."
                       value="{{ request('search') }}">
                <button type="submit">Buscar</button>
                @if(request('search'))
                    <a href="{{ route('manage') }}" class="clear-search">Limpiar búsqueda</a>
                @endif
            </form>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openEventModal()">
                + Nuevo Evento
            </button>
        </div>
    </div>
    
    <!-- Información de paginación -->
    <div class="pagination-info">
        <span>Mostrando {{ $events->firstItem() }} - {{ $events->lastItem() }} de {{ $events->total() }} Eventos</span>
    </div>
    
    <!-- Tabla de eventos -->
    <table>
        <thead>
            <tr>
                <th>Título</th>
                <th>Hora de inicio</th>
                <th>Hora de fin</th>
                <th>Tipo de evento</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="eventTable">
            @forelse($events as $event)
                <tr data-event-id="{{ $event->id }}">
                    <td>{{ $event->title }}</td>
                    <td>{{ $event->start_time->format('d-M-Y H:i') }}</td>
                    <td>{{ $event->end_time->format('d-M-Y H:i') }}</td>
                    <td>{{ $event->event_type }}</td>
                    <td>{{ Str::limit($event->description, 50) }}</td>
                    <td class="actions-cell">
                        <button type="button" 
                                class="btn-edit" 
                                title="Editar"
                                onclick="editEvent({{ $event->id }})">
                            ✏️
                        </button>
                        <button type="button" 
                                class="btn-delete" 
                                title="Eliminar"
                                onclick="deleteEvent({{ $event->id }})">
                            🗑️
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">
                        No hay eventos registrados.
                        <a href="javascript:void(0)" onclick="openEventModal()">Crea el primero</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <!-- Paginación -->
    <div class="pagination">
        {{ $events->appends(request()->query())->links() }}
    </div>
</div>

<!-- Modal para crear/editar evento -->
<div id="eventModal" class="modal" style="overflow: auto;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Nuevo Evento</h2>
            <span class="close" onclick="closeEventModal()">&times;</span>
        </div>
        <form id="eventForm" method="POST">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="event_id" id="event_id" value="">
            
            <div class="form-group">
                <label for="title">Título *</label>
                <input type="text" 
                       name="title" 
                       id="title" 
                       class="form-control" 
                       required>
                <div class="error-message" id="title_error"></div>
            </div>
            
            <div class="form-group">
                <label for="start_time">Hora de inicio *</label>
                <input type="datetime-local" 
                       name="start_time" 
                       id="start_time" 
                       class="form-control"
                       required>
                <div class="error-message" id="start_time_error"></div>
            </div>
            
            <div class="form-group">
                <label for="end_time">Hora de fin *</label>
                <input type="datetime-local" 
                       name="end_time" 
                       id="end_time" 
                       class="form-control"
                       required>
                <div class="error-message" id="end_time_error"></div>
            </div>
            
            <div class="form-group">
                <label for="event_type">Tipo de evento *</label>
                <select name="event_type" id="event_type" class="form-control" required>
                    <option value="">Seleccionar...</option>
                    <option value="Incidente">Incidente</option>
                    <option value="Reunión">Reunión</option>
                    <option value="Tarea">Tarea</option>
                    <option value="Evento">Evento</option>
                </select>
                <div class="error-message" id="event_type_error"></div>
            </div>
            
            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea name="description" 
                         id="description" 
                         class="form-control" 
                         rows="4"></textarea>
                <div class="error-message" id="description_error"></div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span id="submitButtonText">Guardar Evento</span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeEventModal()">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Confirmar Eliminación</h2>
            <span class="close" onclick="closeConfirmModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>¿Estás seguro de que deseas eliminar este evento? Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                Sí, eliminar
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">
                Cancelar
            </button>
        </div>
    </div>
</div>

<style>
/* Estilos generales */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Notificaciones */
.notification {
    padding: 12px 20px;
    margin-bottom: 20px;
    border-radius: 4px;
    font-weight: 500;
    animation: slideDown 0.3s ease;
}
.notification.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.notification.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.notification.info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* Controles */
.controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}
.search-bar form {
    display: flex;
    align-items: center;
    gap: 10px;
}
.search-bar input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 300px;
}
.search-bar button {
    padding: 8px 16px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.clear-search {
    color: #666;
    text-decoration: none;
    font-size: 14px;
}
.clear-search:hover {
    text-decoration: underline;
}

/* Botones */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.3s;
}
.btn-primary {
    background-color: #28a745;
    color: white;
}
.btn-primary:hover {
    background-color: #218838;
}
.btn-secondary {
    background-color: #6c757d;
    color: white;
}
.btn-secondary:hover {
    background-color: #5a6268;
}
.btn-danger {
    background-color: #dc3545;
    color: white;
}
.btn-danger:hover {
    background-color: #c82333;
}

/* Tabla */
table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background-color: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
th, td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
}
tr:hover {
    background-color: #f8f9fa;
}
.actions-cell {
    display: flex;
    gap: 10px;
    min-width: 100px;
}
.btn-edit, .btn-delete {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    padding: 5px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}
.btn-edit:hover {
    background-color: #e9ecef;
    color: #007bff;
}
.btn-delete:hover {
    background-color: #e9ecef;
    color: #dc3545;
}

/* Paginación */
.pagination {
    margin-top: 20px;
    display: flex;
    justify-content: center;
}
.pagination-info {
    margin: 10px 0;
    color: #666;
    font-size: 14px;
}
.pagination .flex {
    display: flex;
    gap: 10px;
}
.pagination a, .pagination span {
    padding: 8px 12px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
    border-radius: 4px;
}
.pagination a:hover {
    background-color: #f0f0f0;
}
.pagination .active span {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}
.pagination .disabled span {
    color: #6c757d;
    background-color: #e9ecef;
    border-color: #ddd;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s ease;
}
.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    width: 90%;
    max-width: 600px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    animation: slideUp 0.3s ease;
}
.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h2 {
    margin: 0;
    color: #333;
}
.close {
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
}
.close:hover {
    color: #333;
}
.modal-body {
    padding: 20px;
}
.modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Formulario */
#eventForm {
    padding: 20px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
}
.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s;
}
.form-control:focus {
    outline: none;
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}
.error-message {
    color: #dc3545;
    font-size: 12px;
    margin-top: 5px;
    min-height: 18px;
}
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
@keyframes slideDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Responsive */
@media (max-width: 768px) {
    .controls {
        flex-direction: column;
        align-items: stretch;
    }
    .search-bar form {
        flex-direction: column;
        align-items: stretch;
    }
    .search-bar input {
        width: 100%;
    }
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
    table {
        display: block;
        overflow-x: auto;
    }
}
</style>

<script>
// Variables globales
let eventIdToDelete = null;

// CSRF Token para peticiones AJAX
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Función para mostrar notificaciones
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.style.display = 'block';
    
    // Ocultar después de 5 segundos
    setTimeout(() => {
        notification.style.display = 'none';
    }, 5000);
}

// Funciones para el modal de evento
function openEventModal(eventId = null) {
    const modal = document.getElementById('eventModal');
    const form = document.getElementById('eventForm');
    const modalTitle = document.getElementById('modalTitle');
    const submitButtonText = document.getElementById('submitButtonText');
    
    // Limpiar errores previos
    clearErrors();
    
    if (eventId) {
        // Modo edición
        modalTitle.textContent = 'Editar Evento';
        submitButtonText.textContent = 'Actualizar Evento';
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('event_id').value = eventId;
        
        // Cargar datos del evento
        fetch(`/events/${eventId}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const event = data.event;
                    document.getElementById('title').value = event.title;
                    document.getElementById('start_time').value = formatDateTimeForInput(event.start_time);
                    document.getElementById('end_time').value = formatDateTimeForInput(event.end_time);
                    document.getElementById('event_type').value = event.event_type;
                    document.getElementById('description').value = event.description || '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al cargar el evento', 'error');
            });
    } else {
        // Modo creación
        modalTitle.textContent = 'Nuevo Evento';
        submitButtonText.textContent = 'Guardar Evento';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('event_id').value = '';
        
        // Limpiar formulario
        form.reset();
        
        // Establecer valores por defecto de fecha/hora
        const now = new Date();
        const nowStr = now.toISOString().slice(0, 16);
        const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000);
        const oneHourLaterStr = oneHourLater.toISOString().slice(0, 16);
        
        document.getElementById('start_time').value = nowStr;
        document.getElementById('end_time').value = oneHourLaterStr;
    }
    
    modal.style.display = 'block';
}

function closeEventModal() {
    const modal = document.getElementById('eventModal');
    modal.style.display = 'none';
    clearErrors();
}

function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.textContent = '';
    });
}

// Funciones para el modal de confirmación
function deleteEvent(eventId) {
    eventIdToDelete = eventId;
    const modal = document.getElementById('confirmModal');
    modal.style.display = 'block';
}

function confirmDelete() {
    if (!eventIdToDelete) return;
    
    fetch(`/events/${eventIdToDelete}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Eliminar la fila de la tabla
            const row = document.querySelector(`tr[data-event-id="${eventIdToDelete}"]`);
            if (row) {
                row.remove();
            }
            closeConfirmModal();
            
            // Si la tabla queda vacía, recargar la página
            const eventRows = document.querySelectorAll('#eventTable tr[data-event-id]');
            if (eventRows.length === 0) {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            showNotification('Error al eliminar el evento', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al eliminar el evento', 'error');
    });
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.style.display = 'none';
    eventIdToDelete = null;
}

// Función auxiliar para formatear fecha/hora
function formatDateTimeForInput(dateTimeStr) {
    const date = new Date(dateTimeStr);
    // Ajustar a zona horaria local
    const offset = date.getTimezoneOffset() * 60000;
    const localDate = new Date(date.getTime() - offset);
    return localDate.toISOString().slice(0, 16);
}

// Función para editar evento
function editEvent(eventId) {
    openEventModal(eventId);
}

// Manejar envío del formulario
document.getElementById('eventForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const eventId = document.getElementById('event_id').value;
    const method = document.getElementById('formMethod').value;
    
    // Determinar URL basada en si es creación o edición
    const url = eventId ? `/events/${eventId}` : '/events';
    
    // Limpiar errores previos
    clearErrors();
    
    // Mostrar indicador de carga
    const submitBtn = form.querySelector('.btn-primary');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span>Guardando...</span>';
    submitBtn.disabled = true;
    
    fetch(url, {
        method: method === 'PUT' ? 'POST' : 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            showNotification(data.message, 'success');
            closeEventModal();
            
            // Recargar la página después de un breve delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else if (data.errors) {
            // Mostrar errores de validación
            Object.keys(data.errors).forEach(field => {
                const errorElement = document.getElementById(`${field}_error`);
                if (errorElement) {
                    errorElement.textContent = data.errors[field][0];
                }
            });
            showNotification('Por favor corrige los errores del formulario', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        showNotification('Error al guardar el evento', 'error');
    });
});

// Configurar botón de confirmación de eliminación
document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);

// Cerrar modales al hacer clic fuera de ellos
window.onclick = function(event) {
    const eventModal = document.getElementById('eventModal');
    const confirmModal = document.getElementById('confirmModal');
    
    if (event.target === eventModal) {
        closeEventModal();
    }
    if (event.target === confirmModal) {
        closeConfirmModal();
    }
}

// Cerrar modales con Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEventModal();
        closeConfirmModal();
    }
});
</script>
@endsection