@extends('layouts.complete')

@section('title', 'Mediciones')

@section('content')
<!-- Asegúrate de incluir Bootstrap CSS, si ya lo tienes en tu layout, puedes omitir estas líneas -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">

<div class="container mt-4">
    <h1>Lista de Mediciones</h1>
    <!-- Botón que dispara el modal -->
    <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#createModal">
        Crear Nueva Medición
    </button>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Corriente</th>
                <th>Voltaje</th>
                <th>Poder</th>
                <th>Energía</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($mediciones as $medicion)
            <tr>
                <td>{{ $medicion->id }}</td>
                <td>{{ $medicion->nombre }}</td>
                <td>{{ $medicion->corriente }}</td>
                <td>{{ $medicion->voltaje }}</td>
                <td>{{ $medicion->poder }}</td>
                <td>{{ $medicion->energia }}</td>
                <td>
                    <a href="{{ route('mediciones.show', $medicion->id) }}" class="btn btn-info btn-sm">Ver</a>
                    <a href="{{ route('mediciones.edit', $medicion->id) }}" class="btn btn-warning btn-sm">Editar</a>
                    <form action="{{ route('mediciones.destroy', $medicion->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal para Crear Nueva Medición -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createModalLabel">Crear Nueva Medición</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('mediciones.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
          </div>
          <div class="form-group">
            <label for="corriente">Corriente</label>
            <input type="number" step="any" class="form-control" id="corriente" name="corriente" required>
          </div>
          <div class="form-group">
            <label for="voltaje">Voltaje</label>
            <input type="number" step="any" class="form-control" id="voltaje" name="voltaje" required>
          </div>
          <div class="form-group">
            <label for="poder">Poder</label>
            <input type="number" step="any" class="form-control" id="poder" name="poder" required>
          </div>
          <div class="form-group">
            <label for="energia">Energía</label>
            <input type="number" step="any" class="form-control" id="energia" name="energia" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary">Crear Medición</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Incluir jQuery, Popper.js y Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

@endsection
