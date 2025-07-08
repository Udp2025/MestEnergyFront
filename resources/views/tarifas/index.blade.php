@extends('layouts.app')

@section('content')
<!-- Se incluyen el CSS personalizado, FontAwesome y Bootstrap -->
<link rel="stylesheet" href="{{ asset('css/inputs.css') }}">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container my-5">
    <h1 class="text-center mb-4">Gestión de Tarifas y Grupos Tarifarios</h1>

    <!-- Mensajes de éxito -->
    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Botón para crear tarifa -->
    <div class="d-flex justify-content-end mb-4">
        <button class="btn btn-success custom-btn-create" data-bs-toggle="modal" data-bs-target="#createTarifaModal">
            <i class="fas fa-plus"></i> Crear Tarifa
        </button>
    </div>

    <div class="row">
        <!-- Grupo Tarifarios -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 custom-card">
                <div class="card-header text-dark">
                    <h3>Grupos Tarifarios</h3>
                </div>
                <div class="card-body">
                    <!-- Se ha añadido la clase "custom-table" para darle un estilo más marcado -->
                    <table class="table table-bordered custom-table">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Factor de Carga</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($grupoTarifarios as $grupo)
                            <tr>
                                <td>{{ $grupo->nombre }}</td>
                                <td>{{ $grupo->factor_carga }}</td>
                                <td>
                                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editGrupoModal-{{ $grupo->id }}">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <form action="{{ route('grupo_tarifarios.destroy', $grupo->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tarifas -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 custom-card">
                <div class="card-header text-dark">
                    <h3>Tarifas</h3>
                </div>
                <div class="card-body">
                    <!-- Se ha añadido la clase "custom-table" para darle un estilo más marcado -->
                    <table class="table table-bordered custom-table">
                        <thead class="table-dark">
                            <tr>
                                <th>Clasificacion</th>
                                <th>Subtransmisión</th>
                                <th>Transmisión</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tarifas as $tarifa)
                            <tr>
                                <td>{{ $tarifa->clasificacion }}</td>
                                <td>{{ $tarifa->subtransmision }}</td>
                                <td>{{ $tarifa->transmision }}</td>
                                <td>
                                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editTarifaModal-{{ $tarifa->id }}">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <form action="{{ route('tarifas.destroy', $tarifa->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Crear Tarifa -->
    <div class="modal fade" id="createTarifaModal" tabindex="-1" aria-labelledby="createTarifaModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered">
             <div class="modal-content custom-modal-content">
                 <div class="modal-header custom-modal-header">
                     <h5 class="modal-title" id="createTarifaModalLabel">Crear Tarifa</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                 </div>
                 <div class="modal-body">
                     <form action="{{ route('tarifas.store') }}" method="POST">
                         @csrf
                         <div class="mb-3">
                             <label for="clasificacion" class="form-label">Nombre:</label>
                             <input type="text" name="clasificacion" id="clasificacion" class="form-control" required>
                         </div>
                         <div class="mb-3">
                             <label for="subtransmision" class="form-label">Subtransmisión:</label>
                             <input type="text" name="subtransmision" id="subtransmision" class="form-control" required>
                         </div>
                         <div class="mb-3">
                             <label for="transmision" class="form-label">Transmisión:</label>
                             <input type="text" name="transmision" id="transmision" class="form-control" required>
                         </div>
                         <div class="modal-footer">
                             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                             <button type="submit" class="btn btn-primary">Crear Tarifa</button>
                         </div>
                     </form>
                 </div>
             </div>
         </div>
    </div>

    <!-- Modales de edición de tarifas y grupos tarifarios -->
    @foreach ($grupoTarifarios as $grupo)
    <div class="modal fade" id="editGrupoModal-{{ $grupo->id }}" tabindex="-1" aria-labelledby="editGrupoModalLabel-{{ $grupo->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal-content">
                <div class="modal-header custom-modal-header">
                    <h5 class="modal-title" id="editGrupoModalLabel-{{ $grupo->id }}">Editar Grupo Tarifario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('grupo_tarifarios.update', $grupo->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre:</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" value="{{ $grupo->nombre }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="factor_carga" class="form-label">Factor de Carga:</label>
                            <input type="text" name="factor_carga" id="factor_carga" class="form-control" value="{{ $grupo->factor_carga }}" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    @foreach ($tarifas as $tarifa)
    <div class="modal fade" id="editTarifaModal-{{ $tarifa->id }}" tabindex="-1" aria-labelledby="editTarifaModalLabel-{{ $tarifa->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal-content">
                <div class="modal-header custom-modal-header">
                    <h5 class="modal-title" id="editTarifaModalLabel-{{ $tarifa->id }}">Editar Tarifa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('tarifas.update', $tarifa->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="clasificacion" class="form-label">Nombre:</label>
                            <input type="text" name="clasificacion" id="clasificacion" class="form-control" value="{{ $tarifa->clasificacion }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="subtransmision" class="form-label">Subtransmisión:</label>
                            <input type="text" name="subtransmision" id="subtransmision" class="form-control" value="{{ $tarifa->subtransmision }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="transmision" class="form-label">Transmisión:</label>
                            <input type="text" name="transmision" id="transmision" class="form-control" value="{{ $tarifa->transmision }}" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
@endsection
