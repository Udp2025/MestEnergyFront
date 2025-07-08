@extends('layouts.app')

@section('content')
<div class="grupo-tarifarios-container">
    <h1>Grupo Tarifarios</h1>
    <a href="{{ route('grupo_tarifarios.create') }}" class="btn-create">Crear Nuevo Grupo Tarifario</a>
    <table class="grupo-tarifarios-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Factor de Carga</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grupo_tarifarios as $grupo)
            <tr>
                <td>{{ $grupo->nombre }}</td>
                <td>{{ $grupo->factor_carga }}</td>
                <td>
                    <a href="{{ route('grupo_tarifarios.edit', $grupo->id) }}" class="btn-edit">Editar</a>
                    <form action="{{ route('grupo_tarifarios.destroy', $grupo->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-delete">Eliminar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
