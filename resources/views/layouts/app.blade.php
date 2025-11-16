<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <!-- Estilos -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Iconos -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    
    @php
        $context = $authContext ?? [
            'isAuthenticated' => false,
            'user' => null,
            'abilities' => [
                'canViewAllSites' => false,
            ],
        ];
        $currentUser = Auth::user();
        $isSuperAdmin = session('is_super_admin', (int) ($currentUser?->cliente_id ?? -1) === 0);
    @endphp
    <script>
        window.App = @json($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    </script>
    @stack('head')
    @vite(['resources/js/app.js', 'resources/css/app.css'])
</head>

<body>
    <div class="custom-container">
        <!-- Sidebar -->
        @if($isSuperAdmin)
        <div class="custom-sidebar" id="sidebar">
            <!-- Logo y botón para minimizar -->
            <div class="custom-logo-section">
                <img src="{{ asset('images/ma_logo_bab.png') }}" alt="Logo" class="custom-logo">
                <button class="custom-toggle-btn" id="toggle-btn" aria-label="Contraer menú">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>

            <!-- Menú -->
            <div class="custom-menu-section">
                <h3 class="custom-menu-title">General</h3>
                
                <ul>
                    <li class="mcst {{ request()->routeIs('home') ? 'custom-active' : '' }}">
                        <i class="fa fa-home" aria-hidden="true"></i>
                        <a href="{{ route('home') }}" style="text-decoration: none; color: inherit;">
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="mcst {{ request()->routeIs('general_clientes*') ? 'custom-active' : '' }}">
                        <i class="fa fa-th-large" aria-hidden="true"></i>
                        <a href="{{ route('general_clientes') }}" style="text-decoration: none; color: inherit;">
                            <span>Dashboard</span>
                        </a>
                    </li>
                </ul>
                @if(auth()->check() && auth()->user()->role === 'admin')
                    <h3 class="custom-menu-title">Gestión de Clientes</h3>
                    <ul>
                        <li class="mcst {{ request()->routeIs('clientes.*') ? 'custom-active' : '' }}">
                            <i class="fas fa-user"></i>
                            <a href="{{ route('clientes.index') }}" style="text-decoration: none; color: inherit;">
                                <span>Clientes</span>
                            </a>
                        </li>
                    </ul>
                @endif

                @if(auth()->check() && auth()->user()->role === 'normal' && auth()->user()->cliente)
                    <h3 class="custom-menu-title">Mi perfil</h3>
                    <ul>
                        <li class="mcst {{ request()->routeIs('mi-perfil') ? 'custom-active' : '' }}">
                            <i class="fas fa-user"></i>
                            <a href="{{ route('mi-perfil') }}" style="text-decoration: none; color: inherit;">
                                <span>Perfil</span>
                            </a>
                        </li>
                    </ul>
                @endif

                <h3 class="custom-menu-title">Datos y Monitoreo</h3>
                <ul>
                    <li class="mcst {{ request()->routeIs('vincular_sensores') ? 'custom-active' : '' }}">
                        <i class="bi bi-radar"></i>
                        <a href="{{route('vincular_sensores')}}" style="text-decoration: none; color: inherit;">
                            <span>Vinculación sensores</span>
                        </a>
                    </li>
                    <li class="mcst {{ request()->routeIs('clientes.clidash') ? 'custom-active' : '' }}">
                        <i class="bi bi-coin"></i>
                        <a href="{{ route('clientes.clidash') }}" style="text-decoration: none; color: inherit;">
                            <span>Costos y Facturación</span>
                        </a>
                    </li>
                    <li class="mcst">
                        <i class="bi bi-file-earmark-medical"></i>
                        <a href="" style="text-decoration: none; color: inherit;">
                            <span>Reportes automáticos</span>
                        </a>
                    </li>
                    <li class="mcst {{ request()->routeIs('site_alerts') ? 'custom-active' : '' }}">
                         <i class="fa fa-bell" aria-hidden="true"></i>
                        <a href="{{route('site_alerts')}}" style="text-decoration: none; color: inherit;">
                           
                            <span>Alertas del sistema</span>
                        </a>
                    </li>
                </ul>

                <h3 class="custom-menu-title">Datos de Proveedores</h3>
                <ul>
                    <li class="mcst {{ request()->routeIs('datos_cfe') ? 'custom-active' : '' }}">
                        <i class="bi bi-database"></i>
                        <a href="{{route('datos_cfe')}}" style="text-decoration: none; color: inherit;">
                            <span>Datos CFE</span>
                        </a>
                    </li>
                     <!--
                    <li class="mcst">
                        <i class="fas fa-chart-bar"></i>
                        <a href="{{ route('mediciones.index') }}" style="text-decoration: none; color: inherit;">
                            <span>Datos PMZ</span>
                        </a>
                    </li>
                    <li class="mcst">
                        <i class="fas fa-cube"></i>
                        <a href="{{ route('areas_carga.index') }}" style="text-decoration: none; color: inherit;">
                            <span>Areas de Carga</span>
                        </a>
                    </li>
                    -->
                   
                    <li class="mcst {{ request()->routeIs('tarifas.*') ? 'custom-active' : '' }}">
                        <i class="bi bi-geo-alt"></i>
                        <a href="{{ route('tarifas.index') }}" style="text-decoration: none; color: inherit;">
                            <span>Tarifas</span>
                        </a>
                    </li>

                    <!--
                    <li class="mcst">
                        <i class="fas fa-cube"></i>
                        <a href="{{ route('usuarios') }}" style="text-decoration: none; color: inherit;">
                            <span>Mediciones</span>
                        </a>
                    </li>
                    <li class="mcst">
                        <i class="fas fa-cube"></i>
                        <a href="{{ route('inicio') }}" style="text-decoration: none; color: inherit;">
                            <span>Mediciones</span>
                        </a>
                    </li>
                    <li class="mcst">
                        <i class="fas fa-cube"></i>
                        <a href="{{ route('panels.index') }}" style="text-decoration: none; color: inherit;">
                            <span>Panels</span>
                        </a>
                    </li>
                    -->
                </ul>

                <h3 class="custom-menu-title">Sistema</h3>
                <ul>
                    <li class="mcst {{ request()->routeIs('config') ? 'custom-active' : '' }}">
                        <i class="fas fa-cog"></i>
                        <a href="{{ route('config') }}" style="text-decoration: none; color: inherit;">
                            <span>Configuración</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Cerrar sesión -->
            <div class="custom-logout">
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>
        </div>
        @endif

        <!-- Contenido principal -->
        <div class="custom-main-content">
            <!-- Navbar -->
            <header class="custom-navbar">
                <div class="custom-navbar-right">
                    <div class="custom-clock" id="time"></div>
                    <div class="custom-notification-icon" id="notification-icon">
                        <i class="fas fa-bell"></i>
                        <div class="custom-notifications" id="notifications">
                            <p>No new notifications</p>
                        </div>
                    </div>
                    <div class="custom-avatar" id="avatar">
                        <img src="{{ Auth::user()->profile_image 
                            ? asset('storage/' . Auth::user()->profile_image) 
                            : asset('images/mest.jpeg') }}" alt="Large User Avatar">
                        <div class="custom-profile-menu" id="profile-menu">
                            <div class="custom-profile-header">
                                <img src="{{ Auth::user()->profile_image 
                                    ? asset('storage/' . Auth::user()->profile_image) 
                                    : asset('images/mest.jpeg') }}" alt="Large User Avatar">
                                <h3>{{ Auth::user()->name }}</h3>
                                <p>{{ Auth::user()->email }}</p>
                            </div>
                            <div class="custom-profile-actions">
                                <a href="{{ route('profile.edit') }}">
                                    <i class="fas fa-user"></i>
                                    Editar perfil
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="custom-logout-btn">
                                        <i class="fas fa-sign-out-alt"></i>
                                        Cerrar sesión
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <span id="time"></span>
                </div>
            </header>
            
            <!-- Contenido dinámico -->
            <main class="custom-content">
                @yield('content')
            </main>
        </div>

        @include('layouts.partials.client-sidebar')
    </div>

    <!-- Scripts -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggle-btn');
        if (toggleBtn && sidebar) {
            const toggleIcon = toggleBtn.querySelector('i');
            const syncIcon = () => {
                if (!toggleIcon) {
                    return;
                }
                toggleIcon.classList.toggle('fa-chevron-left', !sidebar.classList.contains('collapsed'));
                toggleIcon.classList.toggle('fa-chevron-right', sidebar.classList.contains('collapsed'));
            };

            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                syncIcon();
            });

            syncIcon();
        }

        const clockLocale = 'es-MX';
        const timeElement = document.getElementById('time');
        const dateFormatter = typeof Intl !== 'undefined'
            ? new Intl.DateTimeFormat(clockLocale, {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            })
            : null;

        function formatTime(now) {
            if (typeof Intl === 'undefined') {
                return now.toTimeString().split(' ')[0];
            }
            try {
                return new Intl.DateTimeFormat(clockLocale, {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    timeZoneName: 'short',
                }).format(now);
            } catch (_) {
                return new Intl.DateTimeFormat(clockLocale, {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                }).format(now);
            }
        }

        function capitalise(text) {
            return text ? text.charAt(0).toUpperCase() + text.slice(1) : '';
        }

        function updateTime() {
            if (!timeElement) {
                return;
            }
            const now = new Date();
            const fecha = dateFormatter ? capitalise(dateFormatter.format(now)) : now.toDateString();
            const hora = formatTime(now);
            timeElement.textContent = `${fecha} · ${hora}`;
        }

        updateTime();
        setInterval(updateTime, 1000);

        const avatar = document.getElementById('avatar');
        const profileMenu = document.getElementById('profile-menu');
        if (avatar && profileMenu) {
            avatar.addEventListener('click', function() {
                profileMenu.style.display = (profileMenu.style.display === 'block') ? 'none' : 'block';
            });
        }

        const notificationIcon = document.getElementById('notification-icon');
        const notifications = document.getElementById('notifications');
        if (notificationIcon && notifications) {
            notificationIcon.addEventListener('click', function() {
                notifications.style.display = (notifications.style.display === 'block') ? 'none' : 'block';
            });
        }

        // Cerrar el menú de perfil y notificaciones al hacer clic fuera
        window.addEventListener('click', function(event) {
            if (profileMenu && !event.target.closest('#avatar')) {
                profileMenu.style.display = 'none';
            }
            if (notifications && !event.target.closest('#notification-icon')) {
                notifications.style.display = 'none';
            }
        });

        const sidebarRight = document.getElementById('sidebar-right');
        const toggleBtnRight = document.getElementById('toggle-btn-right');
        if (toggleBtnRight && sidebarRight) {
            const rightToggleIcon = toggleBtnRight.querySelector('i');
            const syncRightIcon = () => {
                if (!rightToggleIcon) {
                    return;
                }
                const isCollapsed = sidebarRight.classList.contains('collapsed');
                rightToggleIcon.classList.toggle('fa-chevron-left', isCollapsed);
                rightToggleIcon.classList.toggle('fa-chevron-right', !isCollapsed);
            };

            toggleBtnRight.addEventListener('click', () => {
                sidebarRight.classList.toggle('collapsed');
                syncRightIcon();
            });

            syncRightIcon();
        }
    </script>
</body>

</html>
