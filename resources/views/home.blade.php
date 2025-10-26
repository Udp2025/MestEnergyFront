@extends('layouts.complete')

@section('title', 'Inicio')

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

        <section class="home-section">
            <header class="home-section__header">
                <h2>Clientes Recientes</h2>
            </header>
            <div class="home-grid home-grid--three">
                @forelse($recent_clients as $client)
                    <article class="home-card">
                        <div class="home-card__header">
                            <h3>{{ $client->nombre }}</h3>
                            <span class="badge">ID {{ $client->id }}</span>
                        </div>
                        <dl class="home-card__list">
                            <div>
                                <dt>Alta:</dt>
                                <dd>{{ optional($client->created_at)->format('d M Y') ?? 'N/D' }}</dd>
                            </div>
                            <div>
                                <dt>Estatus:</dt>
                                <dd>{{ $client->estado_cliente === 1 ? 'Activo' : 'Onboarding' }}</dd>
                            </div>
                        </dl>
                    </article>
                @empty
                    <article class="home-card home-card--empty">
                        <p>No se han registrado clientes recientemente.</p>
                    </article>
                @endforelse
            </div>
        </section>

    @else
        <section class="home-section">
            <header class="home-section__header">
                <h2>Resumen del Sitio</h2>
            </header>
            <div class="home-grid home-grid--metrics">
                <article class="home-card metric-card">
                    <h3>Usuarios</h3>
                    <p class="metric-card__value">{{ number_format($metrics['users']) }}</p>
                    <p class="metric-card__hint">Equipo con acceso a la plataforma</p>
                </article>
                <article class="home-card metric-card">
                    <h3>Dispositivos</h3>
                    <p class="metric-card__value">{{ number_format($metrics['devices']) }}</p>
                    <p class="metric-card__hint">Dispositivos monitoreados</p>
                </article>
                <article class="home-card metric-card">
                    <h3>Site</h3>
                    <p class="metric-card__value">{{ $metrics['site_name'] ?? 'N/D' }}</p>
                    <p class="metric-card__hint">Ubicación vinculada</p>
                </article>
            </div>
        </section>

        <section class="home-section">
            <header class="home-section__header">
                <h2>Usuarios del Cliente</h2>
            </header>
            <div class="home-table">
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
                        @forelse($client_users as $clientUser)
                            <tr>
                                <td>{{ $clientUser->name }}</td>
                                <td>{{ $clientUser->email }}</td>
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
                <h2>Alertas recientes</h2>
            </header>
            <div class="home-grid home-grid--two">
                @foreach($alerts as $alert)
                    <article class="home-card">
                        <h3>{{ $alert['title'] }}</h3>
                        <p class="home-card__text">{{ $alert['detail'] }}</p>
                        <p class="home-card__timestamp">Actualizado {{ $alert['timestamp']->diffForHumans() }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
