@php
    $user = Auth::user();
    $isSuperAdmin = $user?->isSuperAdmin();
    $shouldShowClientSidebar = $user && (!$isSuperAdmin || ($isSuperAdmin && request()->routeIs('clientes.show')));
@endphp

@if($shouldShowClientSidebar)
    @php
        $routeClienteParam = request()->route('cliente');
        if ($routeClienteParam instanceof \App\Models\Cliente) {
            $routeClienteParam = $routeClienteParam->getKey();
        }
        $clienteLinkId = $routeClienteParam ?? $user?->cliente_id;
    @endphp
    <aside class="custom-sidebar-right" id="sidebar-right">
        <div class="custom-logo-section custom-logo-section--right">
            <button class="custom-toggle-btn" id="toggle-btn-right" type="button" aria-label="Contraer menú del cliente">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <nav class="custom-menu-section">
            <h3 class="custom-menu-title">Menú</h3>
            <ul>
                <li>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    @if($clienteLinkId)
                        <a href="{{ route('clientes.show', ['cliente' => $clienteLinkId]) }}">
                            <span>Información</span>
                        </a>
                    @else
                        <span>Información</span>
                    @endif
                </li>
                <li>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    <a href="{{ route('general_clientes') }}">
                        <span>Vista General</span>
                    </a>
                </li>
                <li>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    <a href="{{ route('visualize') }}">
                        <span>Energy Dashboard</span>
                    </a>
                </li>
                <li>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    <a href="{{ route('heatmap') }}">
                        <span>Heat Map</span>
                    </a>
                </li>
                <li>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    <a href="{{ route('benchmarking') }}">
                        <span>Benchmarking</span>
                    </a>
                </li>
                <li>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    <a href="{{ route('energyflow') }}">
                        <span>Energy Flow</span>
                    </a>
                </li>
                <li>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    <a href="{{ route('clientes.clidash') }}">
                        <span>Finanzas</span>
                    </a>
                </li>
                <li>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    <a href="{{ route('site_alerts') }}">
                        <span>Alertas</span>
                    </a>
                </li>
                <li>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    <a href="{{ route('tiggers') }}">
                        <span>Triggers</span>
                    </a>
                </li>
                <li>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    <a href="{{ route('manage') }}">
                        <span>Manejo de Eventos</span>
                    </a>
                </li>
                <li>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    <a href="{{ route('groups') }}">
                        <span>Grupos</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
@endif
