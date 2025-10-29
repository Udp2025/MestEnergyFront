@php
    $user = Auth::user();
    $isSuperAdmin = session('is_super_admin', (int) ($user?->cliente_id ?? -1) === 0);
    $clientContextRoutes = [
        'general_clientes',
        'visualize',
        'heatmap',
        'benchmarking',
        'energyflow',
        'clientes.clidash',
        'site_alerts',
        'tiggers',
        'manage',
        'groups',
    ];
    $routeName = optional(request()->route())->getName();
    $selectedClientId = session('selected_cliente_id');
    $clientQueryId = request()->query('cliente');
    $isClientContextRoute = false;

    if ($routeName === 'clientes.show') {
        $isClientContextRoute = true;
    } elseif (
        $selectedClientId &&
        in_array($routeName, $clientContextRoutes, true) &&
        $clientQueryId !== null &&
        (string) $clientQueryId === (string) $selectedClientId
    ) {
        $isClientContextRoute = true;
    }

    if ($isSuperAdmin && !$isClientContextRoute) {
        session()->forget(['selected_cliente_id', 'selected_cliente_name']);
        $selectedClientId = null;
    }

    $shouldShowClientSidebar = $user && (
        !$isSuperAdmin || $isClientContextRoute
    );
@endphp

@if($shouldShowClientSidebar)
    @php
        $routeClienteParam = request()->route('cliente');
        if ($routeClienteParam instanceof \App\Models\Cliente) {
            $routeClienteParam = $routeClienteParam->getKey();
        }
        $clienteLinkId = $routeClienteParam
            ?? ($clientQueryId ?: null)
            ?? $selectedClientId
            ?? $user?->cliente_id;
        $clientQueryParams = ($isSuperAdmin && $clienteLinkId)
            ? ['cliente' => $clienteLinkId]
            : [];
    @endphp
    <aside class="custom-sidebar-right" id="sidebar-right">
        <div class="custom-logo-section custom-logo-section--right">
            <button class="custom-toggle-btn" id="toggle-btn-right" type="button" aria-label="Contraer menú del cliente">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <nav class="custom-menu-section">
            <h3 class="custom-menu-title">Menú</h3>
            <ul>
                <li class="{{ request()->routeIs('clientes.show') ? 'custom-active' : '' }}">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    @if($clienteLinkId)
                        <a href="{{ route('clientes.show', ['cliente' => $clienteLinkId]) }}">
                            <span>Información</span>
                        </a>
                    @else
                        <span>Información</span>
                    @endif
                </li>
                @if($isSuperAdmin)
                    <li class="{{ request()->routeIs('general_clientes') ? 'custom-active' : '' }}">
                        <i class="fas fa-circle" aria-hidden="true"></i>
                        <a href="{{ route('general_clientes', $clientQueryParams) }}">
                            <span>Dashboard</span>
                        </a>
                    </li>
                @endif
                <li class="{{ request()->routeIs('visualize') ? 'custom-active' : '' }}">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    <a href="{{ route('visualize', $clientQueryParams) }}">
                        <span>Energy Dashboard</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('heatmap') ? 'custom-active' : '' }}">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    <a href="{{ route('heatmap', $clientQueryParams) }}">
                        <span>Heat Map</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('benchmarking') ? 'custom-active' : '' }}">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    <a href="{{ route('benchmarking', $clientQueryParams) }}">
                        <span>Benchmarking</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('energyflow') ? 'custom-active' : '' }}">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    <a href="{{ route('energyflow', $clientQueryParams) }}">
                        <span>Energy Flow</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('clientes.clidash') ? 'custom-active' : '' }}">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    <a href="{{ route('clientes.clidash', $clientQueryParams) }}">
                        <span>Finanzas</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('site_alerts') ? 'custom-active' : '' }}">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    <a href="{{ route('site_alerts', $clientQueryParams) }}">
                        <span>Alertas</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('tiggers') ? 'custom-active' : '' }}">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    <a href="{{ route('tiggers', $clientQueryParams) }}">
                        <span>Triggers</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('manage') ? 'custom-active' : '' }}">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    <a href="{{ route('manage', $clientQueryParams) }}">
                        <span>Manejo de Eventos</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('groups') ? 'custom-active' : '' }}">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    <a href="{{ route('groups', $clientQueryParams) }}">
                        <span>Grupos</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
@endif
