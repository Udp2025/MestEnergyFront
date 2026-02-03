@extends('layouts.complete')

@section('title', 'Usuarios del cliente')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/config.css') }}">
@endpush

@php
    use Illuminate\Support\Facades\Storage;
    $disk = config('filesystems.images_disk', 'public');
    $isSuperAdmin = session('is_super_admin', (int) (auth()->user()?->cliente_id ?? -1) === 0);
    $selectedClientId = $selectedClientId ?? ($isSuperAdmin ? null : auth()->user()?->cliente_id);
    $search = $search ?? '';
    $clients = $clients ?? [];
    $currentUserId = $currentUserId ?? auth()->id();
@endphp

@php
    $selectedClientName = '';
    if ($selectedClientId && !empty($clients)) {
        $match = collect($clients)->firstWhere('id', (int) $selectedClientId);
        $selectedClientName = $match->nombre ?? '';
    }
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
                </div>
            </header>
            <form class="config-form" method="POST" action="{{ route('config.users.store') }}" enctype="multipart/form-data">
                @csrf
                @if($isSuperAdmin)
                    <div class="config-field">
                        <label for="new_cliente_id">Cliente</label>
                        <input list="cliente_options_create" id="new_cliente_name" name="new_cliente_name" value="{{ old('new_cliente_name', $selectedClientName ?? '') }}" placeholder="Escribe para filtrar clientes" autocomplete="off" data-client-name>
                        <input type="hidden" id="new_cliente_id" name="new_cliente_id" value="{{ old('new_cliente_id', $selectedClientId) }}">
                        <datalist id="cliente_options_create">
                            @foreach($clients as $client)
                                <option value="{{ $client->nombre }}" data-id="{{ $client->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                @else
                    <input type="hidden" name="new_cliente_id" value="{{ auth()->user()->cliente_id }}">
                @endif
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
            <form method="GET" class="config-filters" id="userFilters">
                @if($isSuperAdmin)
                    <div class="config-field">
                        <label for="cliente_filter">Cliente</label>
                        <input list="cliente_options_filter" id="cliente_filter_name" name="cliente_name" value="{{ $selectedClientName ?? '' }}" placeholder="Filtrar cliente" autocomplete="off" data-client-filter>
                        <input type="hidden" id="cliente_filter" name="cliente" value="{{ $selectedClientId }}">
                        <datalist id="cliente_options_filter">
                            <option value="">Todos</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->nombre }}" data-id="{{ $client->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                @endif
                <div class="config-field">
                    <label for="user_search">Buscar</label>
                    <input id="user_search" name="q" type="search" value="{{ $search }}" placeholder="Nombre o correo">
                </div>
            </form>
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
                                    if ($disk === 's3') {
                                        $profileUrl = Storage::disk($disk)->temporaryUrl($item->profile_image, now()->addMinutes(10));
                                    } else {
                                        $profileUrl = Storage::disk($disk)->url($item->profile_image);
                                    }
                                }
                            @endphp
                            <tr>
                                <td>
                                    <img class="table-avatar" src="{{ $profileUrl }}" alt="Foto de {{ $item->name }}">
                                </td>
                                <td class="user-name-cell">{{ $item->name }} @if((int)$currentUserId === (int)$item->id)<span class="pill pill--muted">Tú</span>@endif</td>
                                <td>{{ $item->email }}</td>
                                <td>{{ ucfirst($item->role) }}</td>
                                <td class="config-table__actions">
                                    <button class="btn-link" type="button"
                                        data-edit-user
                                        data-id="{{ $item->id }}"
                                        data-url="{{ route('config.users.update', $item) }}"
                                        data-name="{{ $item->name }}"
                                        data-email="{{ $item->email }}"
                                        data-role="{{ $item->role }}">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </button>
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
    <div class="config-modal" id="userModal" aria-hidden="true">
        <div class="config-modal__backdrop" data-modal-close></div>
        <div class="config-modal__content" role="dialog" aria-modal="true">
            <div class="config-modal__header">
                <h3 id="modalTitle">Editar usuario</h3>
                <button type="button" class="btn-link danger" data-modal-close aria-label="Cerrar"><i class="fas fa-times"></i></button>
            </div>
            <form id="modalForm" class="config-form" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                <div class="config-form__grid">
                    <div class="config-field">
                        <label for="modal_name">Nombre</label>
                        <input id="modal_name" name="name" type="text" required>
                    </div>
                    <div class="config-field">
                        <label for="modal_email">Email</label>
                        <input id="modal_email" name="email" type="email" required>
                    </div>
                    <div class="config-field">
                        <label for="modal_role">Rol</label>
                        <select id="modal_role" name="role" required>
                            <option value="operaciones">Operaciones</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="config-field">
                        <label for="modal_password">Nueva contraseña</label>
                        <input id="modal_password" name="password" type="password" placeholder="Opcional">
                    </div>
                    <div class="config-field">
                        <label for="modal_password_confirmation">Confirmar contraseña</label>
                        <input id="modal_password_confirmation" name="password_confirmation" type="password" placeholder="Opcional">
                    </div>
                    <div class="config-field">
                        <label for="modal_profile_image">Foto</label>
                        <input id="modal_profile_image" name="profile_image" type="file" accept="image/png,image/jpeg">
                    </div>
                </div>
                <div class="config-form__actions">
                    <button type="submit" class="btn-primary"><i class="fas fa-save" aria-hidden="true"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function () {
        const modal = document.getElementById('userModal');
        const form = document.getElementById('modalForm');
        const nameInput = document.getElementById('modal_name');
        const emailInput = document.getElementById('modal_email');
        const roleInput = document.getElementById('modal_role');
        const passwordInput = document.getElementById('modal_password');
        const passwordConfirmInput = document.getElementById('modal_password_confirmation');
        const clientCreateInput = document.getElementById('new_cliente_name');
        const clientCreateHidden = document.getElementById('new_cliente_id');
        const clientFilterInput = document.getElementById('cliente_filter_name');
        const clientFilterHidden = document.getElementById('cliente_filter');
        const createList = document.getElementById('cliente_options_create');
        const filterList = document.getElementById('cliente_options_filter');

        function openModal(target) {
            const id = target.dataset.id;
            const url = target.dataset.url;
            nameInput.value = target.dataset.name || '';
            emailInput.value = target.dataset.email || '';
            roleInput.value = target.dataset.role || 'operaciones';
            passwordInput.value = '';
            passwordConfirmInput.value = '';
            form.action = url || `/config/users/${id}`;
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.add('is-visible');
        }

        function closeModal() {
            modal.setAttribute('aria-hidden', 'true');
            modal.classList.remove('is-visible');
        }

        document.querySelectorAll('[data-edit-user]').forEach(btn => {
            btn.addEventListener('click', () => openModal(btn));
        });

        modal?.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });

        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('is-visible')) {
                closeModal();
            }
        });

        function resolveClientId(inputEl, dataListEl) {
            if (!inputEl || !dataListEl) return null;
            const entered = inputEl.value.trim().toLowerCase();
            const options = Array.from(dataListEl.options || []);
            const match = options.find(opt => opt.value.toLowerCase() === entered);
            return match ? (match.dataset.id || match.value) : null;
        }

        if (clientCreateInput && createList) {
            clientCreateInput.addEventListener('change', () => {
                const resolved = resolveClientId(clientCreateInput, createList);
                if (clientCreateHidden) clientCreateHidden.value = resolved || '';
            });
        }

        const filtersForm = document.getElementById('userFilters');
        if (filtersForm) {
            const triggerSubmit = () => filtersForm.submit();
            if (clientFilterInput && filterList) {
                clientFilterInput.addEventListener('input', () => {
                    const resolved = resolveClientId(clientFilterInput, filterList);
                    if (clientFilterHidden) clientFilterHidden.value = resolved || '';
                    triggerSubmit();
                });
                clientFilterInput.addEventListener('change', () => {
                    const resolved = resolveClientId(clientFilterInput, filterList);
                    if (clientFilterHidden) clientFilterHidden.value = resolved || '';
                    triggerSubmit();
                });
            }
            const searchInput = document.getElementById('user_search');
            if (searchInput) {
                ['input','change'].forEach(ev => searchInput.addEventListener(ev, triggerSubmit));
            }
        }
    })();
</script>
@endsection
