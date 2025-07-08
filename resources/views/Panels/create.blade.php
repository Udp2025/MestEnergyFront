@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Crear Nuevo Panel</h1>
    <form action="{{ route('panels.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="title">Título</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <button class="btn btn-primary mt-2">Guardar</button>
    </form>
</div>
@endsection
