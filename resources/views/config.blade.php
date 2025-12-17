@extends('layouts.complete')

@section('title', 'Configuración')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/config.css') }}">
@endpush

@php
    use Illuminate\Support\Facades\Storage;
    $disk = config('filesystems.default', 'public');
    $profileImageUrl = asset('images/profile.png');
    if ($user?->profile_image) {
        $profileImageUrl = Storage::disk($disk)->url($user->profile_image);
    }
    $roleValue = in_array($user?->role, ['admin', 'operaciones'], true) ? $user->role : 'operaciones';
    $isAdmin = $user?->role === 'admin';
@endphp

@section('content')
<div class="config-page">
    <header class="config-header">
        <div>
            <h1>Configuración</h1>
            <p>Gestiona tu información personal y las cuentas del equipo.</p>
        </div>
        <div class="config-header__actions">
            <span class="pill pill--muted">Rol: {{ $user->role ?? 'N/D' }}</span>
            @if($isAdmin)
                <a class="btn-link" href="{{ route('config.users') }}">Gestionar usuarios</a>
            @endif
        </div>
    </header>

    @if(session('status') === 'profile-updated')
        <div class="config-alert config-alert--success">Perfil actualizado correctamente.</div>
    @elseif(session('status') === 'password-updated')
        <div class="config-alert config-alert--success">Contraseña actualizada correctamente.</div>
    @endif

    @if(session('user-created'))
        <div class="config-alert config-alert--success">{{ session('user-created') }}</div>
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
                    <p class="eyebrow">Mis datos</p>
                    <h2>Perfil y acceso</h2>
                    <p class="config-subtitle">Actualiza tu nombre, correo, rol, contraseña y foto.</p>
                </div>
                <label class="avatar-floating" for="profile_image" title="Cambiar foto">
                    <img id="profilePreview" src="{{ $profileImageUrl }}" alt="Foto de perfil" class="avatar-floating__img">
                </label>
            </header>
            <form class="config-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('patch')
                <div class="config-form__grid">
                    <div class="config-field">
                        <label for="name">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="config-field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="config-field">
                        <label for="role">Rol</label>
                        <select id="role" name="role" required>
                            <option value="operaciones" {{ old('role', $roleValue) === 'operaciones' ? 'selected' : '' }}>Operaciones</option>
                            <option value="admin" {{ old('role', $roleValue) === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        <small>Solo se permiten los roles admin u operaciones.</small>
                        @error('role')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="password-block">
                        <div class="config-field">
                            <label for="password">Nueva contraseña</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Déjala en blanco para conservarla">
                            @error('password')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="config-field">
                            <label for="password_confirmation">Confirmar contraseña</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Repite la contraseña nueva">
                            @error('password_confirmation')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <input id="profile_image" name="profile_image" type="file" accept="image/png,image/jpeg" hidden>
                </div>
                <div class="config-form__actions">
                    <button type="submit" class="btn-primary">Guardar cambios</button>
                </div>
            </form>
        </section>
    </div>
</div>

<script>
    const profileInput = document.getElementById('profile_image');
    const profilePreview = document.getElementById('profilePreview');
    if (profileInput && profilePreview) {
        profileInput.addEventListener('change', (event) => {
            const [file] = event.target.files || [];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                profilePreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }
</script>
@endsection
