@php
    $user = Auth::user();
    $isSuperAdmin = session('is_super_admin', (int) ($user?->cliente_id ?? -1) === 0);
    $clientContextRoutes = [
        'general_clientes',
        'heatmap',
        'benchmarking',
        'anomaly',
    'forecast',
    'scatter',
    'histogram',
    'timeseries',
    'energyflow',
    'reports',
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

    $shouldShowClientSidebar = (bool) $user;
@endphp

@if($shouldShowClientSidebar)
    @php
        $routeClienteParam = request()->route('cliente');
        if ($routeClienteParam instanceof \App\Models\Cliente) {
            $routeClienteParam = $routeClienteParam->getKey();
        }
        // For normal users lock to their own cliente_id; super admins can use context
        if (!$isSuperAdmin) {
            $clienteLinkId = $user?->cliente_id;
        } else {
            $clienteLinkId = $routeClienteParam
                ?? ($clientQueryId ?: null)
                ?? $selectedClientId
                ?? $user?->cliente_id;
        }
        $clientQueryParams = ($isSuperAdmin && $clienteLinkId)
            ? ['cliente' => $clienteLinkId]
            : [];

        $sidebarClasses = 'custom-sidebar-right';
        if ($isSuperAdmin) {
            $sidebarClasses .= ' collapsed'; // super admins see it globally but collapsed by default
        }
    @endphp
    <aside class="{{ $sidebarClasses }}" id="sidebar-right">
        <div class="custom-logo-section custom-logo-section--right">
            <button class="custom-toggle-btn" id="toggle-btn-right" type="button" aria-label="Contraer menú del cliente">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <nav class="custom-menu-section">
            <h3 class="custom-menu-title">Menú</h3>
            <ul>
                @unless($isSuperAdmin)
                    <li class="{{ request()->routeIs('clientes.show') ? 'custom-active' : '' }}">
                        <i class="fa fa-home" aria-hidden="true"></i>
                        <a href="{{ $clienteLinkId ? route('clientes.show', ['cliente' => $clienteLinkId]) : '#' }}">
                            <span>Home</span>
                        </a>
                    </li>
                @endunless
                <li class="menu-divider" aria-hidden="true"></li>
                @if($isSuperAdmin)
                    <li class="{{ request()->routeIs('general_clientes') ? 'custom-active' : '' }}">
                        <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                        <a href="{{ route('general_clientes', $clientQueryParams) }}">
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="menu-divider" aria-hidden="true"></li>
                @endif
                <li class="{{ request()->routeIs('heatmap') ? 'custom-active' : '' }}">
                    <i class="fas fa-th-large" aria-hidden="true"></i>
                    <a href="{{ route('heatmap', $clientQueryParams) }}">
                        <span>Heat Map</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('benchmarking') ? 'custom-active' : '' }}">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    <a href="{{ route('benchmarking', $clientQueryParams) }}">
                        <span>Benchmarking</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('scatter') ? 'custom-active' : '' }}">
                    <i class="fas fa-braille" aria-hidden="true"></i>
                    <a href="{{ route('scatter', $clientQueryParams) }}">
                        <span>Dispersión</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('histogram') ? 'custom-active' : '' }}">
                    <i class="fas fa-chart-bar" aria-hidden="true"></i>
                    <a href="{{ route('histogram', $clientQueryParams) }}">
                        <span>Histograma</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('timeseries') ? 'custom-active' : '' }}">
                    <i class="fas fa-wave-square" aria-hidden="true"></i>
                    <a href="{{ route('timeseries', $clientQueryParams) }}">
                        <span>Serie temporal</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('anomaly') ? 'custom-active' : '' }}">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <a href="{{ route('anomaly', $clientQueryParams) }}">
                        <span>Detección de anomalías</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('forecast') ? 'custom-active' : '' }}">
                    <i class="fas fa-chart-area" aria-hidden="true"></i>
                    <a href="{{ route('forecast', $clientQueryParams) }}">
                        <span>Pronóstico de Consumo</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('energyflow') ? 'custom-active' : '' }}">
                    <i class="fas fa-bolt" aria-hidden="true"></i>
                    <a href="{{ route('energyflow', $clientQueryParams) }}">
                        <span>Energy Flow</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('reports') ? 'custom-active' : '' }}">
                    <i class="fas fa-file-alt" aria-hidden="true"></i>
                    <a href="{{ route('reports', $clientQueryParams) }}">
                        <span>Reportes automáticos</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('clientes.clidash') ? 'custom-active' : '' }}">
                    <i class="fas fa-coins" aria-hidden="true"></i>
                    <a href="{{ route('clientes.clidash', $clientQueryParams) }}">
                        <span>Finanzas</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('site_alerts') ? 'custom-active' : '' }}">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    <a href="{{ route('site_alerts', $clientQueryParams) }}">
                        <span>Alertas</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('tiggers') ? 'custom-active' : '' }}">
                    <i class="fas fa-bullseye" aria-hidden="true"></i>
                    <a href="{{ route('tiggers', $clientQueryParams) }}">
                        <span>Triggers</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('manage') ? 'custom-active' : '' }}">
                    <i class="fas fa-tools" aria-hidden="true"></i>
                    <a href="{{ route('manage', $clientQueryParams) }}">
                        <span>Manejo de Eventos</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('groups') ? 'custom-active' : '' }}">
                    <i class="fas fa-layer-group" aria-hidden="true"></i>
                    <a href="{{ route('groups', $clientQueryParams) }}">
                        <span>Grupos</span>
                    </a>
                </li>
            </ul>
        </nav>
        @if(!$isSuperAdmin)
            <div class="custom-logout">
                <form id="client-sidebar-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                <a href="#" onclick="event.preventDefault(); document.getElementById('client-sidebar-logout-form').submit();">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                    <span>Cerrar sesión</span>
                </a>
            </div>
        @endif
    </aside>
@endif
