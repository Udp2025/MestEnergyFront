@extends('layouts.complete')

@section('title', 'Configuración de Cuenta')

@section('content')
<link rel="stylesheet" href="{{ asset('css/config.css') }}">

<div class="config-container">
    <aside class="config-menu">
        <h2>Opciones</h2>
        <ul>
            <li class="active">Información Personal</li>
            <li>
                <a href="{{ route('permisosuser') }}" style="text-decoration: none; color: inherit;">
                    Seguridad
                </a>
            </li>
            <li>
                <a href="{{ route('preferencias') }}" style="text-decoration: none; color: inherit;">
                    Preferencias
            </li>
        </ul>
    </aside>

    <main class="config-main">
        {{-- Mensajes de estado y errores --}}
        @if(session('status') == 'profile-updated')
            <div class="alert alert-success">Perfil actualizado correctamente.</div>
        @elseif(session('status') == 'password-updated')
            <div class="alert alert-success">Contraseña actualizada correctamente.</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Formulario para Datos Personales y Foto de Perfil -->
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('patch')
            <div class="config-grid">
                <section class="config-card">
                    <h3>Datos Personales</h3>
                    <label>Nombre</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    
                    <button type="submit" id="updateAccount">Actualizar Datos</button>
                </section>

                <section class="config-card profile-card">
                    <h3>Foto de Perfil</h3>
                    <label for="profileImage" class="profile-label">
                        <img id="profilePreview" src="{{ $user->profile_image ? asset('storage/' . $user->profile_image) : asset('images/profile.png') }}" alt="Perfil">
                        <span>Editar</span>
                    </label>
                    <input type="file" id="profileImage" name="profile_image" accept="image/*" style="display: none;">
                    <button type="submit" id="saveProfileImage">Guardar Cambios</button>
                </section>
            </div>
        </form>

        <p class="alert-text">Es recomendable actualizar tu contraseña regularmente.</p>

        <!-- Formulario para Actualizar Contraseña -->
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('patch')
            <section class="config-card">
                <h3>Actualizar Contraseña</h3>
                <label>Nueva Contraseña</label>
                <input type="password" name="password" required>
                <label>Confirmar Contraseña</label>
                <input type="password" name="password_confirmation" required>
                <button type="submit" id="updatePassword">Cambiar Contraseña</button>
            </section>
        </form>

 
    </main>
</div>

<!-- Script para vista previa de la imagen de perfil -->
<script>
    document.getElementById("profileImage").addEventListener("change", function(event) {
        const reader = new FileReader();
        reader.onload = function(){
            document.getElementById("profilePreview").src = reader.result;
        }
        reader.readAsDataURL(event.target.files[0]);
    });
</script>

  
@endsection
