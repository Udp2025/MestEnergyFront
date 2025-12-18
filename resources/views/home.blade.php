@extends('layouts.complete')

@section('title', 'Inicio')

@push('head')
    @vite(['resources/js/pages/home_fixed_dashboard.js'])
@endpush

@section('content')
<div class="home-dashboard">
    @if($is_super_admin)
        <section class="home-section">
            <header class="home-section__header">
                <h2>Resumen Global</h2>
            </header>
            <div class="home-grid home-grid--metrics">
                <article class="home-card metric-card">
                    <h3>Clientes</h3>
                    <p class="metric-card__value">{{ number_format($metrics['clients']) }}</p>
                    <p class="metric-card__hint">Total de organizaciones activas</p>
                </article>
                <article class="home-card metric-card">
                    <h3>Usuarios</h3>
                    <p class="metric-card__value">{{ number_format($metrics['users']) }}</p>
                    <p class="metric-card__hint">Colaboradores onboarded</p>
                </article>
                <article class="home-card metric-card">
                    <h3>Sitios</h3>
                    <p class="metric-card__value">{{ number_format($metrics['sites']) }}</p>
                    <p class="metric-card__hint">Infraestructura administrada</p>
                </article>
                <article class="home-card metric-card">
                    <h3>Dispositivos</h3>
                    <p class="metric-card__value">{{ number_format($metrics['devices']) }}</p>
                    <p class="metric-card__hint">Sensores</p>
                </article>
            </div>
        </section>
    @endif

    <section class="home-section" data-fixed-dashboard data-super-admin="{{ $is_super_admin ? '1' : '0' }}" data-site-id="{{ $user_site_id ?? '' }}">
        <header class="home-section__header home-section__header--fixed">
            <div>
                <h2>Estatus sitio</h2>
                <p class="home-section__hint">Visión rápida del día de hoy (00:00 - ahora)</p>
            </div>
            <div class="home-section__actions">
                @if($is_super_admin)
                    <label class="inline-field">
                        <span>Sitio</span>
                        <select data-site-select class="home-select">
                            <option value="">Cargando sitios...</option>
                        </select>
                    </label>
                @else
                    <span class="pill pill--muted">Sitio: {{ $metrics['site_name'] ?? 'N/D' }}</span>
                @endif
            </div>
        </header>
        <div class="fixed-kpi-row">
            <article class="home-card kpi-card" data-kpi-card="pf">
                <p class="kpi-label">Cumplimiento PF</p>
                <p class="kpi-value" data-kpi-value="pf">—</p>
                <p class="kpi-meta">Hoy</p>
            </article>
            <article class="home-card kpi-card" data-kpi-card="availability">
                <p class="kpi-label">Disponibilidad</p>
                <p class="kpi-value" data-kpi-value="availability">—</p>
                <p class="kpi-meta">Hoy</p>
            </article>
            <article class="home-card kpi-card" data-kpi-card="activeDevices">
                <p class="kpi-label">Sensores activos</p>
                <p class="kpi-value" data-kpi-value="activeDevices">—</p>
                <p class="kpi-meta">Última hora</p>
            </article>
            <article class="home-card kpi-card" data-kpi-card="energy">
                <p class="kpi-label">Energía total</p>
                <p class="kpi-value" data-kpi-value="energy">—</p>
                <p class="kpi-meta">Hoy</p>
            </article>
        </div>
        <article class="home-card fixed-card fixed-card--main fixed-card--full">
            <div class="fixed-card__header">
                <div>
                    <h3>Energía acumulada hoy</h3>
                    <p class="home-card__hint">Serie de energía por hora</p>
                </div>
                <div class="fixed-toggle" role="group" aria-label="Modo de agregación">
                    <button type="button" class="fixed-toggle__btn is-active" data-mode="agg">Agregada</button>
                    <button type="button" class="fixed-toggle__btn" data-mode="deagg">Por dispositivo</button>
                </div>
            </div>
            <div class="fixed-chart" data-dashboard-chart aria-label="Energía acumulada del día"></div>
            <p class="home-card__hint" data-chart-status>Calculando…</p>
        </article>
    </section>

    @if($is_super_admin)
        <section class="home-section">
            <header class="home-section__header">
                <h2>Clientes Recientes</h2>
            </header>
            <div class="home-table home-table--recent">
                <table>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Fecha alta</th>
                            <th>Estatus</th>
                            <th>ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent_clients as $client)
                            <tr>
                                <td class="cell-truncate" title="{{ $client->nombre }}">{{ $client->nombre }}</td>
                                <td>{{ optional($client->created_at)->format('d M Y') ?? 'N/D' }}</td>
                                <td>{{ $client->estado_cliente === 1 ? 'Activo' : 'Onboarding' }}</td>
                                <td>#{{ $client->id }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">No se han registrado clientes recientemente.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    @else
        @php
            $clientUsers = $client_users ?? collect();
            $alertsCollection = collect($alerts ?? []);
            $summary = $client_summary ?? [];
            $isMissingClient = $client_missing ?? false;
            $activeUserId = $active_user_id ?? null;
            $siteName = $metrics['site_name'] ?? null;
        @endphp

        @if($isMissingClient)
            <section class="home-section">
                <article class="home-card home-card--empty home-card--guide">
                    <h3>Tu cuenta no tiene un cliente asignado</h3>
                    <p>Para ver el inicio necesitas que tu usuario esté vinculado a un cliente y sitio. Contacta a un administrador o revisa tu perfil.</p>
                    <div class="home-card__actions">
                        <a class="btn-link" href="{{ route('profile.edit') }}">Actualizar perfil</a>
                    </div>
                </article>
            </section>
        @else
            <section class="home-section">
                <header class="home-section__header">
                    <h2>Resumen del sitio</h2>
                </header>
                <div class="home-grid home-grid--metrics">
                    <article class="home-card metric-card">
                        <h3>Sitio</h3>
                        <p class="metric-card__value">{{ $siteName ?? 'Sin sitio' }}</p>
                        <p class="metric-card__hint">Ubicación vinculada al cliente</p>
                    </article>
                    <article class="home-card metric-card">
                        <h3>Usuarios</h3>
                        <p class="metric-card__value">{{ number_format($metrics['users']) }}</p>
                        <p class="metric-card__hint">Equipo con acceso</p>
                    </article>
                    <article class="home-card metric-card">
                        <h3>Dispositivos</h3>
                        <p class="metric-card__value">{{ number_format($metrics['devices']) }}</p>
                        <p class="metric-card__hint">Dispositivos monitoreados</p>
                    </article>
                    
                </div>
            </section>

            <section class="home-section">
                <header class="home-section__header">
                    <h2>Resumen del cliente</h2>
                    <a class="btn-link" href="{{ route('mi-perfil') }}">Ver ficha</a>
                </header>
                <article class="home-card home-card--summary">
                    @php
                        $estadoLabel = ($summary['estado_cliente'] ?? null) === 1 ? 'Activo' : 'Onboarding';
                        $capacitacion = ($summary['capacitacion'] ?? null) ? 'Capacitación completada' : 'Capacitación pendiente';
                    @endphp
                    <div class="summary-pills">
                        <span class="pill pill--status">{{ $estadoLabel }}</span>
                        <span class="pill pill--muted">{{ $capacitacion }}</span>
                    </div>
                    <div class="summary-grid">
                        <div class="summary-block">
                            <p class="summary-label">Cliente</p>
                            <p class="summary-value">{{ $summary['nombre'] ?? 'N/D' }}</p>
                            <p class="summary-meta">RFC: {{ $summary['rfc'] ?? 'N/D' }}</p>
                            <p class="summary-meta">{{ $summary['contacto_nombre'] ?? 'Contacto no definido' }}</p>
                        </div>
                        <div class="summary-block">
                            <p class="summary-label">Contacto</p>
                            <p class="summary-value">{{ $summary['email'] ?? 'Sin correo' }}</p>
                            <p class="summary-meta">{{ $summary['telefono'] ?? 'Sin teléfono' }}</p>
                        </div>
                        <div class="summary-block">
                            <p class="summary-label">Ubicación</p>
                            <p class="summary-value">
                                {{ $summary['ciudad'] ?? 'Ciudad N/D' }},
                                {{ $summary['estado'] ?? 'Estado N/D' }}
                            </p>
                            <p class="summary-meta">{{ $summary['direccion'] ?? 'Dirección no disponible' }}</p>
                            <p class="summary-meta">{{ $summary['pais'] ?? 'Pais N/D' }}</p>
                        </div>
                        <div class="summary-block summary-block--stacked">
                            <p class="summary-label">Operación</p>
                            <p class="summary-meta">Tarifa región: {{ $summary['tarifa_region'] ?? 'N/D' }}</p>
                            <p class="summary-meta">Factor de carga: {{ $summary['factor_carga'] ?? 'N/D' }}</p>
                            <p class="summary-meta">Cambio USD: {{ $summary['cambio_dolar'] ?? 'N/D' }}</p>
                        </div>
                    </div>
                </article>
            </section>

            <section class="home-section">
                <header class="home-section__header">
                    <h2>Usuarios del cliente</h2>
                    <div class="home-section__actions">
                        <span class="pill pill--muted">{{ $clientUsers->count() }} usuarios</span>
                    </div>
                </header>
                <div class="home-table home-table--scrollable">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Alta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clientUsers as $clientUser)
                                @php $isCurrent = $clientUser->id === $activeUserId; @endphp
                                <tr class="{{ $isCurrent ? 'is-current-user' : '' }}">
                                    <td class="cell-truncate">
                                        <div class="user-name">
                                            <span title="{{ $clientUser->name }}">{{ $clientUser->name }}</span>
                                            @if($isCurrent)
                                                <span class="tag-current" aria-label="Tu usuario">Tú</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="cell-truncate" title="{{ $clientUser->email }}">{{ $clientUser->email }}</td>
                                    <td>{{ ucfirst($clientUser->role ?? 'usuario') }}</td>
                                    <td>{{ optional($clientUser->created_at)->format('d M Y') ?? 'N/D' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">No hay usuarios registrados para este cliente.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="home-section">
                <header class="home-section__header">
                    <h2>Alertas del sitio</h2>
                    <a class="btn-link" href="{{ route('site_alerts') }}">Ir a configuración</a>
                </header>
                <div class="home-grid home-grid--two">
                    @forelse($alertsCollection as $alert)
                        <article class="home-card home-card--alert">
                            <div class="home-card__header">
                                <h3>{{ $alert['name'] ?? $alert['slug'] }}</h3>
                                <span class="pill {{ ($alert['is_active'] ?? false) ? 'pill--success' : 'pill--muted' }}">
                                    {{ ($alert['is_active'] ?? false) ? 'Activa' : 'Pausada' }}
                                </span>
                            </div>
                            <p class="home-card__text">
                                Umbral: {{ $alert['operator'] === 'below' ? '<' : '>' }} {{ $alert['threshold'] ?? 'N/D' }}
                            </p>
                            <p class="home-card__hint">
                                Último valor: {{ $alert['last_value'] ?? 'N/D' }} · {{ $alert['last_triggered_at'] ?? 'Sin eventos' }}
                            </p>
                            <p class="home-card__timestamp">Sitio: {{ $alert['site_name'] ?? ($siteName ?? 'N/D') }}</p>
                        </article>
                    @empty
                        <article class="home-card home-card--empty">
                            <p>No tienes alertas configuradas. Configura tus alertas para recibir notificaciones.</p>
                        </article>
                    @endforelse
                </div>
            </section>
        @endif
    @endif
</div>
@endsection
