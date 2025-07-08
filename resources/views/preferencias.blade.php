 @extends('layouts.complete')

@section('title', 'Preferences')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/principal.css') }}">

<div class="config-container">
    <!-- Panel lateral -->
    <aside class="config-menu">
        <h2>Opciones</h2>
        <ul>
            <li class="active">
                <a href="{{ route('config') }}" style="text-decoration: none; color: inherit;">
                    Informacion personal
            </li>
            <li>
                <a href="{{ route('permisosuser') }}" style="text-decoration: none; color: inherit;">
                    Seguridad
                </a>
            </li>
            <li>Preferencias</li>
        </ul>
    </aside>
    <section>
    <button onclick="toggleDarkMode()">🌙 Cambiar Modo</button>

    </section>

 
</div>
<script src="{{ asset('js/principal.js') }}"></script>

@endsection