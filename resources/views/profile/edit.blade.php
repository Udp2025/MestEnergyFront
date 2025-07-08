@extends('layouts.complete')

@section('title', 'Configuración de Usuario')

@section('content')
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
            <li>Preferencias</li>
        </ul>
    </aside>

    <main class="config-main">


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

<script>
    document.getElementById("profileImage").addEventListener("change", function(event) {
        const reader = new FileReader();
        reader.onload = function() {
            document.getElementById("profilePreview").src = reader.result;
        }
        reader.readAsDataURL(event.target.files[0]);
    });
</script>

<style>
    .config-container {
        display: flex;
        gap: 20px;
        max-width: 1100px;
        margin: auto;
        padding: 20px;
    }

    .config-menu {
        width: 250px;
        background: linear-gradient(135deg, rgb(234, 157, 93), rgb(189, 116, 43));
        color: white;
        padding: 20px;
        border-radius: 12px;
    }

    .config-menu h2 {
        text-align: center;
        font-weight: 700;
    }

    .config-menu ul {
        list-style: none;
        padding: 0;
    }

    .config-menu ul li {
        padding: 10px;
        cursor: pointer;
        border-radius: 6px;
        transition: background 0.3s;
    }

    .config-menu ul li:hover,
    .config-menu ul .active {
        background: rgba(255, 255, 255, 0.2);
    }

    .config-main {
        flex-grow: 1;
    }

    .config-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }

    .config-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    h3 {
        padding-bottom: 5px;
    }

    input {
        width: 100%;
        padding: 10px;
        margin: 6px 0;
        border: 1px solid #ccc;
        border-radius: 8px;
    }

    button {
        background: rgb(255, 111, 0);
        color: white;
        padding: 10px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s;
    }

    button:hover {
        background: rgb(219, 63, 6);
    }

    .profile-card {
        text-align: center;
    }

    .profile-card img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 3px solid rgb(255, 98, 0);
    }

    .alert {
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>
@endsection