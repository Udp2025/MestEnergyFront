@extends('layouts.complete')

@section('title', 'Permisos de Usuarios')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/permisosuser.css') }}">

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

    <!-- Contenido principal -->
    <main class="config-main">
        <div class="header-main">
            <div>
                <h1>Usuarios</h1>
            </div>
            <button class="btn-crear-usuario" id="btnCrearUsuario">
                <i class="fas fa-plus"></i> Crear Usuario
            </button>
        </div>

        <!-- Tabla de usuarios -->
        <table class="config-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th># (ID)</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($usuarios as $usuario)
                <tr>
                    <td>
                        @php
                            $userProfileUrl = null;
                            if ($usuario->profile_image) {
                                $defaultDisk = config('filesystems.default', 'public');
                                $storage = \Illuminate\Support\Facades\Storage::disk($defaultDisk);
                                if ($storage->exists($usuario->profile_image)) {
                                    $userProfileUrl = $storage->url($usuario->profile_image);
                                } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($usuario->profile_image)) {
                                    $userProfileUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($usuario->profile_image);
                                }
                            }
                        @endphp
                        @if($userProfileUrl)
                            <img src="{{ $userProfileUrl }}" alt="Foto de {{ $usuario->nombre }}" class="avatar">
                        @else
                            <img src="{{ asset('images/default-avatar.png') }}" alt="Avatar default" class="avatar">
                        @endif

                    </td>
                    <td>#{{ $usuario->id }}</td>
                    <td>{{ $usuario->name }}</td>
                    <td>{{ $usuario->email }}</td>
                    <td>{{ $usuario->role }}</td>
                    <td>
                        <button class="btn-accion btn-editar" data-id="{{ $usuario->id }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-accion btn-eliminar" data-id="{{ $usuario->id }}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Modal para crear/editar usuario -->
        <div id="modalUsuario" class="modal">
            <div class="modal-content">
                <span class="close" id="closeModal">&times;</span>
                <h2 id="modalTitle">Crear Usuario</h2>
                <form id="formUsuario" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="usuario_id" name="usuario_id" value="">

                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required>

                    <label for="correo">Correo:</label>
                    <input type="email" id="correo" name="correo" required>

                    <label for="role">Rol:</label>
                    <select id="role" name="role" required>
                        <option value="webmaster">Webmaster</option>
                        <option value="admin">Admin</option>
                        <option value="analyst">Analista</option>
                    </select>

                    <label for="foto">Foto:</label>
                    <input type="file" id="foto" name="foto" accept="image/*">

                    <button type="submit" id="btnGuardarUsuario">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="{{ asset('js/permisosuser.js') }}"></script>

@endsection
