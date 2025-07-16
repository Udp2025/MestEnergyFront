@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<link rel="stylesheet" href="{{ asset('css/clientes.css') }}">

<div class="clientes-container">
    <div class="clientes-header">
        <h1 class="clientes-titulo">Clientes</h1>
    </div>
    <div class="clientes-header-dos">
        <button class="btn-crear-cliente" data-bs-toggle="modal" data-bs-target="#createClientModal">
            + Crear Cliente
        </button>
    </div>

    <div class="clientes-tabla-container">
        <table class="clientes-tabla">
            <thead >
                <tr>
                    <th class = "mest-color beige">Nombre</th>
                    <th class = "mest-color beige">Razón Social</th>
                    <th class = "mest-color beige">Correo</th>
                    <th class = "mest-color beige">Monitoreando desde</th>
                    <th class = "mest-color beige">Locaciones</th>
                    <th class = "mest-color beige">Áreas</th>
                    <th class = "mest-color beige">Medidores</th>
                    <th class = "mest-color beige">Reportes</th>
                    <th class = "mest-color beige">Estado</th>
                    <th class = "mest-color beige">Acciones</th>
                </tr>
            </thead>
            <tbody class="clientes-tabla-body">
                @foreach ($clientes as $cliente)
                <tr>
                    <td>
                    <a href="{{ route('clientes.show', ['cliente' => $cliente->id]) }}">
                        {{ $cliente->nombre }}
                    </a>
                    </td>
                    <td>{{ $cliente->razon_social }}</td>
                    <td>{{ $cliente->email }}</td>
                    <td>
                        @if ($cliente->user)
                        {{ $cliente->user->created_at->format('d/m/Y') }}
                        @else
                        Sin usuario
                        @endif
                    </td>
                    <td>{{ $cliente->locaciones->count() }}</td>
                    <td>{{ $cliente->areas->count() }}</td>
                    <td>{{ $cliente->medidores->count() }}</td>
                    <td>{{ $cliente->reportes->count() }}</td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" class="toggle-status" data-id="{{ $cliente->id }}" {{ $cliente->estado == 'Activo' ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton{{ $cliente->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                ...
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $cliente->id }}">
                                <li>
                                    <a class="dropdown-item" href="{{ route('clientes.show', $cliente) }}">
                                        <i class="fas fa-eye"></i> Ver Detalle
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('clientes.edit', $cliente) }}">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('clientes.destroy', $cliente) }}"
                                        onclick="event.preventDefault(); document.getElementById('delete-form-{{ $cliente->id }}').submit();">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <form id="delete-form-{{ $cliente->id }}" action="{{ route('clientes.destroy', $cliente) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para editar cliente -->
@foreach ($clientes as $cliente)
<div class="modal fade" id="editClientModal{{ $cliente->id }}" tabindex="-1" aria-labelledby="editClientModalLabel{{ $cliente->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Header del Modal -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editClientModalLabel{{ $cliente->id }}">
                    <i class="fas fa-edit"></i> Editar Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <!-- Formulario para editar cliente -->
            <form action="{{ route('clientes.update', $cliente) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- Nombre -->
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre:</label>
                            <input type="text" id="nombre" name="nombre" class="form-control rounded-pill" value="{{ $cliente->nombre }}" required>
                        </div>
                        <!-- Razón Social -->
                        <div class="col-md-6">
                            <label for="razon_social" class="form-label">Razón Social:</label>
                            <input type="text" id="razon_social" name="razon_social" class="form-control rounded-pill" value="{{ $cliente->razon_social }}" required>
                        </div>
                        <!-- Correo -->
                        <div class="col-md-6">
                            <label for="email" class="form-label">Correo:</label>
                            <input type="email" id="email" name="email" class="form-control rounded-pill" value="{{ $cliente->email }}" required>
                        </div>
                        <!-- Teléfono -->
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono:</label>
                            <input type="text" id="telefono" name="telefono" class="form-control rounded-pill" value="{{ $cliente->telefono }}" required>
                        </div>
                        <!-- Calle -->
                        <div class="col-md-6">
                            <label for="calle" class="form-label">Calle:</label>
                            <input type="text" id="calle" name="calle" class="form-control rounded-pill" value="{{ $cliente->calle }}" required>
                        </div>
                        <!-- Número -->
                        <div class="col-md-6">
                            <label for="numero" class="form-label">Número:</label>
                            <input type="text" id="numero" name="numero" class="form-control rounded-pill" value="{{ $cliente->numero }}" required>
                        </div>
                        <!-- Colonia -->
                        <div class="col-md-6">
                            <label for="colonia" class="form-label">Colonia:</label>
                            <input type="text" id="colonia" name="colonia" class="form-control rounded-pill" value="{{ $cliente->colonia }}" required>
                        </div>
                        <!-- Código Postal -->
                        <div class="col-md-6">
                            <label for="codigo_postal" class="form-label">Código Postal:</label>
                            <input type="number" id="codigo_postal" name="codigo_postal" class="form-control rounded-pill" value="{{ $cliente->codigo_postal }}" required>
                        </div>
                        <!-- Ciudad -->
                        <div class="col-md-6">
                            <label for="ciudad" class="form-label">Ciudad:</label>
                            <input type="text" id="ciudad" name="ciudad" class="form-control rounded-pill" value="{{ $cliente->ciudad }}" required>
                        </div>
                        <!-- Estado -->
                        <div class="col-md-6">
                            <label for="estado" class="form-label">Estado:</label>
                            <input type="text" id="estado" name="estado" class="form-control rounded-pill" value="{{ $cliente->estado }}" required>
                        </div>
                        <!-- País -->
                        <div class="col-md-6">
                            <label for="pais" class="form-label">País:</label>
                            <input type="text" id="pais" name="pais" class="form-control rounded-pill" value="{{ $cliente->pais }}" required>
                        </div>
                        <!-- Cambio de Dólar -->
                        <div class="col-md-6">
                            <label for="cambio_dolar" class="form-label">Cambio de Dólar:</label>
                            <input type="number" id="cambio_dolar" name="cambio_dolar" class="form-control rounded-pill" value="{{ $cliente->cambio_dolar }}" required>
                        </div>
                    </div>
                </div>
                <!-- Footer del Modal -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary rounded-pill">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- Modal para crear cliente -->
<div class="modal fade" id="createClientModal" tabindex="-1" aria-labelledby="createClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Header del Modal -->
            <div class="modal-header mest-color text-white ">
                <h5 class="modal-title" id="createClientModalLabel">
                    <i class="fas fa-user-plus"></i> Crear Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <!-- Formulario para crear cliente -->
            <form action="{{ route('clientes.store') }}" method="POST" id="clientesForm" class="mestbg" >
                @csrf
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- Nombre -->
                        <div class="col-md-6">
                            <label for="nombre" class="form-label mest-color-text">Nombre:</label>
                            <input type="text" id="nombre" name="nombre" class="form-control rounded-pill"  required>
                        </div>
                        <!-- Razón Social -->
                        <div class="col-md-6">
                            <label for="razon_social" class="form-label mest-color-text">Razón Social:</label>
                            <input type="text" id="razon_social" name="razon_social" class="form-control rounded-pill"  required>
                        </div>
                        <!-- Correo -->
                        <div class="col-md-6">
                            <label for="email" class="form-label mest-color-text">Correo:</label>
                            <input type="email" id="email" name="email" class="form-control rounded-pill"  required>
                        </div>
                        <!-- Teléfono -->
                        <div class="col-md-6">
                            <label for="telefono" class="form-label mest-color-text">Teléfono:</label>
                            <input type="text" id="telefono" name="telefono" class="form-control rounded-pill"  required>
                        </div>
                        <!-- Calle -->
                        <div class="col-md-6">
                            <label for="calle" class="form-label mest-color-text">Calle:</label>
                            <input type="text" id="calle" name="calle" class="form-control rounded-pill"  required>
                        </div>
                        <!-- Número -->
                        <div class="col-md-6">
                            <label for="numero" class="form-label mest-color-text">Número:</label>
                            <input type="text" id="numero" name="numero" class="form-control rounded-pill" required>
                        </div>
                        <!-- Colonia -->
                        <div class="col-md-6">
                            <label for="colonia" class="form-label mest-color-text">Colonia:</label>
                            <input type="text" id="colonia" name="colonia" class="form-control rounded-pill" required>
                        </div>
                        <!-- Código Postal -->
                        <div class="col-md-6">
                            <label for="codigo_postal" class="form-label mest-color-text">Código Postal:</label>
                            <input type="number" id="codigo_postal" name="codigo_postal" class="form-control rounded-pill"  required>
                        </div>
                        <!-- Ciudad -->
                        <div class="col-md-6">
                            <label for="ciudad" class="form-label mest-color-text">Ciudad:</label>
                            <input type="text" id="ciudad" name="ciudad" class="form-control rounded-pill" required>
                        </div>
                        <!-- Estado -->
                        <div class="col-md-6">
                            <label for="estado" class="form-label mest-color-text">Estado:</label>
                            <input type="text" id="estado" name="estado" class="form-control rounded-pill" required>
                        </div>
                        <!-- País -->
                        <div class="col-md-6">
                            <label for="pais" class="form-label mest-color-text">País:</label>
                            <input type="text" id="pais" name="pais" class="form-control rounded-pill"  required>
                        </div>
                        <!-- Cambio de Dólar -->
                        <div class="col-md-6">
                            <label for="cambio_dolar" class="form-label mest-color-text">Cambio de dolar:</label>
                            <input type="numeric" id="cambio_dolar" name="cambio_dolar" class="form-control rounded-pill"  required>
                        </div>
                    </div>
                </div>
                <!-- Footer del Modal -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn mest-color rounded-pill beige">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Íconos Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<!-- Bootstrap JS (bundle incluye Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.toggle-status');

        toggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const clienteId = this.getAttribute('data-id');
                const estado = this.checked;

                fetch(`/clientes/update-status/${clienteId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            estado: estado
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log(data.success);
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    });
</script>

@endsection