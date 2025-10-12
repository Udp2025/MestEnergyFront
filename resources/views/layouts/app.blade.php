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
    @endphp
    <script>
        window.App = @json($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    </script>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
</head>

<body>
    <div class="custom-container">
        <!-- Sidebar -->
        <div class="custom-sidebar" id="sidebar">
            <!-- Logo y botón para minimizar -->
            <div class="custom-logo-section">
                <img src="{{ asset('images/ma_logo_bab.png') }}" alt="Logo" class="custom-logo">
                <button class="custom-toggle-btn" id="toggle-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <!-- Menú -->
            <div class="custom-menu-section">
                <h3 class="custom-menu-title">DASHBOARD GENERAL</h3>
                
                <ul>
                    <li class="custom-active mcst">
                        <i class="fa fa-th-large" aria-hidden="true"></i>
                        <a href="{{ route('dashboard') }}" style="text-decoration: none; color: inherit;">
                            
                            <span>Vista General</span>
                        </a>
                    </li>
                </ul>
                <ul>
                   <li class="mcst">
                         <i class="fa fa-bell" aria-hidden="true"></i>
                        <a href="{{route('site_alerts')}}" style="text-decoration: none; color: inherit;">
                           
                            <span>Alertas del sistema</span>
                        </a>
                    </li>
                </ul>
                @if(auth()->check() && auth()->user()->role === 'admin')
                    <h3 class="custom-menu-title">GESTIÓN DE CLIENTES</h3>
                    <ul>
                        <li class="mcst">
                            <i class="fas fa-user"></i>
                            <a href="{{ route('clientes.index') }}" style="text-decoration: none; color: inherit;">
                                <span>Clientes</span>
                            </a>
                        </li>
                    </ul>
                    <ul>
                        <li class="mcst">
                            <i class="bi bi-geo-alt"></i>
                            <a href="{{ route('clientes.index') }}" style="text-decoration: none; color: inherit;">
                                <span>Locaciones</span>
                            </a>
                        </li>
                    </ul>
                    <ul>
                        <li class="mcst">
                            <i class="bi bi-crosshair"></i>
                            <a href="{{ route('clientes.index') }}" style="text-decoration: none; color: inherit;">
                                <span>Áreas / Zonas</span>
                            </a>
                        </li>
                    </ul>
                @endif

                @if(auth()->check() && auth()->user()->role === 'normal' && auth()->user()->cliente)
                    <h3 class="custom-menu-title">Mi perfil</h3>
                    <ul>
                        <li class="mcst">
                            <i class="fas fa-user"></i>
                            <a href="{{ route('mi-perfil') }}" style="text-decoration: none; color: inherit;">
                                <span>Perfil</span>
                            </a>
                        </li>
                    </ul>
                @endif

                <h3 class="custom-menu-title">DATOS y MONITOREO</h3>
                <ul>
                    <li class="mcst">
                        <i class="bi bi-radar"></i>
                        <a href="" style="text-decoration: none; color: inherit;">
                            <span>Sensores / Medidores</span>
                        </a>
                    </li>
                    <li class="mcst">
                        <i class="bi bi-graph-up"></i>
                        <a href="" style="text-decoration: none; color: inherit;">
                            <span>Consumo Energético</span>
                        </a>
                    </li>
                    <li class="mcst">
                        <i class="bi bi-coin"></i>
                        <a href="" style="text-decoration: none; color: inherit;">
                            <span>Pronostico de Consumo</span>
                        </a>
                    </li>
                    <li class="mcst">
                        <i class="bi bi-file-earmark-medical"></i>
                        <a href="" style="text-decoration: none; color: inherit;">
                            <span>Costos Estimados / Facturación</span>
                        </a>
                    </li>
                    <li class="mcst">
                        <i class="bi bi-card-heading"></i>
                        <a href="" style="text-decoration: none; color: inherit;">
                            <span>Reportes Automáticos</span>
                        </a>
                    </li>
                </ul>

                <h3 class="custom-menu-title">CONFIGURACION DE INPUTS</h3>
                <ul>
                    <li class="mcst">
                        <i class="bi bi-database"></i>
                        <a href="{{ route('clientes.index') }}" style="text-decoration: none; color: inherit;">
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
                   
                    <li class="mcst">
                        <i class="bi bi-database"></i>
                        <a href="{{ route('tarifas.index') }}" style="text-decoration: none; color: inherit;">
                            <span>Fixed Inputs</span>
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
                    <li class="mcst">
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

        <!-- Contenido principal -->
        <div class="custom-main-content">
            <!-- Navbar -->
            <header class="custom-navbar">
                <div class="custom-search-bar-container">
                    <input type="text" class="custom-search-bar" placeholder="Buscar aquí...">
                    <button class="custom-search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="custom-navbar-right">
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
    </div>

    <!-- Scripts -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggle-btn');
        const logoText = document.getElementById('logo-text');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            logoText.classList.toggle('hidden');
        });

        function updateTime() {
            const now = new Date();
            document.getElementById('time').textContent = now.toLocaleTimeString();
        }
        setInterval(updateTime, 1000);

        document.getElementById('avatar').addEventListener('click', function() {
            var profileMenu = document.getElementById('profile-menu');
            profileMenu.style.display = (profileMenu.style.display === 'block') ? 'none' : 'block';
        });

        document.getElementById('notification-icon').addEventListener('click', function() {
            var notifications = document.getElementById('notifications');
            notifications.style.display = (notifications.style.display === 'block') ? 'none' : 'block';
        });

        // Cerrar el menú de perfil y notificaciones al hacer clic fuera
        window.addEventListener('click', function(event) {
            if (!event.target.closest('#avatar')) {
                document.getElementById('profile-menu').style.display = 'none';
            }
            if (!event.target.closest('#notification-icon')) {
                document.getElementById('notifications').style.display = 'none';
            }
        });
    </script>
</body>

</html>
