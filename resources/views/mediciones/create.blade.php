@extends('layouts.app')  

@section('content')
    <div class="container">
        <h1>Crear Medición</h1>

         <form action="{{ route('mediciones.store') }}" method="POST">
            @csrf 
            
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required>
            </div>

            <div class="mb-3">
                <label for="corriente" class="form-label">Corriente</label>
                <input type="number" class="form-control" id="corriente" name="corriente" required>
            </div>

            <div class="mb-3">
                <label for="voltaje" class="form-label">Voltaje</label>
                <input type="number" class="form-control" id="voltaje" name="voltaje" required>
            </div>

            <div class="mb-3">
                <label for="poder" class="form-label">Poder</label>
                <input type="number" class="form-control" id="poder" name="poder" required>
            </div>

            <div class="mb-3">
                <label for="energia" class="form-label">Energia</label>
                <input type="number" class="form-control" id="energia" name="energia" required>
            </div>

            <button type="submit" class="btn btn-primary">Crear Medición</button>
        </form>
    </div>
@endsection
