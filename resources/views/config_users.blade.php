@extends('layouts.complete')

@section('title', 'Usuarios del cliente')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/config.css') }}">
@endpush

@php
    use Illuminate\Support\Facades\Storage;
    $disk = config('filesystems.default', 'public');
    $isSuperAdmin = session('is_super_admin', (int) (auth()->user()?->cliente_id ?? -1) === 0);
@endphp

@section('content')
<div class="config-page">
    <header class="config-header">
        <div>
            <h1>Usuarios</h1>
            <p>Gestiona las cuentas del cliente.</p>
        </div>
        <div class="config-header__actions">
            <span class="pill pill--muted">Total: {{ $users->count() }}</span>
            <a class="btn-link btn-link--solid" href="{{ route('config') }}">Volver a configuración</a>
        </div>
    </header>

    @if(session('user-created'))
        <div class="config-alert config-alert--success">{{ session('user-created') }}</div>
    @endif
    @if(session('user-updated'))
        <div class="config-alert config-alert--success">{{ session('user-updated') }}</div>
    @endif
    @if(session('user-deleted'))
        <div class="config-alert config-alert--success">{{ session('user-deleted') }}</div>
    @endif
    @if($errors->any())
        <div class="config-alert config-alert--error">
            <strong>Revisa los campos:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="config-grid config-grid--single">
        <section class="config-card config-card--wide">
            <header class="config-card__header">
                <div>
                    <p class="eyebrow">Nuevo usuario</p>
                    <h2>Crear</h2>
                    <p class="config-subtitle">Se asigna al mismo cliente.</p>
                </div>
            </header>
            <form class="config-form" method="POST" action="{{ route('config.users.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="config-field">
                    <label for="new_name">Nombre</label>
                    <input id="new_name" name="new_name" type="text" value="{{ old('new_name') }}" required>
                </div>
                <div class="config-field">
                    <label for="new_email">Email</label>
                    <input id="new_email" name="new_email" type="email" value="{{ old('new_email') }}" required>
                </div>
                <div class="config-field">
                    <label for="new_role">Rol</label>
                    <select id="new_role" name="new_role" required>
                        <option value="operaciones" {{ old('new_role') === 'operaciones' ? 'selected' : '' }}>Operaciones</option>
                        <option value="admin" {{ old('new_role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
                <div class="config-field">
                    <label for="new_password">Contraseña</label>
                    <input id="new_password" name="new_password" type="password" required>
                </div>
                <div class="config-field">
                    <label for="new_password_confirmation">Confirmar contraseña</label>
                    <input id="new_password_confirmation" name="new_password_confirmation" type="password" required>
                </div>
                <div class="config-field">
                    <label for="new_profile_image">Foto de perfil (opcional)</label>
                    <input id="new_profile_image" name="new_profile_image" type="file" accept="image/png,image/jpeg">
                </div>
                <div class="config-form__actions">
                    <button type="submit" class="btn-primary">Crear usuario</button>
                </div>
            </form>
        </section>

        <section class="config-card config-card--wide">
            <header class="config-card__header">
                <div>
                    <p class="eyebrow">Equipo</p>
                    <h2>Usuarios del cliente</h2>
                    <p class="config-subtitle">Edita o elimina usuarios existentes.</p>
                </div>
            </header>
            <div class="config-table-wrap">
                <table class="config-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $item)
                            @php
                                $profileUrl = asset('images/default-avatar.png');
                                if ($item->profile_image) {
                                    $profileUrl = Storage::disk($disk)->url($item->profile_image);
                                }
                            @endphp
                            <tr>
                                <td>
                                    <img class="table-avatar" src="{{ $profileUrl }}" alt="Foto de {{ $item->name }}">
                                </td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->email }}</td>
                                <td>{{ ucfirst($item->role) }}</td>
                                <td class="config-table__actions">
                                    <details class="config-details">
                                        <summary class="btn-link" aria-label="Editar usuario"><i class="fas fa-edit" aria-hidden="true"></i></summary>
                                        <form class="inline-form" method="POST" action="{{ route('config.users.update', $item) }}" enctype="multipart/form-data">
                                            @csrf
                                            @method('PATCH')
                                            <div class="config-form__grid">
                                                <div class="config-field">
                                                    <label>Nombre</label>
                                                    <input type="text" name="name" value="{{ $item->name }}" required>
                                                </div>
                                                <div class="config-field">
                                                    <label>Email</label>
                                                    <input type="email" name="email" value="{{ $item->email }}" required>
                                                </div>
                                                <div class="config-field">
                                                    <label>Rol</label>
                                                    <select name="role" required>
                                                        <option value="operaciones" {{ $item->role === 'operaciones' ? 'selected' : '' }}>Operaciones</option>
                                                        <option value="admin" {{ $item->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                                    </select>
                                                </div>
                                                <div class="config-field">
                                                    <label>Nueva contraseña</label>
                                                    <input type="password" name="password" placeholder="Opcional">
                                                </div>
                                                <div class="config-field">
                                                    <label>Confirmar contraseña</label>
                                                    <input type="password" name="password_confirmation" placeholder="Opcional">
                                                </div>
                                                <div class="config-field">
                                                    <label>Foto</label>
                                                    <input type="file" name="profile_image" accept="image/png,image/jpeg">
                                                </div>
                                            </div>
                                            <div class="config-form__actions">
                                                <button type="submit" class="btn-primary"><i class="fas fa-save" aria-hidden="true"></i></button>
                                            </div>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('config.users.destroy', $item) }}" onsubmit="return confirm('¿Eliminar usuario?');">
                                        @csrf
                    @method('DELETE')
                                        <button type="submit" class="btn-link danger"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">No hay usuarios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
